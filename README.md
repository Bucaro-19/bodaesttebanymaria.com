# Invitacion de boda

Proyecto PHP/MySQL para invitacion de boda con enlaces personalizados de RSVP y panel administrativo.

## Archivos principales

- `index.php`: invitacion publica. El formulario funciona solo con URL personalizada, por ejemplo `/?invitado=familia-perez-1234`.
- `admin.php`: panel para crear invitados, editar pases, copiar mensajes y revisar respuestas.
- `database.sql`: estructura de base de datos para importar en phpMyAdmin.
- `config.example.php`: plantilla de configuracion. En el servidor se debe copiar como `config.php`.

## Configuracion en BanaHosting / cPanel

1. Entra a cPanel y abre **MySQL Databases**.
2. Crea una base de datos, por ejemplo `usuario_boda`.
3. Crea un usuario MySQL, por ejemplo `usuario_boda_user`, con una contrasena fuerte.
4. Asigna ese usuario a la base de datos con **ALL PRIVILEGES**.
5. Abre **phpMyAdmin**, selecciona la base de datos e importa `database.sql`.
6. Copia `config.example.php` como `config.php` y cambia estos valores:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'usuario_boda');
define('DB_USER', 'usuario_boda_user');
define('DB_PASS', 'CONTRASENA_DEL_USUARIO');

define('SITE_URL', 'https://bodaesttebanymaria.com');
define('ADMIN_PASSWORD', 'CONTRASENA_DEL_PANEL');
define('ADMIN_EMAIL', 'correo-para-notificaciones@example.com');
define('MAIL_FROM', 'noreply@bodaesttebanymaria.com');
```

## Uso

1. Entra a `/admin.php`.
2. Agrega cada invitado o familia con su cantidad de pases.
3. Usa el boton **Copiar** para generar el mensaje con URL personalizada.
4. Cuando el invitado confirme, la respuesta queda guardada en `invitados` y se registra una copia en `rsvp_historial`.

## Deploy con GitHub Actions

El repositorio incluye `.github/workflows/deploy.yml`. Cada push a `main` sube los archivos por FTP usando estos secrets del repositorio:

- `FTP_SERVER`
- `FTP_USERNAME`
- `FTP_PASSWORD`

El workflow no sube `config.php` porque contiene credenciales privadas. Crea ese archivo una vez en el hosting usando `config.example.php` como plantilla.

Si el usuario FTP entra directo a `public_html`, deja `server-dir: ./`. Si entra al home de cPanel, cambia esa linea por:

```yaml
server-dir: public_html/
```

## Vista local

No abras `index.html`; el proyecto real usa `index.php`. Para verlo localmente, usa un servidor PHP desde la carpeta del proyecto:

```bash
php -S localhost:8000
```

Despues abre `http://localhost:8000/index.php`. Para probar el formulario necesitas una base de datos accesible y un token creado desde `/admin.php`.

## Pendiente de personalizacion

Falta reemplazar fotos y videos definitivos cuando esten listos.
