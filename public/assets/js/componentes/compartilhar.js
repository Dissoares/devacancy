/**
 * Botão único de compartilhar. Celular/tablet: folha de compartilhamento nativa (Web Share API), que já lista
 * os apps instalados. Computador: menu com as redes mais usadas e "copiar link".
 * Marcação: <button data-compartilhar data-url data-titulo data-texto>.
 */
(() => {
  'use strict';

  const textos = window.TEXTOS ?? {};
  let menu = null;
  let botaoAtivo = null;

  const redes = (url, texto) => {
    const u = encodeURIComponent(url);
    const t = encodeURIComponent(texto);
    return [
      { nome: 'WhatsApp', href: `https://wa.me/?text=${encodeURIComponent(`${texto} ${url}`)}`, cor: '#25d366' },
      { nome: 'Telegram', href: `https://t.me/share/url?url=${u}&text=${t}`, cor: '#229ed9' },
      { nome: 'X', href: `https://twitter.com/intent/tweet?text=${t}&url=${u}`, cor: '#111827' },
      { nome: 'Facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${u}`, cor: '#1877f2' },
      { nome: 'LinkedIn', href: `https://www.linkedin.com/sharing/share-offsite/?url=${u}`, cor: '#0a66c2' },
      { nome: 'E-mail', href: `mailto:?subject=${t}&body=${encodeURIComponent(`${texto}\n${url}`)}`, cor: '#64748b' },
    ];
  };

  const ehToque = () => window.matchMedia('(pointer: coarse)').matches;

  document.addEventListener('click', async (evento) => {
    const botao = evento.target.closest('[data-compartilhar]');
    if (!botao) {
      if (menu && !menu.contains(evento.target)) fechar();
      return;
    }
    evento.preventDefault();
    evento.stopPropagation();

    const { url, titulo, texto } = botao.dataset;

    if (navigator.share && ehToque()) {
      try {
        await navigator.share({ title: titulo, text: texto, url });
      } catch (erro) {
        if (erro?.name !== 'AbortError') abrirMenu(botao, url, texto);
      }
      return;
    }

    if (menu && botaoAtivo === botao) {
      fechar();
      return;
    }
    abrirMenu(botao, url, texto);
  });

  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') fechar();
  });
  window.addEventListener('resize', fechar);

  function abrirMenu(botao, url, texto) {
    fechar();
    botaoAtivo = botao;

    menu = document.createElement('div');
    menu.className = 'menu-compartilhar';
    menu.setAttribute('role', 'menu');
    menu.setAttribute('aria-label', textos.compartilhar_titulo ?? 'Compartilhar');

    redes(url, texto).forEach((rede) => {
      const item = document.createElement('a');
      item.className = 'menu-compartilhar-item';
      item.href = rede.href;
      item.target = '_blank';
      item.rel = 'noopener noreferrer';
      item.setAttribute('role', 'menuitem');
      const ponto = document.createElement('span');
      ponto.className = 'menu-compartilhar-ponto';
      ponto.style.background = rede.cor;
      item.append(ponto, rede.nome);
      item.addEventListener('click', () => setTimeout(fechar, 50));
      menu.appendChild(item);
    });

    const copiar = document.createElement('button');
    copiar.type = 'button';
    copiar.className = 'menu-compartilhar-item menu-compartilhar-copiar';
    copiar.setAttribute('role', 'menuitem');
    copiar.textContent = textos.compartilhar_copiar ?? 'Copiar link';
    copiar.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(url);
      } catch {
        const campo = document.createElement('textarea');
        campo.value = url;
        document.body.appendChild(campo);
        campo.select();
        document.execCommand('copy');
        campo.remove();
      }
      copiar.textContent = textos.compartilhar_copiado ?? 'Link copiado!';
      copiar.classList.add('copiado');
      setTimeout(fechar, 1200);
    });
    menu.appendChild(copiar);

    document.body.appendChild(menu);
    posicionar(botao);
    botao.setAttribute('aria-expanded', 'true');
    menu.querySelector('a, button')?.focus({ preventScroll: true });
  }

  /** Abre abaixo do botão, alinhado à direita dele, sem sair da tela. */
  function posicionar(botao) {
    const caixa = botao.getBoundingClientRect();
    const largura = menu.offsetWidth;
    const altura = menu.offsetHeight;
    let esquerda = Math.min(Math.max(8, caixa.right - largura), window.innerWidth - largura - 8);
    let topo = caixa.bottom + 8;
    if (topo + altura > window.innerHeight - 8) topo = Math.max(8, caixa.top - altura - 8);
    menu.style.left = `${esquerda + window.scrollX}px`;
    menu.style.top = `${topo + window.scrollY}px`;
  }

  function fechar() {
    menu?.remove();
    botaoAtivo?.removeAttribute('aria-expanded');
    menu = null;
    botaoAtivo = null;
  }
})();
