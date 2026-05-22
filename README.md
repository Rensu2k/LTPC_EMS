# 🏛️ LTPC_EMS (Learning & Training Programs Center - Evaluation Management System)

A high-performance, enterprise-grade enrollment, payment, and assessment management platform engineered for the **Surigao City Livelihood Training and Productivity Center** (SSCT / SNSU). Built on the modern **Laravel 12**, **Inertia.js 2**, and **Vue 3** stack.

---

## 👥 Authors & Intellectual Property
*   **Lead Developer:** [Clarence Buenaflor](mailto:cbuenaflor2@ssct.edu.ph)
*   **Contributor:** [Jester Pastor](mailto:pastorjester98@mail.com)
*   **Copyright:** © 2025-2026 Clarence Buenaflor & Jester Pastor. All Rights Reserved.
*   **License:** Proprietary (Unauthorized copying, modification, or distribution is strictly prohibited).

---

## ⚡ Technical Stack Overview

| Layer | Technology / Package | Version / Details |
| :--- | :--- | :--- |
| **Backend Framework** | [Laravel](https://laravel.com) | `^12.0` (PHP `^8.2`) |
| **Frontend Framework** | [Vue.js](https://vuejs.org) | `^3.4` (Composition API) |
| **Bridge** | [Inertia.js](https://inertiajs.com) | `^2.0` (Seamless Single Page App experience) |
| **Styling** | [Tailwind CSS](https://tailwindcss.com) | `^3.2` with custom dashboard theme |
| **Build Tool** | [Vite](https://vite.dev) | `^6.2` (Ultra-fast hot module replacement) |
| **Database** | MySQL | InnoDB engine, optimized indexes |
| **Key Libraries** | Three.js, Vanilla Tilt, ExcelJS, jsPDF, Vue Toastification | Premium user interface animations & exports |

---

## 🔑 Persona-Based Features

### 👑 Administrator Module
*   **Enterprise Dashboard:** Direct analytical overviews of revenue, student pipelines, and trainer load.
*   **User & Role Management:** High-security access control mapping roles dynamically to users (Admin, Officer, Cashier) with active/inactive state switches.
*   **System Diagnostics:** High-fidelity real-time operational state metrics, resource utilization, and application uptime.
*   **Backup & Restore Panel:** System snapshot generation configurations.

### 📋 Training Officer Module
*   **Trainee Admissions:** Extensive profiling, status validation, and historical records tracking.
*   **Trainer Portals:** Track accreditation states, trainer workloads, qualifications, and specialties.
*   **Program & Course Catalogs:** Dynamic schedule adjustments, capacity thresholds, and course offerings.
*   **Assessment & Reassessment Engine:** Performance grading, dynamic reassessment workflows, and historical results auditing.

### 💰 Cashier & Financial Module
*   **Multi-Channel Payment Processing:** Dedicated flows for enrollment invoices, assessment fees, and additional learning supplies.
*   **Interactive Receipt Builder:** Customizable receipt layouts with automatic high-fidelity exports to PDF (`jsPDF`) and Excel (`ExcelJS`).
*   **Financial Reporting:** Live cash collections breakdowns, billing histories, and reconciliation logs.

---

## 📈 Scalability & Performance Engineering
LTPC_EMS is explicitly audited and load-tested for enterprise datasets containing **1 Million+ records** and **20+ concurrent high-throughput users**.

### 1. Database Optimization & Subqueries
*   Bypasses memory-intensive Laravel Eloquent collections in controller actions.
*   Aggregations, filter calculations, and histories are offloaded directly to database-level subqueries, preventing PHP memory exhaustion and PHP-FPM gateway timeouts.

### 2. Strategic Indexing Schema
Crucial tables feature custom composite and single-column indices:
*   `trainees` (`id`, `status`)
*   `enrollments` (`trainee_id`, `program_id`, `status`)
*   `payments` (`enrollment_id`, `type`, `amount`)
*   `assessment_results` (`trainee_id`, `status`, `created_at`)
This prevents costly full-table scans, keeping lookups at O(log N) or O(1).

### 3. Recommended Production Database Configuration (`my.ini` / `my.cnf`)
Ensure the MySQL InnoDB engine has ample buffer pool allocation for millions of queries:
```ini
[mysqld]
innodb_buffer_pool_size = 1G      # Recommended minimum for large datasets
innodb_log_file_size = 256M
innodb_write_io_threads = 8
innodb_read_io_threads = 8
max_connections = 150
```

---

## 🛡️ Enterprise Security Hardening

*   **🛡️ Dev-Signature Middleware (`dev-signature`):** An administrative middleware layer asserting application integrity and licensing verification on system-critical operations.
*   **🚦 Strict Rate Limiting:** All financial routes (Cashier module) and administrative updates are rate-limited (`throttle:60,1`) to eliminate automated brute-force attacks.
*   **🧼 High-Level XSS Sanitization:** Advanced input filtering coupled with Inertia rendering prevents stored and reflected cross-site scripting vulnerabilities.
*   **🔒 Protection Against Information Disclosure:** System health and diagnostic endpoints are restricted strictly to authorized Administrators.

---

## 📋 Requirements
Before you begin, ensure you have the following installed:
*   **XAMPP / Laragon** (PHP `^8.2` and MySQL `^8.0` / MariaDB `^10.4` required)
*   **Composer** (PHP Package Manager) - [Download](https://getcomposer.org/)
*   **Node.js** (includes NPM) - [Download](https://nodejs.org/)
*   **Git** - [Download](https://git-scm.com/)

---

## 🛠️ Installation Guide

### Step 1: Initialize Database Environment
1.  Open the XAMPP Control Panel.
2.  Start the **Apache** and **MySQL** services.
3.  Go to `http://localhost/phpmyadmin` and create a database named `ltpc_database` using the `utf8mb4_unicode_ci` collation.

### Step 2: Clone & Place the Repository
Clone the repository into your web server's public directory (e.g., `C:\xampp\htdocs\LTPC_EMS`):
```bash
cd C:\xampp\htdocs
git clone <repository-url> LTPC_EMS
cd LTPC_EMS
```

### Step 3: Configure Environment Setup
1.  Create your local configuration:
    ```bash
    copy .env.example .env
    ```
2.  Open `.env` and specify the database variables:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=ltpc_database
    DB_USERNAME=root
    DB_PASSWORD=
    ```

### Step 4: Install Dependencies & Setup Keys
1.  Install PHP packages:
    ```bash
    composer install --optimize-autoloader
    ```
2.  Install Javascript modules:
    ```bash
    npm install
    ```
3.  Generate the application security key:
    ```bash
    php artisan key:generate
    ```

### Step 5: Database Migrations & Seeding
1.  Execute schema migrations:
    ```bash
    php artisan migrate
    ```
2.  (Recommended) Seed initial mock records, roles, and administrative configurations:
    ```bash
    php artisan db:seed
    ```

### Step 6: Build Assets & Run

#### Option A: Development (Hot Reloading)
Run the Vite development server in one terminal pane:
```bash
npm run dev
```
Start the local PHP server in another terminal pane:
```bash
php artisan serve
```
🎉 Your app is now live at `http://127.0.0.1:8000`.

#### Option B: Production Mode
Compile the frontend assets with high optimizations:
```bash
npm run build:prod
```
Access the application directly via your virtual host or local Apache setup: `http://localhost/LTPC_EMS/public`.

---

## 🔧 Developer Command Cheat Sheet

### Refresh & Seed Database
```bash
php artisan migrate:fresh --seed
```

### Optimize Config & Cache Clearing
Use these to flush caches after modifying configurations or language files:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
```

### Access Interactive Tinker Shell
```bash
php artisan tinker
```

---

## 📁 Directory Structure Overview
```
LTPC_EMS/
├── app/
│   ├── Http/
│   │   ├── Controllers/   # Multi-role controllers (Admin, Cashier, Officer)
│   │   └── Middleware/    # Dev-signature, role validation, throttles
│   └── Models/            # hard-scoped models (Trainee, Trainer, Payment)
├── bootstrap/             # Framework bootstrapper files
├── config/                # Platform configuration profiles
├── database/
│   ├── migrations/        # Production database schemas
│   └── seeders/           # Dynamic mock data generators
├── public/                # Web entrypoint and compiled production assets
├── resources/
│   ├── js/                # Inertia Page views (Vue 3 Components)
│   └── views/             # Master Blade layouts
└── routes/
    └── web.php            # Security-hardened system route declarations
```

---

## 📚 Seeded Authentication Credentials

After running `php artisan db:seed`, you can log in with:

| Role | Username / Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@ltpc.com` | `password` |
| **Training Officer** | `officer@ltpc.com` | `password` |
| **Cashier** | `cashier@ltpc.com` | `password` |

---

## 🆘 Troubleshooting & Diagnostic Logs

*   **Database Lockouts / Connection Issues:** Check that MySQL is running in your XAMPP controller panel and ensure the database credentials match your `.env` configuration.
*   **Vite Execution Issues:** Ensure you have run `npm install` and that your Node version is `^18.0` or higher.
*   **Error Inspection:** Full application runtime trace logs are recorded inside `storage/logs/laravel.log`.

---

**Happy Coding! 🚀**
