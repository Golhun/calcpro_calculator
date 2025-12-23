Licensing Engine Schema Specification (v1)
Purpose

This document defines the frozen data contract used by the Licensing Engine. It covers:

The signed license payload (license.key)

The local client cache (license.state.json)

Required fields, formats, and enforcement rules

This schema is designed to be product-agnostic and offline-first.

1. License Payload File (license.key)
   Format

UTF-8 JSON (minified or pretty is acceptable)

Must include a digital signature over a canonicalized payload

Stored locally on the client machine

File name

license.key

Schema (v1)
{
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

"trial": {
"trial_days": null
},

"fingerprint": {
"mode": "machine",
"bound": true,
"fingerprint_hash": "sha256:..."
},

"policy": {
"check_interval_days": 30,
"warn_after_days": 180,
"max_offline_days": 365,
"max_transfers": 2
},

"meta": {
"notes": null
},

"signature_alg": "ed25519",
"signature": "BASE64_SIGNATURE"
}

1.1 Field Definitions
schema_version (int)

Fixed: 1 for this schema version.

license_id (string)

Unique license identifier.

Recommended format: LIC- + 8–16 chars.

Example: LIC-9F3B2C8A

product_id (string)

Product identifier used across all licensing operations.

Example: calcpro

customer (object)

customer_id (string): vendor-issued unique customer reference.

name (string): customer’s legal or trade name.

plan (enum string)

Supported values:

trial

perpetual

subscription (reserved for future)

status (enum string)

Supported values:

TRIAL

TRIAL_EXPIRED

ACTIVE

ACTIVE_WARN

EXPIRED

SUSPENDED

REVOKED

Meaning

ACTIVE: normal operation

ACTIVE_WARN: runs, but warn (e.g. offline too long)

SUSPENDED: block until vendor reactivates

REVOKED: block permanently

EXPIRED: block because usage period ended

TRIAL_EXPIRED: trial ended, block or reduce-mode based on policy

issued_at, expires_at, updates_until (RFC3339 UTC string)

Required: issued_at

Required: expires_at

Required: updates_until for perpetual and subscription

For trial, updates_until should exist but normally equals expires_at

Interpretation

expires_at: right-to-run deadline

updates_until: right-to-update deadline

trial.trial_days (int or null)

If plan=trial, must be an integer > 0.

If plan≠trial, must be null.

fingerprint (object)

mode: currently only machine

bound (bool): true means license is machine-bound

fingerprint_hash (string):

sha256:<hex> strongly recommended

Either pre-bound at issuance, or set at first activation

policy (object)

check_interval_days (int): server validation interval target (default 30)

warn_after_days (int): days since last check before showing warnings (default 180)

max_offline_days (int): hard maximum allowed without server validation (default 365)

max_transfers (int): allowed vendor-approved transfers per period (policy enforced server-side)

meta.notes (string or null)

Optional notes for vendor use.

signature_alg (string)

v1 uses ed25519 (recommended for simplicity and security).

signature (string)

Base64 signature of the canonical payload.

2. Canonicalization and Signing Rules
   2.1 Canonical Payload

To ensure consistent verification:

The signed payload is the JSON object excluding:

signature

Canonicalization must:

sort keys consistently

normalize whitespace (no spaces)

preserve exact field values

2.2 Verification

Client verifies:

Signature is valid for payload

product_id matches application

status allows run

expires_at not passed (using trusted time strategy)

fingerprint matches machine (if bound)

3. Local Validation Cache (license.state.json)
   Purpose

Enables offline operation and supports grace windows.

File name

license.state.json

Schema (v1)
{
"schema_version": 1,

"license_id": "LIC-9F3B2C8A",
"product_id": "calcpro",

"first_activated_at": "2025-12-23T10:11:00Z",

"last_success_check_at": "2026-01-23T08:00:00Z",
"next_check_due_at": "2026-02-22T08:00:00Z",

"last_server_status": "ACTIVE",
"last_server_message": "OK",

"locked_to_fingerprint_hash": "sha256:...",

"clock_guard": {
"last_seen_time": "2026-01-23T08:00:00Z",
"rollback_count": 0
}
}

3.1 Cache Field Definitions

schema_version: fixed 1

license_id, product_id: copy from license payload

first_activated_at: written once on first activation

last_success_check_at: updated after server validation success

next_check_due_at: computed using policy check_interval_days

last_server_status: last status returned

last_server_message: last informational message

locked_to_fingerprint_hash: fingerprint hash enforced locally

clock_guard: soft protection against time rollback

4. Enforcement Rules (Client)
   4.1 Startup enforcement

Client must block startup if:

signature invalid

product_id mismatch

status in {SUSPENDED, REVOKED, EXPIRED, TRIAL_EXPIRED}

fingerprint mismatch (when bound)

current trusted time > expires_at

4.2 Offline enforcement

If no server check occurs:

allow run until max_offline_days from last_success_check_at

warn after warn_after_days

hard block after max_offline_days

4.3 Update entitlement enforcement

When installing or applying updates:

each release must provide APP_RELEASE_DATE

update allowed only if:

APP_RELEASE_DATE <= updates_until

If not allowed:

do not apply update

present upgrade messaging

5. Trial Model (v1 default)
   Trial behavior

Trial is machine-bound

Trial starts on first activation (or first run, depending on implementation choice)

Trial ends when expires_at is reached

After trial:

block or reduce-mode can be configured later, but v1 will block by default unless explicitly changed

6. Reserved Extensions (Future)

The following are reserved for future without breaking v1:

subscription plan enforcement

entitlements or addons blocks (feature-based editions)

multi-seat licensing

floating licenses (LAN license server)

offline activation codes and emergency unlock payloads

7. Compatibility Guarantees

v1 clients must ignore unknown fields

server may add new fields without breaking v1 clients

schema version bump required only for breaking changes

8. Quick Examples
   Perpetual License Example

plan=perpetual

expires_at far future

updates_until limited

full access implied

Trial License Example

plan=trial

trial.trial_days=60

expires_at set to trial end

updates_until=expires_at

9. Implementation Notes

License verification logic must be ionCube-encoded in production.

The public verification key must be shipped with the client (safe for public distribution).

Private signing key must never be shipped to clients.
