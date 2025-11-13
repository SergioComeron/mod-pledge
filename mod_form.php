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

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_pledge_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB, $COURSE;

        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'activity', get_string('activity', 'pledge'));
        $mform->setExpanded('activity', true);

        // Agregar selector para vincular una actividad del curso.
        $modinfo = get_fast_modinfo($COURSE);
        $activities = array(0 => get_string('none', 'pledge'));  // opción "ninguna"
        if (!empty($modinfo->cms)) {
            foreach ($modinfo->cms as $cm) {
                // Mostrar solo actividades tipo 'quiz'
                if ($cm->uservisible && $cm->modname === 'quiz') {  
                    $activities[$cm->id] = format_string($cm->name);
                }
            }
        }
        asort($activities);
        $mform->addElement('select', 'linkedactivity', get_string('selectactivity', 'pledge'), $activities);
        $mform->addHelpButton('linkedactivity', 'linkedactivity', 'pledge');
        $mform->setDefault('linkedactivity', 0);


        $mform->addElement('header', 'availability', get_string('availability', 'pledge'));
        $mform->setExpanded('availability', true);

        $name = get_string('allow', 'pledge');
        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'timeopen', $name, $options);
        $namec = get_string('datelimit', 'pledge');
        $optionsc = array('optional' => true);
        $mform->addElement('date_time_selector', 'timeclosed', $namec, $optionsc);

        
        // Se elimina la regla 'required' estándar para aplicar validación personalizada.
        // $mform->addRule('linkedactivity', null, 'required', null, 'client');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        
        // Validamos que se haya seleccionado una actividad (distinta de la opción "none" que es 0)
        if (empty($data['linkedactivity']) || $data['linkedactivity'] == 0) {
            $errors['linkedactivity'] = get_string('selectlinkedactivityrequired', 'pledge');
        } else {
            // Obtener información de la actividad enlazada
            $cm = get_coursemodule_from_id('', $data['linkedactivity'], 0, false, MUST_EXIST);
            $moduleinfo = $DB->get_record('modules', array('id' => $cm->module), 'name', MUST_EXIST);
            
            // Obtener las fechas de la actividad enlazada según su tipo
            $activitydates = null;
            if ($moduleinfo->name === 'quiz') {
                $activity = $DB->get_record('quiz', array('id' => $cm->instance), 'timeopen, timeclose');
                if ($activity) {
                    $activitydates = array(
                        'timeopen' => $activity->timeopen,
                        'timeclose' => $activity->timeclose
                    );
                }
            }
            // Aquí podrías añadir más tipos de actividades si es necesario
            
            // Validar concordancia de fechas
            if ($activitydates) {
                $pledgetimeopen = isset($data['timeopen']) ? $data['timeopen'] : 0;
                $pledgetimeclosed = isset($data['timeclosed']) ? $data['timeclosed'] : 0;
                
                // Si la actividad tiene fecha de inicio y el pledge también
                if ($activitydates['timeopen'] > 0 && $pledgetimeopen > 0) {
                    // El pledge no puede abrirse después del inicio de la actividad
                    if ($pledgetimeopen > $activitydates['timeopen']) {
                        $errors['timeopen'] = get_string('pledgeopentoolate', 'pledge', userdate($activitydates['timeopen']));
                    }
                }
                
                // Si la actividad tiene fecha de cierre y el pledge también
                if ($activitydates['timeclose'] > 0 && $pledgetimeclosed > 0) {
                    // El pledge no puede cerrarse después del cierre de la actividad
                    if ($pledgetimeclosed > $activitydates['timeclose']) {
                        $errors['timeclosed'] = get_string('pledgeclosetoolate', 'pledge', userdate($activitydates['timeclose']));
                    }
                }
                
                // Si ambas fechas del pledge están definidas, validar que timeopen < timeclosed
                if ($pledgetimeopen > 0 && $pledgetimeclosed > 0) {
                    if ($pledgetimeopen >= $pledgetimeclosed) {
                        $errors['timeclosed'] = get_string('pledgeclosedbeforeopen', 'pledge');
                    }
                }
                
                // Validar que el pledge esté abierto antes de que cierre la actividad
                if ($activitydates['timeclose'] > 0 && $pledgetimeopen > 0) {
                    if ($pledgetimeopen >= $activitydates['timeclose']) {
                        $errors['timeopen'] = get_string('pledgeopenafteractivityclose', 'pledge');
                    }
                }
            }
        }
        
        return $errors;
    }
}

