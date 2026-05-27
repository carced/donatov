# Donatov — PHP/MySQL bilingual storefront

A PHP + MySQL game top-up storefront built from public catalog data scraped from [donatov.net](https://donatov.net). Prices are shown in **USD** using the [Central Bank of Russia](https://www.cbr.ru/) daily USD rate, with a **40% discount** applied (`price_usd = (price_rub / usd_rub) * 0.6`).

Supports **Russian** and **English** (cached translations via optional API or built-in dictionary).

## Requirements

- Docker & Docker Compose, or PHP 8.2+ and MySQL 8+
- Python 3.10+ (for scraper)

## Quick start (Docker)

```bash
cp .env.example .env
docker compose up -d --build
# Wait for MySQL to be healthy, then scrape and import:
pip install -r scraper/requirements.txt
python3 scraper/scrape.py
docker compose exec web php import/import_to_mysql.php
```

Open [http://localhost:8080](http://localhost:8080)

## Manual setup

1. Create MySQL database and run `database/schema.sql`
2. Copy `.env.example` to `.env` and set DB credentials
3. Run scraper: `python3 scraper/scrape.py`
4. Import: `php import/import_to_mysql.php`
5. Point web server document root to `public/`

## Scraper

```bash
pip install -r scraper/requirements.txt
python3 scraper/scrape.py
```

Writes to `data/`:

- `catalog.json` — full catalog from `/good/list/json`
- `goods/{id}.json` — per-product packs, fields, content
- `paymethods.json`, `checkout.html`, `assets/css/`, `assets/covers/`
- `manifest.json` — scrape report

## Import

```bash
php import/import_to_mysql.php
```

- Fetches CBR USD/RUB rate
- Imports categories, goods, packs, checkout fields, payment methods
- Computes USD prices with 40% discount
- Generates English translations (see below)

## Translation

Set in `.env`:

| Variable | Description |
|----------|-------------|
| `TRANSLATION_PROVIDER` | `none` (dictionary fallback), `libretranslate`, `deepl`, `google` |
| `TRANSLATION_API_KEY` | API key when required |
| `LIBRETRANSLATE_URL` | LibreTranslate base URL |

With `none`, common UI strings use `lang/dict_en.json`; product names fall back to Russian until an API is configured.

## Cron

```bash
# Daily FX + price recalculation
php cron/refresh_rates.php

# Full re-scrape and import (weekly)
php cron/refresh_catalog.php
```

## Admin

- URL: `/admin`
- Password: `ADMIN_PASSWORD` in `.env` (default `changeme`)

## Project structure

```
scraper/          Python scraper
data/             Scraped JSON & assets (gitignored)
database/         MySQL schema
import/           JSON → MySQL import
src/              PHP application code
templates/        Views
public/           Web root
cron/             Maintenance scripts
lang/             UI strings (ru/en)
```

## Legal notice

Only use scraped content if you have the right to do so. This project is for authorized replication of catalog/layout data. Game logos and third-party trademarks belong to their owners.

## License

See repository license. Source catalog data is owned by the original site operator.


## Referral program

Users open `/referral` to get an instant personal link (no sign-up). Accounts are tied to the browser via a secure cookie, get a personal link (`/r/YOURCODE`), and earn **$0.10** per unique visitor per day when someone opens that link.

- Balance is shown on `/referral/dashboard`
- On a product page, logged-in users with enough balance can check **Pay with referral balance** to complete the order instantly (no crypto)

Apply DB changes on existing installs:

```bash
mysql donatov < database/referral_migration.sql
```

Fresh installs include referral tables in `database/schema.sql`.


### Anonymous referrals

No email or password. Visiting `/referral` creates a referral code automatically. A signed cookie keeps your balance across sessions on the same browser.

```bash
mysql donatov < database/referral_anonymous_migration.sql
```
