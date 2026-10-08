# Devacancy

Plataforma de candidaturas para desenvolvedores. PHP 8.3 puro + MySQL + HTMX, sem framework.

## Instalação

```bash
composer dump-autoload -o
cp .env.exemplo .env          # ajuste banco e URL
php database/instalar.php seu@email.com "SenhaForte123"   # cria banco, tabelas e o admin
```

Aponte o servidor para a pasta do projeto (o `.htaccess` da raiz libera só a `public/`).

## Estrutura

```
src/
  Core/          roteador, container (injeção de dependências), sessão, CSRF, validação, views
  Controllers/   recebem a requisição e devolvem a resposta
  Services/      regras de negócio
  Repositories/  acesso ao MySQL (PDO)
  Interfaces/    contratos dos repositórios, provedores OAuth e middlewares
  Entities/      Usuario, PerfilProfissional, PerfilEmpresa
  DTOs/          dados de entrada validados entre as camadas
  Enums/         TipoUsuario, Senioridade, AreaAtuacao, ModeloTrabalho, TamanhoEmpresa, ProvedorSocial
  Middlewares/   autenticação, admin, visitante, perfil completo
  Providers/     login com Google, GitHub e LinkedIn
  Exceptions/
views/           layouts, partials e páginas
public/assets/   css/ (base, componentes, paginas/) e js/ (app, componentes/, paginas/, vendor/)
routes/web.php   todas as rotas
database/        migrações SQL e instalador
storage/         currículos enviados (privados) e logs
```

`Usuario` guarda a conta e o login; os dados específicos ficam em `PerfilProfissional` ou `PerfilEmpresa` (1:1), conforme o `TipoUsuario`.

## Login social

Crie os apps OAuth e preencha as chaves no `.env`. URLs de retorno:

| Provedor | Onde criar | URL de retorno |
|---|---|---|
| Google | Google Cloud Console → APIs e serviços → Credenciais | `{APP_URL}/auth/google/retorno` |
| GitHub | GitHub → Settings → Developer settings → OAuth Apps | `{APP_URL}/auth/github/retorno` |
| LinkedIn | LinkedIn Developers → app com o produto "Sign In with LinkedIn using OpenID Connect" | `{APP_URL}/auth/linkedin/retorno` |

Enquanto as chaves não forem preenchidas, os botões avisam que o login social ainda não está disponível.

## Rotas principais

- `/` landing page
- `/cadastro` e `/cadastro?tipo=empresa` cadastro duplo
- `/entrar` login
- `/completar-perfil` segunda etapa do cadastro
- `/painel` área do profissional ou da empresa
- `/vagas-compativeis` resultado do match logo após o cadastro do profissional (vagas borradas + CTA)
- `/planos` e `/pagamento/{id}` checkout PIX da assinatura
- `/admin/vagas` cadastro das vagas que alimentam o match
- `/admin` painel de cadastros (busca, filtros, detalhes, currículo e exportação CSV)

## Pagamentos (ExPay)

Preencha `EXPAY_API_KEY` e `EXPAY_WEBHOOK_TOKEN` no `.env` e configure no painel da ExPay o webhook `{APP_URL}/webhooks/expay?token={EXPAY_WEBHOOK_TOKEN}`. O webhook só dispara a conferência: o status é sempre consultado na API, e a página do PIX também consulta a cada 5 segundos. Sem chave, em `APP_AMBIENTE=local`, o checkout roda em modo simulação com um botão para aprovar o pagamento. Planos e preços ficam em `config/app.php` (`planos`).
