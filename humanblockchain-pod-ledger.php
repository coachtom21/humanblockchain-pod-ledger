<?php
/**
 * Plugin Name: HumanBlockchain PoD Ledger
 * Plugin URI: https://humanblockchain.local
 * Description: 2-scan Proof-of-Delivery pledge ledger with 8-12 week maturity or reconciliation moment. Device registration + single "Detente 2030" QR entrypoint.
 * Version: 1.0.0
 * Author: HumanBlockchain
 * License: GPL-2.0+
 * Text Domain: hbc-pod-ledger
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('HBC_POD_LEDGER_VERSION', '1.0.0');
define('HBC_POD_LEDGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HBC_POD_LEDGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HBC_POD_LEDGER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Disclaimer constant
define('HBC_POD_LEDGER_DISCLAIMER', 'Pledges are obligations that mature in 8-12 weeks or are settled via reconciliation moments; they are not payments.');

// Licensing Protocol constants
define('HBC_LICENSE_TITLE', 'MEGAvoter Brand Licensing Protocol');
define('HBC_LICENSE_VERSION', '1.0');
define('HBC_LICENSE_CORE', 'By registering this device, you accept a limited, revocable license to participate in MEGAvoter-branded Proof of Delivery (PoD) accounting. Participation records pledges—not payments—and produces append-only audit entries. Your Voice determines priorities; Proof determines truth.');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Activator.php';
    HBC_POD_LEDGER\Activator::activate();
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Deactivator.php';
    HBC_POD_LEDGER\Deactivator::deactivate();
});

/**
 * Main plugin class
 */
class HBC_POD_LEDGER {
    
    protected static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init();
    }
    
    private function load_dependencies() {
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Db.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Serendipity.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Ledger.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Reconciliation.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Participation.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Rest.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Admin.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Services/DiscordService.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Services/QRTigerService.php';
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Services/GitHubService.php';
    }
    
    private function init() {
        // Initialize components
        HBC_POD_LEDGER\Db::get_instance();
        HBC_POD_LEDGER\Rest::get_instance();
        HBC_POD_LEDGER\Admin::get_instance();
        HBC_POD_LEDGER\Reconciliation::get_instance();
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function register_shortcodes() {
        add_shortcode('hbc_enter', array($this, 'shortcode_enter'));
        add_shortcode('hbc_pod_flow', array($this, 'shortcode_pod_flow'));
        add_shortcode('hbc_participate', array($this, 'shortcode_participate'));
        add_shortcode('hbc_receipt', array($this, 'shortcode_receipt'));
        add_shortcode('hbc_determine', array($this, 'shortcode_determine'));
    }
    
    public function shortcode_enter($atts) {
        ob_start();
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/shortcode-enter.php';
        return ob_get_clean();
    }
    
    public function shortcode_pod_flow($atts) {
        ob_start();
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/shortcode-pod-flow.php';
        return ob_get_clean();
    }
    
    public function shortcode_participate($atts) {
        ob_start();
        wp_enqueue_style('hbc-pod-ledger-public', HBC_POD_LEDGER_PLUGIN_URL . 'assets/public.css', array(), HBC_POD_LEDGER_VERSION);
        wp_enqueue_script('hbc-pod-ledger-public', HBC_POD_LEDGER_PLUGIN_URL . 'assets/public.js', array('jquery'), HBC_POD_LEDGER_VERSION, true);
        wp_localize_script('hbc-pod-ledger-public', 'hbcPodLedger', array(
            'apiUrl' => rest_url('hbc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/shortcode-participate.php';
        return ob_get_clean();
    }
    
    public function shortcode_receipt($atts) {
        ob_start();
        wp_enqueue_style('hbc-pod-ledger-public', HBC_POD_LEDGER_PLUGIN_URL . 'assets/public.css', array(), HBC_POD_LEDGER_VERSION);
        wp_enqueue_script('hbc-pod-ledger-public', HBC_POD_LEDGER_PLUGIN_URL . 'assets/public.js', array('jquery'), HBC_POD_LEDGER_VERSION, true);
        wp_localize_script('hbc-pod-ledger-public', 'hbcPodLedger', array(
            'apiUrl' => rest_url('hbc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/shortcode-receipt.php';
        return ob_get_clean();
    }
    
    public function shortcode_determine($atts) {
        ob_start();
        wp_enqueue_style('hbc-pod-ledger-public', HBC_POD_LEDGER_PLUGIN_URL . 'assets/public.css', array(), HBC_POD_LEDGER_VERSION);
        wp_enqueue_script('hbc-pod-ledger-public', HBC_POD_LEDGER_PLUGIN_URL . 'assets/public.js', array('jquery'), HBC_POD_LEDGER_VERSION, true);
        wp_localize_script('hbc-pod-ledger-public', 'hbcPodLedger', array(
            'apiUrl' => rest_url('hbc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
        include HBC_POD_LEDGER_PLUGIN_DIR . 'templates/shortcode-determine.php';
        return ob_get_clean();
    }
    
    public function enqueue_public_assets() {
        wp_enqueue_style('hbc-pod-ledger-public', HBC_POD_LEDGER_PLUGIN_URL . 'assets/public.css', array(), HBC_POD_LEDGER_VERSION);
        wp_enqueue_script('hbc-pod-ledger-public', HBC_POD_LEDGER_PLUGIN_URL . 'assets/public.js', array('jquery'), HBC_POD_LEDGER_VERSION, true);
        wp_localize_script('hbc-pod-ledger-public', 'hbcPodLedger', array(
            'apiUrl' => rest_url('hbc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'hbc-pod-ledger') === false) {
            return;
        }
        wp_enqueue_style('hbc-pod-ledger-admin', HBC_POD_LEDGER_PLUGIN_URL . 'assets/admin.css', array(), HBC_POD_LEDGER_VERSION);
    }
}

// Initialize plugin
HBC_POD_LEDGER::get_instance();
