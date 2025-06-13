# LTPC - Learning and Training Programs Center

A modern web application for managing training programs, trainees, trainers, and assessments built with Laravel and Vue.js.

## 🚀 Features

-   **Trainee Management**: Register and manage trainee information with comprehensive forms
-   **Trainer Management**: Add and manage trainer profiles and qualifications
-   **Course Management**: Create and organize training courses and programs
-   **Assessment Management**: Handle assessments and evaluations
-   **Dashboard**: Overview of all activities and statistics
-   **Modern UI**: Clean, responsive interface built with Vue.js and Tailwind CSS

## 📋 Requirements

Before you start, make sure you have these installed on your computer:

-   **XAMPP** (includes PHP and MySQL) - [Download here](https://www.apachefriends.org/download.html)
-   **Composer** (PHP package manager) - [Download here](https://getcomposer.org/download/)
-   **Node.js** (includes NPM) - [Download here](https://nodejs.org/en/download/)
-   **Git** (version control) - [Download here](https://git-scm.com/downloads)

## 🛠️ Installation Guide

### Step 1: Start XAMPP

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Click **Admin** next to MySQL to open phpMyAdmin

### Step 2: Create Database

1. In phpMyAdmin, click **New** in the left sidebar
2. Create a new database named `ltpc_database`
3. Click **Create**

### Step 3: Download the Project

1. Open Command Prompt or PowerShell
2. Navigate to your XAMPP htdocs folder:
    ```bash
    cd C:\xampp\htdocs
    ```
3. Clone or download this project (if you haven't already)

### Step 4: Install Dependencies

1. Navigate to the project folder:
    ```bash
    cd LTPC
    ```
2. Install PHP dependencies:
    ```bash
    composer install
    ```
3. Install JavaScript dependencies:
    ```bash
    npm install
    ```

### Step 5: Configure Environment

1. Copy the environment file:
    ```bash
    copy .env.example .env
    ```
2. Generate application key:
    ```bash
    php artisan key:generate
    ```
3. Open `.env` file in a text editor and update database settings:
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=ltpc_database
    DB_USERNAME=root
    DB_PASSWORD=
    ```

### Step 6: Set Up Database

1. Run database migrations:
    ```bash
    php artisan migrate
    ```
2. (Optional) Seed with sample data:
    ```bash
    php artisan db:seed
    ```

### Step 7: Build Frontend Assets

```bash
npm run build
```

### Step 8: Start the Application

```bash
php artisan serve
```

🎉 **Your application is now running at:** `http://localhost:8000`

## 🔧 Development Commands

### For Development (with hot reload):

```bash
npm run dev
```

Then in another terminal:

```bash
php artisan serve
```

### Clear Application Cache:

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Reset Database:

```bash
php artisan migrate:fresh --seed
```

## 📁 Project Structure

```
LTPC/
├── app/                    # Laravel application logic
│   ├── Http/Controllers/   # Controllers for handling requests
│   └── Models/            # Database models
├── database/
│   └── migrations/        # Database structure files
├── resources/
│   ├── js/               # Vue.js components and pages
│   └── views/            # Blade templates
├── routes/               # Application routes
└── public/              # Public assets
```

## 🔍 Troubleshooting

### Common Issues:

**1. "Composer not found"**

-   Make sure Composer is installed and added to your system PATH
-   Restart your terminal after installation

**2. "npm not found"**

-   Make sure Node.js is installed
-   Restart your terminal after installation

**3. "Database connection failed"**

-   Make sure MySQL is running in XAMPP
-   Check your `.env` database settings
-   Ensure the database `ltpc_database` exists

**4. "Permission denied"**

-   On Windows, run Command Prompt as Administrator
-   Make sure you have write permissions in the htdocs folder

**5. "Application key not set"**

-   Run: `php artisan key:generate`

**6. "Mix manifest not found"**

-   Run: `npm run build` or `npm run dev`

### Still having issues?

1. Check if all services in XAMPP are running (Apache and MySQL should be green)
2. Make sure you're in the correct directory (`C:\xampp\htdocs\LTPC`)
3. Try clearing cache with: `php artisan cache:clear`

## 📚 Default Login Credentials

After seeding the database, you can use these credentials:

-   **Email**: admin@ltpc.com
-   **Password**: password

## 🆘 Getting Help

If you encounter any issues:

1. Check the troubleshooting section above
2. Look at the Laravel logs in `storage/logs/laravel.log`
3. Make sure all requirements are properly installed
4. Verify that XAMPP services are running

---

**Happy Coding! 🚀**
