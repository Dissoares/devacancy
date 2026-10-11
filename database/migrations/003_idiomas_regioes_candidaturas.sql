-- Vagas no Brasil ou internacionais (remotas no exterior), com a moeda do salário.
ALTER TABLE vagas
    ADD COLUMN regiao VARCHAR(20) NOT NULL DEFAULT 'brasil' AFTER modelo_trabalho,
    ADD COLUMN moeda  CHAR(3)     NOT NULL DEFAULT 'BRL' AFTER salario_max,
    ADD KEY ix_vagas_regiao (ativa, regiao);

-- Onde o profissional quer trabalhar e em que moeda informou a pretensão. Idioma e país vêm do navegador/IP no cadastro.
ALTER TABLE perfis_profissionais
    ADD COLUMN regiao_vagas     VARCHAR(20) NOT NULL DEFAULT 'brasil' AFTER modelo_trabalho,
    ADD COLUMN moeda_pretensao  CHAR(3)     NOT NULL DEFAULT 'BRL' AFTER pretensao_salarial;

ALTER TABLE usuarios
    ADD COLUMN idioma  VARCHAR(5) NOT NULL DEFAULT 'pt' AFTER telefone,
    ADD COLUMN pais    CHAR(2)    NULL AFTER idioma;

-- Pagamentos em reais (PIX/PayPal) ou dólar (PayPal). Assinaturas recorrentes do PayPal geram um pagamento por cobrança.
ALTER TABLE pagamentos
    ADD COLUMN moeda               CHAR(3)     NOT NULL DEFAULT 'BRL' AFTER valor,
    ADD COLUMN assinatura_externa  VARCHAR(64) NULL AFTER gateway_id,
    ADD KEY ix_pagamentos_assinatura_externa (assinatura_externa);

-- Fila de candidaturas: o profissional pede, a equipe envia e marca o status.
CREATE TABLE IF NOT EXISTS candidaturas (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id     BIGINT UNSIGNED NOT NULL,
    vaga_id        BIGINT UNSIGNED NOT NULL,
    status         VARCHAR(20) NOT NULL DEFAULT 'na_fila',
    criado_em      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_candidaturas_usuario_vaga (usuario_id, vaga_id),
    KEY ix_candidaturas_status (status, criado_em),
    CONSTRAINT fk_candidaturas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_candidaturas_vaga FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
