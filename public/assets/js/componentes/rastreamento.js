/*
 * Pixel da Meta e Google Analytics, só depois do consentimento.
 * A página entrega window.DV_RASTREIO = { pixel, ga, eventos, textos }. Sem aceite nada é carregado e os eventos são descartados.
 */
(() => {
  const cfg = window.DV_RASTREIO;
  if (!cfg) return;

  const COOKIE = 'dv_cookies';
  const lerEscolha = () => (document.cookie.match(new RegExp('(?:^|; )' + COOKIE + '=(\\w+)')) || [])[1] || '';
  const gravarEscolha = (valor) => {
    const seguro = location.protocol === 'https:' ? '; secure' : '';
    document.cookie = `${COOKIE}=${valor}; max-age=${60 * 60 * 24 * 180}; path=/; samesite=lax${seguro}`;
  };

  let carregado = false;

  function carregarFerramentas() {
    if (carregado) return;
    carregado = true;

    if (cfg.pixel) {
      /* eslint-disable */
      !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
      /* eslint-enable */
      window.fbq('init', cfg.pixel);
      window.fbq('track', 'PageView');
    }

    if (cfg.ga) {
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () { window.dataLayer.push(arguments); };
      window.gtag('js', new Date());
      window.gtag('config', cfg.ga);
      const script = document.createElement('script');
      script.async = true;
      script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.ga);
      document.head.appendChild(script);
    }

    (cfg.eventos || []).forEach((evento) => {
      if (cfg.pixel && evento.meta) window.fbq('track', evento.meta[0], evento.meta[1] || {}, { eventID: evento.id });
      if (cfg.ga && evento.ga) window.gtag('event', evento.ga[0], evento.ga[1] || {});
    });
  }

  function mostrarAviso() {
    if (document.querySelector('.aviso-cookies')) return;
    const t = cfg.textos;
    const caixa = document.createElement('div');
    caixa.className = 'aviso-cookies';
    caixa.setAttribute('role', 'dialog');
    caixa.setAttribute('aria-label', t.rotulo);

    const texto = document.createElement('p');
    texto.textContent = t.aviso + ' ';
    const link = document.createElement('a');
    link.href = '/privacidade';
    link.textContent = t.politica;
    texto.appendChild(link);

    const acoes = document.createElement('div');
    acoes.className = 'aviso-cookies-acoes';
    const recusar = document.createElement('button');
    recusar.type = 'button';
    recusar.className = 'botao botao-secundario botao-pequeno';
    recusar.textContent = t.recusar;
    const aceitar = document.createElement('button');
    aceitar.type = 'button';
    aceitar.className = 'botao botao-primario botao-pequeno';
    aceitar.textContent = t.aceitar;
    acoes.append(recusar, aceitar);

    caixa.append(texto, acoes);
    document.body.appendChild(caixa);

    recusar.addEventListener('click', () => { gravarEscolha('recusado'); caixa.remove(); });
    aceitar.addEventListener('click', () => { gravarEscolha('aceito'); caixa.remove(); carregarFerramentas(); });
  }

  // Link "Cookies" do rodapé: a pessoa pode mudar de ideia.
  document.addEventListener('click', (evento) => {
    if (evento.target.closest('[data-abrir-cookies]')) {
      evento.preventDefault();
      mostrarAviso();
    }
  });

  const escolha = lerEscolha();
  if (escolha === 'aceito') carregarFerramentas();
  else if (escolha !== 'recusado') mostrarAviso();
})();
