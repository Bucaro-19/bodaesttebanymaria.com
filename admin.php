<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

$login_error = '';
$notice = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (hash_equals(ADMIN_PASSWORD, (string)($_POST['password'] ?? ''))) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    }

    $login_error = 'Contrasena incorrecta.';
}

if (!($_SESSION['admin_logged_in'] ?? false)) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin | Boda</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-stone-100 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-8">
            <h1 class="text-2xl font-bold text-stone-900 text-center mb-6">Panel de invitados</h1>
            <?php if ($login_error !== ''): ?>
                <div class="bg-red-50 text-red-700 border border-red-200 rounded-md p-3 text-sm mb-4"><?php echo h($login_error); ?></div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <input type="password" name="password" placeholder="Contrasena" required class="w-full border border-stone-300 rounded-md px-4 py-3 focus:outline-none focus:ring-2 focus:ring-stone-700">
                <button class="w-full bg-stone-900 text-white rounded-md px-4 py-3 font-semibold hover:bg-stone-800">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!DB_AVAILABLE) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin | Boda</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-stone-100 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-xl w-full p-8">
            <p class="text-sm uppercase tracking-widest text-stone-500 mb-2">Panel de invitados</p>
            <h1 class="text-2xl font-bold text-stone-900 mb-4">Sin conexion a la base de datos</h1>
            <p class="text-stone-700 mb-4">La invitacion puede verse localmente, pero el panel necesita MySQL para crear invitados y guardar respuestas.</p>
            <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-md p-4 text-sm">
                <strong>Detalle tecnico:</strong> <?php echo h($db_error ?? 'No disponible'); ?>
            </div>
            <a href="?logout=1" class="inline-flex mt-6 bg-stone-900 hover:bg-stone-800 text-white rounded-md px-5 py-2 font-semibold">Cerrar sesion</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_guest') {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $pases = max(1, (int)($_POST['pases'] ?? 1));
        $telefono = trim((string)($_POST['telefono'] ?? ''));

        if ($nombre !== '') {
            $token = generate_token($conn, $nombre);
            $stmt = $conn->prepare('INSERT INTO invitados (nombre, token, pases, telefono) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssis', $nombre, $token, $pases, $telefono);
            $stmt->execute();
            $stmt->close();
        }

        header('Location: admin.php');
        exit;
    }

    if ($action === 'edit_guest') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $token = normalize_slug(trim((string)($_POST['token'] ?? '')));
        $pases = max(1, (int)($_POST['pases'] ?? 1));
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $estado = (string)($_POST['asiste'] ?? 'null');
        $cantidad = max(0, (int)($_POST['cantidad_asistentes'] ?? 0));

        if ($id > 0 && $nombre !== '' && $token !== '') {
            $asiste = null;
            if ($estado === '1') {
                $asiste = 1;
                $cantidad = max(1, min($pases, $cantidad === 0 ? 1 : $cantidad));
            } elseif ($estado === '0') {
                $asiste = 0;
                $cantidad = 0;
            } else {
                $cantidad = 0;
            }

            $stmt = $conn->prepare(
                'UPDATE invitados
                 SET nombre = ?, token = ?, pases = ?, telefono = ?, email = ?, asiste = ?, cantidad_asistentes = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('ssissiii', $nombre, $token, $pases, $telefono, $email, $asiste, $cantidad, $id);
            $stmt->execute();
            $stmt->close();
        }

        header('Location: admin.php');
        exit;
    }

    if ($action === 'delete_guest') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM invitados WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }

        header('Location: admin.php');
        exit;
    }
}

$stats_result = $conn->query(
    'SELECT
        COUNT(*) AS total_invitaciones,
        COALESCE(SUM(pases), 0) AS total_pases,
        COALESCE(SUM(CASE WHEN asiste = 1 THEN 1 ELSE 0 END), 0) AS confirmados,
        COALESCE(SUM(CASE WHEN asiste = 1 THEN cantidad_asistentes ELSE 0 END), 0) AS asistentes,
        COALESCE(SUM(CASE WHEN asiste = 0 THEN 1 ELSE 0 END), 0) AS declinados,
        COALESCE(SUM(CASE WHEN asiste IS NULL THEN 1 ELSE 0 END), 0) AS pendientes
     FROM invitados'
);
$stats = $stats_result->fetch_assoc();

$guest_result = $conn->query('SELECT * FROM invitados ORDER BY asiste IS NULL DESC, asiste DESC, nombre ASC');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Boda Estteban y Maria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function copyInvitation(nombre, token) {
            const url = <?php echo json_encode(rtrim(SITE_URL, '/') . '/?invitado='); ?> + encodeURIComponent(token);
            const firstName = nombre.trim().split(/\s+/)[0] || 'invitado';
            const message = `Hola ${firstName}, queremos compartirte nuestra invitacion de boda. Por favor confirma tu asistencia en este enlace personalizado: ${url}`;

            navigator.clipboard.writeText(message).then(() => {
                alert('Mensaje copiado al portapapeles.');
            });
        }

        function openEditModal(guest) {
            document.getElementById('edit-id').value = guest.id;
            document.getElementById('edit-nombre').value = guest.nombre;
            document.getElementById('edit-token').value = guest.token;
            document.getElementById('edit-pases').value = guest.pases;
            document.getElementById('edit-telefono').value = guest.telefono || '';
            document.getElementById('edit-email').value = guest.email || '';
            document.getElementById('edit-asiste').value = guest.asiste === null ? 'null' : String(guest.asiste);
            document.getElementById('edit-cantidad').value = guest.cantidad_asistentes || 0;
            document.getElementById('edit-url').textContent = <?php echo json_encode(rtrim(SITE_URL, '/') . '/?invitado='); ?> + encodeURIComponent(guest.token);
            document.getElementById('edit-modal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('edit-modal').classList.add('hidden');
        }
    </script>
</head>
<body class="bg-stone-100 min-h-screen text-stone-900">
    <main class="max-w-7xl mx-auto p-4 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <p class="text-sm uppercase tracking-widest text-stone-500">Boda Estteban y Maria</p>
                <h1 class="text-3xl font-bold">Dashboard de invitados</h1>
            </div>
            <a href="?logout=1" class="inline-flex justify-center bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-semibold">Cerrar sesion</a>
        </div>

        <section class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg border border-stone-200 p-5">
                <p class="text-sm text-stone-500">Invitaciones</p>
                <p class="text-3xl font-bold"><?php echo (int)$stats['total_invitaciones']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-stone-200 p-5">
                <p class="text-sm text-stone-500">Pases</p>
                <p class="text-3xl font-bold"><?php echo (int)$stats['total_pases']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-stone-200 p-5">
                <p class="text-sm text-stone-500">Confirmados</p>
                <p class="text-3xl font-bold text-emerald-700"><?php echo (int)$stats['confirmados']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-stone-200 p-5">
                <p class="text-sm text-stone-500">Asistentes</p>
                <p class="text-3xl font-bold text-emerald-700"><?php echo (int)$stats['asistentes']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-stone-200 p-5">
                <p class="text-sm text-stone-500">Pendientes</p>
                <p class="text-3xl font-bold text-amber-700"><?php echo (int)$stats['pendientes']; ?></p>
            </div>
        </section>

        <section class="bg-white rounded-lg border border-stone-200 p-5 mb-8">
            <form method="post" class="grid grid-cols-1 md:grid-cols-[1fr_120px_180px_auto] gap-4 items-end">
                <input type="hidden" name="action" value="add_guest">
                <label class="block">
                    <span class="block text-sm font-semibold mb-1">Nombre del invitado o familia</span>
                    <input name="nombre" required class="w-full border border-stone-300 rounded-md px-3 py-2" placeholder="Ej. Familia Perez Lopez">
                </label>
                <label class="block">
                    <span class="block text-sm font-semibold mb-1">Pases</span>
                    <input name="pases" type="number" min="1" value="1" required class="w-full border border-stone-300 rounded-md px-3 py-2">
                </label>
                <label class="block">
                    <span class="block text-sm font-semibold mb-1">Telefono</span>
                    <input name="telefono" class="w-full border border-stone-300 rounded-md px-3 py-2" placeholder="Opcional">
                </label>
                <button class="bg-stone-900 hover:bg-stone-800 text-white rounded-md px-5 py-2 font-semibold">Agregar</button>
            </form>
        </section>

        <section class="bg-white rounded-lg border border-stone-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-stone-50 text-stone-600 uppercase tracking-wide">
                        <tr>
                            <th class="p-4">Invitado</th>
                            <th class="p-4">Estado</th>
                            <th class="p-4">Pases</th>
                            <th class="p-4">Contacto</th>
                            <th class="p-4">URL</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php while ($guest = $guest_result->fetch_assoc()): ?>
                            <?php
                            $status = 'Pendiente';
                            $status_class = 'bg-amber-100 text-amber-800';
                            if ($guest['asiste'] === '1') {
                                $status = 'Confirma';
                                $status_class = 'bg-emerald-100 text-emerald-800';
                            } elseif ($guest['asiste'] === '0') {
                                $status = 'No asiste';
                                $status_class = 'bg-red-100 text-red-800';
                            }
                            ?>
                            <tr class="hover:bg-stone-50">
                                <td class="p-4">
                                    <div class="font-semibold"><?php echo h($guest['nombre']); ?></div>
                                    <div class="text-stone-500"><?php echo h($guest['token']); ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo $status_class; ?>"><?php echo $status; ?></span>
                                </td>
                                <td class="p-4">
                                    <?php echo (int)($guest['cantidad_asistentes'] ?? 0); ?> / <?php echo (int)$guest['pases']; ?>
                                </td>
                                <td class="p-4">
                                    <div><?php echo h($guest['telefono']); ?></div>
                                    <div class="text-stone-500"><?php echo h($guest['email']); ?></div>
                                </td>
                                <td class="p-4">
                                    <a class="text-stone-700 underline" target="_blank" href="<?php echo h(invitation_url($guest['token'])); ?>">Abrir</a>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick='copyInvitation(<?php echo json_encode($guest['nombre']); ?>, <?php echo json_encode($guest['token']); ?>)' class="bg-indigo-50 text-indigo-700 border border-indigo-200 rounded px-3 py-1.5 font-semibold">Copiar</button>
                                        <button type="button" onclick='openEditModal(<?php echo json_encode($guest, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="bg-amber-50 text-amber-700 border border-amber-200 rounded px-3 py-1.5 font-semibold">Editar</button>
                                        <form method="post" onsubmit="return confirm('Seguro que deseas borrar este invitado?');">
                                            <input type="hidden" name="action" value="delete_guest">
                                            <input type="hidden" name="id" value="<?php echo (int)$guest['id']; ?>">
                                            <button class="bg-red-50 text-red-700 border border-red-200 rounded px-3 py-1.5 font-semibold">Borrar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div id="edit-modal" class="hidden fixed inset-0 bg-black/60 p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-xl mx-auto mt-10 p-6">
            <div class="flex justify-between gap-4 items-start mb-5">
                <div>
                    <h2 class="text-xl font-bold">Editar invitado</h2>
                    <p id="edit-url" class="text-xs text-stone-500 break-all mt-1"></p>
                </div>
                <button type="button" onclick="closeEditModal()" class="text-stone-500 hover:text-stone-900 text-2xl leading-none">&times;</button>
            </div>
            <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="edit_guest">
                <input type="hidden" name="id" id="edit-id">
                <label class="block md:col-span-2">
                    <span class="block text-sm font-semibold mb-1">Nombre</span>
                    <input name="nombre" id="edit-nombre" required class="w-full border border-stone-300 rounded-md px-3 py-2">
                </label>
                <label class="block md:col-span-2">
                    <span class="block text-sm font-semibold mb-1">Token URL</span>
                    <input name="token" id="edit-token" required class="w-full border border-stone-300 rounded-md px-3 py-2">
                </label>
                <label class="block">
                    <span class="block text-sm font-semibold mb-1">Pases</span>
                    <input name="pases" id="edit-pases" type="number" min="1" required class="w-full border border-stone-300 rounded-md px-3 py-2">
                </label>
                <label class="block">
                    <span class="block text-sm font-semibold mb-1">Asistentes</span>
                    <input name="cantidad_asistentes" id="edit-cantidad" type="number" min="0" class="w-full border border-stone-300 rounded-md px-3 py-2">
                </label>
                <label class="block">
                    <span class="block text-sm font-semibold mb-1">Telefono</span>
                    <input name="telefono" id="edit-telefono" class="w-full border border-stone-300 rounded-md px-3 py-2">
                </label>
                <label class="block">
                    <span class="block text-sm font-semibold mb-1">Email</span>
                    <input name="email" id="edit-email" type="email" class="w-full border border-stone-300 rounded-md px-3 py-2">
                </label>
                <label class="block md:col-span-2">
                    <span class="block text-sm font-semibold mb-1">Estado</span>
                    <select name="asiste" id="edit-asiste" class="w-full border border-stone-300 rounded-md px-3 py-2">
                        <option value="null">Pendiente</option>
                        <option value="1">Confirma asistencia</option>
                        <option value="0">No asistira</option>
                    </select>
                </label>
                <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()" class="bg-stone-100 hover:bg-stone-200 text-stone-800 rounded-md px-5 py-2 font-semibold">Cancelar</button>
                    <button class="bg-stone-900 hover:bg-stone-800 text-white rounded-md px-5 py-2 font-semibold">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
