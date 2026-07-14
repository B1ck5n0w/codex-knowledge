import sqlite3
from pathlib import Path


DB_PATH = Path("job_research.db")


def main():
    conn = sqlite3.connect(DB_PATH)
    print("COUNTS")
    print("companies", conn.execute("select count(*) from companies").fetchone()[0])
    print("pages", conn.execute("select count(*) from pages").fetchone()[0])
    print("job_links", conn.execute("select count(*) from job_links").fetchone()[0])
    print()

    print("JOB LINKS")
    rows = conn.execute(
        """
        select companies.name, job_links.title, job_links.url, job_links.notes
        from job_links
        join companies on companies.id = job_links.company_id
        order by companies.name, job_links.title
        """
    ).fetchall()
    for row in rows:
        print(" | ".join(str(value or "") for value in row))
    print()

    print("CAREER/JOB PAGES")
    rows = conn.execute(
        """
        select companies.name, pages.page_type, pages.title, pages.url
        from pages
        join companies on companies.id = pages.company_id
        where pages.page_type in ('career', 'job')
        order by companies.name, pages.page_type desc, pages.url
        """
    ).fetchall()
    for row in rows:
        print(" | ".join(str(value or "") for value in row))
    print()

    print("CRAWL RUNS")
    rows = conn.execute(
        """
        select started_at, finished_at, companies_requested, companies_crawled,
            pages_crawled, job_links_seen, notes
        from crawl_runs
        order by id
        """
    ).fetchall()
    for row in rows:
        print(" | ".join(str(value or "") for value in row))


if __name__ == "__main__":
    main()
