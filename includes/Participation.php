<?php
namespace HBC_POD_LEDGER;

class Participation {
    
    /**
     * Create participation record
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_participation';
        
        // Generate receipt hash
        $receipt_hash = self::generate_receipt_hash($data);
        
        // Validate and bound allocation preferences
        $allocation_pref = self::validate_allocation_preferences($data['allocation_preference'] ?? array());
        
        $insert_data = array(
            'device_hash' => sanitize_text_field($data['device_hash']),
            'session_id' => isset($data['session_id']) ? sanitize_text_field($data['session_id']) : wp_generate_password(32, false),
            'created_at' => current_time('mysql'),
            'branch_preference' => isset($data['branch_preference']) ? sanitize_text_field($data['branch_preference']) : null,
            'pledge_intent' => isset($data['pledge_intent']) ? sanitize_text_field($data['pledge_intent']) : null,
            'confirmation_flags_json' => isset($data['confirmation_flags']) ? json_encode($data['confirmation_flags']) : null,
            'allocation_preference_json' => json_encode($allocation_pref),
            'user_message' => isset($data['user_message']) ? sanitize_textarea_field($data['user_message']) : null,
            'receipt_hash' => $receipt_hash,
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create participation record', array('status' => 500));
        }
        
        return array(
            'id' => $wpdb->insert_id,
            'receipt_hash' => $receipt_hash,
        );
    }
    
    /**
     * Get participation by receipt hash
     */
    public static function get_by_receipt_hash($receipt_hash) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_participation';
        
        $participation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE receipt_hash = %s",
            $receipt_hash
        ));
        
        if (!$participation) {
            return null;
        }
        
        // Decode JSON fields
        if ($participation->confirmation_flags_json) {
            $participation->confirmation_flags = json_decode($participation->confirmation_flags_json, true);
        }
        if ($participation->allocation_preference_json) {
            $participation->allocation_preference = json_decode($participation->allocation_preference_json, true);
        }
        
        return $participation;
    }
    
    /**
     * Get aggregated participation data
     */
    public static function get_aggregate($range = 'today') {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_participation';
        
        // Calculate date range
        $date_condition = self::get_date_condition_sql($range);
        
        // Total participants
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $date_condition");
        
        // Branch preference distribution
        $branches = $wpdb->get_results(
            "SELECT branch_preference, COUNT(*) as count 
             FROM $table 
             WHERE $date_condition AND branch_preference IS NOT NULL 
             GROUP BY branch_preference"
        );
        $branch_dist = array();
        foreach ($branches as $branch) {
            $branch_dist[$branch->branch_preference] = intval($branch->count);
        }
        
        // Pledge intent distribution
        $intents = $wpdb->get_results(
            "SELECT pledge_intent, COUNT(*) as count 
             FROM $table 
             WHERE $date_condition AND pledge_intent IS NOT NULL 
             GROUP BY pledge_intent"
        );
        $intent_dist = array();
        foreach ($intents as $intent) {
            $intent_dist[$intent->pledge_intent] = intval($intent->count);
        }
        
        // Top confirmation flags
        $flags = $wpdb->get_results(
            "SELECT confirmation_flags_json FROM $table WHERE $date_condition AND confirmation_flags_json IS NOT NULL"
        );
        $flag_counts = array();
        foreach ($flags as $flag_row) {
            $flag_data = json_decode($flag_row->confirmation_flags_json, true);
            if (is_array($flag_data)) {
                foreach ($flag_data as $key => $value) {
                    if ($value === true) {
                        $flag_counts[$key] = ($flag_counts[$key] ?? 0) + 1;
                    }
                }
            }
        }
        arsort($flag_counts);
        
        return array(
            'total_participants' => intval($total),
            'branch_distribution' => $branch_dist,
            'pledge_intent_distribution' => $intent_dist,
            'top_confirmation_flags' => array_slice($flag_counts, 0, 10, true),
            'range' => $range,
        );
    }
    
    /**
     * Validate and bound allocation preferences
     */
    private static function validate_allocation_preferences($prefs) {
        $default_individual = floatval(get_option('hbc_patronage_individual', '0.50'));
        $default_group = floatval(get_option('hbc_patronage_group_pool', '0.40'));
        $treasury = 0.10; // Fixed
        
        $individual = isset($prefs['patronage_individual']) ? floatval($prefs['patronage_individual']) : $default_individual;
        $group = isset($prefs['patronage_group_pool']) ? floatval($prefs['patronage_group_pool']) : $default_group;
        
        // Enforce bounds
        $individual = max(0.40, min(0.60, $individual));
        $group = max(0.30, min(0.50, $group));
        
        // Ensure they sum to 1.00 with treasury
        $total = $individual + $group + $treasury;
        if ($total != 1.00) {
            // Normalize
            $scale = 0.90 / ($individual + $group); // 0.90 = 1.00 - 0.10 (treasury)
            $individual = round($individual * $scale, 2);
            $group = round($group * $scale, 2);
        }
        
        return array(
            'patronage_individual' => number_format($individual, 2, '.', ''),
            'patronage_group_pool' => number_format($group, 2, '.', ''),
            'patronage_treasury_reserve' => '0.10',
        );
    }
    
    /**
     * Generate receipt hash
     */
    private static function generate_receipt_hash($data) {
        $input = $data['device_hash'] . current_time('mysql') . wp_generate_password(16, false);
        return hash('sha256', $input);
    }
    
    /**
     * Get date condition SQL for range (returns prepared SQL string)
     */
    private static function get_date_condition_sql($range) {
        global $wpdb;
        
        switch ($range) {
            case '7d':
                $date = date('Y-m-d H:i:s', strtotime('-7 days'));
                return $wpdb->prepare("created_at >= %s", $date);
            case '30d':
                $date = date('Y-m-d H:i:s', strtotime('-30 days'));
                return $wpdb->prepare("created_at >= %s", $date);
            case 'today':
            default:
                $date = date('Y-m-d 00:00:00');
                return $wpdb->prepare("created_at >= %s", $date);
        }
    }
    
    /**
     * Format XP in scientific notation
     */
    public static function format_xp_scientific($xp_value) {
        if (!$xp_value || $xp_value == 0) {
            return '0 XP';
        }
        
        $xp_num = floatval($xp_value);
        if ($xp_num == 0) {
            return '0 XP';
        }
        
        $scientific = $xp_num.toExponential(2);
        $parts = explode('e', $scientific);
        $base = floatval($parts[0]);
        $exponent = intval($parts[1]);
        
        $base_display = ($base % 1 === 0) ? number_format($base, 0) : number_format($base, 2);
        
        return $base_display . ' × 10<sup>' . $exponent . '</sup> XP';
    }
}
