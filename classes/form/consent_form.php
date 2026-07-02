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
 * Data processing consent form (step 1).
 *
 * @package   mod_pledge
 * @copyright 2025 Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pledge\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Paso 1: formulario de consentimiento del tratamiento de datos.
 *
 * Separado del código de honor para que el consentimiento RGPD sea específico,
 * inequívoco y no vaya empaquetado con la aceptación académica.
 *
 * @package   mod_pledge
 * @copyright 2025 Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consent_form extends \moodleform {
    /**
     * Define the data consent form elements.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('checkbox', 'consent', get_string('consent', 'pledge'));
        $mform->addRule('consent', get_string('needconsent', 'pledge'), 'required', null, 'client');

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('continue', 'pledge'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    /**
     * Validate the submitted form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Array of validation errors.
     */
    public function validation($data, $files) {
        return [];
    }
}
