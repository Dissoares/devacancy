/**
 * Devacancy — painel administrativo: gaveta de detalhes e exportação com os filtros atuais.
 */
(() => {
  'use strict';

  const gaveta = document.getElementById('gaveta');
  if (!gaveta) return;

  let elementoAnterior = null;

  const abrir = () => {
    elementoAnterior = document.activeElement;
    gaveta.classList.add('aberta');
    gaveta.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    gaveta.querySelector('.botao-fechar')?.focus();
  };

  const fechar = () => {
    gaveta.classList.remove('aberta');
    gaveta.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    elementoAnterior?.focus?.();
  };

  document.addEventListener('htmx:afterSwap', (evento) => {
    if (evento.detail.target.id === 'gaveta-conteudo') abrir();
  });

  gaveta.addEventListener('click', (evento) => {
    if (evento.target.closest('[data-fechar-gaveta]')) fechar();
  });

  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape' && gaveta.classList.contains('aberta')) fechar();
  });

  // Mantém o link de exportação com os mesmos filtros da tabela.
  const filtros = document.querySelector('.filtros');
  const linkExportar = document.querySelector('[data-exportar]');
  const atualizarExportacao = () => {
    const parametros = new URLSearchParams();
    new FormData(filtros).forEach((valor, chave) => {
      if (chave !== '_token' && String(valor).trim()) parametros.set(chave, valor);
    });
    linkExportar.href = `/admin/usuarios/exportar${parametros.size ? `?${parametros}` : ''}`;
  };
  filtros?.addEventListener('input', atualizarExportacao);
  filtros?.addEventListener('change', atualizarExportacao);
})();
