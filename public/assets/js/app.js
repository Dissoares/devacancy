/**
 * Devacancy — comportamento global: integração com HTMX e utilidades compartilhadas.
 */
(() => {
  'use strict';

  const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  // Todo pedido HTMX leva o token CSRF no cabeçalho.
  document.addEventListener('htmx:configRequest', (evento) => {
    evento.detail.headers['X-CSRF-Token'] = tokenCsrf;
  });

  // Depois de trocar um formulário com erros, leva a pessoa até o primeiro campo com problema.
  document.addEventListener('htmx:afterSwap', (evento) => {
    const primeiroErro = evento.detail.target.querySelector?.('.campo-com-erro');
    if (!primeiroErro) return;
    primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' });
    primeiroErro.querySelector('input:not([type="hidden"]), select, textarea')?.focus({ preventScroll: true });
  });

  // Erros de rede/servidor em requisições HTMX viram um aviso discreto.
  document.addEventListener('htmx:responseError', (evento) => {
    const status = evento.detail.xhr.status;
    const mensagem = status === 403
      ? 'Sua sessão expirou. Atualize a página e tente novamente.'
      : 'Algo deu errado. Tente novamente em instantes.';
    mostrarAviso(mensagem);
  });

  document.addEventListener('htmx:sendError', () => mostrarAviso('Sem conexão. Verifique sua internet.'));

  function mostrarAviso(mensagem, tipo = 'erro') {
    let aviso = document.querySelector('.aviso-flutuante');
    if (!aviso) {
      aviso = document.createElement('div');
      aviso.className = 'aviso-flutuante';
      aviso.setAttribute('role', 'alert');
      document.body.appendChild(aviso);
    }
    aviso.textContent = mensagem;
    aviso.classList.toggle('aviso-sucesso', tipo === 'sucesso');
    aviso.classList.add('visivel');
    clearTimeout(aviso.temporizador);
    aviso.temporizador = setTimeout(() => aviso.classList.remove('visivel'), 4500);
  }

  window.Devacancy = { mostrarAviso };
})();
