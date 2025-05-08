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
 * @package   mod_pledge
 * @copyright 2025, author_fullname <author_link>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__).'/lib.php');


require('../../config.php');
// require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT); // ID del módulo de curso (cm)

$cm = get_coursemodule_from_id('pledge', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);

$pledge = $DB->get_record('pledge', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/pledge/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($pledge->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($pledge->name));

// ¿Ya ha aceptado este usuario?
$accepted = $DB->record_exists('pledge_acceptance', [
    'pledgeid' => $pledge->id,
    'userid' => $USER->id,
]);

if (!$accepted && optional_param('accept', 0, PARAM_BOOL)) {
    // Guardar aceptación
    $record = (object)[
        'pledgeid' => $pledge->id,
        'userid' => $USER->id,
        'timeaccepted' => time()
    ];
    $DB->insert_record('pledge_acceptance', $record);

    // Confirmación
    echo $OUTPUT->notification(get_string('pledgeaccepted', 'mod_pledge'), 'notifysuccess');
    $accepted = true;
}

// Mostrar texto de introducción
// Mostrar el código de honor configurado a nivel global
$honorcode = get_config('mod_pledge', 'globalhonorcode');
echo $OUTPUT->box(format_text($honorcode, FORMAT_HTML), 'generalbox');

// Mostrar botón si aún no se ha aceptado
if (!$accepted) {
    $accepturl = new moodle_url('/mod/pledge/view.php', ['id' => $cm->id, 'accept' => 1]);
    echo $OUTPUT->single_button($accepturl, get_string('acceptpledge', 'mod_pledge'));
} else {
    echo $OUTPUT->box(get_string('alreadyaccepted', 'mod_pledge'), 'generalbox');
}

echo $OUTPUT->footer();
