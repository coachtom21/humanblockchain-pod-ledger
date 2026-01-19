<?php
namespace HBC_POD_LEDGER;

class Reconciliation {
    
    protected static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('hbc_daily_maturity_scanner', array($this, 'daily_maturity_scanner'));
        add_action('hbc_month_end_close', array($this, 'month_end_close'));
        add_action('hbc_first_day_distribution', array($this, 'first_day_distribution'));
        add_action('hbc_year_end_close', array($this, 'year_end_close'));
        add_action('hbc_monthly_tranche_close', array($this, 'monthly_tranche_close'));
        add_action('hbc_september_first_issuance', array($this, 'september_first_issuance'));
        add_action('hbc_pmg_announcement', array($this, 'pmg_announcement'));
    }
    
    public static function schedule_cron_jobs() {
        // Daily maturity scanner (runs at 2 AM)
        if (!wp_next_scheduled('hbc_daily_maturity_scanner')) {
            wp_schedule_event(strtotime('tomorrow 2:00'), 'daily', 'hbc_daily_maturity_scanner');
        }
        
        // Month-end close (runs on last day of month at 11:59 PM)
        if (!wp_next_scheduled('hbc_month_end_close')) {
            $last_day = date('Y-m-t 23:59:59');
            wp_schedule_single_event(strtotime($last_day), 'hbc_month_end_close');
        }
        
        // First-day distribution (runs on first day of month at 12:00 AM)
        if (!wp_next_scheduled('hbc_first_day_distribution')) {
            $first_day = date('Y-m-01 00:00:00');
            wp_schedule_single_event(strtotime($first_day), 'hbc_first_day_distribution');
        }
        
        // Year-end close (runs on Sept 1 at 12:00 AM)
        if (!wp_next_scheduled('hbc_year_end_close')) {
            $year_end = date('Y-09-01 00:00:00');
            wp_schedule_single_event(strtotime($year_end), 'hbc_year_end_close');
        }
        
        // Month-end tranche close (runs on last day of month at 11:59 PM)
        if (!wp_next_scheduled('hbc_monthly_tranche_close')) {
            $last_day = date('Y-m-t 23:59:59');
            wp_schedule_single_event(strtotime($last_day), 'hbc_monthly_tranche_close');
        }
        
        // September 1 issuance (runs on Sept 1 at 12:00 AM)
        if (!wp_next_scheduled('hbc_september_first_issuance')) {
            $sept_first = date('Y-09-01 00:00:00');
            wp_schedule_single_event(strtotime($sept_first), 'hbc_september_first_issuance');
        }
        
        // PMG announcement (runs on Aug 11 at 12:00 PM)
        if (!wp_next_scheduled('hbc_pmg_announcement')) {
            $aug_11 = date('Y-08-11 12:00:00');
            wp_schedule_single_event(strtotime($aug_11), 'hbc_pmg_announcement');
        }
    }
    
    public static function unschedule_cron_jobs() {
        wp_clear_scheduled_hook('hbc_daily_maturity_scanner');
        wp_clear_scheduled_hook('hbc_month_end_close');
        wp_clear_scheduled_hook('hbc_first_day_distribution');
        wp_clear_scheduled_hook('hbc_year_end_close');
        wp_clear_scheduled_hook('hbc_monthly_tranche_close');
        wp_clear_scheduled_hook('hbc_september_first_issuance');
        wp_clear_scheduled_hook('hbc_pmg_announcement');
    }
    
    /**
     * Daily maturity scanner - checks for matured pledges
     */
    public function daily_maturity_scanner() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        $today = current_time('mysql');
        
        // Find entries that have reached maturity_date but not yet reconciled
        $matured = $wpdb->get_results($wpdb->prepare(
            "SELECT id, pledge_total, confirmed_at, maturity_date 
             FROM $table 
             WHERE status = 'CONFIRMED' 
             AND maturity_date <= %s 
             AND mature_by >= %s
             AND id NOT IN (SELECT DISTINCT parent_entry_id FROM $table WHERE status = 'RECONCILED' AND parent_entry_id IS NOT NULL)",
            $today,
            $today
        ));
        
        $totals = array(
            'count' => count($matured),
            'total_pledge' => '0.00',
            'timestamp' => $today
        );
        
        if (!empty($matured)) {
            $total = 0;
            foreach ($matured as $entry) {
                $total += floatval($entry->pledge_total);
            }
            $totals['total_pledge'] = number_format($total, 2, '.', '');
        }
        
        // Write journal entry
        $this->write_journal_entry('DAILY', date('Y-m-d 00:00:00', strtotime($today)), date('Y-m-d 23:59:59', strtotime($today)), $totals);
    }
    
    /**
     * Month-end close - records receipts/obligations snapshot
     */
    public function month_end_close() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');
        
        // Get all CONFIRMED entries for the month
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT COUNT(*) as count, SUM(pledge_total) as total 
             FROM $table 
             WHERE status = 'CONFIRMED' 
             AND confirmed_at >= %s 
             AND confirmed_at <= %s",
            $month_start,
            $month_end
        ));
        
        $totals = array(
            'count' => intval($entries[0]->count),
            'total_pledge' => number_format(floatval($entries[0]->total ?: 0), 2, '.', ''),
            'timestamp' => current_time('mysql')
        );
        
        $this->write_journal_entry('MONTH_END', $month_start, $month_end, $totals);
        
        // Reschedule for next month
        $next_month = date('Y-m-t 23:59:59', strtotime('+1 month'));
        wp_schedule_single_event(strtotime($next_month), 'hbc_month_end_close');
    }
    
    /**
     * First-day distribution - marks matured items eligible
     */
    public function first_day_distribution() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        $today = date('Y-m-d 00:00:00');
        
        // Find entries that matured in previous month
        $prev_month_start = date('Y-m-01 00:00:00', strtotime('-1 month'));
        $prev_month_end = date('Y-m-t 23:59:59', strtotime('-1 month'));
        
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT COUNT(*) as count, SUM(pledge_total) as total 
             FROM $table 
             WHERE status = 'CONFIRMED' 
             AND maturity_date >= %s 
             AND maturity_date <= %s",
            $prev_month_start,
            $prev_month_end
        ));
        
        $totals = array(
            'count' => intval($entries[0]->count),
            'total_pledge' => number_format(floatval($entries[0]->total ?: 0), 2, '.', ''),
            'timestamp' => current_time('mysql')
        );
        
        $this->write_journal_entry('FIRST_DAY', $prev_month_start, $prev_month_end, $totals);
        
        // Reschedule for next month
        $next_first = date('Y-m-01 00:00:00', strtotime('+1 month'));
        wp_schedule_single_event(strtotime($next_first), 'hbc_first_day_distribution');
    }
    
    /**
     * Year-end close - Sept 1 (waives maturity for completed actions)
     */
    public function year_end_close() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_ledger';
        
        $year_start = date('Y-09-01 00:00:00', strtotime('-1 year'));
        $year_end = date('Y-08-31 23:59:59');
        
        // Get all CONFIRMED entries from previous year
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT COUNT(*) as count, SUM(pledge_total) as total 
             FROM $table 
             WHERE status = 'CONFIRMED' 
             AND confirmed_at >= %s 
             AND confirmed_at <= %s",
            $year_start,
            $year_end
        ));
        
        $totals = array(
            'count' => intval($entries[0]->count),
            'total_pledge' => number_format(floatval($entries[0]->total ?: 0), 2, '.', ''),
            'timestamp' => current_time('mysql'),
            'note' => 'Year-end close: waives maturity for completed actions; no carry-forward of obligations'
        );
        
        $this->write_journal_entry('YEAR_END', $year_start, $year_end, $totals);
        
        // Reschedule for next year
        $next_year = date('Y-09-01 00:00:00', strtotime('+1 year'));
        wp_schedule_single_event(strtotime($next_year), 'hbc_year_end_close');
    }
    
    /**
     * Month-end tranche close - closes device tranches for the month
     */
    public function monthly_tranche_close() {
        $period_start = date('Y-m-01 00:00:00');
        $period_end = date('Y-m-t 23:59:59');
        
        $result = self::closeDeviceTranches($period_start, $period_end);
        
        // Write journal entry
        $totals = array(
            'tranches_closed' => is_array($result) ? $result['closed_count'] : 0,
            'timestamp' => current_time('mysql'),
        );
        
        $this->write_journal_entry('MONTH_END_TRANCHE', $period_start, $period_end, $totals);
        
        // Reschedule for next month
        $next_month = date('Y-m-t 23:59:59', strtotime('+1 month'));
        wp_schedule_single_event(strtotime($next_month), 'hbc_monthly_tranche_close');
    }
    
    /**
     * Close device tranches for a given period
     */
    public static function closeDeviceTranches($period_start, $period_end) {
        global $wpdb;
        $ledger_table = $wpdb->prefix . 'hbc_ledger';
        $tranches_table = $wpdb->prefix . 'hbc_device_tranches';
        $devices_table = $wpdb->prefix . 'hbc_devices';
        
        // Get all devices with ledger entries in period
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT seller_device_hash as device_hash FROM $ledger_table 
             WHERE confirmed_at >= %s AND confirmed_at <= %s AND status = 'CONFIRMED'
             UNION
             SELECT DISTINCT buyer_device_hash as device_hash FROM $ledger_table 
             WHERE confirmed_at >= %s AND confirmed_at <= %s AND status = 'CONFIRMED'",
            $period_start, $period_end, $period_start, $period_end
        ));
        
        $closed_count = 0;
        
        foreach ($devices as $device_row) {
            $device_hash = $device_row->device_hash;
            if (empty($device_hash)) continue;
            
            // Get all ledger entries for this device in period
            $entries = $wpdb->get_results($wpdb->prepare(
                "SELECT id, pledge_total, allocations_json FROM $ledger_table 
                 WHERE (seller_device_hash = %s OR buyer_device_hash = %s)
                 AND confirmed_at >= %s AND confirmed_at <= %s AND status = 'CONFIRMED'",
                $device_hash, $device_hash, $period_start, $period_end
            ));
            
            if (empty($entries)) continue;
            
            // Calculate receipts and obligations
            $receipts_total = 0.00;
            $obligations_total = 0.00;
            $entry_ids = array();
            
            foreach ($entries as $entry) {
                $entry_ids[] = $entry->id;
                $allocations = json_decode($entry->allocations_json, true);
                
                if (is_array($allocations)) {
                    // Receipts: buyer rebate
                    if (isset($allocations['buyer_rebate'])) {
                        $receipts_total += floatval($allocations['buyer_rebate']);
                    }
                    // Obligations: seller pledge
                    $obligations_total += floatval($entry->pledge_total);
                }
            }
            
            // Generate tranche hash (SHA-256 of all entry IDs + amounts)
            $tranche_data = implode(',', $entry_ids) . '|' . $receipts_total . '|' . $obligations_total;
            $tranche_hash = hash('sha256', $tranche_data);
            
            // Create tranche record
            $wpdb->insert($tranches_table, array(
                'device_hash' => $device_hash,
                'tranche_period_start' => $period_start,
                'tranche_period_end' => $period_end,
                'tranche_hash' => $tranche_hash,
                'receipts_total' => $receipts_total,
                'obligations_total' => $obligations_total,
                'net_position' => $receipts_total - $obligations_total,
                'entry_count' => count($entries),
                'closed_at' => current_time('mysql'),
                'closed_by' => 'system',
                'status' => 'closed',
                'created_at' => current_time('mysql'),
            ));
            
            $closed_count++;
        }
        
        return array(
            'closed_count' => $closed_count,
            'period_start' => $period_start,
            'period_end' => $period_end,
        );
    }
    
    /**
     * September 1 issuance - issues referral awards and recognition events
     */
    public function september_first_issuance() {
        global $wpdb;
        $referrals_table = $wpdb->prefix . 'hbc_referrals';
        
        // Get all pending referrals
        $referrals = $wpdb->get_results(
            "SELECT * FROM $referrals_table WHERE status = 'pending'"
        );
        
        $awarded_count = 0;
        
        foreach ($referrals as $referral) {
            // Award referral
            require_once HBC_POD_LEDGER_PLUGIN_DIR . 'includes/Referral.php';
            Referral::awardReferral($referral->id);
            $awarded_count++;
        }
        
        // Write journal entry
        $totals = array(
            'referrals_awarded' => $awarded_count,
            'timestamp' => current_time('mysql'),
        );
        
        $year_start = date('Y-09-01 00:00:00', strtotime('-1 year'));
        $year_end = date('Y-08-31 23:59:59');
        
        $this->write_journal_entry('SEPTEMBER_FIRST_ISSUANCE', $year_start, $year_end, $totals);
        
        // Reschedule for next year
        $next_year = date('Y-09-01 00:00:00', strtotime('+1 year'));
        wp_schedule_single_event(strtotime($next_year), 'hbc_september_first_issuance');
    }
    
    /**
     * PMG announcement - announces 10 Postmaster Generals (annual XP leaders)
     */
    public function pmg_announcement() {
        global $wpdb;
        $ledger_table = $wpdb->prefix . 'hbc_ledger';
        
        // Calculate annual XP leaders (top 10 by pledge_total)
        $year_start = date('Y-09-01 00:00:00', strtotime('-1 year'));
        $year_end = date('Y-08-31 23:59:59');
        
        $leaders = $wpdb->get_results($wpdb->prepare(
            "SELECT seller_device_hash, SUM(pledge_total) as total_pledge, COUNT(*) as entry_count
             FROM $ledger_table
             WHERE status = 'CONFIRMED'
             AND confirmed_at >= %s
             AND confirmed_at <= %s
             GROUP BY seller_device_hash
             ORDER BY total_pledge DESC
             LIMIT 10",
            $year_start,
            $year_end
        ));
        
        $pmg_list = array();
        foreach ($leaders as $leader) {
            $pmg_list[] = array(
                'device_hash' => $leader->seller_device_hash,
                'total_pledge' => floatval($leader->total_pledge),
                'entry_count' => intval($leader->entry_count),
            );
        }
        
        // Write journal entry
        $totals = array(
            'pmg_count' => count($pmg_list),
            'pmg_list' => $pmg_list,
            'timestamp' => current_time('mysql'),
        );
        
        $this->write_journal_entry('PMG_ANNOUNCEMENT', $year_start, $year_end, $totals);
        
        // Reschedule for next year
        $next_year = date('Y-08-11 12:00:00', strtotime('+1 year'));
        wp_schedule_single_event(strtotime($next_year), 'hbc_pmg_announcement');
    }
    
    /**
     * Write journal entry to reconciliation table
     */
    private function write_journal_entry($type, $period_start, $period_end, $totals) {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_reconciliation';
        
        $wpdb->insert($table, array(
            'recon_type' => $type,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'totals_json' => json_encode($totals),
            'created_at' => current_time('mysql'),
        ));
    }
}
