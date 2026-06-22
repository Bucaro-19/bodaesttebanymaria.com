<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

$token_url = trim((string)($_GET['invitado'] ?? ''));
$invitado = null;
$form_message = '';
$form_status = '';

if ($token_url !== '' && DB_AVAILABLE) {
    $stmt = $conn->prepare('SELECT id, nombre, token, pases, telefono, email, asiste, cantidad_asistentes, mensaje, restricciones_alimenticias, cancion FROM invitados WHERE token = ? LIMIT 1');
    $stmt->bind_param('s', $token_url);
    $stmt->execute();
    $invitado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($token_url !== '') {
    $form_status = 'warning';
    $form_message = 'Vista local sin conexión a base de datos. La invitación se puede revisar, pero el RSVP necesita MySQL.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_rsvp') {
    if (!DB_AVAILABLE) {
        $form_status = 'danger';
        $form_message = 'No hay conexión a la base de datos. En el hosting funcionará cuando config.php esté instalado y la BD exista.';
    } elseif (!$invitado) {
        $form_status = 'danger';
        $form_message = 'Este enlace no es válido. Por favor confirma desde tu invitación personalizada.';
    } else {
        $asiste = ($_POST['asiste'] ?? '') === 'si' ? 1 : 0;
        $pases = max(1, (int)$invitado['pases']);
        $cantidad = $asiste === 1 ? max(1, min($pases, (int)($_POST['cantidad_asistentes'] ?? 1))) : 0;
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $mensaje = trim((string)($_POST['mensaje'] ?? ''));
        $restricciones = trim((string)($_POST['restricciones_alimenticias'] ?? ''));
        $cancion = trim((string)($_POST['cancion'] ?? ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_status = 'danger';
            $form_message = 'Revisa el correo, parece que no tiene un formato válido.';
        } else {
            $stmt = $conn->prepare(
                'UPDATE invitados
                 SET asiste = ?, cantidad_asistentes = ?, telefono = ?, email = ?, mensaje = ?, restricciones_alimenticias = ?, cancion = ?, fecha_respuesta = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param('iisssssi', $asiste, $cantidad, $telefono, $email, $mensaje, $restricciones, $cancion, $invitado['id']);
            $saved = $stmt->execute();
            $stmt->close();

            if ($saved) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $user_agent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
                $stmt = $conn->prepare(
                    'INSERT INTO rsvp_historial (invitado_id, asiste, cantidad_asistentes, telefono, email, mensaje, restricciones_alimenticias, cancion, ip, user_agent)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('iiisssssss', $invitado['id'], $asiste, $cantidad, $telefono, $email, $mensaje, $restricciones, $cancion, $ip, $user_agent);
                $stmt->execute();
                $stmt->close();

                $invitado['asiste'] = $asiste;
                $invitado['cantidad_asistentes'] = $cantidad;
                $invitado['telefono'] = $telefono;
                $invitado['email'] = $email;
                $invitado['mensaje'] = $mensaje;
                $invitado['restricciones_alimenticias'] = $restricciones;
                $invitado['cancion'] = $cancion;

                $form_status = 'success';
                $form_message = 'Gracias, tu respuesta quedó guardada correctamente.';

                if (defined('ADMIN_EMAIL') && ADMIN_EMAIL !== '' && strpos(ADMIN_EMAIL, 'example.com') === false) {
                    $estado = $asiste === 1 ? 'Sí asistirá' : 'No asistirá';
                    $subject = 'RSVP boda: ' . $invitado['nombre'];
                    $body = "Nueva respuesta de boda\n\n";
                    $body .= "Invitado: {$invitado['nombre']}\n";
                    $body .= "Estado: {$estado}\n";
                    $body .= "Cantidad: {$cantidad}\n";
                    $body .= "Teléfono: {$telefono}\n";
                    $body .= "Email: {$email}\n";
                    $body .= "Restricciones: {$restricciones}\n";
                    $body .= "Canción: {$cancion}\n";
                    $body .= "Mensaje: {$mensaje}\n";
                    $headers = 'From: ' . MAIL_FROM . "\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    @mail(ADMIN_EMAIL, $subject, $body, $headers);
                }
            } else {
                $form_status = 'danger';
                $form_message = 'No se pudo guardar tu respuesta. Intenta de nuevo en unos minutos.';
            }
        }
    }
}
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Estteban & María | Invitación de boda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Invitación de boda de Estteban y María. 22 de noviembre de 2026 en Finca La Ruca, San Lucas Sacatepéquez.">
    <meta name="keywords" content="boda Estteban María, invitación de boda, RSVP boda">
    <meta name="author" content="Estteban y María">
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/png" href="images/favicon.png" />
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="lds-heart">
            <div></div>
        </div>
    </div>
    <!-- Main -->
    <div id="oliven-page"> 
        <a href="#" class="js-oliven-nav-toggle oliven-nav-toggle"><i></i></a>
        <!-- Sidebar Section -->
        <aside id="oliven-aside">
            <!-- Logo -->
            <div class="oliven-logo">
                <a href="#home">
                    <img src="medios/web/logo.png" alt="Estteban y María">
                </a>
            </div>
            <!-- Menu -->
            <nav class="oliven-main-menu">
                <ul>
                    <li><a href="#home">Inicio</a></li>
                    <li><a href="#couple">Nos casamos</a></li>
                    <li><a href="#story">Invitación</a></li>
                    <li><a href="#whenwhere">Cuándo y dónde</a></li>
                    <li><a href="#rsvp">R.S.V.P</a></li>
                    <li><a href="#gift">Regalos</a></li>
                </ul>
            </nav>
            <!-- Sidebar Footer -->
            <div class="footer1"> <span class="separator"></span>
                <p>Boda de Estteban y María
                    <br />22 de noviembre de 2026
                </p>
            </div>
        </aside>
        <!-- Content Section -->
        <div id="oliven-main">
            <!-- Header Video -->
            <header id="home" class="video-fullscreen-wrap position-relative">
                <!-- The opacity on the image is made with "data-overlay-dark="number". You can change it using the numbers 0-9. -->
                <div class="video-fullscreen-video wedding-video" data-overlay-dark="7">
                    <video playsinline="" autoplay="" loop="" muted="">
                        <source src="medios/web/video-chatayestteban.mp4" type="video/mp4" autoplay="" loop="" muted="">
                    </video>
                </div>
                <div class="v-middle caption overlay">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-12">
                                <h1>Estteban & María</h1>
                                <h5>22 de noviembre de 2026 - Finca La Ruca</h5>
                            </div>
                        </div>
                        <!-- scroll down -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="arrow bounce text-center">
                                    <a href="#couple"> <i class="ti-heart"></i> </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- Nos casamos -->
            <div id="couple" class="bridegroom clear section-padding bg-pink">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 text-center animate-box" data-animate-effect="fadeInUp">
                            <h3 class="oliven-couple-title">¡Nos casamos!</h3>
                            <h4 class="oliven-couple-subtitle">22 de noviembre de 2026 - San Lucas Sacatepéquez</h4>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Countdown -->
            <div id="countdown" class="section-padding bg-img bg-fixed" data-background="medios/web/contador.jpg">
                <div class="container">
                    <div class="row">
                        <div class="section-head col-md-12">
                            <h4>Falta para celebrar juntos</h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <ul>
                                <li><span id="days"></span>Días</li>
                                <li><span id="hours"></span>Horas</li>
                                <li><span id="minutes"></span>Minutos</li>
                                <li><span id="seconds"></span>Segundos</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Nuestra historia -->
            <div id="story" class="story section-padding">
                <div class="container">
                    <div class="row">
                        <div class="col-md-5 mb-30">
                            <div class="story-img animate-box" data-animate-effect="fadeInLeft">
                                <div class="img"> <img src="medios/web/pareja.jpg" class="img-fluid" alt="Estteban y María"> </div>
                            </div>
                        </div>
                        <div class="col-md-7 animate-box" data-animate-effect="fadeInRight">
                            <div class="story-editorial">
                                <p class="se-leadin">Por todas las veces que nos preguntaron</p>
                                <div class="se-script">¿Y pa' cuándo?</div>
                                <p class="se-answer">¡Pues ya! Nos alegra contarles que llegó el momento de responder esa pregunta.</p>

                                <p class="se-body">Dios ha sido bueno con nosotros y nos ha guiado hasta este momento. Por eso, con mucha alegría, queremos invitarlos a acompañarnos el día en que uniremos nuestras vidas delante de Dios.</p>
                                <p class="se-body">Y como ninguna historia se celebra igual sin las personas que han sido parte de ella, nos encantará compartir este momento con ustedes.</p>

                                <div class="se-details">
                                    <p><strong class="se-label">Recepción solo para adultos</strong>Aunque queremos mucho a los pequeños de la familia, en esta ocasión la celebración será exclusivamente para adultos. Gracias por comprender y por acompañarnos en este día tan especial.</p>
                                    <p><strong class="se-label">Código de vestimenta · Formal</strong>La boda será en jardín y San Lucas en noviembre tiene fama de ponerse frío, así que no olviden traer algo para abrigarse.</p>
                                    <p>Y aunque el código es formal, creemos firmemente que nadie debería sufrir por unos zapatos incómodos. Los tenis también son bienvenidos.</p>
                                </div>

                                <div class="se-verse">
                                    <span class="se-dots"></span>
                                    <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#BD945A"/></svg>
                                    <p>"Y sobre todas estas cosas vestíos de amor, que es el vínculo perfecto."</p>
                                    <div class="se-cite">Colosenses 3:14</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Friends -->
            <div id="friends" class="friends section-padding bg-pink" style="display: none;">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 mb-30"> <span class="oliven-title-meta">Our best friends ever</span>
                            <h2 class="oliven-title mb-30">Thanks for being there</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="owl-carousel owl-theme">
                                <div class="item">
                                    <div class="img"> <img src="images/friends/b1.jpg" alt=""> </div>
                                    <div class="info valign">
                                        <div class="full-width">
                                            <h6>Eleanor Chris</h6><span>Bridesmaids</span>
                                            <p>Enstibulum eringilla dui athe elitene miss minibus viverra nectar.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="img"> <img src="images/friends/w1.jpg" alt=""> </div>
                                    <div class="info valign">
                                        <div class="full-width">
                                            <h6>Stefano Smiht</h6><span>Groomsmen</span>
                                            <p>Enstibulum eringilla dui athe elitene miss minibus viverra nectar.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="img"> <img src="images/friends/b2.jpg" alt=""> </div>
                                    <div class="info valign">
                                        <div class="full-width">
                                            <h6>Vanessa Brown</h6><span>Bridesmaids</span>
                                            <p>Enstibulum eringilla dui athe elitene miss minibus viverra nectar.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="img"> <img src="images/friends/w2.jpg" alt=""> </div>
                                    <div class="info valign">
                                        <div class="full-width">
                                            <h6>Matthew Brown</h6><span>Groomsmen</span>
                                            <p>Enstibulum eringilla dui athe elitene miss minibus viverra nectar.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="img"> <img src="images/friends/b3.jpg" alt=""> </div>
                                    <div class="info valign">
                                        <div class="full-width">
                                            <h6>Fredia Halle</h6><span>Bridesmaids</span>
                                            <p>Enstibulum eringilla dui athe elitene miss minibus viverra nectar.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="img"> <img src="images/friends/w3.jpg" alt=""> </div>
                                    <div class="info valign">
                                        <div class="full-width">
                                            <h6>Pablo Dante</h6><span>Groomsmen</span>
                                            <p>Enstibulum eringilla dui athe elitene miss minibus viverra nectar.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-5">
                        <div class="col-md-12">
                            <div class="waze-map">
                                <iframe src="https://embed.waze.com/iframe?zoom=16&lat=14.598513&lon=-90.657937&ct=livemap" width="600" height="450" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- See you -->
            <div id="seeyou" class="seeyou section-padding bg-img bg-fixed" data-background="medios/web/fecha.jpg">
                <div class="container">
                    <div class="row">
                        <div class="section-head col-md-12 text-center"> <span><i class="ti-heart"></i></span>
                            <h4>Esperamos compartir este día contigo</h4>
                            <h3>22.11.2026</h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Organization -->
            <div id="organization" class="organization section-padding bg-pink" style="display: none;">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 mb-30"> <span class="oliven-title-meta">Wedding</span>
                            <h2 class="oliven-title">Organization</h2>
                        </div>
                    </div>
                    <div class="row bord-box bg-img" data-background="images/slider.jpg">
                        <div class="col-md-3 item-box">
                            <h2 class="custom-font numb">01</h2>
                            <h6 class="title">Ceremony</h6>
                            <p>Delta tristiu the jusone duise vitae diam neque nivami mis est augue artine aringilla the at elit finibus vivera.</p>
                        </div>
                        <div class="col-md-3 item-box">
                            <h2 class="custom-font numb">02</h2>
                            <h6 class="title">Lunch Time</h6>
                            <p>Delta tristiu the jusone duise vitae diam neque nivami mis est augue artine aringilla the at elit finibus vivera.</p>
                        </div>
                        <div class="col-md-3 item-box">
                            <h2 class="custom-font numb">03</h2>
                            <h6 class="title">Party</h6>
                            <p>Delta tristiu the jusone duise vitae diam neque nivami mis est augue artine aringilla the at elit finibus vivera.</p>
                        </div>
                        <div class="col-md-3 item-box">
                            <h2 class="custom-font numb">04</h2>
                            <h6 class="title">Cake Cutting</h6>
                            <p>Delta tristiu the jusone duise vitae diam neque nivami mis est augue artine aringilla the at elit finibus vivera.</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Gallery -->
            <div id="gallery" class="section-padding" style="display: none;">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 mb-30"> <span class="oliven-title-meta">Gallery</span>
                            <h2 class="oliven-title">Our Memories</h2>
                        </div>
                    </div>
                    <div class="row">
                        <ul class="col list-unstyled list-inline mb-0 gallery-menu" id="gallery-filter">
                            <li class="list-inline-item"><a class="active" data-filter="*">All</a></li>
                            <li class="list-inline-item"><a class="" data-filter=".ceremony">Ceremony</a></li>
                            <li class="list-inline-item"><a class="" data-filter=".party">Party</a></li>
                        </ul>
                    </div>
                </div>
                <div class="container">
                    <div class="row gallery-filter mt-3">
                        <div class="col-md-4 gallery-item ceremony">
                            <a href="images/gallery/1.jpg" class="img-zoom">
                                <div class="gallery-box">
                                    <div class="gallery-img"> <img src="images/gallery/1.jpg" class="img-fluid mx-auto d-block" alt=""> </div>
                                    <div class="gallery-detail">
                                        <h4 class="mb-0">Wedding Ceremony</h4>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 gallery-item party">
                            <a href="images/gallery/2.jpg" class="img-zoom">
                                <div class="gallery-box">
                                    <div class="gallery-img"> <img src="images/gallery/2.jpg" class="img-fluid mx-auto d-block" alt=""> </div>
                                    <div class="gallery-detail">
                                        <h4 class="mb-0">Wedding Party</h4>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 gallery-item ceremony">
                            <a href="images/gallery/3.jpg" class="img-zoom">
                                <div class="gallery-box">
                                    <div class="gallery-img"> <img src="images/gallery/3.jpg" class="img-fluid mx-auto d-block" alt=""> </div>
                                    <div class="gallery-detail">
                                        <h4 class="mb-0">Wedding Ceremony</h4>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 gallery-item party">
                            <a href="images/gallery/4.jpg" class="img-zoom">
                                <div class="gallery-box">
                                    <div class="gallery-img"> <img src="images/gallery/4.jpg" class="img-fluid mx-auto d-block" alt=""> </div>
                                    <div class="gallery-detail">
                                        <h4 class="mb-0">Wedding Party</h4>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 gallery-item ceremony">
                            <a href="images/gallery/5.jpg" class="img-zoom">
                                <div class="gallery-box">
                                    <div class="gallery-img"> <img src="images/gallery/5.jpg" class="img-fluid mx-auto d-block" alt=""> </div>
                                    <div class="gallery-detail">
                                        <h4 class="mb-0">Wedding Ceremony</h4>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 gallery-item party">
                            <a href="images/gallery/6.jpg" class="img-zoom">
                                <div class="gallery-box">
                                    <div class="gallery-img"> <img src="images/gallery/6.jpg" class="img-fluid mx-auto d-block" alt=""> </div>
                                    <div class="gallery-detail">
                                        <h4 class="mb-0">Wedding Party</h4>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- When & Where -->
            <div id="whenwhere" class="whenwhere section-padding bg-pink">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 mb-30"> <span class="oliven-title-meta">Detalles</span>
                            <h2 class="oliven-title">Cuándo y dónde</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="owl-carousel owl-theme">
                                <div class="item">
                                    <div class="whenwhere-img"> <img src="medios/web/ceremonia.jpg" alt="Ceremonia"></div>
                                    <div class="content">
                                        <h5>Ceremonia</h5>
                                        <p><i class="ti-location-pin"></i> Finca La Ruca, San Lucas Sacatepéquez</p>
                                        <p><i class="ti-time"></i> <span>3:00 PM</span></p>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="whenwhere-img"> <img src="medios/web/recepcion.jpg" alt="Recepción"></div>
                                    <div class="content">
                                        <h5>Recepción</h5>
                                        <p><i class="ti-location-pin"></i> Finca La Ruca, San Lucas Sacatepéquez</p>
                                        <p><i class="ti-time"></i> <span>5:00 PM</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Confirmation -->
            <div id="rsvp" class="section-padding bg-img bg-fixed" data-background="medios/web/ambiente.jpg">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6 offset-md-3 bg-white p-40"> <span class="oliven-title-meta text-center">¿Nos acompañas?</span>
                            <h2 class="oliven-title text-center">Confirma tu asistencia</h2>
                            <br>
                            <?php if ($form_message !== ''): ?>
                                <div class="alert alert-<?php echo h($form_status); ?>" role="alert">
                                    <?php echo h($form_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($invitado): ?>
                                <p class="text-center">
                                    Hola, <strong><?php echo h(first_display_name($invitado['nombre'])); ?></strong>.
                                    Esta invitación tiene <?php echo (int)$invitado['pases']; ?> pase<?php echo (int)$invitado['pases'] === 1 ? '' : 's'; ?> reservado<?php echo (int)$invitado['pases'] === 1 ? '' : 's'; ?> para ti.
                                </p>
                                <form class="rsvp__form" method="post" action="?invitado=<?php echo h($token_url); ?>#rsvp">
                                    <input type="hidden" name="action" value="confirm_rsvp">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <select name="asiste" class="form-control" required>
                                                    <option value="">Selecciona una opción...</option>
                                                    <option value="si" <?php echo isset($invitado['asiste']) && (int)$invitado['asiste'] === 1 ? 'selected' : ''; ?>>Sí, asistiré</option>
                                                    <option value="no" <?php echo isset($invitado['asiste']) && (int)$invitado['asiste'] === 0 ? 'selected' : ''; ?>>No podré asistir</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <select name="cantidad_asistentes" class="form-control" required>
                                                    <?php for ($i = 1; $i <= (int)$invitado['pases']; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo (int)($invitado['cantidad_asistentes'] ?? 1) === $i ? 'selected' : ''; ?>>
                                                            <?php echo $i; ?> persona<?php echo $i === 1 ? '' : 's'; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input name="telefono" type="text" class="form-control" placeholder="Teléfono / WhatsApp" value="<?php echo h($invitado['telefono'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input name="email" type="email" class="form-control" placeholder="Correo electrónico (opcional)" value="<?php echo h($invitado['email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea name="restricciones_alimenticias" cols="30" rows="3" class="form-control" placeholder="Restricciones alimenticias o alergias"><?php echo h($invitado['restricciones_alimenticias'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input name="cancion" type="text" class="form-control" placeholder="Canción que no puede faltar" value="<?php echo h($invitado['cancion'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea name="mensaje" cols="30" rows="5" class="form-control" placeholder="Mensaje para los novios"><?php echo h($invitado['mensaje'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input name="submit" type="submit" class="btn buttono" value="ENVIAR CONFIRMACIÓN">
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning" role="alert">
                                    Para confirmar, abre el enlace personalizado que recibiste por WhatsApp.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Regalos -->
            <div id="gift" class="gift-section gift">
                <div class="container">
                    <div class="row">
                        <div class="col-md-4 mb-30">
                         <!--   <br> <span class="oliven-title-meta">Con cariño</span>-->
                            <h2 class="oliven-title">Regalos</h2>
                        </div>
                        <div class="col-md-8">
                            <p>Su presencia será nuestro mejor regalo.</p>
                            <p>Sin embargo, si desean bendecirnos de una manera adicional, tendremos la opción de transferencia bancaria o sobres el día del evento.</p>
                            <div class="bank-transfer-card">
                                <div class="bank-transfer-copy">Transferencia bancaria</div>
                                <div class="bank-transfer-title">Cuenta de ahorro BI-1534460</div>
                                <div class="bank-transfer-name">Lemus Chinchilla Estteban Jose O/ Rosito</div>
                                <button type="button" class="bank-copy-button" onclick="copyBankAccount('BI-1534460')">
                                    <i class="ti-files"></i>
                                    Copiar cuenta
                                </button>
                            </div>
                            <p>Esperamos compartir con ustedes una tarde llena de amor, alegría, buena comida y muchos recuerdos inolvidables.</p>
                            <p><strong>Con cariño,<br>Estteban & María</strong></p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="footer2">
                <div class="oliven-narrow-content">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <h2>
                                <a href="#home"><img src="images/logo.png" alt=""><span>Estteban <small>&</small> María</span></a>
                            </h2>
                            <p class="copyright">22 de noviembre de 2026 - Finca La Ruca, San Lucas Sacatepéquez</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- jQuery -->
        <script src="js/jquery.min.js"></script>
        <script src="js/modernizr-2.6.2.min.js"></script>
        <script src="js/jquery.easing.1.3.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/jquery.waypoints.min.js"></script>
        <script src="js/sticky-kit.min.js"></script>
        <script src="js/isotope.js"></script>
        <script src="js/jquery.magnific-popup.min.js"></script>
        <script src="js/owl.carousel.min.js"></script>
        <script src="js/main.js"></script>
        <script>
            function copyBankAccount(account) {
                var successMessage = 'Cuenta copiada: ' + account;

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(account).then(function () {
                        alert(successMessage);
                    });
                    return;
                }

                var input = document.createElement('input');
                input.value = account;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                alert(successMessage);
            }
        </script>
    </div>
</body>
</html>
