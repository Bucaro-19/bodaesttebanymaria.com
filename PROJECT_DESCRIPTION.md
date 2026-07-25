# Descripcion del proyecto

Ultima actualizacion de traspaso: 2026-07-23.

## Resumen

Este proyecto es una invitacion web de boda para Estteban y Maria, publicada en `bodaesttebanymaria.com`.

La invitacion permite confirmar asistencia mediante enlaces personalizados por invitado. Cada invitado o familia recibe una URL con token, por ejemplo:

```txt
https://bodaesttebanymaria.com/?invitado=familia-perez-1234
```

El sitio guarda las respuestas en MySQL y tiene un panel administrativo en:

```txt
https://bodaesttebanymaria.com/admin.php
```

## Traspaso para continuar en otra cuenta

Ruta local actual:

```txt
/Users/joseaurelioporras/Documents/Proyectos Git /boda Chata y Estteban
```

Repositorio GitHub:

```txt
git@github.com:Bucaro-19/bodaesttebanymaria.com.git
```

Rama principal:

```txt
main
```

Ultimo commit remoto antes de esta documentacion:

```txt
9de442d Document session features in AGENTS and PROJECT_DESCRIPTION
```

Al abrir desde otra cuenta de Codex, primero leer:

```txt
AGENTS.md
PROJECT_DESCRIPTION.md
```

Luego confirmar:

```bash
git status --short
git log -1 --oneline
gh run list --limit 3
```

## Stack

- PHP procedural.
- MySQL/MariaDB.
- HTML/CSS/JS basado en una plantilla de boda.
- jQuery y Owl Carousel para carruseles.
- GitHub Actions para deploy FTP a BanaHosting.

## Archivos principales

- `index.php`: invitacion publica, contenido visual y formulario RSVP.
- `admin.php`: dashboard de invitados, carga masiva CSV y generador de mensajes.
- `includes/helpers.php`: funciones auxiliares para escape HTML, tokens, URLs, telefono y encabezados CSV.
- `config.example.php`: plantilla de configuracion.
- `database.sql`: script de tablas `invitados` y `rsvp_historial`.
- `.github/workflows/deploy.yml`: deploy FTP automatico.
- `css/style.css`: estilos principales y ajustes responsive.
- `js/main.js`: inicializacion de carruseles, fondos y countdown.
- `medios/web/`: imagenes/video optimizados usados por la pagina.
- `AGENTS.md`: instrucciones tecnicas para agentes.
- `PROJECT_DESCRIPTION.md`: descripcion funcional y estado del proyecto.

## Como correr localmente

Desde la raiz del proyecto:

```bash
php -S 127.0.0.1:8000
```

Abrir:

```txt
http://127.0.0.1:8000/index.php
```

Importante: `index.php` usa `config.php`. Ese archivo no se versiona. Si el entorno local no tiene MySQL activo o no puede conectarse al host configurado, puede aparecer error de `mysqli`. No cambiar la app por ese error sin revisar primero la configuracion local/hosting.

## Lo que ya se hizo

- Se conecto el repo local con GitHub:

```txt
git@github.com:Bucaro-19/bodaesttebanymaria.com.git
```

- Se creo el workflow de GitHub Actions para deploy FTP.
- Se creo el esquema SQL.
- Se creo el panel administrativo.
- Se implemento RSVP por token personalizado.
- Se configuro `config.php` como archivo ignorado para no subir credenciales.
- Se reemplazo la plantilla vieja `index.html` por flujo real en `index.php`.
- Se eliminaron/ocultaron secciones de plantilla que no se usan visualmente.
- Se agrego contenido real de la invitacion: fecha, lugar, horarios, texto principal, versiculo Colosenses 3:14, solo adultos, vestimenta formal, frio de San Lucas, tenis permitidos y regalos.
- Se corrigio ortografia visible en espanol.
- Se agregaron medios reales: logo, video de portada, fotos de pareja, contador, fecha, ambiente, ceremonia y recepcion.
- Se optimizaron fotos en `medios/web/`.
- Se ajusto el hero con overlay oscuro y encuadres desktop/mobile.
- Se ajusto la seccion "Cuándo y dónde" para tablet/desktop con 2 tarjetas, fotos uniformes y sin repeticion.
- Se agrego iframe de Waze para Finca La Ruca en "Cuándo y dónde", con marcador visual y boton para abrir la ubicacion en Waze.
- Se cambio en tablet/desktop la foto de "Esperamos compartir este dia contigo" por `fecha.jpg`, el fondo de RSVP por `ambiente.jpg` y el encuadre del contador para mostrar mejor las manos.
- Se agrego tarjeta de Banco Industrial en "Regalos": cuenta de ahorro `BI-1534460`, nombre `Lemus Chinchilla Estteban Jose O/ Rosito`, y boton para copiar solo `1534460`.
- Se simplifico el formulario RSVP: se quitaron restricciones alimenticias y cancion; se mantiene mensaje para los novios.
- Se hizo mas claro el selector de cantidad de pases: ahora se ve como una tarjeta seleccionable con flecha, texto de ayuda y resumen dinamico.
- Se agrego carga masiva de invitados por CSV en `admin.php`: detecta columnas por encabezado, genera token unico, limpia telefono y omite nombres duplicados.
- Se agregaron helpers `clean_phone()` y `normalize_header()` en `includes/helpers.php`.
- Se cargaron 55 invitaciones (110 pases) desde un Excel.
- Se agregaron correos automaticos via `mail()` del hosting: confirmacion HTML al invitado y aviso a organizadores (`ADMIN_EMAIL`) por cada respuesta.
- Se mejoro el flujo RSVP con Post/Redirect/Get para evitar reenvios al recargar, aviso cuando el invitado ya respondio, confirmacion al reenviar y bloqueo del boton "Enviando..." para evitar multiples envios.
- Se actualizaron los mensajes del admin con botones "Copiar" y "Recordatorio", incluyendo nombre, link, cantidad de pases y fecha limite 15 de septiembre.
- Se actualizo esta documentacion para traspaso entre cuentas de Codex.

## Estado funcional actual

La invitacion publica:

- Muestra logo, video hero, contador, historia, detalles, Waze, regalos y formulario RSVP.
- Usa URL personalizada con `?invitado=token`.
- Respeta la cantidad maxima de pases asignados en admin.
- Guarda la confirmacion en `invitados`.
- Guarda historial de respuestas en `rsvp_historial`.
- Puede enviar notificaciones segun configuracion en `config.php`.

El panel admin:

- Permite crear invitados/familias.
- Permite administrar pases.
- Permite importar invitados por CSV o texto pegado.
- Genera tokens/enlaces personalizados.
- Muestra estado de confirmacion.
- Permite copiar mensajes de invitacion y recordatorio por invitado.

Formulario RSVP visible actualmente:

- Asistencia.
- Cantidad de asistentes.
- Telefono.
- Correo opcional.
- Mensaje para los novios.
- El campo de cantidad de asistentes mantiene un dropdown real, pero visualmente se presenta como selector destacado para que los invitados entiendan que pueden cambiarlo.

Ya no muestra:

- Restricciones alimenticias.
- Cancion.

Nota tecnica: las columnas `restricciones_alimenticias` y `cancion` siguen existiendo en la base de datos por compatibilidad, pero se guardan vacias desde el formulario actual.

## RSVP y correos

- Guardado: solo ocurre con `POST action=confirm_rsvp`.
- Redireccion: despues de guardar, redirige a `/?invitado=token&ok=1#rsvp`.
- Reenvio: si el invitado ya respondio, se muestra aviso y se pide confirmacion antes de actualizar.
- Anti doble envio: el boton se deshabilita y cambia a "Enviando..." durante el submit.
- Correo al invitado: si dejo email valido, recibe confirmacion HTML.
- Correo a organizadores: cada respuesta envia aviso a `ADMIN_EMAIL`; puede haber varios correos separados por coma en `config.php`.

## Ubicacion y Waze

La seccion "Cuándo y dónde" incluye:

- Iframe de Waze con coordenadas `lat=14.598513` y `lon=-90.657937`.
- Marcador visual propio encima del iframe, porque el embed de Waze puede no mostrar pin.
- Boton "Abrir ubicación en Waze" usando el link universal compartido por el usuario:

```txt
https://ul.waze.com/ul?venue_id=176488594.1765148084.896612&overview=yes&utm_campaign=default&utm_source=waze_website&utm_medium=lm_share_location
```

Tambien existe una URL de mapa compartido de Waze como referencia:

```txt
https://www.waze.com/en/live-map/directions/finca-la-ruca-canton-reforma-3-52-san-lucas-sacatepequez?place=w.176488594.1765148084.896612
```

## Regalos

La seccion de regalos incluye tarjeta con estilo inspirado en Banco Industrial:

- Titulo: Banco Industrial.
- Cuenta: `Cuenta de ahorro BI-1534460`.
- Nombre: `Lemus Chinchilla Estteban Jose O/ Rosito`.
- El boton copia solo el numero `1534460`.

## Configuracion del hosting

El hosting es BanaHosting/cPanel.

El archivo real `config.php` debe existir en el servidor junto a `index.php`. No se sube por GitHub Actions.

La base de datos ya fue creada e importada segun el usuario:

- Tabla `invitados`
- Tabla `rsvp_historial`

No escribir credenciales reales en archivos versionados. Usar `config.example.php` como referencia.

Para correos, `config.php` del servidor debe definir:

- `MAIL_FROM`: remitente.
- `ADMIN_EMAIL`: destinatarios de avisos; admite varios correos separados por coma.

Correo de notificaciones y credenciales reales viven fuera del repo, dentro de `config.php` o cPanel. No documentarlas en Git.

## Flujo de uso

1. Entrar a `/admin.php`.
2. Crear invitado o familia con cantidad de pases, o usar carga masiva CSV.
3. Copiar mensaje/enlace personalizado desde el panel.
4. Invitado abre su URL y confirma asistencia.
5. Respuesta se guarda en `invitados`.
6. Cada respuesta tambien queda registrada en `rsvp_historial`.
7. Si hay correos configurados, se envia confirmacion al invitado y aviso a organizadores.

## Estado del deploy

Cada push a `main` despliega por FTP automaticamente.

Comando util:

```bash
gh run list --limit 3
```

El ultimo deploy revisado antes de esta documentacion estaba en `success`.

Los documentos `AGENTS.md` y `PROJECT_DESCRIPTION.md` estan excluidos del upload FTP, pero si se commitean/pushean siguen disponibles en GitHub.

## Pendientes o mejoras posibles

- Comprimir video hero con `ffmpeg` si el peso de 29 MB afecta carga en celular.
- Eliminar archivos heredados que ya no se usan: `blog.html`, `post.html`, `mail.php` e imagenes viejas de plantilla en `images/`.
- Agregar exportacion CSV de respuestas desde el admin; la carga masiva por CSV ya esta hecha.
- Revisar entregabilidad de correos (SPF/DKIM del dominio) si los avisos caen en spam.
- Revisar visualmente en celulares reales despues de cada cambio de encuadre.
- Verificar con el usuario si quiere mantener columnas antiguas `restricciones_alimenticias` y `cancion` o eliminarlas en una migracion futura.
- Hacer una prueba completa real: crear invitado de prueba, abrir link personalizado, confirmar asistencia, verificar BD, verificar correo y borrar o marcar el invitado de prueba.
