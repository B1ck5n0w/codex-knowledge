# Firmenwebseiten-Crawl Status

Stand: 2026-07-20, Wochenlauf

## Datenbank

- Datei: `job_research.db`
- Firmen: 36
- Seiten: 90
- Echte Direkt-Joblinks: 3
- Jobboard-Fallbacks: 1
- Seed-Datei: `companies_seed.json`
- Crawler: `company_crawler.py`
- Report: `company_crawler_report.py`
- Cleanup: `cleanup_job_research_db.py`

## Aktuelle Treffer

| Unternehmen | Ort | Rolle | Quelle | URL | Status |
|---|---|---|---|---|---|
| moses. Verlag GmbH | Kempen | E-Commerce Manager*in, 30-40 Std., hybrid | Firmenwebseite | https://www.moses-verlag.de/moses.-Verlag/Karriere/Offene-Stellen/Ecomm-Management/ | weiterhin gesehen |
| Redcare Pharmacy | Sevenum | Senior Product Owner, ERP | Firmenwebseite | https://www.redcare-pharmacy.com/careers/open-jobs/details/744000101841515 | weiterhin gesehen; Pendel-/ERP-Fit kritisch |
| Redcare Pharmacy | Sevenum | Supply Chain Director | Firmenwebseite | https://www.redcare-pharmacy.com/careers/open-jobs/details/744000117623477 | neu; 120-140k EUR, aber nur angrenzender Commerce-Fit |
| Paradies GmbH | Neukirchen-Vluyn | E-Commerce Manager Shopify und Marktplaetze | Jobboard-Fallback | https://www.stepstone.de/stellenangebote--E-Commerce-Manager-m-w-d-fuer-Shopify-und-Marktplaetze-Neukirchen-Vluyn-Paradies-GmbH--13733444-inline.html | erneut auffindbar; kein Direktlink belegbar |

## Erfasste Karriere-/Stellenseiten

| Unternehmen | URL |
|---|---|
| moses. Verlag GmbH | https://www.moses-verlag.de/Unternehmen/Karriere/ |
| moses. Verlag GmbH | https://www.moses-verlag.de/Unternehmen/Karriere/Offene-Stellen/ |
| Landgard | https://karriere.landgard.de/ |
| Landgard | https://karriere.landgard.de/karriere-site/fach-fuehrungskraefte |
| Paradies GmbH | https://paradies.de/pages/karriere |
| Veiling Rhein-Maas | https://karriere.veilingrheinmaas.com/ |
| Carl Kuehne KG Werk Straelen | https://www.kuehne.de/karriere |
| Carl Kuehne KG Werk Straelen | https://www.kuehne.de/karriere/stellenangebote |
| Bonduelle Deutschland | https://www.bonduelle.de/karriere |
| ELTEN GmbH | https://elten.com/karriere/ |
| Walther Faltsysteme | https://faltbox.com/de/ |
| ZOXS GmbH | https://www.zoxs.de/karriere.html |
| RZH Rechenzentrum fuer Heilberufe | https://www.rzh.de/karriere/ |
| Nagels Druck | https://nagels.com/jobs/ |
| Nagels Druck | https://nagels.com/karriere-bei-nagels/ |
| Trox GmbH | https://karriere.trox.de/ |
| ABS Safety GmbH | https://www.absturzsicherung.de/unternehmen/jobs-karriere.html |
| Herbrand Gruppe | https://herbrand.de/stellenangebote/ |
| Herbrand Gruppe | https://herbrand.de/herbrand-gruppe-als-arbeitgeber |
| Omexom Deutschland Standort Uedem | https://karriere.omexom.de/ |
| Silesia Gerhard Hanke | https://www.silesia-aroma.com/ |
| Kersia Deutschland | https://www.kersia-group.com/careers/ |
| Sauels Gruppe | https://www.sauels.de/karriere |
| Sauels Gruppe | https://www.sauels.de/en/careers |
| Canon Production Printing Netherlands | https://cpp.canon/careers/ |
| vidaXL | https://careers.vidaxl.com/ |
| vidaXL | https://careers.vidaxl.com/vacancies?_locale=en |
| Redcare Pharmacy | https://www.redcare-pharmacy.com/careers |
| Redcare Pharmacy | https://www.redcare-pharmacy.com/careers/open-jobs |
| Leolux | https://www.leolux.com/de/karriere |
| Leolux | https://www.leolux.com/unternehmen/karriere |
| Belden | https://www.belden.com/about/life-at-belden |
| Ewals Cargo Care | https://career.ewals.com/ |
| Scheuten Glas | https://www.werkenbijscheuten.com/ |

## Hinweise

- Wochenlauf 2026-07-20: Alle 36 Seed-Firmen in sechs kleinen Batches gestartet. Wegen der lokalen Netzwerk-Sandbox waren keine neuen Python-Seitenabrufe moeglich; der Report blieb bei 36 Firmen und 90 Seiten. Die wichtigsten Arbeitgeberseiten wurden deshalb gezielt ueber aktuelle Webindizes und Direktlinks geprueft.
- moses E-Commerce Manager*in ist ueber den direkten Firmenlink weiterhin vollstaendig aufrufbar (30-40 Std., zwei Home-Office-Tage), wird aber in der aktuellen Stellenuebersicht nicht angezeigt. Vor einer Bewerbung deshalb telefonisch oder per E-Mail bestaetigen lassen, dass die Stelle noch besetzt wird.
- Redcare Senior Product Owner ERP ist weiterhin aktiv. Neu hinzugekommen ist Supply Chain Director in Sevenum mit 120.000-140.000 EUR, mindestens drei Praesenztagen und echter Fuehrungsverantwortung; wegen Supply-Chain- statt Commerce-Fokus nur Prioritaet C.
- Paradies bleibt nur als Jobboard-Fallback belegbar; der bestehende StepStone-Link ist weiter aufrufbar, ein Arbeitgeber-Direktlink fehlt.
- Wochenlauf 2026-07-13: 36 Firmen in vier kleinen Batches geprueft; 58 Seitenabrufe erfolgreich. Content-Hashes mehrerer Seiten haben sich geaendert, aber die gezielte Nachpruefung ergab keine neue passende Commerce-Leitungsrolle im Kernradius.
- Der moses-Treffer ist weiterhin aktiv. Er bleibt ein guter 30-Stunden-/Nebengewerbe-Sonderfall, ist fachlich jedoch stark Marketplace-/Amazon-operativ und ohne erkennbare Personalverantwortung.
- Neu aufgenommen wurde Redcare Pharmacy: Senior Product Owner ERP in Sevenum. Strategischer Product-/Delivery-Fit, aber Pendelzeit und fehlender klarer Commerce-Fokus begrenzen die Prioritaet.
- Paradies ist aktuell nur per StepStone-Fallback belegbar; die Arbeitgeberseite lieferte im Crawl HTTP 503.
- ArtiTree und der fruehere Fressnapf-Solution-Architecture-Treffer konnten nicht erneut belegt werden und sind in `E-Commerce_Jobs_Seen.json` entsprechend markiert.

- Fressnapf-Karriereseiten liefen im Initialcrawl in Timeouts; sie bleiben in `companies_seed.json` mit expliziten Karriere-URLs.
- bofrost-Karriereseite hatte im Python-Crawl ein Zertifikatsproblem; sie bleibt in `companies_seed.json`.
- Einige externe Karriereportale/Unternehmensseiten blocken automatisierte Requests oder liefern Zertifikats-/DNS-Fehler. Diese Firmen bleiben in der Seed-Liste, muessen aber bei Bedarf per Websuche oder manuell gepflegtem Direktlink nachgezogen werden.
- Einige Firmen nutzen Shop-Systeme mit vielen irrelevanten Kategorie-/Produktseiten. Der Crawler filtert diese inzwischen staerker aus.
- Joblink-Erkennung wurde nach dem Initiallauf enger gezogen; konkrete Joblinks sollen nur noch in `job_links` stehen, wenn es wirklich Stellenanzeigen sind.

## Empfohlener Laufmodus

Crawler in kleinen Batches ausfuehren, z. B.:

```powershell
& 'C:\Users\chder\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' company_crawler.py --start 0 --count 5 --max-pages 5
& 'C:\Users\chder\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' company_crawler.py --start 5 --count 5 --max-pages 4
& 'C:\Users\chder\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' company_crawler.py --start 10 --count 5 --max-pages 4
& 'C:\Users\chder\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' company_crawler.py --start 15 --count 7 --max-pages 3
& 'C:\Users\chder\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' company_crawler.py --start 22 --count 6 --max-pages 3
& 'C:\Users\chder\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' company_crawler.py --start 28 --count 8 --max-pages 3
& 'C:\Users\chder\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' company_crawler_report.py
```
