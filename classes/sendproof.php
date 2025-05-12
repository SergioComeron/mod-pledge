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

namespace mod_pledge\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Class sendjustification
 *
 * @package    mod_pledge
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sendjustification extends \core\task\adhoc_task {

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        // Obtener todos los registros de pledge_acceptance donde 'justificante' es NULL.
        $records = $DB->get_records_select('pledge_acceptance', 'justificante IS NULL');
        
        foreach ($records as $record) {
            // Aquí debes invocar una función para enviar el PDF al usuario.
            // Por ejemplo, la función send_pdf_to_user($userid, $pledgeid) debe encargarse de generar y enviar el PDF.
            $sent = self::send_pdf_to_user($record->userid, $record->pledgeid);
            
            if ($sent) {
                // Si el PDF se envía correctamente, actualizamos el campo justificante con el timestamp actual.
                $record->justificante = time();
                $DB->update_record('pledge_acceptance', $record);
            }
        }
    }

    /**
     * Función para enviar el PDF al usuario.
     *
     * @param int $userid
     * @param int $pledgeid
     * @return bool Devuelve true si el envío fue exitoso, false en caso contrario.
     */
    private static function send_pdf_to_user($userid, $pledgeid) {
        // Aquí debes implementar la lógica necesaria para generar el PDF y enviarlo, por ejemplo, por correo.
        // Este es un ejemplo simplificado que siempre retorna true.
        
        // Ejemplo:
        // $pdf = generate_pdf($pledgeid); // Función que genera el PDF.
        // $user = get_complete_user_data('id', $userid); // Obtiene datos del usuario.
        // $sent = email_to_user($user, $from, "Asunto", "Mensaje", $pdf);
        // return $sent;

        $pledgeacceptance = $DB->get_record('pledge_acceptance', array('id' => $pledgeid));
        $user = $DB->get_record('user', array('id' => $userid));
        // Crear el PDF
        $fs = get_file_storage();
        $pdf = new \pdf();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('UDIMA');
        $pdf->SetTitle('Justificante de asistencia');
        $pdf->SetSubject('Justificante');
        $pdf->SetMargins(20, 30, 20); // Margen superior más amplio para el logo
        $pdf->AddPage();

        // Insertar logotipo (ajusta tamaño y posición si quieres)
        $logopath = $CFG->dirroot . '/local/recibeexamen/pix/udima_logo.png';
        if (file_exists($logopath)) {
            $pdf->Image($logopath, 15, 10, 30);
        }

        // Contenido HTML del justificante
        $fecha = userdate(time(), '%d de %B de %Y');
        $html = '
        <style>
            .title { font-size: 16pt; font-weight: bold; text-align: center; margin-bottom: 20px; }
            .text { font-size: 12pt; text-align: justify; }
            .info { font-size: 11pt; }
            .footer { font-size: 9pt; text-align: center; margin-top: 50px; }
        </style>

        <div class="title">JUSTIFICANTE DE ASISTENCIA A EXAMEN</div>

        <div class="text">
            Collado Villalba, a ' . $fecha . '<br><br>

            D/Dª <strong>' . fullname($user) . '</strong> con Número de Documento de Identificación: <strong>' . $dniprs  . '</strong>,
            matriculado/a en esta Universidad en estudios universitarios conducentes a una titulación oficial, ha asistido a
            la realización del examen convocado por la Universidad a Distancia de Madrid, en la fecha, hora y sede que figura
            a continuación, expidiéndose a petición del interesado el presente certificado a los efectos oportunos.
        </div><br>

        <div class="info">
            <strong>Información relativa al examen:</strong><br><br>
            <strong>Código examen:</strong> ' . $exacodnum . '<br>
            <strong>Titulación:</strong> '. $planomid1 .'<br>
            <strong>Asignatura:</strong> ' . $assnomid1 . '<br>
            <strong>Fecha y hora de inicio:</strong> ' . $fechainicio . '<br>
            <strong>Fecha y hora de finalización:</strong> ' . $fechafin . '<br>
            <strong>Sede:</strong> ' . $sede . '<br>
        </div><br><br>

        <div class="text">Firma y sello</div><br><br>
        ';

        // Escribir el HTML
        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Añadir firma (opcional)
        $firmapath = $CFG->dirroot . '/local/recibeexamen/pix/firma.png';
        if (file_exists($firmapath)) {
            $pdf->Image($firmapath, 20, $pdf->GetY(), 50);
        }

        // Pie de página
        $pdf->Ln(40);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 10, "Carretera de La Coruña, km 38,500 (vía de servicio, n.º 15) • 28400 Collado Villalba (Madrid) • 902 02 00 03\nwww.udima.es • informa@udima.es", 0, 'C');

        // Guardar PDF en temporal
        $filename = "justificante_{$user->username}.pdf";
        $tempdir = make_temp_directory('local_recibeexamen');
        $pdfpath = $tempdir . '/' . $filename;
        $pdf->Output($pdfpath, 'F');

        // Enviar correo
        $subject = "Justificante - {$user->username}";

        // Preparar mensajes para texto plano y HTML.
        $message_plain = "Estimado/a {$user->firstname},\n\nAdjunto le remitimos el justificante de asistencia al examen que se realizó en la fecha: " . $fechainicio . " en la sede: " . $sede . ".\n\nSaludos cordiales.";
        $message_html = nl2br($message_plain);

        // Enviar correo con adjunto
        $emailresult = email_to_user(
            $user,
            \core_user::get_support_user(), // Aseguramos el namespace global
            $subject,
            $message_plain,
            $message_html,
            $pdfpath,
            $filename
        );

        if (!$emailresult) {
            throw new \moodle_exception('errorcannotemail', 'local_recibeexamen');
        }

        // Eliminar temporal
        @unlink($pdfpath);

        // Marcar como procesado
        $pledgeacceptance->justificante = time();
        $DB->update_record('pledge_acceptance', $pledgeacceptance);
        return true;
    }
}