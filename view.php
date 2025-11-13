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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once("$CFG->libdir/formslib.php");

// Configurar la página (ten en cuenta que debes ajustar el contexto y otros parámetros según corresponda)

global $USER;

class accept_form extends moodleform {
    // Añadir elementos al formulario
    public function definition() {
        global $CFG;
        $mform = $this->_form; // No olvides la barra baja
        $mform->addElement('checkbox', 's', get_string('accept', 'pledge'));
        $mform->addRule('s', get_string('needaccept', 'pledge'), 'required', null, 'client');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('continue', 'pledge'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }
    function validation($data, $files) {
        return array();
    }
}

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('pledge', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$pledge = $DB->get_record('pledge', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$PAGE->set_url('/mod/pledge/view.php', array('id' => $cm->id));

$PAGE->set_title(format_string($pledge->name));
$PAGE->set_heading(format_string($course->fullname));

$contextmodule = context_module::instance($cm->id);

// Verificar restricción de tiempo solo para estudiantes (no para profesores)
if (!has_capability('mod/pledge:viewattempts', $contextmodule)) {
    $now = time();
    $timeopen = $pledge->timeopen ?? 0;
    $timeclosed = $pledge->timeclosed ?? 0;

    // Comprobar si el pledge aún no está abierto
    if ($timeopen > 0 && $now < $timeopen) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('pledgenotavailable', 'pledge', userdate($timeopen)), 'error');
        echo $OUTPUT->footer();
        exit;
    }

    // Comprobar si el pledge ya está cerrado
    if ($timeclosed > 0 && $now > $timeclosed) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('pledgeclosed', 'pledge', userdate($timeclosed)), 'error');
        echo $OUTPUT->footer();
        exit;
    }
}

// Procesar eliminación si se recibe el parámetro 'deleteid'
$deleteid = optional_param('deleteid', 0, PARAM_INT);
if ($deleteid && confirm_sesskey()) {
    $user = $DB->get_record('pledge_acceptance', array('id' => $deleteid), 'userid');
    $DB->delete_records('pledge_acceptance', array('id' => $deleteid));
    // Actualizamos el estado de finalización a "incompleto" para el pledge.
    redirect($PAGE->url, get_string('deleted', 'pledge'));
}

// echo $OUTPUT->header();
// echo $OUTPUT->heading($pledge->name);

$mform = new accept_form($PAGE->url);

if (has_capability('mod/pledge:viewattempts', $contextmodule)) {
    echo $OUTPUT->header();

    // Mostrar una tabla con todos los pledge aceptados para este pledge.
    $table = new html_table();
    // Se añade columna extra para la eliminación.
    $table->head = array(get_string('user'), get_string('timeaccepted', 'pledge'), get_string('delete', 'pledge'));
    
    $records = $DB->get_records('pledge_acceptance', array('pledgeid' => $pledge->id));
    if ($records) {
        foreach ($records as $record) {
            $user = $DB->get_record('user',
                array('id' => $record->userid),
                'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename'
            );
            $fullname = fullname($user);
            $deleteurl = new moodle_url($PAGE->url, array(
                'deleteid' => $record->id,
                'sesskey' => sesskey()
            ));
            // Agregamos un enlace de eliminación con confirmación.
            $deleteaction = html_writer::link($deleteurl, get_string('delete', 'pledge'),
                array('onclick' => 'return confirm("'.get_string('confirmdelete', 'pledge').'");')
            );
            $table->data[] = array($fullname, userdate($record->timeaccepted), $deleteaction);
        }
        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('nopledges', 'pledge'));
    }
} else if ($DB->record_exists('pledge_acceptance', array('pledgeid' => $pledge->id, 'userid' => $USER->id))) {
    echo $OUTPUT->header();
    // Si el usuario ya ha aceptado, mostramos el mensaje correspondiente.
    echo html_writer::tag('p', get_string('alreadyaccepted', 'pledge'));
    if (!empty($pledge->linkedactivity)) {
        // Supongamos que linkedactivity almacena el id del course module.
        $cmactivity = $DB->get_record('course_modules', array('id' => $pledge->linkedactivity), '*', MUST_EXIST);
        // Obtenemos el nombre del módulo consultando la tabla 'modules'
        $moduleinfo = $DB->get_record('modules', array('id' => $cmactivity->module), 'name', MUST_EXIST);
        // Construir la URL: usualmente es /mod/<modulename>/view.php?id=<course_module_id>
        $activityurl = new moodle_url('/mod/' . $moduleinfo->name . '/view.php', array('id' => $cmactivity->id));
        echo html_writer::tag('p', html_writer::link($activityurl, get_string('linkedactivity', 'pledge')));
    }
} else if ($data = $mform->get_data()) {
    // Guardamos el registro de aceptación.
    $record = new stdClass();
    $record->pledgeid     = $pledge->id;
    $record->userid       = $USER->id;
    $record->timeaccepted = time();
    $DB->insert_record('pledge_acceptance', $record);

    if (get_config('mod_pledge', 'sendjustificantes')) {
        // Lanzar la tarea sendjustification.
        // Crear una instancia de la tarea adhoc.
        $task = new \mod_pledge\task\sendjustification();
        // Establecer los datos personalizados si se necesitan.
        $customdata = array(
            'pledgeid' => $pledge->id
        );
        $task->set_custom_data($customdata);
        // Encolar la tarea.
        \core\task\manager::queue_adhoc_task($task);
    }

    // Marcamos el pledge como completado utilizando el course module del pledge.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
    echo $OUTPUT->header();
    echo html_writer::tag('p', get_string('pledgeaccepted', 'pledge'));

    // Si hay actividad vinculada y se desea mostrar un enlace para entrar, se puede dejar opcional.
    if (!empty($pledge->linkedactivity)) {
        $cmactivity = $DB->get_record('course_modules', array('id' => $pledge->linkedactivity), '*', MUST_EXIST);
        $moduleinfo = $DB->get_record('modules', array('id' => $cmactivity->module), 'name', MUST_EXIST);
        $activityurl = new moodle_url('/mod/' . $moduleinfo->name . '/view.php', array('id' => $cmactivity->id));
        echo html_writer::tag('p', html_writer::link($activityurl, get_string('linkedactivity', 'pledge')));
    }
} else {
    echo $OUTPUT->header();
    // Mostrar el mensaje del código de honor guardado en globalhonorcode en una caja bonita.
    $globalhonorcode = get_config('mod_pledge', 'globalhonorcode');
    if (!empty($globalhonorcode)) {
        echo html_writer::start_tag('div', array('class' => 'card my-3'));
        echo html_writer::tag('div', format_text($globalhonorcode), array('class' => 'card-body'));
        echo html_writer::end_tag('div');
    }
    // Si aún no se ha aceptado, se muestra el formulario.
    $mform->display();
}

echo $OUTPUT->footer();
