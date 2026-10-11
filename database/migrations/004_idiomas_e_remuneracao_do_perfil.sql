-- Respostas do questionário inicial: idiomas que a pessoa fala e a última remuneração.
ALTER TABLE perfis_profissionais
    ADD COLUMN idiomas             JSON         NULL AFTER stack,
    ADD COLUMN ultima_remuneracao  INT UNSIGNED NULL AFTER pretensao_salarial;
