# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Qué es este plugin

`mod_pledge` es un módulo de actividad de Moodle (tipo `mod`). Presenta al estudiante un
"código de honor" que debe aceptar antes de acceder a una actividad vinculada (siempre un
`quiz`). Al aceptar, opcionalmente se genera y envía por correo un **justificante de
asistencia a examen** en PDF (contexto UDIMA). Reside dentro de un árbol Moodle en
`moodle/mod/pledge`; requiere una instalación Moodle funcionando para ejecutarse (rama
`$CFG->branch >= 400`).

## Comandos

No hay build ni suite de tests propia. Tras cualquier cambio en el esquema o `version.php`
hay que subir la versión (`$plugin->version` en `version.php`, formato `AAAAMMDDXX`) y correr
el upgrade:

```bash
# Desde la raíz del Moodle (moodle/), no desde mod/pledge/
php admin/cli/upgrade.php --non-interactive

# Purga de cachés tras tocar strings de idioma, settings o callbacks
php admin/cli/purge_caches.php

# Ejecutar tareas adhoc encoladas (p. ej. sendjustification) manualmente
php admin/cli/adhoc_task.php --execute
```

Si añades o cambias campos de BD, edita **`db/install.xml`** (instalación limpia) **y**
`db/upgrade.php` (instancias existentes) de forma coherente, y sube la versión.

## Arquitectura

Flujo principal (`view.php`, el corazón del plugin):

1. El estudiante entra en la actividad pledge. Se comprueba la ventana temporal
   (`timeopen`/`timeclosed`) — los profesores (capacidad `mod/pledge:viewattempts`) la
   saltan.
2. Se muestra el código de honor global (setting `mod_pledge/globalhonorcode`) y un
   formulario de aceptación (`accept_form`, definido inline en `view.php`).
3. Al aceptar se inserta un registro en `pledge_acceptance` y se marca el módulo como
   completado (`completion_info::set_module_viewed`).
4. Si el setting `mod_pledge/sendjustificantes` está activo, se **encola una tarea adhoc**
   `sendjustification` con `pledgeid` como custom data.
5. Los profesores ven en `view.php` la tabla de aceptaciones y pueden borrar registros
   individuales (parámetro `deleteid` + `confirm_sesskey`).

Dos tablas (`db/install.xml`):
- `pledge`: instancia del módulo. Campo clave `linkedactivity` = `cm->id` del quiz vinculado
  (no el instance id). `timeopen`/`timeclosed` = ventana de disponibilidad del pledge.
- `pledge_acceptance`: una fila por (pledgeid, userid) — índice único. Campo `justificante`
  = timestamp de envío del PDF, o NULL si aún no se ha enviado (es el marcador de pendientes).

Generación del justificante (`classes/task/sendjustification.php`):
- La tarea se encola **por `pledgeid`, no por usuario**: procesa todos los
  `pledge_acceptance` con `justificante IS NULL` de ese pledge. Marca `justificante = time()`
  sólo si el envío tuvo éxito.
- El DNI/NIE del alumno se obtiene del **AD corporativo vía `auth_ldap`**
  (`obtener_numdocumento_ldap`): reutiliza `get_auth_plugin('ldap')` para no hardcodear
  credenciales; lee el atributo `uxxinumberdocument`. Un usuario sin DNI lanza excepción.
- El PDF se construye con TCPDF (`$CFG->libdir/pdflib.php`), usa logo/firma de
  `local/recibeexamen/pix/` y se envía con `email_to_user`, con copia fija a
  `justificantes@udima.es`.
- El código de examen se extrae del nombre del quiz con el patrón `#...#`; las fechas del
  justificante salen de `timeopen`/`timeclose` **del quiz**, no del pledge.

Otros puntos:
- `lib.php`: callbacks estándar del módulo (`pledge_supports`, add/update/delete instance).
  `pledge_add_instance` concatena el nombre del quiz al nombre del pledge.
  `pledge_cm_info_view` pinta la disponibilidad bajo el enlace en la página del curso.
- `mod_form.php`: el selector `linkedactivity` sólo lista quizzes; `validation()` exige que
  las fechas del pledge sean coherentes con las del quiz (el pledge no puede abrir después de
  que abra el quiz, ni cerrar después, etc.).
- `locallib.php` → `pledge_update_calendar`: crea/actualiza/borra el evento de calendario
  `open` según `timeopen`.
- `backup/moodle2/`: backup y restore de las dos tablas.

## Convenciones y dependencias

- **Acoplamiento con UDIMA**: la lógica del justificante asume el plugin `local_recibeexamen`
  (imágenes y algunas strings de error como `local_recibeexamen`), el AD corporativo por LDAP
  y que la actividad vinculada es un `quiz`. Al tocar esa tarea, ten en cuenta esas
  dependencias externas.
- Comentarios y strings de usuario en **español**; sigue el estilo existente.
- `cli/test_ldap.php` es un script de prueba manual de la conexión LDAP; no forma parte del
  flujo de producción.

## Git

- Trabajo directo sobre `main`. El harness bloquea el push directo a `main`: crea una rama,
  haz merge y push desde ahí.
- Remoto: `git@github.com:SergioComeron/mod-pledge.git`.
