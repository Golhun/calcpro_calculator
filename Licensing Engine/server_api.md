Licensing Engine Server API Specification (v1)
Purpose

This document defines the frozen API contract for the Licensing Engine server. It is designed for:

Local apps that validate licenses periodically online

Offline-first behavior with defined grace windows

Machine-bound licensing with controlled transfers

Update entitlement and latest-version discovery

All endpoints are implemented through a single root-level script using an action parameter for simplicity.

1. Base URL and Routing
   Base endpoint

POST /license_api.php?action=<action>

Supported actions (v1)

activate

validate

updates

transfer_request

2. Transport and Security Requirements
   Transport

Production must use HTTPS.

Requests and responses are JSON.

Common headers

Content-Type: application/json

X-Product-Id: <product_id> (example: calcpro)

X-Client-Version: <sdk_version> (example: 1.0.0)

X-App-Version: <app_version> (example: 1.2.0) optional but recommended

Authentication (v1)

v1 can start with a shared api_key in .env for admin or internal use.

Client endpoints should not expose admin operations.

Stronger request signing can be added later without breaking v1.

3. Response Envelope (Standard)

All responses must follow this envelope:

Success
{
"ok": true,
"data": { }
}

Error
{
"ok": false,
"error": "Human-readable error message",
"code": "OPTIONAL_ERROR_CODE"
}

4. Error Codes (Recommended)

Use consistent codes to simplify client UX:

INVALID_REQUEST

LICENSE_NOT_FOUND

LICENSE_REVOKED

LICENSE_SUSPENDED

LICENSE_EXPIRED

FINGERPRINT_MISMATCH

TRANSFER_LIMIT_REACHED

ACTIVATION_NOT_ALLOWED

INTERNAL_ERROR

5. Endpoint: Activate
   Purpose

Bind a license to a machine fingerprint for first-time activation or approved transfer.

Route

POST /license_api.php?action=activate

Request Body
{
"license_id": "LIC-9F3B2C8A",
"product_id": "calcpro",
"fingerprint_hash": "sha256:ABC123...",

"machine": {
"hostname": "DM-SPHERE-PC",
"os": "Windows 10",
"php": "8.2.12"
}
}

Server Behavior

Validate license exists and matches product_id

If license status blocks running, return the relevant error

If license is already bound to a different fingerprint:

reject unless a transfer is approved or policy allows rebind

Persist machine binding and activation event

Return a signed license payload

Response (Success)
{
"ok": true,
"data": {
"server_time": "2025-12-23T10:12:00Z",
"status": "ACTIVE",
"message": "Activated",
"license_payload": {
"schema_version": 1,
"license_id": "LIC-9F3B2C8A",
"product_id": "calcpro",
"customer": {
"customer_id": "CUST-00192",
"name": "DM Sphere Pharmacy Limited"
},
"plan": "perpetual",
"status": "ACTIVE",
"issued_at": "2025-12-23T00:00:00Z",
"expires_at": "2124-12-23T00:00:00Z",
"updates_until": "2031-12-23T00:00:00Z",
"trial": { "trial_days": null },
"fingerprint": {
"mode": "machine",
"bound": true,
"fingerprint_hash": "sha256:ABC123..."
},
"policy": {
"check_interval_days": 30,
"warn_after_days": 180,
"max_offline_days": 365,
"max_transfers": 2
},
"meta": { "notes": null },
"signature_alg": "ed25519",
"signature": "BASE64_SIGNATURE"
}
}
}

Response (Fingerprint mismatch)
{
"ok": false,
"error": "License is bound to a different machine.",
"code": "FINGERPRINT_MISMATCH"
}

6. Endpoint: Validate
   Purpose

Periodic server check-in to confirm status, refresh policy, and get update metadata.

Route

POST /license_api.php?action=validate

Request Body
{
"license_id": "LIC-9F3B2C8A",
"product_id": "calcpro",
"fingerprint_hash": "sha256:ABC123...",

"app": {
"version": "1.2.0",
"release_date": "2026-01-10T00:00:00Z"
},

"client_state": {
"last_success_check_at": "2026-01-01T00:00:00Z"
}
}

Server Behavior

Confirm license exists and matches product

Confirm fingerprint match for bound licenses

Return current license status and policy

Return latest version metadata (optional but recommended)

Persist a check-in record

Response (Success)
{
"ok": true,
"data": {
"server_time": "2026-01-23T08:00:00Z",
"status": "ACTIVE",
"message": "OK",

    "policy": {
      "check_interval_days": 30,
      "warn_after_days": 180,
      "max_offline_days": 365
    },

    "updates_until": "2031-12-23T00:00:00Z",

    "latest_version": {
      "version": "1.5.0",
      "release_date": "2026-02-01T00:00:00Z",
      "download_url": "https://example.com/downloads/calcpro_1.5.0.zip"
    }

}
}

Response (Suspended)
{
"ok": true,
"data": {
"server_time": "2026-01-23T08:00:00Z",
"status": "SUSPENDED",
"message": "License suspended. Contact support.",
"policy": {
"check_interval_days": 30,
"warn_after_days": 180,
"max_offline_days": 365
},
"updates_until": "2031-12-23T00:00:00Z",
"latest_version": null
}
}

Notes:

v1 returns ok: true even if status blocks usage, because the request itself succeeded.

The client will enforce the status.

7. Endpoint: Updates
   Purpose

Lightweight update check. Useful when you do not want full validation every time.

Route

POST /license_api.php?action=updates

Request Body
{
"license_id": "LIC-9F3B2C8A",
"product_id": "calcpro",
"fingerprint_hash": "sha256:ABC123...",
"current_version": "1.2.0"
}

Server Behavior

Confirm license exists and fingerprint match

Return latest version metadata

Return eligible based on updates_until and the latest release date

Client must still enforce locally

Response (Success)
{
"ok": true,
"data": {
"updates_until": "2031-12-23T00:00:00Z",
"eligible": true,
"latest_version": {
"version": "1.5.0",
"release_date": "2026-02-01T00:00:00Z",
"download_url": "https://example.com/downloads/calcpro_1.5.0.zip"
}
}
}

Response (Not eligible)
{
"ok": true,
"data": {
"updates_until": "2031-12-23T00:00:00Z",
"eligible": false,
"latest_version": {
"version": "1.5.0",
"release_date": "2026-02-01T00:00:00Z",
"download_url": "https://example.com/downloads/calcpro_1.5.0.zip"
}
}
}

8. Endpoint: Transfer Request
   Purpose

Create a transfer request record that the vendor can approve. This is the customer-facing workflow.

Route

POST /license_api.php?action=transfer_request

Request Body
{
"license_id": "LIC-9F3B2C8A",
"product_id": "calcpro",

"from_fingerprint_hash": "sha256:OLD...",
"to_fingerprint_hash": "sha256:NEW...",

"reason": "Old PC crashed",
"contact": {
"name": "John Doe",
"email": "client@example.com",
"phone": "+233..."
}
}

Server Behavior

Ensure license exists

Create a transfer request record with status OPEN

Do not immediately rebind license

Return a request ID

Response (Success)
{
"ok": true,
"data": {
"request_id": "TR-000104",
"status": "OPEN",
"message": "Request received. Support will respond."
}
}

Response (Transfer limit reached)
{
"ok": false,
"error": "Transfer limit reached for this license.",
"code": "TRANSFER_LIMIT_REACHED"
}

9. Server Data Requirements

The server must store:

License record

Machine binding record

Check-in history

Transfer requests

Product latest version info

See schema.md and the database schema that will be created during implementation.

10. Client Enforcement Expectations (v1)

The client must:

Enforce status at startup

Enforce fingerprint match

Enforce offline policy windows using cached timestamps

Enforce update entitlement using updates_until and APP_RELEASE_DATE

The server provides truth, the client enforces.

11. Versioning & Compatibility

v1 clients must ignore unknown fields in responses

server can add new fields without breaking v1

breaking changes require schema_version increment and v2 docs

12. Implementation Notes (v1)

All endpoints can be implemented in one script (license_api.php) using an action switch.

Always return RFC3339 UTC timestamps.

Always log check-ins and activation attempts for auditing.

Keep error messages human-readable.
