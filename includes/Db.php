<?php
namespace HBC_POD_LEDGER;

class Db {
    
    protected static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Devices table
        $devices_table = $wpdb->prefix . 'hbc_devices';
        $devices_sql = "CREATE TABLE IF NOT EXISTS $devices_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            device_hash char(64) NOT NULL,
            platform varchar(50) NOT NULL,
            tz varchar(50) DEFAULT NULL,
            lat decimal(10,8) DEFAULT NULL,
            lng decimal(11,8) DEFAULT NULL,
            registered_at datetime NOT NULL,
            branch varchar(50) DEFAULT NULL,
            buyer_poc_id varchar(100) DEFAULT NULL,
            seller_poc_id varchar(100) DEFAULT NULL,
            meta longtext DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY device_hash (device_hash),
            KEY idx_branch (branch),
            KEY idx_buyer_poc (buyer_poc_id),
            KEY idx_seller_poc (seller_poc_id)
        ) $charset_collate;";
        
        // Ledger table
        $ledger_table = $wpdb->prefix . 'hbc_ledger';
        $ledger_sql = "CREATE TABLE IF NOT EXISTS $ledger_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            voucher_id varchar(100) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'INITIATED',
            seller_device_hash char(64) NOT NULL,
            buyer_device_hash char(64) DEFAULT NULL,
            buyer_identifier_hash char(64) DEFAULT NULL,
            order_ref varchar(255) DEFAULT NULL,
            initiated_at datetime NOT NULL,
            confirmed_at datetime DEFAULT NULL,
            lat_init decimal(10,8) DEFAULT NULL,
            lng_init decimal(11,8) DEFAULT NULL,
            lat_conf decimal(10,8) DEFAULT NULL,
            lng_conf decimal(11,8) DEFAULT NULL,
            pledge_total decimal(10,2) DEFAULT NULL,
            allocations_json longtext DEFAULT NULL,
            branch varchar(50) DEFAULT NULL,
            buyer_poc_id varchar(100) DEFAULT NULL,
            seller_poc_id varchar(100) DEFAULT NULL,
            maturity_date datetime DEFAULT NULL,
            mature_by datetime DEFAULT NULL,
            parent_entry_id bigint(20) UNSIGNED DEFAULT NULL,
            audit_json longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_voucher_id (voucher_id),
            KEY idx_status (status),
            KEY idx_seller_device (seller_device_hash),
            KEY idx_buyer_device (buyer_device_hash),
            KEY idx_buyer_identifier (buyer_identifier_hash),
            KEY idx_maturity_date (maturity_date),
            KEY idx_parent_entry (parent_entry_id)
        ) $charset_collate;";
        
        // Reconciliation table
        $reconciliation_table = $wpdb->prefix . 'hbc_reconciliation';
        $reconciliation_sql = "CREATE TABLE IF NOT EXISTS $reconciliation_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recon_type varchar(50) NOT NULL,
            period_start datetime NOT NULL,
            period_end datetime NOT NULL,
            totals_json longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_recon_type (recon_type),
            KEY idx_period_start (period_start)
        ) $charset_collate;";
        
        // Participation table
        $participation_table = $wpdb->prefix . 'hbc_participation';
        $participation_sql = "CREATE TABLE IF NOT EXISTS $participation_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            device_hash char(64) NOT NULL,
            session_id varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL,
            branch_preference varchar(50) DEFAULT NULL,
            pledge_intent varchar(50) DEFAULT NULL,
            confirmation_flags_json longtext DEFAULT NULL,
            allocation_preference_json longtext DEFAULT NULL,
            user_message text DEFAULT NULL,
            receipt_hash char(64) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_device_hash (device_hash),
            KEY idx_session_id (session_id),
            KEY idx_receipt_hash (receipt_hash),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($devices_sql);
        dbDelta($ledger_sql);
        dbDelta($reconciliation_sql);
        dbDelta($participation_sql);
    }
    
    public static function hash_device_id($device_id) {
        return hash('sha256', $device_id . wp_salt());
    }
    
    public static function hash_identifier($identifier) {
        return hash('sha256', $identifier . wp_salt());
    }
}
