<?php
namespace HBC_POD_LEDGER;

class Ledger {
    
    /**
     * Create Scan 1 (Seller Initiation) entry
     */
    public static function initiate($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        // Hash device and identifier
        $seller_device_hash = Db::hash_device_id($data['seller_device_id']);
        $buyer_identifier_hash = Db::hash_identifier($data['buyer_identifier']);
        
        // Get allocations
        $allocations = self::get_default_allocations();
        
        // Create audit trail
        $audit = array(
            array(
                'event' => 'INITIATED',
                'timestamp' => current_time('mysql'),
                'actor' => 'seller',
                'device_hash' => $seller_device_hash,
                'data' => array(
                    'voucher_id' => $data['voucher_id'],
                    'buyer_identifier_hash' => $buyer_identifier_hash,
                )
            )
        );
        
        $insert_data = array(
            'voucher_id' => sanitize_text_field($data['voucher_id']),
            'status' => 'INITIATED',
            'pledge_type' => 'seller_pledge', // Seller issues pledge, doesn't earn
            'seller_device_hash' => $seller_device_hash,
            'buyer_identifier_hash' => $buyer_identifier_hash,
            'order_ref' => isset($data['order_ref']) ? sanitize_text_field($data['order_ref']) : null,
            'initiated_at' => current_time('mysql'),
            'lat_init' => isset($data['lat']) ? floatval($data['lat']) : null,
            'lng_init' => isset($data['lng']) ? floatval($data['lng']) : null,
            'pledge_total' => get_option('hbc_pledge_total', '10.30'),
            'allocations_json' => json_encode($allocations),
            'branch' => isset($data['branch']) ? sanitize_text_field($data['branch']) : null,
            'buyer_poc_id' => isset($data['buyer_poc_id']) ? sanitize_text_field($data['buyer_poc_id']) : null,
            'seller_poc_id' => isset($data['seller_poc_id']) ? sanitize_text_field($data['seller_poc_id']) : null,
            'audit_json' => json_encode($audit),
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create ledger entry', array('status' => 500));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Create Scan 2 (Buyer Acceptance) entry
     */
    public static function confirm($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        // Find existing entry by voucher_id
        $voucher_id = sanitize_text_field($data['voucher_id']);
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE voucher_id = %s AND status = 'INITIATED' ORDER BY id DESC LIMIT 1",
            $voucher_id
        ));
        
        if (!$entry) {
            return new \WP_Error('not_found', 'No matching INITIATED entry found for voucher_id', array('status' => 404));
        }
        
        // Hash buyer device
        $buyer_device_hash = Db::hash_device_id($data['buyer_device_id']);
        
        // Calculate maturity dates
        $maturity_min_days = intval(get_option('hbc_maturity_min_days', 56));
        $maturity_max_days = intval(get_option('hbc_maturity_max_days', 84));
        $confirmed_at = current_time('mysql');
        $maturity_date = date('Y-m-d H:i:s', strtotime($confirmed_at . " +{$maturity_min_days} days"));
        $mature_by = date('Y-m-d H:i:s', strtotime($confirmed_at . " +{$maturity_max_days} days"));
        
        // Get seller device info to check POC status
        $seller_device_hash = $entry->seller_device_hash;
        $is_in_active_poc = self::isSellerInActivePOC($seller_device_hash);
        
        // Recalculate allocations with routing logic
        $allocations = json_decode($entry->allocations_json, true);
        if (!is_array($allocations)) {
            $allocations = self::get_default_allocations();
        }
        
        // Apply routing logic for $0.40
        $routing_note = '';
        if ($is_in_active_poc) {
            // Active POC: $0.40 to group pool, $0.10 to treasury
            $allocations['patronage_group_pool'] = 0.40;
            $allocations['patronage_treasury_reserve'] = 0.10;
            $routing_note = 'Active POC: $0.40 to group bonus pool';
            
            // Add to group bonus pool
            self::addToGroupBonusPool($entry->seller_poc_id, $entry->id, 0.40, $seller_device_hash);
        } else {
            // Inactive POC: $0.40 to branch treasury reserves
            $allocations['patronage_treasury_reserve'] = 0.50; // $0.40 + $0.10
            $routing_note = 'Inactive POC: $0.40 to branch treasury reserves';
        }
        
        // Update audit trail (append-only)
        $audit = json_decode($entry->audit_json, true);
        if (!is_array($audit)) {
            $audit = array();
        }
        $audit[] = array(
            'event' => 'CONFIRMED',
            'timestamp' => $confirmed_at,
            'actor' => 'buyer',
            'device_hash' => $buyer_device_hash,
            'data' => array(
                'confirm_delivery' => true,
                'routing' => $routing_note,
            )
        );
        
        // Get seller-buyer assignment info
        $seller_coach = self::getSellerCoach($buyer_device_hash);
        $buyer_coach = self::getBuyerCoach($seller_device_hash);
        
        // Update entry
        $update_data = array(
            'status' => 'CONFIRMED',
            'buyer_device_hash' => $buyer_device_hash,
            'confirmed_at' => $confirmed_at,
            'lat_conf' => isset($data['lat']) ? floatval($data['lat']) : null,
            'lng_conf' => isset($data['lng']) ? floatval($data['lng']) : null,
            'maturity_date' => $maturity_date,
            'mature_by' => $mature_by,
            'allocations_json' => json_encode($allocations),
            'routing_note' => $routing_note,
            'seller_coach_device_hash' => $seller_coach,
            'buyer_coach_device_hash' => $buyer_coach,
            'audit_json' => json_encode($audit),
        );
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $entry->id),
            array('%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to confirm ledger entry', array('status' => 500));
        }
        
        return $entry->id;
    }
    
    /**
     * Check if seller is in an active POC
     */
    private static function isSellerInActivePOC($seller_device_hash) {
        global $wpdb;
        $assignments_table = $wpdb->prefix . 'hbc_seller_buyer_assignments';
        
        // Check if seller has active buyer assignments
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $assignments_table WHERE seller_device_hash = %s AND status = 'active'",
            $seller_device_hash
        ));
        
        return $count > 0;
    }
    
    /**
     * Get seller coach for a buyer
     */
    private static function getSellerCoach($buyer_device_hash) {
        global $wpdb;
        $assignments_table = $wpdb->prefix . 'hbc_seller_buyer_assignments';
        
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT seller_device_hash FROM $assignments_table WHERE buyer_device_hash = %s AND status = 'active' LIMIT 1",
            $buyer_device_hash
        ));
        
        return $assignment ? $assignment->seller_device_hash : null;
    }
    
    /**
     * Get buyer coach for a seller
     */
    private static function getBuyerCoach($seller_device_hash) {
        // For now, return null - this would be the seller's coach in their Seller POC
        return null;
    }
    
    /**
     * Add contribution to group bonus pool
     */
    private static function addToGroupBonusPool($poc_id, $ledger_entry_id, $amount, $contributor_device_hash) {
        global $wpdb;
        $pools_table = $wpdb->prefix . 'hbc_group_bonus_pools';
        $contributions_table = $wpdb->prefix . 'hbc_group_bonus_contributions';
        
        // Get or create pool
        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $pools_table WHERE poc_id = %s AND poc_type = 'seller' LIMIT 1",
            $poc_id
        ));
        
        if (!$pool) {
            // Create new pool
            $wpdb->insert($pools_table, array(
                'poc_id' => $poc_id,
                'poc_type' => 'seller',
                'pool_amount' => 0.00,
                'rotation_period' => 'monthly',
                'created_at' => current_time('mysql'),
            ));
            $pool_id = $wpdb->insert_id;
        } else {
            $pool_id = $pool->id;
        }
        
        // Add contribution
        $wpdb->insert($contributions_table, array(
            'pool_id' => $pool_id,
            'ledger_entry_id' => $ledger_entry_id,
            'contribution_amount' => $amount,
            'contributor_device_hash' => $contributor_device_hash,
            'contributed_at' => current_time('mysql'),
        ));
        
        // Update pool amount
        $wpdb->query($wpdb->prepare(
            "UPDATE $pools_table SET pool_amount = pool_amount + %f WHERE id = %d",
            $amount,
            $pool_id
        ));
    }
    
    /**
     * Create correction entry (append-only)
     */
    public static function create_correction($parent_entry_id, $correction_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        // Get parent entry
        $parent = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $parent_entry_id
        ));
        
        if (!$parent) {
            return new \WP_Error('not_found', 'Parent entry not found', array('status' => 404));
        }
        
        // Create audit trail
        $audit = array(
            array(
                'event' => 'CORRECTION',
                'timestamp' => current_time('mysql'),
                'actor' => 'system',
                'parent_entry_id' => $parent_entry_id,
                'data' => $correction_data
            )
        );
        
        // Create new entry based on parent
        $insert_data = array(
            'voucher_id' => $parent->voucher_id . '_corr_' . time(),
            'status' => 'CORRECTION',
            'seller_device_hash' => $parent->seller_device_hash,
            'buyer_device_hash' => $parent->buyer_device_hash,
            'buyer_identifier_hash' => $parent->buyer_identifier_hash,
            'order_ref' => $parent->order_ref,
            'initiated_at' => $parent->initiated_at,
            'confirmed_at' => $parent->confirmed_at,
            'lat_init' => $parent->lat_init,
            'lng_init' => $parent->lng_init,
            'lat_conf' => $parent->lat_conf,
            'lng_conf' => $parent->lng_conf,
            'pledge_total' => $parent->pledge_total,
            'allocations_json' => $parent->allocations_json,
            'branch' => $parent->branch,
            'buyer_poc_id' => $parent->buyer_poc_id,
            'seller_poc_id' => $parent->seller_poc_id,
            'maturity_date' => $parent->maturity_date,
            'mature_by' => $parent->mature_by,
            'parent_entry_id' => $parent_entry_id,
            'audit_json' => json_encode($audit),
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create correction entry', array('status' => 500));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get default allocations
     */
    private static function get_default_allocations() {
        return array(
            'buyer_rebate' => get_option('hbc_buyer_rebate', '5.00'),
            'social_impact' => get_option('hbc_social_impact', '4.00'),
            'patronage_total' => get_option('hbc_patronage_total', '1.00'),
            'patronage_individual' => get_option('hbc_patronage_individual', '0.50'),
            'patronage_group_pool' => get_option('hbc_patronage_group_pool', '0.40'),
            'patronage_treasury_reserve' => get_option('hbc_patronage_treasury_reserve', '0.10'),
        );
    }
}
