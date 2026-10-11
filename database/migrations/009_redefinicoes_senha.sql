-- Links de "esqueci minha senha": guarda só o hash do token, com validade curta e uso único.
CREATE TABLE IF NOT EXISTS redefinicoes_senha (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  BIGINT UNSIGNED NOT NULL,
    token_hash  CHAR(64)        NOT NULL,
    expira_em   DATETIME        NOT NULL,
    usado_em    DATETIME        NULL,
    criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_redefinicoes_token (token_hash),
    KEY ix_redefinicoes_usuario (usuario_id, criado_em),
    CONSTRAINT fk_redefinicoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
