/**
 * Devacancy — questionário do funil: mostra uma pergunta por vez, avança sozinho nas escolhas únicas
 * e só deixa continuar quando a pergunta obrigatória foi respondida. Sem JS, o formulário aparece inteiro.
 */
(() => {
  'use strict';

  const formulario = document.querySelector('[data-questionario]');
  if (!formulario) return;

  const perguntas = [...formulario.querySelectorAll('[data-pergunta]')];
  const progresso = formulario.querySelector('[data-progresso-questionario]');
  const botaoVoltar = formulario.querySelector('[data-voltar]');
  const botaoContinuar = formulario.querySelector('[data-continuar]');
  const botaoEnviar = formulario.querySelector('[data-enviar]');
  const reduzido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let atual = Math.min(Number(formulario.dataset.passoInicial) || 0, perguntas.length - 1);

  const ultima = () => atual === perguntas.length - 1;

  function mostrar(indice) {
    const direcao = indice >= atual ? 'entra-direita' : 'entra-esquerda';
    atual = indice;
    perguntas.forEach((pergunta, posicao) => {
      pergunta.classList.toggle('ativa', posicao === indice);
      pergunta.classList.remove('entra-direita', 'entra-esquerda');
      if (posicao === indice) pergunta.classList.add(direcao);
      pergunta.setAttribute('aria-hidden', String(posicao !== indice));
    });
    progresso.style.width = `${((indice + 1) / perguntas.length) * 100}%`;
    botaoVoltar.disabled = indice === 0;
    botaoContinuar.hidden = ultima();
    botaoEnviar.hidden = !ultima();
    window.scrollTo({ top: 0, behavior: reduzido ? 'auto' : 'smooth' });

    const campo = perguntas[indice].querySelector('input:checked, .entrada-tags input, input.entrada');
    if (campo && window.matchMedia('(pointer: fine)').matches) campo.focus({ preventScroll: true });
  }

  /** Radios obrigatórios precisam de uma escolha; grupos de checkbox, de pelo menos uma; a stack, de uma tag. */
  function respondida(pergunta) {
    const obrigatorio = pergunta.querySelector('[data-obrigatorio]');
    if (obrigatorio) return obrigatorio.value.trim() !== '';

    const radios = pergunta.querySelectorAll('input[type="radio"][required]');
    if (radios.length) return [...radios].some((radio) => radio.checked);

    const caixas = pergunta.querySelectorAll('input[type="checkbox"]');
    if (caixas.length) return [...caixas].some((caixa) => caixa.checked);

    return true;
  }

  function avisar(pergunta) {
    pergunta.classList.remove('tremer');
    void pergunta.offsetWidth;
    pergunta.classList.add('tremer');
    window.Devacancy?.mostrarAviso(formulario.dataset.mensagemObrigatorio);
  }

  function avancar() {
    const pergunta = perguntas[atual];
    if (!respondida(pergunta)) {
      avisar(pergunta);
      return;
    }
    if (ultima()) {
      formulario.requestSubmit(botaoEnviar);
      return;
    }
    mostrar(atual + 1);
    comemorar();
  }

  // ---------- Perfil montando ao vivo ----------
  const perfilVivo = document.querySelector('[data-perfil-vivo]');
  const comemoracao = formulario.querySelector('[data-comemoracao]');
  let percentual = 0;

  /** Texto das opções marcadas de uma pergunta (o rótulo visível), ou o valor digitado. */
  function respostaDe(campo) {
    const pergunta = formulario.querySelector(`[data-pergunta="${campo}"]`);
    if (!pergunta) return [];
    const oculto = pergunta.querySelector('input[type="hidden"][name]');
    if (oculto) return oculto.value.split(',').map((valor) => valor.trim()).filter(Boolean);
    const texto = pergunta.querySelector('input.entrada');
    if (texto) return texto.value.trim() ? [texto.value.trim()] : [];
    return [...pergunta.querySelectorAll('input:checked')]
      .filter((entrada) => entrada.value !== '')
      .map((entrada) => (entrada.closest('label')?.querySelector('strong') ?? entrada.closest('label'))?.textContent.trim())
      .filter(Boolean);
  }

  function atualizarPerfil() {
    if (!perfilVivo) return;
    const linhas = [...perfilVivo.querySelectorAll('[data-perfil-linha]')];
    let preenchidas = 0;
    linhas.forEach((linha) => {
      const valores = respostaDe(linha.dataset.perfilLinha);
      const alvo = linha.querySelector('[data-perfil-valor]');
      const novo = valores.join('|');
      if (valores.length) preenchidas++;
      if (alvo.dataset.atual === novo) return;
      alvo.dataset.atual = novo;
      alvo.replaceChildren();
      if (!valores.length) {
        alvo.textContent = '—';
      } else if (linha.dataset.perfilLinha === 'stack') {
        valores.slice(0, 6).forEach((valor) => {
          const chip = document.createElement('span');
          chip.className = 'etiqueta';
          chip.textContent = valor;
          const logo = window.Devacancy?.iconeStack?.(valor);
          if (logo) chip.prepend(logo);
          alvo.appendChild(chip);
        });
      } else {
        alvo.textContent = valores.join(', ');
      }
      linha.classList.toggle('preenchida', valores.length > 0);
      linha.classList.remove('piscar');
      void linha.offsetWidth;
      if (valores.length) linha.classList.add('piscar');
    });
    percentual = Math.round((preenchidas / linhas.length) * 100);
    perfilVivo.querySelector('[data-perfil-anel]').style.setProperty('--valor', `${percentual}%`);
    perfilVivo.querySelector('[data-perfil-percentual]').textContent = `${percentual}%`;
  }

  /** "Perfil 40% pronto" aparece rapidinho ao avançar. */
  function comemorar() {
    if (!comemoracao || percentual === 0) return;
    comemoracao.textContent = comemoracao.dataset.texto.replace(':n', percentual);
    comemoracao.classList.remove('visivel');
    void comemoracao.offsetWidth;
    comemoracao.classList.add('visivel');
  }

  formulario.addEventListener('change', atualizarPerfil);
  formulario.addEventListener('input', atualizarPerfil);
  atualizarPerfil();
  formulario.classList.add('em-passos');
  botaoContinuar.addEventListener('click', avancar);
  botaoVoltar.addEventListener('click', () => atual > 0 && mostrar(atual - 1));

  // Escolha única: avança sozinho depois de um instante, para a pessoa ver a opção marcada.
  perguntas.filter((pergunta) => pergunta.hasAttribute('data-avancar-ao-escolher')).forEach((pergunta) => {
    pergunta.addEventListener('change', (evento) => {
      if (evento.target.type !== 'radio') return;
      setTimeout(avancar, 260);
    });
  });

  // Enter no campo de cidade avança em vez de enviar o formulário.
  formulario.addEventListener('keydown', (evento) => {
    if (evento.key !== 'Enter' || !evento.target.matches('input.entrada')) return;
    evento.preventDefault();
    avancar();
  });

  formulario.addEventListener('submit', (evento) => {
    const pendente = perguntas.findIndex((pergunta) => !respondida(pergunta));
    if (pendente === -1) return;
    evento.preventDefault();
    mostrar(pendente);
    avisar(perguntas[pendente]);
  });

  mostrar(atual);
})();
