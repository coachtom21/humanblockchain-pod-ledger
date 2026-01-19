# MEGAvoter.com Licensing Protocol - Implementation Documentation

## Overview
This document outlines all changes and additions made to implement the MEGAvoter.com Brand Licensing Protocol within the HumanBlockchain PoD Ledger plugin.

## Version
- **Protocol Version:** 1.0
- **Implementation Date:** 2024
- **Plugin Version:** 1.0.0

---

## 1. New Constants Added

### File: `humanblockchain-pod-ledger.php`

Added three new constants for the licensing protocol:

- **HBC_LICENSE_TITLE**: "MEGAvoter Brand Licensing Protocol"
- **HBC_LICENSE_VERSION**: "1.0"
- **HBC_LICENSE_CORE**: Full license acceptance text used in UI and acceptance records

**Purpose:** Standardized licensing text used throughout the plugin for consistency.

---

## 2. New Database Table

### File: `includes/Db.php`

**Table: `wp_hbc_licensing`**

Append-only table for storing licensing acceptance records.

**Schema:**
- `id` (bigint, PK, auto-increment)
- `device_hash` (char(64), indexed) - Hashed device identifier
- `license_version` (varchar(20)) - Protocol version accepted
- `accepted_at` (datetime) - UTC timestamp of acceptance
- `lat_round` (decimal(8,3)) - Rounded latitude (3 decimals)
- `lng_round` (decimal(9,3)) - Rounded longitude (3 decimals)
- `branch` (varchar(50)) - Peace Pentagon branch assignment
- `buyer_poc_id` (varchar(100)) - Buyer POC cluster ID
- `seller_poc_id` (varchar(100)) - Seller POC cluster ID
- `acceptance_hash` (char(64), unique) - SHA-256 hash of canonical payload
- `payload_json` (longtext) - Complete canonical JSON payload

**Indexes:**
- Primary key on `id`
- Index on `device_hash`
- Unique index on `acceptance_hash`
- Index on `accepted_at`

**Design Principle:** Append-only - never update or delete records. Corrections create new entries.

---

## 3. New Class: Licensing.php

### File: `includes/Licensing.php`

Core class handling all licensing protocol operations.

### Methods:

#### `createAcceptance($device_hash, $device_data)`
- Main entry point for creating licensing acceptance
- Rounds geo coordinates to 3 decimals
- Builds canonical payload
- Generates acceptance hash (SHA-256)
- Writes to local database
- Creates ledger entry with status "LICENSED"
- Attempts GitHub append (non-blocking)
- Returns acceptance hash and status

#### `writeLocal($device_hash, $payload, $device_data)`
- Writes acceptance record to `wp_hbc_licensing` table
- Append-only operation
- Stores complete canonical payload as JSON

#### `writeLedgerLicensedEntry($device_hash, $acceptance_hash, $payload)`
- Creates entry in `wp_hbc_ledger` with status "LICENSED"
- Voucher ID format: "LICENSE-" + first 16 chars of acceptance_hash
- Audit trail includes LICENSE_ACCEPTED event
- Separate from PoD INITIATED/CONFIRMED entries

#### `buildCanonicalPayload($data)`
- Builds JSON payload with ordered keys for consistent hashing
- Structure:
  - type: "LICENSE_ACCEPTED"
  - license_version
  - accepted_at_utc (ISO8601)
  - device_hash
  - geo_round: {lat, lng}
  - branch
  - buyer_poc_id
  - seller_poc_id
  - acceptance_hash (added after initial hash generation)

#### `appendToGitHub($payload)`
- Calls GitHubService to append to remote log
- Non-blocking - does not fail registration if GitHub fails
- Writes reconciliation record on failure

#### `writeGitHubFailureReconciliation($acceptance_hash, $error)`
- Creates reconciliation entry with type "GITHUB_APPEND_FAIL"
- Stores acceptance_hash and error summary
- Allows tracking of GitHub sync issues

---

## 4. Updated REST API Endpoint

### File: `includes/Rest.php`

#### `register_device()` - Updated

**New Requirements:**
- Now requires `accept_license: true` in request body
- Returns 400 error if `accept_license` is missing or false
- Error message: "License acceptance required to register this device."

**New Flow:**
1. Validates `accept_license` flag
2. Registers device (existing logic)
3. Calls `Licensing::createAcceptance()` after successful registration
4. Returns response with:
   - Standard device info
   - `acceptance_hash`
   - `github_append` status ("success" or "failed")
   - Warning message if GitHub append failed

**Authentication:**
- Updated `check_api_auth()` to accept both `X-API-Key` and `X-HBC-KEY` headers
- Maintains backward compatibility with existing nonce-based auth

---

## 5. GitHub Service Implementation

### File: `includes/Services/GitHubService.php`

**Complete rewrite** - No longer a stub. Full GitHub Contents API implementation.

### Methods:

#### `appendLedgerLine($dateYmd, $line)`
- Main method for appending NDJSON lines to GitHub
- Determines file path: `append-only-ledger/licensing/YYYY/MM/YYYY-MM-DD.log`
- Gets existing file content (if exists) to obtain SHA
- Appends new line with newline separator
- Creates file if it doesn't exist
- Commits with message: "Append licensing entry {acceptance_hash}"

#### `getFileContent($file_path)`
- Fetches file from GitHub using Contents API
- Returns content (base64 decoded) and SHA
- Returns false if file doesn't exist (404)
- Handles API errors gracefully

#### `putFileContent($file_path, $content, $sha, $commit_message)`
- Creates or updates file in GitHub
- Uses PUT method to Contents API
- Includes SHA for updates (prevents conflicts)
- Base64 encodes content
- Returns success/error status

**Configuration:**
- Token: `hbc_github_token` (WP option)
- Owner: `hbc_github_owner` (WP option)
- Repo: `hbc_github_repo` (default: "SmallStreetApplied-Atlanta")
- Branch: `hbc_github_branch` (default: "main")
- Logs Path: `hbc_github_logs_path` (default: "append-only-ledger/")

**Security:**
- Token never returned in REST responses
- Stored in WordPress options (encrypted in transit)
- Masked display in admin UI

---

## 6. Admin Settings Updates

### File: `includes/Admin.php`

**New Settings Section:** "GitHub Integration (Licensing Protocol)"

**Settings Fields:**
- GitHub Token (Fine-grained) - Password field with masked display
- GitHub Owner/Org - Text input
- GitHub Repository - Text input (default: SmallStreetApplied-Atlanta)
- GitHub Branch - Text input (default: main)
- Logs Path Root - Text input (default: append-only-ledger/)

**Updated Method:**
- `render_settings_page()` now saves GitHub configuration options

### File: `templates/admin-settings.php`

**Changes:**
- Added GitHub Integration settings section
- Token field shows masked value (first 8 + last 4 chars)
- All GitHub fields grouped together
- Other integrations (Discord, QRTiger) remain as stubs

---

## 7. Frontend Template Updates

### File: `templates/shortcode-participate.php`

**Step 1 (Register Device) - Added:**
- License acceptance checkbox
- Required field validation
- Displays full license text (HBC_LICENSE_CORE constant)
- JavaScript validates checkbox before registration

**JavaScript Updates:**
- Checks `accept_license` checkbox before allowing registration
- Sends `accept_license: true` in registration API call
- Displays warning if GitHub append failed

### File: `templates/shortcode-enter.php`

**Added:**
- License acceptance checkbox in modal
- Required before clicking "YES" button
- Displays license title and core text

### File: `assets/public.js`

**Updated:**
- `hbc-pod-yes` click handler now checks license acceptance
- Shows alert if license not accepted
- Prevents proceeding without acceptance

---

## 8. Data Flow

### Device Registration with Licensing:

1. **User Action:**
   - User fills device registration form
   - Checks "I accept the MEGAvoter Brand Licensing Protocol" checkbox
   - Clicks "Register Device"

2. **Frontend:**
   - JavaScript validates license acceptance
   - Sends POST to `/wp-json/hbc/v1/register-device` with `accept_license: true`

3. **Backend Validation:**
   - REST endpoint checks `accept_license === true`
   - Returns 400 if missing or false

4. **Device Registration:**
   - Device registered in `wp_hbc_devices`
   - Branch and POC assignments made

5. **Licensing Acceptance:**
   - `Licensing::createAcceptance()` called
   - Geo rounded to 3 decimals
   - Canonical payload built
   - Acceptance hash generated (SHA-256)
   - Written to `wp_hbc_licensing` (local, append-only)
   - Entry created in `wp_hbc_ledger` with status "LICENSED"
   - GitHub append attempted (non-blocking)

6. **GitHub Append:**
   - File path determined: `append-only-ledger/licensing/YYYY/MM/YYYY-MM-DD.log`
   - Existing file fetched (if exists)
   - New NDJSON line appended
   - File committed to GitHub
   - On failure: reconciliation record written

7. **Response:**
   - Returns device info + acceptance_hash
   - Includes `github_append` status
   - Warning message if GitHub failed

---

## 9. NDJSON Format

### GitHub Log File Format:

Each line is a complete JSON object (NDJSON - Newline Delimited JSON):

```json
{"type":"LICENSE_ACCEPTED","license_version":"1.0","accepted_at_utc":"2024-01-15T10:30:00+00:00","device_hash":"abc123...","geo_round":{"lat":40.713,"lng":-74.006},"branch":"planning","buyer_poc_id":"buyer_40.7_-74.0_2024-01-15","seller_poc_id":"seller_pool_5_set_2","acceptance_hash":"def456..."}
```

**Key Order (Canonical):**
1. type
2. license_version
3. accepted_at_utc
4. device_hash
5. geo_round
6. branch
7. buyer_poc_id
8. seller_poc_id
9. acceptance_hash

**File Structure:**
- Path: `append-only-ledger/licensing/YYYY/MM/YYYY-MM-DD.log`
- One line per acceptance
- Append-only (never modify existing lines)
- UTF-8 encoding

---

## 10. Security Features

### Authentication:
- REST endpoints accept `X-API-Key` or `X-HBC-KEY` headers
- Nonce-based auth for logged-in users
- API key stored in WordPress options

### Data Protection:
- Device IDs hashed before storage (SHA-256 with WP salt)
- Minimal PII stored
- GitHub token never exposed in responses
- Token masked in admin UI

### Append-Only Enforcement:
- No UPDATE or DELETE operations on licensing table
- Corrections create new entries
- GitHub commits are append-only
- Reconciliation records track failures

---

## 11. Error Handling

### GitHub Append Failures:
- Registration succeeds even if GitHub fails
- Reconciliation record created with type "GITHUB_APPEND_FAIL"
- Error summary stored in `totals_json`
- Response includes warning flag
- No user-facing error (non-blocking)

### Validation Errors:
- Missing `accept_license`: 400 error with clear message
- Invalid device data: Standard validation errors
- Database errors: 500 error with generic message

---

## 12. Reconciliation Records

### New Reconciliation Type: `GITHUB_APPEND_FAIL`

**Stored in:** `wp_hbc_reconciliation` table

**Structure:**
- `recon_type`: "GITHUB_APPEND_FAIL"
- `period_start`: Timestamp of failure
- `period_end`: Same as period_start
- `totals_json`: Contains:
  - `acceptance_hash`: Hash of the acceptance that failed
  - `error_summary`: Error message from GitHub API
  - `timestamp`: UTC timestamp

**Purpose:** Audit trail for GitHub sync issues. Allows manual reconciliation later.

---

## 13. Ledger Entry Types

### New Status: `LICENSED`

**Created in:** `wp_hbc_ledger` table

**Characteristics:**
- `status`: "LICENSED"
- `voucher_id`: "LICENSE-{first_16_chars_of_acceptance_hash}"
- `seller_device_hash`: Device hash that accepted license
- `initiated_at`: UTC timestamp
- `lat_init` / `lng_init`: Rounded geo coordinates
- `branch`, `buyer_poc_id`, `seller_poc_id`: From device assignment
- `audit_json`: Contains LICENSE_ACCEPTED event

**Note:** This entry is separate from PoD flow entries (INITIATED/CONFIRMED). It represents license acceptance, not delivery confirmation.

---

## 14. Configuration Requirements

### Required Settings (Admin):
1. **GitHub Token:** Fine-grained personal access token with Contents API permissions
2. **GitHub Owner:** Organization or username (e.g., "SmallStreetApplied")
3. **GitHub Repository:** Repository name (default: "SmallStreetApplied-Atlanta")
4. **GitHub Branch:** Branch name (default: "main")
5. **Logs Path Root:** Base path for log files (default: "append-only-ledger/")

### Token Permissions Required:
- Contents: Read and Write
- Repository access: Specific repository or all repositories

---

## 15. File Structure Changes

### New Files:
- `includes/Licensing.php` - Core licensing protocol class

### Modified Files:
- `humanblockchain-pod-ledger.php` - Added constants
- `includes/Db.php` - Added licensing table
- `includes/Rest.php` - Updated register_device endpoint
- `includes/Services/GitHubService.php` - Full implementation
- `includes/Admin.php` - Added settings save logic
- `templates/admin-settings.php` - Added GitHub settings UI
- `templates/shortcode-participate.php` - Added license checkbox
- `templates/shortcode-enter.php` - Added license checkbox
- `assets/public.js` - Added license validation

---

## 16. Testing Checklist

### Device Registration:
- [ ] Registration fails without `accept_license: true`
- [ ] Registration succeeds with `accept_license: true`
- [ ] Licensing record created in `wp_hbc_licensing`
- [ ] Ledger entry created with status "LICENSED"
- [ ] Acceptance hash is unique and consistent

### GitHub Integration:
- [ ] GitHub append succeeds when configured
- [ ] File created at correct path if doesn't exist
- [ ] Line appended to existing file correctly
- [ ] Commit message includes acceptance hash
- [ ] Reconciliation record created on failure
- [ ] Registration succeeds even if GitHub fails

### UI/UX:
- [ ] License checkbox appears in participate flow
- [ ] License checkbox appears in enter modal
- [ ] Cannot proceed without accepting license
- [ ] License text displays correctly
- [ ] Warning shown if GitHub append failed

### Admin:
- [ ] GitHub settings save correctly
- [ ] Token masked in display
- [ ] All GitHub fields configurable
- [ ] Settings persist after save

---

## 17. Important Notes

### Terminology:
- **NEVER** use the word "stamp" anywhere
- Use: voucher, hang tag, proof of delivery label, delivery credential, PoD
- Licensing protocol is operational acceptance, not legal advice

### Append-Only Principle:
- All licensing records are append-only
- Never update or delete existing records
- Corrections create new entries referencing prior hash
- GitHub log files are append-only (never modify existing lines)

### Non-Blocking GitHub:
- GitHub append failures do not prevent device registration
- Local records are always created
- Reconciliation records track failures for manual resolution
- User experience is not impacted by GitHub issues

### Privacy:
- Minimal PII stored
- Device identifiers hashed
- Geo coordinates rounded to 3 decimals
- No raw email addresses stored

---

## 18. Future Enhancements (Not Implemented)

### Potential Additions:
- Admin UI to view licensing acceptances
- Bulk reconciliation tool for failed GitHub appends
- License version migration support
- Webhook notifications on GitHub commits
- License revocation mechanism (as new entry, not deletion)

---

## 19. API Examples

### Successful Device Registration:

**Request:**
```json
POST /wp-json/hbc/v1/register-device
Headers:
  X-WP-Nonce: {nonce}
  Content-Type: application/json

Body:
{
  "device_id": "device-123",
  "platform": "ios",
  "lat": 40.7128,
  "lng": -74.0060,
  "timestamp": "2024-01-15T10:30:00Z",
  "accept_license": true
}
```

**Response:**
```json
{
  "device_hash": "abc123...",
  "branch": "planning",
  "buyer_poc_id": "buyer_40.7_-74.0_2024-01-15",
  "seller_poc_id": "seller_pool_5_set_2",
  "welcome_copy": "...",
  "acceptance_hash": "def456...",
  "github_append": "success"
}
```

### Failed License Acceptance:

**Request:** (missing accept_license)
```json
{
  "device_id": "device-123",
  "platform": "ios",
  "lat": 40.7128,
  "lng": -74.0060,
  "timestamp": "2024-01-15T10:30:00Z"
}
```

**Response:**
```json
{
  "code": "license_required",
  "message": "License acceptance required to register this device.",
  "data": {
    "status": 400
  }
}
```

---

## 20. Database Migration

### On Plugin Activation:

The `wp_hbc_licensing` table is automatically created via `dbDelta()` when the plugin is activated.

**Migration Status:**
- Table created on first activation
- No data migration required (new feature)
- Existing devices are not retroactively licensed
- Only new registrations require license acceptance

---

## 21. Compliance & Audit

### Audit Trail:
- Every acceptance has unique `acceptance_hash`
- Canonical payload ensures consistent hashing
- Local database stores complete payload
- GitHub log provides remote append-only ledger
- Reconciliation records track sync failures

### Compliance Features:
- Append-only design prevents tampering
- Hash-based verification possible
- Dual storage (local + remote) for redundancy
- Timestamped entries (UTC)
- Geo coordinates rounded for privacy

---

## Summary

The MEGAvoter.com Licensing Protocol implementation adds a mandatory license acceptance step to device registration, creating append-only audit trails both locally and remotely (GitHub). The system is designed to be non-blocking, privacy-conscious, and fully auditable while maintaining the existing PoD ledger functionality unchanged.
