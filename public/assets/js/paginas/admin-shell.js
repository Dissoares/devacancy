/**
 * Devacancy — estrutura do painel admin: menu lateral no celular, botões "Copiar" e interruptores.
 */
(() => {
  'use strict';

  const raiz = document.querySelector('[data-admin]');
  if (!raiz) return;

  const lateral = document.getElementById('admin-lateral');
  const fundo = raiz.querySelector('[data-admin-fechar]');
  const abrirBotao = raiz.querySelector('[data-admin-abrir]');

  const abrir = () => {
    raiz.classList.add('menu-aberto');
    fundo.hidden = false;
    abrirBotao?.setAttribute('aria-expanded', 'true');
    lateral.querySelector('a')?.focus({ preventScroll: true });
  };
  const fechar = () => {
    raiz.classList.remove('menu-aberto');
    fundo.hidden = true;
    abrirBotao?.setAttribute('aria-expanded', 'false');
  };

  abrirBotao?.addEventListener('click', () => (raiz.classList.contains('menu-aberto') ? fechar() : abrir()));
  fundo?.addEventListener('click', fechar);
  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') fechar();
  });
  lateral.addEventListener('click', (evento) => {
    if (evento.target.closest('a')) fechar();
  });

  // Botões "Copiar" (tokens de webhook, endereços).
  document.addEventListener('click', async (evento) => {
    const botao = evento.target.closest('[data-copiar]');
    if (!botao) return;
    const campo = document.querySelector(botao.dataset.copiar);
    if (!campo) return;
    const texto = 'value' in campo ? campo.value : campo.textContent;
    try {
      await navigator.clipboard.writeText(texto);
    } catch {
      const area = document.createElement('textarea');
      area.value = texto;
      document.body.appendChild(area);
      area.select();
      document.execCommand('copy');
      area.remove();
    }
    window.Devacancy?.mostrarAviso(botao.dataset.mensagem ?? 'Copiado!', 'sucesso');
  });

  // Ao salvar um cartão de configuração, o botão mostra que está trabalhando.
  document.addEventListener('submit', (evento) => {
    const botao = evento.target.querySelector?.('[data-salvando]');
    if (!botao) return;
    botao.disabled = true;
    botao.dataset.rotuloOriginal = botao.textContent;
    botao.textContent = 'Salvando…';
  });
})();
