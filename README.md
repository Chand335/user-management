# User Management API

Production-Ready REST API with RBAC, Observability & Audit Logging

---

# ⚙️ Tech Stack

* Laravel 12
* MySQL
* Laravel Sanctum (API Authentication)
* spatie/laravel-permission (RBAC)
* Laravel Telescope (Monitoring)
* Database Queue Driver
* Insomnia (API testing)

---

# 🚀 Installation & Setup

## 1️⃣ Clone Repository

```bash
git clone https://github.com/Chand335/user-management.git
cd user-management
```

---




## 2️⃣ Install Dependencies

```bash
composer install
```

```bash
npm install
npm run build
```
---

## 3️⃣ Environment Setup

Copy environment file:

```bash
cp .env.example .env
```

Generate key:

```bash
php artisan key:generate
```

---

## 4️⃣ Configure Environment Variables

Update `.env`:

```env
APP_NAME="User Management API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=user_management
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 5️⃣ Run Migrations & Seeders

```bash
php artisan migrate
php artisan db:seed
or
php artisan migrate:fresh --seed
```

Seeder creates:

* 1 Admin user
* 1 Manager user
* Roles & permissions

### Default Users

Admin:

```
email: admin@example.com
password: Admin@123
```

Manager:

```
email: manager@example.com
password: Manager@123
```

---

## 6️⃣ Queue Setup

Create queue tables:

```bash
php artisan queue:table
php artisan migrate
```

Start queue worker:

```bash
php artisan queue:work
```

Required for:

* Welcome Email
* Delayed Reset Password Email

---

# 🔐 Authentication

Uses Laravel Sanctum.

### Login

`POST /api/login`

Returns access token.

Use token in header:

```
Authorization: Bearer {token}
```

---

### Logout

`POST /api/logout`

Revokes current token.

---

# 👥 User Management Endpoints

All routes require authentication.

---

## GET /api/users

Supports:

* page
* per_page
* search (name/email)
* sort_by
* sort_dir

Returns paginated response with meta.

---

## POST /api/users

Creates user.

* Validates request
* Hashes password
* Assigns roles
* Sends Welcome Email (queued)
* Creates audit log

---

## POST /api/users/(id)

Updates user by ID.

* Supports role updates
* Logs changed attributes
* Uses conditional validation

---

## Delete /api/users/(id)

Soft deletes user.

Safeguard:

* Prevents self-deletion

---

## POST /api/users/reset-password

* Generates secure token
* Queues email
* Delayed by 45 seconds
* Logs reset request

---

# 🛡 RBAC Implementation

Using spatie/laravel-permission.

### Roles

* admin
* manager

### Permission

* manage_users

### Enforcement

* Admin: Full access
* Manager: Restricted
* Manager accessing admin-only route returns 403

---

# 📋 Validation Strategy

Uses Form Requests.

Validation includes:

* required
* exists
* unique
* min
* max
* confirmed
* nullable
* sometimes
* conditional rules

### Structured Validation Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

# 📦 API Response Format

### Success

```json
{
  "success": true,
  "message": "User created successfully",
  "data": {}
}
```

### Error

```json
{
  "success": false,
  "message": "Forbidden",
  "error_code": 403
}
```

Proper HTTP status codes are used.

---

# 📑 Audit Logging System

Audit logs stored in `audit_logs` table.

Fields:

* actor_user_id
* action
* target_user_id
* payload_diff (JSON)
* ip_address
* user_agent
* created_at

Tracked actions:

* created_user
* updated_user
* deleted_user
* reset_password_requested

Implemented via:

* UserObserver
* Central AuditLogService

---

# 📬 Email System

## Welcome Email

* Queued
* Professional Blade template
* Includes:

  * Header
  * User details
  * CTA button
  * Footer

---

## Reset Password Email

* Queued
* Delayed (30 seconds)
* Secure token included

---

# 🔍 Laravel Telescope

Installed in dev mode.

Start server:

```bash
php artisan serve
or
composer dev
```

Access:

```
/telescope
```

Monitors:

* Requests
* Database queries
* Queued jobs
* Mail
* Exceptions

### Production Restriction

Telescope access restricted to:

```php
app()->environment('local')
```

---

# 🔒 Security Practices

* Passwords hashed (bcrypt)
* Sensitive fields hidden
* Sanctum token-based auth
* Soft deletes
* Structured error responses
* Rate limiting

---

---


