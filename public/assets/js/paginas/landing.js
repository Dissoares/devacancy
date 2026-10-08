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
    botaoMenu.setAttribute('aria-label', abrir ? 'Fechar menu' : 'Abrir menu');
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
