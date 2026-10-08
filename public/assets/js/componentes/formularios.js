/**
 * Devacancy — componentes de formulário.
 * Cada componente é ligado via atributo data-* e reativado quando o HTMX troca um trecho da página.
 */
(() => {
  'use strict';

  const SUGESTOES_STACK = [
    'JavaScript', 'TypeScript', 'React', 'Next.js', 'Vue', 'Angular', 'Node.js', 'PHP', 'Laravel',
    'Python', 'Django', 'FastAPI', 'Java', 'Spring Boot', 'C#', '.NET', 'Go', 'Ruby on Rails', 'Kotlin',
    'Swift', 'Flutter', 'React Native', 'SQL', 'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Docker',
    'Kubernetes', 'AWS', 'Azure', 'GCP', 'Git', 'GraphQL', 'Tailwind CSS',
  ];

  const icone = (nome) => `<svg class="icone" aria-hidden="true"><use href="#i-${nome}"/></svg>`;

  // ---------- Tipo de conta (profissional / empresa) ----------
  function iniciarSeletorDeTipo(raiz) {
    raiz.querySelectorAll('[data-formulario-tipo]').forEach((formulario) => {
      const aplicar = (tipo) => {
        document.body.dataset.tipo = tipo;
        formulario.querySelectorAll('[data-provedor]').forEach((link) => {
          const url = new URL(link.href, location.origin);
          url.searchParams.set('tipo', tipo);
          link.href = url.pathname + url.search;
          link.hidden = link.dataset.somente !== undefined && link.dataset.somente !== tipo;
        });
        const endereco = new URL(location.href);
        endereco.searchParams.set('tipo', tipo);
        history.replaceState(null, '', endereco.pathname + endereco.search);
      };

      formulario.querySelectorAll('input[name="tipo"]').forEach((opcao) => {
        opcao.addEventListener('change', () => aplicar(opcao.value));
      });
      aplicar(formulario.querySelector('input[name="tipo"]:checked')?.value ?? 'profissional');
    });
  }

  // ---------- Mostrar / ocultar senha ----------
  function iniciarAlternarSenha(raiz) {
    raiz.querySelectorAll('[data-alternar-senha]').forEach((botao) => {
      const campo = botao.parentElement.querySelector('input');
      botao.addEventListener('click', () => {
        const mostrar = campo.type === 'password';
        campo.type = mostrar ? 'text' : 'password';
        botao.innerHTML = icone(mostrar ? 'olho-fechado' : 'olho');
        botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
      });
    });
  }

  // ---------- Força da senha ----------
  function iniciarForcaSenha(raiz) {
    raiz.querySelectorAll('[data-forca-senha-alvo]').forEach((campo) => {
      const medidor = campo.closest('.campo')?.querySelector('[data-forca-senha]');
      if (!medidor) return;
      campo.addEventListener('input', () => {
        const senha = campo.value;
        let nivel = 0;
        if (senha.length >= 8) nivel++;
        if (senha.length >= 12) nivel++;
        if (/[A-Z]/.test(senha) && /[a-z]/.test(senha)) nivel++;
        if (/\d/.test(senha) && /[^A-Za-z0-9]/.test(senha)) nivel++;
        medidor.dataset.nivel = senha ? String(Math.max(1, nivel)) : '0';
      });
    });
  }

  // ---------- Campo de tags (stack) ----------
  function iniciarCampoTags(raiz) {
    raiz.querySelectorAll('[data-campo-tags]').forEach((componente) => {
      const oculto = componente.querySelector('input[type="hidden"]');
      const caixa = componente.querySelector('.entrada-tags');
      const digitacao = caixa.querySelector('input');
      const areaSugestoes = componente.querySelector('.sugestoes-tags');
      const maximo = 20;

      let tags = oculto.value.split(',').map((tag) => tag.trim()).filter(Boolean);

      const existe = (valor) => tags.some((tag) => tag.toLowerCase() === valor.toLowerCase());

      const sincronizar = () => {
        oculto.value = tags.join(', ');
        caixa.querySelectorAll('.tag').forEach((tag) => tag.remove());
        tags.forEach((valor, indice) => {
          const tag = document.createElement('span');
          tag.className = 'tag';
          tag.textContent = valor;
          const remover = document.createElement('button');
          remover.type = 'button';
          remover.setAttribute('aria-label', `Remover ${valor}`);
          remover.innerHTML = icone('x');
          remover.addEventListener('click', () => {
            tags.splice(indice, 1);
            sincronizar();
            digitacao.focus();
          });
          tag.appendChild(remover);
          caixa.insertBefore(tag, digitacao);
        });
        digitacao.placeholder = tags.length ? 'Adicionar mais…' : digitacao.dataset.placeholder;
        filtrarSugestoes();
      };

      const adicionar = (valor) => {
        valor = valor.replace(/,/g, '').trim().slice(0, 40);
        if (!valor || existe(valor) || tags.length >= maximo) return;
        tags.push(valor);
        sincronizar();
      };

      const filtrarSugestoes = () => {
        if (!areaSugestoes) return;
        const termo = digitacao.value.trim().toLowerCase();
        let visiveis = 0;
        areaSugestoes.querySelectorAll('.sugestao-tag').forEach((botao) => {
          const valor = botao.textContent;
          const mostrar = !existe(valor) && (!termo || valor.toLowerCase().includes(termo)) && visiveis < 14;
          botao.hidden = !mostrar;
          if (mostrar) visiveis++;
        });
      };

      if (areaSugestoes) {
        SUGESTOES_STACK.forEach((valor) => {
          const botao = document.createElement('button');
          botao.type = 'button';
          botao.className = 'sugestao-tag';
          botao.textContent = valor;
          botao.addEventListener('click', () => {
            adicionar(valor);
            digitacao.value = '';
            filtrarSugestoes();
          });
          areaSugestoes.appendChild(botao);
        });
      }

      digitacao.dataset.placeholder = digitacao.placeholder;
      caixa.addEventListener('click', () => digitacao.focus());
      digitacao.addEventListener('input', filtrarSugestoes);
      digitacao.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter' || evento.key === ',' || evento.key === 'Tab') {
          if (!digitacao.value.trim()) return;
          evento.preventDefault();
          adicionar(digitacao.value);
          digitacao.value = '';
          filtrarSugestoes();
        } else if (evento.key === 'Backspace' && !digitacao.value && tags.length) {
          tags.pop();
          sincronizar();
        }
      });
      digitacao.addEventListener('blur', () => {
        if (digitacao.value.trim()) {
          adicionar(digitacao.value);
          digitacao.value = '';
        }
      });

      sincronizar();
    });
  }

  // ---------- Upload de currículo ----------
  function iniciarZonaArquivo(raiz) {
    raiz.querySelectorAll('[data-zona-arquivo]').forEach((zona) => {
      const campo = zona.querySelector('input[type="file"]');
      const titulo = zona.querySelector('strong');
      const detalhe = zona.querySelector('small');
      const tituloOriginal = titulo.textContent;
      const detalheOriginal = detalhe.textContent;

      const mostrarArquivo = () => {
        const arquivo = campo.files[0];
        zona.classList.toggle('com-arquivo', Boolean(arquivo));
        if (!arquivo) {
          titulo.textContent = tituloOriginal;
          detalhe.textContent = detalheOriginal;
          return;
        }
        const tamanhoMb = (arquivo.size / 1024 / 1024).toFixed(1);
        titulo.textContent = arquivo.name;
        detalhe.textContent = arquivo.size > 5 * 1024 * 1024
          ? `${tamanhoMb} MB — o limite é 5 MB`
          : `${tamanhoMb} MB · pronto para enviar`;
      };

      campo.addEventListener('change', mostrarArquivo);
      ['dragenter', 'dragover'].forEach((tipo) => zona.addEventListener(tipo, () => zona.classList.add('arrastando')));
      ['dragleave', 'drop'].forEach((tipo) => zona.addEventListener(tipo, () => zona.classList.remove('arrastando')));
    });
  }

  // ---------- Máscara de telefone (WhatsApp) ----------
  function iniciarMascaraTelefone(raiz) {
    raiz.querySelectorAll('[data-mascara-telefone]').forEach((campo) => {
      const formatar = () => {
        const numeros = campo.value.replace(/\D/g, '').slice(0, 11);
        let texto = numeros;
        if (numeros.length > 2) texto = `(${numeros.slice(0, 2)}) ${numeros.slice(2)}`;
        if (numeros.length > 7) texto = `(${numeros.slice(0, 2)}) ${numeros.slice(2, 7)}-${numeros.slice(7)}`;
        campo.value = texto;
      };
      campo.addEventListener('input', formatar);
      formatar();
    });
  }

  function iniciar(raiz) {
    iniciarSeletorDeTipo(raiz);
    iniciarAlternarSenha(raiz);
    iniciarForcaSenha(raiz);
    iniciarCampoTags(raiz);
    iniciarZonaArquivo(raiz);
    iniciarMascaraTelefone(raiz);
  }

  // O HTMX dispara htmx:load para o documento inicial e para cada trecho novo inserido.
  document.addEventListener('htmx:load', (evento) => iniciar(evento.detail.elt));
})();
