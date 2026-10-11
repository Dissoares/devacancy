-- De onde a vaga veio: cadastro manual no admin, GitHub (issues da comunidade) ou Adzuna (agregador).
ALTER TABLE vagas
    ADD COLUMN fonte VARCHAR(20) NOT NULL DEFAULT 'manual' AFTER empresa,
    ADD KEY ix_vagas_fonte (fonte);

UPDATE vagas SET fonte = 'github' WHERE link LIKE 'https://github.com/%';
