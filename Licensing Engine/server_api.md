# Licensing Engine — Server API Specification (v1)

## Purpose

A concise, frozen contract for the Licensing Engine server. This document describes the v1 HTTP API used by client SDKs and apps to: activate licenses, validate status, check for updates, and request transfers. The design emphasizes an offline-friendly client model, machine-bound licensing, and a simple, extensible server interface.

---

## Table of contents

- [Base URL & Routing](#base-url--routing)
- [Transport & Security](#transport--security)
- [Response Envelope](#response-envelope)
- [Error Codes](#error-codes)
- [Endpoints](#endpoints)
  - [Activate](#activate)
  - [Validate](#validate)
  - [Updates](#updates)
  - [Transfer Request](#transfer-request)
- [Server Data Requirements](#server-data-requirements)
- [Client Enforcement Expectations](#client-enforcement-expectations)
- [Versioning & Compatibility](#versioning--compatibility)
- [References](#references)

---

## Base URL & Routing

Base endpoint (single script):

```
POST /license_api.php?action=<action>
```

Supported actions (v1): `activate`, `validate`, `updates`, `transfer_request`.

Notes:
- The action-driven single-script approach keeps the surface area small for early integration.
- Future versions may add route-based endpoints.

---

## Transport & Security

- Transport: **HTTPS required** in production.
- Payloads: requests and responses should be JSON (Content-Type: application/json).
- Common headers (recommended):
  - `X-Product-Id: <product_id>` (e.g. `calcpro`)
  - `X-Client-Version: <sdk_version>` (e.g. `1.0.0`)
  - `X-App-Version: <app_version>` (optional, recommended)

Authentication (v1):
- v1 may use a shared `api_key` (from `.env`) for internal/admin use. Client-facing endpoints should not expose admin operations.
- Future: request signing / HMAC can be added in later API versions without breaking v1.

---

## Response Envelope

All responses must follow this envelope for consistency:

Success example:
```json
{
  "ok": true,
  "data": { }
}
```

Error example:
```json
{
  "ok": false,
  "error": "Human-readable error message",
  "code": "OPTIONAL_ERROR_CODE"
}
```

---

## Error Codes (recommended)

Use stable codes so clients can react programmatically:
- `INVALID_REQUEST`
- `LICENSE_NOT_FOUND`
- `LICENSE_REVOKED`
- `LICENSE_SUSPENDED`
- `LICENSE_EXPIRED`
- `FINGERPRINT_MISMATCH`
- `TRANSFER_LIMIT_REACHED`
- `ACTIVATION_NOT_ALLOWED`
- `INTERNAL_ERROR`

---

## Endpoints

Each endpoint follows this consistent structure: Purpose → Route → Request → Server Behavior → Success / Error Responses.

### Activate

**Purpose:** Bind a license to a machine fingerprint for first-time activation or an approved transfer.

**Route:**
```
POST /license_api.php?action=activate
```

**Request body:**
```json
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
```

**Server behavior:**
- Validate license exists and product_id matches.
- If license status blocks usage, return the relevant status (client will enforce behavior).
- If license is bound to another fingerprint, reject unless a transfer has been approved or policy allows rebind.
- Persist machine binding and activation event.
- Return a signed license payload.

**Success response:**
```json
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
      "customer": { "customer_id": "CUST-00192", "name": "DM Sphere Pharmacy Limited" },
      "plan": "perpetual",
      "status": "ACTIVE",
      "issued_at": "2025-12-23T00:00:00Z",
      "expires_at": "2124-12-23T00:00:00Z",
      "updates_until": "2031-12-23T00:00:00Z",
      "trial": { "trial_days": null },
      "fingerprint": { "mode": "machine", "bound": true, "fingerprint_hash": "sha256:ABC123..." },
      "policy": { "check_interval_days": 30, "warn_after_days": 180, "max_offline_days": 365, "max_transfers": 2 },
      "meta": { "notes": null },
      "signature_alg": "ed25519",
      "signature": "BASE64_SIGNATURE"
    }
  }
}
```

**Error (fingerprint mismatch) example:**
```json
{
  "ok": false,
  "error": "License is bound to a different machine.",
  "code": "FINGERPRINT_MISMATCH"
}
```

---

### Validate

**Purpose:** Periodic check-in to confirm status, refresh policy, and return update metadata.

**Route:**
```
POST /license_api.php?action=validate
```

**Request body:**
```json
{
  "license_id": "LIC-9F3B2C8A",
  "product_id": "calcpro",
  "fingerprint_hash": "sha256:ABC123...",
  "app": { "version": "1.2.0", "release_date": "2026-01-10T00:00:00Z" },
  "client_state": { "last_success_check_at": "2026-01-01T00:00:00Z" }
}
```

**Server behavior:**
- Confirm license exists and matches product.
- Confirm fingerprint match for bound licenses.
- Return current license status and policy.
- Return latest version metadata when available.
- Persist a check-in record for audit/monitoring.

**Success response example:**
```json
{
  "ok": true,
  "data": {
    "server_time": "2026-01-23T08:00:00Z",
    "status": "ACTIVE",
    "message": "OK",
    "policy": { "check_interval_days": 30, "warn_after_days": 180, "max_offline_days": 365 },
    "updates_until": "2031-12-23T00:00:00Z",
    "latest_version": { "version": "1.5.0", "release_date": "2026-02-01T00:00:00Z", "download_url": "https://example.com/downloads/calcpro_1.5.0.zip" }
  }
}
```

**Note:** v1 intentionally returns `ok: true` even when `status` implies blocked usage — the client enforces the status.

---

### Updates

**Purpose:** Lightweight update check without a full validation request.

**Route:**
```
POST /license_api.php?action=updates
```

**Request body:**
```json
{
  "license_id": "LIC-9F3B2C8A",
  "product_id": "calcpro",
  "fingerprint_hash": "sha256:ABC123...",
  "current_version": "1.2.0"
}
```

**Server behavior:**
- Confirm license exists and fingerprint match.
- Return latest version metadata and whether updates are eligible (based on `updates_until` and release dates).

**Success response (eligible):**
```json
{
  "ok": true,
  "data": {
    "updates_until": "2031-12-23T00:00:00Z",
    "eligible": true,
    "latest_version": { "version": "1.5.0", "release_date": "2026-02-01T00:00:00Z", "download_url": "https://example.com/downloads/calcpro_1.5.0.zip" }
  }
}
```

---

### Transfer Request

**Purpose:** Create a transfer request that vendor/support can review and approve.

**Route:**
```
POST /license_api.php?action=transfer_request
```

**Request body:**
```json
{
  "license_id": "LIC-9F3B2C8A",
  "product_id": "calcpro",
  "from_fingerprint_hash": "sha256:OLD...",
  "to_fingerprint_hash": "sha256:NEW...",
  "reason": "Old PC crashed",
  "contact": { "name": "John Doe", "email": "client@example.com", "phone": "+233..." }
}
```

**Server behavior:**
- Ensure license exists.
- Create a transfer request record with status `OPEN` (do not rebind the license immediately).
- Return the request ID to the caller.

**Success response example:**
```json
{
  "ok": true,
  "data": { "request_id": "TR-000104", "status": "OPEN", "message": "Request received. Support will respond." }
}
```

**Error example (limit reached):**
```json
{
  "ok": false,
  "error": "Transfer limit reached for this license.",
  "code": "TRANSFER_LIMIT_REACHED"
}
```

---

## Server Data Requirements

The server must store (at minimum):
- License records
- Machine binding records
- Check-in history
- Transfer requests
- Product/latest version information

See `schema.md` for the proposed database schema and migration plan.

---

## Client Enforcement Expectations (v1)

Clients must enforce these behaviors locally:
- Enforce license `status` at startup.
- Enforce fingerprint match.
- Enforce offline policy windows using cached timestamps and `check_interval_days` / `warn_after_days` / `max_offline_days`.
- Enforce update entitlement using `updates_until` and app release dates.

The server provides authoritative truth; the client enforces discovery and policy locally for offline resilience.

---

## Versioning & Compatibility

- This document describes API **v1**. v1 is intentionally stable and conservative.
- Add backwards-compatible fields in responses when needed; avoid breaking changes. When incompatible changes are required, bump the API major version.

---

## References

- `schema.md` — database schema and migrations
- Audit logs and operational monitoring should be defined in the implementation notes.

---

*Document last updated: 2025-12-23*

v1 clients must ignore unknown fields in responses

server can add new fields without breaking v1

breaking changes require schema_version increment and v2 docs

12. Implementation Notes (v1)

All endpoints can be implemented in one script (license_api.php) using an action switch.

Always return RFC3339 UTC timestamps.

Always log check-ins and activation attempts for auditing.

Keep error messages human-readable.
