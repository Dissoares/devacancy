-- Limite de tentativas por IP e por e-mail (login, cadastro, recuperação de senha), valendo mesmo sem cookie de sessão.
CREATE TABLE IF NOT EXISTS limites_tentativas (
    chave      VARCHAR(150) NOT NULL PRIMARY KEY,
    tentativas INT UNSIGNED NOT NULL DEFAULT 0,
    expira_em  DATETIME     NOT NULL,
    KEY ix_limites_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
