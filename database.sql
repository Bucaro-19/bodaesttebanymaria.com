CREATE TABLE invitados (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(160) NOT NULL,
    token VARCHAR(190) NOT NULL,
    pases INT UNSIGNED NOT NULL DEFAULT 1,
    telefono VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    asiste TINYINT(1) NULL DEFAULT NULL,
    cantidad_asistentes INT UNSIGNED NULL DEFAULT NULL,
    mensaje TEXT NULL,
    restricciones_alimenticias TEXT NULL,
    cancion VARCHAR(190) NULL,
    fecha_respuesta DATETIME NULL DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invitados_token (token),
    KEY idx_invitados_asiste (asiste),
    KEY idx_invitados_fecha_respuesta (fecha_respuesta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rsvp_historial (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invitado_id INT UNSIGNED NOT NULL,
    asiste TINYINT(1) NOT NULL,
    cantidad_asistentes INT UNSIGNED NOT NULL DEFAULT 0,
    telefono VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    mensaje TEXT NULL,
    restricciones_alimenticias TEXT NULL,
    cancion VARCHAR(190) NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_historial_invitado (invitado_id),
    CONSTRAINT fk_historial_invitado
        FOREIGN KEY (invitado_id) REFERENCES invitados(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

