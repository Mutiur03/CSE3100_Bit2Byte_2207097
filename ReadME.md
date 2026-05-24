# Bit2Byte

Bit2Byte is a PHP and MySQL club website with an admin panel for managing member applications, events, projects, and committee members.

## Requirements

- XAMPP with Apache, MySQL, and PHP
- phpMyAdmin or MySQL CLI
- Node.js only if you want browser-sync hot reload

## Fresh Install

1. Place the project in:

```text
C:\xampp\htdocs\Bit2Byte
```

2. Copy `.env.example` to `.env`.

3. Update `.env`:

```env
DB_HOST=localhost
DB_NAME=bit2byte
DB_USER=root
DB_PASS=

ADMIN_DEFAULT_NAME=Admin Name
ADMIN_DEFAULT_EMAIL=admin@example.com
ADMIN_DEFAULT_PASSWORD=change-this-password
```

4. Start Apache and MySQL from XAMPP.

5. Create the database and tables by running `schema.sql` in phpMyAdmin.

Open phpMyAdmin, go to the SQL tab, paste the contents of `schema.sql`, then run it.

## Seed Starter Data

After tables are created, seed the first admin and sample content:

```powershell
C:\xampp\php\php.exe seed-data.php
```

The seed command adds data only when the target table is empty:

- admin account from `.env`
- sample events
- sample projects
- sample committee members

## Optional Automatic Setup

Manual setup is recommended. For local development only, `index.php` includes a commented setup block:

```php
require_once __DIR__ . '/setup-data.php';
run_schema_file($pdo);
seed_all_data($pdo);
```

Uncomment it, load the homepage once, then comment it again. Do not leave it enabled during normal use.

## Run The Website

Public site:

```text
http://localhost/Bit2Byte/
```

Admin login:

```text
http://localhost/Bit2Byte/login.php
```

Use the admin email and password from `.env`.

## Hot Reload

Install dependencies:

```powershell
npm install
```

Run browser-sync:

```powershell
npm run dev
```

Then open the browser-sync URL, usually:

```text
http://localhost:3000/Bit2Byte/
```

## Uploads

Uploaded images are stored in:

```text
uploads/members
uploads/committee
```

The `uploads/.htaccess` file disables PHP execution inside uploads for safety.

## Main Files

- `index.php` - public homepage
- `login.php` - admin login
- `admin-dashboard.php` - admin panel UI
- `admin-content.php` - event, project, and committee create/update/delete handler
- `member-status.php` - approve or reject member applications
- `signup.php` - member registration handler
- `schema.sql` - database tables
- `setup-data.php` - setup and seed helper functions
- `seed-data.php` - command-line seed runner
- `content-data.php` - shared content queries and escaping helper
- `db.php` - environment loading and database connection

## Notes

- Normal page loads should not create tables or seed data.
- Keep `.env` private and do not commit it.
- If admin login still works after changing tables, clear session by logging out or deleting localhost cookies.

