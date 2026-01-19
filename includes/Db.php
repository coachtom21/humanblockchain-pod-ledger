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
        
        // Licensing table
        $licensing_table = $wpdb->prefix . 'hbc_licensing';
        $licensing_sql = "CREATE TABLE IF NOT EXISTS $licensing_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            device_hash char(64) NOT NULL,
            license_version varchar(20) NOT NULL,
            accepted_at datetime NOT NULL,
            lat_round decimal(8,3) DEFAULT NULL,
            lng_round decimal(9,3) DEFAULT NULL,
            branch varchar(50) DEFAULT NULL,
            buyer_poc_id varchar(100) DEFAULT NULL,
            seller_poc_id varchar(100) DEFAULT NULL,
            acceptance_hash char(64) NOT NULL,
            payload_json longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_device_hash (device_hash),
            UNIQUE KEY acceptance_hash (acceptance_hash),
            KEY idx_accepted_at (accepted_at)
        ) $charset_collate;";
        
        // Seller-Buyer Assignments table (5 buyers per seller)
        $assignments_table = $wpdb->prefix . 'hbc_seller_buyer_assignments';
        $assignments_sql = "CREATE TABLE IF NOT EXISTS $assignments_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            seller_device_hash char(64) NOT NULL,
            buyer_device_hash char(64) NOT NULL,
            seller_poc_id varchar(100) NOT NULL,
            buyer_poc_id varchar(100) NOT NULL,
            assignment_index tinyint(3) UNSIGNED NOT NULL,
            assigned_at datetime NOT NULL,
            status enum('active', 'inactive', 'rotated') DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY unique_seller_buyer (seller_device_hash, buyer_device_hash),
            KEY idx_seller (seller_device_hash),
            KEY idx_buyer (buyer_device_hash),
            KEY idx_seller_poc (seller_poc_id),
            KEY idx_buyer_poc (buyer_poc_id)
        ) $charset_collate;";
        
        // Group Bonus Pools table
        $bonus_pools_table = $wpdb->prefix . 'hbc_group_bonus_pools';
        $bonus_pools_sql = "CREATE TABLE IF NOT EXISTS $bonus_pools_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            poc_id varchar(100) NOT NULL,
            poc_type enum('buyer', 'seller') NOT NULL,
            pool_amount decimal(10,2) DEFAULT 0.00,
            rotation_period enum('monthly', 'quarterly', 'annual') DEFAULT 'monthly',
            last_rotated_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_poc (poc_id, poc_type)
        ) $charset_collate;";
        
        // Group Bonus Contributions table
        $bonus_contributions_table = $wpdb->prefix . 'hbc_group_bonus_contributions';
        $bonus_contributions_sql = "CREATE TABLE IF NOT EXISTS $bonus_contributions_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pool_id bigint(20) UNSIGNED NOT NULL,
            ledger_entry_id bigint(20) UNSIGNED NOT NULL,
            contribution_amount decimal(10,2) NOT NULL,
            contributor_device_hash char(64) NOT NULL,
            contributed_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_pool (pool_id),
            KEY idx_ledger (ledger_entry_id),
            KEY idx_contributor (contributor_device_hash)
        ) $charset_collate;";
        
        // Device Tranches table
        $tranches_table = $wpdb->prefix . 'hbc_device_tranches';
        $tranches_sql = "CREATE TABLE IF NOT EXISTS $tranches_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            device_hash char(64) NOT NULL,
            tranche_period_start datetime NOT NULL,
            tranche_period_end datetime NOT NULL,
            tranche_hash char(64) NOT NULL,
            receipts_total decimal(10,2) DEFAULT 0.00,
            obligations_total decimal(10,2) DEFAULT 0.00,
            net_position decimal(10,2) DEFAULT 0.00,
            entry_count int(10) UNSIGNED DEFAULT 0,
            closed_at datetime DEFAULT NULL,
            closed_by enum('vfn', 'pmg', 'system') DEFAULT 'system',
            status enum('open', 'closing', 'closed', 'reconciled') DEFAULT 'open',
            reconciliation_notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_device (device_hash),
            KEY idx_period (tranche_period_start, tranche_period_end),
            UNIQUE KEY tranche_hash (tranche_hash),
            KEY idx_status (status)
        ) $charset_collate;";
        
        // NWP Licenses table
        $nwp_licenses_table = $wpdb->prefix . 'hbc_nwp_licenses';
        $nwp_licenses_sql = "CREATE TABLE IF NOT EXISTS $nwp_licenses_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id varchar(100) NOT NULL,
            qrtiger_vcard_id varchar(255) NOT NULL,
            device_hash char(64) NOT NULL,
            discord_user_id varchar(255) DEFAULT NULL,
            license_status enum('active', 'suspended', 'expired', 'revoked') DEFAULT 'active',
            role_class enum('yamer', 'megavoter', 'patron') NOT NULL,
            poc_id varchar(100) DEFAULT NULL,
            issued_at datetime NOT NULL,
            expires_at datetime DEFAULT NULL,
            revoked_at datetime DEFAULT NULL,
            revocation_reason text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY license_id (license_id),
            KEY idx_device (device_hash),
            KEY idx_vcard (qrtiger_vcard_id),
            KEY idx_discord (discord_user_id),
            KEY idx_status (license_status)
        ) $charset_collate;";
        
        // Referrals table
        $referrals_table = $wpdb->prefix . 'hbc_referrals';
        $referrals_sql = "CREATE TABLE IF NOT EXISTS $referrals_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_device_hash char(64) NOT NULL,
            referred_device_hash char(64) NOT NULL,
            referrer_membership_tier enum('yamer', 'megavoter', 'patron') NOT NULL,
            referral_award_yam_amount bigint(20) UNSIGNED NOT NULL,
            referral_award_usd_equivalent decimal(10,2) NOT NULL,
            discord_invite_code varchar(255) DEFAULT NULL,
            discord_inviter_user_id varchar(255) DEFAULT NULL,
            referral_source enum('discord_invite', 'url_param', 'manual') DEFAULT 'url_param',
            awarded_at datetime DEFAULT NULL,
            status enum('pending', 'awarded', 'reconciled') DEFAULT 'pending',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_referrer (referrer_device_hash),
            KEY idx_referred (referred_device_hash),
            KEY idx_discord_invite (discord_invite_code)
        ) $charset_collate;";
        
        // Membership Pricing table
        $membership_pricing_table = $wpdb->prefix . 'hbc_membership_pricing';
        $membership_pricing_sql = "CREATE TABLE IF NOT EXISTS $membership_pricing_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            membership_tier enum('yamer', 'megavoter', 'patron') NOT NULL,
            annual_pledge decimal(10,2) DEFAULT NULL,
            monthly_pledge decimal(10,2) DEFAULT NULL,
            platform_contribution decimal(10,2) DEFAULT 0.00,
            is_free boolean DEFAULT FALSE,
            effective_date datetime NOT NULL,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY membership_tier (membership_tier)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($devices_sql);
        dbDelta($ledger_sql);
        dbDelta($reconciliation_sql);
        dbDelta($participation_sql);
        dbDelta($licensing_sql);
        dbDelta($assignments_sql);
        dbDelta($bonus_pools_sql);
        dbDelta($bonus_contributions_sql);
        dbDelta($tranches_sql);
        dbDelta($nwp_licenses_sql);
        dbDelta($referrals_sql);
        dbDelta($membership_pricing_sql);
        
        // Add new columns to existing tables
        self::add_missing_columns();
        
        // Initialize default membership pricing
        self::init_membership_pricing();
    }
    
    /**
     * Add missing columns to existing tables
     */
    private static function add_missing_columns() {
        global $wpdb;
        
        // Add columns to wp_hbc_devices
        $devices_table = $wpdb->prefix . 'hbc_devices';
        $devices_columns = $wpdb->get_col("DESC $devices_table");
        
        if (!in_array('discord_user_id', $devices_columns)) {
            $wpdb->query("ALTER TABLE $devices_table ADD COLUMN discord_user_id varchar(255) DEFAULT NULL AFTER seller_poc_id");
            $wpdb->query("ALTER TABLE $devices_table ADD COLUMN discord_username varchar(255) DEFAULT NULL AFTER discord_user_id");
            $wpdb->query("ALTER TABLE $devices_table ADD COLUMN discord_connected_at datetime DEFAULT NULL AFTER discord_username");
            $wpdb->query("ALTER TABLE $devices_table ADD COLUMN discord_invite_code varchar(255) DEFAULT NULL AFTER discord_connected_at");
            $wpdb->query("ALTER TABLE $devices_table ADD COLUMN assigned_seller_device_hash char(64) DEFAULT NULL AFTER seller_poc_id");
            $wpdb->query("ALTER TABLE $devices_table ADD COLUMN assigned_buyer_count tinyint(3) UNSIGNED DEFAULT 0 AFTER assigned_seller_device_hash");
            $wpdb->query("ALTER TABLE $devices_table ADD KEY idx_discord_user (discord_user_id)");
            $wpdb->query("ALTER TABLE $devices_table ADD KEY idx_assigned_seller (assigned_seller_device_hash)");
        }
        
        // Add columns to wp_hbc_ledger
        $ledger_table = $wpdb->prefix . 'hbc_ledger';
        $ledger_columns = $wpdb->get_col("DESC $ledger_table");
        
        if (!in_array('pledge_type', $ledger_columns)) {
            $wpdb->query("ALTER TABLE $ledger_table ADD COLUMN pledge_type enum('seller_pledge', 'buyer_rebate', 'patronage', 'social_impact', 'treasury_reserve') DEFAULT NULL AFTER status");
            $wpdb->query("ALTER TABLE $ledger_table ADD COLUMN membership_pledge_type enum('annual', 'monthly', 'delivery') DEFAULT NULL AFTER pledge_type");
            $wpdb->query("ALTER TABLE $ledger_table ADD COLUMN platform_contribution decimal(10,2) DEFAULT 0.00 AFTER allocations_json");
            $wpdb->query("ALTER TABLE $ledger_table ADD COLUMN seller_coach_device_hash char(64) DEFAULT NULL AFTER seller_poc_id");
            $wpdb->query("ALTER TABLE $ledger_table ADD COLUMN buyer_coach_device_hash char(64) DEFAULT NULL AFTER buyer_poc_id");
            $wpdb->query("ALTER TABLE $ledger_table ADD COLUMN routing_note text DEFAULT NULL AFTER platform_contribution");
        }
    }
    
    /**
     * Initialize default membership pricing
     */
    private static function init_membership_pricing() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_membership_pricing';
        
        // Check if data exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return; // Already initialized
        }
        
        // Insert default pricing
        $wpdb->insert($table, array(
            'membership_tier' => 'yamer',
            'annual_pledge' => null,
            'monthly_pledge' => null,
            'platform_contribution' => 0.00,
            'is_free' => true,
            'effective_date' => current_time('mysql'),
        ));
        
        $wpdb->insert($table, array(
            'membership_tier' => 'megavoter',
            'annual_pledge' => 12.00,
            'monthly_pledge' => null,
            'platform_contribution' => 0.00,
            'is_free' => false,
            'effective_date' => current_time('mysql'),
        ));
        
        $wpdb->insert($table, array(
            'membership_tier' => 'patron',
            'annual_pledge' => 360.00,
            'monthly_pledge' => 30.00,
            'platform_contribution' => 10.00,
            'is_free' => false,
            'effective_date' => current_time('mysql'),
        ));
    }
    
    public static function hash_device_id($device_id) {
        return hash('sha256', $device_id . wp_salt());
    }
    
    public static function hash_identifier($identifier) {
        return hash('sha256', $identifier . wp_salt());
    }
}
