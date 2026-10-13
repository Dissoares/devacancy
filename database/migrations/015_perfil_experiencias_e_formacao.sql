-- Experiências e formação do profissional (vindas da importação do currículo ou do LinkedIn), guardadas como lista.
ALTER TABLE perfis_profissionais
    ADD COLUMN experiencias LONGTEXT NULL AFTER nivel_ingles,
    ADD COLUMN formacao LONGTEXT NULL AFTER experiencias,
    ADD COLUMN importado_em DATETIME NULL AFTER formacao;
