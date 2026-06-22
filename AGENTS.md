# AGENTS.md

Guia para agentes que continuen este proyecto de invitacion de boda.

## Contexto

Este repositorio contiene la invitacion web de boda de Estteban y Maria para `https://bodaesttebanymaria.com`.

La app es PHP/MySQL sin framework:

- `index.php`: invitacion publica y formulario RSVP por URL personalizada.
- `admin.php`: panel privado para crear invitados, editar pases, copiar enlaces y revisar respuestas.
- `database.sql`: estructura de base de datos.
- `config.example.php`: plantilla de configuracion.
- `config.php`: configuracion real local/hosting. Esta ignorado por Git y no debe commitearse.

## Reglas importantes

- No subir `config.php` al repositorio. Contiene credenciales privadas.
- No subir los archivos fuente pesados dentro de `medios/`; `.gitignore` solo permite `medios/web/**`.
- Usar `medios/web/` para assets publicados en la pagina.
- No restaurar `index.html`; fue eliminado porque la app real usa `index.php`.
- No tocar mobile si el usuario pide cambios solo para desktop/tablet, y viceversa.
- No quitar el patron Post/Redirect/Get del RSVP: tras guardar, `index.php` hace `header('Location: /?invitado=token&ok=1#rsvp')` y `exit`. Ese redirect evita que al recargar o volver atras se reenvie el formulario y se dupliquen los correos. Por eso ese `header()` debe ir en el bloque PHP inicial, antes de cualquier salida HTML.
- Los correos personales de aviso van solo en `config.php` (`ADMIN_EMAIL`), nunca en archivos versionados (el repo es publico en GitHub).
- El envio de correos depende de la funcion `mail()` del hosting. `MAIL_FROM` debe estar definido en `config.php` o el correo al invitado no se envia (hay un guard que lo evita romper).
- Antes de commitear cambios PHP, correr:

```bash
php -l index.php
php -l admin.php
```

Si se toca JavaScript, correr:

```bash
node -c js/main.js
```

## Deploy

El deploy se hace automaticamente con GitHub Actions al hacer push a `main`.

Workflow:

- `.github/workflows/deploy.yml`
- Action: `SamKirkland/FTP-Deploy-Action@v4.3.5`
- Secrets requeridos:
  - `FTP_SERVER`
  - `FTP_USERNAME`
  - `FTP_PASSWORD`

El workflow excluye:

- `.git`
- `.github`
- `.DS_Store`
- `config.php`
- `README.md`
- `AGENTS.md`
- `PROJECT_DESCRIPTION.md`
- `database.sql`

Despues de cada push, verificar estado con:

```bash
gh run list --limit 3
```

## Base de datos

El SQL crea:

- `invitados`
- `rsvp_historial`

El flujo RSVP usa el parametro:

```txt
/?invitado=token-personalizado
```

El panel `admin.php` genera y administra esos tokens.

Cada respuesta actualiza la fila en `invitados` (incluye `fecha_respuesta = NOW()`) y ademas inserta un registro en `rsvp_historial`. Reenviar el formulario actualiza la misma fila (no duplica invitado), pero si agrega otra fila al historial.

## Funcionalidad RSVP y correos (index.php)

El flujo del formulario RSVP en `index.php`:

- Solo procesa el guardado en `POST` con `action=confirm_rsvp`. Abrir el enlace (GET) nunca guarda ni envia correos.
- Patron Post/Redirect/Get: tras guardar, redirige a `/?invitado=token&ok=1#rsvp`. El parametro `ok=1` muestra el mensaje verde de exito. Recargar esa pagina ya no reenvia nada. No eliminar este redirect.
- Aviso "ya respondio": si el invitado ya tiene respuesta y abre su enlace, se muestra un recuadro amarillo con su respuesta registrada y la fecha (`fecha_respuesta`), indicando que reenviar actualizara su respuesta. Se oculta justo despues de guardar (cuando llega con `ok=1`).
- Confirmacion al reenviar + bloqueo de boton: el formulario usa `handleRsvpSubmit()` (en el `<script>` al final). Si ya respondio, pide `confirm()`. Siempre deshabilita el boton de envio y lo cambia a "Enviando..." para evitar envios multiples, con red de seguridad que lo reactiva a los 8s.

Correos (via `mail()` del hosting):

- Al invitado: si dejo un correo valido, recibe un correo HTML de confirmacion. Si confirma asistencia, incluye cuantos pases aparto, la fecha del evento, horarios, lugar y botones de ubicacion (Waze y Google Maps). Si declina, un mensaje breve. Remitente `MAIL_FROM`, `Reply-To` = `ADMIN_EMAIL`.
- A los organizadores: aviso a `ADMIN_EMAIL` en cada respuesta (confirme o decline). Asunto codificado UTF-8 tipo "Confirmo: <nombre> (N pases)" o "No asistira: <nombre>", con los detalles y enlace al panel. `ADMIN_EMAIL` admite varios correos separados por coma (se configura en `config.php`, no en el repo).

## Admin: carga masiva y mensajes (admin.php)

- Carga masiva (CSV): seccion "Carga masiva" que importa invitados desde un archivo CSV o pegando filas. Detecta columnas por su encabezado (nombre / invitados-pases / telefono, en cualquier orden), genera un token unico por invitado, limpia el telefono y omite nombres que ya existen (re-subir es seguro). Al terminar redirige con `?imported=&skipped=&dups=` para el resumen.
- Botones de mensaje por invitado: "Copiar" (mensaje de invitacion) y "Recordatorio" (mensaje de recordatorio). Ambos rellenan nombre, link personalizado y cantidad de pases. Funciones JS: `copyInvitation()`, `copyReminder()`, helpers `copyToClipboard()` y `pasesLabel()` (plural/singular correcto). Los mensajes piden confirmar a mas tardar el 15 de septiembre.
- Helpers nuevos en `includes/helpers.php`: `clean_phone()` (normaliza a solo digitos conservando `+` internacional) y `normalize_header()` (detecta columnas del CSV).

## Configuracion requerida en config.php

Ademas de DB y `SITE_URL`/`ADMIN_PASSWORD`, para que los correos funcionen el `config.php` del servidor debe tener:

- `MAIL_FROM`: remitente, ej. `noreply@bodaesttebanymaria.com`.
- `ADMIN_EMAIL`: destinatarios de los avisos, uno o varios separados por coma. Si queda con `example.com` o vacio, no se envia el aviso a organizadores (pero el correo al invitado si, porque solo depende de `MAIL_FROM`).

## Medios actuales

Assets web publicados:

- `medios/web/logo.png`
- `medios/web/video-chatayestteban.mp4`
- `medios/web/contador.jpg`
- `medios/web/pareja.jpg`
- `medios/web/ambiente.jpg`
- `medios/web/fecha.jpg`
- `medios/web/ceremonia.jpg`
- `medios/web/recepcion.jpg`

Los archivos originales pesados en `medios/` estan ignorados. Si se agregan nuevas imagenes, optimizarlas y copiarlas a `medios/web/`.

Ejemplo usado antes para optimizar con macOS `sips`:

```bash
sips -s format jpeg -s formatOptions 78 -Z 1800 medios/original.jpg --out medios/web/nombre.jpg
```

## Decisiones visuales actuales

- El video hero tiene overlay oscuro para legibilidad.
- El encuadre del video hero en desktop es `object-position: 50% 50%`.
- En mobile se ajusto solo el video con `object-position: 58% 50%`.
- La seccion "Cuándo y dónde" usa 2 tarjetas uniformes en tablet/desktop.
- La seccion "Cuándo y dónde" incluye iframe de Waze, marcador visual centrado y boton para abrir la ubicacion en Waze.
- Mobile mantiene comportamiento de una tarjeta/carrusel.
- En tablet/desktop:
  - `#seeyou` usa `medios/web/fecha.jpg`.
  - `#rsvp` usa `medios/web/ambiente.jpg`.
  - `#countdown` tiene encuadre especifico para que se vean mejor las manos.

## Estado actual del contenido

Informacion principal:

- Pareja: Estteban y Maria.
- Fecha: domingo 22 de noviembre de 2026.
- Fecha limite para confirmar asistencia (en los mensajes de invitacion y recordatorio): 15 de septiembre de 2026.
- Lista de invitados cargada: 55 invitaciones, 110 pases en total (importados desde un Excel con la carga masiva del admin).
- Ceremonia: 3:00 PM.
- Recepcion: 5:00 PM.
- Lugar: Finca La Ruca, San Lucas Sacatepequez.
- Recepcion solo para adultos.
- Codigo de vestimenta: formal.
- Nota: boda en jardin, San Lucas puede ponerse frio; tenis tambien bienvenidos.
- Regalos: presencia como mejor regalo, transferencia bancaria a Banco Industrial o sobres el dia del evento.
- Cuenta publicada: Banco Industrial, cuenta de ahorro `BI-1534460`; el boton copia solo `1534460`.
- El formulario RSVP visible pide asistencia, cantidad, telefono, correo opcional y mensaje. Ya no pide restricciones alimenticias ni cancion.

## Pendientes conocidos

- Confirmar si se cambiara o comprimira mas el video hero; actualmente pesa alrededor de 29 MB.
- Confirmar si se eliminan archivos plantilla no usados como `blog.html`, `post.html`, `mail.php` y secciones ocultas heredadas.
- Revisar en dispositivos reales los encuadres desktop/tablet/mobile despues de cambios visuales.
- La carga masiva de invitados por CSV ya existe; falta la exportacion CSV de respuestas desde el admin.
- Revisar entregabilidad de correos (configurar SPF/DKIM del dominio) si los avisos caen en spam, sobre todo en dominios estrictos como `@miumg.edu.gt`.
