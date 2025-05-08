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
 * @param stdClass $pledge Submitted data from the form in mod_form.php
 * @param mod_pledge_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted pledge record
 */
function pledge_add_instance($pledge,  $mform = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/pledge/locallib.php');

    $pledge->timecreated = time();
    $cmid       = $pledge->coursemodule;

    $pledge->id = $DB->insert_record('pledge', $pledge);
    pledge_update_calendar($pledge, $cmid);
    // $course = $DB->get_record('course', array('id'=>$pledge->course));
    // if ($course->enablecompletion==0){
    //   $course->enablecompletion = 1;
    //   $DB->update_record('course', $course);
    // }
    return $pledge->id;
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
    // $cm = get_coursemodule_from_id('pledge', $id, 0, false, MUST_EXIST);
    $cm = get_coursemodule_from_instance('pledge', $pledge->id, $pledge->course, false, MUST_EXIST);

    $context = context_course::instance($pledge->course);
    $result = true;
    $attempts = $DB->get_records('pledge_acceptance', array('pledge'=>$pledge->id));
    if (! $DB->delete_records('pledge', array('id' => $pledge->id))) {
        // $DB->delete_records('pledge_attepts', array('pledge' => $id));
        $result = false;
    }
    foreach($attempts as $attempt) {
        delete_attempt_pledge_pledge($attempt->id, $context, $cm);
    }

    return $result;
}

function delete_attempt_pledge_pledge_pledge($idattempt, $context, $cm){
    global $DB;
    $attempt = $DB->get_record('pledge_acceptance', array('id' => $idattempt));
    if (!$attempt) {
        return false;
    }
    
    // set_module_unviewed($cm, $attempt->userid); --> No se que hacía esto aqui, peta cuando se borran cursos con 
    // pledges que tengan intentos. Creo que se me ha colado sin querer, xq no tiene sentido marcar como visto en un borrado.
    $DB->delete_records('pledge_acceptance', array('id' => $idattempt));
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