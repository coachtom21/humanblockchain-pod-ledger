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
     */
    public static function assign_buyer_poc($lat, $lng, $timestamp) {
        // Round to 0.1 degree grid
        $grid_lat = round($lat * 10) / 10;
        $grid_lng = round($lng * 10) / 10;
        
        // Day window (YYYY-MM-DD)
        $day_window = date('Y-m-d', strtotime($timestamp));
        
        // Create cluster ID
        $cluster_id = sprintf('buyer_%s_%s_%s', $grid_lat, $grid_lng, $day_window);
        
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
