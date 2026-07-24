# Git-Arbeitsweise für Codex-Projekte

- Bei jeder Aufgabe, die Dateien in einem Git-Repository verändert, prüfe zu Beginn Branch, Arbeitsbaum und Remote-Status.
- Vor Änderungen dürfen fremde oder noch nicht gesicherte Benutzeränderungen weder überschrieben noch verworfen werden.
- Hole aktuelle Remote-Änderungen vor der Arbeit, sofern der Arbeitsbaum dies konfliktfrei zulässt.
- Prüfe nach der Umsetzung die betroffenen Dateien und führe angemessene Tests aus.
- Suche vor dem Commit nach Schlüsseln, Zertifikaten, `.env`-Dateien, Zugangsdaten und anderen sensiblen Inhalten. Solche Dateien dürfen nicht eingecheckt werden.
- Erstelle nach erfolgreich abgeschlossener Arbeit einen inhaltlich aussagekräftigen Commit und pushe ihn zum vorhandenen Remote.
- Bei Konflikten, fehlendem Remote, fehlender Berechtigung oder unklaren sensiblen Dateien nicht erzwingen, sondern den Benutzer informieren.
- Reine Analyse-, Review- oder Statusaufgaben erzeugen keinen Commit.

Die Windows-Aufgabe `Codex Git Auto-Sync` wird nicht verwendet. Git-Abgleiche erfolgen kontrolliert innerhalb der jeweiligen Codex-Arbeitssitzung.
