<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit tests for the pledge module library functions.
 *
 * @package    mod_pledge
 * @copyright  2025 Sergio Comerón <info@sergiocomeron.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pledge;

use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Unit tests for the pledge module library functions.
 */
#[CoversFunction('pledge_supports')]
final class lib_test extends \advanced_testcase {
    /**
     * pledge_supports returns the expected values for each feature.
     */
    public function test_pledge_supports(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/pledge/lib.php');

        $this->assertTrue(pledge_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertTrue(pledge_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertFalse(pledge_supports(FEATURE_MOD_INTRO));
        $this->assertFalse(pledge_supports(FEATURE_SHOW_DESCRIPTION));
        $this->assertNull(pledge_supports('a_feature_that_does_not_exist'));
    }
}
