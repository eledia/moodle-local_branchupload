<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * German language strings for local_branchupload.
 *
 * Strings are kept in alphabetical order, as required by the Moodle
 * coding style and enforced by moodle-plugin-ci.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['branchupload:upload'] = 'Benutzerlisten für Außenstellen per CSV hochladen';
$string['cohort_created'] = 'Kohorte "{$a}" erstellt.';
$string['col_default_branch'] = 'Behörde';
$string['col_default_cohorts'] = 'Kohorten';
$string['col_default_email'] = 'Email';
$string['col_default_firstname'] = 'Vorname';
$string['col_default_lastname'] = 'Name';
$string['col_default_oldemail'] = 'Alte_Email';
$string['col_default_orgunit'] = 'Organisationseinheit';
$string['col_default_remove'] = 'Löschen';
$string['csvcolumns_info'] = 'Erforderliche Spalten: {$a->email}, {$a->branch}, {$a->orgunit}, {$a->lastname}, {$a->firstname}. Optionale Spalten: {$a->remove} (1 = Benutzer entfernen), {$a->cohorts} (zusätzliche Kohorten, Pipe-getrennt, z.B. KohorteA|KohorteB), {$a->oldemail} (bisherige E-Mail-Adresse beim Umbenennen eines Benutzers). Die Spaltenbezeichnungen lassen sich in den Plugin-Einstellungen anpassen.';
$string['csvdelimiter'] = 'CSV-Trennzeichen';
$string['csvfile'] = 'CSV-Datei';
$string['csvfile_help'] = 'Laden Sie eine CSV-Datei mit Benutzerdaten hoch. Die erforderlichen und optionalen Spaltenbezeichnungen sind im Hinweisfeld unterhalb der Datei-Auswahl aufgelistet und können in den Plugin-Einstellungen angepasst werden.';
$string['encoding'] = 'Zeichenkodierung';
$string['error_branchmismatch'] = 'Außenstelle stimmt nicht überein: Zeile hat "{$a->rowbranch}", aber Ihre Außenstelle ist "{$a->userbranch}".';
$string['error_cohort_is_branch'] = 'Die Kohorte "{$a}" entspricht einer Außenstelle und darf nicht über die Kohorten-Spalte zugewiesen werden.';
$string['error_crossbranch_update'] = 'Benutzer "{$a->username}" kann nicht aktualisiert werden: Benutzer gehört zur Außenstelle "{$a->userbranch}", nicht zu Ihrer Außenstelle "{$a->uploaderbranch}".';
$string['error_csvparse'] = 'Fehler beim Lesen der CSV-Datei. Bitte überprüfen Sie Format und Zeichenkodierung.';
$string['error_email_conflict'] = 'Die neue E-Mail-Adresse "{$a}" wird bereits von einem anderen Benutzer verwendet.';
$string['error_emptycsv'] = 'Die CSV-Datei ist leer oder enthält nur Kopfzeilen.';
$string['error_invalidemail'] = 'Ungültige E-Mail-Adresse: {$a}';
$string['error_invalidoldemail'] = 'Ungültige bisherige E-Mail-Adresse: {$a}';
$string['error_maxusers'] = 'Die CSV-Datei enthält {$a->count} Zeilen, das Maximum ist {$a->max}. Bitte teilen Sie die Datei auf.';
$string['error_missingbranch'] = 'Behörde ist erforderlich.';
$string['error_missingcolumns'] = 'Fehlende Pflichtspalten: {$a}';
$string['error_missingemail'] = 'E-Mail ist erforderlich.';
$string['error_missingfirstname'] = 'Vorname ist erforderlich.';
$string['error_missinglastname'] = 'Name (Nachname) ist erforderlich.';
$string['error_missingorgunit'] = 'Organisationseinheit ist erforderlich.';
$string['error_no_branch_value'] = 'Für Ihr Benutzerkonto ist kein Außenstellen-Wert hinterlegt. Bitte kontaktieren Sie einen Administrator.';
$string['error_noconfigured_branchfield'] = 'Das Profilfeld für die Außenstelle wurde nicht konfiguriert. Bitte kontaktieren Sie einen Administrator.';
$string['error_noconfigured_orgunitfield'] = 'Das Profilfeld für die Organisationseinheit wurde nicht konfiguriert. Bitte kontaktieren Sie einen Administrator.';
$string['error_unknowncohort'] = 'Kohorte "{$a}" existiert nicht und die automatische Erstellung ist deaktiviert.';
$string['examplecsv'] = 'Beispiel-CSV-Datei';
$string['examplecsv_help'] = 'Laden Sie diese Datei als Vorlage für Ihren Benutzerupload herunter.';
$string['header_details'] = 'Details';
$string['header_rownumber'] = 'Zeilennummer';
$string['header_status'] = 'Status';
$string['pluginname'] = 'Benutzerupload für Außenstellen';
$string['preview_admin_mode'] = 'Admin-Modus — Einschränkungen der Außenstelle gelten nicht.';
$string['preview_branch_locked'] = 'Ihre Außenstelle: <strong>{$a}</strong> — alle hochgeladenen Benutzer werden dieser Außenstelle zugeordnet.';
$string['preview_cancel'] = 'Abbrechen';
$string['preview_confirm'] = 'Upload bestätigen';
$string['preview_summary'] = '{$a->total} Zeilen gefunden: {$a->valid} gültig, {$a->errors} Fehler, {$a->warnings} Warnungen.';
$string['preview_title'] = 'Upload-Vorschau';
$string['previewstep'] = 'Vorschau';
$string['privacy:metadata'] = 'Das Plugin "Benutzerupload für Außenstellen" speichert selbst keine personenbezogenen Daten. Es verwendet die Moodle-Kern-APIs (user_create_user, user_update_user, delete_user, profile_save_data, cohort_add_member) zur Benutzerverwaltung; diese Subsysteme erfüllen ihre eigenen Datenschutzanforderungen. Hochgeladene CSV-Dateien werden im Arbeitsspeicher von Moodles csv_import_reader verarbeitet und nach der Verarbeitung gelöscht.';
$string['result_created'] = 'Erstellt';
$string['result_deleted'] = 'Gelöscht';
$string['result_error'] = 'Fehler';
$string['result_skipped'] = 'Übersprungen';
$string['result_suspended'] = 'Gesperrt';
$string['result_updated'] = 'Aktualisiert';
$string['result_warning'] = 'Warnung';
$string['results_back'] = 'Weitere Datei hochladen';
$string['results_created'] = 'Benutzer erstellt';
$string['results_deleted'] = 'Benutzer gelöscht';
$string['results_errors'] = 'Fehler';
$string['results_skipped'] = 'Zeilen übersprungen';
$string['results_summary'] = 'Verarbeitung abgeschlossen.';
$string['results_suspended'] = 'Benutzer gesperrt';
$string['results_title'] = 'Upload-Ergebnis';
$string['results_updated'] = 'Benutzer aktualisiert';
$string['resultstep'] = 'Ergebnis';
$string['setting_autocreate'] = 'Kohorten automatisch erstellen';
$string['setting_autocreate_desc'] = 'Wenn aktiviert, werden Kohorten, die in der CSV referenziert werden aber noch nicht existieren, automatisch erstellt. Andernfalls werden Zeilen mit unbekannten Kohorten abgelehnt.';
$string['setting_branchfield'] = 'Profil-Feld für Außenstelle';
$string['setting_branchfield_desc'] = 'Das benutzerdefinierte Profilfeld, das die Zugehörigkeit zur Außenstelle bestimmt. Nicht-Admins können nur Benutzer für ihre eigene Außenstelle hochladen.';
$string['setting_col_branch'] = 'CSV-Spalte: Behörde';
$string['setting_col_branch_desc'] = 'CSV-Bezeichnung der Behörden-Spalte. Muss einer Kohort-Idnummer entsprechen. Standardwert in der Site-Sprache: {$a}.';
$string['setting_col_cohorts'] = 'CSV-Spalte: Zusätzliche Kohorten (optional)';
$string['setting_col_cohorts_desc'] = 'Optionale CSV-Bezeichnung für zusätzliche Kohort-Zuweisungen. Werte sind Pipe-getrennte Kohort-Idnummern (z.B. KohorteA|KohorteB). Standardwert in der Site-Sprache: {$a}.';
$string['setting_col_email'] = 'CSV-Spalte: E-Mail';
$string['setting_col_email_desc'] = 'CSV-Bezeichnung der E-Mail-Spalte (gleichzeitig Moodle-Benutzername). Standardwert in der Site-Sprache: {$a}.';
$string['setting_col_firstname'] = 'CSV-Spalte: Vorname';
$string['setting_col_firstname_desc'] = 'CSV-Bezeichnung der Vorname-Spalte. Standardwert in der Site-Sprache: {$a}.';
$string['setting_col_lastname'] = 'CSV-Spalte: Nachname';
$string['setting_col_lastname_desc'] = 'CSV-Bezeichnung der Nachname-Spalte. Standardwert in der Site-Sprache: {$a}.';
$string['setting_col_oldemail'] = 'CSV-Spalte: Bisherige E-Mail (optional)';
$string['setting_col_oldemail_desc'] = 'Optionale CSV-Bezeichnung für das Umbenennen eines Benutzers. Wenn diese Spalte einen Wert enthält, wird der bestehende Benutzer anhand der bisherigen E-Mail aktualisiert und auf die neue E-Mail/den neuen Benutzernamen aus der E-Mail-Spalte umgestellt. Standardwert in der Site-Sprache: {$a}.';
$string['setting_col_orgunit'] = 'CSV-Spalte: Organisationseinheit';
$string['setting_col_orgunit_desc'] = 'CSV-Bezeichnung der Spalte für die Organisationseinheit. Standardwert in der Site-Sprache: {$a}.';
$string['setting_col_remove'] = 'CSV-Spalte: Lösch-Markierung (optional)';
$string['setting_col_remove_desc'] = 'Optionale CSV-Bezeichnung für die Lösch-Markierung. Zeilen, in denen diese Spalte 1, ja, yes oder true enthält, werden — abhängig von der Löschaktion — gesperrt oder gelöscht. Standardwert in der Site-Sprache: {$a}.';
$string['setting_columns_heading'] = 'CSV-Spaltenbezeichnungen';
$string['setting_columns_heading_desc'] = 'Passen Sie die CSV-Spaltenbezeichnungen an Ihre Eingabedateien an. Die Erkennung ist gross-/kleinschreibungsunabhängig und ignoriert umliegende Leerzeichen. Ein leeres Feld stellt den für die konfigurierte Site-Sprache ($CFG->lang) hinterlegten Standardwert wieder her. Änderungen hier wirken sich auf den Hinweistext im Upload-Formular, die Vorschau-/Ergebnis-Tabellen, den Download der Beispiel-CSV-Datei und die Spaltenvalidierung aus — zentral an einer Stelle.';
$string['setting_deleteaction'] = 'Löschaktion';
$string['setting_deleteaction_delete'] = 'Benutzerkonto dauerhaft löschen';
$string['setting_deleteaction_desc'] = 'Was passiert, wenn eine Zeile in der Lösch-Spalte den Wert 1 hat. "Sperren" deaktiviert das Konto, "Löschen" entfernt es dauerhaft.';
$string['setting_deleteaction_suspend'] = 'Benutzerkonto sperren';
$string['setting_maxusers'] = 'Maximale Benutzer pro Upload';
$string['setting_maxusers_desc'] = 'Die maximale Anzahl an Zeilen in einem einzelnen CSV-Upload. 0 für unbegrenzt.';
$string['setting_orgunitfield'] = 'Profil-Feld für Organisationseinheit';
$string['setting_orgunitfield_desc'] = 'Das benutzerdefinierte Profilfeld, in das die Organisationseinheit aus der CSV-Datei gespeichert wird.';
$string['status_create'] = 'Wird erstellt';
$string['status_delete'] = 'Wird gelöscht';
$string['status_ok'] = 'OK';
$string['status_skip'] = 'Übersprungen';
$string['status_suspend'] = 'Wird gesperrt';
$string['status_update'] = 'Wird aktualisiert';
$string['step_completed'] = 'Abgeschlossener Schritt';
$string['step_current'] = 'Aktueller Schritt';
$string['step_indicator_label'] = 'Upload-Fortschritt';
$string['step_upcoming'] = 'Bevorstehender Schritt';
$string['summary_total'] = 'Gesamt';
$string['summary_warnings'] = 'Warnungen';
$string['uploadstep'] = 'CSV hochladen';
$string['uploadusers'] = 'Benutzer hochladen';
