# Familien-Radtourenplaner

Öffne `index.html` in einem Browser. Die Kartenkacheln werden von OpenStreetMap geladen.

Für eine echte Straßenroute wird ein eigener kostenloser OpenRouteService-Schlüssel benötigt. Der Schlüssel wird nur im Browser verwendet und nicht gespeichert. Ohne Schlüssel erzeugt die Anwendung einen klar als solchen markierten Planungsentwurf; dieser ist nicht zur Navigation geeignet.

Die Anwendung kann den Start per Adresse über Nominatim suchen, die aktuelle GPS-Position verwenden oder einen Kartenpunkt übernehmen. Ziel- und Stoppvorschläge werden beim jeweiligen Klick über OpenStreetMap/Overpass abgefragt. Beim Klick auf **Komplette Tour berechnen** werden die gewählten Koordinaten an OpenRouteService übermittelt. Bitte die resultierende Tour vor einer Fahrt mit Kindern und Anhänger auf Sperrungen, Wegoberfläche und Öffnungszeiten prüfen.
