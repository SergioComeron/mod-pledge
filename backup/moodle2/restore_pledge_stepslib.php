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
 * TODO describe file restore_pledge_stepslib
 *
 * @package    mod_pledge
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pledge/backup/moodle2/restore_pledge_stepslib.php');

/**
 * Restore task for the pledge activity module
 *
 * Provides all the settings and steps to perform complete restore of the activity.
 *
 * @package   mod_pledge
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 class restore_pledge_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('pledge', '/activity/pledge');
        if ($userinfo) {
            $paths[] = new restore_path_element('pledge_acceptance', '/activity/pledge/acceptances/acceptance');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_pledge($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        // Create the pledge instance.
        $newitemid = $DB->insert_record('pledge', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_pledge_acceptance($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->pledge = $this->get_new_parentid('pledge');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('pledge_acceptance', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add pledge related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_pledge', 'acceptance', null);
    }
}