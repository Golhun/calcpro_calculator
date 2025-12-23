# PHP Licensing Engine (Offline-First)

A standalone, reusable licensing engine for PHP applications, designed primarily for locally installed software with optional online validation. Built to work seamlessly with **ionCube-encoded** PHP files.

---

## 🚀 Overview

This engine is product-agnostic and built for desktop or server-based PHP installations. It supports perpetual licenses, time-limited trials, machine binding, and update entitlement control while maintaining an **offline-first** philosophy.

## 🎯 Design Goals

- **Environment:** Work for local desktop/server installations.
- **Connectivity:** Operate offline by default with controlled periodic online checks.
- **Licensing:** Support perpetual licenses with update entitlements.
- **Security:** Prevent tampering using ionCube encoding and digital signatures.
- **Simplicity:** Remain auditable, extensible, and free of "SaaS-forced" assumptions.

---

## ✅ Functional Scope

### What This Engine Does

- Validates license authenticity via digital signatures.
- Enforces trial periods and expiration dates.
- Binds licenses to specific hardware (Machine Fingerprinting).
- Controls update eligibility based on release dates.
- Manages offline usage windows and optional server sync.

### What This Engine Does NOT Do

- ✘ Implement your application's business logic.
- ✘ Manage the UI/UX for license entry.
- ✘ Enforce feature flags (can be extended to do so).
- ✘ Auto-update your application files.

---

## 🏗 High-Level Architecture

```text
+-------------------+
|   PHP Application |
+-------------------+
          |
          v
+-------------------+
| Licensing Client  |  ← ionCube-encoded
| (this project)    |
+-------------------+
          |
          v
+-------------------+
| License File      |  ← signed, machine-bound
+-------------------+

Optional:
          |
          v
+-------------------+
| License Server    |
+-------------------+
🔑 Core Concepts
1. Offline-First Philosophy
Internet loss should never "brick" the application. Online checks are periodic and non-blocking based on a policy-driven window.

2. Machine Binding
Licenses are bound using a unique fingerprint derived from stable hardware and OS identifiers to prevent silent reuse across multiple machines.

3. Update Entitlement Model
We separate "License Validity" from "Update Entitlement."

Valid for use: The software runs.

Eligible for updates: Allowed only if APP_RELEASE_DATE ≤ updates_until.

4. ionCube Integration
Only critical security logic is encoded to balance protection with developer flexibility:

Validation & Fingerprinting logic.

Signature verification.

Server communication.

📂 Project Structure (Planned)
Plaintext

/
├── README.md               # You are here
├── LICENSE.md              # Legal terms
├── schema.md               # License file JSON structure
├── server_api.md           # API contract for remote validation
├── license_client.php      # Main entry point (Encoded)
├── fingerprint.php         # HWID generation (Encoded)
├── validator.php           # Logic for checking signatures (Encoded)
├── policy.php              # Offline/Trial logic (Encoded)
├── helpers.php             # Non-critical utilities
└── examples/               # Integration samples
🛠 Integration
Applications should interact only with the public API. No application code should inspect license internals.

PHP

// Standard integration pattern
License::boot();
License::assertValid();

if (License::canReceiveUpdates()) {
    // Logic for checking for new versions
}
🛡 Security Model
Digital Signatures: Licenses are signed using asymmetric cryptography.

Asymmetric Trust: The client holds only the Public Key.

Tamper Proofing: Logic is hidden via ionCube to prevent "cracking" the validation checks.

🚧 Status
Phase: Design / Initial Architecture.

Upcoming: Finalizing JSON schema and Server API contracts.

Licensing should protect the business without punishing honest customers.
```
