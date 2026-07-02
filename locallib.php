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
 * Internal library of functions for the pledge module.
 *
 * @package    mod_pledge
 * @copyright  2025 Sergio Comerón <info@sergiocomeron.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Update the calendar entries for this pledge instance.
 *
 * @param stdClass $pledge An pledge object
 * @param cmid cmid
 */
function pledge_update_calendar(stdClass $pledge, $cmid) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/calendar/lib.php');

    $event = new stdClass();
    $event->eventtype = 'open';
    $event->type = CALENDAR_EVENT_TYPE_STANDARD;

    if (
        $event->id = $DB->get_field(
            'event',
            'id',
            ['modulename' => 'pledge', 'instance' => $pledge->id,
            'eventtype' => $event->eventtype]
        )
    ) {
        if ((!empty($pledge->timeopen)) && ($pledge->timeopen > 0)) {
            $event->name = get_string('calendarstart', 'pledge', $pledge->name);
            $event->timestart = $pledge->timeopen;
            $event->timesort = $pledge->timeopen;
            $event->visible = instance_is_visible('pledge', $pledge);
            $event->timeduration = 0;

            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->delete();
        }
    } else {
        if ((!empty($pledge->timeopen)) && ($pledge->timeopen > 0)) {
            $event->name = get_string('calendarstart', 'pledge', $pledge->name);
            $event->courseid = $pledge->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'pledge';
            $event->instance = $pledge->id;
            $event->timestart = $pledge->timeopen;
            $event->timesort = $pledge->timeopen;
            $event->visible = instance_is_visible('pledge', $pledge);
            $event->timeduration = 0;
            calendar_event::create($event);
        }
    }
    return true;
}

/**
 * Render one of the visual acceptance steps (data consent / honor code).
 *
 * Produce una tarjeta moderna con indicador de pasos, cabecera con icono y el
 * formulario integrado. Usa clases de Bootstrap comunes a Moodle 4.x y 5.x.
 *
 * @param int    $step     Número de paso actual (1 o 2).
 * @param string $variant  'data' (consentimiento) u 'honor' (código de honor).
 * @param string $title    Título del paso.
 * @param string $subtitle Subtítulo breve bajo el título.
 * @param string $bodyhtml Cuerpo ya formateado como HTML (salida de format_text).
 * @param string $formhtml HTML del formulario renderizado.
 * @return string HTML de la pantalla.
 */
function pledge_render_consent_step($step, $variant, $title, $subtitle, $bodyhtml, $formhtml) {
    // Indicador de pasos.
    $steps = [
        1 => get_string('stepdata', 'pledge'),
        2 => get_string('stephonor', 'pledge'),
    ];
    $stepperitems = '';
    foreach ($steps as $num => $label) {
        if ($num > 1) {
            $stepperitems .= html_writer::span('', 'pledge-step-line');
        }
        $class = 'pledge-step';
        $circle = (string)$num;
        if ($num < $step) {
            $class .= ' is-done';
            $circle = html_writer::tag('i', '', ['class' => 'fa fa-check']);
        } else if ($num === $step) {
            $class .= ' is-active';
        }
        $stepperitems .= html_writer::tag(
            'span',
            html_writer::span($circle, 'pledge-step-circle') . html_writer::span(s($label), 'pledge-step-label'),
            ['class' => $class]
        );
    }
    $stepper = html_writer::div($stepperitems, 'pledge-stepper');

    // Cabecera con icono.
    $icons = ['data' => 'fa fa-user-shield', 'honor' => 'fa fa-file-signature'];
    $headclass = 'pledge-consent-head' . ($variant === 'honor' ? ' pledge-head-honor' : '');
    $iconhtml = html_writer::span(
        html_writer::tag('i', '', ['class' => $icons[$variant] ?? $icons['data']]),
        'pledge-consent-icon'
    );
    $titlehtml = html_writer::tag('h2', s($title), ['class' => 'pledge-consent-title'])
        . html_writer::tag('p', s($subtitle), ['class' => 'pledge-consent-subtitle']);
    $head = html_writer::div($iconhtml . html_writer::div($titlehtml), $headclass);

    // Cuerpo: texto informativo + formulario.
    $body = html_writer::div($bodyhtml, 'pledge-consent-text');
    $body .= html_writer::div($formhtml, 'pledge-consent-form');
    $bodywrap = html_writer::div($body, 'card-body pledge-consent-body');

    $card = html_writer::div($head . $bodywrap, 'card pledge-consent-card');
    return html_writer::div($stepper . $card, 'pledge-consent');
}

/**
 * Store a text version (by content hash) so the exact accepted text can be retrieved later.
 *
 * Guarda el contenido una sola vez por hash: si ya existe esa versión no la duplica.
 * Un texto vacío no se almacena (su hash no referenciará ninguna fila).
 *
 * @param string $content Contenido HTML del texto (consentimiento o código de honor).
 * @return string El hash SHA-1 del contenido (aunque no se haya almacenado por estar vacío).
 */
function pledge_store_text_version($content) {
    global $DB;

    $content = (string)$content;
    $hash = sha1($content);

    if ($content === '') {
        return $hash;
    }

    if (!$DB->record_exists('pledge_textversion', ['contenthash' => $hash])) {
        $record = new stdClass();
        $record->contenthash = $hash;
        $record->content = $content;
        $record->timecreated = time();
        // Puede haber carreras entre peticiones simultáneas con el mismo hash; ignoramos el duplicado.
        try {
            $DB->insert_record('pledge_textversion', $record);
        } catch (dml_exception $e) {
            if (!$DB->record_exists('pledge_textversion', ['contenthash' => $hash])) {
                throw $e;
            }
        }
    }

    return $hash;
}

/**
 * Retrieve the stored text for a given content hash.
 *
 * @param string|null $hash Hash SHA-1 almacenado en pledge_acceptance.
 * @return string|null El contenido HTML, o null si no se conserva esa versión.
 */
function pledge_get_text_version($hash) {
    global $DB;

    if (empty($hash)) {
        return null;
    }

    $content = $DB->get_field('pledge_textversion', 'content', ['contenthash' => $hash]);
    return $content === false ? null : $content;
}
