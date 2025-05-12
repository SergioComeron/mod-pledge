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
 * Callback implementations for Pledge
 *
 * Documentation: {@link https://moodledev.io/docs/apis/plugintypes/mod}
 *
 * @package    mod_pledge
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function pledge_supports($feature) {
    global $CFG;
    if ($CFG->branch >= 400) {
        switch($feature) {
            case FEATURE_COMPLETION_TRACKS_VIEWS:
                return true;
            case FEATURE_MOD_INTRO:
                return false;
            case FEATURE_SHOW_DESCRIPTION:
                return false;
            case FEATURE_BACKUP_MOODLE2:
                return true;
            case FEATURE_MOD_PURPOSE:
                return MOD_PURPOSE_ADMINISTRATION;
            default:
                return null;
        }
    } else {
        switch($feature) {
            case FEATURE_COMPLETION_TRACKS_VIEWS:
                return true;
            case FEATURE_MOD_INTRO:
                return false;
            case FEATURE_SHOW_DESCRIPTION:
                return false;
            case FEATURE_BACKUP_MOODLE2:
                return true;
            default:
                return null;
        }
    }
}

/**
 * Saves a new instance of the pledge into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $data Submitted data from the form in mod_form.php
 * @param mod_pledge_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted pledge record
 */
function pledge_add_instance($data, $mform) {
    global $DB;
    
    // Si se ha seleccionado una actividad vinculada y no es "none" (valor 0)
    if (!empty($data->linkedactivity) && $data->linkedactivity != 0) {
        // Recuperar el course module de la actividad vinculada (asumiendo que es un cuestionario)
        $cm = $DB->get_record('course_modules', array('id' => $data->linkedactivity), '*', MUST_EXIST);
        // Conocer el tipo de módulo (debe ser 'quiz')
        $moduleinfo = $DB->get_record('modules', array('id' => $cm->module), 'name', MUST_EXIST);
        if ($moduleinfo->name === 'quiz') {
            // Recuperamos el nombre del cuestionario desde la tabla 'quiz'
            $quiz = $DB->get_record('quiz', array('id' => $cm->instance), 'name', MUST_EXIST);
            // Concatenar el nombre del pledge con el nombre del cuestionario
            $data->name = $data->name . ' (' . $quiz->name . ')';
        }
    }
    
    // Asignar los tiempos de creación y modificación.
    $data->timecreated = time();
    $data->timemodified = time();
    
    // Guardamos el registro del pledge con el nombre modificado.
    $data->id = $DB->insert_record('pledge', $data);
    return $data->id;
}

/**
 * Updates an instance of the pledge in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $pledge An object from the form in mod_form.php
 * @param mod_pledge_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function pledge_update_instance($pledge,  $mform = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/pledge/locallib.php');

    $pledge->timemodified = time();
    $pledge->id = $pledge->instance;
    $cmid       = $pledge->coursemodule;

    $result = $DB->update_record('pledge', $pledge);
    pledge_update_calendar($pledge, $cmid);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every assignment event in the site is checked, else
 * only assignment events belonging to the course specified are checked.
 *
 * @param int $courseid
 * @param int|stdClass $instance pledge module instance or ID.
 * @param int|stdClass $cm Course module object or ID.
 * @return bool
 */
function pledge_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/pledge/locallib.php');

    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('pledge', array('id' => $instance), '*', MUST_EXIST);
        }
        if (isset($cm)) {
            if (!is_object($cm)) {
                $cm = (object)array('id' => $cm);
            }
        } else {
            $cm = get_coursemodule_from_instance('pledge', $instance->id);
        }
        pledge_update_calendar($instance, $cm->id);
        return true;
    }

    if ($courseid) {
        if (!is_numeric($courseid)) {
            return false;
        }
        if (!$pledges = $DB->get_records('pledge', array('course' => $courseid))) {
            return true;
        }
    } else {
        return true;
    }

    foreach ($pledges as $pledge) {
        $cm = get_coursemodule_from_instance('pledge', $pledge->id);
        pledge_update_calendar($pledge, $cm->id);
    }

    return true;
}

/**
 * Removes an instance of the pledge from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function pledge_delete_instance($id) {
    global $CFG, $DB;

    if (! $pledge = $DB->get_record('pledge', array('id' => $id))) {
        return false;
    }
    // Obtenemos el course module
    $cm = get_coursemodule_from_instance('pledge', $pledge->id, $pledge->course, false, MUST_EXIST);

    $context = context_course::instance($pledge->course);
    $result = true;
    
    // Eliminar todos los registros de aceptación de este pledge.
    $DB->delete_records('pledge_acceptance', array('pledge' => $pledge->id));

    // Borramos el registro principal del pledge.
    if (! $DB->delete_records('pledge', array('id' => $pledge->id))) {
        $result = false;
    }
    // En caso de que tengas procesos adicionales para borrar datos relacionados (por ejemplo, eventos),
    // puedes llamarlos aquí.

    return $result;
}

/**
* Marks a module as viewed.
*
* Should be called whenever a module is 'viewed' (it is up to the module how to
* determine that). Has no effect if viewing is not set as a completion condition.
*
* Note that this function must be called before you print the page header because
* it is possible that the navigation block may depend on it. If you call it after
* printing the header, it shows a developer debug warning.
*
* @param stdClass|cm_info $cm Activity
* @param int $userid User ID or 0 (default) for current user
* @return void
*/
/* function set_module_unviewed($cm, $userid) {
    global $PAGE, $DB;
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $completion = new completion_info($course);
    if ($PAGE->headerprinted) {
        debugging('set_module_unviewed must be called before header is printed',
            DEBUG_DEVELOPER);
    }
 
    // Don't do anything if view condition is not turned on
    if ($cm->completionview == COMPLETION_VIEW_NOT_REQUIRED || !$completion->is_enabled($cm)) {
        return;
    }
 
    // Get current completion state
    $data = $completion->get_data($cm, false, $userid);
 
    // If we already viewed it, don't do anything
    if ($data->viewed == COMPLETION_NOT_VIEWED) {
        return;
    }
 
    // OK, change state, save it, and update completion
    $data->viewed = COMPLETION_NOT_VIEWED;
    $completion->internal_set_data($cm, $data);
    $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
} */