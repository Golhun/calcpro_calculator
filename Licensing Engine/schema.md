# Licensing Engine — Schema Specification (v1)

## Purpose

A concise, stable data contract for Licensing Engine v1. Covers the on-disk signed license payload (`license.key`), the local validation cache (`license.state.json`), canonicalization & signing rules, enforcement policies, and quick examples.

---

## Table of contents

- [License payload (`license.key`)](#license-payload-licensekey)
- [Field definitions](#field-definitions)
- [Canonicalization & signing rules](#canonicalization--signing-rules)
- [Local validation cache (`license.state.json`)](#local-validation-cache-licensestateful)
- [Client enforcement rules](#client-enforcement-rules)
- [Trial model](#trial-model)
- [Reserved extensions](#reserved-extensions)
- [Compatibility guarantees](#compatibility-guarantees)
- [Quick examples](#quick-examples)
- [Implementation notes & security](#implementation-notes--security)

---

## License payload (`license.key`)

Format: UTF-8 JSON (minified or pretty). Must include a detached digital signature over a canonicalized payload. Stored on the client as `license.key`.

Example payload (v1):

```json
{
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
  "fingerprint": { "mode": "machine", "bound": true, "fingerprint_hash": "sha256:..." },
  "policy": { "check_interval_days": 30, "warn_after_days": 180, "max_offline_days": 365, "max_transfers": 2 },
  "meta": { "notes": null },
  "signature_alg": "ed25519",
  "signature": "BASE64_SIGNATURE"
}
```

---

## Field definitions

- `schema_version` (int): fixed `1` for this document.
- `license_id` (string): unique license identifier (recommend: `LIC-` + 8–16 chars).
- `product_id` (string): product short-name used across API and client.
- `customer` (object): `{ customer_id, name }` vendor identity fields.
- `plan` (enum): `trial`, `perpetual`, (`subscription` reserved).
- `status` (enum): `TRIAL`, `TRIAL_EXPIRED`, `ACTIVE`, `ACTIVE_WARN`, `EXPIRED`, `SUSPENDED`, `REVOKED`.
  - `ACTIVE`: normal operation
  - `ACTIVE_WARN`: running but client should warn (e.g., prolonged offline)
  - `SUSPENDED` / `REVOKED` / `EXPIRED` / `TRIAL_EXPIRED`: block by policy
- `issued_at`, `expires_at`, `updates_until` (RFC3339 UTC strings):
  - `issued_at` and `expires_at` are required
  - `updates_until` required for perpetual/subscription; for trials, typically equals `expires_at`
- `trial.trial_days` (int | null): integer > 0 when `plan=trial`, otherwise `null`.
- `fingerprint` (object): `{ mode, bound, fingerprint_hash }` — `fingerprint_hash` should be `sha256:<hex>` where possible.
- `policy` (object): `{ check_interval_days, warn_after_days, max_offline_days, max_transfers }` — server-enforced limits.
- `meta.notes` (string | null): optional vendor notes.
- `signature_alg` (string): `ed25519` recommended for v1.
- `signature` (string): base64 signature over the canonicalized payload.

---

## Canonicalization & signing rules

### Canonical payload
- The value signed is the JSON object **without** the `signature` field.
- Canonicalization rules (v1):
  - Sort object keys lexicographically.
  - Use a deterministic whitespace-free representation (no extra spaces, newline rules consistent).
  - Preserve exact field values and types.

> Implementation note: Choose a consistent canonicalization library on both server and client to avoid signature verification issues.

### Verification steps (client)
- Verify signature using the public key and `signature_alg`.
- Ensure `product_id` matches the running application.
- Ensure `status` allows running and `expires_at` not passed.
- If `fingerprint.bound === true`, ensure `fingerprint_hash` matches the machine.

---

## Local validation cache (`license.state.json`)

Purpose: support offline workflows, grace windows, and quick client checks. Stored as JSON on the client.

Example `license.state.json` (v1):

```json
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
  "clock_guard": { "last_seen_time": "2026-01-23T08:00:00Z", "rollback_count": 0 }
}
```

### Cache fields
- `first_activated_at`: set once on first successful activation.
- `last_success_check_at`: updated after each successful server validation.
- `next_check_due_at`: computed from `last_success_check_at` + `policy.check_interval_days`.
- `last_server_status` / `last_server_message`: last returned server state.
- `locked_to_fingerprint_hash`: local enforcement copy of machine binding.
- `clock_guard`: simple anti-rollback guard `{ last_seen_time, rollback_count }`.

---

## Client enforcement rules

### Startup enforcement
Client must block startup when:
- signature verification fails
- `product_id` mismatch
- `status` in `{ SUSPENDED, REVOKED, EXPIRED, TRIAL_EXPIRED }`
- fingerprint mismatch (when bound)
- current trusted time > `expires_at`

### Offline enforcement
When offline or unable to contact server:
- Allow running until `max_offline_days` since `last_success_check_at`.
- Warn after `warn_after_days`.
- Hard block after `max_offline_days`.

### Update entitlement
When applying updates, require `APP_RELEASE_DATE` (from the update metadata) to be <= `updates_until`; otherwise deny applying the update and present upgrade messaging.

---

## Trial model (v1)
- Trial is machine-bound and typically starts at first activation.
- `trial.trial_days` controls trial length; `expires_at` marks the trial end.
- v1 default behavior: block after trial expires unless policy changes are applied.

---

## Reserved extensions (future)
- Subscription enforcement and recurring billing hooks
- Entitlements / add-ons (feature flags)
- Multi-seat / floating licenses and LAN license servers
- Offline activation codes and emergency unlock payloads

---

## Compatibility guarantees
- v1 clients must ignore unknown fields.
- Server may add non-breaking fields freely; breaking changes require schema version bump.

---

## Quick examples
- Perpetual: `plan=perpetual`, `expires_at` far future, `updates_until` limited.
- Trial: `plan=trial`, `trial.trial_days=60`, `expires_at` equals trial end.

---

## Implementation notes & security
- License verification logic should be protected (ionCube or similar) in production builds.
- Public verification key is safe to ship with the client; private signing key must remain server-side and secure.

---

*Document last updated: 2025-12-23*
