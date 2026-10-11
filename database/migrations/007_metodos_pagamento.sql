-- Liga/desliga de cada forma de pagamento pelo admin, independente de ter chave configurada no .env.
CREATE TABLE IF NOT EXISTS metodos_pagamento (
    chave          VARCHAR(20) PRIMARY KEY,
    ativo          TINYINT(1) NOT NULL DEFAULT 1,
    atualizado_em  DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO metodos_pagamento (chave, ativo) VALUES ('pix', 1), ('paypal', 1);
