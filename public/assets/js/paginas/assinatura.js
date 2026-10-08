/**
 * Devacancy — funil de assinatura: animação de análise do perfil, máscara de CPF e botão de copiar o PIX.
 */
(() => {
  'use strict';

  // Tela "vagas compatíveis": marca as etapas da análise uma a uma e então revela o resultado.
  const analise = document.querySelector('[data-analise]');
  const resultado = document.querySelector('[data-resultado]');
  if (analise && resultado) {
    const etapas = [...analise.querySelectorAll('li')];
    const reduzido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const intervalo = reduzido ? 0 : 900;

    // O CSS esconde o resultado enquanto <html> tem a classe "js" e ainda não tem "analise-concluida".
    etapas.forEach((etapa, indice) => setTimeout(() => etapa.classList.add('feito'), intervalo * (indice + 1)));
    setTimeout(() => document.documentElement.classList.add('analise-concluida'), intervalo * (etapas.length + 1));
  }

  // 000.000.000-00 enquanto digita.
  document.addEventListener('input', (evento) => {
    const campo = evento.target.closest?.('[data-mascara-cpf]');
    if (!campo) return;
    const digitos = campo.value.replace(/\D/g, '').slice(0, 11);
    campo.value = digitos
      .replace(/^(\d{3})(\d)/, '$1.$2')
      .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
      .replace(/\.(\d{3})(\d)/, '.$1-$2');
  });

  document.addEventListener('click', async (evento) => {
    const botao = evento.target.closest('[data-copiar]');
    if (!botao) return;
    const campo = document.querySelector(botao.dataset.copiar);
    try {
      await navigator.clipboard.writeText(campo.value);
    } catch {
      campo.select();
      document.execCommand('copy');
    }
    window.Devacancy?.mostrarAviso('Código PIX copiado.', 'sucesso');
  });
})();
