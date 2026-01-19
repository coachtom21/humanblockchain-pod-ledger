<?php
namespace HBC_POD_LEDGER;

require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Participation.php';

class Rest {
    
    protected static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('hbc/v1', '/register-device', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_device'),
            'permission_callback' => array($this, 'check_api_auth'),
        ));
        
        register_rest_route('hbc/v1', '/pod/initiate', array(
            'methods' => 'POST',
            'callback' => array($this, 'pod_initiate'),
            'permission_callback' => array($this, 'check_api_auth'),
        ));
        
        register_rest_route('hbc/v1', '/pod/confirm', array(
            'methods' => 'POST',
            'callback' => array($this, 'pod_confirm'),
            'permission_callback' => array($this, 'check_api_auth'),
        ));
        
        register_rest_route('hbc/v1', '/ledger', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_ledger'),
            'permission_callback' => array($this, 'check_api_auth'),
        ));
        
        register_rest_route('hbc/v1', '/device/(?P<device_hash>[a-f0-9]{64})', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_device'),
            'permission_callback' => array($this, 'check_api_auth'),
        ));
        
        register_rest_route('hbc/v1', '/participation/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'participation_create'),
            'permission_callback' => array($this, 'check_api_auth'),
        ));
        
        register_rest_route('hbc/v1', '/participation/receipt/(?P<receipt_hash>[a-f0-9]{64})', array(
            'methods' => 'GET',
            'callback' => array($this, 'participation_receipt'),
            'permission_callback' => '__return_true', // Public endpoint
        ));
        
        register_rest_route('hbc/v1', '/participation/aggregate', array(
            'methods' => 'GET',
            'callback' => array($this, 'participation_aggregate'),
            'permission_callback' => '__return_true', // Public endpoint
        ));
    }
    
    public function check_api_auth($request) {
        // Check nonce for logged-in users
        if (is_user_logged_in()) {
            $nonce = $request->get_header('X-WP-Nonce');
            return wp_verify_nonce($nonce, 'wp_rest');
        }
        
        // Check API key for non-logged-in users (X-API-Key or X-HBC-KEY)
        $api_key = $request->get_header('X-API-Key');
        if (empty($api_key)) {
            $api_key = $request->get_header('X-HBC-KEY');
        }
        $stored_key = get_option('hbc_api_key');
        
        if (empty($api_key) || $api_key !== $stored_key) {
            return new \WP_Error('unauthorized', 'Invalid API key', array('status' => 401));
        }
        
        return true;
    }
    
    public function register_device($request) {
        $params = $request->get_json_params();
        
        // Validate required fields
        $required = array('device_id', 'platform', 'lat', 'lng', 'timestamp');
        foreach ($required as $field) {
            if (empty($params[$field])) {
                return new \WP_Error('missing_field', "Missing required field: $field", array('status' => 400));
            }
        }
        
        // Require license acceptance
        if (empty($params['accept_license']) || $params['accept_license'] !== true) {
            return new \WP_Error('license_required', 'License acceptance required to register this device.', array('status' => 400));
        }
        
        // Hash device ID
        $device_hash = Db::hash_device_id($params['device_id']);
        
        // Check if device already exists
        global $wpdb;
        $devices_table = $wpdb->prefix . 'hbc_devices';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $devices_table WHERE device_hash = %s",
            $device_hash
        ));
        
        if ($existing) {
            // Return existing device info
            return rest_ensure_response(array(
                'device_hash' => $existing->device_hash,
                'branch' => $existing->branch,
                'buyer_poc_id' => $existing->buyer_poc_id,
                'seller_poc_id' => $existing->seller_poc_id,
                'welcome_copy' => Serendipity::get_welcome_copy($existing->branch, $existing->buyer_poc_id, $existing->seller_poc_id),
            ));
        }
        
        // Assign branch and POCs
        $branch = Serendipity::assign_branch($device_hash);
        $buyer_poc_id = Serendipity::assign_buyer_poc($params['lat'], $params['lng'], $params['timestamp']);
        $seller_poc_id = Serendipity::assign_seller_poc($device_hash, $params['timestamp']);
        
        // Prepare meta
        $meta = array(
            'push_token' => isset($params['push_token']) ? sanitize_text_field($params['push_token']) : null,
        );
        
        // Insert device
        $insert_data = array(
            'device_hash' => $device_hash,
            'platform' => sanitize_text_field($params['platform']),
            'tz' => isset($params['timezone']) ? sanitize_text_field($params['timezone']) : null,
            'lat' => floatval($params['lat']),
            'lng' => floatval($params['lng']),
            'registered_at' => current_time('mysql'),
            'branch' => $branch,
            'buyer_poc_id' => $buyer_poc_id,
            'seller_poc_id' => $seller_poc_id,
            'meta' => json_encode($meta),
        );
        
        $result = $wpdb->insert($devices_table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to register device', array('status' => 500));
        }
        
        // Create licensing acceptance
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Licensing.php';
        $device_data = array(
            'lat' => floatval($params['lat']),
            'lng' => floatval($params['lng']),
            'branch' => $branch,
            'buyer_poc_id' => $buyer_poc_id,
            'seller_poc_id' => $seller_poc_id,
        );
        $licensing_result = Licensing::createAcceptance($device_hash, $device_data);
        
        $response = array(
            'device_hash' => $device_hash,
            'branch' => $branch,
            'buyer_poc_id' => $buyer_poc_id,
            'seller_poc_id' => $seller_poc_id,
            'welcome_copy' => Serendipity::get_welcome_copy($branch, $buyer_poc_id, $seller_poc_id),
            'acceptance_hash' => $licensing_result['acceptance_hash'],
            'github_append' => $licensing_result['github_append'],
        );
        
        if ($licensing_result['github_append'] === 'failed') {
            $response['warning'] = 'Device registered successfully, but GitHub append failed. Check reconciliation records.';
        }
        
        return rest_ensure_response($response);
    }
    
    public function pod_initiate($request) {
        $params = $request->get_json_params();
        
        // Validate required fields
        $required = array('seller_device_id', 'buyer_identifier', 'voucher_id', 'lat', 'lng', 'timestamp');
        foreach ($required as $field) {
            if (empty($params[$field])) {
                return new \WP_Error('missing_field', "Missing required field: $field", array('status' => 400));
            }
        }
        
        // Get device info for branch/POC
        $seller_device_hash = Db::hash_device_id($params['seller_device_id']);
        global $wpdb;
        $devices_table = $wpdb->prefix . 'hbc_devices';
        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT branch, buyer_poc_id, seller_poc_id FROM $devices_table WHERE device_hash = %s",
            $seller_device_hash
        ));
        
        $data = array(
            'seller_device_id' => $params['seller_device_id'],
            'buyer_identifier' => $params['buyer_identifier'],
            'voucher_id' => $params['voucher_id'],
            'lat' => $params['lat'],
            'lng' => $params['lng'],
            'order_ref' => isset($params['order_ref']) ? $params['order_ref'] : null,
        );
        
        if ($device) {
            $data['branch'] = $device->branch;
            $data['buyer_poc_id'] = $device->buyer_poc_id;
            $data['seller_poc_id'] = $device->seller_poc_id;
        }
        
        $entry_id = Ledger::initiate($data);
        
        if (is_wp_error($entry_id)) {
            return $entry_id;
        }
        
        return rest_ensure_response(array(
            'entry_id' => $entry_id,
            'status' => 'INITIATED',
            'voucher_id' => $params['voucher_id'],
        ));
    }
    
    public function pod_confirm($request) {
        $params = $request->get_json_params();
        
        // Validate required fields
        $required = array('buyer_device_id', 'voucher_id', 'confirm_delivery', 'lat', 'lng', 'timestamp');
        foreach ($required as $field) {
            if (empty($params[$field])) {
                return new \WP_Error('missing_field', "Missing required field: $field", array('status' => 400));
            }
        }
        
        if ($params['confirm_delivery'] !== true) {
            return new \WP_Error('invalid_confirm', 'confirm_delivery must be true', array('status' => 400));
        }
        
        $data = array(
            'buyer_device_id' => $params['buyer_device_id'],
            'voucher_id' => $params['voucher_id'],
            'lat' => $params['lat'],
            'lng' => $params['lng'],
        );
        
        $entry_id = Ledger::confirm($data);
        
        if (is_wp_error($entry_id)) {
            return $entry_id;
        }
        
        return rest_ensure_response(array(
            'entry_id' => $entry_id,
            'status' => 'CONFIRMED',
            'voucher_id' => $params['voucher_id'],
        ));
    }
    
    public function get_ledger($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        $params = $request->get_query_params();
        $where = array('1=1');
        $where_values = array();
        
        // Filters
        if (!empty($params['status'])) {
            $where[] = 'status = %s';
            $where_values[] = sanitize_text_field($params['status']);
        }
        
        if (!empty($params['branch'])) {
            $where[] = 'branch = %s';
            $where_values[] = sanitize_text_field($params['branch']);
        }
        
        if (!empty($params['voucher_id'])) {
            $where[] = 'voucher_id = %s';
            $where_values[] = sanitize_text_field($params['voucher_id']);
        }
        
        if (!empty($params['date_from'])) {
            $where[] = 'initiated_at >= %s';
            $where_values[] = sanitize_text_field($params['date_from']);
        }
        
        if (!empty($params['date_to'])) {
            $where[] = 'initiated_at <= %s';
            $where_values[] = sanitize_text_field($params['date_to']);
        }
        
        $where_sql = implode(' AND ', $where);
        $limit = isset($params['per_page']) ? intval($params['per_page']) : 50;
        $offset = isset($params['page']) ? (intval($params['page']) - 1) * $limit : 0;
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC LIMIT %d OFFSET %d",
                array_merge($where_values, array($limit, $offset))
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            );
        }
        
        $entries = $wpdb->get_results($query);
        
        // Decode JSON fields
        foreach ($entries as $entry) {
            if ($entry->allocations_json) {
                $entry->allocations = json_decode($entry->allocations_json, true);
            }
            if ($entry->audit_json) {
                $entry->audit = json_decode($entry->audit_json, true);
            }
            unset($entry->allocations_json, $entry->audit_json);
        }
        
        return rest_ensure_response($entries);
    }
    
    public function get_device($request) {
        $device_hash = $request->get_param('device_hash');
        
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_devices';
        
        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE device_hash = %s",
            $device_hash
        ));
        
        if (!$device) {
            return new \WP_Error('not_found', 'Device not found', array('status' => 404));
        }
        
        if ($device->meta) {
            $device->meta = json_decode($device->meta, true);
        }
        
        return rest_ensure_response($device);
    }
    
    public function participation_create($request) {
        $params = $request->get_json_params();
        
        // Validate required fields
        if (empty($params['device_hash'])) {
            return new \WP_Error('missing_field', 'Missing required field: device_hash', array('status' => 400));
        }
        
        $result = Participation::create($params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }
    
    public function participation_receipt($request) {
        $receipt_hash = $request->get_param('receipt_hash');
        
        $participation = Participation::get_by_receipt_hash($receipt_hash);
        
        if (!$participation) {
            return new \WP_Error('not_found', 'Receipt not found', array('status' => 404));
        }
        
        // Get device info
        global $wpdb;
        $devices_table = $wpdb->prefix . 'hbc_devices';
        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $devices_table WHERE device_hash = %s",
            $participation->device_hash
        ));
        
        $response = array(
            'participation' => $participation,
            'device' => $device,
        );
        
        return rest_ensure_response($response);
    }
    
    public function participation_aggregate($request) {
        $params = $request->get_query_params();
        $range = isset($params['range']) ? sanitize_text_field($params['range']) : 'today';
        
        if (!in_array($range, array('today', '7d', '30d'))) {
            $range = 'today';
        }
        
        $aggregate = Participation::get_aggregate($range);
        
        return rest_ensure_response($aggregate);
    }
}
