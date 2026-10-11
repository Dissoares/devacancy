-- Nível de inglês, só preenchido para quem marcou inglês nos idiomas que fala.
ALTER TABLE perfis_profissionais
    ADD COLUMN nivel_ingles VARCHAR(20) NULL AFTER idiomas;
