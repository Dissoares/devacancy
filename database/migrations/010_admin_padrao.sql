-- Administrador padrão para o primeiro acesso, criado só se ainda não existir nenhum administrador.
-- Guarda apenas o hash da senha. Depois de entrar, promova a sua conta em /admin/administradores e exclua esta.
INSERT IGNORE INTO usuarios (tipo, nome, email, senha_hash, cadastro_completo)
SELECT 'admin', 'Administrador padrão', 'admin@devacancy.online', '$2y$10$7XGS4x4aZFs909yBfRMZrer0ZTpqeZFik06IfQPqjBgazSk7d2FQO', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE tipo = 'admin');
