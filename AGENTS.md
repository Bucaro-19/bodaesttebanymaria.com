# AGENTS.md

Guia para agentes que continuen este proyecto de invitacion de boda.

Ultima actualizacion de traspaso: 2026-07-23.

## Traspaso rapido para nueva cuenta de Codex

Al continuar desde otra cuenta o hilo:

1. Abrir este folder local:

```txt
/Users/joseaurelioporras/Documents/Proyectos Git /boda Chata y Estteban
```

2. Leer primero estos dos archivos:

```txt
AGENTS.md
PROJECT_DESCRIPTION.md
```

3. Confirmar el estado del repositorio:

```bash
git status --short
git log -1 --oneline
git remote -v
gh run list --limit 3
```

Estado conocido al momento de documentar el traspaso:

- Rama principal: `main`.
- Remoto: `git@github.com:Bucaro-19/bodaesttebanymaria.com.git`.
- Ultimo commit remoto antes de esta documentacion: `9de442d Document session features in AGENTS and PROJECT_DESCRIPTION`.
- El deploy anterior revisado termino en `success` en GitHub Actions.

Si la nueva cuenta de Codex no tiene acceso al repo por SSH, revisar llaves SSH/GitHub antes de intentar hacer push.

## Contexto

Este repositorio contiene la invitacion web de boda de Estteban y Maria para `https://bodaesttebanymaria.com`.

La app es PHP/MySQL sin framework:

- `index.php`: invitacion publica y formulario RSVP por URL personalizada.
- `admin.php`: panel privado para crear invitados, editar pases, copiar enlaces, cargar invitados por CSV y revisar respuestas.
- `database.sql`: estructura de base de datos.
- `config.example.php`: plantilla de configuracion.
- `config.php`: configuracion real local/hosting. Esta ignorado por Git y no debe commitearse.

## Reglas importantes

- No subir `config.php` al repositorio. Contiene credenciales privadas.
- No escribir credenciales reales de base de datos, FTP, correo o admin en archivos versionados.
- No subir los archivos fuente pesados dentro de `medios/`; `.gitignore` solo permite `medios/web/**`.
- Usar `medios/web/` para assets publicados en la pagina.
- No restaurar `index.html`; fue eliminado porque la app real usa `index.php`.
- No tocar mobile si el usuario pide cambios solo para desktop/tablet, y viceversa.
- Si el usuario pide "solo mobile", proteger desktop/tablet. Si pide "solo desktop/tablet", proteger mobile.
- Mantener actualizados `AGENTS.md` y `PROJECT_DESCRIPTION.md` cuando se haga un cambio relevante.
- No quitar el patron Post/Redirect/Get del RSVP: tras guardar, `index.php` hace `header('Location: /?invitado=token&ok=1#rsvp')` y `exit`. Ese redirect evita que al recargar o volver atras se reenvie el formulario y se dupliquen los correos. Por eso ese `header()` debe ir en el bloque PHP inicial, antes de cualquier salida HTML.
- Los correos personales de aviso van solo en `config.php` (`ADMIN_EMAIL`), nunca en archivos versionados.
- El envio de correos depende de la funcion `mail()` del hosting. `MAIL_FROM` debe estar definido en `config.php` o el correo al invitado no se envia.

Antes de commitear cambios PHP, correr:

```bash
php -l index.php
php -l admin.php
```

Si se toca JavaScript, correr:

```bash
node -c js/main.js
```

Para levantar localmente:

```bash
php -S 127.0.0.1:8000
```

Abrir:

```txt
http://127.0.0.1:8000/index.php
```

Nota local: si `config.php` apunta a BanaHosting o a `localhost` sin MySQL local activo, PHP puede fallar al conectar. Eso no necesariamente indica que el hosting este roto. Verificar siempre el entorno antes de cambiar credenciales.

## Deploy

El deploy se hace automaticamente con GitHub Actions al hacer push a `main`.

Workflow:

- `.github/workflows/deploy.yml`
- Action: `SamKirkland/FTP-Deploy-Action@v4.3.5`
- Secrets requeridos: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`.

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

El workflow despliega el sitio, pero excluye los documentos de traspaso. Aun asi, un push solo de documentacion puede disparar el workflow; verificar que finalice en `success`.

## Base de datos

El SQL crea:

- `invitados`
- `rsvp_historial`

El flujo RSVP usa el parametro:

```txt
/?invitado=token-personalizado
```

El panel `admin.php` genera y administra esos tokens.

Cada respuesta actualiza la fila en `invitados` (incluye `fecha_respuesta = NOW()`) y ademas inserta un registro en `rsvp_historial`. Reenviar el formulario actualiza la misma fila, no duplica invitado, pero si agrega otra fila al historial.

Importante: aunque la interfaz publica ya no muestra `restricciones_alimenticias` ni `cancion`, las columnas siguen existiendo en la base de datos y en el backend se guardan vacias si no vienen en el POST. No hacer migraciones destructivas sin confirmacion del usuario.

## Funcionalidad RSVP y correos

Flujo del formulario RSVP en `index.php`:

- Solo procesa el guardado en `POST` con `action=confirm_rsvp`. Abrir el enlace con `GET` nunca guarda ni envia correos.
- Patron Post/Redirect/Get: tras guardar, redirige a `/?invitado=token&ok=1#rsvp`. El parametro `ok=1` muestra el mensaje verde de exito. Recargar esa pagina ya no reenvia nada.
- Aviso "ya respondio": si el invitado ya tiene respuesta y abre su enlace, se muestra un recuadro amarillo con su respuesta registrada y la fecha (`fecha_respuesta`), indicando que reenviar actualizara su respuesta.
- Confirmacion al reenviar y bloqueo de boton: el formulario usa `handleRsvpSubmit()` al final de `index.php`. Si ya respondio, pide `confirm()`. Siempre deshabilita el boton de envio y lo cambia a "Enviando..." para evitar envios multiples, con red de seguridad que lo reactiva a los 8s.

Correos via `mail()` del hosting:

- Al invitado: si dejo un correo valido, recibe un correo HTML de confirmacion. Si confirma asistencia, incluye pases, fecha, horarios, lugar y botones de ubicacion (Waze y Google Maps). Si declina, recibe un mensaje breve. Remitente `MAIL_FROM`, `Reply-To` = `ADMIN_EMAIL`.
- A organizadores: aviso a `ADMIN_EMAIL` en cada respuesta, con asunto tipo "Confirmo: <nombre> (N pases)" o "No asistira: <nombre>", detalles y enlace al panel. `ADMIN_EMAIL` admite varios correos separados por coma en `config.php`.

## Admin: carga masiva y mensajes

- Carga masiva CSV en `admin.php`: importa invitados desde archivo CSV o pegando filas. Detecta columnas por encabezado (nombre, invitados/pases, telefono), genera token unico, limpia telefono y omite nombres duplicados.
- Botones por invitado: "Copiar" para mensaje de invitacion y "Recordatorio" para mensaje de recordatorio. Ambos usan nombre, link personalizado, cantidad de pases y fecha limite 15 de septiembre.
- Funciones JS: `copyInvitation()`, `copyReminder()`, `copyToClipboard()` y `pasesLabel()`.
- Helpers en `includes/helpers.php`: `clean_phone()` y `normalize_header()`.

## Configuracion requerida en config.php

Ademas de DB y `SITE_URL`/`ADMIN_PASSWORD`, para que los correos funcionen el `config.php` del servidor debe tener:

- `MAIL_FROM`: remitente, por ejemplo `noreply@bodaesttebanymaria.com`.
- `ADMIN_EMAIL`: destinatarios de avisos, uno o varios separados por coma.

Si `ADMIN_EMAIL` queda vacio o con dominio de ejemplo, no se envia aviso a organizadores. No documentar credenciales ni correos personales reales en Git.

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
- El logo publicado es `medios/web/logo.png` y se aumento de tamaño en desktop y mobile.
- La seccion "Cuándo y dónde" usa 2 tarjetas uniformes en tablet/desktop.
- La seccion "Cuándo y dónde" incluye iframe de Waze, marcador visual centrado y boton para abrir la ubicacion en Waze.
- El marcador visual de Waze se agrego porque el iframe puede no mostrar un pin aunque las coordenadas sean correctas.
- El boton de Waze apunta a una URL universal `ul.waze.com` compartida por el usuario.
- Mobile mantiene comportamiento de una tarjeta/carrusel.
- En tablet/desktop: `#seeyou` usa `medios/web/fecha.jpg`, `#rsvp` usa `medios/web/ambiente.jpg`, y `#countdown` tiene encuadre especifico para que se vean mejor las manos.
- En mobile: `#seeyou` tambien debe usar `medios/web/fecha.jpg`, tiene overlay/opacity para contraste del texto, y `#rsvp` debe usar `medios/web/ambiente.jpg`.

## Estado actual del contenido

Informacion principal:

- Pareja: Estteban y Maria.
- Fecha: domingo 22 de noviembre de 2026.
- Fecha limite para confirmar asistencia en mensajes de invitacion/recordatorio: 15 de septiembre de 2026.
- Lista de invitados cargada: 55 invitaciones, 110 pases en total (importados desde Excel con carga masiva del admin).
- Ceremonia: 3:00 PM.
- Recepcion: 5:00 PM.
- Lugar: Finca La Ruca, San Lucas Sacatepequez.
- Recepcion solo para adultos.
- Codigo de vestimenta: formal.
- Nota: boda en jardin, San Lucas puede ponerse frio; tenis tambien bienvenidos.
- Regalos: presencia como mejor regalo, transferencia bancaria a Banco Industrial o sobres el dia del evento.
- Cuenta publicada: Banco Industrial, cuenta de ahorro `BI-1534460`; el boton copia solo `1534460`.
- El formulario RSVP visible pide asistencia, cantidad, telefono, correo opcional y mensaje. Ya no pide restricciones alimenticias ni cancion.
- El campo de cantidad de pases usa el `select` real `cantidad_asistentes`, pero esta envuelto en una tarjeta visual (`pass-select-card`) con flecha, etiqueta y resumen dinamico para que sea claro que se puede tocar/cambiar. Inicia con "Seleccione cuántos pases" y no permite confirmar asistencia sin escoger una cantidad valida; si el invitado indica que no asistira, el selector se deshabilita.

## Archivos heredados y no usados

El repo todavia conserva archivos de plantilla original que podrian eliminarse mas adelante si el usuario lo aprueba:

- `blog.html`
- `post.html`
- `mail.php`
- varias imagenes antiguas dentro de `images/`

No eliminarlos sin confirmacion, porque el usuario puede querer comparar o rescatar algo visual.

## Checklist antes de entregar cambios futuros

- Revisar `git status --short` antes y despues.
- Si se toca PHP: `php -l index.php` y/o `php -l admin.php`.
- Si se toca JS: `node -c js/main.js`.
- Si se toca frontend visual, probar al menos en desktop y mobile localmente.
- Si se hace push: revisar `gh run list --limit 3`.
- Documentar cambios relevantes en este archivo y en `PROJECT_DESCRIPTION.md`.

## Pendientes conocidos

- Confirmar si se cambiara o comprimira mas el video hero; actualmente pesa alrededor de 29 MB.
- Confirmar si se eliminan archivos plantilla no usados como `blog.html`, `post.html`, `mail.php` y secciones ocultas heredadas.
- Revisar en dispositivos reales los encuadres desktop/tablet/mobile despues de cambios visuales.
- La carga masiva de invitados por CSV ya existe; falta la exportacion CSV de respuestas desde el admin.
- Revisar entregabilidad de correos (configurar SPF/DKIM del dominio) si los avisos caen en spam, sobre todo en dominios estrictos.
- Verificar con el usuario si quiere mantener columnas antiguas `restricciones_alimenticias` y `cancion` o eliminarlas en una migracion futura.
