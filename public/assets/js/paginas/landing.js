/**
 * Devacancy — landing page: cabeçalho, menu móvel, animações de entrada e demonstração do painel.
 */
(() => {
  'use strict';

  const reduzirMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---------- Cabeçalho ----------
  const cabecalho = document.querySelector('[data-cabecalho]');
  const botaoMenu = document.querySelector('[data-botao-menu]');

  const atualizarCabecalho = () => cabecalho?.classList.toggle('rolado', window.scrollY > 12);
  atualizarCabecalho();
  window.addEventListener('scroll', atualizarCabecalho, { passive: true });

  const alternarMenu = (abrir) => {
    cabecalho.classList.toggle('menu-aberto', abrir);
    botaoMenu.setAttribute('aria-expanded', String(abrir));
    botaoMenu.setAttribute('aria-label', abrir ? botaoMenu.dataset.rotuloFechar : botaoMenu.dataset.rotuloAbrir);
  };

  botaoMenu?.addEventListener('click', () => alternarMenu(!cabecalho.classList.contains('menu-aberto')));
  document.querySelectorAll('#navegacao a').forEach((link) => link.addEventListener('click', () => alternarMenu(false)));

  // ---------- Animação de entrada ----------
  const elementos = document.querySelectorAll('.revelar');
  if ('IntersectionObserver' in window && !reduzirMovimento) {
    const observador = new IntersectionObserver((entradas) => {
      entradas.forEach((entrada) => {
        if (!entrada.isIntersecting) return;
        entrada.target.classList.add('visivel');
        observador.unobserve(entrada.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    elementos.forEach((elemento, indice) => {
      elemento.style.transitionDelay = `${(indice % 3) * 80}ms`;
      observador.observe(elemento);
    });
  } else {
    elementos.forEach((elemento) => elemento.classList.add('visivel'));
  }

  // ---------- Números reais que contam de 0 até o valor ao carregar ----------
  document.querySelectorAll('[data-contar-ate]').forEach((elemento) => {
    const alvo = Number(elemento.dataset.contarAte) || 0;
    const formatar = new Intl.NumberFormat(document.documentElement.lang);
    const inicio = performance.now();
    const passo = (agora) => {
      const fracao = Math.min(1, (agora - inicio) / 1200);
      elemento.textContent = formatar.format(Math.round(alvo * (1 - (1 - fracao) ** 3)));
      if (fracao < 1) requestAnimationFrame(passo);
    };
    requestAnimationFrame(passo);
  });

  // ---------- Palavra que alterna no título ("Você programa em React." → Python → Java…) ----------
  const palavra = document.querySelector('.palavra-rotativa');
  if (palavra) {
    const palavras = JSON.parse(palavra.dataset.palavras || '[]');
    // Pré-carrega os logos para a troca não piscar.
    palavras.forEach(({ icone }) => { new Image().src = icone; });
    let indicePalavra = 0;
    const duracaoSaida = reduzirMovimento ? 0 : 220;

    const trocar = () => {
      indicePalavra = (indicePalavra + 1) % palavras.length;
      const antiga = palavra.firstElementChild;
      antiga.classList.add('saindo');

      // Só insere a palavra nova depois que a antiga sumiu por completo: nunca ficam as duas
      // visíveis juntas (palavras de tamanhos bem diferentes, como "Java" e "Python", se sobrepunham).
      setTimeout(() => {
        antiga.remove();
        const { nome, icone } = palavras[indicePalavra];
        const nova = document.createElement('span');
        const logo = document.createElement('img');
        logo.className = 'palavra-icone';
        logo.src = icone;
        logo.alt = '';
        nova.append(logo, nome);
        nova.className = 'entrando';
        palavra.appendChild(nova);
      }, duracaoSaida);
    };

    setInterval(trocar, 2200);
  }

  // ---------- Vitrine "ao vivo": a vaga do fim da lista volta para o topo, como se tivesse acabado de entrar ----------
  // A lista é buscada a cada volta porque o HTMX troca a vitrine inteira a cada minuto.
  if (document.querySelector('.vitrine-lista')) {
    setInterval(() => {
      const listaVitrine = document.querySelector('.vitrine-lista');
      if (!listaVitrine || listaVitrine.children.length < 4) return;
      const ultima = listaVitrine.lastElementChild;
      ultima.classList.remove('nova');
      listaVitrine.prepend(ultima);
      void ultima.offsetWidth;
      ultima.classList.add('nova');
    }, 3600);
  }

  // ---------- "Como funciona": abas que avançam sozinhas (estilo stories) trocando a tela do celular ----------
  const demo = document.querySelector('[data-demo-passos]');
  if (demo) {
    const abas = [...demo.querySelectorAll('[data-aba]')];
    const cenas = [...demo.querySelectorAll('[data-cena]')];
    const duracao = 4800;
    let atualAba = 0;
    let temporizador = null;
    let pausado = false;

    const ativar = (indice) => {
      atualAba = indice;
      abas.forEach((aba, posicao) => {
        aba.classList.toggle('ativa', posicao === indice);
        aba.setAttribute('aria-pressed', String(posicao === indice));
        aba.style.setProperty('--duracao', `${duracao}ms`);
      });
      cenas.forEach((cena, posicao) => cena.classList.toggle('ativa', posicao === indice));
      demo.classList.remove('rodando');
      void demo.offsetWidth;
      demo.classList.add('rodando');
      clearTimeout(temporizador);
      temporizador = setTimeout(() => !pausado && ativar((atualAba + 1) % abas.length), duracao);
    };

    abas.forEach((aba, indice) => aba.addEventListener('click', () => ativar(indice)));
    demo.addEventListener('mouseenter', () => { pausado = true; demo.classList.add('pausado'); });
    demo.addEventListener('mouseleave', () => { pausado = false; demo.classList.remove('pausado'); ativar(atualAba); });

    // Só começa quando a seção aparece na tela.
    if ('IntersectionObserver' in window) {
      const observador = new IntersectionObserver(([entrada]) => {
        if (!entrada.isIntersecting) return;
        ativar(0);
        observador.disconnect();
      }, { threshold: 0.35 });
      observador.observe(demo);
    } else {
      ativar(0);
    }
  }

  // ---------- CTA fixo no celular ----------
  // Regra direta, recalculada a cada rolagem: aparece quando o início do questionário já saiu da tela por cima
  // e o botão laranja do CTA final não está visível (para não duplicar). O body ganha "com-cta-fixo" para o
  // rodapé reservar espaço só enquanto o botão está na tela.
  const ctaFixo = document.querySelector('[data-cta-fixo]');
  const inicioQuiz = document.querySelector('#comecar');
  const botaoFinal = document.querySelector('.cta-final .inicio-quiz-final');
  if (ctaFixo && inicioQuiz) {
    const linkFixo = ctaFixo.querySelector('a');
    let agendado = false;

    // O cartão do CTA final só "esconde" o botão fixo enquanto está bem à vista (quase metade dele na tela).
    // Se sobrou só uma faixa no topo (a pessoa já está no rodapé), o botão fixo volta: nada fica sem ação.
    const bemVisivel = (elemento) => {
      const area = elemento.getBoundingClientRect();
      const sobreposto = Math.min(area.bottom, window.innerHeight) - Math.max(area.top, 0);
      return sobreposto / area.height >= 0.45;
    };

    const atualizar = () => {
      agendado = false;
      const passouDoInicio = inicioQuiz.getBoundingClientRect().bottom < 0;
      const visivel = passouDoInicio && !(botaoFinal && bemVisivel(botaoFinal));
      ctaFixo.classList.toggle('visivel', visivel);
      document.body.classList.toggle('com-cta-fixo', visivel);
      ctaFixo.setAttribute('aria-hidden', String(!visivel));
      linkFixo.tabIndex = visivel ? 0 : -1;
    };

    const agendar = () => {
      if (agendado) return;
      agendado = true;
      requestAnimationFrame(atualizar);
    };

    window.addEventListener('scroll', agendar, { passive: true });
    window.addEventListener('resize', agendar);
    atualizar();
  }

  // ---------- Holofote que segue o mouse nos cartões ----------
  document.querySelectorAll('[data-holofote]').forEach((cartao) => {
    cartao.addEventListener('pointermove', (evento) => {
      const area = cartao.getBoundingClientRect();
      cartao.style.setProperty('--x', `${evento.clientX - area.left}px`);
      cartao.style.setProperty('--y', `${evento.clientY - area.top}px`);
    });
  });

  // ---------- Terminal da demonstração (efeito de digitação em loop) ----------
  const linhas = [...document.querySelectorAll('[data-terminal] [data-digitar]')];
  if (!linhas.length) return;

  if (reduzirMovimento) {
    linhas.forEach((linha) => { linha.textContent = linha.dataset.digitar; });
    return;
  }

  const cursor = document.createElement('span');
  cursor.className = 'cursor-terminal';
  const esperar = (ms) => new Promise((resolver) => setTimeout(resolver, ms));

  async function digitar(linha) {
    const texto = linha.dataset.digitar;
    linha.after(cursor);
    for (let i = 1; i <= texto.length; i++) {
      linha.textContent = texto.slice(0, i);
      await esperar(22 + Math.random() * 30);
    }
  }

  async function ciclo() {
    for (;;) {
      linhas.forEach((linha) => { linha.textContent = ''; });
      for (const linha of linhas) {
        await digitar(linha);
        await esperar(450);
      }
      await esperar(4200);
    }
  }

  setTimeout(ciclo, 700);
})();

/**
 * Aviso de saída (só computador): quando o mouse sobe em direção à barra do navegador, oferece começar.
 * Regras para não irritar: só depois de 12s na página, só uma vez por visita, nunca para quem já clicou em
 * começar, e nunca em tela de toque.
 */
(() => {
  'use strict';

  const caixa = document.querySelector('[data-saida]');
  if (!caixa || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

  const CHAVE = 'dv_saida_vista';
  const jaVista = () => { try { return sessionStorage.getItem(CHAVE) === '1'; } catch { return false; } };
  const marcarVista = () => { try { sessionStorage.setItem(CHAVE, '1'); } catch { /* sem armazenamento: só não lembra */ } };
  if (jaVista()) return;

  const inicio = Date.now();
  let anterior = null;

  function abrir() {
    if (!caixa.hidden || jaVista() || Date.now() - inicio < 12000) return;
    marcarVista();
    anterior = document.activeElement;
    caixa.hidden = false;
    caixa.querySelector('[data-saida-cta]').focus();
  }

  function fechar() {
    caixa.hidden = true;
    anterior?.focus?.();
  }

  document.addEventListener('mouseout', (evento) => {
    if (!evento.relatedTarget && evento.clientY <= 0) abrir();
  });

  caixa.querySelectorAll('[data-saida-fechar]').forEach((el) => el.addEventListener('click', fechar));
  document.addEventListener('keydown', (evento) => { if (evento.key === 'Escape' && !caixa.hidden) fechar(); });

  // Quem já foi para o questionário não precisa ser interrompido depois.
  document.addEventListener('click', (evento) => {
    if (evento.target.closest('a[href^="/comecar"]')) marcarVista();
  });
})();

/**
 * Constelação do hero: os logos acompanham o mouse com profundidades diferentes (parallax)
 * e as animações pausam quando o hero sai da tela.
 */
(() => {
  'use strict';

  const hero = document.querySelector('.hero');
  const constelacao = hero?.querySelector('[data-constelacao]');
  if (!constelacao) return;

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(([entrada]) => hero.classList.toggle('hero-fora', !entrada.isIntersecting)).observe(hero);
  }

  const semMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (semMovimento || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

  let pendente = false;
  let alvoX = 0;
  let alvoY = 0;

  const aplicar = () => {
    pendente = false;
    constelacao.style.setProperty('--px', alvoX.toFixed(3));
    constelacao.style.setProperty('--py', alvoY.toFixed(3));
  };

  hero.addEventListener('pointermove', (evento) => {
    const area = hero.getBoundingClientRect();
    alvoX = ((evento.clientX - area.left) / area.width - 0.5) * -2;
    alvoY = ((evento.clientY - area.top) / area.height - 0.5) * -2;
    if (!pendente) {
      pendente = true;
      requestAnimationFrame(aplicar);
    }
  }, { passive: true });

  hero.addEventListener('pointerleave', () => { alvoX = 0; alvoY = 0; requestAnimationFrame(aplicar); });
})();
