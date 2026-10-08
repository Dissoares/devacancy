-- Vagas cadastradas pela equipe (curadoria interna). Base do match com os perfis.
CREATE TABLE IF NOT EXISTS vagas (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo           VARCHAR(150) NOT NULL,
    empresa          VARCHAR(150) NOT NULL,
    area             VARCHAR(20)  NOT NULL,
    senioridade      VARCHAR(20)  NOT NULL,
    stack            JSON         NOT NULL,
    modelo_trabalho  VARCHAR(20)  NOT NULL,
    cidade           VARCHAR(120) NULL,
    salario_min      INT UNSIGNED NULL,
    salario_max      INT UNSIGNED NULL,
    link             VARCHAR(500) NULL,
    descricao        TEXT         NULL,
    ativa            TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_vagas_match (ativa, area, senioridade),
    KEY ix_vagas_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uma linha por profissional: a assinatura vale até expira_em e cada pagamento estende o prazo.
CREATE TABLE IF NOT EXISTS assinaturas (
    usuario_id     BIGINT UNSIGNED PRIMARY KEY,
    plano          VARCHAR(30) NOT NULL,
    expira_em      DATETIME    NOT NULL,
    criado_em      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assinaturas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cobranças geradas no gateway (ExPay).
CREATE TABLE IF NOT EXISTS pagamentos (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    plano           VARCHAR(30)   NOT NULL,
    valor           DECIMAL(10,2) NOT NULL,
    metodo          VARCHAR(20)   NOT NULL DEFAULT 'pix',
    status          VARCHAR(20)   NOT NULL DEFAULT 'pendente',
    gateway_id      VARCHAR(64)   NULL,
    pix_copia_cola  TEXT          NULL,
    pix_qr_base64   MEDIUMTEXT    NULL,
    expira_em       DATETIME      NULL,
    pago_em         DATETIME      NULL,
    criado_em       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pagamentos_gateway (gateway_id),
    KEY ix_pagamentos_usuario (usuario_id, criado_em),
    CONSTRAINT fk_pagamentos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
