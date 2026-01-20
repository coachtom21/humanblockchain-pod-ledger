# System Flow: Pocketbook Brigade Cart + VFN-YAM JAM Economy
## Part 1: Core Architecture & User Flow

**Date:** January 2026  
**Version:** 1.0  
**Status:** Based on Client Requirements  
**Purpose:** Complete system flow documentation for MEGAvoter.com → Pocketbook Brigade transition

---

## 🎯 Core Mission Statement

**One Sentence Mission:**
Convert MEGAvoter.com into a Pocketbook Brigade shopping cart where every pledge creates a ledger obligation, and every obligation can only be settled through 2-scan Proof of Delivery on a registered device that has scanned the YAM-is-On universal QR code.

**The Human Blockchain Anchor:**
> "Has your device ever scanned the YAM-is-On universal QR code?"
> 
> This is the cornerstone question. This scan creates the economic identity. It's hard to cheat because:
> - Device generates timestamp + geo-location + device signature
> - Every settlement requires 2 scans (seller + buyer)
> - Fraud requires multiple coordinated devices + behavior history
> - That's the "real world friction" that makes it a human blockchain

---

## 📋 Table of Contents

1. [Website Transition](#website-transition)
2. [Participation Tiers](#participation-tiers)
3. [Device Registration Flow](#device-registration-flow)
4. [2-Scan Proof of Delivery Workflow](#2-scan-proof-of-delivery-workflow)
5. [Transaction ID System](#transaction-id-system)
6. [Ledger Structure](#ledger-structure)
7. [Governance Calendar](#governance-calendar)
8. [Value & Rewards System](#value--rewards-system)
9. ["How Money Works" Doctrine](#how-money-works-doctrine)
10. [Hang Tag Credentialing](#hang-tag-credentialing)
11. [Complete User Journey](#complete-user-journey)

---

## 1. Website Transition

### Strategic Move: MEGAvoter.com → Pocketbook Brigade Cart

**Goal:**
Convert MEGAvoter.com into a single-purpose shopping cart site focused on pre-order/backorder distribution.

**Actions Required:**
- ✅ Unpublish ALL existing MEGAvoter.com pages
- ✅ Keep only these pages:
  - Home (Pocketbook Brigade Cart Landing)
  - Shop
  - Cart
  - Checkout
  - My Account
  - Register Device
  - Proof of Delivery
  - FAQ
  - Legal / Disclaimer

**Site Language Rules (Critical):**
- ✅ Use: **Pledge** / **Promise to Pay** / **Backorder**
- ❌ Never use: "payment" framing
- ✅ Make clear: **"Settlement only occurs upon Proof of Delivery"**

**WooCommerce Configuration:**
- Products sold as **Pre-orders / Backorders**
- Checkout language: "This is a pledge" + "Delivery required for settlement"
- Product categories:
  - Organized Krill merchandise (shirts/hats/etc.)
  - Services (events, sponsorships, licenses)
  - Voucher packs / PoD hang tags

---

## 2. Participation Tiers

### Tier 1: YAM JAM Member (Open Participation)

**Status:** ✅ Anyone can participate

**Required:**
- Scan universal YAM-is-On QR code
- Register device (email minimum)

**Optional:**
- QR Tiger v-card link
- Discord invite acceptance

**NOT Required:**
- ❌ PayPal/Venmo credentials

**What YAM JAM Members Can Do:**
- Scan universal QR
- Earn XP
- Join Discord Gracebook
- Participate in POC assignments
- Complete Proof-of-Delivery scans
- Build trust history

---

### Tier 2: VFN Member (Opt-in "Book Closers")

**Status:** ✅ Opt-in volunteer reconciliation role

**Required:**
- Scan universal YAM-is-On QR code
- Register device
- QR Tiger v-card
- PayPal/Venmo credentials (or Alipay/FonePay)
- Agreement checkbox: **"I will post monthly totals of receipts & obligations"**

**What VFN Members Do:**
- Post monthly totals of receipts and obligations
- Act as tranche reporters
- Serve as monthly reconcilers
- Provide visibility clearing
- Function as "human blockchain accountants"

**Permission:**
- ✅ Only VFN can submit/attest monthly tranche totals
- ❌ Non-VFN cannot access tranche-close endpoints (403 error)

---

## 3. Device Registration Flow

### Core Truth Model

**The Device is Identity:**
- Not email
- Not wallet address
- Not username
- **The device is the proof anchor**

### Registration Requirements

**For YAM JAM (Tier 1):**
```
User scans universal QR code
    ↓
Device Registration Page
    ↓
Required Fields:
    - Email
    - Device permission acceptance (location + camera)
    ↓
Optional Fields:
    - QR Tiger v-card link
    - Discord invite acceptance
    ↓
System Output:
    - device_id issued
    - has_scanned_universal_qr = true
    - Ledger wallet created (xp_balance, pending_xp, pledge_balance)
    - Peace Pentagon branch assignment (serendipity)
    - Buyer POC assignment (local)
    - Seller POC assignment (global)
```

**For VFN Upgrade (Tier 2):**
```
YAM JAM Member clicks "Join VFN"
    ↓
VFN Upgrade Form
    ↓
Required Fields:
    - QR Tiger v-card (required for VFN)
    - PayPal/Venmo credentials (or Alipay/FonePay)
    - Agreement checkbox: "I will post monthly totals"
    ↓
System Output:
    - VFN role enabled
    - Tranche posting permissions granted
    - VFN member status activated
```

---

## 4. 2-Scan Proof of Delivery Workflow

### Card-Swipe Metaphor

This is the core algorithm that makes the system work.

### Scan 1: Seller Authorization

**Purpose:** Creates open obligation (like card swipe authorization)

**What Happens:**
- Seller initiates pledge
- Obligation "open" status created
- Buyer named/identified
- Receipt intent recorded

**Data Captured:**
- `seller_device_id`
- `buyer_identifier` (device_id or email/handle)
- `order_id`
- `timestamp` (server-signed)
- `geo` (latitude/longitude)
- `voucher_id` / `hang_tag_id` (optional now, required later)

**Ledger Entry Created:**
- `POD_AUTHORIZED`
- Status: `PENDING`

---

### Scan 2: Buyer Settlement

**Purpose:** Closes obligation permanently (like card swipe settlement)

**What Happens:**
- Buyer confirms receipt
- Obligation "settled" status
- Reward lifecycle triggered
- XP maturity countdown begins

**Data Captured:**
- `buyer_device_id`
- `order_id` (must match Scan 1)
- `timestamp` (server-signed)
- `geo` (latitude/longitude)
- `confirmation_status` (Y/N)
- `photo` (optional)

**Ledger Entry Created:**
- `POD_SETTLED`
- Status: `ACTIVE`
- XP granted: `XP_PENDING_GRANTED`

**Critical Rules:**
- ✅ Scan 2 requires Scan 1 to exist
- ✅ Scan 2 cannot happen twice (idempotent)
- ✅ Settled records cannot be edited (append-only)

**Pocketbook Change (Consumer Spending Capture):**
Consumer spending is captured through this device-driven, 2-scan Proof of Delivery system. Every exchange becomes verifiable only when:
- The seller initiates the pledge (Scan #1)
- The buyer confirms receipt (Scan #2)

That second scan is the moment human value becomes ledger value.

**New World Penny (NWP) = Service Recognition:**
Every confirmed delivery generates a seller-sponsored New World Penny (NWP) — not as "money," but as:
- A human engagement resource
- A service award
- A unit of proof-backed value inside the Human Blockchain

**More deliveries = more pennies**, meaning: the system rewards service participation, not wealth hoarding.

---

## 5. Transaction ID System

### Core Rule: Universal QR + Seller-Initiated TXID

**Universal QR Code:**
- Same QR code for everyone (entry point to PoD flow)
- Contains only redirect URL, no unique data per tag
- Universal QR = entry point, not unique identifier

**Transaction ID (TXID):**
- **Seller-initiated Transaction ID = the ONLY unique identifier**
- Generated by seller during Scan #1
- Buyer assignment is optional at creation, becomes bound during Scan #2

### Transaction Data Model

**Transaction Record Fields:**
```
txid (unique, seller-generated; primary key)
seller_poc_id
sponsor_mode = INDIVIDUAL | GROUP
circle_branch = PLANNING | BUDGET | MEDIA | DISTRIBUTION | MEMBERSHIP
credential_id (hang tag/voucher serial)
status = PENDING | ACTIVE | EXTINGUISHED
issued_at (timestamp)
issued_geo (optional)
buyer_device_id (nullable until Scan #2)
buyer_member_id (nullable until Scan #2)
confirmed_at (nullable until Scan #2)
confirmed_geo (nullable until Scan #2)
xp_value_usd (default 10.30)
yam_redemptive_value (default 105000 YAM for $5 component)
matures_at (issued_at + 8–12 weeks policy)
extinguished_at (nullable)
extinguish_rate (96.5% or 100% Sept 1)
audit_hash (optional append-only integrity)
```

### Transaction ID Flow

**Scan #1 — Seller Initiates (Creates TXID):**
```
Seller scans universal QR
    ↓
Seller authentication (device registration + Discord accepted)
    ↓
Seller provides:
    - seller POC / branch / sponsor mode
    - credential serial (hang tag/voucher)
    ↓
Backend Actions:
    - Generate TXID (unique, non-guessable)
    - Create transaction record:
        status = PENDING
        buyer_* = null
    - Store seller device + geo + time
    ↓
Return TXID receipt (screen + optional QR/short code)
    ↓
✅ Result: Pending XP obligation exists, but not yet validated
```

**Scan #2 — Buyer Confirms (Binds Buyer to TXID):**
```
Buyer scans the same universal QR
    ↓
Buyer enters/scans TXID (or receives via seller display/QR)
    ↓
Buyer device registration + Discord accepted
    ↓
Backend Actions:
    - Lookup transaction by txid
    - Validate eligibility:
        exists, not expired/cancelled
        status must be PENDING
        credential not already confirmed elsewhere
    - Bind buyer identity:
        set buyer_device_id, buyer_member_id
        set confirmed_at, confirmed_geo
    - Set status = ACTIVE
    - Start maturity clock (matures_at per 8–12 week policy)
    ↓
✅ Result: Active XP is now booked as community resource tied to TXID
```

**Extinguishment (Redemption Closes Obligation):**
```
Redemption Event — Extinguish TXID
    ↓
Inputs:
    - txid
    - redemption method (YAM)
    - date context (Sept 1 or monthly)
    ↓
Backend Actions:
    - Validate:
        status must be ACTIVE
        maturity met (unless annual reconciliation override)
    - Compute extinguish rate:
        96.5% standard months
        100% on Sept 1 Redemption Day
    - Mark closed:
        status = EXTINGUISHED
        set extinguished_at
        store extinguish_rate and redemption reference
    ↓
✅ Result: Obligation settled, ledger shows no open community-resource liability
```

### Hang Tag / Voucher Sponsorship Rule

**POC Transaction ID Binding:**
Every hang tag or voucher must carry a Transaction ID linked to the Seller POC.

**Transaction ID Contains (Minimum Fields):**
- Seller POC ID (primary)
- Circle / Branch Code (Peace Pentagon: Planning, Budget, Media, Distribution, Membership)
- Sponsor Type (INDIVIDUAL | GROUP)
- Sponsor ID (member ID / group ID)
- Unique Credential ID (tag serial / voucher serial)

**Without a Seller POC Transaction ID, the credential is invalid for XP booking.**

**Sponsorship Modes (Both Allowed):**

1. **Individual Sponsorship:**
   - Single individual issues and sponsors the pledge
   - Use case: personal reseller, single organizer, estate planner, independent captain
   - Transaction routes XP/patronage to individual's Seller POC

2. **Group Sponsorship:**
   - Group (circle / branch / seller collective) issues and sponsors the pledge
   - Use case: event booth, rally distribution team, mutual aid delivery circle
   - Transaction routes XP/patronage to designated group sponsorship wallet/ledger

**Key Guarantees:**
- ✅ Single source of truth: TXID is the only unique key
- ✅ Universal QR stays universal: QR never needs to be unique per tag
- ✅ Fraud resistance: TXID can only move forward (PENDING → ACTIVE → EXTINGUISHED) once, append-only
- ✅ Clean reporting: Seller POC attribution guaranteed at creation; buyer attribution guaranteed at confirmation

---

## 6. Ledger Structure

### Append-Only Requirement (Non-Negotiable)

**Core Rule:**
- ✅ **INSERT only**
- ❌ **No UPDATE/DELETE**
- Corrections happen via reversing entries (contra entries)

### Required Ledger Events

**Identity & Registration:**
- `DEVICE_REGISTERED`
- `UNIVERSAL_QR_SCANNED`

**Pledge & Delivery:**
- `PLEDGE_CREATED`
- `POD_AUTHORIZED`
- `POD_SETTLED`
- `XP_PENDING_GRANTED`
- `XP_MATURED` (optional rule)

**Reconciliation:**
- `TRANCHE_MONTH_END`
- `TRANCHE_FIRST_DAY`
- `ANNUAL_CLOSE_AUG31`
- `REDEMPTION_DAY_SEP1`

### Database Tables (Minimum)

1. **Devices Table**
   - device_id, device_hash, email, platform, geo, registered_at
   - has_scanned_universal_qr, vfn_enabled, branch, buyer_poc_id, seller_poc_id

2. **Orders Table**
   - order_id, device_id, pledge_amount, status, created_at

3. **PoD Events Table**
   - event_id, order_id, scan_type (1 or 2), device_id, timestamp, geo, status

4. **Ledger Entries Table** (Append-only)
   - entry_id, event_type, device_id, order_id, xp_amount, yam_amount, status
   - timestamp, geo, audit_hash, parent_entry_id (for reversals)

5. **Tranche Table**
   - tranche_id, period_start, period_end, totals_json, vfn_attestations, created_at

---

## 6. Governance Calendar

### Locked Dates (Do Not Change)

**✅ Inaugural Event:**
- **Date:** August 31, 2026
- **Purpose:** First official "Annual Close" of VFN–YAM JAM economy
- **Action:** System-wide reconciliation of all pledge activity

**✅ Annual Close (Every Year):**
- **Date:** Every August 31 (starting 2026)
- **What Closes:**
  - Annual receipts
  - Annual obligations
  - COGS
  - Revenues
  - Unredeemed XP
  - Pending maturity balances
  - Settlement status of Proof-of-Delivery transactions

**✅ Redemption Day (Every Year):**
- **Date:** Every September 1
- **What Happens:**
  - Redemption eligibility snapshot
  - Annual statements generated
  - Maturity waiver for completed actions
  - New annual cycle begins

**Site Rule Statement:**
> **"August 31 is Annual Close. September 1 is Redemption Day."**

### Monthly Tranche Processing

**End of Month (Last Day):**
- Post all receipts
- Post all obligations created (unsettled)
- Post PoD-completed settlements
- Post pending maturity XP

**First of Month (First Day):**
- Post disbursement allocations
- Post XP credit posting
- Post treasury reserve updates

**This is the heartbeat of the "Bank-like Constitution."**

---

## 8. Value & Rewards System

### Community Value Per Delivery

**Fixed Engagement Value:**
Every confirmed delivery inside the Human Blockchain system has a fixed community engagement value of:
- ✅ **$10.30 XP per Proof of Delivery**

This value is not speculation and not "market priced." It is a ledger-defined engagement unit created only when delivery is proven.

**Redemptive Value Component (YAM):**
Inside the $10.30 engagement value is a defined consumer redemption component:
- ✅ **$5 YAM redemptive value = 105,000 YAM**

**Math:**
- 1 USD = 21,000 YAM
- Therefore $5 = 105,000 YAM

This is the buyer-visible incentive that makes participation obvious and rewarding.

### 10-Pack Hang Tags

**Complimentary 10-Pack with Device Registration:**
Every newly registered device qualifies for:
- ✅ Complimentary 10-pack of Hang Tags (pre-order)

This creates the "starter engine":
- Devices onboard first
- PoD capacity is granted
- Marketplace participation becomes immediate

**10-Pack Ledger Logic:**
Since each validated PoD = $10.30 XP, then:
- ✅ **1 validated 10-pack = $103.00 XP community value**

**Math:**
- $10.30 × 10 deliveries = $103.00

**Scientific Notation:**
- $103 XP = **1.03 × 10² USD**
- In sextillionth-of-a-penny micro-units: **1.03 × 10²⁵ sextillionth-of-a-penny units**

This value is appended into the General Ledger only after:
- All 10 hang tags are activated
- Each one completes the 2-scan process
- Buyer acceptance occurs

### XP (Engagement Value)

**Definition:**
- XP = Sextillionth-of-a-penny atomic units
- 1 XP = 10⁻²³ USD
- 1 USD = 10²³ XP
- **XP is NOT money** - it's engagement accounting

**Engagement Units = Sextillionth-of-a-Penny Denomination:**
Rewards are denominated in sextillionths of a penny (micro-value beyond visibility), signaling clearly:
- This is not a currency to hoard
- This is engagement accounting
- Value is tied to verified participation

It's a public declaration: **"Human engagement is the asset — not the fiat."**

### XP Stages in General Ledger

**Three-Stage Lifecycle:**

1. **Pending XP:**
   - Pre-order pledge or backorder pledge is issued
   - Obligation exists, but no Proof of Delivery confirmed yet
   - XP is authorized but not yet earned

2. **Active XP:**
   - Proof-of-Delivery completed through 2-scan protocol
   - Seller scan initiates pledge
   - Buyer scan confirms acceptance
   - Ledger recognizes obligation as validated
   - XP becomes live engagement credit and community resource entry

3. **Settled XP (Extinguished):**
   - Obligation is extinguished
   - Extinguishment occurs by redemption in YAM
   - Ledger entry closes (no carry-forward as unresolved obligation)

**Status Progression:**
```
PLEDGE_CREATED (Pending)
    ↓
POD_AUTHORIZED (Pending)
    ↓
POD_SETTLED (Active)
    ↓
XP_PENDING_GRANTED (Active)
    ↓
XP_MATURED (8-12 weeks) (Active)
    ↓
XP_REDEEMED/EXTINGUISHED (Settled)
```

### YAM (Redemption Value)

**Peg Rate:**
- **21,000 YAM = 1 USD** (locked)
- All redemptions based on YAM value
- Balances denominated in XP atomic units

**Extinguishment (Redemption Rules):**
Redemption = extinguishment of the community obligation.

**Monthly Standard Redemption:**
- ✅ **96.5% of face value**
- Applies to all months prior to Redemption Day

**September 1st Redemption Day:**
- ✅ **100% of face value**
- Full settlement day
- Annual "moment of truth" ledger settlement

**Display Format:**
- Primary: XP (atomic units)
- Derived: YAM-equivalent
- Reference: USD (display-only)

### Maturity Window (8–12 Weeks)

All pledges carry a minimum 8–12 week maturity period before any redemption becomes possible.

**This maturity window exists to ensure:**
- No instant extraction
- Reduced fraud incentives
- Real-world delivery proof precedes financial realization

---

## 8. Complete User Journey

### Journey 1: YAM JAM Member (Open Participation)

```
1. Visitor arrives at MEGAvoter.com
    ↓
2. Sees Pocketbook Brigade Cart
    ↓
3. Scans universal YAM-is-On QR code
    ↓
4. Device Registration Page
    - Enters email
    - Accepts device permissions
    ↓
5. Device registered
    - device_id issued
    - has_scanned_universal_qr = true
    - Branch/POC assignments made
    ↓
6. Can now:
    - Browse shop
    - Place pre-orders (pledges)
    - Participate in 2-scan PoD
    - Earn XP
    - Build trust history
```

### Journey 2: VFN Member (Opt-in Upgrade)

```
1. YAM JAM Member clicks "Join VFN"
    ↓
2. VFN Upgrade Form
    - Provides QR Tiger v-card
    - Provides PayPal/Venmo credentials
    - Checks agreement: "I will post monthly totals"
    ↓
3. VFN role activated
    - VFN permissions granted
    - Can now submit tranche totals
    ↓
4. Monthly Responsibilities:
    - Post monthly totals of receipts & obligations
    - Attest tranche close batches
    - Serve as "human blockchain accountant"
```

### Journey 3: 2-Scan Proof of Delivery

```
1. Seller receives order/pledge
    ↓
2. Seller Scan (Scan 1):
    - Scans universal QR
    - Selects order
    - Initiates delivery
    - System creates: POD_AUTHORIZED
    ↓
3. Seller delivers to buyer
    ↓
4. Buyer Scan (Scan 2):
    - Scans same universal QR
    - Enters order_id or scans TXID
    - Confirms receipt
    - System creates: POD_SETTLED
    ↓
5. System Actions:
    - XP granted (pending maturity)
    - NWP issued (seller recognition)
    - Ledger entries created (append-only)
    - Obligation closed
```

---

## 9. "How Money Works" Doctrine

### Official Hang Tag Moniker

**"HOW MONEY WORKS" (From Now On)**

This is the standard moniker (repeatable meaning) tied to every MEGAvoter hang tag.

**Doctrine Text:**
> How Money Works from now on is simple:
> 
> Consumer spending becomes community value only when delivery is proven.
> 
> Every exchange is validated by 2-scan Proof of Delivery:
> - Seller Scan = pledge issued
> - Buyer Scan = receipt accepted
> 
> Each confirmed delivery appends **$10.30 XP engagement value** to the General Ledger.
> 
> A 10-pack of accepted Proofs of Delivery appends **$103 XP** in community value.
> 
> XP moves through three General Ledger stages:
> - **Pending** (pledge issued)
> - **Active** (Proof accepted)
> - **Settled** (obligation extinguished)
> 
> Extinguishment = redemption in YAM:
> - 96.5% monthly (standard redemption)
> - 100% on September 1st Redemption Day
> 
> New World Pennies (NWP) recognize service:
> - More deliveries = more pennies.
> 
> Rewards are measured in sextillionths of a penny — so value is earned by participation, not hoarding.
> 
> **That's How Money Works.**

**Hang Tag Usage Rule (Brand Standard):**
> "This tag is not decoration — it is a delivery credential that turns human action into ledger value."

---

## 10. Hang Tag Credentialing

### Full Credentialing Requirements

**A hang tag becomes an official Proof-of-Delivery credential only after two actions are completed:**

**✅ 1) Device Registration (Required)**
Your mobile device must be registered to:
- Bind delivery actions to a verified device ID
- Capture geo-location + timestamp for the ledger
- Prevent fraud through device accountability

**✅ 2) Discord Invite Acceptance (Required)**
You must accept the Discord Gracebook invitation to:
- Receive your Peace Pentagon role assignment
- Activate Buyer/Seller POC placement (Serendipity Protocol)
- Receive XP maturity tracking and settlement eligibility

**Credential Rule (Simple / Non-Negotiable):**
- ❌ No Registered Device = No XP
- ❌ No Discord Acceptance = No Membership Ledger Rights
- ❌ No 2-Scan Proof = No Delivery Confirmation

**Hang Tag Activation Clause:**
> This tag is a delivery credential.
> 
> It is activated only by 2-scan Proof of Delivery using a registered device and an accepted Discord Gracebook membership invite.
> 
> Seller scan initiates the pledge. Buyer scan confirms receipt.
> 
> Only confirmed deliveries append XP community value to the ledger.

### Proof Requirement: Voucher / Hang Tag Only

A delivery is only recognized when completed by:
- ✅ Voucher or Hang Tag activation
- ✅ Using the required 2-scan Proof of Delivery protocol

**Two scans required:**
1. Seller Scan (pledge initiation)
2. Buyer Scan (receipt confirmation)

**Without both scans, no Proof of Delivery exists and no XP is booked.**

**IMPORTANT: No Stamps (Policy)**
Pocketbook Brigade is built on a strict policy:
- ✅ Vouchers / Hang Tags only
- ❌ No "stamps" of any kind (term prohibited)

---

## 🔑 Key Principles

### Small Street Applied–Atlanta Ethos

**Wall Street vs Small Street (Core Ethos Statement):**

**Wall Street Ethos:**
- Perpetual hoarding
- Speculation-first trading
- Value measured by:
  - Price movement
  - Volatility
  - Scarcity
  - Insider advantage
- ✅ It rewards those who already have money

**Small Street Applied–Atlanta Ethos:**
- Perpetual participation
- Proof-first accounting
- Value measured by:
  - Verified human action
  - Delivery and service
  - Local economic exchange
  - Trust history anchored to devices
- ✅ It rewards those who show up

**The SSA Breakthrough: Separate Engagement Value from Redemption Value**

This is the keystone concept:

**1) Engagement Value = XP Micro-Pennies:**
- XP is NOT money
- XP is recorded in micro-penny atomic units
- XP is an accounting measure of verified human participation
- It is intentionally too small to "get rich" from
- Its purpose is to prove involvement without triggering custodial / fiat framing
- XP is the visible trail of human blockchain trust

**2) Redemption Value = YAM-Pegged Obligations:**
- Redemption is based on the YAM peg: **21,000 YAM = 1 USD** (reference)
- Redemption only occurs via rules:
  - Tranche closing
  - Maturity
  - Annual close (Aug 31)
  - Redemption Day (Sep 1)
- Redemption value is governance-controlled, not market-hyped

**3) Recognition Value = NWP (New World Penny):**
- Seller/service acknowledgment token
- Attaches to confirmed PoD events
- Builds "proof of economic life" visibility
- NWP doesn't measure money — it measures "service delivered"

**"Then Trading" — But Trading Based on Participation, Not Hoarding:**

SSA is not anti-trade — it is anti-speculation without service.

Trading becomes legitimate only when:
- A device has scanned the universal QR
- 2-scan PoD confirms delivery
- Ledger captures receipts and obligations
- Tranche visibility and reconciliation occurs

So the system "trades" because life happened, not because someone hoarded an asset.

**Consumer Spending is Tracked by NWP Seller Recognition:**

**Traditional Economy:**
Consumer spending is hidden in:
- Credit card networks
- Bank ledgers
- Private reporting
- Hidden fee systems

**SSA Economy:**
Consumer spending becomes visible through:
- ✅ NWP (New World Penny) seller recognition

**Meaning:**
Every fulfilled pledge / delivery produces:
- Proof of Delivery record
- Seller recognition
- NWP service acknowledgment

**Consumer activity becomes:**
- Auditable
- Countable
- Localizable
- Human-trust-based

That's why it tracks real consumer spending better than Wall Street ever can.

**The Breakthrough Statement:**
> "VFN is just people doing deliveries.
> YAM JAM is just the ledger of that service.
> Your phone becomes your receipt book.
> A pledge becomes real only when Proof of Delivery happens."
> 
> "21,000 to 1 isn't magic — it's just bookkeeping for human value."

**UI/Language for the Website:**
> Wall Street measures hoarding.
> Small Street measures living.
> Every scan proves participation.
> Every delivery proves service.
> XP tracks engagement in micro-pennies.
> YAM tracks redemption obligations.
> NWP recognizes sellers and proves consumer spending happened.

### Three Distinct Ledgers (Implementation Requirement)

To support this doctrine in code, we need 3 distinct ledgers:

**A) Engagement Ledger (XP):**
- Atomic micro-units
- Earned by participation events
- Never treated as "cash"

**B) Redemption Ledger (YAM-based reference conversion):**
- Obligation tracking
- Tranche closing
- Annual close + Redemption Day controls

**C) Recognition Ledger (NWP):**
- Seller/service acknowledgment token
- Attaches to confirmed PoD events
- Builds "proof of economic life" visibility

---

## 📊 System Architecture Summary

**Three Core Systems:**
1. **Pocketbook Brigade Cart (WooCommerce)** - Creates pledges
2. **Device Identity + Universal QR** - Creates trust anchor
3. **2-Scan PoD + Append-Only Ledger** - Creates settlement truth

**Scale Strategy:**
- Device is identity (not email/wallet)
- Event-stream based ledger
- Tranche batching for heavy compute
- Server-signed timestamps (never trust client)
- Idempotent endpoints (retry-safe)

### Scale & Security Requirements

**This system must not be easy to cheat.**

**Required Protections:**
- ✅ Rate limits for scan endpoints
- ✅ Replay protection
- ✅ Server signed timestamps
- ✅ Idempotent endpoints (retry safe)
- ✅ Device token/session signature

**Performance Strategy:**
- Write events fast
- Calculate totals in batch (monthly/yearly)
- Never compute heavy totals on normal user page loads

**Scale Reality (How to Build So It Doesn't Collapse):**

To support large participation, the system should treat the ledger as an event stream:
- Store raw PoD events (fast writes)
- Store ledger entries (append-only, audit)
- Compute totals in batch (month-end / year-end), not on every page load

**Minimum "Don't Paint Yourself Into a Corner" Requirements:**
- Idempotent endpoints (retries don't double-write)
- Rate limiting on scan endpoints
- Server-signed timestamps (don't trust client time)
- Device token/session signature to reduce replay

**Minimum Viable Scale Stack:**
- WordPress front-end / WooCommerce
- Separate backend service for ledger + PoD logs (even simple PHP API on AWS)
- Database:
  - device table
  - pod_events table (scan1/scan2)
  - ledger_entries table
  - tranche table

---

## 🛠️ Implementation Roadmap & Milestones

### Development Roadmap (Sequenced Milestones)

**What "Done" Means for This Build:**

There are three systems that must work together:
1. Pocketbook Brigade Cart (WooCommerce)
2. Device Identity + Universal QR scan history
3. 2-Scan Proof of Delivery + Append-Only Ledger + Tranche Close (VFN-only)

**The Gating Factor:**
- Clean data model + event log
- Role permissions (YAM JAM vs VFN)
- Auditability (append-only)
- Mobile reliability (scan UX + permissions)

### Milestone A — Site Takeover (Cart-Only)

**Scope:**
- Unpublish all MEGAvoter pages
- Replace with Pocketbook Brigade Cart-only navigation
- Checkout language = pledge/promise-to-pay + PoD settlement framing

**Acceptance:**
- ✅ Only approved pages are live
- ✅ A product can be pledged from shop → checkout → order created
- ✅ Order is labeled "PLEDGE_CREATED" (not "paid")
- ✅ No "payment settlement" language

**Timeframe:** Week 1–2

---

### Milestone B — YAM JAM Open Participation (No PayPal/Venmo)

**Scope:**
- Universal QR scan landing
- Device registration (Tier 1: anyone)
- Device gets device_id + "has_scanned_universal_qr = true"
- Optional: QR Tiger v-card link capture
- Optional: Discord join CTA

**Acceptance:**
- ✅ A brand-new visitor can scan the universal QR and get a device_id
- ✅ Device history shows the universal scan event
- ✅ No PayPal/Venmo is required anywhere in Tier 1

**Timeframe:** Week 3–4

---

### Milestone C — VFN Opt-In Upgrade (PayPal/Venmo Required)

**Scope:**
- Add "Join VFN" upgrade path
- Capture PayPal/Venmo only for VFN
- VFN terms checkbox: "I will post monthly totals of receipts & obligations"
- Permission switch: only VFN can submit tranche totals

**Acceptance:**
- ✅ A Tier 1 user can remain non-VFN forever
- ✅ VFN upgrade requires PayPal/Venmo + agreement checkbox
- ✅ Non-VFN cannot access tranche-close endpoints (403)

**Timeframe:** Week 3–4 (parallel with Milestone B)

---

### Milestone D — 2-Scan Proof of Delivery (Card-Swipe Model)

**Scope:**
- Scan 1 (seller authorization): creates open obligation
- Scan 2 (buyer settlement): closes obligation permanently
- Event capture: timestamp + geo + device signature
- PoD timeline UI for the user + admin audit feed

**Acceptance:**
- ✅ You can execute: Scan 1 → Scan 2 → "POD_SETTLED"
- ✅ Scan 2 cannot happen without Scan 1
- ✅ Scan 2 cannot happen twice
- ✅ Ledger shows both events, append-only

**Timeframe:** Week 5–7

---

### Milestone E — Append-Only Ledger Engine

**Scope:**
- A dedicated ledger table/log with immutable entries
- Reversals are allowed, edits are not
- Ledger entry types:
  - DEVICE_REGISTERED
  - UNIVERSAL_QR_SCANNED
  - PLEDGE_CREATED
  - POD_AUTHORIZED
  - POD_SETTLED
  - XP_PENDING_GRANTED
  - XP_MATURED (optional rule)
  - TRANCHE_MONTH_END
  - TRANCHE_FIRST_DAY
  - ANNUAL_CLOSE_AUG31
  - REDEMPTION_DAY_SEP1

**Acceptance:**
- ✅ No UPDATE/DELETE permitted on ledger entries (enforced in code + permissions)
- ✅ Any correction creates a compensating entry
- ✅ A single device's full ledger can be exported
- ✅ Build fails if ledger UPDATE/DELETE present
- ✅ Every important action emits ledger entry

**Timeframe:** Week 8–10

---

### Milestone F — Monthly Tranche Automation (VFN-Only Posting)

**Scope:**
- Month-end aggregation job:
  - Totals receipts
  - Totals obligations open/closed
  - Totals PoD settled
- First-day posting job:
  - Updates balances
  - Carries forward pending maturity
- VFN member submits "monthly totals" record (or signs the batch)

**Acceptance:**
- ✅ Month-end job produces deterministic totals
- ✅ VFN-only can "finalize/attest" monthly totals
- ✅ Admin dashboard shows month totals + list of VFN attestations
- ✅ Month-end totals reproducible

**Timeframe:** Week 8–10 (parallel with Milestone E)

---

### Milestone G — Inaugural Annual Close + Redemption Day (Locked Calendar)

**Scope:**
- Aug 31, 2026 = inaugural annual close event logic
- Every Aug 31 thereafter = annual close
- Every Sept 1 = redemption day snapshot + statements

**Acceptance:**
- ✅ Simulate calendar dates in staging:
  - Run Annual Close
  - Generate statement summaries per device
  - Run Redemption Day snapshot
- ✅ Public rule displayed: "Aug 31 Annual Close. Sept 1 Redemption Day."
- ✅ Statements generated per device

**Timeframe:** Week 11–12

---

## 🤖 Cursor AI Agency Prompts

**These prompts are designed so Cursor produces production-grade code, not vague pseudocode.**

### Prompt 1 — SPEC First (No Code Yet)

```
Create a single source-of-truth SPEC.md for this system with: 
roles (YAM JAM vs VFN), event types, state machine, database schema, 
API endpoints, and acceptance tests for each milestone. 
No code until SPEC.md is complete.
```

### Prompt 2 — Two-Tier Registration

```
Implement device registration with tiers:
Tier 1 YAM JAM: no PayPal/Venmo
Tier 2 VFN: requires PayPal/Venmo + agreement
Enforce tranche permissions.
```

### Prompt 3 — Append-Only Ledger

```
Implement ledger with INSERT only. 
Add unit test: fail build if UPDATE/DELETE touches ledger table.
```

### Prompt 4 — 2-Scan PoD Logic

```
Implement scan1 and scan2 endpoints + guards + audit log.
Include fraud checks:
- mismatched geo anomalies
- duplicate scan2 prevention
- time window flags
```

### Prompt 5 — Tranche System (VFN Only)

```
Implement month-end + first-day batch jobs and VFN attestation signature.
Output admin dashboard:
- total receipts
- total obligations open
- PoD settled count
- XP pending matured by window
```

### Prompt 6 — Annual Close Simulation

```
Build staging "time travel" to simulate Aug31 annual close + Sep1 redemption.
Deliver:
- Statement generator per device (PDF/CSV)
- Public metrics page: totals + transparency
```

### Prompt 7 — State Machine (The Heart)

```
Implement the pledge→PoD→ledger state machine as code with explicit 
transitions and guards. Include unit tests that prove: 
scan2 can't happen without scan1, scan2 can't repeat, 
non-VFN can't attest tranches.
```

### Prompt 8 — Scale Discipline

```
Design endpoints to be idempotent, add replay protection, rate limits, 
and server-signed event timestamps. Provide a load-safe batching 
strategy for month-end totals.
```

---

## 📊 XP Math & Redemption Rules (Locked)

### XP Atomic Units

**Definition:**
- 1 XP = 1 sextillionth of a penny
- 1 penny = $0.01
- 1 XP = 10⁻²¹ pennies = 10⁻²³ USD
- 1 USD = 100 pennies = 10²³ XP

**Example:**
- 210,000 XP = 210,000 × 10⁻²³ USD = 2.1 × 10⁻¹⁸ USD
- (Nowhere near a penny)

### YAM Peg Bridge

**YAM Peg:**
- 21,000 YAM = 1 USD
- Therefore 1 USD = 10²³ XP = 21,000 YAM
- So 1 YAM = (10²³ / 21,000) XP ≈ 4.7619047619 × 10¹⁸ XP

**This is the bridge between engagement accounting (XP) and redemption accounting (YAM peg).**

### Redemption Standard (Non-Negotiable)

**Core Rule:**
✅ All redemptions are based on YAM at 21,000:1 USD, but balances are stored/earned in XP atomic units.

**USD is display-only.**

**Implementation Rules:**
- Store XP as integer (bigint / string-bigint). No floats.
- Maintain peg as rational constants:
  - XP_PER_USD = 10^23
  - YAM_PER_USD = 21000
  - XP_PER_YAM = XP_PER_USD / YAM_PER_USD (store as fraction, not float)

**UI Display:**
- XP (primary)
- YAM-equivalent (derived)
- USD reference (derived)

**Display Suggestion:**
```
XP: 2.10e+05 XP
USD reference: 2.10e-18 USD
YAM-equivalent: computed from peg
```

**Anti-Confusion Rule:**
- 🚫 Do NOT code XP as "USD points."
- ✅ XP is YAM-denominated value.

**Locked Statement:**
> "XP is denominated in YAM units (peg 21,000:1). USD is reference only."

---

---

## 📝 Summary: Complete System Flow

### The Pocketbook Change Engine

**Consumer Spending Capture:**
Consumer spending is captured by device-driven 2-scan proof of delivery system that rewards those who participate in the delivery.

**Seller-Issued NWP (New World Penny):**
- Sponsored by seller
- Considered engagement resource of human blockchain value
- More deliveries = more pennies recognizing service
- Issued by seller pledge

**Pledge Maturity:**
- Pledges carry minimum 8–12 week maturity before redemption possible
- Outside annual reconciliation of receipts and obligations on August 31st

**Capturing Consumer = Pocketbook Change:**
- Engagement rewards in sextillionth of a penny denomination
- Value is earned by participation, not hoarding

### The Complete Cycle

```
1. Device Registration + Discord Acceptance
    ↓
2. Universal QR Scan (creates economic identity)
    ↓
3. Pre-order/Backorder Pledge (creates obligation)
    ↓
4. Seller Scan #1 (creates TXID, initiates delivery)
    ↓
5. Buyer Scan #2 (confirms receipt, activates XP)
    ↓
6. XP Granted (Pending → Active → Settled)
    ↓
7. NWP Issued (seller recognition)
    ↓
8. Maturity (8-12 weeks)
    ↓
9. Extinguishment (96.5% monthly or 100% Sept 1)
```

---

---

## 🎯 The "Realistic" Management Rule

**Critical Success Factor:**

If the development team can't produce Milestones A–C cleanly (cart + open YAM JAM registration + VFN upgrade separation), everything else will drag—because the permission model will keep changing.

**So:**
1. Lock identity + roles early
2. Then build PoD + ledger
3. Then tranches
4. Then annual close

**The One-Sentence Explanation for Developers:**

> The Pocketbook Brigade cart creates pledge obligations. Those obligations become real trade value only after 2-scan Proof of Delivery on registered devices. Device identity is the backbone of the human blockchain because the only meaningful proof is whether the device has scanned the YAM-is-On universal QR code and later participated in PoD events. Receipts and obligations post monthly in tranches. The inaugural reconciliation event is Aug 31, 2026, and every Aug 31 thereafter is annual close. Sept 1 is Redemption Day.

---

**End of Part 1: Core Architecture & User Flow**

*See Part 2 for Implementation Details, Roadmap, and Technical Specifications*
