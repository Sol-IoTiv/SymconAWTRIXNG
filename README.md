# AWTRIX NG für IP-Symcon

Mit diesem Modul zeigt eine AWTRIX-NG-Uhr Werte und Meldungen aus IP-Symcon an. Die Verbindung läuft lokal über HTTP; ein MQTT-Broker ist nicht erforderlich. Die Grundfunktionen werden auf einer Ulanzi TC001 im laufenden Betrieb getestet. AWTRIX 3 wird nicht unterstützt.

Aktueller Entwicklungsstand: **0.7** auf `dev`. Variablenanzeige, Standardanzeigen, Galerie-Zugang und Reihenfolge wurden im Praxistest bestätigt. Die automatische PHP-Testsuite und die Gerätesuche sind noch nicht abschließend geprüft. Es gibt noch keinen freigegebenen Stable-Release.

## Funktionen

- Gerätesuche im lokalen Netz oder manuelle Eingabe von IP-Adresse und Port.
- Symcon-Variablen mit Text, Einheit, Nachkommastellen, Farbe, Icon und Anzeigedauer.
- Aktualisierung bei Wertänderungen und regelmäßig; veraltete Werte werden markiert.
- Standardanzeigen einzeln auswählen: Uhrzeit, Datum, Temperatur, Luftfeuchtigkeit und Batterie.
- AWTRIX-Icon-Galerie öffnen, Icons auf der Uhr verwalten und in Symcon auswählen.
- Dynamische Icon-Regeln, etwa für PV-Erzeugung und Akkustand.
- Anzeigen per Drag-and-drop sortieren und die Reihenfolge auf die Uhr übertragen.
- Verbindungstest, Testnachricht und Benachrichtigungen aus PHP-Skripten.

[Änderungsverlauf](CHANGELOG.md) · [Ausführliche Anleitung](docs/ANLEITUNG.md)

## Entwicklungsstände

| Branch | Verwendung |
|---|---|
| `dev` | Aktuelle Entwicklung und Praxistests |
| `beta-staging` | Teststand nach ausdrücklicher Freigabe per Pull Request |
| `main` | Für freigegebene Versionen vorgesehen; derzeit noch Ausgangsstand |

Neue Änderungen werden nur auf `dev` hochgeladen. Übernahmen nach `beta-staging` oder `main` erfolgen nach Freigabe über Pull Requests. Die Version in `library.json` beschreibt den jeweiligen Modulstand, noch keinen Store-Release.

## Einrichtung

### Mit Gerätesuche

Instanz **AWTRIX NG Gerätesuche** erstellen und **Geräte suchen** drücken. In der Ergebnisliste die Uhr auswählen und die zugehörige AWTRIX-NG-Instanz erstellen. Die IP-Adresse wird übernommen. Danach mit Verbindungstest, Icon-Auswahl und Anzeigen fortfahren.

Die Suche läuft auf dem Symcon-Server und verwendet das [AWTRIX-NG-Discovery-Protokoll](https://blueforcer.github.io/awtrix-ng/esp32/getting-started/discovery/): eine UDP-Broadcast-Anfrage an Port 4210, Antworten für zwei Sekunden auf Port 4211. Sie verändert keine Einstellungen der Uhr. Bei mehreren Netzwerkkarten kann eine gezielte Broadcast-Adresse nötig sein (zum Beispiel `192.168.98.255` bei einem Netz `192.168.98.0/24`). Eine geänderte Broadcast-Adresse zuerst übernehmen. Broadcasts funktionieren üblicherweise nicht über VLAN-Grenzen oder Gastnetze; die manuelle IP-Eingabe bleibt verfügbar. Port 4211 muss frei sein. Gefundene Geräte werden anhand ihrer Discovery-Antwort gelistet; der HTTP-Verbindungstest erfolgt anschließend in der Geräteinstanz.

### Mit manueller IP-Adresse

1. In der Symcon-Modulverwaltung `https://github.com/Sol-IoTiv/SymconAWTRIXNG` hinzufügen und für die aktuellen Funktionen den Branch **`dev`** auswählen. Anschließend bei Änderungen in der Modulverwaltung aktualisieren.
2. Instanz vom Typ **AWTRIX NG** erstellen.
3. IP-Adresse oder Hostname der bereits ins WLAN eingebundenen Uhr eintragen, beispielsweise `192.168.98.50`. Kein `http://` und kein Pfad. Optional ist ein Port möglich.
4. Falls in AWTRIX ein Webserver-Login aktiviert ist, dessen Benutzername und Passwort eintragen. Änderungen **übernehmen**.
5. **Verbindung testen** und **Testnachricht** verwenden.
6. **Icon-Galerie öffnen (TC001)** drücken. Der Knopf öffnet den AWTRIX Hub mit dem Filter ESP32 / 32×8. Im Hub anmelden, ein Icon öffnen und **Send to AWTRIX** verwenden. Dabei die richtige Uhr auswählen bzw. verbinden. Alternativ **Icons auf der Uhr verwalten** öffnen und dort Icons hinzufügen. Danach im Symcon-Modul **Icons von Uhr laden** drücken. Die Auswahl enthält die gespeicherten Icons; eine Bildvorschau ist noch nicht implementiert.
7. In **Anzeigen** eine Zeile hinzufügen: eindeutige Kennung (z. B. `aussen`), Variable, Icon, Nachkommastellen, Einheit und Dauer auswählen. Wieder **übernehmen**.

Beispiel: Variable `22180`, Kennung `aussen`, Nachkommastellen `1`, Einheit ` °C`, Dauer `5`. Der Wert 18.4 erscheint als `18,4 °C`. Einheiten werden exakt angehängt; gewünschte Leerzeichen daher mit eingeben. Boolesche Variablen erscheinen als Ein/Aus, Stringvariablen unverändert. Variablenprofile werden nicht automatisch übernommen.

Die Kennung ist im Hinzufügen- und Bearbeiten-Dialog einer Anzeige ein Pflichtfeld: 1–16 Zeichen, erlaubt sind A–Z, a–z, 0–9, Unterstrich und Bindestrich. Leere Kennungen und Leerzeichen werden durch die Formularvalidierung abgewiesen. In Icon-Regeln wird die Anzeige über ein Dropdown ausgewählt. Neue oder umbenannte Anzeigen zuerst übernehmen, damit die Auswahl aktualisiert wird. Auch deaktivierte Anzeigen sind auswählbar. Verweise auf entfernte Anzeigen bleiben mit „Anzeige fehlt“ sichtbar und können korrigiert oder gelöscht werden. Ohne angelegte Anzeigen ist das Hinzufügen von Icon-Regeln nicht verfügbar. Doppelte Anzeigenkennungen werden zusätzlich beim Übernehmen durch das Modul geprüft.

## Verhalten

### Icons abhängig vom Wert

Im Abschnitt **Icons abhängig vom Wert** Regeln mit der gleichen Kennung wie die Anzeige hinzufügen. Jede Regel enthält eine Obergrenze und ein Icon. Die kleinste Obergrenze, die größer oder gleich dem Variablenwert ist, gewinnt; die Reihenfolge der Zeilen spielt keine Rolle. Oberhalb aller Grenzen gilt das normale Icon aus der Anzeigenliste. Regeln verwenden den ungerundeten numerischen Variablenwert, nicht den angezeigten Text. Für String- und boolesche Variablen bleibt das normale Icon aktiv. Eine Regel mit „Kein Icon“ blendet das Icon in diesem Bereich aus. Änderungen übernehmen.

**PV:** Anzeige `pv`, normales Icon `pv` (bisher beispielsweise `53562`). Eine Regel `pv` / `0` / `no_pv` (bisher `58526`) zeigt bei null und negativen Werten das Nacht-Icon, bei positiven Werten das PV-Icon. Die Namen bzw. IDs müssen auf der neuen Uhr tatsächlich vorhanden sein.

**Hausakku:** Anzeige `hausakku`, normales Icon `akku100` (bisher `56566`). Regeln:

| Kennung | Obergrenze einschließlich | Icon aus deinem bisherigen Skript |
|---|---:|---|
| hausakku | 10 | akku00 / 58975 |
| hausakku | 20 | akku20 / 56562 |
| hausakku | 40 | akku40 / 56563 |
| hausakku | 60 | akku60 / 56564 |
| hausakku | 80 | akku80 / 56565 |

Über 80 % wird das normale Icon `akku100` verwendet. Die Tabelle reproduziert die Schwellen deines bisherigen Skripts. Icons zuerst auf der NG-Uhr installieren und **Icons von Uhr laden** verwenden. Alte LaMetric-IDs sind Beispiele; entscheidend sind die Dateinamen auf der neuen Uhr. Regeln bleiben bei vorübergehend deaktivierten Anzeigen gespeichert und wirken wieder, sobald die Anzeige mit derselben Kennung aktiviert wird.

### AWTRIX-Hub-Galerie

Die Galerie wird als externe Webseite geöffnet und nicht in das Symcon-Formular eingebettet. Der Hub übernimmt Anmeldung, Auswahl und Übertragung auf die Uhr. Symcon speichert dafür weder Hub-Passwort noch Hub-Schlüssel. Der Browser muss die Uhr im lokalen Netzwerk erreichen können. Die im Modul gespeicherten HTTP-Zugangsdaten werden nicht an den Hub übergeben. Der Galerie-Knopf ist für die TC001 (ESP32, 32×8) gefiltert. Nach erfolgreichem Import **Icons von Uhr laden** drücken; das lädt das Formular neu, deshalb vorher Änderungen übernehmen.

### Standardanzeigen auswählen

Im Abschnitt **Standardanzeigen der Uhr** die Option **Standardanzeigen über Symcon steuern** aktivieren. Anschließend Uhrzeit, Datum, Gerätetemperatur, Luftfeuchtigkeit und Batteriestand einzeln anhaken und **Übernehmen** drücken. Beispiel: Nur Uhrzeit anhaken, um die Uhrzeit zusammen mit den eigenen Symcon-Anzeigen zu zeigen. Uhrzeit und Datum anhaken, um beide zu behalten. Alle fünf abwählen, um ausschließlich eigene Anzeigen zu verwenden.

Die Steuerung ist nach Installation und Aktualisierung zunächst ausgeschaltet. Dann bleiben die Einstellungen der Uhr unverändert. Wird die Steuerung später ausgeschaltet, bleiben die zuletzt gesetzten Zustände auf der Uhr bestehen. Bei aktiver Steuerung gleicht das Modul regelmäßig ab; nur abweichende Schalter werden geschrieben. Es berücksichtigt nur vorhandene eingebaute Apps, keine fremden Skripte oder zusätzlichen Anzeigen. Die Einstellungen werden von AWTRIX über Neustarts hinweg gespeichert.

Jede aktive Zeile erzeugt eine eigene Pushed App. Namen beginnen mit `ips_<InstanzID>_`; die Kennung muss innerhalb der Instanz eindeutig sein. Bestehende Uhr-Apps werden nicht überschrieben. Die Anzeigenliste lässt sich per Drag-and-drop umsortieren. **Übernehmen** oder **Anzeigen jetzt senden** überträgt die gespeicherte Reihenfolge auf die Uhr. Die eigenen Anzeigen werden als zusammenhängende Gruppe an der Position der ersten eigenen Anzeige eingeordnet. Andere Apps behalten ihre relative Reihenfolge und ihren Ein-/Aus-Zustand. Standardanzeigen können weiterhin separat aktiviert werden; ihre Position wird hier nicht konfiguriert. Regelmäßige Wertaktualisierungen schreiben die Reihenfolge nicht erneut.

Aktualisiert wird bei Wertänderungen, beim Übernehmen und regelmäßig (Standard 60 Sekunden). Damit erscheinen Anzeigen nach einem Neustart der Uhr erneut. Nach drei Aktualisierungsintervallen ohne erfolgreiche Übertragung markiert NG den Wert als veraltet. Deaktivierte oder entfernte Zeilen werden beim nächsten erfolgreichen Abgleich von der Uhr gelöscht. Nur vom Modul erfasste eigene Namen werden gelöscht; ein Hostwechsel löscht keine Anzeigen auf der bisherigen Uhr. Vor Hostwechsel oder Löschen der Instanz die Zeilen deaktivieren und erfolgreich synchronisieren, wenn die Anzeigen dort entfernt werden sollen.

Bei Verbindungs- oder Konfigurationsfehlern zeigt die Instanz einen Fehlerstatus und die Variable **Letzter Fehler** enthält Details. IP-Symcon muss die Uhr per HTTP erreichen können. Empfehlenswert ist eine feste DHCP-Zuordnung im Router. Die bestehende AWTRIX-3-MQTT-Anbindung kann parallel unverändert betrieben werden.

## Benachrichtigungen aus Skripten

```php
// 12345 durch die ID der AWTRIX-NG-Instanz ersetzen.
AWNG_Notify(12345, 'Waschmaschine fertig!', '', 10);
AWNG_UpdateDisplays(12345);
```

`AWNG_TestConnection(12345)` liefert den Verbindungstest als Text, `AWNG_LoadIcons(12345)` lädt die Icon-Auswahl. Senden und Aktualisieren liefern einen booleschen Erfolgswert.

## Geplante Erweiterungen

- Gemeinsame Anzeige für Bezug/Einspeisung aus zwei Variablen.
- Icon-Vorschau und vereinfachte Icon-Installation.
- Weitere Formatierungsoptionen und frei platzierbare Standardanzeigen.
- Später Prüfung für den Symcon Module Store.

## Technische Referenz

IP-Symcon ab 7.0 mit PHP/cURL. Verwendet die AWTRIX-NG-API v1: `GET /api/v1/version`, `GET /api/v1/apps`, `GET /api/v1/files?dir=/ICONS`, `PUT /api/v1/apps/pushed/{name}`, `PUT /api/v1/apps/{name}/enabled`, `PUT /api/v1/apps/order`, `DELETE /api/v1/apps/{name}` und `POST /api/v1/notifications`. Für die Gerätesuche werden zusätzlich UDP-Ports 4210 und 4211 verwendet.

- [AWTRIX NG Dokumentation](https://blueforcer.github.io/awtrix-ng/esp32/)
- [HTTP API](https://blueforcer.github.io/awtrix-ng/esp32/reference/http/)
- [Pushed Apps](https://blueforcer.github.io/awtrix-ng/esp32/guides/pushed-apps/)

Eigenständige Integration; kein offizielles Modul von Symcon, Ulanzi oder Blueforcer. Keine Firmware, fremden Icons oder Zugangsdaten enthalten.

## Entwicklung unterstützen

Wenn du die Weiterentwicklung unterstützen möchtest, kannst du das gerne tun:

[![Spenden mit PayPal](assets/paypal_logo.png)](https://www.paypal.com/donate/?business=PR9P7V7RMFHFQ&no_recurring=0&item_name=Spende+als+Dankesch%C3%B6n+f%C3%BCr+die+Modulentwicklung+Symcon&currency_code=EUR)

![QR-Code zur PayPal-Spende](assets/paypal_qr.png)

☕ Vielen Dank für deine Unterstützung!
