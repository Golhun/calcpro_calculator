# Licensing Engine — Integration Guide

This document explains how to integrate the **Licensing Engine** into any PHP project, which files to copy from the `Licensing Engine/` folder, what files/changes are needed in the host project, and basic testing and security recommendations.

---

## Overview ✅

The Licensing Engine provides a compact client for offline-friendly, cryptographically signed licenses and an optional server API for activation, validation, updates, and transfers. The core idea: the Engine ships a client library that reads a signed `license.key` and enforces policy at app startup.

> **Important:** For strict tamper protection set `require_signature` to `true` in the client configuration and provide the `public_key_b64` (or set environment variable `PUBLIC_KEY_B64`). If `require_signature` is true and no public key is configured, boot will fail.

---

## Files to copy from `Licensing Engine/` (client-side)

Copy these files (or their compiled/protected equivalents) into your project or vendor folder. Keep structure intact where possible.

- `License.php` — main client class (boot, assertValid, activateOnline)
- `Storage.php` — read/write license and state files
- `Validator.php` — structural & policy checks, fingerprint checks
- `sign.php` — signature verification helpers
- `canonical.php` — canonical JSON helpers used for signing
- `Fingerprint.php` — machine fingerprint generation
- `Policy.php`, `TimeGuard.php` — offline/check-in helpers
- `HttpClient.php` — optional: server calls (activation/validate/updates)
- `client_config.php` — default per-app config (copy and edit into your app)
- `test_validate.php` — example script to test local validation
- `keys_generate.php`, `issue_local_license.php` — dev helpers (server-side)

Server-side (optional) or for your central licensing host:

- `license_api.php` — example server API endpoints
- `schema.sql` — DB schema for licensing records
- `server_api.md` — server-side contract and usage

---

## Files / changes required in the host project

Add or verify the following in the host project:

1. `config.php` (app-level config)
   - Add constants to allow overrides if you want app-level control:
     - `LICENSE_FILE` — path to `license.key` (recommended: outside web root)
     - `LICENSE_STATE_FILE` — path to `license.state.json`
     - `LICENSE_PRODUCT_ID` — product id the app expects (should match license `product_id`)
   - Example:

```php
// config.php (example)
define('LICENSE_FILE', __DIR__ . '/../secrets/license.key');
define('LICENSE_STATE_FILE', __DIR__ . '/../secrets/license.state.json');
define('LICENSE_PRODUCT_ID', 'your_product_id_here');
```

2. `license_bootstrap.php` (use provided file or adapt)

   - This file must execute very early (top of `index.php`, `api.php`, CLI entry scripts) to hard-stop execution on license failure.
   - The provided `license_bootstrap.php` loads `config.php` first (so `LICENSE_FILE` and other overrides are visible) and then boots `License::boot()`.
   - Always hard-fail on validation errors (throw, exit with 403 in web, nonzero in CLI).

3. Include the bootstrap at the top of entry points.

```php
// index.php or api.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/license_bootstrap.php';
```

4. Client config copy

   - Copy `Licensing Engine/client_config.php` into your project (or include it from the engine) and adjust:
     - `product_id` (must match server)
     - `license_file` and `state_file` (if you didn't set app constants)
     - `server.base_url` if you use server activation
     - Optionally set `'public_key_b64' => 'BASE64_PUB_KEY'` and `'require_signature' => true`

5. Secrets & Keys

   - Protect private keys on your server (signing keys). Do NOT include private keys in the client app.
   - Only distribute the public key (base64) to clients via `public_key_b64` in `client_config.php` or `PUBLIC_KEY_B64` env var.

6. .gitignore
   - Add `license.key` and `license.state.json` to `.gitignore` and/or ensure they are stored outside of version control.

---

## Activation & typical workflow

1. Server-side: create license record and use your signing key to build a signed payload (see `issue_local_license.php` or server `buildSignedPayload()` flow).
2. Client-side: run activation (if server enabled)

```php
$lic = License::boot('/path/to/client_config.php');
$lic->activateOnline('LIC-ABC123', $machineInfo);
```

Activation stores `license.key` on disk and populates `license.state.json` with validation state.

---

## Testing & verification ✅

- Local validation: run `php Licensing\ Engine/test_validate.php` (adjust path) to confirm `License::assertValid()` succeeds.
- Enforcement test (CLI): `php tests/test_license_enforcement.php` — this verifies the bootstrap blocks execution when license missing/invalid.
- Tamper test: with `require_signature` enabled and `public_key_b64` configured, modify `expires_at` in `license.key` and confirm validation fails.

---

## Recommended production settings & security notes 🔒

- Always set `'require_signature' => true` and supply `public_key_b64` in production client configs to ensure signature verification is enforced.
- Keep signing private keys offline and secure (use a HSM or private machine). Only sign payloads on the server-side.
- Store `license.key` and `license.state.json` outside the webroot and ensure proper filesystem permissions.
- Optionally obfuscate/protect validation logic (e.g., ionCube) if you ship PHP where tampering is a concern.
- Use `clock_guard` settings in `client_config.php` to limit clock rollback attacks.

---

## Common pitfalls & troubleshooting ⚠️

- Boot order: if the app loads the licensing client before `config.php`, app overrides (like `LICENSE_FILE`) may not be applied. The provided bootstrap loads `config.php` first.
- Missing public key: if `require_signature` is true and no public key configured, boot will throw "License signature required but public key not configured.".
- Multiple entry points: ensure every entry point (index.php, api.php, any CLI tasks that must be protected) either includes `license_bootstrap.php` or otherwise checks the license.

---

## Quick integration checklist ✅

- [ ] Copy client files (library + `client_config.php`) into your project or include via vendor structure
- [ ] Configure `product_id` and file paths (`LICENSE_FILE` or client config)
- [ ] Provide `public_key_b64` and set `require_signature` in production
- [ ] Insert `require_once 'license_bootstrap.php'` at top of web & API entry points
- [ ] Add `license.key` and `license.state.json` to `.gitignore`
- [ ] Run `php test_validate.php` and `php tests/test_license_enforcement.php`
