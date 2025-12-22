# Calc Pro

Calc Pro is a simple, well-structured PHP + MySQL calculator application designed for **learning, practice, and demonstration** purposes.  
It intentionally brings multiple calculator domains into **one small app** to help you practice architecture, PHP logic, MySQL integration, frontend interaction, and **ionCube usage patterns**.

This project is ideal for:

- Practicing PHP application structure without frameworks
- Understanding secure calculation workflows
- Learning how to integrate ionCube legitimately
- Using Git and GitHub for version control and traceability

---

## Key Features

### 1. Basic Calculator

- Arithmetic expressions
- Brackets and operator precedence
- Expression evaluation using `math.js` (safe, no JS eval)

### 2. Scientific Calculator

- Trigonometric functions: `sin`, `cos`, `tan`
- Logarithmic functions: `log`, `ln`
- Powers, roots, factorial
- Constants: `pi`, `e`

### 3. Financial Calculations

- Simple Interest
- Compound Interest
- Loan Repayment (amortized loans)
- All formulas calculated server-side in PHP

### 4. Statistical Calculations

- Mean
- Median
- Mode
- Variance
- Standard Deviation
- Min / Max
- Robust input parsing and validation

### 5. Graph Drawing

- Plot `y = f(x)` functions
- Configurable range and step size
- Uses Chart.js for clean rendering
- Save and reload graph definitions from MySQL

### 6. Persistence (MySQL)

- Calculation history stored in database
- Saved graph definitions
- Clean PDO-based database access

### 7. ionCube Practice Support

- Proper ionCube Loader detection
- Clear separation of protected logic
- No bypassing or circumvention
- Safe for real-world learning

---

## Technology Stack

| Layer        | Technology                      |
| ------------ | ------------------------------- |
| Backend      | PHP 8.x                         |
| Database     | MySQL 8.x                       |
| Frontend     | Tailwind CSS                    |
| JS Framework | Alpine.js                       |
| Math Engine  | math.js                         |
| Charts       | Chart.js                        |
| Icons        | Heroicons (inline SVG)          |
| Security     | ionCube Loader (practice-ready) |
| Versioning   | Git + GitHub                    |

---

## Database Configuration

**Important:**  
The database name is:

```
calcpro
```

### Create the Database

```sql
CREATE DATABASE calcpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Tables

Tables are defined in `schema.sql`. Import them using:

```bash
mysql -u root -p calcpro < schema.sql
```

---

## Environment Configuration

Database connection is handled in `db.php` using PDO.

### Default (Local Development)

```text
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = calcpro
DB_USER = root
DB_PASS = (empty)
```

### Recommended (Environment Variables)

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=calcpro
export DB_USER=root
export DB_PASS=your_password
```

---

## Running the Application

Using PHP’s built-in server:

```bash
php -S localhost:8000
```

Open in browser:

```
http://localhost:8000/index.php
```

---

## ionCube Learning Notes

This project **does not** attempt to bypass ionCube.

What it demonstrates correctly:

- How to detect the ionCube Loader
- How to fail gracefully if the loader is missing
- Where to place encoded files
- How to separate protected logic

### Recommended ionCube Workflow

1. Write sensitive PHP logic in a separate file
2. Encode it locally using the official ionCube Encoder
3. Deploy encoded output to a server with ionCube Loader enabled
4. Keep fallback messaging clean and professional

---

## Git & GitHub Workflow

Recommended commit style (Conventional Commits):

```text
feat: add scientific calculator
feat: add financial loan calculator
fix: validate empty expressions
docs: update README
refactor: extract db connector
```

### Initial Setup

```bash
git init
git add .
git commit -m "feat: initial Calc Pro implementation"

git branch -M main
git remote add origin <your-github-repo-url>
git push -u origin main
```

---

## Project Philosophy

- Simple directory structure (all files in root)
- No frameworks, no magic
- Explicit logic, readable code
- Production-minded patterns without overengineering
- Easy to extend into a real product later

---

## Roadmap (Optional Enhancements)

- User authentication and per-user history
- CSV / PDF export of calculations
- Unit conversions (engineering & medical)
- Offline-first asset bundling
- Role-based access with ionCube-protected modules

---

## License

MIT License  
You are free to use, modify, and extend this project.

---

**Calc Pro**  
A clean practice ground for real-world PHP, math, databases, and software discipline.
