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
 * TODO describe file backup_pledge_stepslib
 *
 * @package    mod_pledge
 * @copyright  2025 Sergio Comerón <info@sergiocomeron.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete pledge structure for backup, with file and id annotations
 *
 * @package   mod_pledge
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_pledge_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        // Define the main element 'pledge' with fields from the pledge table.
        $pledge = new backup_nested_element('pledge', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'linkedactivity', 'timecreated'
        ));

        // Define child element for acceptances.
        $acceptances = new backup_nested_element('acceptances');
        $acceptance = new backup_nested_element('acceptance', array('id'), array(
            'pledgeid', 'userid', 'timeaccepted', 'justificante'
        ));

        // Build the tree structure.
        $pledge->add_child($acceptances);
        $acceptances->add_child($acceptance);

        // Define sources.
        $pledge->set_source_table('pledge', array('id' => backup::VAR_ACTIVITYID));

        if ($userinfo) {
            $acceptance->set_source_table('pledge_acceptance', array('pledgeid' => '../../id'));
        }

        $acceptance->annotate_ids('user', 'userid');
        $pledge->annotate_files('mod_pledge', 'intro', null);

        return $this->prepare_activity_structure($pledge);
    }
}
