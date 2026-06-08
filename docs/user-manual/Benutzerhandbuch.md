---
title: "Benutzerupload für Außenstellen — Benutzerhandbuch"
subtitle: "local_branchupload · Version 1.4.0"
author: "eLeDia GmbH · Christopher Reimann"
date: "Juni 2026"
lang: de
---

# Überblick

Das Moodle-Plugin **„Benutzerupload für Außenstellen"** (`local_branchupload`) ermöglicht es Verantwortlichen in den Außenstellen einer Organisation, Mitarbeiterlisten als CSV-Datei in Moodle hochzuladen — **ohne** dafür Administrator-Rechte zu benötigen. Die hochgeladenen Benutzer werden automatisch

- als Moodle-Benutzer angelegt (oder bestehende aktualisiert),
- der richtigen Außenstelle zugeordnet (als Kohorte),
- mit einer Organisationseinheit versehen (als benutzerdefiniertes Profilfeld),
- per E-Mail über ihre Zugangsdaten informiert.

Das Plugin wurde ursprünglich für den **Landkreis Ravensburg** entwickelt, ist aber für jede Organisation mit *Außenstellen-Struktur* geeignet — Behörde, Schule, Niederlassung, Tochtergesellschaft, Schulungsstandort.

## Sicherheitskonzept auf einen Blick

Jede Außenstelle darf **ausschließlich eigene Benutzer** verwalten:

- Die Außenstelle des Uploaders wird über ein **Profilfeld** im Moodle-Benutzerkonto bestimmt (Standardname „Behörde").
- Es können nur Benutzer für die **eigene** Außenstelle hochgeladen werden.
- Bestehende Benutzer einer **anderen** Außenstelle können nicht verändert werden.
- Auch das *Smuggeln* einer fremden Außenstellen-Kohorte über die Spalte `Kohorten` ist unterbunden.
- **Site-Administratoren** unterliegen keiner Einschränkung und können für alle Außenstellen hochladen.

## Was ist neu in 1.4.0?

- **Englische kanonische Schlüssel** (`email`, `branch`, `orgunit`, `lastname`, `firstname`, `remove`, `cohorts`, `oldemail`) als interne Bezeichner — was Sie als Anwender davon sehen, ist dass die Plugin-Konfigurationsschlüssel nun englisch sind. Die Standardüberschriften Ihrer CSV-Datei bleiben für deutsche Moodle-Instanzen unverändert deutsch (`Behörde`, `Organisationseinheit`, `Name`, `Vorname`, `Löschen`, `Kohorten`, `Alte_Email`).
- **Sprachabhängige Standardwerte:** Die Standard-Spaltenüberschriften werden aus der konfigurierten Site-Sprache (`$CFG->lang`) abgeleitet. Eine deutsche Moodle-Installation sieht die deutschen Bezeichner, eine englische die englischen (`Branch`, `OrgUnit`, `LastName`, `FirstName`, `Remove`, `Cohorts`, `OldEmail`) — automatisch und ohne Code-Änderung.
- **Automatische Migration:** Eine in Version 1.3.0 vorgenommene Anpassung der CSV-Spaltenbezeichnungen wird beim Update auf 1.4.0 automatisch übernommen (`db/upgrade.php`). Es ist keine manuelle Aktion erforderlich.

---

# Für den Administrator: Einrichtung des Plugins

> **Wichtig.** Die folgenden Schritte müssen **einmalig** durch einen Moodle-Administrator durchgeführt werden, bevor die Außenstellen den Upload nutzen können.

## Installation

1. Kopieren Sie den Ordner `branchupload` in das Verzeichnis `local/` Ihrer Moodle-Installation. Bei Moodle 5.x liegt dieses im neuen `public/`-Wurzelverzeichnis:

   ```
   {moodle_stammverzeichnis}/public/local/branchupload/
   ```

   Bei Moodle 4.x:

   ```
   {moodle_stammverzeichnis}/local/branchupload/
   ```

2. Melden Sie sich als Administrator an und navigieren Sie zu **Website-Administration → Systemnachrichten**. Moodle erkennt das neue Plugin automatisch und führt die Installation durch. Alternativ können Sie die Installation per Kommandozeile auslösen:

   ```bash
   php admin/cli/upgrade.php --non-interactive
   ```

3. Nach der Installation erscheint die Konfigurationsseite. Diese können Sie auch später jederzeit aufrufen unter **Website-Administration → Plugins → Lokale Plugins → Benutzerupload für Außenstellen**.

## Profilfelder anlegen

Das Plugin benötigt **zwei benutzerdefinierte Profilfelder**. Diese müssen angelegt werden, bevor das Plugin konfiguriert wird.

**Navigieren Sie zu:** Website-Administration → Nutzer/innen → Konten → Profilfelder

### Profilfeld 1: Außenstelle / Behörde

Dieses Feld bestimmt, welcher Außenstelle ein Benutzer zugeordnet ist.

| Einstellung | Empfohlener Wert |
|-------------|------------------|
| **Feldtyp** | Textfeld (oder Dropdown-Menü, wenn die Außenstellen feststehen) |
| **Kurzbezeichnung** | `branchoffice` |
| **Name** | Behörde |
| **Pflichtfeld** | Nein (wird vom Plugin automatisch gesetzt) |
| **Sichtbar für** | Für alle sichtbar |

> **Tipp bei Dropdown-Menü:** Geben Sie die Außenstellen als Optionen ein (eine pro Zeile), z. B.:
>
> ```
> GmndAchbrg
> GmndWangen
> GmndIsny
> StadtRV
> ```
>
> Die Werte müssen **exakt** den Kohorten-Kennungen entsprechen (siehe nächster Abschnitt).

### Profilfeld 2: Organisationseinheit

In dieses Feld wird die Abteilung bzw. Organisationseinheit aus der CSV-Datei gespeichert.

| Einstellung | Empfohlener Wert |
|-------------|------------------|
| **Feldtyp** | Textfeld |
| **Kurzbezeichnung** | `orgunit` |
| **Name** | Organisationseinheit |
| **Pflichtfeld** | Nein |
| **Sichtbar für** | Für alle sichtbar |

## Kohorten anlegen

Jede Außenstelle wird durch eine **Kohorte** repräsentiert. Die **Kohortenkennung** (`idnumber`) muss mit dem Wert in der CSV-Spalte `Behörde` übereinstimmen.

**Navigieren Sie zu:** Website-Administration → Nutzer/innen → Konten → Kohorten

Erstellen Sie für jede Außenstelle eine Kohorte:

| Einstellung | Beispielwert |
|-------------|--------------|
| **Name** | Gemeinde Achberg |
| **Kohortenkennung** | `GmndAchbrg` |
| **Kontext** | System |

Wiederholen Sie dies für alle Außenstellen, z. B.:

| Name | Kohortenkennung |
|------|-----------------|
| Gemeinde Achberg | `GmndAchbrg` |
| Gemeinde Wangen | `GmndWangen` |
| Gemeinde Isny | `GmndIsny` |
| Stadt Ravensburg | `StadtRV` |

> **Alternativ:** In den Plugin-Einstellungen können Sie *„Kohorten automatisch erstellen"* aktivieren. Dann werden fehlende Kohorten beim Upload automatisch angelegt. Dies empfiehlt sich jedoch nur in der Einrichtungsphase, nicht im laufenden Produktivbetrieb.

## Plugin-Einstellungen konfigurieren

**Navigieren Sie zu:** Website-Administration → Plugins → Lokale Plugins → Benutzerupload für Außenstellen

| Einstellung | Beschreibung | Empfehlung |
|-------------|--------------|------------|
| **Profil-Feld für Außenstelle** | Wählen Sie das zuvor angelegte Profilfeld „Behörde" aus der Dropdown-Liste. | `branchoffice` |
| **Profil-Feld für Organisationseinheit** | Wählen Sie das Profilfeld „Organisationseinheit" aus. | `orgunit` |
| **Kohorten automatisch erstellen** | Wenn aktiviert, werden Kohorten aus der CSV automatisch angelegt, falls sie noch nicht existieren. | Im Produktivbetrieb: **deaktiviert** |
| **Löschaktion** | Was passiert, wenn in der CSV-Spalte „Löschen" der Wert 1 steht. „Sperren" deaktiviert das Konto (wiederherstellbar). „Löschen" entfernt es dauerhaft. | **Sperren** (sicherer) |
| **Maximale Benutzer pro Upload** | Begrenzt die Anzahl der Zeilen pro Datei. `0` = unbegrenzt. | `500` |

### Optional: CSV-Spaltenbezeichnungen anpassen

Im Abschnitt **„CSV-Spaltenbezeichnungen"** können Sie alle acht Spaltenüberschriften umbenennen, damit sie zu Ihren bereits vorhandenen CSV-Dateien passen. Die intern verwendeten Schlüssel (englisch, ASCII-only) ändern sich dabei nie — was sich ändert, ist nur die *Zeichenkette*, die in der ersten Zeile Ihrer Datei stehen muss.

| Konfigurationsschlüssel | Pflicht? | Standard (deutsche Site) | Standard (englische Site) |
|-------------------------|:--------:|--------------------------|---------------------------|
| `local_branchupload/col_email`     | ja  | `Email`                | `Email`     |
| `local_branchupload/col_branch`    | ja  | `Behörde`              | `Branch`    |
| `local_branchupload/col_orgunit`   | ja  | `Organisationseinheit` | `OrgUnit`   |
| `local_branchupload/col_lastname`  | ja  | `Name`                 | `LastName`  |
| `local_branchupload/col_firstname` | ja  | `Vorname`              | `FirstName` |
| `local_branchupload/col_remove`    | nein | `Löschen`             | `Remove`    |
| `local_branchupload/col_cohorts`   | nein | `Kohorten`            | `Cohorts`   |
| `local_branchupload/col_oldemail`  | nein | `Alte_Email`          | `OldEmail`  |

Eigenschaften der Spalten­erkennung:

- **Groß-/Kleinschreibung wird ignoriert** (`Email`, `email`, `EMAIL` sind identisch).
- **Umgebende Leerzeichen werden ignoriert.**
- Ein **leeres Feld** in den Einstellungen stellt den für die Site-Sprache hinterlegten Standardwert wieder her.

Geänderte Bezeichnungen wirken sich automatisch auf folgende Stellen aus — ohne dass Sie Code ändern müssen:

- den **Hinweistext** im Upload-Formular oberhalb des Dateiauswahl-Buttons,
- den **Download** der Beispiel-CSV-Datei,
- die **Spaltentitel** in den Vorschau- und Ergebnis-Tabellen,
- die **Fehlermeldungen**, wenn eine Pflichtspalte in der hochgeladenen Datei fehlt.

> **Upgrade-Hinweis (1.3.0 → 1.4.0).** Falls Sie auf Version 1.3.0 bereits eigene Überschriften unter den damals deutschen Konfigurationsschlüsseln (`col_behoerde`, `col_orgeinheit`, `col_name`, `col_vorname`, `col_loeschen`, `col_kohorten`, `col_alte_email`) hinterlegt hatten, übernimmt `db/upgrade.php` diese Werte beim Update automatisch in die neuen englischen Schlüssel. Es ist keine manuelle Aktion erforderlich.

## Berechtigungen vergeben

Das Plugin bringt eine eigene Berechtigung mit: **`local/branchupload:upload`**.

Diese muss den Personen zugewiesen werden, die Benutzer hochladen dürfen. Es gibt zwei gleichwertige Wege:

### Option A: Über eine Rolle (empfohlen)

1. **Website-Administration → Nutzer/innen → Berechtigungen → Rollen verwalten**.
2. Erstellen Sie eine neue Rolle (z. B. „Außenstellen-Verwalter") oder bearbeiten Sie eine bestehende.
3. Unter **Berechtigungen filtern** suchen Sie nach `branchupload`.
4. Setzen Sie `local/branchupload:upload` auf **Erlauben**.
5. Weisen Sie diese Rolle den gewünschten Personen auf **Systemebene** zu: **Website-Administration → Nutzer/innen → Berechtigungen → Systemrollen zuweisen**.

### Option B: Direkt für einzelne Nutzer

1. **Website-Administration → Nutzer/innen → Berechtigungen → Systemrollen zuweisen**.
2. Wählen Sie die Rolle, die die Upload-Berechtigung enthält.
3. Fügen Sie die gewünschten Nutzer hinzu.

## Außenstellen-Zuordnung der Uploader

**Entscheidend:** Jeder Benutzer, der hochladen soll, muss selbst einen Wert im Profilfeld „Behörde" haben. Dieser Wert bestimmt, für welche Außenstelle er Benutzer anlegen darf.

1. Navigieren Sie zum Profil des Upload-Verantwortlichen.
2. Bearbeiten Sie das Profil und setzen Sie das Feld **Behörde** auf die entsprechende Kohortenkennung (z. B. `GmndAchbrg`).
3. **Ohne diesen Wert kann der Benutzer keine Uploads durchführen** — er erhält eine entsprechende Fehlermeldung.

> **Beispiel.** Frau Müller ist Verantwortliche der Gemeinde Achberg. In ihrem Profil steht *Behörde = GmndAchbrg*. Sie kann nur CSV-Dateien hochladen, in denen alle Zeilen den Wert `GmndAchbrg` in der Spalte `Behörde` haben.

---

# Für die Außenstellen: Benutzer hochladen

> **Diesen Abschnitt können Sie direkt an Ihre Außenstellen-Verantwortlichen weitergeben.**

## Die CSV-Datei vorbereiten

Die Benutzerliste wird als **CSV-Datei** (Textdatei mit Trennzeichen) hochgeladen. Sie können die Datei in Excel, LibreOffice Calc oder einem Texteditor erstellen.

### Wichtige Hinweise zur Dateierstellung

- **Trennzeichen:** Semikolon (`;`) ist die Standardeinstellung. Beim Upload kann auch ein anderes Trennzeichen gewählt werden.
- **Zeichenkodierung:** UTF-8 — damit Umlaute (ä, ö, ü) korrekt verarbeitet werden.
- **Erste Zeile = Spaltenüberschriften.** Die erste Zeile der Datei enthält die Spaltennamen (siehe unten).

> **Tipp für Excel.** Beim Speichern wählen Sie *„CSV UTF-8 (durch Trennzeichen getrennt)"*. Die deutsche Excel-Version verwendet standardmäßig Semikolon als Trennzeichen.

## Pflichtangaben

Jede CSV-Datei **muss** folgende Spalten enthalten (Standardüberschriften für eine deutsche Moodle-Site):

| Spalte | Beschreibung | Beispiel |
|--------|--------------|----------|
| **Email** | E-Mail-Adresse des Benutzers. Wird gleichzeitig als Moodle-Benutzername verwendet. | `max.mustermann@example.de` |
| **Behörde** | Kennung der Außenstelle. Muss mit der Kohortenkennung übereinstimmen. | `GmndAchbrg` |
| **Organisationseinheit** | Abteilung oder Organisationseinheit des Benutzers. | `Bauverwaltung` |
| **Name** | Nachname. | `Mustermann` |
| **Vorname** | Vorname. | `Max` |

## Optionale Angaben

Diese Spalten *können* zusätzlich angegeben werden, müssen aber nicht:

| Spalte | Beschreibung | Werte |
|--------|--------------|-------|
| **Löschen** | Benutzer sperren oder löschen. Wenn leer oder nicht vorhanden, passiert nichts. | `1`, `ja`, `yes` oder `true`. Leer lassen = normaler Import. |
| **Kohorten** | Zusätzliche Kohorten-Zuordnungen (neben der Behörde). Mehrere Kohorten mit senkrechtem Strich (`|`) trennen. | `SchulungA|SchulungB` |
| **Alte_Email** | Bisherige E-Mail-Adresse eines Benutzers, wenn sich seine E-Mail geändert hat. Der Benutzer wird über die alte E-Mail gefunden und dann mit der neuen E-Mail aus der Spalte `Email` aktualisiert. | `alte.adresse@example.de` |

> **Hinweis zu Kohorten.** Über die Spalte `Kohorten` können **keine Außenstellen-Kohorten** zugewiesen werden. Kohorten, die einer Außenstelle entsprechen (z. B. `GmndAchbrg`), dürfen ausschließlich über die Spalte `Behörde` zugeordnet werden. Dies verhindert, dass Benutzer über die Kohorten-Spalte einer fremden Außenstelle zugewiesen werden. Administratoren sind von dieser Einschränkung ausgenommen.

### So sieht eine vollständige CSV-Datei aus

```csv
Email;Behörde;Organisationseinheit;Name;Vorname;Löschen;Kohorten;Alte_Email
max.mustermann@example.de;GmndAchbrg;Bauverwaltung;Mustermann;Max;;SchulungA;
erika.neu@example.de;GmndAchbrg;Finanzen;Musterfrau;Erika;;SchulungA|SchulungB;erika.musterfrau@example.de
hans.beispiel@example.de;GmndAchbrg;Ordnungsamt;Beispiel;Hans;1;;
```

**Erklärung der Zeilen:**

- **Zeile 1:** Max Mustermann wird als Benutzer angelegt, der Kohorte `GmndAchbrg` zugeordnet und zusätzlich in die Kohorte `SchulungA` aufgenommen.
- **Zeile 2:** Erika Musterfrau hat ihre E-Mail geändert — sie wird über die alte Adresse `erika.musterfrau@example.de` gefunden und auf `erika.neu@example.de` aktualisiert. Zusätzlich wird sie den Kohorten `SchulungA` und `SchulungB` zugeordnet.
- **Zeile 3:** Hans Beispiel wird **gesperrt** (oder gelöscht, je nach Administrator-Einstellung), weil `Löschen = 1` gesetzt ist.

## Beispiel-CSV herunterladen

Auf der Upload-Seite finden Sie einen Link **„Beispiel-CSV-Datei"**. Laden Sie diese Datei herunter und verwenden Sie sie als Vorlage für Ihre eigene Benutzerliste.

Die Vorlage wird *dynamisch* aus Ihren aktuell konfigurierten Spaltenbezeichnungen erzeugt — wenn Ihre Administration die Standardbezeichnungen z. B. zu `EmailAddress;Site;Department;…` angepasst hat, enthält die heruntergeladene Datei genau diese Bezeichnungen.

## Schritt-für-Schritt: Upload durchführen

### Schritt 1 — Upload-Seite öffnen

Melden Sie sich in Moodle an. Sie finden den Link **„Benutzer hochladen"** an folgenden Stellen:

- in der **linken Navigationsleiste** (Seitenmenü),
- auf Ihrer **Profilseite**,
- falls Sie Administrator sind: unter **Website-Administration → Plugins → Lokale Plugins → Benutzer hochladen**.

### Schritt 2 — CSV-Datei hochladen

1. Klicken Sie auf **„Datei auswählen"** und wählen Sie Ihre CSV-Datei aus (oder ziehen Sie die Datei per Drag-and-Drop in das Upload-Feld).
2. **CSV-Trennzeichen:** Wählen Sie das Trennzeichen Ihrer Datei (Standard: Semikolon).
3. **Zeichenkodierung:** Belassen Sie die Einstellung auf UTF-8, es sei denn, Ihre Datei hat eine andere Kodierung.
4. Klicken Sie auf **„CSV hochladen"**.

### Schritt 3 — Vorschau prüfen

Nach dem Hochladen sehen Sie eine **Vorschau-Tabelle** mit allen Zeilen Ihrer Datei:

- **Oben** wird Ihre Außenstelle angezeigt (z. B. „Ihre Außenstelle: **GmndAchbrg**").
- Jede Zeile hat einen **Status-Badge**:
  - **Grün — Wird erstellt:** neuer Benutzer wird angelegt.
  - **Blau — Wird aktualisiert:** Benutzer existiert bereits, Daten werden aktualisiert.
  - **Gelb — Wird gesperrt:** Benutzer wird deaktiviert (Löschen-Spalte = 1).
  - **Rot — Fehler:** Diese Zeile hat ein Problem und wird übersprungen. Der Fehlergrund wird angezeigt.
- **Zusammenfassung:** Über der Tabelle sehen Sie die Gesamtzahl der Zeilen sowie wie viele gültig sind und wie viele Fehler haben.

> **Prüfen Sie die Vorschau sorgfältig!** Nur als gültig markierte Zeilen werden verarbeitet — fehlerhafte werden übersprungen.

### Schritt 4 — Upload bestätigen

- Wenn alles in Ordnung ist, klicken Sie auf **„Upload bestätigen"**.
- Wenn Sie Fehler bemerken, klicken Sie auf **„Abbrechen"**, korrigieren Sie Ihre CSV-Datei und laden Sie sie erneut hoch.

### Schritt 5 — Ergebnis prüfen

Nach der Verarbeitung sehen Sie eine **Ergebnis-Übersicht** mit den Kennzahlen *Erstellt*, *Aktualisiert*, *Gesperrt*, *Gelöscht*, *Übersprungen* und *Fehler* — sowie darunter eine detaillierte Tabelle mit dem Ergebnis jeder einzelnen Zeile.

> **Was passiert mit den Zugangsdaten?** Neu angelegte Benutzer erhalten ihre Zugangsdaten **automatisch per E-Mail**. Dies geschieht über den Moodle-Cron-Job und kann einige Minuten dauern. Die Benutzer werden beim ersten Login aufgefordert, ihr Passwort zu ändern.

---

# Häufige Fragen (FAQ)

### „Ich kann keine Benutzer hochladen — es kommt eine Fehlermeldung."

Mögliche Ursachen:

1. **Keine Berechtigung.** Ihr Administrator muss Ihnen die Berechtigung `local/branchupload:upload` zuweisen.
2. **Kein Außenstellen-Wert.** In Ihrem Profil ist das Feld „Behörde" nicht ausgefüllt. Bitten Sie Ihren Administrator, dies zu setzen.
3. **Plugin nicht konfiguriert.** Der Administrator muss in den Plugin-Einstellungen die Profilfelder auswählen.

### „Einige Zeilen werden als Fehler angezeigt."

Prüfen Sie die angezeigte Fehlermeldung. Häufige Gründe:

- **E-Mail-Adresse ungültig** — z. B. fehlendes `@`-Zeichen oder Tippfehler.
- **Behörde stimmt nicht überein** — die Spalte `Behörde` enthält einen anderen Wert als Ihre eigene Außenstelle.
- **Kohorte existiert nicht** — die angegebene Kohortenkennung ist im System nicht vorhanden und die automatische Erstellung ist deaktiviert.
- **Pflichtfeld leer** — `Name`, `Vorname`, `Email`, `Behörde` oder `Organisationseinheit` fehlt.

### „Was passiert, wenn ich eine Datei mit einem bestehenden Benutzer hochlade?"

Der Benutzer wird **aktualisiert** — Vorname, Nachname, Organisationseinheit und Kohorten-Zuordnungen werden auf die Werte aus der CSV-Datei gesetzt. Das **Passwort** wird *nicht* geändert.

### „Wie kann ich die E-Mail-Adresse eines Benutzers ändern?"

Verwenden Sie die optionale Spalte **`Alte_Email`**: Tragen Sie die neue E-Mail in die Spalte `Email` und die bisherige E-Mail in die Spalte `Alte_Email` ein. Der Benutzer wird über die alte E-Mail gefunden und auf die neue E-Mail (inklusive Benutzername) aktualisiert.

**Beispiel:**

```csv
Email;Behörde;Organisationseinheit;Name;Vorname;Alte_Email
neue.mail@example.de;GmndAchbrg;Bauverwaltung;Mustermann;Max;alte.mail@example.de
```

### „Kann ich Benutzer aus anderen Außenstellen bearbeiten?"

**Nein.** Sie können nur Benutzer bearbeiten, die Ihrer eigenen Außenstelle zugeordnet sind. Versuche, Benutzer einer anderen Außenstelle zu aktualisieren, werden mit einer Fehlermeldung abgewiesen.

### „Was bedeutet ‚Löschen' — wird der Benutzer komplett entfernt?"

Das hängt von der Einstellung des Administrators ab:

- **Sperren** (Standardeinstellung): Das Konto wird deaktiviert. Der Benutzer kann sich nicht mehr anmelden, aber die Daten bleiben erhalten. Das Konto kann später wieder aktiviert werden.
- **Löschen:** Das Konto wird dauerhaft entfernt. Dies kann nicht rückgängig gemacht werden.

### „Umlaute in meiner CSV-Datei werden falsch angezeigt."

Stellen Sie sicher, dass Ihre Datei in **UTF-8** gespeichert ist:

- **Excel:** Speichern als „CSV UTF-8 (durch Trennzeichen getrennt)".
- **LibreOffice:** Beim Export UTF-8 als Zeichensatz wählen.
- **Upload-Formular:** Wählen Sie die passende Zeichenkodierung (Standard: UTF-8).

### „Wann erhalten die Benutzer ihre Zugangsdaten?"

Neue Benutzer erhalten eine E-Mail mit Benutzername und Passwort, sobald der **Moodle-Cron-Job** gelaufen ist. Dies geschieht normalerweise innerhalb weniger Minuten nach dem Upload. Bei Problemen kontaktieren Sie Ihren Administrator.

### „Können wir die deutschen Spaltenüberschriften gegen englische austauschen?"

Ja. In den Plugin-Einstellungen unter *„CSV-Spaltenbezeichnungen"* können Sie jede Spaltenüberschrift einzeln umbenennen — z. B. von `Behörde` zu `Branch` oder zu `Site` oder wie auch immer Ihre Eingabedaten heißen. Alternativ: Stellen Sie die Site-Sprache (`$CFG->lang`) auf Englisch um, dann gelten automatisch die englischen Standardbezeichnungen `Branch / OrgUnit / LastName / FirstName / Remove / Cohorts / OldEmail` (siehe Abschnitt *„CSV-Spaltenbezeichnungen anpassen"*).

---

# Fehlermeldungen und ihre Bedeutung

| Fehlermeldung | Bedeutung | Lösung |
|---------------|-----------|--------|
| „Fehlende Pflichtspalten: …" | Die CSV-Datei enthält nicht alle erforderlichen Spaltenüberschriften. | Prüfen Sie die erste Zeile der Datei. Erforderlich sind die in den Plugin-Einstellungen konfigurierten Bezeichner — standardmäßig `Email`, `Behörde`, `Organisationseinheit`, `Name`, `Vorname`. |
| „Außenstelle stimmt nicht überein" | Die Behörde in der CSV-Zeile weicht von Ihrer eigenen Außenstelle ab. | Korrigieren Sie den Wert in der Spalte `Behörde` oder lassen Sie den Upload von der zuständigen Außenstelle durchführen. |
| „Kohorte existiert nicht" | Die angegebene Kohortenkennung wurde im System nicht gefunden. | Bitten Sie den Administrator, die Kohorte anzulegen, oder die automatische Erstellung zu aktivieren. |
| „Ungültige E-Mail-Adresse" | Das Format der E-Mail ist fehlerhaft. | Prüfen Sie die E-Mail-Adresse auf Tippfehler. |
| „E-Mail ist erforderlich" | Die Spalte `Email` ist in dieser Zeile leer. | Tragen Sie eine E-Mail-Adresse ein. |
| „Benutzer kann nicht aktualisiert werden — andere Außenstelle" | Der Benutzer gehört zu einer anderen Außenstelle als Ihrer. | Dieser Benutzer kann nur von seiner eigenen Außenstelle verwaltet werden. |
| „Die neue E-Mail-Adresse wird bereits verwendet" | Ein anderer Benutzer nutzt bereits die E-Mail, auf die gewechselt werden soll. | Prüfen Sie, ob die neue E-Mail korrekt ist. |
| „Ungültige bisherige E-Mail-Adresse" | Das Format der alten E-Mail in der Spalte `Alte_Email` ist fehlerhaft. | Prüfen Sie die alte E-Mail-Adresse. |
| „Die Kohorte entspricht einer Außenstelle und darf nicht über die Kohorten-Spalte zugewiesen werden" | In der Spalte `Kohorten` wurde eine Außenstellen-Kohorte angegeben. | Außenstellen-Kohorten dürfen nur über die Spalte `Behörde` zugewiesen werden. |
| „Profilfeld nicht konfiguriert" | Der Administrator hat das Plugin nicht vollständig eingerichtet. | Kontaktieren Sie Ihren Administrator. |
| „Kein Außenstellen-Wert hinterlegt" | In Ihrem eigenen Profil fehlt der Wert im Feld „Behörde". | Kontaktieren Sie Ihren Administrator, damit der Wert gesetzt wird. |
| „Die CSV-Datei enthält … Zeilen, das Maximum ist …" | Die Datei überschreitet das maximale Zeilenlimit. | Teilen Sie die Datei in mehrere kleinere Dateien auf. |
| „Fehler beim Lesen der CSV-Datei" | Die Datei konnte nicht verarbeitet werden. | Prüfen Sie das Dateiformat, die Zeichenkodierung und das Trennzeichen. |

---

# Technische Hinweise

## Systemvoraussetzungen

- **Moodle** 4.5 (build `2024100700`) oder neuer — inklusive Moodle 5.x.
- **PHP** 8.1, 8.2, 8.3 oder 8.4.
- **Datenbanken** MariaDB / MySQL und PostgreSQL (siehe CI-Matrix).

## Datensicherheit

- Hochgeladene CSV-Dateien werden **nicht dauerhaft gespeichert**. Die Daten werden ausschließlich temporär im Arbeitsspeicher von Moodles `csv_import_reader` verarbeitet und nach Abschluss gelöscht.
- Neue Benutzer erhalten automatisch generierte Passwörter per E-Mail. Die Passwörter werden ausschließlich gehasht in der Moodle-Datenbank gespeichert.
- Benutzer werden beim ersten Login zur Passwortänderung aufgefordert.

## Dateistruktur des Plugins

```
local/branchupload/
├── version.php                — Plugin-Version (1.4.0) und Abhängigkeiten
├── index.php                  — Upload-Seite (3 Schritte)
├── download_example.php       — Beispiel-CSV-Download (dynamisch)
├── lib.php                    — Navigations-Hooks
├── settings.php               — Administrator-Einstellungen
├── db/
│   ├── access.php             — Berechtigungsdefinitionen
│   └── upgrade.php            — Migration der Konfigurationsschlüssel
├── classes/
│   ├── column_config.php      — CSV-Spalten-Konfiguration
│   ├── form/upload_form.php   — Upload-Formular
│   ├── process.php            — Verarbeitungslogik
│   └── privacy/provider.php   — Datenschutz-API (null provider)
├── templates/
│   ├── preview.mustache       — Vorschau-Tabelle
│   └── results.mustache       — Ergebnis-Übersicht
├── lang/{en,de}/              — Sprachdateien (Englisch, Deutsch)
├── tests/                     — PHPUnit + Behat
├── docs/user-manual/          — Dieses Handbuch (DE + EN)
├── README.md                  — Technische Dokumentation (Englisch)
└── CHANGELOG.md               — Versionshistorie
```

## Tests ausführen

```bash
# Vom Moodle-Stammverzeichnis aus, nach einmaliger Initialisierung:
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit public/local/branchupload/tests/process_test.php
```

Für die Behat-Akzeptanztests siehe [README.md](../../README.md#behat).

## Datenschutz

Das Plugin ist ein *Null-Provider* nach der Moodle Privacy API: Es speichert selbst keine personenbezogenen Daten. Alle Benutzerdaten fließen ausschließlich durch die Moodle-Kern-APIs (`user_create_user`, `user_update_user`, `delete_user`, `profile_save_data`, `cohort_add_member`), die ihre eigenen Datenschutz-Anforderungen erfüllen.

Eine vollständige WCAG-2.2-Level-AA-Konformitätserklärung findet sich in [ACCESSIBILITY.md](../../ACCESSIBILITY.md).

## Lizenz und Kontakt

Dieses Plugin steht unter der **GNU GPL v3 oder neuer**.

**Copyright** © 2026 eLeDia GmbH, Berlin — [eledia.de](https://eledia.de).
**Autor:** Christopher Reimann — `christopher.reimann@eledia.de`.

Anfragen, Fehlerberichte und Verbesserungsvorschläge bitte an `info@eledia.de` oder direkt als GitHub-Issue im Plugin-Repository.
