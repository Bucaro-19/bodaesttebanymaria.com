# Descripcion del proyecto

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

## Stack

- PHP procedural.
- MySQL/MariaDB.
- HTML/CSS/JS basado en una plantilla de boda.
- jQuery y Owl Carousel para carruseles.
- GitHub Actions para deploy FTP a BanaHosting.

## Archivos principales

- `index.php`: invitacion publica, contenido visual y formulario RSVP.
- `admin.php`: dashboard de invitados.
- `includes/helpers.php`: funciones auxiliares para escape HTML, tokens y URLs.
- `config.example.php`: plantilla de configuracion.
- `database.sql`: script de tablas `invitados` y `rsvp_historial`.
- `.github/workflows/deploy.yml`: deploy FTP automatico.
- `css/style.css`: estilos principales y ajustes responsive.
- `js/main.js`: inicializacion de carruseles, fondos y countdown.
- `medios/web/`: imagenes/video optimizados usados por la pagina.

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
- Se agrego contenido real de la invitacion:
  - Fecha, lugar, horarios.
  - Texto "Por todas las veces que nos preguntaron..."
  - Versiculo Colosenses 3:14.
  - Solo adultos.
  - Vestimenta formal, frio de San Lucas y tenis permitidos.
  - Regalos.
- Se corrigio ortografia visible en espanol.
- Se agregaron medios reales:
  - Logo.
  - Video de portada.
  - Fotos de pareja, contador, fecha, ambiente, ceremonia y recepcion.
- Se optimizaron fotos en `medios/web/`.
- Se ajusto el hero:
  - Overlay oscuro para legibilidad.
  - Encuadre desktop y mobile.
- Se ajusto la seccion "Cuándo y dónde" para tablet/desktop:
  - 2 tarjetas.
  - Fotos uniformes.
  - Sin repeticion.
- Se agrego iframe de Waze para Finca La Ruca en "Cuándo y dónde", con marcador visual y boton para abrir la ubicacion en Waze.
- Se cambio en tablet/desktop:
  - Foto de "Esperamos compartir este dia contigo" por `fecha.jpg`.
  - Fondo de RSVP por `ambiente.jpg`.
  - Encuadre de contador para mostrar mejor las manos.
- Se agrego tarjeta de Banco Industrial en "Regalos":
  - Cuenta de ahorro `BI-1534460`.
  - Nombre: Lemus Chinchilla Estteban Jose O/ Rosito.
  - Boton para copiar solo `1534460`.
- Se simplifico el formulario RSVP:
  - Se quitaron los campos de restricciones alimenticias y cancion.
  - Se mantiene mensaje para los novios.
- Se agrego carga masiva de invitados por CSV en `admin.php`:
  - Detecta columnas por encabezado, genera token unico, limpia telefono y omite nombres duplicados.
  - Helpers `clean_phone()` y `normalize_header()` en `includes/helpers.php`.
  - Se cargaron 55 invitaciones (110 pases) desde un Excel.
- Se agregaron correos automaticos (via `mail()` del hosting):
  - Al invitado: confirmacion HTML con pases, fecha, lugar y botones de ubicacion (Waze y Google Maps), o mensaje breve si declina.
  - A los organizadores (`ADMIN_EMAIL`, varios correos por coma): aviso por cada respuesta, con asunto claro y enlace al panel.
- Se mejoro el flujo del RSVP:
  - Patron Post/Redirect/Get para que recargar no reenvie el formulario ni duplique correos.
  - Aviso cuando el invitado ya respondio (con su respuesta y fecha) y confirmacion al reenviar.
  - Bloqueo del boton de envio ("Enviando...") para evitar envios multiples.
- Se actualizaron los mensajes del admin:
  - Botones "Copiar" (invitacion) y "Recordatorio" por invitado, con nombre, link y cantidad de pases, y fecha limite 15 de septiembre.

## Configuracion del hosting

El hosting es BanaHosting/cPanel.

El archivo real `config.php` debe existir en el servidor junto a `index.php`. No se sube por GitHub Actions.

La base de datos ya fue creada e importada segun el usuario:

- Tabla `invitados`
- Tabla `rsvp_historial`

No escribir credenciales reales en archivos versionados. Usar `config.example.php` como referencia.

Para los correos, el `config.php` del servidor debe definir `MAIL_FROM` (remitente) y `ADMIN_EMAIL` (destinatarios de los avisos; admite varios correos separados por coma). Estos correos personales viven solo en `config.php`, nunca en el repo publico.

## Flujo de uso

1. Entrar a `/admin.php`.
2. Crear invitado o familia con cantidad de pases.
3. Copiar mensaje/enlace personalizado desde el panel.
4. Invitado abre su URL y confirma asistencia.
5. Respuesta se guarda en `invitados`.
6. Cada respuesta tambien queda registrada en `rsvp_historial`.

## Estado del deploy

Cada push a `main` despliega por FTP automaticamente.

Comando util:

```bash
gh run list --limit 3
```

## Pendientes o mejoras posibles

- Comprimir video hero con `ffmpeg` si el peso de 29 MB afecta carga en celular.
- Eliminar archivos heredados que ya no se usan:
  - `blog.html`
  - `post.html`
  - `mail.php`
  - imagenes viejas de plantilla en `images/`
- Agregar exportacion CSV de respuestas desde el admin (la carga masiva por CSV ya esta hecha).
- Revisar entregabilidad de correos (SPF/DKIM del dominio) si los avisos caen en spam.
- Revisar visualmente en celulares reales despues de cada cambio de encuadre.
