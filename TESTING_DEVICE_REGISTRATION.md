# Testing Device Registration with Discord Requirement

## Prerequisites

1. **Plugin Activated**: Ensure the `humanblockchain-pod-ledger` plugin is activated
2. **Database Migrated**: Tables should be created automatically on activation
3. **API Key Set**: Set an API key in WordPress admin (Settings → HBC PoD Ledger → API Key)
4. **Discord Credentials**: You'll need Discord user ID and username for testing

---

## Method 1: Manual API Testing (cURL)

### Step 1: Get Your API Key (What You Just Did!)

**What you need to do:**
1. ✅ You already did this! You went to WordPress Admin
2. ✅ You clicked on "HBC PoD Ledger" in the left menu
3. ✅ You clicked on "Settings" 
4. ✅ You can see your API key displayed on the page: `pdbbVcRz0sBXo9cuiyEPxy8M3htf90uy`

**That's it!** You have everything you need from Step 1.

**What is this API key for?**
- It's like a password that allows your test requests to talk to the plugin
- You'll use it in the `X-HBC-KEY` header when testing

**Your Site URL:**
- Your site URL is: `http://humanblockchain.local` (or whatever your local site URL is)

### Step 2: Test Without Discord (Should Fail)

```bash
curl -X POST http://humanblockchain.local/wp-json/hbc/v1/register-device \
  -H "Content-Type: application/json" \
  -H "X-HBC-KEY: YOUR_API_KEY_HERE" \
  -d '{
    "device_id": "test-device-123",
    "platform": "web",
    "lat": 40.7128,
    "lng": -74.0060,
    "timestamp": "2024-01-15T10:30:00Z",
    "accept_license": true
  }'
```

**Expected Response (Error):**
```json
{
  "code": "discord_required",
  "message": "Discord connection is required to register this device. Please connect your Discord account first.",
  "data": {
    "status": 400
  }
}
```

### Step 3: Test With Discord (Should Succeed)

```bash
curl -X POST http://humanblockchain.local/wp-json/hbc/v1/register-device \
  -H "Content-Type: application/json" \
  -H "X-HBC-KEY: YOUR_API_KEY_HERE" \
  -d '{
    "device_id": "test-device-123",
    "platform": "web",
    "lat": 40.7128,
    "lng": -74.0060,
    "timestamp": "2024-01-15T10:30:00Z",
    "accept_license": true,
    "discord_user_id": "123456789012345678",
    "discord_username": "testuser#1234"
  }'
```

**Expected Response (Success):**
```json
{
  "device_hash": "abc123...",
  "branch": "planning",
  "buyer_poc_id": "buyer_40.7_-74.0_2024-01-15",
  "seller_poc_id": "seller_pool_5_set_2",
  "welcome_copy": "Welcome to the HumanBlockchain network...",
  "acceptance_hash": "def456...",
  "github_append": "success"
}
```

### Step 4: Test With Discord Invite Referral

```bash
curl -X POST http://humanblockchain.local/wp-json/hbc/v1/register-device \
  -H "Content-Type: application/json" \
  -H "X-HBC-KEY: YOUR_API_KEY_HERE" \
  -d '{
    "device_id": "test-device-456",
    "platform": "web",
    "lat": 40.7128,
    "lng": -74.0060,
    "timestamp": "2024-01-15T10:30:00Z",
    "accept_license": true,
    "discord_user_id": "987654321098765432",
    "discord_username": "newuser#5678",
    "discord_invite_code": "INVITE_CODE_FROM_REFERRER"
  }'
```

**Expected Response (With Referral):**
```json
{
  "device_hash": "xyz789...",
  "branch": "budget",
  "buyer_poc_id": "buyer_40.7_-74.0_2024-01-15",
  "seller_poc_id": "seller_pool_10_set_3",
  "welcome_copy": "Welcome to the HumanBlockchain network...",
  "acceptance_hash": "ghi789...",
  "github_append": "success",
  "referral_award": {
    "yam_amount": 21000,
    "usd_equivalent": 1.00
  }
}
```

---

## Method 2: Using Postman

### Setup

1. **Create New Request**
   - Method: `POST`
   - URL: `http://humanblockchain.local/wp-json/hbc/v1/register-device`

2. **Headers**
   - `Content-Type`: `application/json`
   - `X-HBC-KEY`: `YOUR_API_KEY_HERE`

3. **Body (raw JSON)**

**Test 1: Missing Discord (Should Fail)**
```json
{
  "device_id": "test-device-123",
  "platform": "web",
  "lat": 40.7128,
  "lng": -74.0060,
  "timestamp": "2024-01-15T10:30:00Z",
  "accept_license": true
}
```

**Test 2: With Discord (Should Succeed)**
```json
{
  "device_id": "test-device-123",
  "platform": "web",
  "lat": 40.7128,
  "lng": -74.0060,
  "timestamp": "2024-01-15T10:30:00Z",
  "accept_license": true,
  "discord_user_id": "123456789012345678",
  "discord_username": "testuser#1234"
}
```

---

## Method 3: Frontend Testing (Browser Console)

### Step 1: Get WordPress Nonce

1. Log in to WordPress admin
2. Open browser console on any WordPress page
3. Run this to get the nonce:

```javascript
// Get nonce from WordPress
const nonce = wpApiSettings.nonce || '';
console.log('Nonce:', nonce);
```

### Step 2: Test Registration in Console

```javascript
fetch('/wp-json/hbc/v1/register-device', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify({
    device_id: 'test-device-' + Date.now(),
    platform: 'web',
    lat: 40.7128,
    lng: -74.0060,
    timestamp: new Date().toISOString(),
    accept_license: true,
    discord_user_id: '123456789012345678',
    discord_username: 'testuser#1234'
  })
})
.then(response => response.json())
.then(data => {
  console.log('Success:', data);
})
.catch(error => {
  console.error('Error:', error);
});
```

---

## Method 4: Using the Participation Shortcode

### Step 1: Update Frontend Template

The `shortcode-participate.php` template needs to be updated to include Discord fields. Here's what needs to be added:

**Add Discord Input Fields to Step 1:**

```html
<p>
    <label>Discord User ID (Required):</label><br>
    <input type="text" id="discord-user-id" required style="width: 100%; padding: 8px;" placeholder="123456789012345678">
</p>
<p>
    <label>Discord Username (Required):</label><br>
    <input type="text" id="discord-username" required style="width: 100%; padding: 8px;" placeholder="username#1234">
</p>
<p>
    <label>Discord Invite Code (Optional - for referrals):</label><br>
    <input type="text" id="discord-invite-code" style="width: 100%; padding: 8px;" placeholder="Leave blank if not referred">
</p>
```

**Update JavaScript to Include Discord Fields:**

```javascript
data: JSON.stringify({
    device_id: deviceId,
    platform: platform,
    lat: geoData.lat,
    lng: geoData.lng,
    timestamp: new Date().toISOString(),
    timezone: timezone || null,
    accept_license: true,
    discord_user_id: $('#discord-user-id').val(),
    discord_username: $('#discord-username').val(),
    discord_invite_code: $('#discord-invite-code').val() || null
}),
```

### Step 2: Test on Frontend

1. Create a page with `[hbc_participate]` shortcode
2. Visit the page
3. Fill in all fields including Discord credentials
4. Click "Register Device"
5. Check for success/error messages

---

## How to Get Discord Credentials for Testing

### Option 1: Use Discord Developer Mode

1. Open Discord
2. Go to Settings → Advanced → Enable Developer Mode
3. Right-click on your profile → Copy User ID
4. Your username is visible in your profile (e.g., `username#1234`)

### Option 2: Use Test/Dummy Values

For testing purposes, you can use:
- `discord_user_id`: `123456789012345678` (18-digit number)
- `discord_username`: `testuser#1234` (any format)

**Note:** These won't work for actual Discord integration, but will pass validation for testing the registration flow.

### Option 3: Create a Test Discord Account

1. Create a new Discord account
2. Get the User ID and username
3. Use these for testing

---

## Verification Steps

### 1. Check Database

After successful registration, verify in database:

```sql
SELECT * FROM wp_hbc_devices 
WHERE discord_user_id = '123456789012345678' 
ORDER BY registered_at DESC LIMIT 1;
```

**Expected Fields:**
- `discord_user_id`: Should match your input
- `discord_username`: Should match your input
- `discord_connected_at`: Should be current timestamp
- `device_hash`: Should be populated
- `branch`: Should be assigned
- `buyer_poc_id`: Should be assigned
- `seller_poc_id`: Should be assigned

### 2. Check Licensing Table

```sql
SELECT * FROM wp_hbc_licensing 
WHERE device_hash = 'YOUR_DEVICE_HASH' 
ORDER BY accepted_at DESC LIMIT 1;
```

**Expected:**
- `device_hash`: Should match device
- `license_version`: Should be "1.0"
- `acceptance_hash`: Should be populated

### 3. Check Referral Table (If Invite Code Used)

```sql
SELECT * FROM wp_hbc_referrals 
WHERE referred_device_hash = 'YOUR_DEVICE_HASH';
```

**Expected:**
- `referral_source`: Should be "discord_invite"
- `referral_award_yam_amount`: Should be 21000, 105000, or 525000
- `referral_award_usd_equivalent`: Should be calculated correctly

---

## Common Issues & Solutions

### Issue 1: "Invalid API key" Error

**Solution:**
- Check that API key is set in WordPress admin
- Ensure header name is `X-HBC-KEY` (not `X-API-Key`)
- For logged-in users, use `X-WP-Nonce` instead

### Issue 2: "Discord required" Error Even With Discord

**Solution:**
- Check that both `discord_user_id` AND `discord_username` are provided
- Ensure they are not empty strings
- Check JSON formatting

### Issue 3: "License acceptance required" Error

**Solution:**
- Ensure `accept_license: true` (boolean, not string)
- Check JSON is valid

### Issue 4: Database Errors

**Solution:**
- Ensure plugin is activated
- Check that database tables were created
- Run `wp db query "SHOW TABLES LIKE 'wp_hbc_%';"` to verify tables exist

---

## Testing Checklist

- [ ] Test registration without Discord → Should fail with clear error
- [ ] Test registration with Discord → Should succeed
- [ ] Test registration with Discord invite code → Should create referral record
- [ ] Verify device record in database
- [ ] Verify licensing acceptance record
- [ ] Verify referral record (if invite code used)
- [ ] Test duplicate device registration → Should return existing device
- [ ] Test with invalid API key → Should fail with 401
- [ ] Test with missing required fields → Should fail with specific field error

---

## Next Steps After Testing

1. **Test POC Binding**: Register 25 devices to same Buyer POC to test binding
2. **Test Referral Awards**: Test with different membership tiers
3. **Test NWP Issuance**: Complete a full PoD flow to test NWP licensing
4. **Test Tranche Close**: Run month-end tranche close manually

---

## Quick Test Script

Save this as `test-device-registration.sh`:

```bash
#!/bin/bash

API_KEY="YOUR_API_KEY_HERE"
SITE_URL="http://humanblockchain.local"

echo "Testing device registration without Discord (should fail)..."
curl -X POST "$SITE_URL/wp-json/hbc/v1/register-device" \
  -H "Content-Type: application/json" \
  -H "X-HBC-KEY: $API_KEY" \
  -d '{
    "device_id": "test-device-'$(date +%s)'",
    "platform": "web",
    "lat": 40.7128,
    "lng": -74.0060,
    "timestamp": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'",
    "accept_license": true
  }'

echo -e "\n\nTesting device registration with Discord (should succeed)..."
curl -X POST "$SITE_URL/wp-json/hbc/v1/register-device" \
  -H "Content-Type: application/json" \
  -H "X-HBC-KEY: $API_KEY" \
  -d '{
    "device_id": "test-device-'$(date +%s)'",
    "platform": "web",
    "lat": 40.7128,
    "lng": -74.0060,
    "timestamp": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'",
    "accept_license": true,
    "discord_user_id": "123456789012345678",
    "discord_username": "testuser#1234"
  }'
```

Make it executable and run:
```bash
chmod +x test-device-registration.sh
./test-device-registration.sh
```
