# HumanBlockchain PoD Ledger Plugin

A WordPress plugin implementing a 2-scan Proof-of-Delivery pledge ledger with 8-12 week maturity or reconciliation moments.

## Installation

1. Upload the `humanblockchain-pod-ledger` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create database tables on activation

## Features

- **Device Registration**: Register devices with geo-location and assign Peace Pentagon branches and POC clusters
- **2-Scan PoD Flow**: Seller initiation (Scan 1) and Buyer acceptance (Scan 2)
- **Append-Only Ledger**: Immutable ledger entries with audit trails
- **Pledge Accounting**: $10.30 default pledge with configurable allocations
- **Maturity Windows**: 8-12 week maturity periods with automatic tracking
- **Reconciliation**: Month-end, first-day, and year-end reconciliation jobs
- **Admin Interface**: Manage devices, ledger entries, and reconciliation records

## REST API Endpoints

All endpoints require authentication via:
- Logged-in users: `X-WP-Nonce` header with WordPress REST nonce
- Non-logged-in users: `X-API-Key` header with API key from Settings

### Register Device
```
POST /wp-json/hbc/v1/register-device
Content-Type: application/json

{
  "device_id": "unique-device-id",
  "platform": "ios|android|web",
  "lat": 40.7128,
  "lng": -74.0060,
  "timestamp": "2024-01-15T10:00:00Z",
  "timezone": "America/New_York",
  "push_token": "optional-push-token"
}
```

### Initiate PoD (Scan 1)
```
POST /wp-json/hbc/v1/pod/initiate
Content-Type: application/json

{
  "seller_device_id": "seller-device-id",
  "buyer_identifier": "buyer@example.com",
  "voucher_id": "voucher-123",
  "lat": 40.7128,
  "lng": -74.0060,
  "timestamp": "2024-01-15T10:00:00Z",
  "order_ref": "optional-order-ref"
}
```

### Confirm PoD (Scan 2)
```
POST /wp-json/hbc/v1/pod/confirm
Content-Type: application/json

{
  "buyer_device_id": "buyer-device-id",
  "voucher_id": "voucher-123",
  "confirm_delivery": true,
  "lat": 40.7128,
  "lng": -74.0060,
  "timestamp": "2024-01-15T10:05:00Z"
}
```

### Get Ledger Entries
```
GET /wp-json/hbc/v1/ledger?status=CONFIRMED&branch=planning&per_page=50&page=1
```

### Get Device
```
GET /wp-json/hbc/v1/device/{device_hash}
```

## Shortcodes

### [hbc_enter]
Displays the "Is This Proof of Delivery?" modal gate.

### [hbc_pod_flow]
Displays the 2-scan PoD flow forms.

## Example cURL Requests

### Register Device
```bash
curl -X POST https://yoursite.com/wp-json/hbc/v1/register-device \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "device_id": "device-123",
    "platform": "ios",
    "lat": 40.7128,
    "lng": -74.0060,
    "timestamp": "2024-01-15T10:00:00Z"
  }'
```

### Initiate PoD
```bash
curl -X POST https://yoursite.com/wp-json/hbc/v1/pod/initiate \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "seller_device_id": "seller-device-123",
    "buyer_identifier": "buyer@example.com",
    "voucher_id": "voucher-abc",
    "lat": 40.7128,
    "lng": -74.0060,
    "timestamp": "2024-01-15T10:00:00Z"
  }'
```

## Database Tables

- `wp_hbc_devices`: Registered devices
- `wp_hbc_ledger`: PoD ledger entries (append-only)
- `wp_hbc_reconciliation`: Reconciliation journal entries

## Cron Jobs

- **Daily Maturity Scanner**: Runs at 2 AM daily
- **Month-End Close**: Runs on last day of month at 11:59 PM
- **First-Day Distribution**: Runs on first day of month at 12:00 AM
- **Year-End Close**: Runs on September 1 at 12:00 AM

## Important Notes

- Pledges are **NOT payments**; they are obligations that mature in 8-12 weeks
- The ledger is **append-only**; corrections create new entries referencing parent entries
- Device IDs and buyer identifiers are **hashed** before storage (SHA-256 with WP salt)
- The "Detente 2030" settlement moment is where pledges mature or reconcile into final truth

## Disclaimer

Pledges are obligations that mature in 8-12 weeks or are settled via reconciliation moments; they are not payments.
