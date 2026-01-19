<?php
namespace HBC_POD_LEDGER;

class Serendipity {
    
    private static $branches = array(
        'planning',
        'budget',
        'media',
        'distribution',
        'membership'
    );
    
    /**
     * Assign Peace Pentagon branch based on device hash
     */
    public static function assign_branch($device_hash) {
        $hash_int = hexdec(substr($device_hash, 0, 8));
        $index = $hash_int % count(self::$branches);
        return self::$branches[$index];
    }
    
    /**
     * Assign Buyer POC: cluster by rounded geo grid (0.1 degrees) + day window
     * Max 30 members per Buyer POC (25 buyers + 5 sellers)
     */
    public static function assign_buyer_poc($lat, $lng, $timestamp) {
        global $wpdb;
        
        // Round to 0.1 degree grid
        $grid_lat = round($lat * 10) / 10;
        $grid_lng = round($lng * 10) / 10;
        
        // Day window (YYYY-MM-DD)
        $day_window = date('Y-m-d', strtotime($timestamp));
        
        // Create cluster ID
        $cluster_id = sprintf('buyer_%s_%s_%s', $grid_lat, $grid_lng, $day_window);
        
        // Check if Buyer POC has 25 buyers (ready to bind to Seller POC)
        $devices_table = $wpdb->prefix . 'hbc_devices';
        $buyer_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $devices_table WHERE buyer_poc_id = %s",
            $cluster_id
        ));
        
        // If Buyer POC reaches 25 buyers, trigger binding to Seller POC
        if ($buyer_count >= 24) { // 24 because we're about to add one more
            // This will be handled after device registration
            // See bindBuyerPOCToSellerPOC() method
        }
        
        return $cluster_id;
    }
    
    /**
     * Assign Seller POC: hash device_id+timestamp to one of N global seller pools
     * Then group into 5-seller sets
     */
    public static function assign_seller_poc($device_hash, $timestamp) {
        // Number of global seller pools
        $num_pools = 100;
        
        // Hash device + timestamp
        $hash_input = $device_hash . $timestamp;
        $pool_hash = hash('sha256', $hash_input);
        $pool_index = hexdec(substr($pool_hash, 0, 8)) % $num_pools;
        
        // Group into 5-seller sets
        $set_number = floor($pool_index / 5);
        
        return sprintf('seller_pool_%d_set_%d', $pool_index, $set_number);
    }
    
    /**
     * Assign buyer to seller (5 buyers per seller)
     */
    public static function assignBuyerToSeller($buyer_device_hash, $buyer_poc_id, $seller_poc_id) {
        global $wpdb;
        $assignments_table = $wpdb->prefix . 'hbc_seller_buyer_assignments';
        $devices_table = $wpdb->prefix . 'hbc_devices';
        
        // Get all sellers in the Seller POC
        $sellers = $wpdb->get_results($wpdb->prepare(
            "SELECT device_hash, assigned_buyer_count FROM $devices_table 
             WHERE seller_poc_id = %s 
             ORDER BY assigned_buyer_count ASC, registered_at ASC",
            $seller_poc_id
        ));
        
        $assigned_seller = null;
        $assignment_index = 0;
        
        // Find seller with < 5 buyers
        foreach ($sellers as $seller) {
            if ($seller->assigned_buyer_count < 5) {
                $assigned_seller = $seller->device_hash;
                $assignment_index = intval($seller->assigned_buyer_count);
                break;
            }
        }
        
        // If all sellers have 5 buyers, we need a new Seller POC
        if (!$assigned_seller) {
            return new \WP_Error('poc_full', 'All sellers in POC have 5 buyers assigned. New Seller POC needed.', array('status' => 400));
        }
        
        // Create assignment
        $result = $wpdb->insert($assignments_table, array(
            'seller_device_hash' => $assigned_seller,
            'buyer_device_hash' => $buyer_device_hash,
            'seller_poc_id' => $seller_poc_id,
            'buyer_poc_id' => $buyer_poc_id,
            'assignment_index' => $assignment_index,
            'assigned_at' => current_time('mysql'),
            'status' => 'active',
        ));
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to assign buyer to seller', array('status' => 500));
        }
        
        // Update seller's assigned_buyer_count
        $wpdb->query($wpdb->prepare(
            "UPDATE $devices_table SET assigned_buyer_count = assigned_buyer_count + 1 WHERE device_hash = %s",
            $assigned_seller
        ));
        
        // Update buyer's assigned_seller_device_hash
        $wpdb->query($wpdb->prepare(
            "UPDATE $devices_table SET assigned_seller_device_hash = %s WHERE device_hash = %s",
            $assigned_seller,
            $buyer_device_hash
        ));
        
        return array(
            'seller_device_hash' => $assigned_seller,
            'assignment_index' => $assignment_index,
        );
    }
    
    /**
     * Bind Buyer POC (25 buyers) to Seller POC (5 sellers)
     * Triggered when Buyer POC reaches 25 buyers
     */
    public static function bindBuyerPOCToSellerPOC($buyer_poc_id) {
        global $wpdb;
        $devices_table = $wpdb->prefix . 'hbc_devices';
        $assignments_table = $wpdb->prefix . 'hbc_seller_buyer_assignments';
        
        // Verify Buyer POC has exactly 25 buyers
        $buyer_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $devices_table WHERE buyer_poc_id = %s",
            $buyer_poc_id
        ));
        
        if ($buyer_count < 25) {
            return new \WP_Error('insufficient_buyers', 'Buyer POC must have 25 buyers before binding', array('status' => 400));
        }
        
        // Get all buyers in this POC
        $buyers = $wpdb->get_results($wpdb->prepare(
            "SELECT device_hash, seller_poc_id FROM $devices_table WHERE buyer_poc_id = %s",
            $buyer_poc_id
        ));
        
        // Group buyers by their Seller POC
        $seller_poc_groups = array();
        foreach ($buyers as $buyer) {
            if ($buyer->seller_poc_id) {
                if (!isset($seller_poc_groups[$buyer->seller_poc_id])) {
                    $seller_poc_groups[$buyer->seller_poc_id] = array();
                }
                $seller_poc_groups[$buyer->seller_poc_id][] = $buyer->device_hash;
            }
        }
        
        // For each Seller POC, assign 5 buyers to each seller
        foreach ($seller_poc_groups as $seller_poc_id => $buyer_hashes) {
            // Get sellers in this Seller POC
            $sellers = $wpdb->get_results($wpdb->prepare(
                "SELECT device_hash FROM $devices_table WHERE seller_poc_id = %s ORDER BY registered_at ASC LIMIT 5",
                $seller_poc_id
            ));
            
            if (count($sellers) !== 5) {
                continue; // Skip if Seller POC doesn't have exactly 5 sellers
            }
            
            // Assign 5 buyers to each seller
            $buyer_index = 0;
            foreach ($sellers as $seller) {
                for ($i = 0; $i < 5 && $buyer_index < count($buyer_hashes); $i++) {
                    self::assignBuyerToSeller($buyer_hashes[$buyer_index], $buyer_poc_id, $seller_poc_id);
                    $buyer_index++;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Generate welcome copy string
     */
    public static function get_welcome_copy($branch, $buyer_poc_id, $seller_poc_id) {
        $branch_name = ucfirst($branch);
        return sprintf(
            'Welcome to the HumanBlockchain network. You have been assigned to the %s branch. Your local Buyer POC cluster is %s, and your global Seller POC group is %s. The Detente 2030 settlement moment is where pledges mature or reconcile into final truth.',
            $branch_name,
            $buyer_poc_id,
            $seller_poc_id
        );
    }
}
