-- PayPal "só com e-mail": pagamento avulso confirmado pelo aviso automático (IPN) do PayPal ou à mão pelo admin.
INSERT IGNORE INTO metodos_pagamento (chave, ativo) VALUES ('paypal_email', 1);
