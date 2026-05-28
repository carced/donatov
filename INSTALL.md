# GameWiwi.com — installation

## Quick install (shared hosting / public_html)

1. Upload **all project files** to your hosting (e.g. entire folder into `public_html`).
2. Create a MySQL database and user in cPanel (or phpMyAdmin).
3. Open in browser: `https://gamewiwi.com/install.php`
4. Fill in database details and site URL (`https://gamewiwi.com`).
5. Click **Install now** (check import catalog if `data/catalog.json` is present).
6. **Delete** `install.php` and `public/install.php` after success.

### Document root options

**Option A (recommended):** Set domain document root to the `public/` folder.

**Option B (flat upload):** Upload everything into `public_html` so you have:

```
public_html/
  index.php
  install.php
  .htaccess
  assets/
  src/
  templates/
  lang/
  database/
  ...
```

The included root `index.php` boots the app from either layout.

## Requirements

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Extensions: `pdo`, `pdo_mysql`, `json`
- Writable `storage/` and `.env`

## After install

- Admin orders: `/admin` (password from installer)
- Import catalog manually (SSH): `php import/import_to_mysql.php`
- Set `APP_URL=https://gamewiwi.com` in `.env` if you change domain

## Configuration

Copy `.env.example` to `.env` or use the web installer. Key values:

```
APP_NAME=GameWiwi.com
APP_URL=https://gamewiwi.com
```
