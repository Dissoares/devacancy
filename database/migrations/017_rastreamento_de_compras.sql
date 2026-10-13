-- Marca os pagamentos cuja conversão já foi enviada ao Pixel e ao Analytics, para cada compra disparar uma só vez.
ALTER TABLE pagamentos ADD COLUMN rastreado_em DATETIME NULL AFTER pago_em;

-- Compras que já existem não viram conversão nova (senão o primeiro acesso depois da atualização contaria todas de novo).
UPDATE pagamentos SET rastreado_em = NOW() WHERE rastreado_em IS NULL;
