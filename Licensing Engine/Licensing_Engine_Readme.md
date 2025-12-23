# PHP Licensing Engine (Offline-First)

## Purpose

A small, product-agnostic licensing engine for PHP apps focused on desktop/server installations. It provides:
- Signed license payloads (offline-friendly)
- Machine binding and activation
- Update entitlement controls
- A compact server API for validation and transfer workflows

---

## Table of contents

- [Quick start](#quick-start) ✅
- [Core features](#core-features) 🎯
- [Integration example](#integration-example) 🛠
- [Architecture](#architecture) 🏗
- [Security model](#security-model) 🛡
- [Project structure](#project-structure) 📂
- [Status & roadmap](#status--roadmap) 🚧
- [Contributing](#contributing) 🤝
- [License](#license) 📜

---

## Quick start

1. Drop the ionCube-encoded client files into your app.
2. Call the public API:

```php
License::boot();
License::assertValid();
```

3. Use `License::canReceiveUpdates()` to gate update checks.

Note: The verification logic is encoded for production; see [Security model](#security-model).

---

## Core features

- Offline-first validation and policy enforcement
- Machine fingerprint binding and activation
- Trial, perpetual, and update-entitlement models
- Simple server API for `activate`, `validate`, `updates`, and `transfer_request`

What this engine intentionally does NOT include:
- Application-specific business logic or UI
- Automatic file updates (it only indicates update eligibility)

---

## Integration example

Typical usage in app bootstrap:

```php
require_once 'license_client.php';

License::boot();
if (!License::assertValid()) {
  // Show licensing UI or exit
}

if (License::canReceiveUpdates()) {
  // Query update server
}
```

---

## Architecture

- Client: ionCube-encoded verification and local enforcement.
- Local artifacts: `license.key` (signed payload) and `license.state.json` (cache).
- Optional Server: provides activation/validation/update metadata and transfer workflows.

Core concepts:
- Offline-first: allow limited offline use using policy windows.
- Machine-binding: licenses can be tied to specific machines to reduce abuse.
- Update entitlement: updates allowed only when `APP_RELEASE_DATE <= updates_until`.

---

## Security model

- Licenses are signed server-side (ed25519 recommended).
- Clients ship only the public verification key.
- Critical verification logic should be ionCube-encoded for production builds.
- Private signing key must remain on the server and be properly protected.

---

## Project structure

```
/ (root)
├── README.md
├── LICENSE.md
├── schema.md
├── server_api.md
├── license_client.php      # Encoded verification library
├── fingerprint.php         # HWID generation (encoded)
├── validator.php           # Signature validation (encoded)
├── policy.php              # Offline/trial logic (encoded)
├── helpers.php             # Non-sensitive helpers
└── examples/               # Integration examples
```

---

## Status & roadmap

- Phase: Design / Initial Architecture
- Next: finalize `schema.md` and `server_api.md`, add integration examples, and prepare test harnesses

---

## Contributing

- Keep docs clear and include JSON examples for schema/API updates.
- Tests and examples are welcome in `/examples`.

---

## License

Refer to `LICENSE.md` in the repo for licensing terms.

---

*Last updated: 2025-12-23*
```
