# AWTRIX NG in Symcon einrichten

[Zur README](../README.md) · [Änderungsverlauf](../CHANGELOG.md)

## 1. Modul installieren

In der Symcon-Modulverwaltung die Bibliothek `https://github.com/Sol-IoTiv/SymconAWTRIXNG` hinzufügen und **dev** auswählen. Dies ist der aktuelle Entwicklungs- und Testbranch. Symcon ab Version 7.0 und eine bereits im WLAN eingerichtete AWTRIX-NG-Uhr werden benötigt.

## 2. Uhr verbinden

Eine Instanz **AWTRIX NG** erstellen und die IP-Adresse oder den Hostnamen der Uhr eingeben. Bei geändertem Webport beispielsweise `192.168.1.50:8080` verwenden. Kein `http://` und keinen Pfad eingeben. Die IP-Adresse erscheint beim Start auf dem Display und ist auch in der Geräteliste des Routers zu finden.

Falls die Uhr eine Webserver-Anmeldung verlangt, Benutzername und Passwort im Abschnitt Anmeldung eintragen. **Übernehmen**, dann **Verbindung testen** und **Testnachricht** drücken.

Die frühere Gerätesuche ist ab 0.8 entfernt. Falls bereits eine Instanz **AWTRIX NG Gerätesuche** angelegt wurde, diese vor dem Modulupdate in Symcon löschen. Die Geräteinstanz **AWTRIX NG** mit den konfigurierten Anzeigen bleibt bestehen.

## 3. Anzeigen hinzufügen

Unter **Anzeigen** auf **Hinzufügen** klicken:

| Feld | Bedeutung |
|---|---|
| Aktiv | Zeile anzeigen; ausgeschaltete Zeilen bleiben konfiguriert |
| Kennung | Eindeutiger Name, etwa `warmwasser`; 1–16 Zeichen aus A–Z, a–z, 0–9, _ und - |
| Inhalt | Variablenwert, Laufschrift oder Countdown (Tage) |
| Variable | Nur für Variablenwert erforderlich |
| Freier Text | Text für Laufschrift |
| Enddatum | Nur für Countdown, als JJJJ-MM-TT |
| Laufschrift-Tempo | 1 bis 200 Prozent, Standard 100 |
| Text davor | Optionaler Text vor dem Wert |
| Einheit / Text danach | Beispielsweise ` °C`, ` kW` oder ` %`; Leerzeichen mit eingeben |
| Nachkommastellen | 0 bis 6; gilt für numerische Variablen |
| Icon | Ein bereits auf der Uhr installiertes Icon oder „Kein Icon“ |
| Dauer | Anzeigedauer von 1 bis 300 Sekunden |
| Textfarbe | Farbe des angezeigten Texts |

**Übernehmen** sendet die Anzeigen. Zahlen erhalten ein Dezimalkomma, boolesche Werte erscheinen als Ein/Aus und Strings unverändert. Symcon-Variablenprofile werden nicht automatisch übernommen.

## Laufschrift und Countdown

Beide werden als normale Zeilen unter **Anzeigen** angelegt und können gemeinsam mit Variablenwerten sortiert werden. Bestehende Zeilen bleiben Variablenanzeigen.

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
3. **Enddatum** als `JJJJ-MM-TT` eingeben, zum Beispiel `2026-12-24`.
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
AWNG_PlaySound(12345);            // ausgewählte Melodie
AWNG_PlaySound(12345, 'klingel'); // gespeicherter Melodienname ohne .txt
AWNG_StopSound(12345);           // laufenden Alert stoppen
AWNG_Notify(12345, 'Türklingel', '', 10, 'klingel');
```

`12345` durch die eigene Instanz-ID ersetzen. **Kein Ton** lässt Nachrichten mit `AWNG_NotifyWithSound` stumm; Probehören benötigt eine ausgewählte Melodie. Fehlende Melodien oder stumme Regler können dazu führen, dass eine Nachricht ohne hörbaren Ton erscheint. Die Liste wird getrennt je Uhr gespeichert; nach einem Hostwechsel erneut laden. Ruhezeiten, eigene RTTTL-Eingabe im Modul und automatische Ereignisregeln sind noch nicht enthalten.

## 4. Icons installieren

**Icon-Galerie öffnen (TC001)** öffnet die gefilterte Galerie auf awtrix.de. Im Hub anmelden, Icon öffnen und **Send to AWTRIX** verwenden. Die richtige Uhr auswählen bzw. verbinden. Der Browser muss die Uhr im lokalen Netzwerk erreichen können.

Alternativ **Icons auf der Uhr verwalten** verwenden und eigene Dateien über die AWTRIX-Oberfläche hinzufügen. Danach in Symcon **Icons von Uhr laden** drücken. Das Formular wird neu geladen; ungespeicherte Änderungen daher vorher übernehmen. Anschließend in der Anzeigenzeile das Icon auswählen.

Im Dropdown stehen die Dateinamen der installierten Icons ohne Erweiterung. Icons der alten Uhr werden nicht automatisch übertragen. Eine Bildvorschau im Symcon-Dropdown ist derzeit nicht enthalten.

## 5. Icons nach Wert wechseln

Im Abschnitt **Icons abhängig vom Wert** eine Regel hinzufügen. Im Dropdown die Anzeige auswählen; neue Anzeigen vorher übernehmen. Dann die Obergrenze und das Icon wählen.

Es gilt die kleinste passende Obergrenze einschließlich des Grenzwerts. Die Reihenfolge der Regeln ist unerheblich. Oberhalb aller Grenzen verwendet die Anzeige ihr normales Icon. Verglichen wird der ursprüngliche numerische Wert, unabhängig von der Textformatierung. Eine Regel mit „Kein Icon“ blendet das Icon für ihren Bereich aus.

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

## 6. Reihenfolge ändern

Zeilen in der Liste **Anzeigen** per Drag-and-drop verschieben und **Übernehmen** drücken. Alternativ nach dem Speichern **Anzeigen jetzt senden** verwenden. Nur Verschieben ohne Übernahme speichert die neue Reihenfolge nicht.

Auf der Uhr erscheinen die eigenen aktiven Anzeigen als zusammenhängende Gruppe an der Position der ersten eigenen Anzeige. Andere Apps behalten ihre relative Reihenfolge und ihren Ein-/Aus-Zustand. Die Position von Uhrzeit und Datum innerhalb dieser Gruppe lässt sich derzeit nicht konfigurieren. Normale Wertaktualisierungen schreiben die Reihenfolge nicht erneut.

## 7. Standardanzeigen auswählen

Unter **Standardanzeigen der Uhr** die Steuerung über Symcon aktivieren. Uhrzeit, Datum, Gerätetemperatur, Luftfeuchtigkeit und Batterie nach Wunsch anhaken und **Übernehmen** drücken.

- Nur Uhrzeit: Alle Haken außer Uhrzeit entfernen.
- Uhrzeit und Datum: Diese beiden anhaken.
- Nur Symcon-Werte: Alle fünf abwählen, Steuerung aktiviert lassen.

Ohne aktivierte Steuerung verändert das Modul die Standardanzeigen nicht. Beim späteren Ausschalten der Steuerung bleiben die zuletzt gesetzten Zustände auf der Uhr erhalten. Aktive Steuerung setzt abweichende Änderungen aus der AWTRIX-Oberfläche beim nächsten Abgleich wieder auf die Symcon-Auswahl zurück.

## Anzeige und Übergänge einstellen

1. IP-Adresse speichern und **Übergänge von Uhr laden** drücken. Das Formular wird neu geladen; Änderungen vorher übernehmen.
2. Unter **Anzeige und Übergänge** die Steuerung über Symcon einschalten.
3. Übergang auswählen, beispielsweise Fade, und Dauer festlegen: 500 Millisekunden = 0,5 Sekunden; 0 = sofortiger Wechsel.
4. Bewegungsrichtung und automatischen Anzeigenwechsel wählen. Bei ausgeschaltetem Wechsel erfolgt der Wechsel nur per Taste oder API.
5. Helligkeitsautomatik über Lichtsensor aktivieren oder manuell 0 bis 255 wählen. Ohne Lichtsensor die Automatik ausschalten.
6. **Übernehmen** drücken. Später kann **Anzeigen jetzt senden** die gespeicherten Einstellungen erneut abgleichen.

Die Einstellungen gelten für alle Anzeigen auf der Uhr. Nur geänderte Werte werden geschrieben. Normale Wertaktualisierungen ändern sie nicht. Ohne aktivierte Steuerung bleibt die Uhrkonfiguration erhalten; beim späteren Ausschalten bleiben die zuletzt übertragenen Einstellungen bestehen. Ein Wechsel der Bewegungsrichtung verändert die Reihenfolge nicht. Die Dauer jeder eigenen Anzeige wird weiterhin in ihrer Anzeigenzeile festgelegt.

## 8. Aktualisierung und Fehlerhilfe

Werte werden bei Änderungen, beim Übernehmen und regelmäßig übertragen. Das Feld **Alle Anzeigen erneut senden (Sekunden)** legt das zusätzliche Sendeintervall fest, standardmäßig 60 Sekunden. Wertänderungen werden unabhängig davon sofort gesendet. Die Anzeigedauer je Eintrag wird separat in der Anzeigenliste eingestellt. Nach einem Uhrneustart erscheinen die Anzeigen bei der nächsten Übertragung erneut. Nach drei Intervallen ohne Aktualisierung markiert AWTRIX den Wert als veraltet. Das erkennt fehlende Übertragung, nicht einen ausgefallenen Sensor, dessen Wert in Symcon unverändert bleibt.

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

`AWNG_LoadTransitions(12345)` lädt die Übergangsauswahl. `AWNG_TestConnection(12345)` testet die Verbindung. `AWNG_LoadIcons(12345)` aktualisiert die Icon-Auswahl und lädt das Formular neu. `AWNG_GetIconsURL(12345)` liefert die Adresse der Icon-Verwaltung der Uhr.

## Entwicklung unterstützen

Den freiwilligen Spendenbutton und QR-Code findest du in der [README](../README.md#entwicklung-unterstützen).
