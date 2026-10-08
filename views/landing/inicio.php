<?php
$stacks = ['JavaScript', 'TypeScript', 'React', 'Next.js', 'Vue', 'Angular', 'Node.js', 'PHP', 'Laravel', 'Python', 'Django', 'Java', 'Spring', 'C#', '.NET', 'Go', 'Ruby', 'Kotlin', 'Swift', 'Flutter', 'React Native', 'AWS', 'Docker', 'Kubernetes', 'PostgreSQL', 'MySQL'];
$metade = (int) ceil(count($stacks) / 2);
$faixas = [array_slice($stacks, 0, $metade), array_slice($stacks, $metade)];

$perguntas = [
    ['Como vocês se candidatam por mim?', 'Nosso time analisa o seu perfil, seleciona as vagas que combinam com a sua stack, o seu nível e as suas preferências, e envia a candidatura usando os dados e o currículo que você cadastrou. Cada envio aparece no seu painel, com o link da vaga.'],
    ['Vocês pedem a senha do meu LinkedIn ou de outras contas?', 'Nunca. O login com GitHub, LinkedIn ou Google serve só para criar a sua conta com segurança. Nós não acessamos, não guardamos e não pedimos senhas de nenhuma plataforma.'],
    ['Quantas candidaturas por dia?', 'Até 2 por dia, todos os dias úteis. Preferimos poucas candidaturas certeiras a dezenas de envios genéricos que queimam o seu nome com os recrutadores.'],
    ['Posso escolher onde não quero me candidatar?', 'Sim. Você define o modelo de trabalho, a pretensão salarial e as cidades, e pode pedir para evitar empresas específicas — como a empresa onde você trabalha hoje.'],
    ['Quanto custa?', 'O cadastro é gratuito. Quando a assinatura abrir, quem entrou no beta garante a condição especial de fundador. Você será avisado antes de qualquer cobrança.'],
    ['Sou empresa. Como funciona para mim?', 'Cadastre a sua empresa com as stacks e os níveis que você contrata. Assim que abrirmos o acesso para empresas, você recebe perfis de devs já organizados por stack, senioridade e pretensão.'],
    ['Meus dados estão seguros?', 'Seus dados são usados apenas para encontrar vagas e enviar candidaturas em seu nome, conforme a LGPD. O currículo fica em área privada e você pode pedir a exclusão da conta quando quiser.'],
];
?>

<!-- ============================== HERO ============================== -->
<section class="hero">
  <div class="hero-fundo" aria-hidden="true">
    <div class="hero-grade"></div>
    <div class="hero-luz hero-luz-1"></div>
    <div class="hero-luz hero-luz-2"></div>
  </div>

  <div class="container hero-interno">
    <div class="hero-texto">
      <a class="hero-anuncio" href="/cadastro">
        <span class="selo selo-sucesso selo-ponto">Beta aberto</span>
        <span>Vagas limitadas para os primeiros devs</span>
        <?= icone('chevron-dir', 'icone icone-sm') ?>
      </a>

      <h1 class="hero-titulo">
        Você programa.<br>
        <span class="texto-gradiente">A gente se candidata.</span>
      </h1>

      <p class="hero-subtitulo">
        Cadastre seu perfil uma vez. Todos os dias, encontramos as vagas que combinam com a sua
        <strong>stack</strong> e o seu <strong>nível</strong> — e enviamos a sua candidatura por você.
      </p>

      <div class="hero-acoes">
        <a class="botao botao-primario botao-grande" href="/cadastro">
          Quero entrar no beta <?= icone('seta', 'icone icone-seta') ?>
        </a>
        <a class="botao botao-secundario botao-grande" href="/cadastro?tipo=empresa">
          <?= icone('predio') ?> Sou empresa
        </a>
      </div>

      <div class="hero-social">
        <span>Cadastro em 1 minuto com</span>
        <div class="hero-social-icones">
          <span title="GitHub"><?= icone('github') ?></span>
          <span title="LinkedIn"><?= icone('linkedin') ?></span>
          <span title="Google"><?= icone('google') ?></span>
        </div>
      </div>
    </div>

    <!-- Demonstração do painel -->
    <div class="hero-demo" aria-hidden="true">
      <div class="demo-janela">
        <div class="demo-barra">
          <span class="demo-ponto"></span><span class="demo-ponto"></span><span class="demo-ponto"></span>
          <span class="demo-endereco mono">devacancy.com.br/painel</span>
        </div>

        <div class="demo-corpo">
          <div class="demo-perfil">
            <div class="avatar">MS</div>
            <div>
              <strong>Mariana S.</strong>
              <span>Back-end Pleno · Remoto</span>
            </div>
            <span class="selo selo-sucesso selo-ponto demo-status-ativo">Ativo</span>
          </div>

          <div class="demo-terminal mono" data-terminal>
            <div class="linha-terminal"><span class="prompt">›</span> <span data-digitar="analisando perfil: PHP · Laravel · MySQL · Docker"></span></div>
            <div class="linha-terminal"><span class="prompt">›</span> <span data-digitar="42 vagas novas · 6 com match acima de 85%"></span></div>
            <div class="linha-terminal"><span class="prompt sucesso">✓</span> <span data-digitar="2 candidaturas enviadas hoje"></span></div>
          </div>

          <div class="demo-lista">
            <div class="demo-item" style="--atraso: 0">
              <div class="demo-logo" style="--cor: #7c6cff">F</div>
              <div class="demo-info">
                <strong>Back-end Pleno</strong>
                <span>Fintech · PHP / Laravel</span>
              </div>
              <span class="demo-match">94%</span>
              <span class="selo selo-sucesso">Entrevista</span>
            </div>
            <div class="demo-item" style="--atraso: 1">
              <div class="demo-logo" style="--cor: #4cc9f0">S</div>
              <div class="demo-info">
                <strong>Desenvolvedora PHP</strong>
                <span>SaaS B2B · Remoto</span>
              </div>
              <span class="demo-match">91%</span>
              <span class="selo selo-primario">Em análise</span>
            </div>
            <div class="demo-item" style="--atraso: 2">
              <div class="demo-logo" style="--cor: #ffb547">E</div>
              <div class="demo-info">
                <strong>Software Engineer</strong>
                <span>E-commerce · Híbrido</span>
              </div>
              <span class="demo-match">87%</span>
              <span class="selo selo-neutro">Enviada</span>
            </div>
          </div>
        </div>
      </div>

      <div class="demo-flutuante demo-flutuante-1">
        <span class="demo-flutuante-icone"><?= icone('check') ?></span>
        <div><strong>Candidatura enviada</strong><span>Back-end Pleno · agora</span></div>
      </div>
      <div class="demo-flutuante demo-flutuante-2">
        <span class="demo-flutuante-icone roxo"><?= icone('alvo') ?></span>
        <div><strong>Match de 94%</strong><span>Stack e nível compatíveis</span></div>
      </div>
    </div>
  </div>
</section>

<!-- ============================== STACKS ============================== -->
<section class="faixa-stacks" aria-label="Stacks atendidas">
  <p class="faixa-stacks-titulo">Para devs de todas as stacks e níveis — do estágio ao tech lead</p>
  <?php foreach ($faixas as $indice => $faixa): ?>
    <div class="letreiro <?= $indice === 1 ? 'letreiro-reverso' : '' ?>">
      <div class="letreiro-trilho">
        <?php for ($repeticao = 0; $repeticao < 2; $repeticao++): ?>
          <?php foreach ($faixa as $stack): ?>
            <span class="letreiro-item"><?= icone('codigo', 'icone icone-sm') ?><?= e($stack) ?></span>
          <?php endforeach; ?>
        <?php endfor; ?>
      </div>
    </div>
  <?php endforeach; ?>
</section>

<!-- ============================== PROBLEMA ============================== -->
<section class="secao">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo">O problema</span>
      <h2>Procurar vaga virou um <span class="texto-gradiente">segundo emprego</span>.</h2>
      <p>Você é pago para resolver problemas com código — não para preencher o mesmo formulário trinta vezes.</p>
    </div>

    <div class="grade-dores">
      <article class="dor revelar">
        <span class="dor-icone"><?= icone('relogio') ?></span>
        <h3>Horas filtrando vagas</h3>
        <p>Dezenas de sites, filtros diferentes e vagas que nem combinam com a sua stack ou o seu nível.</p>
      </article>
      <article class="dor revelar">
        <span class="dor-icone"><?= icone('arquivo') ?></span>
        <h3>Formulários sem fim</h3>
        <p>Recadastrar experiência, links e currículo em cada plataforma, de novo e de novo.</p>
      </article>
      <article class="dor revelar">
        <span class="dor-icone"><?= icone('caixa') ?></span>
        <h3>Zero acompanhamento</h3>
        <p>Depois de enviar, você perde o controle de onde se candidatou e do que aconteceu com cada vaga.</p>
      </article>
    </div>
  </div>
</section>

<!-- ============================== COMO FUNCIONA ============================== -->
<section class="secao secao-destaque" id="como-funciona">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo">Como funciona</span>
      <h2>Três passos. Depois, é com a gente.</h2>
      <p>Você investe um minuto no cadastro. Nós cuidamos da busca e das candidaturas todos os dias.</p>
    </div>

    <ol class="passos">
      <li class="passo revelar">
        <span class="passo-numero mono">01</span>
        <div class="passo-visual">
          <div class="mini-botoes-sociais">
            <span><?= icone('github') ?> GitHub</span>
            <span><?= icone('linkedin') ?> LinkedIn</span>
            <span><?= icone('google') ?> Google</span>
          </div>
        </div>
        <h3>Crie seu perfil em 1 minuto</h3>
        <p>Entre com GitHub, LinkedIn ou Google, informe a sua stack, o seu nível e o que você procura. Anexe o currículo.</p>
      </li>
      <li class="passo revelar">
        <span class="passo-numero mono">02</span>
        <div class="passo-visual">
          <div class="mini-match">
            <div class="mini-match-linha"><span>Stack</span><i style="--valor: 92%"></i><b>92%</b></div>
            <div class="mini-match-linha"><span>Nível</span><i style="--valor: 100%"></i><b>100%</b></div>
            <div class="mini-match-linha"><span>Salário</span><i style="--valor: 85%"></i><b>85%</b></div>
          </div>
        </div>
        <h3>A gente faz a curadoria</h3>
        <p>Selecionamos diariamente vagas compatíveis com a sua stack, senioridade, pretensão salarial e modelo de trabalho.</p>
      </li>
      <li class="passo revelar">
        <span class="passo-numero mono">03</span>
        <div class="passo-visual">
          <div class="mini-status">
            <span class="selo selo-neutro">Enviada</span>
            <span class="selo selo-primario">Em análise</span>
            <span class="selo selo-sucesso">Entrevista</span>
          </div>
        </div>
        <h3>Candidaturas todos os dias</h3>
        <p>Até 2 candidaturas por dia, com o seu perfil ajustado para cada vaga. Você acompanha tudo pelo painel.</p>
      </li>
    </ol>
  </div>
</section>

<!-- ============================== RECURSOS ============================== -->
<section class="secao" id="recursos">
  <div class="container">
    <div class="secao-cabecalho revelar">
      <span class="sobretitulo">Recursos</span>
      <h2>Feito para quem quer qualidade,<br>não spam de currículo.</h2>
    </div>

    <div class="grade-recursos">
      <article class="recurso recurso-largo revelar" data-holofote>
        <div class="recurso-texto">
          <span class="recurso-icone"><?= icone('alvo') ?></span>
          <h3>Match por stack e senioridade</h3>
          <p>Cada vaga é avaliada contra o seu perfil real: tecnologias, anos de experiência, nível e preferências. Só vai o que faz sentido.</p>
        </div>
        <div class="recurso-visual-stack">
          <?php foreach (['PHP' => true, 'Laravel' => true, 'Vue' => true, 'Docker' => true, 'AWS' => false, 'Kotlin' => false, 'MySQL' => true, 'Redis' => false] as $tecnologia => $combina): ?>
            <span class="etiqueta <?= $combina ? 'etiqueta-combina' : '' ?>"><?= $combina ? icone('check', 'icone icone-sm') : '' ?><?= e($tecnologia) ?></span>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="recurso revelar" data-holofote>
        <span class="recurso-icone"><?= icone('brilho') ?></span>
        <h3>Perfil ajustado a cada vaga</h3>
        <p>Destacamos as experiências que mais importam para cada oportunidade.</p>
      </article>

      <article class="recurso revelar" data-holofote>
        <span class="recurso-icone"><?= icone('grafico') ?></span>
        <h3>Painel de acompanhamento</h3>
        <p>Veja empresa, vaga, data e status de cada candidatura em um só lugar.</p>
      </article>

      <article class="recurso revelar" data-holofote>
        <span class="recurso-icone"><?= icone('ajustes') ?></span>
        <h3>Você no controle</h3>
        <p>Defina salário mínimo, remoto ou híbrido e empresas que prefere evitar.</p>
      </article>

      <article class="recurso revelar" data-holofote>
        <span class="recurso-icone"><?= icone('usuarios') ?></span>
        <h3>Curadoria humana</h3>
        <p>Pessoas reais revisam cada envio. Nada de robô disparando currículo.</p>
      </article>

      <article class="recurso recurso-faixa revelar" data-holofote>
        <span class="recurso-icone"><?= icone('escudo') ?></span>
        <div>
          <h3>Privacidade de verdade</h3>
          <p>Nunca pedimos a senha das suas contas. Seus dados são usados só para as suas candidaturas, conforme a LGPD — e você pode excluir tudo quando quiser.</p>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- ============================== EMPRESAS ============================== -->
<section class="secao" id="empresas">
  <div class="container">
    <div class="empresas revelar">
      <div class="empresas-texto">
        <span class="sobretitulo">Para empresas</span>
        <h2>Contratando devs?<br>Pare de garimpar.</h2>
        <p>Receba perfis de desenvolvedores já organizados por stack, senioridade e pretensão salarial — com currículo, GitHub e LinkedIn em um só lugar.</p>
        <ul class="lista-verificada">
          <li><?= icone('check', 'icone icone-sm') ?> Devs filtrados pela stack que você usa</li>
          <li><?= icone('check', 'icone icone-sm') ?> Nível e pretensão definidos antes da conversa</li>
          <li><?= icone('check', 'icone icone-sm') ?> Prioridade no acesso ao banco de talentos</li>
        </ul>
        <a class="botao botao-claro botao-grande" href="/cadastro?tipo=empresa">
          Cadastrar minha empresa <?= icone('seta', 'icone icone-seta') ?>
        </a>
      </div>

      <div class="empresas-visual" aria-hidden="true">
        <?php foreach ([
            ['RA', 'Dev Front-end', 'Sênior', ['React', 'TypeScript', 'Next.js'], 'Remoto'],
            ['LM', 'Dev Back-end', 'Pleno', ['Node.js', 'PostgreSQL', 'AWS'], 'Híbrido'],
            ['JP', 'Dev Mobile', 'Pleno', ['Flutter', 'Dart', 'Firebase'], 'Remoto'],
        ] as [$iniciais, $cargo, $nivel, $tecnologias, $modelo]): ?>
          <div class="candidato">
            <div class="avatar"><?= e($iniciais) ?></div>
            <div class="candidato-info">
              <strong><?= e($cargo) ?> <span class="selo selo-primario"><?= e($nivel) ?></span></strong>
              <div class="lista-etiquetas">
                <?php foreach ($tecnologias as $tecnologia): ?><span class="etiqueta"><?= e($tecnologia) ?></span><?php endforeach; ?>
              </div>
            </div>
            <span class="candidato-modelo"><?= icone('local', 'icone icone-sm') ?><?= e($modelo) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ============================== BETA ============================== -->
<section class="secao">
  <div class="container">
    <div class="beta revelar">
      <div class="beta-texto">
        <span class="selo selo-primario selo-ponto">Beta fundador</span>
        <h2>Entre agora e garanta a condição de fundador.</h2>
        <p>Estamos abrindo a plataforma aos poucos para manter a qualidade da curadoria. Quem entra no beta tem prioridade e condição especial quando a assinatura abrir.</p>
      </div>
      <div class="beta-cartao">
        <ul class="beta-lista">
          <li><?= icone('check', 'icone icone-sm') ?> Cadastro gratuito</li>
          <li><?= icone('check', 'icone icone-sm') ?> Até 2 candidaturas por dia</li>
          <li><?= icone('check', 'icone icone-sm') ?> Painel com o status de cada vaga</li>
          <li><?= icone('check', 'icone icone-sm') ?> Condição especial de fundador</li>
        </ul>
        <a class="botao botao-primario botao-grande botao-bloco" href="/cadastro">Garantir minha vaga no beta <?= icone('seta', 'icone icone-seta') ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ============================== FAQ ============================== -->
<section class="secao" id="perguntas">
  <div class="container faq">
    <div class="secao-cabecalho secao-cabecalho-esquerda revelar">
      <span class="sobretitulo">Dúvidas</span>
      <h2>Perguntas frequentes</h2>
      <p>Não encontrou o que procurava? Crie sua conta e fale com a gente pelo painel.</p>
    </div>

    <div class="faq-lista">
      <?php foreach ($perguntas as $indice => [$pergunta, $resposta]): ?>
        <details class="faq-item revelar" <?= $indice === 0 ? 'open' : '' ?>>
          <summary><?= e($pergunta) ?><span class="faq-sinal" aria-hidden="true"></span></summary>
          <div class="faq-resposta"><p><?= e($resposta) ?></p></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================== CTA FINAL ============================== -->
<section class="secao secao-final">
  <div class="container">
    <div class="cta-final revelar">
      <div class="cta-final-brilho" aria-hidden="true"></div>
      <h2>Sua próxima vaga pode<br><span class="texto-gradiente">começar hoje.</span></h2>
      <p>Um minuto de cadastro. Candidaturas todos os dias. Você só se prepara para as entrevistas.</p>
      <div class="hero-acoes">
        <a class="botao botao-primario botao-grande" href="/cadastro">Criar minha conta grátis <?= icone('seta', 'icone icone-seta') ?></a>
        <a class="botao botao-secundario botao-grande" href="/cadastro?tipo=empresa">Sou empresa</a>
      </div>
    </div>
  </div>
</section>
