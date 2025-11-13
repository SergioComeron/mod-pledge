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
 * Restore structure step for mod_pledge
 *
 * @package    mod_pledge
 * @copyright  2025 Sergio Comerón <info@sergiocomeron.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one pledge activity
 */
class restore_pledge_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('pledge', '/activity/pledge');
        if ($userinfo) {
            $paths[] = new restore_path_element('pledge_acceptance', '/activity/pledge/acceptances/acceptance');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_pledge($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        $newitemid = $DB->insert_record('pledge', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_pledge_acceptance($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->pledgeid = $this->get_new_parentid('pledge');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $exists = $DB->record_exists('pledge_acceptance', [
            'pledgeid' => $data->pledgeid,
            'userid' => $data->userid
        ]);

        if (!$exists) {
            $newitemid = $DB->insert_record('pledge_acceptance', $data);
            $this->set_mapping('pledge_acceptance', $oldid, $newitemid);
        } else {
            $existing = $DB->get_record('pledge_acceptance', [
                'pledgeid' => $data->pledgeid,
                'userid' => $data->userid
            ], 'id');
            if ($existing) {
                $this->set_mapping('pledge_acceptance', $oldid, $existing->id);
            }
        }
    }

    protected function after_execute() {
        // Add pledge related files
        $this->add_related_files('mod_pledge', 'intro', null);
    }
}