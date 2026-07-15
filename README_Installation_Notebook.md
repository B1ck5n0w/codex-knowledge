# Codex auf dem Notebook einrichten

Diese Anleitung richtet die bestehenden Codex-Projekte auf einem zweiten Windows-Gerät ein. Danach werden Code und Notizen über GitHub automatisch abgeglichen; große Arbeitsdaten liegen in Google Drive.

## 1. Google Drive einrichten

1. Installiere **Google Drive für Desktop** und melde dich mit demselben Google-Konto wie auf dem PC an.
2. Stelle sicher, dass dieser Ordner im Explorer erreichbar ist:

   ```text
   Meine Ablage\Projekte\KI Projekte
   ```

   Der Laufwerksbuchstabe kann anders sein als auf dem PC (zum Beispiel `H:` statt `G:`). Das Einrichtungs-Skript erkennt den Ordner automatisch.

## 2. GitHub und Codex installieren

1. Installiere GitHub Desktop und melde dich mit dem GitHub-Konto **B1ck5n0w** an. Git verwendet anschließend den Git Credential Manager; an einer Git-Passwortabfrage niemals ein GitHub-Passwort eingeben.
2. Installiere bzw. öffne Codex.
3. Öffne PowerShell und führe diese beiden Befehle aus:

   ```powershell
   git clone https://github.com/B1ck5n0w/codex-knowledge.git "$HOME\Documents\Chris Derix Privat"
   & "$HOME\Documents\Chris Derix Privat\scripts\setup-notebook.ps1"
   ```

Das Skript klont diese Projekte und richtet die Synchronisation ein:

- `Sommer Party`
- `freizeitexperten.de\freizeitexperten-erp-dev-git`
- `freizeitexperten.de\plugins-work`
- `Fokus Tracker Website` im Google-Drive-Ordner

Falls GitHub beim ersten privaten Repository eine Anmeldung verlangt, melde dich im geöffneten Browserfenster an. Alternativ kann die Anmeldung gezielt gestartet werden:

```powershell
git credential-manager github login --browser --username B1ck5n0w
```

Die Anleitung enthält nur den Clone von `codex-knowledge` und den anschließenden Aufruf des Setup-Skripts; es gibt keinen Clone-Befehl mit einer `.ps1`-Zieldatei.

## 3. Automatische Synchronisation prüfen

Das Skript richtet zwei Windows-Aufgaben ein:

- **Codex Git Auto-Sync:** alle 15 Minuten; zieht Änderungen, erstellt bei Bedarf einen Commit und pusht ihn nach GitHub.
- **Codex Drive Data Backup:** stündlich; sichert große Freizeitexperten-Arbeitsdaten in Google Drive.

Prüfen in PowerShell:

```powershell
schtasks /Query /TN "Codex Git Auto-Sync"
schtasks /Query /TN "Codex Drive Data Backup"
```

## 4. Projekte in Codex öffnen

Füge in der Codex-App bei **Projekte** die gewünschten Ordner hinzu. Wichtig ist insbesondere:

```text
<Google-Drive-Laufwerk>:\Meine Ablage\Projekte\KI Projekte\Fokus Tracker Website
```

## Arbeitsregeln

- Arbeite immer nur auf einem Gerät gleichzeitig.
- Warte vor einem Gerätewechsel kurz, damit der GitHub-Abgleich abgeschlossen ist.
- Quellcode, Markdown und kleine Projektdateien gehören nach GitHub.
- Große Medien, Datenbanken und Backups bleiben in Google Drive.
- Keine Schlüssel, Zertifikate, `.env`-Dateien oder Passwörter nach GitHub einchecken.
