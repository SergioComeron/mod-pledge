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
 * TODO describe file backup_pledge_activity_task.class
 *
 * @package    mod_pledge
 * @category  backup
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pledge/backup/moodle2/backup_pledge_stepslib.php');

/**
 * Restore task for the pledge activity module
 *
 * Provides all the settings and steps to perform complete restore of the activity.
 *
 * @package    mod_pledge
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_pledge_activity_task extends backup_activity_task {
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // We have just one structure step here.
        $this->add_step(new restore_pledge_activity_structure_step('pledge_structure', 'pledge.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of newmodules.
        $search = '/('.$base.'\/mod\/pledge\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@PLEDGEINDEX*$2@$', $content);

        // Link to pledge view by moduleid.
        $search = '/('.$base.'\/mod\/pledge\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@PLEDGEVIEWBYID*$2@$', $content);

        return $content;
    }

}