<?php
namespace HBC_POD_LEDGER;

class Activator {
    
    public static function activate() {
        // Create database tables
        Db::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Schedule cron jobs
        Reconciliation::schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function set_default_options() {
        $defaults = array(
            'hbc_pledge_total' => '10.30',
            'hbc_buyer_rebate' => '5.00',
            'hbc_social_impact' => '4.00',
            'hbc_patronage_total' => '1.00',
            'hbc_patronage_individual' => '0.50',
            'hbc_patronage_group_pool' => '0.40',
            'hbc_patronage_treasury_reserve' => '0.10',
            'hbc_maturity_min_days' => 56,
            'hbc_maturity_max_days' => 84,
            'hbc_api_key' => wp_generate_password(32, false),
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
