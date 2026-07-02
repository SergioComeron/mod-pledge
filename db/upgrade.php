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
 * Upgrade steps for Pledge
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    mod_pledge
 * @category   upgrade
 * @copyright  2025 Sergio Comerón <info@sergiocomeron.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_pledge_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025051000) {
        // Obtén el manager de la base de datos.
        $dbman = $DB->get_manager();

        // Define la tabla a actualizar.
        $table = new xmldb_table('pledge_acceptance');

        // Define el nuevo campo "justificante".
        $field = new xmldb_field('justificante', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timeaccepted');

        // Si el campo aún no existe, se añade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Marca el savepoint para esta versión.
        upgrade_plugin_savepoint(true, 2025051000, 'mod', 'pledge');
    }

    if ($oldversion < 2025111201) {
        // Define field timeopen to be added to pledge.
        $table = new xmldb_table('pledge');
        $field = new xmldb_field('timeopen', XMLDB_TYPE_INTEGER, '10', null, false, null, '0', 'linkedactivity');

        // Conditionally launch add field timeopen.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timeclosed to be added to pledge.
        $field = new xmldb_field('timeclosed', XMLDB_TYPE_INTEGER, '10', null, false, null, '0', 'timeopen');

        // Conditionally launch add field timeclosed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pledge savepoint reached.
        upgrade_mod_savepoint(true, 2025111201, 'pledge');
    }

    if ($oldversion < 2026070200) {
        // Añadir la prueba del consentimiento del tratamiento de datos (RGPD).
        $table = new xmldb_table('pledge_acceptance');

        // Define field consenttime to be added to pledge_acceptance.
        $field = new xmldb_field('consenttime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'justificante');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field consentversion to be added to pledge_acceptance.
        $field = new xmldb_field('consentversion', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'consenttime');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pledge savepoint reached.
        upgrade_mod_savepoint(true, 2026070200, 'pledge');
    }

    if ($oldversion < 2026070206) {
        // Campo con el hash del código de honor aceptado.
        $table = new xmldb_table('pledge_acceptance');
        $field = new xmldb_field('honorversion', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'consentversion');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Tabla con el contenido de cada versión de texto aceptada, indexada por hash.
        $texttable = new xmldb_table('pledge_textversion');
        if (!$dbman->table_exists($texttable)) {
            $texttable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $texttable->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $texttable->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $texttable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $texttable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $texttable->add_index('contenthash_idx', XMLDB_INDEX_UNIQUE, ['contenthash']);
            $dbman->create_table($texttable);
        }

        // Backfill: guardar el texto vigente de ambos ajustes para que las aceptaciones
        // existentes cuyo hash coincida con el texto actual sean recuperables.
        require_once($CFG->dirroot . '/mod/pledge/locallib.php');
        pledge_store_text_version(get_config('mod_pledge', 'dataconsent'));
        pledge_store_text_version(get_config('mod_pledge', 'globalhonorcode'));

        // Pledge savepoint reached.
        upgrade_mod_savepoint(true, 2026070206, 'pledge');
    }

    return true;
}
