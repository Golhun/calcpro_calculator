-- schema.sql
-- Licensing Engine Database Schema (v1)

CREATE TABLE licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,

    license_id VARCHAR(64) NOT NULL UNIQUE,
    product_id VARCHAR(64) NOT NULL,

    customer_id VARCHAR(64) NULL,
    customer_name VARCHAR(255) NULL,

    plan ENUM('trial', 'perpetual', 'subscription') NOT NULL,
    status ENUM(
        'TRIAL',
        'TRIAL_EXPIRED',
        'ACTIVE',
        'ACTIVE_WARN',
        'SUSPENDED',
        'REVOKED',
        'EXPIRED'
    ) NOT NULL,

    issued_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    updates_until DATETIME NOT NULL,

    trial_days INT NULL,

    fingerprint_hash VARCHAR(128) NULL,
    fingerprint_bound TINYINT(1) NOT NULL DEFAULT 0,

    check_interval_days INT NOT NULL DEFAULT 30,
    warn_after_days INT NOT NULL DEFAULT 180,
    max_offline_days INT NOT NULL DEFAULT 365,
    max_transfers INT NOT NULL DEFAULT 2,

    notes TEXT NULL,

    signature_alg VARCHAR(32) NOT NULL,
    signature TEXT NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_product (product_id),
    INDEX idx_status (status)
);

-- Track activations and validations
CREATE TABLE license_checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,

    license_id VARCHAR(64) NOT NULL,
    fingerprint_hash VARCHAR(128) NOT NULL,

    action ENUM('activate', 'validate') NOT NULL,
    server_status VARCHAR(32) NOT NULL,
    message VARCHAR(255) NULL,

    app_version VARCHAR(32) NULL,
    app_release_date DATE NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_license (license_id)
);

-- Transfer requests
CREATE TABLE license_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    request_id VARCHAR(64) NOT NULL UNIQUE,
    license_id VARCHAR(64) NOT NULL,

    from_fingerprint_hash VARCHAR(128) NOT NULL,
    to_fingerprint_hash VARCHAR(128) NOT NULL,

    reason TEXT NULL,
    contact_name VARCHAR(255) NULL,
    contact_email VARCHAR(255) NULL,
    contact_phone VARCHAR(64) NULL,

    status ENUM('OPEN', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'OPEN',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,

    INDEX idx_license (license_id)
);

-- Latest product versions
CREATE TABLE product_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    product_id VARCHAR(64) NOT NULL,
    version VARCHAR(32) NOT NULL,
    release_date DATE NOT NULL,
    download_url TEXT NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_product_version (product_id, version)
);
