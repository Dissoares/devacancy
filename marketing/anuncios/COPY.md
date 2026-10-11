# Anúncios Devacancy — Facebook e Instagram

Três peças, cada uma com um gatilho diferente, nos formatos **Feed 4:5 (1080×1350)** e **Stories/Reels 9:16 (1080×1920)**.
Os PNGs ficam nesta pasta (`a-…`, `b-…`, `c-…`). Para refazer: `node marketing/anuncios/gerar.js` (precisa do Playwright).

Link de destino (um por peça, para você ver qual converte):

```
https://devacancy.online/comecar?utm_source=facebook&utm_medium=paid_social&utm_campaign=lancamento&utm_content=a-marca
https://devacancy.online/comecar?utm_source=facebook&utm_medium=paid_social&utm_campaign=lancamento&utm_content=b-curiosidade
https://devacancy.online/comecar?utm_source=facebook&utm_medium=paid_social&utm_campaign=lancamento&utm_content=c-dor-tempo
```

---

## Peça A — "Você programa. A gente se candidata." (marca + simplicidade)

**Gatilho:** promessa clara do produto em uma frase, com a prova visual de uma vaga real da plataforma.

- **Texto principal:**
  > Você programa. A gente se candidata. 👨‍💻
  >
  > Responda 1 minuto de perguntas, veja as vagas de dev que combinam com você e deixe a nossa equipe enviar as candidaturas.
  >
  > ✅ Ver as vagas é grátis, sem cartão
  > ✅ Filtradas por stack, nível e modelo de trabalho
  > ✅ Você acompanha tudo em um painel
- **Título:** Você programa. A gente se candidata.
- **Descrição:** Veja suas vagas grátis
- **Botão:** Cadastre-se
- **Imagem:** `a-marca-feed.png` / `a-marca-story.png`

## Peça B — "Quantas vagas combinam com você?" (curiosidade + especificidade)

**Gatilho:** lacuna de curiosidade ("?" no lugar do número) + baixo esforço ("1 minuto") + o resultado é personalizado.

- **Texto principal:**
  > Quantas vagas de dev combinam com o seu perfil? 🤔
  >
  > Responda 1 minuto e descubra: a gente cruza sua stack, nível, modelo de trabalho e pretensão com as vagas abertas e mostra só as que fazem sentido.
  >
  > É grátis e não pede cartão.
- **Título:** Descubra suas vagas em 1 minuto
- **Descrição:** Grátis · sem cartão
- **Botão:** Saiba mais
- **Imagem:** `b-curiosidade-feed.png` / `b-curiosidade-story.png`

## Peça C — "Cansou de preencher formulário de vaga?" (dor + tempo)

**Gatilho:** dor conhecida de todo dev (formulários repetidos) + o antes/depois mostra o ganho de tempo.

- **Texto principal:**
  > Cansou de preencher formulário de vaga? 😮‍💨
  >
  > Enquanto você programa, a nossa equipe envia as suas candidaturas todos os dias. Você só acompanha e se prepara para as entrevistas.
  >
  > Comece vendo as vagas compatíveis, de graça.
- **Título:** Pare de preencher formulário
- **Descrição:** Candidaturas enviadas por nós
- **Botão:** Cadastre-se
- **Imagem:** `c-dor-tempo-feed.png` / `c-dor-tempo-story.png`

---

## Variações de título para testar (qualquer peça)

- Seu tempo vale mais que formulário
- Vagas de dev que combinam com a sua stack
- Veja suas vagas antes de assinar qualquer coisa
- Um minuto agora, candidaturas todos os dias

## Como subir a campanha

- **Objetivo:** Cadastros/Leads (com o Pixel instalado). Sem Pixel, comece com "Tráfego" para a página.
- **Público:** Brasil, 20 a 45 anos. Interesses: desenvolvimento de software, programação, JavaScript, PHP, Python, React, Node.js, GitHub, Stack Overflow, desenvolvimento web. Comece amplo e deixe o algoritmo achar.
- **Posicionamentos:** Feed e Stories/Reels do Facebook e do Instagram (use o 4:5 no feed e o 9:16 nos Stories/Reels).
- **Teste:** um conjunto de anúncios com as 3 peças, R$ 30 a R$ 50 por dia por 5 a 7 dias. Pause as que tiverem custo por cadastro bem acima das outras e coloque o orçamento na melhor.
- **Exclusão:** assim que houver Pixel, exclua quem já se cadastrou.

## Cuidados com as regras da Meta

- Não prometa emprego nem resultado ("garantimos vaga", "você será contratado"). Os textos acima só prometem o que o produto faz.
- Não dirija o texto a uma característica pessoal ("você está desempregado?"). Fale da ação ("cansou de preencher formulário?").
- A página de destino precisa ter **política de privacidade** e **termos de uso** acessíveis, e o contato visível. O site ainda não tem essas páginas.
- Quando houver depoimentos ou números, use só dados reais da plataforma.
