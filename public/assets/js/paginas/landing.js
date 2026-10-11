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
 * Partículas do hero: os logos das tecnologias à deriva, sem moldura, desenhados num canvas.
 *  - Profundidade: os de trás são menores, mais claros e levemente desfocados; os da frente, maiores e mais nítidos.
 *  - Interação: o dedo/mouse afasta os logos (eles voltam ao fluxo sozinhos); um toque/clique faz uma onda que os espalha;
 *    rolar a página move cada camada numa velocidade diferente (efeito de profundidade).
 *  - Cuidados: pausa fora da tela e com a aba escondida, limita a resolução, e com "reduzir movimento" desenha parado.
 */
(() => {
  'use strict';

  const hero = document.querySelector('.hero');
  const canvas = hero?.querySelector('[data-particulas]');
  if (!canvas || !canvas.getContext) return;

  const ctx = canvas.getContext('2d');
  const semMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const urls = JSON.parse(canvas.dataset.logos || '[]');
  if (!urls.length) return;

  const CAMADAS = [
    { tamanho: 0.62, alfa: 0.34, velocidade: 0.55, desfoque: 1.2, rolagem: 0.10 },
    { tamanho: 0.85, alfa: 0.55, velocidade: 0.85, desfoque: 0.4, rolagem: 0.22 },
    { tamanho: 1.15, alfa: 0.92, velocidade: 1.2, desfoque: 0, rolagem: 0.38 },
  ];

  let largura = 0;
  let altura = 0;
  let escala = 1;
  let particulas = [];
  let sprites = [];
  let imagens = [];
  let rodando = false;
  let visivel = true;
  let ultimo = 0;
  let rolagemAnterior = window.scrollY;
  const ponteiro = { x: -9999, y: -9999, ativo: false };
  const ondas = [];

  const celular = () => window.innerWidth <= 900;
  const rand = (a, b) => a + Math.random() * (b - a);

  /** Os SVGs não trazem width/height; sem isso o Firefox não desenha no canvas. Injeta o tamanho e carrega. */
  async function carregar(url) {
    try {
      const texto = (await (await fetch(url)).text()).replace('<svg ', '<svg width="128" height="128" ');
      const endereco = URL.createObjectURL(new Blob([texto], { type: 'image/svg+xml' }));
      const imagem = new Image();
      await new Promise((ok, erro) => { imagem.onload = ok; imagem.onerror = erro; imagem.src = endereco; });
      return imagem;
    } catch {
      return null;
    }
  }

  /** Cada logo é pré-desenhado num "sprite" com sombra suave; no loop só se copia a imagem (barato). */
  function montarSprite(imagem, lado) {
    const margem = Math.ceil(lado * 0.22);
    const folha = document.createElement('canvas');
    folha.width = folha.height = Math.ceil((lado + margem * 2) * escala);
    const c = folha.getContext('2d');
    c.scale(escala, escala);
    c.shadowColor = 'rgba(30, 58, 138, 0.28)';
    c.shadowBlur = lado * 0.22;
    c.shadowOffsetY = lado * 0.06;
    c.drawImage(imagem, margem, margem, lado, lado);

    const suave = document.createElement('canvas');
    suave.width = folha.width;
    suave.height = folha.height;
    const cs = suave.getContext('2d');
    cs.filter = 'blur(' + (1.3 * escala) + 'px)';
    cs.drawImage(folha, 0, 0);
    return { folha, suave, lado, margem };
  }

  function criar() {
    const quantidade = celular() ? 26 : 32;
    const base = celular() ? 30 : 38;
    particulas = Array.from({ length: quantidade }, (_, i) => {
      const camada = CAMADAS[i % 3];
      const lado = Math.round(base * camada.tamanho * rand(0.85, 1.2));
      const angulo = rand(0, Math.PI * 2);
      const vel = rand(5, 13) * camada.velocidade;
      return {
        i: i % sprites.length,
        camada,
        lado,
        x: rand(0, largura),
        // no celular o hero é bem comprido: a maioria começa na primeira tela, onde a pessoa está olhando
        y: celular() && i % 4 !== 0 ? rand(0, Math.min(altura, 900)) : rand(0, altura),
        bx: Math.cos(angulo) * vel,
        by: Math.sin(angulo) * vel * 0.7 - 2,
        vx: 0,
        vy: 0,
        angulo: 0,
        mostrar: 1,
        fase: rand(0, 6.28),
        giro: rand(0.3, 0.9),
      };
    });
  }

  /** Áreas com texto: os logos desbotam e são empurrados pra fora delas, pra nunca atrapalhar a leitura. */
  let zonas = [];
  let medidoEm = 0;

  function linhasDe(elemento, folga) {
    const origem = hero.getBoundingClientRect();
    const alcance = document.createRange();
    alcance.selectNodeContents(elemento);
    return [...alcance.getClientRects()]
      .filter((r) => r.width > 4 && r.height > 4)
      .map((r) => ({ e: r.left - origem.left - folga, t: r.top - origem.top - folga, d: r.right - origem.left + folga, b: r.bottom - origem.top + folga }));
  }

  function medirZonas() {
    medidoEm = performance.now();
    const origem = hero.getBoundingClientRect();
    const lista = [{ e: 0, t: 0, d: largura, b: 78 }]; // faixa do menu (no topo da página; ao rolar, o menu ganha fundo)
    for (const seletor of ['.hero-titulo', '.hero-subtitulo']) {
      const el = hero.querySelector(seletor);
      if (el) lista.push(...linhasDe(el, 12));
    }
    for (const seletor of ['.hero-anuncio', '.hero-confianca']) {
      const el = hero.querySelector(seletor);
      if (el) {
        const r = el.getBoundingClientRect();
        lista.push({ e: r.left - origem.left - 10, t: r.top - origem.top - 10, d: r.right - origem.left + 10, b: r.bottom - origem.top + 10 });
      }
    }
    zonas = lista;
  }

  /** Distância até a zona de texto mais próxima (0 = dentro) e para onde sair dela. */
  function zonaMaisProxima(x, y) {
    let melhor = { dist: Infinity, sx: 0, sy: 0 };
    for (const z of zonas) {
      const dx = Math.max(z.e - x, 0, x - z.d);
      const dy = Math.max(z.t - y, 0, y - z.b);
      const dist = Math.hypot(dx, dy);
      if (dist < melhor.dist) {
        let sx = 0;
        let sy = 0;
        if (dist === 0) {
          const saidas = [[x - z.e, -1, 0], [z.d - x, 1, 0], [y - z.t, 0, -1], [z.b - y, 0, 1]].sort((a, b) => a[0] - b[0])[0];
          sx = saidas[1];
          sy = saidas[2];
        } else {
          sx = dx ? Math.sign(x - (x < z.e ? z.e : z.d)) : 0;
          sy = dy ? Math.sign(y - (y < z.t ? z.t : z.b)) : 0;
        }
        melhor = { dist, sx, sy };
      }
    }
    return melhor;
  }

  function medir() {
    const r = hero.getBoundingClientRect();
    largura = Math.max(1, r.width);
    altura = Math.max(1, r.height);
    escala = Math.min(window.devicePixelRatio || 1, celular() ? 1.5 : 2);
    canvas.width = Math.round(largura * escala);
    canvas.height = Math.round(altura * escala);
    ctx.setTransform(escala, 0, 0, escala, 0, 0);
    medirZonas();
  }

  function atualizar(dt, agora) {
    if (agora - medidoEm > 1200) medirZonas();
    const raio = celular() ? 90 : 130;
    const rolou = window.scrollY - rolagemAnterior;
    rolagemAnterior = window.scrollY;

    for (const p of particulas) {
      // volta suavemente para a velocidade própria de deriva
      p.vx += (p.bx - p.vx) * Math.min(1, dt * 1.6);
      p.vy += (p.by - p.vy) * Math.min(1, dt * 1.6);

      if (ponteiro.ativo) {
        const dx = p.x - ponteiro.x;
        const dy = p.y - ponteiro.y;
        const d2 = dx * dx + dy * dy;
        if (d2 < raio * raio && d2 > 1) {
          const d = Math.sqrt(d2);
          const forca = (1 - d / raio) * 520;
          p.vx += (dx / d) * forca * dt;
          p.vy += (dy / d) * forca * dt;
        }
      }

      for (const onda of ondas) {
        const dx = p.x - onda.x;
        const dy = p.y - onda.y;
        const d = Math.hypot(dx, dy) || 1;
        const frente = Math.abs(d - onda.raio);
        if (frente < 70) {
          const f = (1 - frente / 70) * onda.forca * dt;
          p.vx += (dx / d) * f;
          p.vy += (dy / d) * f;
        }
      }

      const zona = zonaMaisProxima(p.x, p.y);
      // No celular o texto ocupa a largura toda: atrás dele os logos ficam fracos (não somem), pra a tela não ficar vazia.
      const piso = celular() ? 0.2 : 0.05;
      const alvo = piso + (1 - piso) * Math.min(1, zona.dist / 70);
      p.mostrar += (alvo - p.mostrar) * Math.min(1, dt * 5);
      if (zona.dist < 40) {
        const empurrao = (1 - zona.dist / 40) * 70;
        p.vx += zona.sx * empurrao * dt;
        p.vy += zona.sy * empurrao * dt;
      }

      p.x += p.vx * dt;
      p.y += p.vy * dt - rolou * p.camada.rolagem;

      const folga = p.lado;
      if (p.x < -folga) p.x = largura + folga;
      else if (p.x > largura + folga) p.x = -folga;
      if (p.y < -folga) p.y = altura + folga;
      else if (p.y > altura + folga) p.y = -folga;
      p.angulo = Math.sin(agora * 0.0006 * p.giro + p.fase) * 0.22;
    }

    for (let k = ondas.length - 1; k >= 0; k--) {
      const onda = ondas[k];
      onda.raio += 520 * dt;
      onda.forca *= Math.pow(0.04, dt);
      if (onda.raio > 700 || onda.forca < 8) ondas.splice(k, 1);
    }
  }

  function desenhar() {
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // O hero do celular é comprido: só se desenha o que está (ou está quase) na tela.
    const topo = -hero.getBoundingClientRect().top - 90;
    const base = topo + window.innerHeight + 180;

    for (const p of particulas) {
      if (p.y < topo || p.y > base) continue;
      const alfa = p.camada.alfa * p.mostrar;
      if (alfa < 0.02) continue;
      const sprite = sprites[p.i];
      if (!sprite) continue;
      const tam = p.lado + sprite.margem * 2 * (p.lado / sprite.lado);
      const c = Math.cos(p.angulo) * escala;
      const sn = Math.sin(p.angulo) * escala;
      ctx.setTransform(c, sn, -sn, c, p.x * escala, p.y * escala);
      ctx.globalAlpha = alfa;
      ctx.drawImage(p.camada.desfoque > 1 ? sprite.suave : sprite.folha, -tam / 2, -tam / 2, tam, tam);
    }
  }

  let alternar = false;

  function quadro(agora) {
    if (!rodando) return;
    alternar = !alternar;
    if (celular() && alternar) {
      requestAnimationFrame(quadro);
      return;
    }
    const dt = Math.min(0.05, (agora - ultimo) / 1000 || 0.016);
    ultimo = agora;
    atualizar(dt, agora);
    desenhar();
    requestAnimationFrame(quadro);
  }

  function iniciar() {
    if (rodando || semMovimento || !visivel || document.hidden || !particulas.length) return;
    rodando = true;
    ultimo = performance.now();
    requestAnimationFrame(quadro);
  }

  const parar = () => { rodando = false; };

  // ---------- Interação ----------
  const dentroDeControle = (alvo) => alvo instanceof Element && alvo.closest('a, button, input, select, textarea, label, summary');

  function posicao(evento) {
    const r = hero.getBoundingClientRect();
    return { x: evento.clientX - r.left, y: evento.clientY - r.top };
  }

  hero.addEventListener('pointermove', (e) => {
    if (e.pointerType === 'touch') return;
    Object.assign(ponteiro, posicao(e), { ativo: true });
  }, { passive: true });
  hero.addEventListener('pointerleave', () => { ponteiro.ativo = false; });

  // Toque: o dedo empurra os logos enquanto desliza; levantar o dedo solta.
  const noToque = (e) => {
    const toque = e.touches[0];
    if (toque) Object.assign(ponteiro, posicao(toque), { ativo: true });
  };
  hero.addEventListener('touchstart', noToque, { passive: true });
  hero.addEventListener('touchmove', noToque, { passive: true });
  hero.addEventListener('touchend', () => { setTimeout(() => { ponteiro.ativo = false; }, 250); }, { passive: true });
  hero.addEventListener('touchcancel', () => { ponteiro.ativo = false; }, { passive: true });

  // Toque/clique em área vazia: onda que espalha os logos.
  hero.addEventListener('pointerdown', (e) => {
    if (dentroDeControle(e.target)) return;
    const { x, y } = posicao(e);
    ondas.push({ x, y, raio: 0, forca: 2600 });
  }, { passive: true });

  // ---------- Ciclo de vida ----------
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(([entrada]) => {
      visivel = entrada.isIntersecting;
      hero.classList.toggle('hero-fora', !visivel);
      if (visivel) iniciar(); else parar();
    }).observe(hero);
  }
  document.addEventListener('visibilitychange', () => (document.hidden ? parar() : iniciar()));

  function refazer() {
    sprites = imagens.map((imagem) => montarSprite(imagem, celular() ? 44 : 60));
    criar();
    if (semMovimento) { atualizar(0, 0); desenhar(); }
  }

  let temporizadorTamanho = null;
  window.addEventListener('resize', () => {
    clearTimeout(temporizadorTamanho);
    temporizadorTamanho = setTimeout(() => { medir(); if (imagens.length) refazer(); }, 150);
  });

  medir();
  if (document.fonts?.ready) document.fonts.ready.then(medirZonas);
  Promise.all(urls.map(carregar)).then((lista) => {
    imagens = lista.filter(Boolean);
    if (!imagens.length) return;
    refazer();
    canvas.classList.add('pronto');
    iniciar();
  });
})();
