# SSMS — Running on XAMPP (Windows)

Society & Staff Management System · Laravel 12 · PHP 8.2+ · MySQL/MariaDB · Blade + Tailwind + Alpine

---

## 0. What you need installed

| Tool | Version | Check with | Notes |
|---|---|---|---|
| PHP | **8.2 or higher** | `php -v` | XAMPP 8.2.x / 8.3.x are fine. XAMPP 8.1 or lower **will not work**. |
| Composer | 2.x | `composer -V` | Download from getcomposer.org |
| Node.js | 18+ | `node -v` | **Optional** — pre-built assets are already included |
| MySQL/MariaDB | running | XAMPP Control Panel | You already have this started |

**Required PHP extensions** (all ship with XAMPP, just confirm they're uncommented in `php.ini`):

```
pdo_mysql, mbstring, openssl, fileinfo, gd, curl, zip, tokenizer, xml, ctype, json, bcmath
```

`fileinfo` and `gd` are the two that are commonly left commented out in XAMPP — the file upload
module needs both. Open `C:\xampp\php\php.ini`, search for `;extension=fileinfo`, remove the
leading `;`, do the same for `gd`, then restart Apache.

---

## 1. Put the project somewhere

You do **not** have to put it in `htdocs`. The simplest route uses PHP's built-in server:

```
C:\xampp\htdocs\ssms\        <- or anywhere, e.g. D:\projects\ssms
```

Copy the whole `turbo-umbrella-dev1` folder there and rename it if you like.

---

## 2. Create the database

Open <http://localhost/phpmyadmin> → **New** → database name `ssms` → collation
`utf8mb4_unicode_ci` → **Create**.

Or from the terminal:

```bash
C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE ssms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## 3. Install PHP dependencies

From inside the project folder:

```bash
composer install
```

This creates the `vendor/` folder. It takes 1–3 minutes the first time.

---

## 4. Generate the app key

A `.env` file is already included and pre-filled for XAMPP. You only need to generate the key:

```bash
php artisan key:generate
```

If your MySQL root user has a password, open `.env` and set `DB_PASSWORD=yourpassword` before
continuing. (Default XAMPP has no root password, which is what the file assumes.)

---

## 5. Run migrations and seed the data

```bash
php artisan migrate --seed
```

This creates all 25+ tables and fills them with demo data. You should see a summary like:

```
=== DEMO DATA SUMMARY ===
  Towers                 3
  Flats                  24
  Users                  10
  Complaints             8
  Complaint comments     11
  Notices                6
  Maintenance bills      72
  Maintenance payments   ~45
  Visitors               8
  Vehicles               8
  Amenities              6
  Amenity bookings       6
```

If you ever want to wipe and start over:

```bash
php artisan migrate:fresh --seed
```

---

## 6. Link storage and build assets

```bash
php artisan storage:link
```

Front-end assets are **already built** into `public/build/`, so you can skip npm entirely.
If you want to rebuild or develop the CSS/JS:

```bash
npm ci
npm run build          # production build
npm run dev            # live reload while developing
```

---

## 7. Start the app

```bash
php artisan serve
```

Open <http://localhost:8000>

---

## 8. Log in

| Role | Email | Password |
|---|---|---|
| Super Admin | `admin@ssms.local` | `Admin@123` |
| Society Admin | `societyadmin@ssms.local` | `Admin@123` |
| Resident | `john@ssms.local` | `Resident@123` |
| Resident | `jane@ssms.local` | `Resident@123` |
| Resident | `robert@ssms.local` | `Resident@123` |
| Resident | `emily@ssms.local` | `Resident@123` |
| Resident | `michael@ssms.local` | `Resident@123` |
| Staff | `ramesh@ssms.local` | `Staff@123` |
| Staff | `suresh@ssms.local` | `Staff@123` |

Each role sees a completely different dashboard and sidebar — log in as all four to see the
whole system.

---

## Quick copy-paste version

```bash
cd C:\xampp\htdocs\ssms
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

---

## Alternative: serving through Apache instead of `php artisan serve`

Only do this if you specifically want Apache. Add to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName ssms.test
    DocumentRoot "C:/xampp/htdocs/ssms/public"
    <Directory "C:/xampp/htdocs/ssms/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add `127.0.0.1 ssms.test` to `C:\Windows\System32\drivers\etc\hosts`, make sure
`LoadModule rewrite_module` is uncommented in `httpd.conf`, set `APP_URL=http://ssms.test`
in `.env`, restart Apache, then visit <http://ssms.test>.

**Do not** point Apache at the project root and browse to `localhost/ssms/public` — the
document root must be the `public` folder, otherwise your `.env` is exposed to the web.

---

## Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| `SQLSTATE[HY000] [1049] Unknown database 'ssms'` | Database not created | Step 2 |
| `SQLSTATE[HY000] [2002] No connection could be made` | MySQL not started | Start MySQL in XAMPP Control Panel |
| `SQLSTATE[HY000] [1045] Access denied for user 'root'` | Root has a password | Set `DB_PASSWORD=` in `.env` |
| `Vite manifest not found` | `public/build` missing | It's included — if deleted, run `npm ci && npm run build` |
| `No application encryption key has been specified` | Key not generated | `php artisan key:generate` |
| `Class "finfo" not found` / upload fails | `fileinfo` extension off | Uncomment `extension=fileinfo` in `php.ini`, restart Apache |
| `failed to open stream: Permission denied` on `storage/` | Folder permissions | Give your user write access to `storage/` and `bootstrap/cache/` |
| Changes to `.env` seem ignored | Config cached | `php artisan config:clear` |
| `Target class [x] does not exist` | Autoloader stale | `composer dump-autoload` |
| Email verification blocks login | Mail driver is `log` | Seeded users are pre-verified. For new signups, find the link in `storage/logs/laravel.log` |

**Reset button** — if the app gets into a weird state:

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
```

---

## Changes made to the original code

Five fixes were applied to get this running cleanly:

1. **`routes/web.php`** — `DELETE /notifications/clear-read` was registered *after* the
   `DELETE /notifications/{id}` wildcard, so the "Clear read notifications" button was
   resolving to `destroy('clear-read')` and always failing. Moved above the wildcard.

2. **`config/filesystems.php`** — the `ssms` disk root was `storage/app/ssms` while its public
   URL was `/storage/ssms`. Since `storage:link` only links `public/storage → storage/app/public`,
   every uploaded file URL returned 404. Root moved to `storage/app/public/ssms`.

3. **`.env`** — created and pre-configured. Critically `FILESYSTEM_DISK=ssms`, not `local`.
   `.env.example` ships with `local`, which would have silently routed all uploads to
   `storage/app/private` and broken the whole file module.

4. **`RoleSeeder` / `PermissionSeeder` / `SuperAdminSeeder`** — used `create()` against columns
   with unique indexes, so running `php artisan db:seed` a second time crashed with a duplicate
   key error. Switched to `updateOrCreate()` so seeding is now repeatable.

5. **`database/seeders/DemoDataSeeder.php`** — new file. Populates every module with realistic
   data (see below).

Also note: the README says `php artisan seed`. That command does not exist — it is
`php artisan db:seed`.

---

## What the demo seeder creates

`database/seeders/DemoDataSeeder.php` — runs automatically as part of `migrate --seed`, or
on its own with `php artisan db:seed --class=DemoDataSeeder`.

- **3 towers, 24 flats** across 1BHK / 2BHK / 3BHK, one deliberately vacant
- **8 complaints** covering every status in the lifecycle — `open`, `in_progress`, `resolved`,
  `disputed`, `closed` — with an 11-message comment thread including internal-only staff notes,
  so the dispute/reopen workflow is visible without you having to create it
- **72 maintenance bills** over 3 months with a policy template (flat-type based, 2% late fee,
  5 grace days, partial payment allowed) and a mix of `paid` / `partially_paid` / `unpaid` /
  `overdue`, plus matching payment records
- **6 notices** across draft, published and archived, two pinned, spanning all five categories
- **8 visitors** in approved / pending / rejected states, some with exit times recorded
- **8 vehicles** and **6 amenities** with 6 bookings in pending / confirmed / cancelled / completed

The visitor records respect the CHECK constraints that migration
`2026_05_06_130000_add_visitor_transition_constraints.php` adds on MySQL/MariaDB — pending
visitors must have a null approver, and only approved visitors may carry an exit time. Keep that
in mind if you add your own visitor rows by hand.

---

## Scan results

- **271 PHP files** — zero syntax errors
- **107 route names** referenced across controllers and Blade — all resolve
- **83 Blade views** — every `view()`, `@extends` and `@include` target exists
- **116 App classes** — every `use` statement resolves to a file on disk
- **Vite build** — succeeds, 54 modules, 65 KB CSS + 84 KB JS

The codebase is in good shape. The issues found were configuration and route-ordering, not
broken logic.

### Things worth knowing, not blocking

- `package.json` lists both `tailwindcss@^3.1.0` and `@tailwindcss/vite@^4.0.0`. The v4 plugin
  is never imported in `vite.config.js`, so it's dead weight — the build uses Tailwind 3 through
  `postcss.config.js`. It builds fine via `npm ci`. If plain `npm install` ever throws an
  `ERESOLVE` peer conflict, either drop `@tailwindcss/vite` from `devDependencies` or use
  `npm install --legacy-peer-deps`.
- 17 migration files end in `.php.php`. Laravel still loads them, but it's worth renaming for
  tidiness. Rename the files **and** the matching rows in the `migrations` table together,
  or those migrations will re-run.
- `maintenances` ends up with two identical unique indexes on `(flat_id, month)` — one from the
  create migration, one from `add_tenant_unique_constraints`. Harmless on MySQL, just redundant.
- `QUEUE_CONNECTION` is set to `sync` so notifications fire immediately and you don't need a
  worker process. Switch to `database` and run `php artisan queue:work` if you want them async.
- The two scheduled commands (`maintenance:generate` monthly, `maintenance:apply-late-fees`
  daily) need `php artisan schedule:work` running to fire on their own. You can always trigger
  them manually for testing.
