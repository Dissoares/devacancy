-- Conta de acesso: comum a profissionais, empresas e administradores.
CREATE TABLE IF NOT EXISTS usuarios (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo               VARCHAR(20)  NOT NULL,
    nome               VARCHAR(120) NOT NULL,
    email              VARCHAR(190) NOT NULL,
    senha_hash         VARCHAR(255) NULL,
    avatar_url         VARCHAR(500) NULL,
    telefone           VARCHAR(30)  NULL,
    cadastro_completo  TINYINT(1)   NOT NULL DEFAULT 0,
    ultimo_acesso_em   DATETIME     NULL,
    criado_em          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuarios_email (email),
    KEY ix_usuarios_tipo_criado (tipo, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logins sociais vinculados à conta (uma conta pode ter Google, GitHub e LinkedIn).
CREATE TABLE IF NOT EXISTS contas_sociais (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id   BIGINT UNSIGNED NOT NULL,
    provedor     VARCHAR(20)  NOT NULL,
    provedor_id  VARCHAR(191) NOT NULL,
    criado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_contas_sociais_provedor (provedor, provedor_id),
    KEY ix_contas_sociais_usuario (usuario_id),
    CONSTRAINT fk_contas_sociais_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfis_profissionais (
    usuario_id               BIGINT UNSIGNED PRIMARY KEY,
    area                     VARCHAR(20)  NOT NULL,
    senioridade              VARCHAR(20)  NOT NULL,
    cargo_desejado           VARCHAR(120) NOT NULL,
    anos_experiencia         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    stack                    JSON         NOT NULL,
    modelo_trabalho          VARCHAR(20)  NOT NULL,
    cidade                   VARCHAR(120) NULL,
    pretensao_salarial       INT UNSIGNED NULL,
    linkedin_url             VARCHAR(255) NULL,
    github_url               VARCHAR(255) NULL,
    portfolio_url            VARCHAR(255) NULL,
    curriculo_arquivo        VARCHAR(120) NULL,
    curriculo_nome_original  VARCHAR(255) NULL,
    resumo                   TEXT         NULL,
    atualizado_em            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_perfis_profissionais_filtro (area, senioridade),
    CONSTRAINT fk_perfis_profissionais_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfis_empresas (
    usuario_id          BIGINT UNSIGNED PRIMARY KEY,
    nome_empresa        VARCHAR(150) NOT NULL,
    site                VARCHAR(255) NULL,
    tamanho             VARCHAR(20)  NOT NULL,
    cargo_contato       VARCHAR(120) NOT NULL,
    stacks_contratacao  JSON         NOT NULL,
    vagas_abertas       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    mensagem            TEXT         NULL,
    atualizado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_perfis_empresas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
