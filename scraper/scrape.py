#!/usr/bin/env python3
"""Scrape public catalog, products, assets, and checkout metadata from donatov.net."""

from __future__ import annotations

import html
import json
import re
import sys
import time
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urljoin, urlparse

import requests

BASE_URL = "https://donatov.net"
ROOT = Path(__file__).resolve().parent.parent
DATA_DIR = ROOT / "data"
GOODS_DIR = DATA_DIR / "goods"
ASSETS_DIR = DATA_DIR / "assets"
COVERS_DIR = ASSETS_DIR / "covers"
CSS_DIR = ASSETS_DIR / "css"

USER_AGENT = (
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)
DELAY_SEC = 0.4
MAX_RETRIES = 3

RESOURCE_RE = re.compile(r':resource="([^"]+)"', re.DOTALL)
CSS_RE = re.compile(r'href="(/css/[^"]+\.css[^"]*)"')


def session() -> requests.Session:
    s = requests.Session()
    s.headers.update({"User-Agent": USER_AGENT, "Accept-Language": "ru-RU,ru;q=0.9"})
    return s


def fetch(sess: requests.Session, url: str) -> requests.Response:
    last_err: Exception | None = None
    for attempt in range(MAX_RETRIES):
        try:
            resp = sess.get(url, timeout=60)
            resp.raise_for_status()
            return resp
        except Exception as e:
            last_err = e
            time.sleep(1.5 * (attempt + 1))
    raise RuntimeError(f"Failed to fetch {url}: {last_err}")


def parse_resource(html_text: str) -> dict | None:
    m = RESOURCE_RE.search(html_text)
    if not m:
        return None
    return json.loads(html.unescape(m.group(1)))


def download_file(sess: requests.Session, url: str, dest: Path) -> bool:
    if dest.exists() and dest.stat().st_size > 0:
        return True
    try:
        dest.parent.mkdir(parents=True, exist_ok=True)
        r = sess.get(url, timeout=60)
        r.raise_for_status()
        dest.write_bytes(r.content)
        return True
    except Exception as e:
        print(f"  WARN download {url}: {e}", file=sys.stderr)
        return False


def cover_filename(url: str) -> str:
    path = urlparse(url).path
    name = path.split("/")[-1] or "cover.bin"
    return re.sub(r"[^\w.\-]", "_", name)


def scrape_catalog(sess: requests.Session) -> dict:
    print("Fetching catalog...")
    resp = fetch(sess, f"{BASE_URL}/good/list/json")
    data = resp.json()
    (DATA_DIR / "catalog.json").write_text(
        json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    return data


def scrape_paymethods(sess: requests.Session) -> dict:
    print("Fetching payment methods...")
    resp = fetch(sess, f"{BASE_URL}/paymethods/json")
    data = resp.json()
    (DATA_DIR / "paymethods.json").write_text(
        json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    return data


def scrape_checkout_html(sess: requests.Session) -> None:
    print("Fetching checkout page...")
    resp = fetch(sess, f"{BASE_URL}/order/checkout")
    (DATA_DIR / "checkout.html").write_text(resp.text, encoding="utf-8")


def scrape_homepage_assets(sess: requests.Session) -> list[str]:
    print("Fetching homepage for CSS...")
    resp = fetch(sess, BASE_URL + "/")
    css_paths = CSS_RE.findall(resp.text)
    downloaded: list[str] = []
    for path in css_paths:
        url = urljoin(BASE_URL, path.split("?")[0] + ("?" + path.split("?", 1)[1] if "?" in path else ""))
        if "?" in path:
            url = urljoin(BASE_URL, path)
        else:
            url = urljoin(BASE_URL, path)
        fname = path.split("/")[-1].replace("?", "_")
        dest = CSS_DIR / fname
        if download_file(sess, urljoin(BASE_URL, path), dest):
            downloaded.append(str(dest.relative_to(ROOT)))
        time.sleep(0.2)
    return downloaded


def collect_cover_urls(catalog: dict, resource: dict | None) -> set[str]:
    urls: set[str] = set()
    for item in catalog.get("data", {}).get("catalog", []):
        if item.get("cover"):
            urls.add(item["cover"])
        cur = item.get("cur") or {}
        if cur.get("cover"):
            urls.add(cur["cover"])
    if resource:
        good = resource.get("good") or {}
        if good.get("cover"):
            urls.add(good["cover"])
        for pack in resource.get("packs") or []:
            if pack.get("cover"):
                urls.add(pack["cover"])
    return urls


def scrape_goods(sess: requests.Session, catalog: dict) -> tuple[list[dict], list[dict]]:
    items = catalog.get("data", {}).get("catalog", [])
    GOODS_DIR.mkdir(parents=True, exist_ok=True)
    ok: list[dict] = []
    errors: list[dict] = []
    all_covers: set[str] = set()

    print(f"Scraping {len(items)} products...")
    for i, item in enumerate(items, 1):
        gid = item["id"]
        url_path = item.get("url", "")
        full_url = urljoin(BASE_URL, url_path)
        dest = GOODS_DIR / f"{gid}.json"

        if dest.exists():
            try:
                resource = json.loads(dest.read_text(encoding="utf-8"))
                ok.append({"id": gid, "url": url_path, "cached": True})
                all_covers.update(collect_cover_urls(catalog, resource))
                if i % 50 == 0:
                    print(f"  [{i}/{len(items)}] cached {gid}")
                continue
            except json.JSONDecodeError:
                pass

        resource = None
        for attempt in range(MAX_RETRIES):
            try:
                resp = fetch(sess, full_url)
                resource = parse_resource(resp.text)
                if resource is None:
                    raise ValueError("no :resource JSON in page")
                dest.write_text(json.dumps(resource, ensure_ascii=False, indent=2), encoding="utf-8")
                ok.append({"id": gid, "url": url_path})
                all_covers.update(collect_cover_urls(catalog, resource))
                break
            except Exception as e:
                if attempt == MAX_RETRIES - 1:
                    errors.append({"id": gid, "url": url_path, "error": str(e)})
                    print(f"  FAIL {gid} {url_path}: {e}", file=sys.stderr)
                time.sleep(1.0)

        if i % 25 == 0:
            print(f"  [{i}/{len(items)}] done")
        time.sleep(DELAY_SEC)

    print(f"Downloading {len(all_covers)} cover images...")
    for url in sorted(all_covers):
        if not url or not url.startswith("http"):
            continue
        dest = COVERS_DIR / cover_filename(url)
        download_file(sess, url, dest)
        time.sleep(0.1)

    return ok, errors


def main() -> int:
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    sess = session()

    catalog = scrape_catalog(sess)
    time.sleep(DELAY_SEC)
    scrape_paymethods(sess)
    time.sleep(DELAY_SEC)
    scrape_checkout_html(sess)
    time.sleep(DELAY_SEC)
    css_files = scrape_homepage_assets(sess)
    time.sleep(DELAY_SEC)
    ok, errors = scrape_goods(sess, catalog)

    manifest = {
        "scraped_at": datetime.now(timezone.utc).isoformat(),
        "base_url": BASE_URL,
        "catalog_count": len(catalog.get("data", {}).get("catalog", [])),
        "goods_ok": len(ok),
        "goods_errors": len(errors),
        "css_files": css_files,
        "errors": errors,
    }
    (DATA_DIR / "manifest.json").write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    print(json.dumps(manifest, indent=2))
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())
