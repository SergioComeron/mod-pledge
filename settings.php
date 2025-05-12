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
 * Version metadata for the repository_pluginname plugin.
 *
 * @package   repository_pluginname
 * @copyright 2025, author_fullname <author_link>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtextarea(
        'mod_pledge/globalhonorcode',
        get_string('globalhonorcode', 'mod_pledge'),
        get_string('globalhonorcode_desc', 'mod_pledge'),
        "Me comprometo a realizar esta actividad de manera honesta, sin recibir ni ofrecer ayuda externa."
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_pledge/sendjustificantes',
        get_string('sendjustificantes', 'mod_pledge'),
        get_string('sendjustificantes_desc', 'mod_pledge'),
        0
    ));
}
