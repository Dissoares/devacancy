-- Configurações do site editadas no painel admin (chaves de pagamento, e-mail, login social...).
-- O banco de dados continua só no .env.
CREATE TABLE IF NOT EXISTS configuracoes (
    chave          VARCHAR(60) PRIMARY KEY,
    valor          TEXT        NOT NULL,
    atualizado_em  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asaas (PIX e cartão no checkout hospedado da Asaas).
INSERT IGNORE INTO metodos_pagamento (chave, ativo) VALUES ('asaas', 1);
