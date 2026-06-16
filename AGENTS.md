# AGENTS.md

## Cursor Cloud specific instructions

### Services

| Service | Port | Start command |
|---------|------|---------------|
| PHP + Apache (web) | 8080 | `docker compose up -d --build` |
| MySQL 8 | 3306 | Included in `docker compose up` |

See `README.md` for full setup (scraper, import, cron).

### First-time catalog data

Fast path without scraping:

```bash
unzip -qo database/site_assets_bundle.zip -d public/assets/
docker compose exec -T mysql mysql -u donatov -pdonatov_secret donatov < database/catalog_seed.sql
```

Full path: scrape with `python3 scraper/scrape.py`, then `docker compose exec web php import/import_to_mysql.php`.

### Writable directories

The app writes runtime files to `data/` (crypto rate cache, scraper output) and `storage/` (install lock). The Docker entrypoint (`docker-entrypoint.sh`) creates these on container start. If running PHP outside Docker, create them manually: `mkdir -p data storage`.

### Lint / tests

No automated test suite or linter is configured. PHP syntax check:

```bash
docker compose exec web bash -c 'find /var/www/html -name "*.php" | while read f; do php -l "$f" || exit 1; done'
```

### Admin

- URL: `http://localhost:8080/admin`
- Password: value of `ADMIN_PASSWORD` in `.env` (default `changeme`)

### Product URLs

Routes use `/g/{slug}` (not `/good/{slug}`).

### Game guides

- Public URLs: `/guides` (index), `/guide/{good-slug}` (hub), `/guide/{good-slug}/{article-slug}` (article)
- Admin: `/admin` → **Game guides** — Quill WYSIWYG editors (no HTML), multiple articles per game via **+ New article**
- Migrations: `game_guides_migration.sql`, then `game_guides_multi_article.sql` on existing DBs
- DB table: `game_guides` (migration: `database/game_guides_migration.sql`)
- Language rule: one filled language → shown to everyone; both filled → visitor browser/session language
- New PHP classes must be `require_once` in `public/index.php` (no Composer autoload)
