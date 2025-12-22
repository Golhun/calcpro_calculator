# CalcPro Engine

CalcPro Engine is a licensed, web-based advanced calculator built with PHP, MySQL, Alpine.js, and Tailwind CSS.
It is designed as a reference implementation for ionCube licensing, code protection, and feature gating in a real-world PHP application.

The project deliberately separates:

- Free, readable application code
- Licensed, ionCube-encoded calculation engines
- Modern frontend UI
- Persistent storage

This makes it suitable both as a learning project and as a blueprint for commercial PHP software.

---

## Features

### Free Features

- Basic arithmetic calculations
- Responsive, modern UI
- Calculation history stored in MySQL

### Licensed (Pro) Features

- Scientific calculations
- Financial calculations
- Statistical calculations
- Expression parsing
- Feature access controlled by license state

---

## Technology Stack

### Backend

- PHP 8.1 or higher
- MySQL 8+
- PDO
- ionCube Loader (runtime)
- ionCube Encoder (for protected modules)

### Frontend

- Tailwind CSS
- Alpine.js
- Heroicons (SVG icons)

---

## Project Structure

/public
index.php
api.php

/app
/bootstrap
app.php
db.php
license.php
/free
BasicCalculator.php
/protected
ionCube encoded files
/resources
/views
calculator.php

/config
/licenses
/storage

---

## Database Setup

Create the database:

CREATE DATABASE calcpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

Create tables:

CREATE TABLE calculations (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
expression VARCHAR(255) NOT NULL,
result VARCHAR(255) NOT NULL,
module ENUM('basic','scientific','financial','statistics') NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE app_state (
id TINYINT PRIMARY KEY DEFAULT 1,
last_license_check TIMESTAMP NULL,
license_status ENUM('valid','expired','invalid','trial') NOT NULL,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

---

## Installation

1. Clone the repository
2. Configure database credentials in /app/bootstrap/db.php
3. Ensure ionCube Loader is installed for your PHP version
4. Point your web server document root to /public
5. Open the app in your browser

---

## Licensing

This project is designed to work with ionCube licenses.

- License files are stored in /licenses
- License validation logic lives in /app/bootstrap/license.php
- Pro features are implemented in encoded files under /app/protected

During early development, license validation is stubbed to allow UI and logic development before encoding.

---

## Development Philosophy

- Free code stays readable
- Business value lives in encoded modules
- License checks are enforced at both UI and API level
- Configuration and customer data are never overwritten during updates

This mirrors how professional PHP software is shipped and maintained.

---

## Disclaimer

This project is for educational and architectural reference purposes.
You are responsible for complying with ionCube licensing terms when distributing encoded software.

---

## Author

Built as a hands-on exploration of secure PHP software distribution and licensing.
