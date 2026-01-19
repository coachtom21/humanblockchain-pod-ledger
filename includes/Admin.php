<?php
namespace HBC_POD_LEDGER;

class Admin {
    
    protected static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'HBC PoD Ledger',
            'HBC PoD Ledger',
            'manage_options',
            'hbc-pod-ledger',
            array($this, 'render_devices_page'),
            'dashicons-list-view',
            30
        );
        
        add_submenu_page(
            'hbc-pod-ledger',
            'Devices',
            'Devices',
            'manage_options',
            'hbc-pod-ledger',
            array($this, 'render_devices_page')
        );
        
        add_submenu_page(
            'hbc-pod-ledger',
            'Ledger Entries',
            'Ledger Entries',
            'manage_options',
            'hbc-pod-ledger-entries',
            array($this, 'render_ledger_page')
        );
        
        add_submenu_page(
            'hbc-pod-ledger',
            'Reconciliation',
            'Reconciliation',
            'manage_options',
            'hbc-pod-ledger-reconciliation',
            array($this, 'render_reconciliation_page')
        );
        
        add_submenu_page(
            'hbc-pod-ledger',
            'Participation',
            'Participation',
            'manage_options',
            'hbc-pod-ledger-participation',
            array($this, 'render_participation_page')
        );
        
        add_submenu_page(
            'hbc-pod-ledger',
            'Settings',
            'Settings',
            'manage_options',
            'hbc-pod-ledger-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function render_devices_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_devices';
        
        $devices = $wpdb->get_results("SELECT * FROM $table ORDER BY registered_at DESC LIMIT 100");
        
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/admin-devices.php';
    }
    
    public function render_ledger_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $branch = isset($_GET['branch']) ? sanitize_text_field($_GET['branch']) : '';
        
        $where = array('1=1');
        $where_values = array();
        
        if ($status) {
            $where[] = 'status = %s';
            $where_values[] = $status;
        }
        
        if ($branch) {
            $where[] = 'branch = %s';
            $where_values[] = $branch;
        }
        
        $where_sql = implode(' AND ', $where);
        
        if (!empty($where_values)) {
            $entries = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC LIMIT 100",
                $where_values
            ));
        } else {
            $entries = $wpdb->get_results("SELECT * FROM $table WHERE $where_sql ORDER BY id DESC LIMIT 100");
        }
        
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/admin-ledger.php';
    }
    
    public function render_reconciliation_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_reconciliation';
        
        $recons = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
        
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/admin-reconciliation.php';
    }
    
    public function render_participation_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_participation';
        
        $participations = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
        
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/admin-participation.php';
    }
    
    public function render_settings_page() {
        if (isset($_POST['submit']) && check_admin_referer('hbc_settings')) {
            update_option('hbc_pledge_total', sanitize_text_field($_POST['pledge_total']));
            update_option('hbc_buyer_rebate', sanitize_text_field($_POST['buyer_rebate']));
            update_option('hbc_social_impact', sanitize_text_field($_POST['social_impact']));
            update_option('hbc_patronage_total', sanitize_text_field($_POST['patronage_total']));
            update_option('hbc_patronage_individual', sanitize_text_field($_POST['patronage_individual']));
            update_option('hbc_patronage_group_pool', sanitize_text_field($_POST['patronage_group_pool']));
            update_option('hbc_patronage_treasury_reserve', sanitize_text_field($_POST['patronage_treasury_reserve']));
            update_option('hbc_maturity_min_days', intval($_POST['maturity_min_days']));
            update_option('hbc_maturity_max_days', intval($_POST['maturity_max_days']));
            
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}
