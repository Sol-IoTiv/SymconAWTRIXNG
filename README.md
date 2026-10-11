# AWTRIX NG für IP-Symcon

Lokale HTTP-Anbindung für AWTRIX NG, insbesondere auf der Ulanzi TC001. Kein MQTT-Broker notwendig. **Version 0.1 ist ein erster Teststand, noch nicht auf einer echten Symcon-Installation geprüft.** AWTRIX 3 wird nicht unterstützt.

## Einrichtung

### Mit Gerätesuche

Instanz **AWTRIX NG Gerätesuche** erstellen und **Geräte suchen** drücken. In der Ergebnisliste die Uhr auswählen und die zugehörige AWTRIX-NG-Instanz erstellen. Die IP-Adresse wird übernommen. Danach mit Verbindungstest, Icon-Auswahl und Anzeigen fortfahren.

Die Suche läuft auf dem Symcon-Server und verwendet das [AWTRIX-NG-Discovery-Protokoll](https://blueforcer.github.io/awtrix-ng/esp32/getting-started/discovery/): eine UDP-Broadcast-Anfrage an Port 4210, Antworten für zwei Sekunden auf Port 4211. Sie verändert keine Einstellungen der Uhr. Bei mehreren Netzwerkkarten kann eine gezielte Broadcast-Adresse nötig sein (zum Beispiel `192.168.98.255` bei einem Netz `192.168.98.0/24`). Eine geänderte Broadcast-Adresse zuerst übernehmen. Broadcasts funktionieren üblicherweise nicht über VLAN-Grenzen oder Gastnetze; die manuelle IP-Eingabe bleibt verfügbar. Port 4211 muss frei sein. Gefundene Geräte werden anhand ihrer Discovery-Antwort gelistet; der HTTP-Verbindungstest erfolgt anschließend in der Geräteinstanz.

### Mit manueller IP-Adresse

1. In der Symcon-Modulverwaltung `https://github.com/Sol-IoTiv/SymconAWTRIXNG` hinzufügen und für den Praxistest den Branch `beta-staging` auswählen. `dev` enthält laufende Entwicklung, `main` ist für freigegebene Versionen vorgesehen. Der erste Stand auf allen Branches ist noch ein ungetesteter Entwicklungsstand.
2. Instanz vom Typ **AWTRIX NG** erstellen.
3. IP-Adresse oder Hostname der bereits ins WLAN eingebundenen Uhr eintragen, beispielsweise `192.168.98.50`. Kein `http://` und kein Pfad. Optional ist ein Port möglich.
4. Falls in AWTRIX ein Webserver-Login aktiviert ist, dessen Benutzername und Passwort eintragen. Änderungen **übernehmen**.
5. **Verbindung testen** und **Testnachricht** verwenden.
6. Icons im Icons-Bereich der AWTRIX-Weboberfläche installieren. Danach im Symcon-Modul **Icons von Uhr laden** drücken. Die Auswahl enthält die gespeicherten Icons; eine Bildvorschau oder ein Download aus dem Hub ist noch nicht implementiert.
7. In **Anzeigen** eine Zeile hinzufügen: eindeutige Kennung (z. B. `aussen`), Variable, Icon, Nachkommastellen, Einheit und Dauer auswählen. Wieder **übernehmen**.

Beispiel: Variable `22180`, Kennung `aussen`, Nachkommastellen `1`, Einheit ` °C`, Dauer `5`. Der Wert 18.4 erscheint als `18,4 °C`. Einheiten werden exakt angehängt; gewünschte Leerzeichen daher mit eingeben. Boolesche Variablen erscheinen als Ein/Aus, Stringvariablen unverändert. Variablenprofile werden nicht automatisch übernommen.

## Verhalten

Jede aktive Zeile erzeugt eine eigene Pushed App. Namen beginnen mit `ips_<InstanzID>_`; die Kennung muss innerhalb der Instanz eindeutig sein. Bestehende Uhr-Apps werden nicht überschrieben. Neue Anzeigen werden in der Reihenfolge der Tabelle angelegt; nachträgliches Umsortieren der Tabelle ändert die Rotation auf der Uhr noch nicht.

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

- Dynamische Akku-Icons, Bezug/Einspeisung und PV-Zustände.
- Icon-Vorschau und vereinfachte Icon-Installation.
- Anzeige-Reihenfolge und weitere Formatierungsoptionen.
- Später Prüfung für den Symcon Module Store.

## Technische Referenz

IP-Symcon ab 7.0 mit PHP/cURL. Verwendet die AWTRIX-NG-API v1: `GET /api/v1/version`, `GET /api/v1/files?dir=/ICONS`, `PUT /api/v1/apps/pushed/{name}`, `DELETE /api/v1/apps/{name}` und `POST /api/v1/notifications`.

- [AWTRIX NG Dokumentation](https://blueforcer.github.io/awtrix-ng/esp32/)
- [HTTP API](https://blueforcer.github.io/awtrix-ng/esp32/reference/http/)
- [Pushed Apps](https://blueforcer.github.io/awtrix-ng/esp32/guides/pushed-apps/)

Eigenständige Integration; kein offizielles Modul von Symcon, Ulanzi oder Blueforcer. Keine Firmware, fremden Icons oder Zugangsdaten enthalten.
