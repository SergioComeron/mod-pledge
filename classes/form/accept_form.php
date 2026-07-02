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
 * Honor code acceptance form (step 2).
 *
 * @package   mod_pledge
 * @copyright 2025 Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pledge\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Paso 2: formulario para aceptar el código de honor.
 *
 * @package   mod_pledge
 * @copyright 2025 Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accept_form extends \moodleform {
    /**
     * Define the acceptance form elements.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form; // No olvides la barra baja.
        $mform->addElement('checkbox', 's', get_string('accept', 'pledge'));
        $mform->addRule('s', get_string('needaccept', 'pledge'), 'required', null, 'client');

        // Marca de que el consentimiento (paso 1) ya se otorgó en esta sesión de aceptación.
        $mform->addElement('hidden', 'consentgiven', 1);
        $mform->setType('consentgiven', PARAM_INT);

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
