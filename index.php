<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

$token_url = trim((string)($_GET['invitado'] ?? ''));
$invitado = null;
$form_message = '';
$form_status = '';

if ($token_url !== '') {
    $stmt = $conn->prepare('SELECT id, nombre, token, pases, telefono, email, asiste, cantidad_asistentes, mensaje, restricciones_alimenticias, cancion FROM invitados WHERE token = ? LIMIT 1');
    $stmt->bind_param('s', $token_url);
    $stmt->execute();
    $invitado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_rsvp') {
    if (!$invitado) {
        $form_status = 'danger';
        $form_message = 'Este enlace no es valido. Por favor confirma desde tu invitacion personalizada.';
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
            $form_message = 'Revisa el correo, parece que no tiene un formato valido.';
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
                $form_message = 'Gracias, tu respuesta quedo guardada correctamente.';

                if (defined('ADMIN_EMAIL') && ADMIN_EMAIL !== '' && strpos(ADMIN_EMAIL, 'example.com') === false) {
                    $estado = $asiste === 1 ? 'Si asistira' : 'No asistira';
                    $subject = 'RSVP boda: ' . $invitado['nombre'];
                    $body = "Nueva respuesta de boda\n\n";
                    $body .= "Invitado: {$invitado['nombre']}\n";
                    $body .= "Estado: {$estado}\n";
                    $body .= "Cantidad: {$cantidad}\n";
                    $body .= "Telefono: {$telefono}\n";
                    $body .= "Email: {$email}\n";
                    $body .= "Restricciones: {$restricciones}\n";
                    $body .= "Cancion: {$cancion}\n";
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
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Estteban & Maria | Invitacion de boda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Estteban & Maria – Wedding Template is perfect for wedding planners, coordinators, photographers, and event organizers. Minimal, clean, responsive and SEO friendly design with Bootstrap 5+, video background, working contact form, and retina-ready layout.">
    <meta name="keywords" content="wedding template, wedding planner, wedding website, event organizer, wedding coordinator, wedding photographer, modern wedding template, bootstrap wedding theme, responsive wedding template, minimal wedding design">
    <meta name="author" content="DuruThemes">
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
                <a href="index.html"> <img src="images/logo.png" alt=""> <span>Estteban <small>&</small> Maria</span>
                    <h6>15.11.2026</h6>
                </a>
            </div>
            <!-- Menu -->
            <nav class="oliven-main-menu">
                <ul>
                    <li><a href="index.html#home">Home</a></li>
                    <li><a href="index.html#couple">Couple</a></li>
                    <li><a href="index.html#story">Nuestra historia</a></li>
                    <li><a href="index.html#friends">Friends</a></li>
                    <li><a href="index.html#organization">Organization</a></li>
                    <li><a href="index.html#gallery">Gallery</a></li>
                    <li><a href="index.html#whenwhere">When & Where</a></li>
                    <li><a href="index.html#rsvp">R.S.V.P</a></li>
                    <li><a href="index.html#gift">Regalos</a></li>
                    <li><a href="blog.html">Blog</a></li>
                </ul>
            </nav>
            <!-- Sidebar Footer -->
            <div class="footer1"> <span class="separator"></span>
                <p>Boda de Estteban y Maria
                    <br />Fecha por confirmar
                </p>
            </div>
        </aside>
        <!-- Content Section -->
        <div id="oliven-main">
            <!-- Header Video -->
            <header id="home" class="video-fullscreen-wrap position-relative">
                <!-- The opacity on the image is made with "data-overlay-dark="number". You can change it using the numbers 0-9. -->
                <div class="video-fullscreen-video" data-overlay-dark="5">
                    <video playsinline="" autoplay="" loop="" muted="">
                        <source src="https://duruthemes.com/demo/html/olivia-enrico/video.mp4" type="video/mp4" autoplay="" loop="" muted="">
                        <source src="https://duruthemes.com/demo/html/olivia-enrico/video.webm" type="video/webm" autoplay="" loop="" muted="">
                    </video>
                </div>
                <div class="v-middle caption overlay">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-12">
                                <h1>Estteban & Maria</h1>
                                <h5>Fecha por confirmar</h5>
                            </div>
                        </div>
                        <!-- scroll down -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="arrow bounce text-center">
                                    <a href="index.html#couple"> <i class="ti-heart"></i> </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- Couple -->
            <div id="couple" class="bridegroom clear section-padding bg-pink">
                <div class="container">
                    <div class="row mb-60">
                        <div class="col-md-6">
                            <div class="item toright mb-30 animate-box" data-animate-effect="fadeInLeft">
                                <div class="img"> <img src="images/bride.jpg" alt=""> </div>
                                <div class="info valign">
                                    <div class="full-width">
                                        <h6>Maria <i class="ti-heart"></i></h6> <span>La novia</span>
                                        <p>Olivia fringilla dui at elit finibus viverra thenec a lacus seda themo the miss druane semper non the fermen.</p>
                                        <div class="social">
                                            <div class="full-width">
                                                <a href="#0" class="icon"> <i class="ti-facebook"></i> </a>
                                                <a href="#0" class="icon"> <i class="ti-twitter"></i> </a>
                                                <a href="#0" class="icon"> <i class="ti-instagram"></i> </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="item mb-30 animate-box" data-animate-effect="fadeInRight">
                                <div class="img"> <img src="images/groom.jpg" alt=""> </div>
                                <div class="info valign">
                                    <div class="full-width">
                                        <h6>Estteban <i class="ti-heart"></i></h6> <span>El novio</span>
                                        <p>Enrico fringilla dui at elit finibus viverra thenec a lacus seda themo the miss druane semper non the fermen.</p>
                                        <div class="social">
                                            <div class="full-width">
                                                <a href="#0" class="icon"> <i class="ti-facebook"></i> </a>
                                                <a href="#0" class="icon"> <i class="ti-twitter"></i> </a>
                                                <a href="#0" class="icon"> <i class="ti-instagram"></i> </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center animate-box" data-animate-effect="fadeInUp">
                            <h3 class="oliven-couple-title">Nos casamos!</h3>
                            <h4 class="oliven-couple-subtitle">Fecha y lugar por confirmar</h4>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Countdown -->
            <div id="countdown" class="section-padding bg-img bg-fixed" data-background="images/banner-1.jpg">
                <div class="container">
                    <div class="row">
                        <div class="section-head col-md-12">
                            <h4>We will become a family in</h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <ul>
                                <li><span id="days"></span>Days</li>
                                <li><span id="hours"></span>Hours</li>
                                <li><span id="minutes"></span>Minutes</li>
                                <li><span id="seconds"></span>Seconds</li>
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
                                <div class="img"> <img src="images/story.jpg" class="img-fluid" alt=""> </div>
                                <div class="story-img-2 story-wedding" style="background-image: url(images/wedding-logo.png);"></div>
                            </div>
                        </div>
                        <div class="col-md-7 animate-box" data-animate-effect="fadeInRight">
                            <h4 class="oliven-story-subtitle">Nuestro amor.</h4>
                            <h3 class="oliven-story-title">Nuestra historia</h3>
                            <p>Curabit aliquet orci elit genes tristique lorem commodo vitae. Tuliaum tincidunt nete sede gravida aliquam, neque libero hendrerit magna, sit amet mollis lacus ithe maurise. Dunya erat volutpat edat themo the druanye semper.</p>
                            <p>Luality fringilla duiman at elit vinibus viverra nec a lacus themo the druanye sene sollicitudin mi suscipit non sagie the fermen.</p>
                            <p>Phasellus viverra tristique justo duis vitae diam neque nivamus ac est augue artine aringilla dui at elit finibus viverra nec a lacus. Nedana themo eros odio semper soe suscipit non. Curabit aliquet orci elit genes tristique.</p>
                            <h4>Dec 5th, 2026, We Said Yes!</h4>
                            <p>Luality fringilla duiman at elit finibus viverra nec a lacus themo the druanye sene sollicitudin mi suscipit non sagie the fermen.</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Friends -->
            <div id="friends" class="friends section-padding bg-pink">
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
                </div>
            </div>
            <!-- See you -->
            <div id="seeyou" class="seeyou section-padding bg-img bg-fixed" data-background="images/banner-3.jpg">
                <div class="container">
                    <div class="row">
                        <div class="section-head col-md-12 text-center"> <span><i class="ti-heart"></i></span>
                            <h4>Looking forward to see you!</h4>
                            <h3>15.11.2026</h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Organization -->
            <div id="organization" class="organization section-padding bg-pink">
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
            <div id="gallery" class="section-padding">
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
                        <div class="col-md-12 mb-30"> <span class="oliven-title-meta">Questions</span>
                            <h2 class="oliven-title">When & Where</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="owl-carousel owl-theme">
                                <div class="item">
                                    <div class="whenwhere-img"> <img src="images/whenwhere/3.jpg" alt=""></div>
                                    <div class="content">
                                        <h5>Wedding Ceremony</h5>
                                        <p><i class="ti-location-pin"></i> 175 Broadway, Brooklyn, New York 11244, USA</p>
                                        <p><i class="ti-time"></i> <span>12:00am – 13:00pm</span></p>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="whenwhere-img"> <img src="images/whenwhere/1.jpg" alt=""></div>
                                    <div class="content">
                                        <h5>Weddding Party</h5>
                                        <p><i class="ti-location-pin"></i> Fortune Brooklyn restaurant, 149 Broadway, Brooklyn, NY, USA</p>
                                        <p><i class="ti-time"></i> <span>14:00pm</span></p>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="whenwhere-img"> <img src="images/whenwhere/2.jpg" alt=""></div>
                                    <div class="content">
                                        <h5>Accomodations</h5>
                                        <p><i class="ti-direction-alt"></i> Hotel and distance from wedding party restaurant:</p>
                                        <p><i class="ti-direction"></i> The William Vale (7 min)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Confirmation -->
            <div id="rsvp" class="section-padding bg-img bg-fixed" data-background="images/banner-2.jpg">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6 offset-md-3 bg-white p-40"> <span class="oliven-title-meta text-center">Nos acompañas?</span>
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
                                    Esta invitacion tiene <?php echo (int)$invitado['pases']; ?> pase<?php echo (int)$invitado['pases'] === 1 ? '' : 's'; ?> reservado<?php echo (int)$invitado['pases'] === 1 ? '' : 's'; ?> para ti.
                                </p>
                                <form class="rsvp__form" method="post" action="?invitado=<?php echo h($token_url); ?>#rsvp">
                                    <input type="hidden" name="action" value="confirm_rsvp">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <select name="asiste" class="form-control" required>
                                                    <option value="">Selecciona una opcion...</option>
                                                    <option value="si" <?php echo isset($invitado['asiste']) && (int)$invitado['asiste'] === 1 ? 'selected' : ''; ?>>Si, asistire</option>
                                                    <option value="no" <?php echo isset($invitado['asiste']) && (int)$invitado['asiste'] === 0 ? 'selected' : ''; ?>>No podre asistir</option>
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
                                                <input name="telefono" type="text" class="form-control" placeholder="Telefono / WhatsApp" value="<?php echo h($invitado['telefono'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input name="email" type="email" class="form-control" placeholder="Correo electronico (opcional)" value="<?php echo h($invitado['email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea name="restricciones_alimenticias" cols="30" rows="3" class="form-control" placeholder="Restricciones alimenticias o alergias"><?php echo h($invitado['restricciones_alimenticias'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input name="cancion" type="text" class="form-control" placeholder="Cancion que no puede faltar" value="<?php echo h($invitado['cancion'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea name="mensaje" cols="30" rows="5" class="form-control" placeholder="Mensaje para los novios"><?php echo h($invitado['mensaje'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input name="submit" type="submit" class="btn buttono" value="ENVIAR CONFIRMACION">
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
                        <div class="col-md-3 mb-30">
                            <br> <span class="oliven-title-meta">Gift</span>
                            <h2 class="oliven-title">Regalos</h2>
                        </div>
                        <div class="col-md-9">
                            <div class="owl-carousel owl-theme">
                                <div class="client-logo">
                                    <a href="#"><img src="images/gift/1.jpg" alt=""></a>
                                </div>
                                <div class="client-logo">
                                    <a href="#"><img src="images/gift/2.jpg" alt=""></a>
                                </div>
                                <div class="client-logo">
                                    <a href="#"><img src="images/gift/3.jpg" alt=""></a>
                                </div>
                                <div class="client-logo">
                                    <a href="#"><img src="images/gift/4.jpg" alt=""></a>
                                </div>
                                <div class="client-logo">
                                    <a href="#"><img src="images/gift/5.jpg" alt=""></a>
                                </div>
                                <div class="client-logo">
                                    <a href="#"><img src="images/gift/6.jpg" alt=""></a>
                                </div>
                            </div>
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
                                <a href="index.html"><img src="images/logo.png" alt=""><span>Estteban <small>&</small> Maria</span></a>
                            </h2>
                            <p class="copyright">Fecha y lugar por confirmar</p>
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
    </div>
</body>
</html>
