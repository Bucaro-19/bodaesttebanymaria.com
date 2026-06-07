<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function normalize_slug(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'invitado';
}

function generate_token(mysqli $conn, string $name): string
{
    $base = normalize_slug($name);

    do {
        $token = $base . '-' . random_int(1000, 9999);
        $stmt = $conn->prepare('SELECT id FROM invitados WHERE token = ? LIMIT 1');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    } while ($exists);

    return $token;
}

function invitation_url(string $token): string
{
    return rtrim(SITE_URL, '/') . '/?invitado=' . rawurlencode($token);
}

function first_display_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'invitado especial';
    }

    $parts = preg_split('/\s+/', $name) ?: [$name];
    return $parts[0];
}

