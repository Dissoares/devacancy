-- Stripe (cartão, assinatura recorrente): liga/desliga pelo admin como os outros métodos.
INSERT IGNORE INTO metodos_pagamento (chave, ativo) VALUES ('stripe', 1);
