# CalcPro Engine

CalcPro Engine is a PHP-based calculator application designed as a **licensable computation engine**.  
It demonstrates how to build a modern PHP app with **MySQL persistence**, **Tailwind CSS**, **Alpine.js**, and a clean architecture that is ready for **ionCube code protection**.

The project intentionally separates **free, readable code** from **protected Pro logic**, mirroring real-world commercial PHP software distribution.

---

## 1. Purpose of This Project

This project is built to help you:

- Practice real-world PHP application structure
- Implement licensing and feature gating
- Store and audit calculations in MySQL
- Prepare proprietary logic for ionCube encoding
- Build a modern, responsive UI without a framework

It is **not** a toy calculator.  
It is a miniature, production-style software product.

---

## 2. Tech Stack

- **Backend:** PHP 8+ (procedural entry point with OOP core)
- **Database:** MySQL (PDO, prepared statements)
- **Frontend:** Tailwind CSS, Alpine.js
- **Icons:** SVG-ready (Heroicons recommended)
- **Security:** Sessions, password hashing, CSRF protection
- **Licensing Ready:** Designed for ionCube Loader integration

---

## 3. Project Structure

```
calcpro/
├── public/                 # Web root
│   └── index.php
│
├── app/
│   ├── config/             # App and DB configuration
│   ├── core/               # Core utilities (Auth, DB, Security)
│   ├── free/               # Free, readable calculator logic
│   ├── protected/          # Pro logic (to be ionCube-encoded)
│   └── views/              # UI views (Tailwind + Alpine)
│
├── storage/
│   └── logs/
│
├── .env.example
├── README.md
└── .gitignore
```

Key principle:

- Everything in `/app/protected` is considered **commercial IP**
- Everything else is safe to read and maintain

---

## 4. Features Implemented

### Free Features

- Basic arithmetic calculator
- Calculation history per user
- User authentication
- Responsive modern UI

### Licensing Foundation

- License table with plan, expiry, binding fields
- License-aware UI
- Feature-gating hooks ready for Pro logic

### Ready for Pro (Next Phase)

- Scientific calculations
- Financial calculations
- Statistics engine
- Expression parser
- ionCube Loader checks

---

## 5. Database Setup

1. Create a MySQL database named `calcpro`
2. Run the schema provided in the documentation
3. Create at least one user manually (seed)

Passwords **must** be hashed using:

```php
password_hash('your-password', PASSWORD_DEFAULT);
```

---

## 6. Environment Setup

1. Copy `.env.example` to `.env`
2. Update database credentials
3. Ensure PHP sessions are enabled
4. Point your web server to `/public`

Example local setups:

- XAMPP
- Laragon
- Linux + Apache/Nginx

---

## 7. Security Notes

- All database queries use prepared statements
- CSRF tokens are enforced on forms
- Sessions regenerate on login
- Sensitive files should live **outside web root**
- License files should be read-only in production

---

## 8. ionCube Integration Plan

This project is intentionally ionCube-friendly.

Recommended workflow:

1. Develop Pro logic normally in `/app/protected`
2. Encode Pro files with ionCube Encoder
3. Replace original files with encoded versions
4. Verify ionCube Loader availability at runtime
5. Gate access by license plan and environment

---

## 9. Who This Is For

- PHP developers learning commercial software protection
- Engineers building licensable PHP tools
- Developers moving beyond simple CRUD apps
- Anyone who wants clean PHP without heavy frameworks

---

## 10. License

This project is for **learning and internal use**.
Adapt and extend it responsibly for commercial deployment.

---

Built with clarity, structure, and production discipline.
