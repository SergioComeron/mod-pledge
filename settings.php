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
 * Admin settings for the pledge module.
 *
 * @package   mod_pledge
 * @copyright 2025 Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Texto por defecto del consentimiento del tratamiento de datos.
    // Es un texto de partida (no asesoramiento jurídico): el DPO debe completar los
    // [corchetes] y validar la base jurídica, los plazos y los datos de contacto.
    $defaultconsent = '<h4>Información sobre el tratamiento de tus datos personales</h4>'
        . '<p>Para la realización de exámenes en línea con supervisión remota (proctoring), '
        . 'tus datos personales serán tratados conforme al Reglamento (UE) 2016/679 (RGPD) '
        . 'y la Ley Orgánica 3/2018 (LOPDGDD).</p>'
        . '<ul>'
        . '<li><strong>Responsable del tratamiento:</strong> [Nombre de la institución], '
        . '[dirección], [correo de contacto].</li>'
        . '<li><strong>Delegado de Protección de Datos (DPO):</strong> [correo del DPO].</li>'
        . '<li><strong>Finalidad:</strong> garantizar la identidad del estudiante y la '
        . 'integridad académica durante la realización del examen, mediante la supervisión '
        . 'automatizada del proctoring.</li>'
        . '<li><strong>Datos tratados:</strong> imagen y vídeo captados por tu cámara, audio, '
        . 'capturas de pantalla, datos biométricos derivados del reconocimiento facial, '
        . 'dirección IP y datos de tu dispositivo y navegador.</li>'
        . '<li><strong>Encargado del tratamiento:</strong> el servicio de proctoring '
        . '(Smowltech S.L. — «Smowl»), que trata los datos por cuenta del responsable '
        . 'conforme al contrato de encargo suscrito.</li>'
        . '<li><strong>Base jurídica:</strong> [a determinar por el DPO: misión de interés '
        . 'público en el ámbito educativo y/o tu consentimiento explícito para el tratamiento '
        . 'de datos biométricos].</li>'
        . '<li><strong>Conservación:</strong> los datos se conservarán durante [plazo] y, '
        . 'posteriormente, el tiempo necesario para atender responsabilidades legales.</li>'
        . '<li><strong>Destinatarios:</strong> no se cederán datos a terceros salvo '
        . 'obligación legal.</li>'
        . '<li><strong>Derechos:</strong> puedes ejercer los derechos de acceso, '
        . 'rectificación, supresión, oposición, limitación y portabilidad dirigiéndote a '
        . '[correo]. También puedes reclamar ante la Agencia Española de Protección de Datos '
        . '(www.aepd.es).</li>'
        . '</ul>'
        . '<p>Al marcar la casilla de aceptación declaras haber leído y comprendido esta '
        . 'información y consientes expresamente el tratamiento descrito para poder realizar '
        . 'el examen.</p>';

    $settings->add(new admin_setting_confightmleditor(
        'mod_pledge/dataconsent',
        get_string('dataconsent', 'mod_pledge'),
        get_string('dataconsent_desc', 'mod_pledge'),
        $defaultconsent
    ));

    $settings->add(new admin_setting_confightmleditor(
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
