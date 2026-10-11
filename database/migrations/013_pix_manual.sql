-- PIX manual: o site monta o copia e cola com o valor do plano a partir do código PIX do dono; o admin confirma o recebimento.
INSERT IGNORE INTO metodos_pagamento (chave, ativo) VALUES ('pix_manual', 1);
