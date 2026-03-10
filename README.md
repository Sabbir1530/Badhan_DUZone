# Badhan DU Zone

Blood Requisition Management System for Badhan DU Zone.

This project helps volunteers record blood requisitions, track daily or monthly outcomes, manage members, maintain zone contact information, and export admin reports as PDF.

## Features

- Role-based authentication (`admin`, `member`)
- Dashboard with daily stats and blood-group-wise managed/total overview
- New requisition form with validation and CSRF protection
- Requisition list with date filtering (all users) and month filtering (admin)
- Member-side status update (`Managed`, `Referred`, `Others`) with `managed_by`
- Admin report page with summary stats and full data table
- PDF report export using Dompdf
- User management (admin): add member, change password, delete member
- Blood information page:
	- Blood components reference table
	- Blood group compatibility chart
	- Zone committee contact management (admin CRUD)

## Tech Stack

- PHP (PDO, session-based auth)
- MySQL / MariaDB
- HTML + CSS + vanilla JavaScript
- Composer
- [dompdf/dompdf](https://github.com/dompdf/dompdf)

## Project Structure

```text
Badhan_DUZone/
	assets/              # CSS and static assets
	config/              # Application and database configuration
	database/            # SQL schema and hash utility
	includes/            # Auth, DB, helper functions, templates
	vendor/              # Composer dependencies
	*.php                # Main pages and actions
```

## Requirements

- PHP 7.4+ (recommended PHP 8.1+)
- MySQL 5.7+ or MariaDB 10+
- Composer
- Web server (Apache/Nginx) or PHP built-in server for local testing

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/Sabbir1530/Badhan_DUZone.git
cd Badhan_DUZone
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create database and import schema

Use your MySQL client and run:

```bash
mysql -u root -p < database/schema.sql
```

This creates:

- `badhan_duzone` database
- `users`, `requisitions`, `zone_contacts` tables
- Seed users and zone contact data

### 4. Configure application

Open `config/config.php` and update values if needed:

- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `BASE_URL`

Default `BASE_URL` is:

```php
define('BASE_URL', '/Badhan_DUZone/');
```

If you run the project from another path (or domain root), update `BASE_URL` accordingly.

### 5. Run the project

Option A (Apache/Nginx):

- Place the project in your web root
- Visit the app URL in browser

Option B (PHP built-in server):

```bash
php -S localhost:8000
```

Then open:

- `http://localhost:8000/login.php`

## Default Login Credentials

Seeded users from `database/schema.sql`:

- Admin:
	- Username: `admin`
	- Password: `admin123`
- Member:
	- Username: `member`
	- Password: `member123`

Change these passwords immediately in production.

## User Roles and Permissions

### Admin

- View all requisitions
- Filter by day or month
- Access reports and export PDF
- Manage users (members)
- Edit/add/delete zone committee contacts

### Member

- Create requisitions
- View own requisitions by day
- Update own requisition status/comment (`Managed`, `Referred`, `Others`)
- Access blood info page

## Main Routes

- `index.php` -> redirects to login/dashboard
- `login.php` -> login page
- `dashboard.php` -> role-aware dashboard
- `requisition_form.php` -> new requisition form
- `requisition_list.php` -> requisition table and updates
- `admin_report.php` -> admin reporting UI
- `pdf_export.php` -> PDF generation endpoint (admin only)
- `manage_users.php` -> admin user management
- `blood_info.php` -> blood info and zone contacts
- `update_blood_info.php` -> admin zone contact CRUD actions
- `logout.php` -> logout

## Security Notes

- Prepared statements via PDO are used for DB queries
- CSRF token validation is used on sensitive POST actions
- Passwords are hashed using `password_hash()`
- Output escaping with `htmlspecialchars()` is used in views

## Generate Password Hashes (Optional Utility)

To generate hashes for new seeded passwords:

```bash
php database/generate_hash.php
```

## Troubleshooting

- Blank page or DB error:
	- Verify DB credentials in `config/config.php`
	- Ensure `badhan_duzone` database exists and schema is imported
- Login redirect issues:
	- Verify `BASE_URL` in `config/config.php`
- PDF export not working:
	- Run `composer install`
	- Confirm `vendor/autoload.php` exists
- Permission/session issues:
	- Ensure PHP sessions are enabled
	- Ensure web server can write session files

## Production Recommendations

- Use HTTPS
- Set strong database credentials
- Change default seeded passwords
- Restrict server access to sensitive files/folders
- Add regular database backups

## License

No license file is currently included in this repository. Add a license if you plan to distribute or open-source this project.
