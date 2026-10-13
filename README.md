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
- `/comecar` questionário do profissional (anônimo, uma pergunta por tela)
- `/vagas-compativeis` análise animada e resultado do match (antes do cadastro)
- `/criar-conta` cadastro do profissional: e-mail e senha ou Google/GitHub/LinkedIn
- `/planos` checkout: PIX + PayPal (Brasil, em reais) ou só PayPal (outros países, em dólar)
- `/admin/vagas` cadastro das vagas (região Brasil/internacional e moeda) que alimentam o match
- `/admin/candidaturas` fila de candidaturas pedidas pelos assinantes
- `/admin` painel de cadastros (busca, filtros, detalhes, currículo e exportação CSV)

## Idiomas

Português para navegadores em português, inglês para os demais (`?lang=pt|en` troca e fica salvo). Textos em `lang/pt.php` e `lang/en.php`, usados com `t('chave')`. O país (geolocalização do IP via ipwho.is) define moeda e meios de pagamento; em ambiente local, simule com `?pais=US`.

## Pagamentos (PayPal)

Preencha `PAYPAL_CLIENT_ID` e `PAYPAL_SECRET`, rode `php database/paypal-planos.php` para criar os planos (semanal, mensal e trimestral em BRL e USD) e cole os IDs no `.env`. Webhook no painel do PayPal: `{APP_URL}/webhooks/paypal?token={PAYPAL_WEBHOOK_TOKEN}` (eventos `PAYMENT.SALE.COMPLETED` e `BILLING.SUBSCRIPTION.*`). Cada ciclo cobrado vira um pagamento e estende a assinatura; reprocessar o mesmo evento não duplica. Sem credenciais, em ambiente local, aparece um botão de simulação.

## Pagamentos (ExPay)

Preencha `EXPAY_API_KEY` e `EXPAY_WEBHOOK_TOKEN` no `.env` e configure no painel da ExPay o webhook `{APP_URL}/webhooks/expay?token={EXPAY_WEBHOOK_TOKEN}`. O webhook só dispara a conferência: o status é sempre consultado na API, e a página do PIX também consulta a cada 5 segundos. Sem chave, em `APP_AMBIENTE=local` **com o `APP_URL` apontando para a sua máquina** (`.test`, `localhost`), o checkout roda em modo simulação com um botão para aprovar o pagamento. No servidor use sempre `APP_AMBIENTE=producao`. Planos e preços ficam em `config/app.php` (`planos`).

## Vagas reais (importação automática)

`php database/importar-vagas.php` importa vagas reais de duas fontes gratuitas, sempre pulando repetidas e anúncios com mais de 45 dias:

- **GitHub**: vagas abertas dos repositórios de vagas da comunidade (lista em `config/app.php`, chave `github`), interpretando o título e a descrição da issue. Vagas restritas a moradores dos EUA/Canadá são puladas. Sem `GITHUB_TOKEN` no `.env`, o limite da API é 60 consultas por hora (sobe para 5.000 com o token).
- **Adzuna**: agregador de vagas com API gratuita. Crie uma conta em [developer.adzuna.com/signup](https://developer.adzuna.com/signup) e preencha `ADZUNA_APP_ID`/`ADZUNA_APP_KEY` no `.env`; sem essas chaves, esse importador é pulado e só o do GitHub roda. Já vem filtrado para vagas no Brasil (`config/app.php`, chave `adzuna`).

As duas fontes usam o mesmo classificador (`App\Core\ClassificadorVaga`) para adivinhar área, senioridade, modelo de trabalho e stack a partir do texto. Agende o script para rodar 1x por dia.

`php database/baixar-cidades.php` baixa a lista oficial de municípios do Brasil (API do IBGE) e salva em `public/assets/data/cidades-br.json`, usada pelo autocomplete de cidade no cadastro e no perfil. Só é preciso rodar de novo se o IBGE criar algum município.

## Colocando no ar (checklist)

O que precisa estar certo antes de divulgar. Depois de subir os arquivos, abra **Admin → Sistema → Saúde do sistema**: a página confere quase tudo abaixo sozinha.

1. **Arquivos**: suba o projeto inteiro (inclusive `vendor/`, `storage/fontes/` e `public/assets/js/vendor/pdfjs/`). Não suba o `.env` do seu computador nem a pasta `storage/logs/`.
2. **`.env` mínimo no servidor**: só a conexão com o banco (`DB_*`). Todo o resto é configurado em **Admin → Configurações**.
3. **Banco**: rode as migrações em **Admin → Banco de dados**. Sem acesso ao admin ainda, use `/install` com `MIGRACOES_TOKEN` no `.env` (e remova o token depois).
4. **Site**: em Configurações → Site, endereço `https://seudominio` e ambiente **Produção**.
5. **Pagamentos**: configure ao menos uma forma para o Brasil (Asaas, ou PIX manual enquanto isso) e uma para o exterior (Stripe ou PayPal). Cadastre os webhooks nos provedores, usando os endereços mostrados nos cartões.
6. **E-mail**: SMTP em Configurações → E-mail e envie o teste.
7. **Administrador**: promova a sua conta em Administradores e exclua o administrador padrão.
8. **Vagas**: importe em Operação → Vagas e agende a importação (`php database/importar-vagas.php` no cron) ou clique em "Importar do GitHub agora" de vez em quando.
9. **Cloudflare (opcional)**: o site já reconhece o IP e o país reais atrás dela. Deixe o SSL em "Full".
10. **Legal**: `/privacidade` e `/termos` já estão publicadas. Peça a revisão de um advogado e preencha razão social e CNPJ quando tiver.
