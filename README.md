# com_statistics

Joomla-Komponente für die anonyme Besucher-Statistik des Weltspiegel Cottbus.

Enthält das Datenmodell (eine reine Zähler-Tabelle), den Endpunkt für die CSS-Messpunkte und die
Admin-Auswertung. Die eigentliche Messung übernimmt das System-Plugin
[`plg_system_statistics`](../plg_system_statistics), das diese Komponente voraussetzt.

Erfasst wird in zwei Teilen, beide ohne JavaScript, ohne Cookies, ohne Zugriff auf das Endgerät und
ohne personenbezogene Daten:

- **Geräte & Anzeige** über CSS Media Queries (Bildschirmbreite, Eingabegerät …)
- **Seitenaufrufe** (ab 1.1) serverseitig aus den Angaben, die der Browser mit jeder Seitenanfrage
  mitschickt: welche Art Seite, woher der Aufruf kam, und die Wege zwischen den Seiten. Es werden nur
  Seitenaufrufe gezählt, keine Besucher.

Verfahren, Datenmodell, Erweiterbarkeit und rechtliche Einordnung: siehe
[`docs/STATISTIK.md`](../docs/STATISTIK.md) im Projekt-Repository.
