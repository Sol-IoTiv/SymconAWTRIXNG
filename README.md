# AWTRIX NG für IP-Symcon

Mit diesem Modul zeigt eine AWTRIX-NG-Uhr Werte und Meldungen aus IP-Symcon an. Die Verbindung läuft lokal über HTTP; ein MQTT-Broker ist nicht erforderlich. Die Grundfunktionen werden auf einer Ulanzi TC001 im laufenden Betrieb getestet. AWTRIX 3 wird nicht unterstützt.

Aktueller Entwicklungsstand: **0.16** auf `dev`. Variablenanzeige, Standardanzeigen, Galerie-Zugang und Reihenfolge wurden im Praxistest bestätigt. PHP-Syntaxprüfung und lokale Modultests mit nachgebildeter Symcon- und HTTP-Umgebung bestehen. Die neuen Übergangs- und Helligkeitsoptionen müssen noch an der Uhr geprüft werden. Es gibt noch keinen freigegebenen Stable-Release.

## Funktionen

- Einrichtung über IP-Adresse oder Hostname, optional mit Webport.
- Symcon-Variablen mit Text, Einheit, Nachkommastellen, Farbe, Icon und Anzeigedauer.
- Freier Text als kontinuierliche Laufschrift und Countdown bis zu einem Enddatum.
- Gespeicherte Melodien auswählen, probehören, stoppen und mit Nachrichten senden.
- Gesamtlautstärke über Symcon steuern oder stummschalten.
- Aktualisierung bei Wertänderungen und regelmäßig; veraltete Werte werden markiert.
- Standardanzeigen einzeln auswählen: Uhrzeit, Datum, Temperatur, Luftfeuchtigkeit und Batterie.
- AWTRIX-Icon-Galerie öffnen, Icons auf der Uhr verwalten und in Symcon auswählen.
- Dynamische Icon-Regeln, etwa für PV-Erzeugung und Akkustand.
- Übergänge, automatischen Anzeigenwechsel und Helligkeit einstellen.
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

Ab Version 0.8 ist die Discovery-Suche entfernt. Bereits angelegte Instanzen **AWTRIX NG Gerätesuche** vor dem Modulupdate löschen; die Geräteinstanz **AWTRIX NG** beibehalten.

### Mit IP-Adresse oder Hostname

1. In der Symcon-Modulverwaltung `https://github.com/Sol-IoTiv/SymconAWTRIXNG` hinzufügen und für die aktuellen Funktionen den Branch **`dev`** auswählen. Anschließend bei Änderungen in der Modulverwaltung aktualisieren.
2. Instanz vom Typ **AWTRIX NG** erstellen.
3. IP-Adresse oder Hostname der bereits ins WLAN eingebundenen Uhr eintragen, beispielsweise `192.168.98.50`. Kein `http://` und kein Pfad. Optional ist ein Port möglich.
4. Falls in AWTRIX ein Webserver-Login aktiviert ist, dessen Benutzername und Passwort eintragen. Änderungen **übernehmen**.
5. **Verbindung testen** und **Testnachricht** verwenden.
6. **Icon-Galerie öffnen (TC001)** drücken. Der Knopf öffnet den AWTRIX Hub mit dem Filter ESP32 / 32×8. Im Hub anmelden, ein Icon öffnen und **Send to AWTRIX** verwenden. Dabei die richtige Uhr auswählen bzw. verbinden. Alternativ **Icons auf der Uhr verwalten** öffnen und dort Icons hinzufügen. Danach im Symcon-Modul **Icons von Uhr laden** drücken. Die Auswahl enthält die gespeicherten Icons; eine Bildvorschau ist noch nicht implementiert.
7. In **Anzeigen** eine Zeile hinzufügen: eindeutige Kennung (z. B. `aussen`), Variable, Icon, Nachkommastellen, Einheit und Dauer auswählen. Wieder **übernehmen**.

Beispiel: Außentemperatur-Variable, Kennung `aussen`, Nachkommastellen `1`, Einheit ` °C`, Dauer `5`. Der Wert 18.4 erscheint als `18,4 °C`. Einheiten werden exakt angehängt; gewünschte Leerzeichen daher mit eingeben. Boolesche Variablen erscheinen als Ein/Aus, Stringvariablen unverändert. Variablenprofile werden nicht automatisch übernommen.

Die Kennung ist im Hinzufügen- und Bearbeiten-Dialog einer Anzeige ein Pflichtfeld: 1–16 Zeichen, erlaubt sind A–Z, a–z, 0–9, Unterstrich und Bindestrich. Leere Kennungen und Leerzeichen werden durch die Formularvalidierung abgewiesen. In Icon-Regeln wird die Anzeige über ein Dropdown ausgewählt. Neue oder umbenannte Anzeigen zuerst übernehmen, damit die Auswahl aktualisiert wird. Auch deaktivierte Anzeigen sind auswählbar. Verweise auf entfernte Anzeigen bleiben mit „Anzeige fehlt“ sichtbar und können korrigiert oder gelöscht werden. Ohne angelegte Anzeigen ist das Hinzufügen von Icon-Regeln nicht verfügbar. Doppelte Anzeigenkennungen werden zusätzlich beim Übernehmen durch das Modul geprüft.

## Laufschrift und Countdown

Beide werden als normale Zeilen unter **Anzeigen** angelegt und können gemeinsam mit Variablenwerten sortiert werden. Direkt unter **Kennung** steht **Inhalt**. Je nach Auswahl erscheint darunter nur die Variablenauswahl, das freie Textfeld oder der Kalender für das Enddatum. Die übrigen Eingaben sind ausgeblendet; gespeicherte Werte bleiben beim Umschalten erhalten. Tempo ist nur für Laufschrift sichtbar, Nachkommastellen nur für Variablenwerte. Bestehende Zeilen bleiben Variablenanzeigen.

### Freien Text als Laufschrift anzeigen

1. Eine Anzeige mit eindeutiger Kennung hinzufügen.
2. Unter **Inhalt** den Typ **Laufschrift** auswählen.
3. **Freier Text** ausfüllen. Eine Symcon-Variable wird dafür nicht benötigt.
4. Optional Icon, Textfarbe, Text davor und danach einstellen. **Laufschrift-Tempo (%)**: 100 entspricht dem normalen Tempo, 50 ist halb so schnell, 200 doppelt so schnell.
5. Genügend Anzeigedauer wählen und **Übernehmen** drücken.

Der Text läuft kontinuierlich durch, auch wenn er auf das Display passen würde. Die Dauer begrenzt den Aufenthalt in der Rotation; bei langem Text entsprechend mehr Zeit wählen. Die Felder Variable und Enddatum werden bei diesem Typ ignoriert.

### Verbleibende Tage anzeigen

1. Eine Anzeige mit eindeutiger Kennung hinzufügen.
2. Unter **Inhalt** den Typ **Countdown (Tage)** auswählen.
3. **Enddatum** im Kalender auswählen. Bereits früher als Text eingetragene Enddaten werden beim Bearbeiten in die Kalenderauswahl übernommen.
4. Optional Text davor wie `Urlaub in ` und als Einheit ` Tage` eintragen. Icon, Farbe und Dauer nach Wunsch wählen.
5. **Übernehmen** drücken.

Gezählt werden Kalendertage in der Zeitzone des Symcon-Servers, keine angebrochenen 24-Stunden-Blöcke. Morgen ergibt 1, das Enddatum selbst 0. Vergangene Daten bleiben bei 0; negative Werte werden nicht angezeigt. Sommer-/Winterzeit verändert die Tageszählung nicht. Der Wert erscheint immer ohne Nachkommastellen. Ungültige Daten werden abgewiesen. Der Countdown wird beim regelmäßigen Senden neu berechnet, nach Mitternacht spätestens beim nächsten Intervall. Variable und freier Text werden bei diesem Typ ignoriert. Icon-Regeln können auch den berechneten Tageswert verwenden.

## Töne und Lautstärke

1. In der Weboberfläche der Uhr unter **Audio → Melodies** eine kurze Melodie anlegen und speichern. Der Buzzer verwendet RTTTL-Melodien; MP3 oder Sprache werden über diese Funktion nicht unterstützt.
2. Im Modul **Melodien von Uhr laden** drücken. Ungespeicherte Änderungen vorher übernehmen, da das Formular neu geladen wird. Nur gültige gespeicherte Melodien werden übernommen.
3. Unter **Töne und Lautstärke** eine Melodie auswählen und **Übernehmen** drücken.
4. Mit **Ton probehören**, **Ton stoppen** und **Testnachricht mit Ton** testen. Stoppen beendet Alerts; Skriptmusik wird nicht gestoppt.

Die Lautstärkesteuerung ist zunächst ausgeschaltet. **Lautstärke über Symcon steuern** aktivieren, Gesamtlautstärke 0 bis 100 einstellen und übernehmen. 0 schaltet die Uhr stumm. Die Einstellung gilt für die gesamte Uhr; der zusätzliche Alerts-Regler in AWTRIX wirkt ebenfalls. Nur geänderte Lautstärke wird beim Übernehmen oder mit **Anzeigen jetzt senden** übertragen. Beim Ausschalten der Steuerung bleibt die zuletzt gesetzte Lautstärke bestehen.

Regelmäßige Anzeigenupdates lösen keine Töne aus. Bestehende Aufrufe von `AWNG_Notify` bleiben ohne Ton. Für die im Modul ausgewählte Melodie:

```php
AWNG_NotifyWithSound(12345, 'Waschmaschine fertig!', '', 10);
AWNG_PlaySound(12345, '');            // ausgewählte Melodie
AWNG_PlaySound(12345, 'klingel'); // gespeicherter Melodienname ohne .txt
AWNG_StopSound(12345);           // laufenden Alert stoppen
AWNG_NotifyWithMelody(12345, 'Türklingel', '', 10, 'klingel');
```

`12345` durch die eigene Instanz-ID ersetzen. **Kein Ton** lässt Nachrichten mit `AWNG_NotifyWithSound` stumm; Probehören benötigt eine ausgewählte Melodie. Fehlende Melodien oder stumme Regler können dazu führen, dass eine Nachricht ohne hörbaren Ton erscheint. Die Liste wird getrennt je Uhr gespeichert; nach einem Hostwechsel erneut laden. Ruhezeiten, eigene RTTTL-Eingabe im Modul und automatische Ereignisregeln sind noch nicht enthalten.

## Verhalten

### Icons abhängig vom Wert

Im Abschnitt **Icons abhängig vom Wert** Regeln mit der gleichen Kennung wie die Anzeige hinzufügen. Jede Regel enthält eine Obergrenze und ein Icon. Die kleinste Obergrenze, die größer oder gleich dem Variablenwert ist, gewinnt; die Reihenfolge der Zeilen spielt keine Rolle. Oberhalb aller Grenzen gilt das normale Icon aus der Anzeigenliste. Regeln verwenden den ungerundeten numerischen Variablenwert, nicht den angezeigten Text. Für String- und boolesche Variablen bleibt das normale Icon aktiv. Eine Regel mit „Kein Icon“ blendet das Icon in diesem Bereich aus. Änderungen übernehmen.

**PV-Erzeugung:** Wähle in der Anzeige das Icon für aktive Erzeugung als normales Icon. Ergänze eine Regel mit Obergrenze `0` und einem Icon für keine Erzeugung. Bei null oder negativen Werten erscheint das Ruhe-Icon, bei positiven Werten das Erzeugungs-Icon.

**Akkustand:** Wähle die Variable mit dem Ladezustand in Prozent. Als normales Icon verwendest du eine volle Batterie. Ergänze für dieselbe Anzeige folgende Regeln:

| Wert bis einschließlich | Gewünschtes Batterie-Icon |
|---:|---|
| 10 % | Leer |
| 20 % | Fast leer |
| 40 % | Etwa ein Drittel geladen |
| 60 % | Etwa halb geladen |
| 80 % | Weitgehend geladen |

Bei mehr als 80 % erscheint das normale Icon der vollen Batterie. Wähle jeweils ein passendes, auf der Uhr installiertes Icon aus dem Dropdown. Die Grenzen sind Beispiele und können angepasst werden. Regeln bleiben für deaktivierte Anzeigen gespeichert.

### Anzeige und Übergänge

Im Abschnitt **Anzeige und Übergänge** die Steuerung über Symcon aktivieren. Zuerst **Übergänge von Uhr laden** drücken; ungespeicherte Änderungen vorher übernehmen. Das Dropdown enthält die von dieser Uhr unterstützten Übergänge. Effekt, Bewegungsrichtung und Dauer auswählen. `500` Millisekunden entsprechen 0,5 Sekunden, `0` schaltet die Animation aus. Die Reihenfolge wird durch eine umgekehrte Bewegungsrichtung nicht verändert.

**Automatisch zur nächsten Anzeige wechseln** steuert die Rotation. Ohne Haken bleibt die aktuelle Anzeige bis zum Tastendruck oder einem API-Aufruf stehen. Die Anzeigedauer je Zeile bleibt unabhängig davon bestehen.

Die Helligkeit kann automatisch über den Lichtsensor oder manuell von 0 bis 255 geregelt werden. Der manuelle Wert wird nur bei ausgeschalteter Automatik übertragen. Ohne gemeldeten Lichtsensor muss die manuelle Steuerung gewählt werden.

**Übernehmen** oder **Anzeigen jetzt senden** gleicht diese Einstellungen ab. Nur abweichende Werte werden geschrieben; normale Wertaktualisierungen ändern die Einstellungen nicht. Die Optionen gelten für die gesamte Uhr und werden von AWTRIX gespeichert. Die Steuerung ist zunächst ausgeschaltet. Beim Ausschalten bleiben zuletzt gesetzte Einstellungen auf der Uhr erhalten.

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
AWNG_UpdateDisplays(12345, false);
```

`AWNG_TestConnection(12345)` liefert den Verbindungstest als Text, `AWNG_LoadIcons(12345)` lädt die Icon-Auswahl. Senden und Aktualisieren liefern einen booleschen Erfolgswert.

## Geplante Erweiterungen

- Gemeinsame Anzeige für Bezug/Einspeisung aus zwei Variablen.
- Icon-Vorschau und vereinfachte Icon-Installation.
- Weitere Formatierungsoptionen und frei platzierbare Standardanzeigen.
- Später Prüfung für den Symcon Module Store.

## Technische Referenz

IP-Symcon ab 7.0 mit PHP/cURL. Verwendet die AWTRIX-NG-API v1: `GET /api/v1/audio/melodies`, `POST /api/v1/audio/play`, `POST /api/v1/audio/stop`, `GET /api/v1/capabilities`, `GET /api/v1/settings`, `PATCH /api/v1/settings`, `GET /api/v1/version`, `GET /api/v1/apps`, `GET /api/v1/files?dir=/ICONS`, `PUT /api/v1/apps/pushed/{name}`, `PUT /api/v1/apps/{name}/enabled`, `PUT /api/v1/apps/order`, `DELETE /api/v1/apps/{name}` und `POST /api/v1/notifications`.

- [AWTRIX NG Dokumentation](https://blueforcer.github.io/awtrix-ng/esp32/)
- [HTTP API](https://blueforcer.github.io/awtrix-ng/esp32/reference/http/)
- [Pushed Apps](https://blueforcer.github.io/awtrix-ng/esp32/guides/pushed-apps/)

Eigenständige Integration; kein offizielles Modul von Symcon, Ulanzi oder Blueforcer. Keine Firmware, fremden Icons oder Zugangsdaten enthalten.

## Entwicklung unterstützen

Wenn du die Weiterentwicklung unterstützen möchtest, kannst du das gerne tun:

[![Spenden mit PayPal](imgs/paypal_logo.png)](https://www.paypal.com/donate/?business=PR9P7V7RMFHFQ&no_recurring=0&item_name=Spende+als+Dankesch%C3%B6n+f%C3%BCr+die+Modulentwicklung+Symcon&currency_code=EUR)

![QR-Code zur PayPal-Spende](imgs/paypal_qr.png)

☕ Vielen Dank für deine Unterstützung!

Bei globalen `AWNG_`-Aufrufen alle Parameter angeben: Symcon übernimmt PHP-Standardwerte nicht in die erzeugten Funktionen. Für reine Wertupdates `AWNG_UpdateDisplays(12345, false)` verwenden.

## Kompakte Konfigurationsübersicht

Die Anzeigenliste zeigt Aktiv, Kennung, Inhalt, Quelle/Text, Icon und Dauer. Quelle/Text enthält den Variablennamen, den Laufschrifttext oder das Countdown-Enddatum und wird nach Übernahme aktualisiert. Alle weiteren Einstellungen bleiben über das Zahnrad erreichbar und werden weiterhin gespeichert.

Die häufigen Aktionen Anzeigen jetzt senden, Verbindung testen und Testnachricht stehen nebeneinander. Weitere Knöpfe sind unter Icons laden und verwalten, Übergänge laden sowie Töne laden und testen gruppiert. Hinweise stehen unter Hilfe zur Einrichtung.
