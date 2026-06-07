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
define('MAIL_FROM', 'notificaciones@bodaesttebanymaria.com');
```

## Uso

1. Entra a `/admin.php`.
2. Agrega cada invitado o familia con su cantidad de pases.
3. Usa el boton **Copiar** para generar el mensaje con URL personalizada.
4. Cuando el invitado confirme, la respuesta queda guardada en `invitados` y se registra una copia en `rsvp_historial`.

## Pendiente de personalizacion

Falta reemplazar los textos, fecha, ubicacion, fotos y videos definitivos cuando esten listos.

