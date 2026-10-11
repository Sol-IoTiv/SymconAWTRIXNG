# Änderungen

Alle Einträge beschreiben Entwicklungsstände. Ein Upload auf `dev` ist keine Stable-Freigabe. Datum: 11. Oktober 2026.

## 0.8 – Einrichtung über IP-Adresse

- Discovery-Modul wegen unzuverlässiger Gerätesuche entfernt.
- Einrichtung ausschließlich über IP-Adresse oder Hostname der Uhr.
- Zugehörige Discovery-Tests und Workflow-Prüfung entfernt; Dokumentation angepasst.
- Bereits angelegte Discovery-Instanzen vor dem Modulupdate in Symcon löschen. Bestehende AWTRIX-NG-Geräteinstanzen bleiben erhalten.

## 0.7 – Reihenfolge

- Anzeigen in der Symcon-Liste per Drag-and-drop verschieben.
- Gespeicherte Reihenfolge beim Übernehmen oder über „Anzeigen jetzt senden“ auf die Uhr übertragen.
- Eigene Anzeigen als Gruppe an der Position der ersten eigenen Anzeige einordnen.
- Relative Reihenfolge und Ein-/Aus-Zustände anderer Apps erhalten.
- Keine wiederholten Reihenfolge-Schreibzugriffe bei normalen Wertaktualisierungen.

## 0.6 – Anzeigen in Icon-Regeln auswählen

- Dropdown mit den gespeicherten Anzeigenkennungen statt Freitext.
- Auswahl nach Übernahme neuer oder geänderter Anzeigen aktualisieren.
- Verweise auf entfernte Anzeigen mit „Anzeige fehlt“ kennzeichnen.
- Auch deaktivierte Anzeigen auswählbar; ohne Anzeigen keine neuen Regeln hinzufügen.

## 0.5 – Pflichtfeld Kennung

- Kennung im Anzeigen-Dialog validieren: 1–16 Buchstaben, Ziffern, Unterstriche oder Bindestriche.
- Leere Eingaben und Leerzeichen abweisen.
- Doppelte Anzeigenkennungen weiterhin beim Übernehmen prüfen.

## 0.4 – Dynamische Icons

- Icon anhand numerischer Obergrenzen auswählen; kleinste passende Grenze gewinnt.
- Normales Anzeigen-Icon als Rückfall oberhalb aller Grenzen verwenden.
- PV-Beispiel mit eigenem Icon bei null oder negativer Leistung.
- Akku-Beispiel mit Grenzen bei 10, 20, 40, 60 und 80 Prozent.
- Doppelte Obergrenzen derselben Anzeige abweisen.
- Ungerundeten Variablenwert vergleichen; String und Boolean behalten das normale Icon.

## 0.3 – Icon-Galerie

- Gefilterte AWTRIX-Hub-Galerie für TC001 / ESP32 / 32×8 aus dem Modul öffnen.
- Icon-Verwaltung der konfigurierten Uhr öffnen.
- Ablauf für Hub-Import und anschließendes Laden der Icon-Auswahl dokumentieren.
- Keine Hub-Zugangsdaten in Symcon speichern.

## 0.2 – Standardanzeigen

- Uhrzeit, Datum, Gerätetemperatur, Luftfeuchtigkeit und Batterie einzeln ein-/ausschalten.
- Steuerung über Symcon ausdrücklich aktivieren; bestehende Einstellungen zunächst erhalten.
- Nur abweichende Schalter auf der Uhr schreiben.
- Andere Skripte und Apps unverändert lassen.

## 0.1 – Grundfunktionen

- HTTP-Anbindung mit optionaler Basic-Anmeldung, ohne MQTT-Abhängigkeit.
- Pro Uhr eine Instanz; Anzeigen aus Symcon-Variablen konfigurieren.
- Text davor/danach, Nachkommastellen, Textfarbe, Icon und Dauer auswählen.
- Automatisch bei Wertänderung und regelmäßig aktualisieren.
- Eigene entfernte oder deaktivierte Anzeigen löschen; Zuordnung getrennt je Host speichern.
- Veraltete Werte durch AWTRIX markieren lassen.
- Verbindungstest, Testnachricht, Fehlerstatus und „Letzter Fehler“.
- Auf der Uhr installierte Icons laden und auswählen.
- Separate UDP-Gerätesuche mit Übernahme von Adresse und Port.
- PHP-Tests und GitHub-Prüfworkflow vorbereiten.

## Dokumentationspflege nach 0.7

- README an aktuellen Entwicklungsstand und vereinbarten Branch-Ablauf angepasst.
- Ausführliche Anleitung mit Einrichtung, Icon-Regeln, Reihenfolge und Fehlerhilfe ergänzt.
- Changelog für alle bisherigen Modulstände angelegt.
- PayPal-Spendenbutton und QR-Code in der README sowie einen Unterstützungslink im Modul ergänzt.
