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
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_pledge_upgrade($oldversion) {
    global $DB;
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

    return true;
}
