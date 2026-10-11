# AWTRIX NG in Symcon einrichten

[Zur README](../README.md) · [Änderungsverlauf](../CHANGELOG.md)

## 1. Modul installieren

In der Symcon-Modulverwaltung die Bibliothek `https://github.com/Sol-IoTiv/SymconAWTRIXNG` hinzufügen und **dev** auswählen. Dies ist der aktuelle Entwicklungs- und Testbranch. Symcon ab Version 7.0 und eine bereits im WLAN eingerichtete AWTRIX-NG-Uhr werden benötigt.

## 2. Uhr verbinden

Entweder **AWTRIX NG Gerätesuche** anlegen, suchen und aus der Ergebnisliste eine Geräteinstanz erstellen, oder direkt eine Instanz **AWTRIX NG** erstellen und die IP-Adresse eingeben. Bei geändertem Webport beispielsweise `192.168.1.50:8080` verwenden. Kein `http://` und keinen Pfad eingeben.

Falls die Uhr eine Webserver-Anmeldung verlangt, Benutzername und Passwort im Abschnitt Anmeldung eintragen. **Übernehmen**, dann **Verbindung testen** und **Testnachricht** drücken.

Die Suche läuft auf dem Symcon-Server. Sie sendet eine UDP-Anfrage auf Port 4210 und lauscht zwei Sekunden auf Port 4211. Bei ausbleibenden Treffern kann die gezielte Broadcast-Adresse des eigenen Subnetzes helfen. Broadcasts über Gastnetz- oder VLAN-Grenzen sind häufig blockiert; dann die IP manuell eintragen.

## 3. Anzeigen hinzufügen

Unter **Anzeigen** auf **Hinzufügen** klicken:

| Feld | Bedeutung |
|---|---|
| Aktiv | Zeile anzeigen; ausgeschaltete Zeilen bleiben konfiguriert |
| Kennung | Eindeutiger Name, etwa `warmwasser`; 1–16 Zeichen aus A–Z, a–z, 0–9, _ und - |
| Variable | Die Symcon-Variable mit dem gewünschten Wert |
| Text davor | Optionaler Text vor dem Wert |
| Einheit / Text danach | Beispielsweise ` °C`, ` kW` oder ` %`; Leerzeichen mit eingeben |
| Nachkommastellen | 0 bis 6; gilt für numerische Variablen |
| Icon | Ein bereits auf der Uhr installiertes Icon oder „Kein Icon“ |
| Dauer | Anzeigedauer von 1 bis 300 Sekunden |
| Textfarbe | Farbe des angezeigten Texts |

**Übernehmen** sendet die Anzeigen. Zahlen erhalten ein Dezimalkomma, boolesche Werte erscheinen als Ein/Aus und Strings unverändert. Symcon-Variablenprofile werden nicht automatisch übernommen.

## 4. Icons installieren

**Icon-Galerie öffnen (TC001)** öffnet die gefilterte Galerie auf awtrix.de. Im Hub anmelden, Icon öffnen und **Send to AWTRIX** verwenden. Die richtige Uhr auswählen bzw. verbinden. Der Browser muss die Uhr im lokalen Netzwerk erreichen können.

Alternativ **Icons auf der Uhr verwalten** verwenden und eigene Dateien über die AWTRIX-Oberfläche hinzufügen. Danach in Symcon **Icons von Uhr laden** drücken. Das Formular wird neu geladen; ungespeicherte Änderungen daher vorher übernehmen. Anschließend in der Anzeigenzeile das Icon auswählen.

Die Icon-ID entspricht dem Dateinamen auf der neuen Uhr ohne Erweiterung. Icons der alten Uhr werden nicht automatisch übertragen. Eine Bildvorschau im Symcon-Dropdown ist derzeit nicht enthalten.

## 5. Icons nach Wert wechseln

Im Abschnitt **Icons abhängig vom Wert** eine Regel hinzufügen. Im Dropdown die Anzeige auswählen; neue Anzeigen vorher übernehmen. Dann die Obergrenze und das Icon wählen.

Es gilt die kleinste passende Obergrenze einschließlich des Grenzwerts. Die Reihenfolge der Regeln ist unerheblich. Oberhalb aller Grenzen verwendet die Anzeige ihr normales Icon. Verglichen wird der ursprüngliche numerische Wert, unabhängig von der Textformatierung. Eine Regel mit „Kein Icon“ blendet das Icon für ihren Bereich aus.

### PV

Normales Icon der Anzeige: `pv`. Eine Regel mit Obergrenze `0` und Icon `no_pv` zeigt bei null und negativen Werten das andere Icon. Bei jedem positiven Wert erscheint `pv`.

### Hausakku

Normales Icon der Anzeige: `akku100`. Die Anzeige verwendet den Ladezustand in Prozent.

| Obergrenze einschließlich | Icon |
|---:|---|
| 10 | akku00 |
| 20 | akku20 |
| 40 | akku40 |
| 60 | akku60 |
| 80 | akku80 |

Über 80 Prozent erscheint `akku100`. Die Icon-Namen sind Beispiele und müssen zu den installierten Dateien passen. Dieselbe Anzeige darf mehrere Regeln, aber keine doppelte Obergrenze haben.

## 6. Reihenfolge ändern

Zeilen in der Liste **Anzeigen** per Drag-and-drop verschieben und **Übernehmen** drücken. Alternativ nach dem Speichern **Anzeigen jetzt senden** verwenden. Nur Verschieben ohne Übernahme speichert die neue Reihenfolge nicht.

Auf der Uhr erscheinen die eigenen aktiven Anzeigen als zusammenhängende Gruppe an der Position der ersten eigenen Anzeige. Andere Apps behalten ihre relative Reihenfolge und ihren Ein-/Aus-Zustand. Die Position von Uhrzeit und Datum innerhalb dieser Gruppe lässt sich derzeit nicht konfigurieren. Normale Wertaktualisierungen schreiben die Reihenfolge nicht erneut.

## 7. Standardanzeigen auswählen

Unter **Standardanzeigen der Uhr** die Steuerung über Symcon aktivieren. Uhrzeit, Datum, Gerätetemperatur, Luftfeuchtigkeit und Batterie nach Wunsch anhaken und **Übernehmen** drücken.

- Nur Uhrzeit: Alle Haken außer Uhrzeit entfernen.
- Uhrzeit und Datum: Diese beiden anhaken.
- Nur Symcon-Werte: Alle fünf abwählen, Steuerung aktiviert lassen.

Ohne aktivierte Steuerung verändert das Modul die Standardanzeigen nicht. Beim späteren Ausschalten der Steuerung bleiben die zuletzt gesetzten Zustände auf der Uhr erhalten. Aktive Steuerung setzt abweichende Änderungen aus der AWTRIX-Oberfläche beim nächsten Abgleich wieder auf die Symcon-Auswahl zurück.

## 8. Aktualisierung und Fehlerhilfe

Werte werden bei Änderungen, beim Übernehmen und regelmäßig übertragen. Standardintervall: 60 Sekunden. Nach einem Uhrneustart erscheinen die Anzeigen bei der nächsten Übertragung erneut. Nach drei Intervallen ohne Aktualisierung markiert AWTRIX den Wert als veraltet. Das erkennt fehlende Übertragung, nicht einen ausgefallenen Sensor, dessen Wert in Symcon unverändert bleibt.

| Problem | Prüfen |
|---|---|
| Uhr nicht erreichbar | IP, WLAN, Webport und Erreichbarkeit vom Symcon-Server |
| HTTP 401 | Webserver-Benutzername und Passwort |
| Icon fehlt | Auf neuer Uhr installieren, Namen prüfen und Icon-Auswahl neu laden |
| Anzeige fehlt im Regel-Dropdown | Anzeigenliste zuerst übernehmen |
| „Anzeige fehlt“ in einer Regel | Kennung wurde entfernt oder umbenannt; Regel neu zuordnen |
| Reihenfolge unverändert | Änderungen übernehmen und danach „Anzeigen jetzt senden“ drücken |
| Modul meldet Fehler | Variable „Letzter Fehler“ unter der Instanz lesen |

Vor dem Wechsel auf eine andere Uhr oder dem Löschen der Instanz alle Anzeigen deaktivieren und erfolgreich übertragen, wenn die alten Anzeigen entfernt werden sollen. Beim Hostwechsel löscht das Modul nichts auf der bisherigen Uhr.

## 9. Benachrichtigungen aus PHP

```php
// Instanz-ID anpassen.
AWNG_Notify(12345, 'Waschmaschine fertig!', '', 10);
AWNG_UpdateDisplays(12345);       // Werte senden
AWNG_UpdateDisplays(12345, true); // Werte und gespeicherte Reihenfolge senden
```

`AWNG_TestConnection(12345)` testet die Verbindung. `AWNG_LoadIcons(12345)` aktualisiert die Icon-Auswahl und lädt das Formular neu. `AWNG_GetIconsURL(12345)` liefert die Adresse der Icon-Verwaltung der Uhr.
