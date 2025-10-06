# LTPC System Build Sequence Guide

## From Migrations to Complete System

Since you already have your environment set up (XAMPP, Composer, Node.js), this guide provides the exact sequence to recreate the LTPC system starting from database migrations.

---

## 🗂️ Database Tables Overview

The LTPC system consists of these core tables:

1. **users** - Authentication and user management
2. **trainers** - Instructor profiles and information
3. **programs** (formerly courses) - Training course definitions
4. **trainees** - Student registration and personal info
5. **trainee_enrollments** - Enrollment records linking trainees to programs
6. **assessments** - Evaluation and testing records
7. **trainings** - Training session management
8. **training_results** - Training outcome tracking
9. **employments** - Graduate employment tracking
10. **custom_receipts** - Payment and billing records

---

## 🚀 Step-by-Step Build Sequence

### Phase 1: Core Laravel Setup

1. Create new Laravel project
2. Install core dependencies (Inertia.js, Ziggy, Sanctum, Breeze)
3. Set up authentication with Inertia.js and Vue
4. Configure environment file
5. Generate application key
6. Create `resources/js/Layouts/AuthenticatedLayout.vue` file
7. Create `resources/js/Layouts/GuestLayout.vue` file
8. Update `vite.config.js` file
9. Update `resources/js/app.js` file

### Phase 2: User Management System

#### Creating User Sequence:

-   Create migration `database/migrations/xxxx_modify_users_table_for_ltpc.php`
-   Update model `app/Models/User.php`
-   Create controller `app/Http/Controllers/UserController.php`
-   Create frontend files:
    1. `resources/js/Pages/Users/Index.vue`
    2. `resources/js/Pages/Users/Create.vue`
    3. `resources/js/Pages/Users/Edit.vue`
-   Configure routes in `routes/web.php`
-   Create seeder `database/seeders/UserSeeder.php`

### Phase 3: Trainer Management System

#### Creating Trainer Sequence:

-   Create migration `database/migrations/xxxx_create_trainers_table.php`
-   Create model `app/Models/Trainer.php`
-   Create controller `app/Http/Controllers/TrainerController.php`
-   Create frontend files:
    1. `resources/js/Pages/Trainers/Index.vue`
    2. `resources/js/Pages/Trainers/Create.vue`
    3. `resources/js/Pages/Trainers/Edit.vue`
    4. `resources/js/Components/Forms/TrainerForm.vue`
    5. `resources/js/Components/Tables/TrainerTable.vue`
-   Configure routes in `routes/web.php`
-   Create seeder `database/seeders/TrainerSeeder.php`

### Phase 4: Program Management System

#### Creating Program Sequence:

-   Create migration `database/migrations/xxxx_create_programs_table.php`
-   Create model `app/Models/Program.php`
-   Create controller `app/Http/Controllers/ProgramController.php`
-   Create frontend files:
    1. `resources/js/Pages/Programs/Index.vue`
    2. `resources/js/Pages/Programs/Create.vue`
    3. `resources/js/Pages/Programs/Edit.vue`
    4. `resources/js/Components/Forms/ProgramForm.vue`
    5. `resources/js/Components/Tables/ProgramTable.vue`
-   Configure routes in `routes/web.php`
-   Create seeder `database/seeders/ProgramSeeder.php`

### Phase 5: Trainee Management System

#### Creating Trainee Sequence:

-   Create migration `database/migrations/xxxx_create_trainees_table.php`
-   Create model `app/Models/Trainee.php`
-   Create controller `app/Http/Controllers/TraineeController.php`
-   Create frontend files:
    1. `resources/js/Pages/Trainees/Index.vue`
    2. `resources/js/Pages/Trainees/Create.vue`
    3. `resources/js/Pages/Trainees/Edit.vue`
    4. `resources/js/Components/Forms/TraineeForm.vue`
    5. `resources/js/Components/Tables/TraineeTable.vue`
-   Configure routes in `routes/web.php`

### Phase 6: Enrollment Management System

#### Creating Trainee Enrollment Sequence:

-   Create migration `database/migrations/xxxx_create_trainee_enrollments_table.php`
-   Create model `app/Models/TraineeEnrollment.php`
-   Create controller `app/Http/Controllers/TraineeEnrollmentController.php`
-   Create frontend files:
    1. `resources/js/Pages/Enrollments/Index.vue`
    2. `resources/js/Pages/Enrollments/Create.vue`
    3. `resources/js/Components/Modals/EnrollmentModal.vue`
-   Configure routes in `routes/web.php`

### Phase 7: Assessment Management System

#### Creating Assessment Sequence:

-   Create migration `database/migrations/xxxx_create_assessments_table.php`
-   Create model `app/Models/Assessment.php`
-   Create controller `app/Http/Controllers/AssessmentController.php`
-   Create frontend files:
    1. `resources/js/Pages/Assessments/Index.vue`
    2. `resources/js/Pages/Assessments/Create.vue`
    3. `resources/js/Pages/Assessments/Edit.vue`
    4. `resources/js/Components/Forms/AssessmentForm.vue`
    5. `resources/js/Components/Tables/AssessmentTable.vue`
-   Configure routes in `routes/web.php`

### Phase 8: Training Management System

#### Creating Training Sequence:

-   Create migration `database/migrations/xxxx_create_trainings_table.php`
-   Create model `app/Models/Training.php`
-   Create controller `app/Http/Controllers/TrainingController.php`
-   Create frontend files:
    1. `resources/js/Pages/Trainings/Index.vue`
    2. `resources/js/Pages/Trainings/Create.vue`
    3. `resources/js/Pages/Trainings/Edit.vue`
-   Configure routes in `routes/web.php`

### Phase 9: Training Results System

#### Creating Training Results Sequence:

-   Create migration `database/migrations/xxxx_create_training_results_table.php`
-   Create model `app/Models/TrainingResult.php`
-   Create controller `app/Http/Controllers/TrainingResultController.php`
-   Create frontend files:
    1. `resources/js/Pages/TrainingResults/Index.vue`
    2. `resources/js/Pages/TrainingResults/Create.vue`
-   Configure routes in `routes/web.php`

### Phase 10: Employment Tracking System

#### Creating Employment Sequence:

-   Create migration `database/migrations/xxxx_create_employments_table.php`
-   Create model `app/Models/Employment.php`
-   Create controller `app/Http/Controllers/EmploymentController.php`
-   Create frontend files:
    1. `resources/js/Pages/Employments/Index.vue`
    2. `resources/js/Pages/Employments/Create.vue`
    3. `resources/js/Pages/Employments/Edit.vue`
-   Configure routes in `routes/web.php`

### Phase 11: Receipt Management System

#### Creating Custom Receipt Sequence:

-   Create migration `database/migrations/xxxx_create_custom_receipts_table.php`
-   Create model `app/Models/CustomReceipt.php`
-   Create controller `app/Http/Controllers/CustomReceiptController.php`
-   Create frontend files:
    1. `resources/js/Pages/Receipts/Index.vue`
    2. `resources/js/Pages/Receipts/Create.vue`
    3. `resources/js/Pages/Receipts/Edit.vue`
-   Configure routes in `routes/web.php`

### Phase 12: Dashboard and Global Components

#### Creating Dashboard and Shared Components:

-   Create controller `app/Http/Controllers/DashboardController.php`
-   Create frontend files:
    1. `resources/js/Pages/Dashboard/Index.vue`
    2. `resources/js/Components/Modals/ConfirmDeleteModal.vue`
    3. `resources/js/Components/Charts/StatsChart.vue`
-   Configure dashboard routes in `routes/web.php`
-   Update `database/seeders/DatabaseSeeder.php`

---

## ✅ Final Setup Commands

After creating all files:

1. Run all migrations: `php artisan migrate`
2. Seed the database: `php artisan db:seed`
3. Install frontend dependencies: `npm install`
4. Build frontend assets: `npm run build`
5. Start the application: `php artisan serve`

---

## 🔧 File Creation Priority Order

**Critical Files (Must be created first):**

-   Migration files (1-10)
-   Model files (11-20)
-   Controllers (33-40)
-   Routes (41)
-   Seeders (42-45)

**Frontend Files (Create after backend is working):**

-   Layout files (21-22)
-   Page directories (23-29)
-   Component directories (30-32)
-   Configuration files (46-48)
-   Vue pages (49-67)
-   Vue components (68-77)

---

## 🎯 Commands for Each Phase

### Phase 1 Commands:

```bash
composer create-project laravel/laravel LTPC_EMS
cd LTPC_EMS
composer require inertiajs/inertia-laravel tightenco/ziggy laravel/sanctum
composer require --dev laravel/breeze
php artisan breeze:install vue --ssr
npm install && npm run build
copy .env.example .env
php artisan key:generate
```

### Phase 2 Commands:

```bash
php artisan make:migration modify_users_table_for_ltpc --table=users
php artisan make:model Trainer -m
php artisan make:model Program -m
php artisan make:model Trainee -m
php artisan make:model TraineeEnrollment -m
php artisan make:model Assessment -m
php artisan make:model Training -m
php artisan make:model TrainingResult -m
php artisan make:model Employment -m
php artisan make:model CustomReceipt -m
```

### Phase 7 Commands:

```bash
php artisan make:controller DashboardController
php artisan make:controller TraineeController --resource
php artisan make:controller TrainerController --resource
php artisan make:controller ProgramController --resource
php artisan make:controller AssessmentController --resource
php artisan make:controller TrainingController --resource
php artisan make:controller EmploymentController --resource
php artisan make:controller CustomReceiptController --resource
```

### Phase 9 Commands:

```bash
php artisan make:seeder UserSeeder
php artisan make:seeder TrainerSeeder
php artisan make:seeder ProgramSeeder
```

### Phase 10 Commands:

```bash
npm install @heroicons/vue exceljs file-saver jspdf jspdf-autotable vue-toastification xlsx
```

This sequence ensures you build the system in the correct order with proper dependencies established at each step.
