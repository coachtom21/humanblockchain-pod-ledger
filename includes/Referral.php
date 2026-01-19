<?php
namespace HBC_POD_LEDGER;

class Referral {
    
    const YAM_PEG = 21000; // 21,000 YAM = $1 USD
    
    const AWARDS = array(
        'yamer' => 21000,      // $1 USD equivalent
        'megavoter' => 105000,  // $5 USD equivalent
        'patron' => 525000      // $25 USD equivalent
    );
    
    /**
     * Calculate referral award based on membership tier
     */
    public static function calculateAward($membership_tier) {
        return isset(self::AWARDS[$membership_tier]) ? self::AWARDS[$membership_tier] : 0;
    }
    
    /**
     * Calculate USD equivalent from YAM amount
     */
    public static function calculateUSDEquivalent($yam_amount) {
        return $yam_amount / self::YAM_PEG;
    }
    
    /**
     * Process referral from Discord invite (primary method)
     */
    public static function processDiscordReferral($discord_user_id, $discord_invite_code, $referred_device_hash) {
        global $wpdb;
        $referrals_table = $wpdb->prefix . 'hbc_referrals';
        $devices_table = $wpdb->prefix . 'hbc_devices';
        
        // Look up inviter by discord_invite_code
        $inviter = $wpdb->get_row($wpdb->prepare(
            "SELECT device_hash, discord_user_id FROM $devices_table WHERE discord_invite_code = %s LIMIT 1",
            $discord_invite_code
        ));
        
        if (!$inviter) {
            return new \WP_Error('inviter_not_found', 'Inviter not found for Discord invite code', array('status' => 404));
        }
        
        // Get inviter's membership tier
        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $devices_table WHERE device_hash = %s",
            $inviter->device_hash
        ));
        
        // For now, assume membership tier is stored in meta or separate table
        // This would need to be implemented based on your membership system
        $membership_tier = 'yamer'; // Default, should be retrieved from actual membership data
        
        // Calculate award
        $yam_amount = self::calculateAward($membership_tier);
        $usd_equivalent = self::calculateUSDEquivalent($yam_amount);
        
        // Create referral record
        $result = $wpdb->insert($referrals_table, array(
            'referrer_device_hash' => $inviter->device_hash,
            'referred_device_hash' => $referred_device_hash,
            'referrer_membership_tier' => $membership_tier,
            'referral_award_yam_amount' => $yam_amount,
            'referral_award_usd_equivalent' => $usd_equivalent,
            'discord_invite_code' => $discord_invite_code,
            'discord_inviter_user_id' => $inviter->discord_user_id,
            'referral_source' => 'discord_invite',
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ));
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create referral record', array('status' => 500));
        }
        
        return array(
            'referral_id' => $wpdb->insert_id,
            'yam_amount' => $yam_amount,
            'usd_equivalent' => $usd_equivalent,
            'referrer_device_hash' => $inviter->device_hash,
        );
    }
    
    /**
     * Process referral from URL parameter (fallback method)
     */
    public static function processURLReferral($referrer_device_hash, $referred_device_hash) {
        global $wpdb;
        $referrals_table = $wpdb->prefix . 'hbc_referrals';
        $devices_table = $wpdb->prefix . 'hbc_devices';
        
        // Get referrer's membership tier
        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $devices_table WHERE device_hash = %s",
            $referrer_device_hash
        ));
        
        if (!$device) {
            return new \WP_Error('referrer_not_found', 'Referrer device not found', array('status' => 404));
        }
        
        // For now, assume membership tier is stored in meta or separate table
        $membership_tier = 'yamer'; // Default, should be retrieved from actual membership data
        
        // Calculate award
        $yam_amount = self::calculateAward($membership_tier);
        $usd_equivalent = self::calculateUSDEquivalent($yam_amount);
        
        // Create referral record
        $result = $wpdb->insert($referrals_table, array(
            'referrer_device_hash' => $referrer_device_hash,
            'referred_device_hash' => $referred_device_hash,
            'referrer_membership_tier' => $membership_tier,
            'referral_award_yam_amount' => $yam_amount,
            'referral_award_usd_equivalent' => $usd_equivalent,
            'referral_source' => 'url_param',
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ));
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create referral record', array('status' => 500));
        }
        
        return array(
            'referral_id' => $wpdb->insert_id,
            'yam_amount' => $yam_amount,
            'usd_equivalent' => $usd_equivalent,
        );
    }
    
    /**
     * Award referral (mark as awarded)
     */
    public static function awardReferral($referral_id) {
        global $wpdb;
        $referrals_table = $wpdb->prefix . 'hbc_referrals';
        
        $result = $wpdb->update(
            $referrals_table,
            array(
                'status' => 'awarded',
                'awarded_at' => current_time('mysql'),
            ),
            array('id' => $referral_id),
            array('%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
}
