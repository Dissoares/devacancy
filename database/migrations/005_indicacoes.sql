-- Cada usuário tem um código único pra indicar amigos; indicado_por grava quem o trouxe (uma vez, nunca muda).
ALTER TABLE usuarios
    ADD COLUMN codigo_indicacao  VARCHAR(10)     NULL AFTER avatar_url,
    ADD COLUMN indicado_por      BIGINT UNSIGNED NULL AFTER codigo_indicacao,
    ADD UNIQUE KEY uk_usuarios_codigo_indicacao (codigo_indicacao),
    ADD KEY ix_usuarios_indicado_por (indicado_por),
    ADD CONSTRAINT fk_usuarios_indicado_por FOREIGN KEY (indicado_por) REFERENCES usuarios (id) ON DELETE SET NULL;

-- Comissão de 40% paga uma única vez, gerada no primeiro pagamento do indicado (uk garante que não se repete).
CREATE TABLE IF NOT EXISTS comissoes (
    id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_indicador_id   BIGINT UNSIGNED NOT NULL,
    usuario_indicado_id    BIGINT UNSIGNED NOT NULL,
    pagamento_id           BIGINT UNSIGNED NOT NULL,
    valor                  DECIMAL(10,2)   NOT NULL,
    moeda                  CHAR(3)         NOT NULL DEFAULT 'BRL',
    status                 VARCHAR(20)     NOT NULL DEFAULT 'pendente',
    criado_em              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pago_em                DATETIME        NULL,
    UNIQUE KEY uk_comissoes_indicado (usuario_indicado_id),
    KEY ix_comissoes_indicador (usuario_indicador_id, status),
    CONSTRAINT fk_comissoes_indicador FOREIGN KEY (usuario_indicador_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_comissoes_indicado  FOREIGN KEY (usuario_indicado_id)  REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_comissoes_pagamento FOREIGN KEY (pagamento_id) REFERENCES pagamentos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
