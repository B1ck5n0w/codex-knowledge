import hashlib
import html.parser
import argparse
import json
import re
import sqlite3
import sys
import time
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urljoin, urlparse
from urllib.request import Request, urlopen


DB_PATH = Path("job_research.db")
SEED_PATH = Path("companies_seed.json")
MAX_PAGES_PER_COMPANY = 6

CAREER_TERMS = (
    "karriere",
    "career",
    "jobs",
    "stellen",
    "stellenangebote",
    "offene-stellen",
    "bewerbung",
    "arbeiten",
)

JOB_TERMS = (
    "e-commerce",
    "ecomm",
    "ecommerce",
    "commerce",
    "digital",
    "product owner",
    "product manager",
    "shop",
    "online",
    "crm",
    "omnichannel",
    "marketplace",
    "leiter",
    "head",
    "lead",
)

EXCLUDE_URL_TERMS = (
    "/shop/",
    "/produkt",
    "/products",
    "/category",
    "categorycode=",
    "/c/",
    "warenkorb",
    "/cart",
    "katalog",
    "wishlist",
    "account/login",
    "checkout",
    "agb",
    "datenschutz",
    "widerruf",
    "versand",
    "zahlung",
    "faq",
    "impressum",
)


class LinkParser(html.parser.HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []
        self.title = ""
        self._in_title = False
        self._current_href = None
        self._current_title = ""
        self._current_text = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "a" and attrs.get("href"):
            self._current_href = attrs.get("href")
            self._current_title = attrs.get("title") or ""
            self._current_text = []
        if tag == "title":
            self._in_title = True

    def handle_endtag(self, tag):
        if tag == "title":
            self._in_title = False
        if tag == "a" and self._current_href:
            label = self._current_title or normalize_text(" ".join(self._current_text))
            self.links.append((self._current_href, label))
            self._current_href = None
            self._current_title = ""
            self._current_text = []

    def handle_data(self, data):
        if self._in_title:
            self.title += data.strip()
        if self._current_href:
            self._current_text.append(data)


def now_iso():
    return datetime.now(timezone.utc).isoformat(timespec="seconds")


def normalize_text(value):
    return re.sub(r"\s+", " ", value or "").strip()


def fetch(url, timeout=8):
    req = Request(
        url,
        headers={
            "User-Agent": "Mozilla/5.0 job-research-crawler/1.0",
            "Accept": "text/html,application/xhtml+xml",
        },
    )
    with urlopen(req, timeout=timeout) as response:
        final_url = response.geturl()
        content_type = response.headers.get("content-type", "")
        raw = response.read(2_000_000)
    encoding = "utf-8"
    match = re.search(r"charset=([\w-]+)", content_type, re.I)
    if match:
        encoding = match.group(1)
    text = raw.decode(encoding, errors="replace")
    return final_url, content_type, text


def same_domain(base, candidate):
    base_host = urlparse(base).netloc.lower().removeprefix("www.")
    candidate_host = urlparse(candidate).netloc.lower().removeprefix("www.")
    return candidate_host == base_host or candidate_host.endswith("." + base_host)


def score_url(url, label=""):
    haystack = f"{url} {label}".lower()
    score = 0
    for term in CAREER_TERMS:
        if term in haystack:
            score += 3
    for term in JOB_TERMS:
        if term in haystack:
            score += 1
    return score


def excluded_url(url):
    value = url.lower()
    return any(term in value for term in EXCLUDE_URL_TERMS)


def career_url_guesses(company):
    website = company["website"]
    paths = (
        "",
        "karriere/",
        "career/",
        "careers/",
        "jobs/",
        "stellenangebote/",
        "offene-stellen/",
        "unternehmen/karriere/",
        "de/karriere/",
        "de/jobs/",
    )
    guesses = []
    guesses.extend(company.get("job_urls", []))
    guesses.extend(company.get("career_urls", []))
    guesses.extend(urljoin(website, path) for path in paths)
    deduped = []
    for url in guesses:
        if url not in deduped:
            deduped.append(url)
    return deduped


def is_possible_job_page(url, title, text):
    haystack = f"{url} {title} {text[:5000]}".lower()
    title_url = f"{url} {title}".lower()
    career_context = any(term in haystack for term in CAREER_TERMS + ("m/w/d", "vollzeit", "teilzeit", "hybrid"))
    role_context = any(term in title_url for term in JOB_TERMS)
    apply_context = any(term in haystack for term in ("bewerbung", "bewirb", "aufgaben", "profil", "wir bieten"))
    return career_context and role_context and apply_context


def init_db(conn):
    conn.executescript(
        """
        create table if not exists companies (
            id integer primary key,
            name text unique not null,
            city text,
            website text not null,
            priority text,
            reason text,
            blocked integer default 0,
            created_at text,
            updated_at text
        );
        create table if not exists pages (
            id integer primary key,
            company_id integer not null,
            url text not null,
            page_type text,
            title text,
            content_hash text,
            last_status text,
            first_seen_at text,
            last_seen_at text,
            last_changed_at text,
            relevant_score integer default 0,
            unique(company_id, url),
            foreign key(company_id) references companies(id)
        );
        create table if not exists snapshots (
            id integer primary key,
            page_id integer not null,
            fetched_at text,
            content_hash text,
            title text,
            excerpt text,
            foreign key(page_id) references pages(id)
        );
        create table if not exists job_links (
            id integer primary key,
            company_id integer not null,
            url text not null,
            title text,
            source_page text,
            priority text,
            status text,
            first_seen_at text,
            last_seen_at text,
            notes text,
            unique(company_id, url),
            foreign key(company_id) references companies(id)
        );
        create table if not exists crawl_runs (
            id integer primary key,
            started_at text,
            finished_at text,
            companies_requested integer,
            companies_crawled integer,
            pages_crawled integer,
            job_links_seen integer,
            notes text
        );
        """
    )


def upsert_company(conn, company):
    ts = now_iso()
    conn.execute(
        """
        insert into companies(name, city, website, priority, reason, blocked, created_at, updated_at)
        values (?, ?, ?, ?, ?, 0, ?, ?)
        on conflict(name) do update set
            city=excluded.city,
            website=excluded.website,
            priority=excluded.priority,
            reason=excluded.reason,
            updated_at=excluded.updated_at
        """,
        (
            company["name"],
            company.get("city"),
            company["website"],
            company.get("priority"),
            company.get("reason"),
            ts,
            ts,
        ),
    )
    return conn.execute("select id from companies where name = ?", (company["name"],)).fetchone()[0]


def upsert_page(conn, company_id, url, page_type, title, content_hash, status, score, excerpt):
    ts = now_iso()
    row = conn.execute(
        "select id, content_hash from pages where company_id = ? and url = ?",
        (company_id, url),
    ).fetchone()
    if row:
        page_id, old_hash = row
        changed_at = ts if old_hash != content_hash else None
        if changed_at:
            conn.execute(
                """
                update pages set page_type=?, title=?, content_hash=?, last_status=?,
                    last_seen_at=?, last_changed_at=?, relevant_score=?
                where id=?
                """,
                (page_type, title, content_hash, status, ts, changed_at, score, page_id),
            )
        else:
            conn.execute(
                """
                update pages set page_type=?, title=?, last_status=?, last_seen_at=?, relevant_score=?
                where id=?
                """,
                (page_type, title, status, ts, score, page_id),
            )
    else:
        conn.execute(
            """
            insert into pages(company_id, url, page_type, title, content_hash, last_status,
                first_seen_at, last_seen_at, last_changed_at, relevant_score)
            values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """,
            (company_id, url, page_type, title, content_hash, status, ts, ts, ts, score),
        )
        page_id = conn.execute("select last_insert_rowid()").fetchone()[0]
    conn.execute(
        "insert into snapshots(page_id, fetched_at, content_hash, title, excerpt) values (?, ?, ?, ?, ?)",
        (page_id, ts, content_hash, title, excerpt[:2000]),
    )
    return page_id


def upsert_job_link(conn, company_id, url, title, source_page, priority, notes):
    ts = now_iso()
    conn.execute(
        """
        insert into job_links(company_id, url, title, source_page, priority, status,
            first_seen_at, last_seen_at, notes)
        values (?, ?, ?, ?, ?, 'seen', ?, ?, ?)
        on conflict(company_id, url) do update set
            title=excluded.title,
            source_page=excluded.source_page,
            priority=excluded.priority,
            status='seen',
            last_seen_at=excluded.last_seen_at,
            notes=excluded.notes
        """,
        (company_id, url, title, source_page, priority, ts, ts, notes),
    )


def crawl_company(conn, company, max_pages=MAX_PAGES_PER_COMPANY):
    company_id = upsert_company(conn, company)
    website = company["website"]
    allowed_hosts = {urlparse(website).netloc.lower().removeprefix("www.")}
    for career_url in company.get("career_urls", []):
        allowed_hosts.add(urlparse(career_url).netloc.lower().removeprefix("www."))
    queue = [(url, "career" if score_url(url) >= 3 else "home", 0) for url in career_url_guesses(company)]
    seen = set()
    crawled = 0
    found_jobs = 0
    errors = []

    while queue and crawled < max_pages:
        url, page_type, depth = queue.pop(0)
        if url in seen or excluded_url(url):
            continue
        seen.add(url)
        try:
            final_url, content_type, html = fetch(url)
        except Exception as exc:
            errors.append(f"{url}: {exc}")
            continue
        if "text/html" not in content_type and "application/xhtml" not in content_type:
            continue

        parser = LinkParser()
        parser.feed(html)
        title = normalize_text(parser.title)
        text = normalize_text(re.sub(r"<[^>]+>", " ", html))
        content_hash = hashlib.sha256(text.encode("utf-8", errors="ignore")).hexdigest()
        page_score = score_url(final_url, title)
        is_career = page_type == "career" or page_score >= 3
        is_explicit_job = final_url.rstrip("/") in {url.rstrip("/") for url in company.get("job_urls", [])}
        is_job = is_explicit_job or is_possible_job_page(final_url, title, text)
        stored_type = "job" if is_job else ("career" if is_career else "company")
        upsert_page(conn, company_id, final_url, stored_type, title, content_hash, "ok", page_score, text)
        crawled += 1

        if is_job:
            found_jobs += 1
            priority = "B" if company.get("priority") in ("A", "B") else "C"
            notes = "Explizit gepflegter Joblink." if is_explicit_job else "Automatisch erkannter moeglicher Job-/Karriere-Treffer; manuell pruefen."
            upsert_job_link(conn, company_id, final_url, title or final_url, final_url, priority, notes)

        links = []
        for href, label in parser.links:
            absolute = urljoin(final_url, href.split("#")[0])
            if not absolute.startswith(("http://", "https://")):
                continue
            host = urlparse(absolute).netloc.lower().removeprefix("www.")
            if not (same_domain(website, absolute) or host in allowed_hosts):
                continue
            score = score_url(absolute, label)
            if score > 0 and not excluded_url(absolute):
                links.append((score, absolute, label))
        links.sort(reverse=True)
        for score, absolute, label in links[:8]:
            if absolute not in seen:
                queue.append((absolute, "career" if score >= 3 else "company", depth + 1))
        time.sleep(0.4)

    return {
        "company": company["name"],
        "crawled_pages": crawled,
        "job_like_pages": found_jobs,
        "errors": errors[:3],
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--reset", action="store_true")
    parser.add_argument("--start", type=int, default=0, help="Zero-based index in companies_seed.json")
    parser.add_argument("--count", type=int, default=None, help="Number of companies to crawl")
    parser.add_argument("--company", action="append", default=[], help="Company name to crawl; can be passed multiple times")
    parser.add_argument("--max-pages", type=int, default=MAX_PAGES_PER_COMPANY)
    args = parser.parse_args()

    if not SEED_PATH.exists():
        print(f"Missing {SEED_PATH}", file=sys.stderr)
        return 1
    seed = json.loads(SEED_PATH.read_text(encoding="utf-8"))
    conn = sqlite3.connect(DB_PATH, timeout=10)
    init_db(conn)
    if args.reset:
        conn.executescript(
            """
            delete from job_links;
            delete from snapshots;
            delete from pages;
            delete from companies;
            """
        )
        conn.commit()
    companies = seed["companies"]
    if args.company:
        requested = {name.lower() for name in args.company}
        companies = [company for company in companies if company["name"].lower() in requested]
    else:
        end = None if args.count is None else args.start + args.count
        companies = companies[args.start:end]

    run_started = now_iso()
    results = []
    for company in companies:
        if any(blocked.lower() in company["name"].lower() for blocked in seed.get("blocked_companies", [])):
            continue
        results.append(crawl_company(conn, company, args.max_pages))
        conn.commit()
    pages_crawled = sum(result["crawled_pages"] for result in results)
    job_links_seen = conn.execute("select count(*) from job_links").fetchone()[0]
    conn.execute(
        """
        insert into crawl_runs(started_at, finished_at, companies_requested, companies_crawled,
            pages_crawled, job_links_seen, notes)
        values (?, ?, ?, ?, ?, ?, ?)
        """,
        (
            run_started,
            now_iso(),
            len(companies),
            len(results),
            pages_crawled,
            job_links_seen,
            f"start={args.start}, count={args.count}, max_pages={args.max_pages}",
        ),
    )
    conn.commit()
    print(json.dumps({"database": str(DB_PATH), "results": results}, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
