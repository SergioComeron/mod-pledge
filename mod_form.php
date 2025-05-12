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
        // Se elimina la regla 'required' estándar para aplicar validación personalizada.
        // $mform->addRule('linkedactivity', null, 'required', null, 'client');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Validamos que se haya seleccionado una actividad (distinta de la opción "none" que es 0)
        if (empty($data['linkedactivity']) || $data['linkedactivity'] == 0) {
            $errors['linkedactivity'] = get_string('selectlinkedactivityrequired', 'pledge');
        }
        return $errors;
    }
}

