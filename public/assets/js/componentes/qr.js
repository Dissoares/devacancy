/**
 * Desenha QR codes no navegador (a partir do texto em data-qr), sem enviar nada para terceiros.
 * Usa js/vendor/qrcode.js (qrcode-generator, MIT). <div data-qr="texto do código"></div>
 */
(() => {
  'use strict';

  function desenhar(raiz = document) {
    if (typeof qrcode !== 'function') return;
    raiz.querySelectorAll('[data-qr]:not([data-qr-pronto])').forEach((alvo) => {
      try {
        const qr = qrcode(0, 'M');
        qr.addData(alvo.dataset.qr);
        qr.make();
        alvo.innerHTML = qr.createSvgTag({ scalable: true, margin: 2 });
        alvo.dataset.qrPronto = '1';
      } catch {
        alvo.hidden = true;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => desenhar());
  document.addEventListener('htmx:afterSwap', (evento) => desenhar(evento.target));
})();
