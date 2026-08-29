# com_statistics

Joomla-Komponente für die anonyme Besucher-Statistik des Weltspiegel Cottbus.

Enthält das Datenmodell (eine reine Zähler-Tabelle), den Endpunkt für die CSS-Messpunkte und die
Admin-Auswertung. Die eigentliche Messung übernimmt das System-Plugin
[`plg_system_statistics`](../plg_system_statistics), das diese Komponente voraussetzt.

Erfasst wird ausschließlich über CSS Media Queries — kein JavaScript, keine Cookies, kein Zugriff
auf das Endgerät, keine personenbezogenen Daten.

Verfahren, Datenmodell, Erweiterbarkeit und rechtliche Einordnung: siehe
[`docs/STATISTIK.md`](../docs/STATISTIK.md) im Projekt-Repository.
