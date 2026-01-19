<?php
namespace HBC_POD_LEDGER;

class Deactivator {
    
    public static function deactivate() {
        // Clear scheduled cron jobs
        Reconciliation::unschedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
