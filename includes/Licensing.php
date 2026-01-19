<?php
namespace HBC_POD_LEDGER;

class Licensing {
    
    /**
     * Create licensing acceptance record
     */
    public static function createAcceptance($device_hash, $device_data) {
        // Round geo to 3 decimals
        $lat_round = isset($device_data['lat']) ? round(floatval($device_data['lat']), 3) : null;
        $lng_round = isset($device_data['lng']) ? round(floatval($device_data['lng']), 3) : null;
        
        // Build canonical payload (without acceptance_hash first for hashing)
        $payload_data = array(
            'device_hash' => $device_hash,
            'license_version' => HBC_LICENSE_VERSION,
            'accepted_at_utc' => gmdate('c'),
            'lat_round' => $lat_round,
            'lng_round' => $lng_round,
            'branch' => $device_data['branch'] ?? null,
            'buyer_poc_id' => $device_data['buyer_poc_id'] ?? null,
            'seller_poc_id' => $device_data['seller_poc_id'] ?? null,
        );
        
        // Build canonical payload structure
        $payload = self::buildCanonicalPayload($payload_data);
        
        // Generate acceptance hash from canonical payload (before adding acceptance_hash)
        $acceptance_hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
        
        // Add acceptance_hash to payload
        $payload['acceptance_hash'] = $acceptance_hash;
        
        // Write locally
        $local_result = self::writeLocal($device_hash, $payload, $device_data);
        
        if (is_wp_error($local_result)) {
            return $local_result;
        }
        
        // Write ledger entry
        $ledger_result = self::writeLedgerLicensedEntry($device_hash, $acceptance_hash, $payload);
        
        // Attempt GitHub append (non-blocking)
        $github_result = self::appendToGitHub($payload);
        
        return array(
            'acceptance_hash' => $acceptance_hash,
            'local_written' => true,
            'ledger_written' => !is_wp_error($ledger_result),
            'github_append' => $github_result['success'] ? 'success' : 'failed',
            'github_error' => $github_result['error'] ?? null,
        );
    }
    
    /**
     * Write acceptance to local licensing table (append-only)
     */
    private static function writeLocal($device_hash, $payload, $device_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_licensing';
        
        $insert_data = array(
            'device_hash' => $device_hash,
            'license_version' => HBC_LICENSE_VERSION,
            'accepted_at' => current_time('mysql', 1), // UTC
            'lat_round' => $payload['lat_round'],
            'lng_round' => $payload['lng_round'],
            'branch' => $payload['branch'],
            'buyer_poc_id' => $payload['buyer_poc_id'],
            'seller_poc_id' => $payload['seller_poc_id'],
            'acceptance_hash' => $payload['acceptance_hash'],
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to write licensing acceptance', array('status' => 500));
        }
        
        return true;
    }
    
    /**
     * Write LICENSE_ACCEPTED entry to ledger (append-only)
     */
    private static function writeLedgerLicensedEntry($device_hash, $acceptance_hash, $payload) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        $voucher_id = 'LICENSE-' . substr($acceptance_hash, 0, 16);
        
        $audit = array(
            array(
                'event' => 'LICENSE_ACCEPTED',
                'timestamp' => current_time('mysql', 1),
                'actor' => 'system',
                'acceptance_hash' => $acceptance_hash,
                'license_version' => HBC_LICENSE_VERSION,
            )
        );
        
        $insert_data = array(
            'voucher_id' => $voucher_id,
            'status' => 'LICENSED',
            'seller_device_hash' => $device_hash,
            'initiated_at' => current_time('mysql', 1),
            'lat_init' => $payload['lat_round'],
            'lng_init' => $payload['lng_round'],
            'branch' => $payload['branch'],
            'buyer_poc_id' => $payload['buyer_poc_id'],
            'seller_poc_id' => $payload['seller_poc_id'],
            'audit_json' => json_encode($audit),
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to write ledger licensed entry', array('status' => 500));
        }
        
        return true;
    }
    
    /**
     * Build canonical payload (ordered keys for consistent hashing)
     */
    public static function buildCanonicalPayload($data) {
        return array(
            'type' => 'LICENSE_ACCEPTED',
            'license_version' => $data['license_version'] ?? HBC_LICENSE_VERSION,
            'accepted_at_utc' => $data['accepted_at_utc'] ?? gmdate('c'),
            'device_hash' => $data['device_hash'],
            'geo_round' => array(
                'lat' => $data['lat_round'],
                'lng' => $data['lng_round'],
            ),
            'branch' => $data['branch'] ?? null,
            'buyer_poc_id' => $data['buyer_poc_id'] ?? null,
            'seller_poc_id' => $data['seller_poc_id'] ?? null,
        );
    }
    
    /**
     * Append to GitHub (non-blocking, writes reconciliation on failure)
     */
    private static function appendToGitHub($payload) {
        require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Services/GitHubService.php';
        
        $date_ymd = gmdate('Y-m-d');
        $line = json_encode($payload, JSON_UNESCAPED_SLASHES);
        
        $result = \HBC_POD_LEDGER\Services\GitHubService::appendLedgerLine($date_ymd, $line);
        
        if (!$result['success']) {
            // Write reconciliation record for GitHub failure
            self::writeGitHubFailureReconciliation($payload['acceptance_hash'], $result['error']);
        }
        
        return $result;
    }
    
    /**
     * Write reconciliation record for GitHub append failure
     */
    private static function writeGitHubFailureReconciliation($acceptance_hash, $error) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_reconciliation';
        
        $totals = array(
            'acceptance_hash' => $acceptance_hash,
            'error_summary' => $error,
            'timestamp' => current_time('mysql', 1),
        );
        
        $wpdb->insert($table, array(
            'recon_type' => 'GITHUB_APPEND_FAIL',
            'period_start' => current_time('mysql', 1),
            'period_end' => current_time('mysql', 1),
            'totals_json' => json_encode($totals),
            'created_at' => current_time('mysql'),
        ));
    }
}
