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
- Fecha: 22 de noviembre de 2026.
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
