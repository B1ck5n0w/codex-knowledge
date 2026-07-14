import sqlite3


VALID_JOB_URLS = {
    "https://www.moses-verlag.de/moses.-Verlag/Karriere/Offene-Stellen/Ecomm-Management/",
}

FALSE_CAREER_URLS = {
    "https://karriere.veilingrheinmaas.com",
    "https://karriere.veilingrheinmaas.com/",
    "https://karriere.veilingrheinmaas.com/#c4618",
}

FALSE_COMPANY_URLS = {
    "https://www.kuehne.de/ueber-uns/zulieferer-bewerbung",
    "https://www.bofrost.de/cart?directBuy=true",
    "https://www.bofrost.de/katalogbestellung.html",
}


def main():
    conn = sqlite3.connect("job_research.db")
    placeholders = ",".join("?" for _ in VALID_JOB_URLS)
    conn.execute(f"delete from job_links where url not in ({placeholders})", tuple(VALID_JOB_URLS))
    for url in FALSE_CAREER_URLS:
        conn.execute("update pages set page_type = 'career' where url = ?", (url,))
    for url in FALSE_COMPANY_URLS:
        conn.execute("update pages set page_type = 'company' where url = ?", (url,))
    conn.commit()
    print("job_links", conn.execute("select count(*) from job_links").fetchone()[0])


if __name__ == "__main__":
    main()
