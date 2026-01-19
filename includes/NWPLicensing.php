<?php
namespace HBC_POD_LEDGER;

class NWPLicensing {
    
    /**
     * Check if device qualifies for NWP issuance
     * 
     * Requirements:
     * - Seller pledge exists
     * - QRtiger v-card assurance exists
     * - Device match exists
     * - PoD voucher is valid
     * - Buyer scan confirmed
     */
    public static function qualifiesForNWP($voucher_id, $seller_device_hash) {
        global $wpdb;
        $ledger_table = $wpdb->prefix . 'hbc_ledger';
        $licenses_table = $wpdb->prefix . 'hbc_nwp_licenses';
        
        // 1. Check seller has active NWP license
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $licenses_table WHERE device_hash = %s AND license_status = 'active' LIMIT 1",
            $seller_device_hash
        ));
        
        if (!$license) {
            return new \WP_Error('no_license', 'Seller does not have an active NWP license', array('status' => 400));
        }
        
        // 2. Check QRtiger v-card exists and is valid
        if (empty($license->qrtiger_vcard_id)) {
            return new \WP_Error('no_vcard', 'QRtiger v-card not found for seller', array('status' => 400));
        }
        
        // 3. Check PoD voucher is valid (INITIATED → CONFIRMED)
        $ledger_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $ledger_table WHERE voucher_id = %s AND seller_device_hash = %s AND status = 'CONFIRMED' LIMIT 1",
            $voucher_id,
            $seller_device_hash
        ));
        
        if (!$ledger_entry) {
            return new \WP_Error('invalid_voucher', 'PoD voucher not found or not confirmed', array('status' => 400));
        }
        
        // 4. Check device match
        if ($ledger_entry->seller_device_hash !== $seller_device_hash) {
            return new \WP_Error('device_mismatch', 'Device hash mismatch', array('status' => 400));
        }
        
        return true;
    }
    
    /**
     * Issue NWP credential
     * 
     * Creates NWP_CREDENTIAL_ISSUED event
     */
    public static function issueCredential($voucher_id, $seller_device_hash) {
        global $wpdb;
        $ledger_table = $wpdb->prefix . 'hbc_ledger';
        $licenses_table = $wpdb->prefix . 'hbc_nwp_licenses';
        
        // Validate qualifications
        $qualifies = self::qualifiesForNWP($voucher_id, $seller_device_hash);
        if (is_wp_error($qualifies)) {
            return $qualifies;
        }
        
        // Get license info
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $licenses_table WHERE device_hash = %s AND license_status = 'active' LIMIT 1",
            $seller_device_hash
        ));
        
        // Get ledger entry
        $ledger_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $ledger_table WHERE voucher_id = %s AND seller_device_hash = %s AND status = 'CONFIRMED' LIMIT 1",
            $voucher_id,
            $seller_device_hash
        ));
        
        // Create NWP_CREDENTIAL_ISSUED event in audit trail
        $audit = json_decode($ledger_entry->audit_json, true);
        if (!is_array($audit)) {
            $audit = array();
        }
        
        $audit[] = array(
            'event' => 'NWP_CREDENTIAL_ISSUED',
            'timestamp' => current_time('mysql'),
            'actor' => 'system',
            'data' => array(
                'license_id' => $license->license_id,
                'qrtiger_vcard_id' => $license->qrtiger_vcard_id,
                'nwp_units' => 1,
                'issuer' => $seller_device_hash,
                'assurance' => $license->qrtiger_vcard_id,
                'proof' => $voucher_id,
            )
        );
        
        // Update ledger entry
        $wpdb->update(
            $ledger_table,
            array('audit_json' => json_encode($audit)),
            array('id' => $ledger_entry->id),
            array('%s'),
            array('%d')
        );
        
        return array(
            'nwp_issued' => true,
            'nwp_units' => 1,
            'issuer' => $seller_device_hash,
            'assurance' => $license->qrtiger_vcard_id,
            'proof' => $voucher_id,
            'license_id' => $license->license_id,
        );
    }
    
    /**
     * Create NWP license for a device
     */
    public static function createLicense($device_hash, $qrtiger_vcard_id, $role_class, $poc_id = null) {
        global $wpdb;
        $licenses_table = $wpdb->prefix . 'hbc_nwp_licenses';
        $devices_table = $wpdb->prefix . 'hbc_devices';
        
        // Get device info
        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $devices_table WHERE device_hash = %s",
            $device_hash
        ));
        
        if (!$device) {
            return new \WP_Error('device_not_found', 'Device not found', array('status' => 404));
        }
        
        // Generate license ID
        $license_id = 'NWP-' . substr($device_hash, 0, 16) . '-' . time();
        
        // Get Discord user ID if available
        $discord_user_id = isset($device->discord_user_id) ? $device->discord_user_id : null;
        
        // Create license
        $result = $wpdb->insert($licenses_table, array(
            'license_id' => $license_id,
            'qrtiger_vcard_id' => $qrtiger_vcard_id,
            'device_hash' => $device_hash,
            'discord_user_id' => $discord_user_id,
            'license_status' => 'active',
            'role_class' => $role_class,
            'poc_id' => $poc_id ? $poc_id : $device->seller_poc_id,
            'issued_at' => current_time('mysql'),
        ));
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create NWP license', array('status' => 500));
        }
        
        return array(
            'license_id' => $license_id,
            'device_hash' => $device_hash,
            'status' => 'active',
        );
    }
}
