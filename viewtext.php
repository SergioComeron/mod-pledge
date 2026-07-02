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
 * Shows the exact texts (data consent and honor code) accepted by a user.
 *
 * @package   mod_pledge
 * @copyright 2025 Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = required_param('id', PARAM_INT);
$acceptanceid = required_param('acceptanceid', PARAM_INT);

$cm = get_coursemodule_from_id('pledge', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$pledge = $DB->get_record('pledge', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$contextmodule = context_module::instance($cm->id);
require_capability('mod/pledge:viewattempts', $contextmodule);

$acceptance = $DB->get_record(
    'pledge_acceptance',
    ['id' => $acceptanceid, 'pledgeid' => $pledge->id],
    '*',
    MUST_EXIST
);
$user = $DB->get_record('user', ['id' => $acceptance->userid], '*', MUST_EXIST);

$pageurl = new moodle_url('/mod/pledge/viewtext.php', ['id' => $cm->id, 'acceptanceid' => $acceptanceid]);
$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($pledge->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('acceptedtextsfor', 'pledge', fullname($user)));

/**
 * Muestra un bloque con un texto aceptado o un aviso si no se conserva.
 *
 * @param string      $title   Título del bloque.
 * @param int|null    $time    Timestamp de la aceptación.
 * @param string|null $hash    Hash de la versión aceptada.
 * @param string|null $content Contenido recuperado (o null).
 * @return void
 */
function pledge_print_accepted_text($title, $time, $hash, $content) {
    global $OUTPUT;

    echo $OUTPUT->heading($title, 3);
    if (!empty($time)) {
        echo html_writer::tag('p', userdate($time), ['class' => 'text-muted']);
    }

    if ($content !== null) {
        echo html_writer::start_tag('div', ['class' => 'card my-3']);
        echo html_writer::tag('div', format_text($content, FORMAT_HTML), ['class' => 'card-body']);
        echo html_writer::end_tag('div');
    } else if (empty($hash)) {
        echo $OUTPUT->notification(get_string('textnotrecorded', 'pledge'), 'info');
    } else {
        echo $OUTPUT->notification(get_string('textnotavailable', 'pledge'), 'warning');
    }
}

pledge_print_accepted_text(
    get_string('acceptedconsenttext', 'pledge'),
    $acceptance->consenttime,
    $acceptance->consentversion,
    pledge_get_text_version($acceptance->consentversion)
);

pledge_print_accepted_text(
    get_string('acceptedhonortext', 'pledge'),
    $acceptance->timeaccepted,
    $acceptance->honorversion,
    pledge_get_text_version($acceptance->honorversion)
);

echo html_writer::div(
    html_writer::link(
        new moodle_url('/mod/pledge/view.php', ['id' => $cm->id]),
        get_string('back'),
        ['class' => 'btn btn-secondary']
    ),
    'my-3'
);

echo $OUTPUT->footer();
