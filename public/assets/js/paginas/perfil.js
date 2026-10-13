/**
 * Devacancy — importação de currículo. O PDF é lido AQUI, no navegador (pdf.js, hospedado no próprio site):
 * o servidor recebe só o texto e o PDF original, e responde com a tela de revisão.
 */
(() => {
  'use strict';

  const raiz = document.querySelector('[data-importador]');
  if (!raiz) return;

  const textos = JSON.parse(raiz.dataset.textos || '{}');
  const entrada = raiz.querySelector('[data-importador-entrada]');
  const resultado = document.getElementById('importacao-resultado');
  const estado = raiz.querySelector('[data-importador-estado]');
  const erro = raiz.querySelector('[data-importador-erro]');
  const zona = raiz.querySelector('[data-zona-importar]');
  const seletorArquivo = raiz.querySelector('[data-arquivo-importar]');
  const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const TAMANHO_MAXIMO = 15 * 1024 * 1024;

  let origemAtual = 'pdf';

  // ---------------------------------------------------------------- Abas

  const abas = raiz.querySelectorAll('[data-aba]');
  const mostrarAba = (nome) => {
    origemAtual = nome === 'texto' ? 'pdf' : nome;
    abas.forEach((aba) => {
      const ativa = aba.dataset.aba === nome;
      aba.classList.toggle('ativo', ativa);
      aba.setAttribute('aria-selected', String(ativa));
    });
    raiz.querySelectorAll('[data-painel]').forEach((painel) => { painel.hidden = painel.dataset.painel !== nome; });
    zona.hidden = nome === 'texto';
    erro.innerHTML = '';
  };
  abas.forEach((aba) => aba.addEventListener('click', () => mostrarAba(aba.dataset.aba)));

  // Link direto para a aba do LinkedIn (#importar?linkedin) ou para a seção (#importar).
  if (location.hash === '#importar' && !resultado.children.length) {
    raiz.scrollIntoView({ block: 'start' });
  }

  // ---------------------------------------------------------------- Leitura do PDF

  let carregandoBiblioteca = null;
  const carregarPdfJs = () => {
    if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
    carregandoBiblioteca ??= new Promise((resolver, rejeitar) => {
      const script = document.createElement('script');
      script.src = raiz.dataset.pdfjs;
      script.onload = () => {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = raiz.dataset.worker;
        resolver(window.pdfjsLib);
      };
      script.onerror = () => rejeitar(new Error('pdfjs'));
      document.head.appendChild(script);
    });
    return carregandoBiblioteca;
  };

  /** Junta os pedaços de texto em linhas, na ordem em que o PDF os desenha (preserva cada coluna do LinkedIn). */
  const textoDaPagina = (itens) => {
    const linhas = [];
    let linha = '';
    let yAtual = null;
    let fimAnterior = null;

    for (const item of itens) {
      if (!item.str && !item.hasEOL) continue;
      const y = item.transform[5];
      const x = item.transform[4];
      const altura = Math.abs(item.transform[3]) || 10;

      if (yAtual !== null && Math.abs(y - yAtual) > altura * 0.4) {
        linhas.push(linha);
        linha = '';
        fimAnterior = null;
      }
      if (linha !== '' && fimAnterior !== null && x - fimAnterior > altura * 0.15 && !linha.endsWith(' ') && !item.str.startsWith(' ')) {
        linha += ' ';
      }
      linha += item.str;
      yAtual = y;
      fimAnterior = x + (item.width || 0);
      if (item.hasEOL) {
        linhas.push(linha);
        linha = '';
        yAtual = null;
        fimAnterior = null;
      }
    }
    if (linha !== '') linhas.push(linha);
    return linhas.map((texto) => texto.replace(/\s+/g, ' ').trim()).filter(Boolean).join('\n');
  };

  const lerPdf = async (arquivo) => {
    const pdfjs = await carregarPdfJs();
    const documento = await pdfjs.getDocument({ data: await arquivo.arrayBuffer() }).promise;
    const paginas = [];
    for (let numero = 1; numero <= Math.min(documento.numPages, 12); numero++) {
      const pagina = await documento.getPage(numero);
      paginas.push(textoDaPagina((await pagina.getTextContent()).items));
    }
    return paginas.join('\n');
  };

  // ---------------------------------------------------------------- Envio

  const mostrarEstado = (mensagem) => {
    estado.hidden = mensagem === null;
    if (mensagem !== null) raiz.querySelector('[data-estado-texto]').textContent = mensagem;
  };

  const mostrarErro = (html) => { erro.innerHTML = html; };
  const erroSimples = (mensagem) => {
    const aviso = document.createElement('div');
    aviso.className = 'alerta alerta-erro';
    aviso.setAttribute('role', 'alert');
    aviso.textContent = mensagem;
    erro.replaceChildren(aviso);
  };

  const enviar = async (texto, arquivo) => {
    const dados = new FormData();
    dados.set('texto', texto);
    dados.set('origem', origemAtual);
    if (arquivo) dados.set('curriculo', arquivo, arquivo.name);

    mostrarEstado(textos.enviando);
    try {
      const resposta = await fetch('/perfil/importar', { method: 'POST', body: dados, headers: { 'X-CSRF-Token': tokenCsrf } });
      const html = await resposta.text();
      if (!resposta.ok) {
        mostrarErro(html);
        return;
      }
      resultado.innerHTML = html;
      entrada.hidden = true;
      erro.innerHTML = '';
      resultado.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch {
      erroSimples(window.TEXTOS?.sem_conexao ?? '');
    } finally {
      mostrarEstado(null);
    }
  };

  const processarArquivo = async (arquivo) => {
    erro.innerHTML = '';
    if (!arquivo) return;
    if (!/\.pdf$/i.test(arquivo.name) && arquivo.type !== 'application/pdf') {
      erroSimples(textos.so_pdf);
      return;
    }
    if (arquivo.size > TAMANHO_MAXIMO) {
      erroSimples(textos.grande);
      return;
    }

    mostrarEstado(textos.lendo);
    try {
      const texto = await lerPdf(arquivo);
      if (texto.replace(/\s/g, '').length < 100) {
        mostrarEstado(null);
        erroSimples(textos.sem_texto);
        return;
      }
      await enviar(texto, arquivo);
    } catch {
      mostrarEstado(null);
      erroSimples(textos.erro_leitura);
    }
  };

  seletorArquivo.addEventListener('change', () => {
    processarArquivo(seletorArquivo.files[0]);
    seletorArquivo.value = '';
  });

  ['dragenter', 'dragover'].forEach((nome) => zona.addEventListener(nome, (evento) => {
    evento.preventDefault();
    zona.classList.add('arrastando');
  }));
  ['dragleave', 'drop'].forEach((nome) => zona.addEventListener(nome, () => zona.classList.remove('arrastando')));
  zona.addEventListener('drop', (evento) => {
    evento.preventDefault();
    processarArquivo(evento.dataTransfer?.files?.[0]);
  });

  // Texto colado (para quem prefere copiar do LinkedIn ou do Word).
  raiz.querySelector('[data-enviar-texto]')?.addEventListener('click', () => {
    const texto = raiz.querySelector('[data-texto-colado]').value;
    if (texto.trim().length < 100) {
      erroSimples(textos.sem_texto);
      return;
    }
    erro.innerHTML = '';
    enviar(texto, null);
  });

  // ---------------------------------------------------------------- Revisão

  document.addEventListener('click', (evento) => {
    const botao = evento.target.closest('[data-marcar]');
    if (!botao) return;
    botao.closest('[data-revisao]').querySelectorAll('input[name="campos[]"]').forEach((caixa) => {
      caixa.checked = botao.dataset.marcar === 'todos';
    });
  });
})();
