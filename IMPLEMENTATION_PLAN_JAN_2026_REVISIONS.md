# Implementation Plan: January 2026 Revisions
## HumanBlockchain PoD Ledger - Required Changes

**Document Version:** 1.0  
**Date:** January 2026  
**Status:** Planning Phase  
**Based On:** Revisions Jan 2026 + The New Economy + Original Bundle Prompts

---

## Executive Summary

This document outlines all required changes to align the HumanBlockchain PoD Ledger plugin with the January 2026 revisions. The revisions fundamentally shift the economic model from "earning" to "pledging/sponsoring" and enforce the network marketing POC structure (5 sellers + 25 buyers per 30-member guild).

---

## Table of Contents

1. [Critical Terminology Changes](#1-critical-terminology-changes)
2. [POC Structure Enforcement](#2-poc-structure-enforcement)
3. [Referral System Corrections](#3-referral-system-corrections)
4. [Discord Integration (Required)](#4-discord-integration-required)
5. [Allocation Routing Logic](#5-allocation-routing-logic)
6. [Reconciliation & Tranche Close](#6-reconciliation--tranche-close)
7. [NWP Minting as Licensing](#7-nwp-minting-as-licensing)
8. [Membership Tiers & Pricing](#8-membership-tiers--pricing)
9. [VFN/PMG Responsibilities](#9-vfnpmg-responsibilities)
10. [Implementation Milestones](#10-implementation-milestones)
11. [Database Schema Changes](#11-database-schema-changes)
12. [API Endpoint Updates](#12-api-endpoint-updates)
13. [UI/UX Updates](#13-uiux-updates)

---

## 1. Critical Terminology Changes

### 1.1 Economic Language: "Earn" → "Pledge/Sponsor"

**Current State:**
- Seller "receives/earns" $10.30
- UI messages say "Seller receives $10.30 XP"
- Ledger entries use "earned" terminology

**Required Changes:**

#### A) Seller Scan Processing (Phase 4, Step 12)

**Current Language:**
```
"Seller receives $10.30 XP"
"Seller earns $10.30"
```

**New Language:**
```
"Seller issues $10.30 pledge (pending buyer confirmation)"
"Seller sponsors $10.30 pledge"
"Seller pledges $10.30 at minimum once annually on August 31st"
```

**Files to Update:**
- `includes/Ledger.php` - `initiate()` method
- `templates/shortcode-pod-flow.php` - Seller scan UI
- `assets/public.js` - Success messages
- `includes/Rest.php` - Response messages

#### B) Ledger Entry Metadata

**Add New Field:**
- `pledge_type` enum: `seller_pledge`, `buyer_rebate`, `patronage`, `social_impact`, `treasury_reserve`

**Update Status Values:**
- Change `INITIATED` description to "Seller pledge issued"
- Change `CONFIRMED` description to "Buyer confirmed delivery, pledge matures"

**Files to Update:**
- `includes/Db.php` - Add `pledge_type` column to `wp_hbc_ledger`
- `includes/Ledger.php` - Set `pledge_type` on all entries
- `templates/admin-ledger.php` - Display `pledge_type`

#### C) UI Messages Throughout

**Search & Replace:**
- "earn" → "pledge" (where seller-side)
- "earns" → "pledges"
- "earning" → "pledging"
- "received" → "issued" (seller context)
- "receives" → "issues" (seller context)

**Files to Review:**
- All template files
- All JavaScript files
- All admin pages

**Acceptance Criteria:**
- ✅ Every screen/message/ledger entry uses: pledge / sponsor / confirm, not "earn," for seller-side
- ✅ No instances of "seller earns" or "seller receives" in codebase
- ✅ All database metadata reflects pledge terminology

---

## 2. POC Structure Enforcement

### 2.1 Current State Analysis

**Current Implementation:**
- Buyer POC: Max 30 members (local, geo-based)
- Seller POC: Max 5 sellers (global, out-of-state)
- **MISSING:** Assignment of 5 local buyers to each seller
- **MISSING:** Enforcement of 30-member guild structure (5 sellers + 25 buyers)

### 2.2 Required Structure

**30-Member Guild (POC) Structure:**
```
Total: 30 members
├── 5 Sellers (global/out-of-state)
│   └── Each seller assigned 5 local buyers
└── 25 Buyers (local, geo-based)
    └── Each buyer assigned to 1 seller (their "coach")
```

**Key Rules:**
1. Buyer POC capacity: 30 members (25 buyers + 5 sellers)
2. Seller POC capacity: 5 sellers
3. Each seller must have exactly 5 local buyers assigned
4. Each buyer must be assigned to exactly 1 seller
5. Assignment happens when Buyer POC reaches 25 buyers → bind to Seller POC of 5 sellers

### 2.3 Implementation Tasks

#### A) Database Schema Updates

**New Table: `wp_hbc_seller_buyer_assignments`**
```sql
CREATE TABLE wp_hbc_seller_buyer_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_device_hash CHAR(64) NOT NULL,
    buyer_device_hash CHAR(64) NOT NULL,
    seller_poc_id VARCHAR(100) NOT NULL,
    buyer_poc_id VARCHAR(100) NOT NULL,
    assignment_index TINYINT UNSIGNED NOT NULL, -- 0-4 (which of the 5 buyers)
    assigned_at DATETIME NOT NULL,
    status ENUM('active', 'inactive', 'rotated') DEFAULT 'active',
    UNIQUE KEY unique_seller_buyer (seller_device_hash, buyer_device_hash),
    KEY idx_seller (seller_device_hash),
    KEY idx_buyer (buyer_device_hash),
    KEY idx_seller_poc (seller_poc_id),
    KEY idx_buyer_poc (buyer_poc_id)
);
```

**Update `wp_hbc_devices` Table:**
```sql
ALTER TABLE wp_hbc_devices
ADD COLUMN assigned_seller_device_hash CHAR(64) NULL AFTER seller_poc_id,
ADD COLUMN assigned_buyer_count TINYINT UNSIGNED DEFAULT 0 AFTER assigned_seller_device_hash,
ADD KEY idx_assigned_seller (assigned_seller_device_hash);
```

**Update `wp_hbc_ledger` Table:**
```sql
ALTER TABLE wp_hbc_ledger
ADD COLUMN seller_coach_device_hash CHAR(64) NULL AFTER seller_poc_id,
ADD COLUMN buyer_coach_device_hash CHAR(64) NULL AFTER buyer_poc_id;
```

#### B) Serendipity Protocol Updates

**File: `includes/Serendipity.php`**

**New Method: `assignBuyerToSeller($buyer_device_hash, $buyer_poc_id, $seller_poc_id)`**
```php
/**
 * Assigns a buyer to a seller within a POC structure
 * 
 * Rules:
 * - Each seller can have max 5 buyers
 * - Buyers are assigned in round-robin fashion
 * - When Buyer POC reaches 25 buyers, bind to Seller POC
 * 
 * @param string $buyer_device_hash
 * @param string $buyer_poc_id
 * @param string $seller_poc_id
 * @return array|WP_Error Assignment data
 */
public static function assignBuyerToSeller($buyer_device_hash, $buyer_poc_id, $seller_poc_id) {
    // 1. Get all sellers in the Seller POC
    // 2. Find seller with < 5 buyers assigned
    // 3. If all sellers have 5 buyers, create new Seller POC
    // 4. Assign buyer to seller
    // 5. Update assignment_index (0-4)
    // 6. Return assignment data
}
```

**New Method: `bindBuyerPOCToSellerPOC($buyer_poc_id, $seller_poc_id)`**
```php
/**
 * Binds a Buyer POC (25 buyers) to a Seller POC (5 sellers)
 * 
 * Triggered when Buyer POC reaches 25 buyers
 * 
 * @param string $buyer_poc_id
 * @param string $seller_poc_id
 * @return bool|WP_Error
 */
public static function bindBuyerPOCToSellerPOC($buyer_poc_id, $seller_poc_id) {
    // 1. Verify Buyer POC has exactly 25 buyers
    // 2. Verify Seller POC has exactly 5 sellers
    // 3. Assign each buyer to a seller (5 buyers per seller)
    // 4. Create assignment records
    // 5. Mark POCs as "bound"
}
```

**Update `assign_buyer_poc()` Method:**
- After assigning buyer to Buyer POC, check if POC now has 25 buyers
- If yes, trigger `bindBuyerPOCToSellerPOC()`

**Update `assign_seller_poc()` Method:**
- Ensure Seller POC maintains exactly 5 sellers
- When Seller POC is created, mark it as "available for binding"

#### C) UI Visibility Updates

**New Admin Page: "POC Structure"**

**File: `templates/admin-poc-structure.php`**

**Display:**
- List all Buyer POCs with member count
- List all Seller POCs with member count
- Show binding status (bound/unbound)
- Show seller-to-buyer assignments
- Allow manual reassignment (admin only)

**Public Shortcode: `[hbc_my_poc]`**

**File: `templates/shortcode-my-poc.php`**

**Display for each device:**
- "My Buyer POC (local)" - Show POC ID, member count, region
- "My Seller POC (global)" - Show POC ID, member count, branch
- "My 5 Assigned Local Buyers" - List buyer device hashes (anonymized)
- "My Seller Coach" - Show assigned seller device hash (anonymized)

**Files to Create/Update:**
- `includes/Admin.php` - Add "POC Structure" menu item
- `templates/admin-poc-structure.php` - New admin page
- `templates/shortcode-my-poc.php` - New shortcode template
- `includes/Rest.php` - Add endpoint `/wp-json/hbc/v1/poc/structure/{device_hash}`

**Acceptance Criteria:**
- ✅ Any device can display: `buyer_poc_id`, `seller_poc_id`, `branch`, and its 1-of-5 seller coach relationship
- ✅ Database enforces: max 5 buyers per seller, max 25 buyers per Buyer POC
- ✅ UI shows clear POC structure visualization
- ✅ Assignment happens automatically when Buyer POC reaches 25 buyers

---

## 3. Referral System Corrections

### 3.1 Current State

**Current Implementation:**
- Referrals award "XP 1/5/25"
- Stored as XP units
- Displayed as "XP" amounts

### 3.2 Required Changes

**Referral Awards (USD-equivalent at 21,000:1 peg):**
- YAM'er: **21,000 YAM** (not "1 XP")
- MEGAvoter: **105,000 YAM** (not "5 XP")
- Patron: **525,000 YAM** (not "25 XP")

**Key Points:**
- These are YAM-unit awards, not XP
- Display as "USD-equivalent at 21,000:1"
- Store as YAM amounts (integers)
- Calculate USD equivalent: `yam_amount / 21000`

### 3.3 Implementation Tasks

#### A) Database Schema Updates

**Update `wp_hbc_referrals` Table (if exists) or Create:**
```sql
CREATE TABLE wp_hbc_referrals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_device_hash CHAR(64) NOT NULL,
    referred_device_hash CHAR(64) NOT NULL,
    referrer_membership_tier ENUM('yamer', 'megavoter', 'patron') NOT NULL,
    referral_award_yam_amount BIGINT UNSIGNED NOT NULL, -- 21000, 105000, or 525000
    referral_award_usd_equivalent DECIMAL(10,2) NOT NULL, -- Calculated: yam_amount / 21000
    discord_invite_code VARCHAR(255) NULL,
    discord_inviter_user_id VARCHAR(255) NULL,
    referral_source ENUM('discord_invite', 'url_param', 'manual') DEFAULT 'url_param',
    awarded_at DATETIME NULL,
    status ENUM('pending', 'awarded', 'reconciled') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    KEY idx_referrer (referrer_device_hash),
    KEY idx_referred (referred_device_hash),
    KEY idx_discord_invite (discord_invite_code)
);
```

#### B) Referral Calculation Logic

**File: `includes/Referral.php` (new class)**

```php
class Referral {
    const YAM_PEG = 21000; // 21,000 YAM = $1 USD
    
    const AWARDS = [
        'yamer' => 21000,      // $1 USD equivalent
        'megavoter' => 105000,  // $5 USD equivalent
        'patron' => 525000      // $25 USD equivalent
    ];
    
    /**
     * Calculate referral award based on membership tier
     */
    public static function calculateAward($membership_tier) {
        return self::AWARDS[$membership_tier] ?? 0;
    }
    
    /**
     * Calculate USD equivalent
     */
    public static function calculateUSDEquivalent($yam_amount) {
        return $yam_amount / self::YAM_PEG;
    }
}
```

#### C) UI Updates

**Replace All Referral Displays:**
- "1/5/25 XP" → "21,000/105,000/525,000 YAM (USD-equivalent at 21,000:1)"
- "Referral Recognition (USD-equivalent at 21,000:1)"

**Files to Update:**
- `templates/admin-referrals.php` - Display YAM amounts
- `templates/shortcode-referral-status.php` - Show YAM awards
- `assets/public.js` - Update referral success messages
- `includes/Admin.php` - Referral reporting

**Acceptance Criteria:**
- ✅ No UI shows "1/5/25 XP" for referrals
- ✅ All referrals display as YAM-unit awards
- ✅ USD equivalent calculated and displayed
- ✅ Database stores YAM amounts (not XP)

---

## 4. Discord Integration (Required)

### 4.1 Current State

**Current Implementation:**
- Discord is optional
- Phase 6 Step 2: "Optional Discord connection"
- Referral system uses URL params primarily

### 4.2 Required Changes

**Discord is NOW REQUIRED:**
- Role assignments necessary
- Discord invites track referral bonus structure
- Referral trigger: Discord invite attribution is primary (URL param is fallback)
- Membership finalization blocked until Discord connected

### 4.3 Implementation Tasks

#### A) Update Membership Flow

**File: `templates/shortcode-participate.php` or membership registration**

**Phase 6 Step 2 - Change from Optional to Required:**
- Remove "Optional" label
- Add validation: cannot proceed without Discord connection
- Show error if Discord not connected
- Block "Continue" button until Discord connected

**New Flow:**
```
Step 2: Connect Discord (REQUIRED)
    ↓
[Connect Discord] button
    ↓
OAuth flow / Discord bot invite
    ↓
Store: discord_user_id, discord_username, discord_invite_code
    ↓
Assign Discord role based on membership tier
    ↓
[Continue] button enabled
```

#### B) Referral System Updates

**File: `includes/Referral.php`**

**New Method: `processDiscordReferral($discord_user_id, $discord_invite_code)`**
```php
/**
 * Process referral from Discord invite
 * 
 * Primary referral attribution method
 * 
 * @param string $discord_user_id
 * @param string $discord_invite_code
 * @return array|WP_Error
 */
public static function processDiscordReferral($discord_user_id, $discord_invite_code) {
    // 1. Look up inviter by discord_invite_code
    // 2. Get inviter's device_hash and membership_tier
    // 3. Calculate referral award (YAM amount)
    // 4. Create referral record with source='discord_invite'
    // 5. Store discord_inviter_user_id
    // 6. Return award data
}
```

**Update Referral Attribution Priority:**
1. **Primary:** Discord invite code (if present)
2. **Fallback:** URL referral parameter
3. **Manual:** Admin-assigned referrals

#### C) Discord Service Updates

**File: `includes/Services/DiscordService.php`**

**Update from Stub to Real Implementation:**

```php
class DiscordService {
    /**
     * Connect user's Discord account
     * 
     * @param string $device_hash
     * @param string $discord_code OAuth code
     * @return array|WP_Error Discord user data
     */
    public static function connectUser($device_hash, $discord_code) {
        // 1. Exchange code for access token
        // 2. Get Discord user info
        // 3. Store discord_user_id in device record
        // 4. Assign Discord role based on membership tier
        // 5. Return user data
    }
    
    /**
     * Assign Discord role based on membership tier
     * 
     * @param string $discord_user_id
     * @param string $membership_tier
     * @return bool|WP_Error
     */
    public static function assignRole($discord_user_id, $membership_tier) {
        // 1. Map membership tier to Discord role
        // 2. Call Discord API to assign role
        // 3. Return success/failure
    }
    
    /**
     * Track Discord invite usage
     * 
     * @param string $invite_code
     * @param string $discord_user_id User who used the invite
     * @return array|WP_Error Inviter data
     */
    public static function trackInvite($invite_code, $discord_user_id) {
        // 1. Look up invite code in Discord
        // 2. Get inviter's Discord user ID
        // 3. Return inviter data for referral processing
    }
}
```

#### D) Database Updates

**Update `wp_hbc_devices` Table:**
```sql
ALTER TABLE wp_hbc_devices
ADD COLUMN discord_user_id VARCHAR(255) NULL,
ADD COLUMN discord_username VARCHAR(255) NULL,
ADD COLUMN discord_connected_at DATETIME NULL,
ADD COLUMN discord_invite_code VARCHAR(255) NULL,
ADD KEY idx_discord_user (discord_user_id);
```

**Acceptance Criteria:**
- ✅ Discord connection is required before membership finalization
- ✅ Referral awards generated from Discord invite events (not just URL params)
- ✅ Discord roles assigned automatically based on membership tier
- ✅ Discord invite code stored and used for referral attribution

---

## 5. Allocation Routing Logic

### 5.1 Current State

**Current Allocation (per $10.30 pledge):**
- buyer_rebate: $5.00
- social_impact: $4.00
- patronage_total: $1.00
  - patronage_individual: $0.50
  - patronage_group_pool: $0.40
  - patronage_treasury_reserve: $0.10

**Missing Logic:**
- $0.40 routing based on POC activity status
- Conditional routing: active POC → group pool, inactive POC → treasury reserves

### 5.2 Required Changes

**New Routing Rules:**

**For YAM'ers NOT in Active POC:**
- $5.00 buyer rebate (pending maturity)
- $0.50 individual patronage
- $4.00 social impact → Peace Pentagon branch allocation
- $0.50 remaining patronage → Peace Pentagon branch allocation
- **Total to branch:** $4.50

**For YAM'ers IN Active POC:**
- $5.00 buyer rebate (pending maturity)
- $0.50 individual patronage
- $4.00 social impact → Peace Pentagon branch allocation
- $0.40 group bonus → Rotating group bonus pool (within POC)
- $0.10 treasury reserve → Branch treasury reserves

**Key Rule:**
- $0.40 per delivery rotates into group bonus pool **IF** seller is in active POC
- $0.40 goes to branch treasury reserves **IF** seller is NOT in active POC

### 5.3 Implementation Tasks

#### A) Update Ledger Allocation Logic

**File: `includes/Ledger.php`**

**Update `confirm()` Method:**

```php
public static function confirm($voucher_id, $buyer_device_hash, $geo, $timestamp) {
    // ... existing confirmation logic ...
    
    // Get seller device data
    $seller_device = self::getDeviceByHash($seller_device_hash);
    
    // Check if seller is in active POC
    $is_in_active_poc = self::isSellerInActivePOC($seller_device_hash);
    
    // Calculate allocations
    $allocations = [
        'buyer_rebate' => 5.00,
        'social_impact' => 4.00,
        'patronage_individual' => 0.50,
    ];
    
    if ($is_in_active_poc) {
        // Active POC: $0.40 to group pool, $0.10 to treasury
        $allocations['patronage_group_pool'] = 0.40;
        $allocations['patronage_treasury_reserve'] = 0.10;
        $allocations['routing_note'] = 'Active POC: $0.40 to group bonus pool';
    } else {
        // Inactive POC: $0.40 to branch treasury reserves
        $allocations['patronage_treasury_reserve'] = 0.50; // $0.40 + $0.10
        $allocations['routing_note'] = 'Inactive POC: $0.40 to branch treasury reserves';
    }
    
    // Store allocations in ledger entry
    // ... rest of confirmation logic ...
}
```

**New Method: `isSellerInActivePOC($seller_device_hash)`**
```php
/**
 * Check if seller is in an active POC
 * 
 * @param string $seller_device_hash
 * @return bool
 */
public static function isSellerInActivePOC($seller_device_hash) {
    global $wpdb;
    
    // Check if seller has active POC membership
    // Check if seller has assigned buyers (POC is active)
    // Return true if seller is in active POC with assigned buyers
}
```

#### B) Group Bonus Pool Table

**New Table: `wp_hbc_group_bonus_pools`**
```sql
CREATE TABLE wp_hbc_group_bonus_pools (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poc_id VARCHAR(100) NOT NULL,
    poc_type ENUM('buyer', 'seller') NOT NULL,
    pool_amount DECIMAL(10,2) DEFAULT 0.00,
    rotation_period ENUM('monthly', 'quarterly', 'annual') DEFAULT 'monthly',
    last_rotated_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY unique_poc (poc_id, poc_type)
);
```

**New Table: `wp_hbc_group_bonus_contributions`**
```sql
CREATE TABLE wp_hbc_group_bonus_contributions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pool_id BIGINT UNSIGNED NOT NULL,
    ledger_entry_id BIGINT UNSIGNED NOT NULL,
    contribution_amount DECIMAL(10,2) NOT NULL,
    contributor_device_hash CHAR(64) NOT NULL,
    contributed_at DATETIME NOT NULL,
    KEY idx_pool (pool_id),
    KEY idx_ledger (ledger_entry_id),
    KEY idx_contributor (contributor_device_hash)
);
```

#### C) UI Updates

**Add Allocation Breakdown Display:**

**File: `templates/shortcode-receipt.php` or ledger detail view**

**Show per transaction:**
```
Allocation Breakdown:
├── Buyer Rebate: $5.00 (pending maturity)
├── Social Impact: $4.00 → [Branch Name]
├── Individual Patronage: $0.50
└── Group/Treasury: $0.40 → [Group Pool / Treasury Reserve]
    └── Routing: [Active POC: Group Pool] OR [Inactive POC: Treasury]
```

**Files to Update:**
- `templates/admin-ledger.php` - Show allocation breakdown
- `templates/shortcode-receipt.php` - Display routing logic
- `includes/Admin.php` - Group bonus pool reporting

**Acceptance Criteria:**
- ✅ Every completed 2-scan transaction produces a ledger breakdown line-item report
- ✅ $0.40 routing logic implemented (active POC pool vs branch treasury reserves)
- ✅ Group bonus pools tracked per POC
- ✅ UI shows transparent statement: "Where did each component go?"

---

## 6. Reconciliation & Tranche Close

### 6.1 Current State

**Current Implementation:**
- Month-end close: last day of month
- First-day distributions: first day of month
- Year-end close: Sept 1
- **MISSING:** Device tranche close mechanics
- **MISSING:** VFN reconciliation process visibility
- **MISSING:** Annual milestones (Aug 31, Sep 1, Aug 11)

### 6.2 Required Changes

**New Reconciliation Moments:**

1. **Month-End Tranche Close (VFN Responsibility)**
   - Collect receipts/obligations per device for the month
   - Write immutable tranche hash + summary rows (append-only)
   - VFN runs scheduled job + admin dashboard for exceptions

2. **Annual Milestones:**
   - **Aug 31:** Annual close logic (waive maturity for completed actions)
   - **Sep 1:** Issuance/recognition events (referral awards)
   - **Aug 11:** PMG announcement event (10 Postmaster Generals announced)

3. **Device Tranche Settlement:**
   - Each device has a "tranche" of receipts/obligations
   - Tranche closes monthly
   - Settlement location: `wp_hbc_device_tranches` table

### 6.3 Implementation Tasks

#### A) Device Tranche Table

**New Table: `wp_hbc_device_tranches`**
```sql
CREATE TABLE wp_hbc_device_tranches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_hash CHAR(64) NOT NULL,
    tranche_period_start DATETIME NOT NULL,
    tranche_period_end DATETIME NOT NULL,
    tranche_hash CHAR(64) NOT NULL UNIQUE, -- SHA-256 of all entries in period
    receipts_total DECIMAL(10,2) DEFAULT 0.00,
    obligations_total DECIMAL(10,2) DEFAULT 0.00,
    net_position DECIMAL(10,2) DEFAULT 0.00, -- receipts - obligations
    entry_count INT UNSIGNED DEFAULT 0,
    closed_at DATETIME NULL,
    closed_by ENUM('vfn', 'pmg', 'system') DEFAULT 'system',
    status ENUM('open', 'closing', 'closed', 'reconciled') DEFAULT 'open',
    reconciliation_notes TEXT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_device (device_hash),
    KEY idx_period (tranche_period_start, tranche_period_end),
    KEY idx_hash (tranche_hash),
    KEY idx_status (status)
);
```

#### B) Month-End Tranche Close Module

**File: `includes/Reconciliation.php`**

**New Method: `closeDeviceTranches($period_start, $period_end)`**
```php
/**
 * Close device tranches for a given period
 * 
 * VFN responsibility: runs monthly
 * 
 * @param string $period_start YYYY-MM-DD
 * @param string $period_end YYYY-MM-DD
 * @return array|WP_Error Summary of closed tranches
 */
public static function closeDeviceTranches($period_start, $period_end) {
    // 1. Get all devices with open tranches in period
    // 2. For each device:
    //    a. Collect all ledger entries in period
    //    b. Calculate receipts (buyer rebates, etc.)
    //    c. Calculate obligations (seller pledges, etc.)
    //    d. Generate tranche hash (SHA-256 of all entry IDs + amounts)
    //    e. Create tranche record
    //    f. Mark as "closed"
    // 3. Return summary
}
```

**New Method: `annualClose($year)`**
```php
/**
 * Annual close on August 31
 * 
 * Waives maturity for completed actions
 * No carry-forward of obligations
 * 
 * @param int $year
 * @return array|WP_Error
 */
public static function annualClose($year) {
    // 1. Find all pledges with maturity_date <= Aug 31 of year
    // 2. For completed actions (CONFIRMED + buyer scan done):
    //    a. Mark as "matured" (waive remaining maturity period)
    //    b. Update status to "RECONCILED"
    // 3. For incomplete actions:
    //    a. Mark as "expired" (no carry-forward)
    // 4. Write reconciliation journal entry
}
```

**New Method: `septemberFirstIssuance($year)`**
```php
/**
 * September 1 issuance/recognition events
 * 
 * Issues referral awards
 * Recognition events for completed actions
 * 
 * @param int $year
 * @return array|WP_Error
 */
public static function septemberFirstIssuance($year) {
    // 1. Process all pending referral awards
    // 2. Issue YAM amounts to referrers
    // 3. Create recognition events
    // 4. Write journal entries
}
```

**New Method: `pmgAnnouncement($year)`**
```php
/**
 * August 11 PMG announcement
 * 
 * Announces 10 Postmaster Generals (annual XP leaders)
 * 
 * @param int $year
 * @return array|WP_Error PMG list
 */
public static function pmgAnnouncement($year) {
    // 1. Calculate annual XP leaders (top 10)
    // 2. Announce PMG assignments
    // 3. Update PMG records
    // 4. Write announcement event
}
```

#### C) WP-CRON Schedules

**Update `includes/Activator.php`:**

```php
// Month-end tranche close (last day of month, 11:59 PM)
wp_schedule_event(strtotime('last day of this month 23:59:00'), 'monthly', 'hbc_monthly_tranche_close');

// First-day distributions (first day of month, 12:00 AM)
wp_schedule_event(strtotime('first day of next month 00:00:00'), 'monthly', 'hbc_first_day_distributions');

// Annual close (August 31, 11:59 PM)
wp_schedule_event(strtotime('August 31 ' . date('Y') . ' 23:59:00'), 'yearly', 'hbc_annual_close');

// September 1 issuance (September 1, 12:00 AM)
wp_schedule_event(strtotime('September 1 ' . date('Y') . ' 00:00:00'), 'yearly', 'hbc_september_first_issuance');

// PMG announcement (August 11, 12:00 PM)
wp_schedule_event(strtotime('August 11 ' . date('Y') . ' 12:00:00'), 'yearly', 'hbc_pmg_announcement');
```

#### D) Admin Dashboard

**File: `templates/admin-reconciliation.php`**

**Add Sections:**
- Month-End Tranche Close: Run manually or view scheduled jobs
- Device Tranche Viewer: View tranches per device
- Annual Close: Run Aug 31 close manually
- September 1 Issuance: View/run issuance events
- PMG Announcement: View/run PMG announcement

**Files to Update:**
- `includes/Admin.php` - Add reconciliation menu items
- `templates/admin-reconciliation.php` - Add tranche close UI
- `includes/Rest.php` - Add endpoints for manual reconciliation triggers

**Acceptance Criteria:**
- ✅ Month-end tranche close exists and produces append-only summaries
- ✅ Device tranche records created per device per month
- ✅ Annual milestones (Aug 31, Sep 1, Aug 11) implemented
- ✅ VFN can run reconciliation via admin dashboard
- ✅ Single click (or cron) produces "Monthly Close Report" + "Per-device tranche record"

---

## 7. NWP Minting as Licensing

### 7.1 Current State

**Current Implementation:**
- NWP mentioned as "New World Penny"
- Treated as token/coin concept
- Not clearly defined as licensing capability

### 7.2 Required Changes

**NWP = Licensed Proof-of-Service Reward Credential**

**Key Points:**
- NOT a cryptocurrency mint
- NOT token creation
- IS a licensed service reward credential
- Authorized through: Seller pledge + QRtiger v-card + Buyer confirmation

### 7.3 Implementation Tasks

#### A) NWP License Registry Table

**New Table: `wp_hbc_nwp_licenses`**
```sql
CREATE TABLE wp_hbc_nwp_licenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id VARCHAR(100) NOT NULL UNIQUE,
    qrtiger_vcard_id VARCHAR(255) NOT NULL,
    device_hash CHAR(64) NOT NULL,
    discord_user_id VARCHAR(255) NULL,
    license_status ENUM('active', 'suspended', 'expired', 'revoked') DEFAULT 'active',
    role_class ENUM('yamer', 'megavoter', 'patron') NOT NULL,
    poc_id VARCHAR(100) NULL,
    issued_at DATETIME NOT NULL,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    revocation_reason TEXT NULL,
    KEY idx_device (device_hash),
    KEY idx_vcard (qrtiger_vcard_id),
    KEY idx_discord (discord_user_id),
    KEY idx_status (license_status)
);
```

#### B) NWP Issuance Policy Engine

**File: `includes/NWPLicensing.php` (new class)**

```php
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
     * 
     * @param string $voucher_id
     * @param string $seller_device_hash
     * @return bool|WP_Error
     */
    public static function qualifiesForNWP($voucher_id, $seller_device_hash) {
        // 1. Check seller has active NWP license
        // 2. Check QRtiger v-card exists and is valid
        // 3. Check PoD voucher is valid (INITIATED → CONFIRMED)
        // 4. Check device match
        // 5. Return true if all qualify
    }
    
    /**
     * Issue NWP credential
     * 
     * Creates NWP_CREDENTIAL_ISSUED event
     * 
     * @param string $voucher_id
     * @param string $seller_device_hash
     * @return array|WP_Error NWP credential data
     */
    public static function issueCredential($voucher_id, $seller_device_hash) {
        // 1. Validate qualifications
        // 2. Create ledger event: NWP_CREDENTIAL_ISSUED
        // 3. Attach to seller wallet/ledger as "service recognition credit"
        // 4. Return credential data
    }
}
```

#### C) Update Ledger Event Types

**File: `includes/Ledger.php`**

**Add Event Type:**
- `NWP_CREDENTIAL_ISSUED` - NWP service reward credential issued

**Update `confirm()` Method:**
- After buyer confirmation, check if seller qualifies for NWP
- If yes, call `NWPLicensing::issueCredential()`

#### D) UI Text Updates

**Replace All NWP Language:**
- "NWP minted" → "NWP issued"
- "service credential issued"
- "licensed delivery reward recognized"

**Public-Facing Phrasing:**
> "The New World Penny (NWP) is a licensed service reward credential issued only when a delivery pledge is confirmed by buyer scan. It is not money or a token — it's proof that a verified human delivery service occurred under the QR-assured 2-scan system."

**Files to Update:**
- All template files mentioning NWP
- All JavaScript files
- Admin pages
- README.md

**Acceptance Criteria:**
- ✅ NWP issuance policy engine defines qualifications
- ✅ License registry tracks authorized issuers
- ✅ Ledger event type NWP_CREDENTIAL_ISSUED exists
- ✅ UI text uses "issued" not "minted"
- ✅ No references to "token" or "coin" for NWP

---

## 8. Membership Tiers & Pricing

### 8.1 Current State

**Current Documentation Says:**
- "All memberships are FREE"
- "No payment required"
- "No subscription management"
- "Annual/Monthly are labels only"

### 8.2 Required Changes

**Actual Membership Structure:**

- **YAM'ers:** FREE
- **MEGAvoters:** $12 annual pledge
- **Patron:** $30 monthly OR $360 annual pledge
- **Platform Cost:** $10 monthly covered by Patron pledges

### 8.3 Implementation Tasks

#### A) Membership Pricing Table

**New Table: `wp_hbc_membership_pricing`**
```sql
CREATE TABLE wp_hbc_membership_pricing (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    membership_tier ENUM('yamer', 'megavoter', 'patron') NOT NULL UNIQUE,
    annual_pledge DECIMAL(10,2) NULL,
    monthly_pledge DECIMAL(10,2) NULL,
    platform_contribution DECIMAL(10,2) DEFAULT 0.00, -- Patron contributes $10/month
    is_free BOOLEAN DEFAULT FALSE,
    effective_date DATETIME NOT NULL,
    expires_at DATETIME NULL,
    KEY idx_tier (membership_tier)
);
```

**Initial Data:**
```sql
INSERT INTO wp_hbc_membership_pricing VALUES
(1, 'yamer', NULL, NULL, 0.00, TRUE, NOW(), NULL),
(2, 'megavoter', 12.00, NULL, 0.00, FALSE, NOW(), NULL),
(3, 'patron', 360.00, 30.00, 10.00, FALSE, NOW(), NULL);
```

#### B) Update Membership Registration Flow

**File: `templates/shortcode-participate.php`**

**Step 3: Membership Selection - Update Display:**

```
YAM'er — FREE
    "Join and participate in the network."

MEGAvoter — $12 Annual Pledge
    "Support the network with annual commitment."
    [Annual Pledge: $12.00]

Patron — $30 Monthly OR $360 Annual Pledge
    "Support and scale the network."
    [Monthly Pledge: $30.00] OR [Annual Pledge: $360.00]
    [Platform Contribution: $10.00/month covered by Patron pledges]
```

#### C) Pledge Tracking

**Update `wp_hbc_ledger` Table:**
```sql
ALTER TABLE wp_hbc_ledger
ADD COLUMN membership_pledge_type ENUM('annual', 'monthly', 'delivery') NULL AFTER pledge_type,
ADD COLUMN platform_contribution DECIMAL(10,2) DEFAULT 0.00 AFTER allocations_json;
```

**Files to Update:**
- `includes/Ledger.php` - Track membership pledges
- `includes/Admin.php` - Membership pledge reporting
- `templates/admin-ledger.php` - Show membership pledge types

**Acceptance Criteria:**
- ✅ Membership pricing clearly defined in database
- ✅ UI shows actual pricing (not "all free")
- ✅ Patron platform contribution ($10/month) tracked
- ✅ Membership pledges recorded in ledger

---

## 9. VFN/PMG Responsibilities

### 9.1 Current State

**Mentioned but not clearly defined:**
- VFN responsible for maintaining treasury cash flow
- PMG (10 members) responsible for monthly reconciliation
- No clear implementation

### 9.2 Required Changes

**VFN (Virtual Finance Network) Responsibilities:**
- Maintain treasury cash flow
- Run month-end tranche close
- Handle reconciliation exceptions
- Manage treasury reserves

**PMG (Postmaster Generals - 10 members) Responsibilities:**
- Monthly reconciliation to meet treasury cash flow needs
- Annual XP leader selection (announced Aug 11)
- Branch resource allocation decisions

### 9.3 Implementation Tasks

#### A) VFN Admin Dashboard

**File: `templates/admin-vfn-dashboard.php` (new)**

**Sections:**
- Treasury Cash Flow Overview
- Month-End Tranche Close (run/manage)
- Reconciliation Exceptions (handle)
- Treasury Reserves (view/manage)
- Device Tranche Viewer

**Files to Create:**
- `includes/VFN.php` - VFN service class
- `templates/admin-vfn-dashboard.php` - VFN dashboard
- `includes/Admin.php` - Add VFN menu (restricted to VFN role)

#### B) PMG Admin Dashboard

**File: `templates/admin-pmg-dashboard.php` (new)**

**Sections:**
- Monthly Reconciliation (run/manage)
- Annual XP Leaderboard (for Aug 11 announcement)
- Branch Resource Allocation
- PMG Assignment Management

**Files to Create:**
- `includes/PMG.php` - PMG service class
- `templates/admin-pmg-dashboard.php` - PMG dashboard
- `includes/Admin.php` - Add PMG menu (restricted to PMG role)

#### C) WordPress Roles

**Add Custom Roles:**
```php
// In includes/Activator.php
add_role('hbc_vfn', 'VFN Member', [
    'read' => true,
    'manage_hbc_vfn' => true,
]);

add_role('hbc_pmg', 'Postmaster General', [
    'read' => true,
    'manage_hbc_pmg' => true,
    'manage_hbc_reconciliation' => true,
]);
```

**Files to Update:**
- `includes/Activator.php` - Add roles on activation
- `includes/Admin.php` - Restrict menus by role

**Acceptance Criteria:**
- ✅ VFN dashboard exists for treasury cash flow management
- ✅ PMG dashboard exists for monthly reconciliation
- ✅ Custom WordPress roles created (VFN, PMG)
- ✅ Role-based access control implemented

---

## 10. Implementation Milestones

### Milestone 1: Terminology & Ledger Semantics (Priority: HIGH)
**Goal:** No more "earn" where it should be "pledge"

**Tasks:**
- [ ] Update all seller scan copy
- [ ] Update ledger entry metadata (add `pledge_type`)
- [ ] Update UI messages
- [ ] Update database schema
- [ ] Search & replace "earn" → "pledge" throughout codebase

**Estimated Time:** 2-3 days

### Milestone 2: POC Model Enforcement (Priority: HIGH)
**Goal:** Enforce 5 sellers + 25 buyers structure

**Tasks:**
- [ ] Create `wp_hbc_seller_buyer_assignments` table
- [ ] Update Serendipity protocol to assign buyers to sellers
- [ ] Implement binding logic (25 buyers → 5 sellers)
- [ ] Create POC structure admin page
- [ ] Create `[hbc_my_poc]` shortcode
- [ ] Update database schema

**Estimated Time:** 5-7 days

### Milestone 3: Discord Gating + Referral Trigger (Priority: HIGH)
**Goal:** Discord required, referral attribution via Discord invites

**Tasks:**
- [ ] Update membership flow (Discord required)
- [ ] Implement Discord OAuth/bot integration
- [ ] Update referral system (Discord invite primary)
- [ ] Update database schema (add Discord fields)
- [ ] Update DiscordService (real implementation)

**Estimated Time:** 4-5 days

### Milestone 4: Correct Referral Awards (Priority: MEDIUM)
**Goal:** YAM amounts at 21,000:1 peg, not "XP 1/5/25"

**Tasks:**
- [ ] Create/update referral calculation logic
- [ ] Update database schema (YAM amounts)
- [ ] Update UI displays
- [ ] Update admin reporting

**Estimated Time:** 2-3 days

### Milestone 5: Delivery Allocation Logic (Priority: MEDIUM)
**Goal:** $0.40 routing based on POC activity

**Tasks:**
- [ ] Update ledger allocation logic
- [ ] Create group bonus pool tables
- [ ] Implement routing logic (active vs inactive POC)
- [ ] Update UI to show allocation breakdown

**Estimated Time:** 3-4 days

### Milestone 6: Month-End Reconciliation (Priority: MEDIUM)
**Goal:** Device tranche close + annual milestones

**Tasks:**
- [ ] Create device tranche table
- [ ] Implement month-end tranche close
- [ ] Implement annual milestones (Aug 31, Sep 1, Aug 11)
- [ ] Create admin dashboard
- [ ] Update WP-CRON schedules

**Estimated Time:** 5-6 days

### Milestone 7: NWP Licensing (Priority: LOW)
**Goal:** Reframe NWP as licensing capability

**Tasks:**
- [ ] Create NWP license registry table
- [ ] Implement NWP issuance policy engine
- [ ] Update ledger event types
- [ ] Update UI text (remove "mint" language)

**Estimated Time:** 3-4 days

### Milestone 8: Membership Pricing (Priority: LOW)
**Goal:** Define actual membership pricing structure

**Tasks:**
- [ ] Create membership pricing table
- [ ] Update membership registration UI
- [ ] Implement pledge tracking
- [ ] Update documentation

**Estimated Time:** 2-3 days

### Milestone 9: VFN/PMG Dashboards (Priority: LOW)
**Goal:** Admin dashboards for VFN and PMG responsibilities

**Tasks:**
- [ ] Create VFN dashboard
- [ ] Create PMG dashboard
- [ ] Add custom WordPress roles
- [ ] Implement role-based access

**Estimated Time:** 3-4 days

**Total Estimated Time:** 29-38 days

---

## 11. Database Schema Changes

### New Tables Required:

1. `wp_hbc_seller_buyer_assignments` - Seller-to-buyer assignments (5 buyers per seller)
2. `wp_hbc_group_bonus_pools` - Group bonus pools per POC
3. `wp_hbc_group_bonus_contributions` - Contributions to group bonus pools
4. `wp_hbc_device_tranches` - Device tranche records
5. `wp_hbc_nwp_licenses` - NWP license registry
6. `wp_hbc_membership_pricing` - Membership pricing structure
7. `wp_hbc_referrals` - Referral records (if not exists)

### Tables to Modify:

1. `wp_hbc_devices` - Add Discord fields, assignment fields
2. `wp_hbc_ledger` - Add `pledge_type`, `membership_pledge_type`, `platform_contribution`, routing fields
3. `wp_hbc_reconciliation` - Add tranche close records

---

## 12. API Endpoint Updates

### New Endpoints Required:

1. `GET /wp-json/hbc/v1/poc/structure/{device_hash}` - Get POC structure for device
2. `POST /wp-json/hbc/v1/poc/bind` - Bind Buyer POC to Seller POC (admin)
3. `GET /wp-json/hbc/v1/referrals/{device_hash}` - Get referral data
4. `POST /wp-json/hbc/v1/discord/connect` - Connect Discord account
5. `GET /wp-json/hbc/v1/tranches/{device_hash}` - Get device tranches
6. `POST /wp-json/hbc/v1/reconciliation/close-tranches` - Close tranches (VFN)
7. `GET /wp-json/hbc/v1/nwp/qualify/{voucher_id}` - Check NWP qualification

### Endpoints to Update:

1. `POST /wp-json/hbc/v1/register-device` - Require Discord connection
2. `POST /wp-json/hbc/v1/pod/initiate` - Update language (pledge not earn)
3. `POST /wp-json/hbc/v1/pod/confirm` - Update allocation routing logic

---

## 13. UI/UX Updates

### Pages to Update:

1. **Seller Scan Flow:**
   - Change "Seller receives $10.30" → "Seller issues $10.30 pledge"
   - Update success messages

2. **Buyer Scan Flow:**
   - Update confirmation messages
   - Show allocation breakdown

3. **Membership Registration:**
   - Make Discord required
   - Show actual pricing
   - Update referral display (YAM amounts)

4. **Admin Pages:**
   - POC Structure viewer
   - Group Bonus Pool management
   - Device Tranche viewer
   - VFN Dashboard
   - PMG Dashboard
   - Referral reporting (YAM amounts)

5. **Public Shortcodes:**
   - `[hbc_my_poc]` - Show POC structure
   - Update `[hbc_receipt]` - Show allocation breakdown

---

## Next Steps

1. **Review this document** with the team
2. **Prioritize milestones** based on business needs
3. **Create detailed tickets** for each milestone
4. **Begin implementation** starting with Milestone 1 (Terminology)
5. **Test each milestone** before moving to the next
6. **Update documentation** as changes are implemented

---

## Questions to Resolve

1. **Discord Integration:**
   - OAuth flow or bot-based?
   - Which Discord server?
   - Bot token management?

2. **QRtiger Integration:**
   - API credentials?
   - v-card format/structure?

3. **Payment Processing:**
   - How are membership pledges collected? (Venmo, FonePay, etc.)
   - Integration with existing payment systems?

4. **VFN/PMG Access:**
   - Who are the initial VFN/PMG members?
   - How are they assigned?

5. **Treasury Management:**
   - Where is treasury cash flow actually managed?
   - Integration with accounting systems?

---

**Document End**
