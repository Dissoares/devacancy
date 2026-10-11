/**
 * Devacancy — funil de assinatura: animação de análise do perfil, máscara de CPF e botão de copiar o PIX.
 */
(() => {
  'use strict';

  // Tela de busca: mensagens de status, depois as vagas encontradas entram uma a uma com o contador
  // subindo e, no fim, aparece a conclusão com o botão de aplicar. O ritmo roda mesmo com
  // "reduzir animações" (só os movimentos saem, via CSS). Sem JS, tudo aparece de uma vez.
  const busca = document.querySelector('[data-busca]');
  if (busca) {
    const status = busca.querySelector('[data-busca-status]');
    const contador = busca.querySelector('[data-busca-contador]');
    const progresso = busca.querySelector('[data-progresso]');
    const itens = [...busca.querySelectorAll('[data-busca-item]')];
    const conclusao = busca.querySelector('[data-busca-conclusao]');
    const ctaFixo = document.querySelector('[data-cta-fixo-funil]');
    const mensagens = JSON.parse(status.dataset.mensagens || '[]');
    const totalFinal = contador.textContent;
    const esperar = (ms) => new Promise((resolver) => setTimeout(resolver, ms));
    const avancarProgresso = (fracao) => { progresso.style.width = `${Math.round(fracao * 100)}%`; };

    document.documentElement.classList.add('buscando');
    contador.textContent = '0';

    (async () => {
      for (const [indice, mensagem] of mensagens.entries()) {
        status.textContent = mensagem;
        avancarProgresso(((indice + 1) / mensagens.length) * 0.25);
        await esperar(900);
      }

      let encontradas = 0;
      for (const [indice, item] of itens.entries()) {
        item.classList.add('visivel');
        if (!item.classList.contains('vaga-encontrada-vazia')) contador.textContent = String(++encontradas);
        avancarProgresso(0.25 + ((indice + 1) / itens.length) * 0.75);
        await esperar(indice === 0 ? 1200 : 950);
      }

      status.textContent = status.dataset.concluido;
      contador.textContent = totalFinal;
      busca.classList.add('concluida');
      document.documentElement.classList.remove('buscando');
      await esperar(400);
      conclusao.classList.add('visivel');
      conclusao.scrollIntoView({ behavior: 'smooth', block: 'center' });

      // No celular, o botão fixo aparece só quando a conclusão (que já tem o botão) sai da tela.
      if (ctaFixo && 'IntersectionObserver' in window) {
        new IntersectionObserver(([entrada]) => ctaFixo.classList.toggle('visivel', !entrada.isIntersecting)).observe(conclusao);
      }
    })();
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
    window.Devacancy?.mostrarAviso(botao.dataset.mensagem, 'sucesso');
  });

  // Abas de meio de pagamento (PIX / PayPal).
  document.querySelectorAll('[data-metodos]').forEach((metodos) => {
    metodos.querySelectorAll('[data-aba]').forEach((aba) => {
      aba.addEventListener('click', () => {
        metodos.querySelectorAll('[data-aba]').forEach((outra) => {
          outra.classList.toggle('ativo', outra === aba);
          outra.setAttribute('aria-selected', String(outra === aba));
        });
        metodos.querySelectorAll('[data-painel]').forEach((painel) => { painel.hidden = painel.dataset.painel !== aba.dataset.aba; });
      });
    });
  });

  // Botão de assinatura do PayPal: o plano escolhido é lido na hora do clique.
  const areaPaypal = document.querySelector('[data-paypal]');
  if (areaPaypal) {
    const { clienteId, moeda, usuario, idioma } = areaPaypal.dataset;
    const planos = JSON.parse(areaPaypal.dataset.planos || '{}');
    const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const planoEscolhido = () => document.querySelector('input[name="plano"]:checked')?.value;

    const sdk = document.createElement('script');
    sdk.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clienteId)}&vault=true&intent=subscription&currency=${moeda}&locale=${idioma}`;
    sdk.dataset.sdkIntegrationSource = 'button-factory';
    sdk.onload = () => {
      window.paypal.Buttons({
        style: { shape: 'rect', color: 'gold', layout: 'vertical', label: 'subscribe' },
        createSubscription: (dados, acoes) => {
          const planId = planos[planoEscolhido()];
          if (!planId) {
            window.Devacancy?.mostrarAviso(areaPaypal.dataset.erro);
            throw new Error('Plano sem ID no PayPal');
          }
          return acoes.subscription.create({
            plan_id: planId,
            custom_id: usuario,
            application_context: { shipping_preference: 'NO_SHIPPING', locale: idioma.replace('_', '-') },
          });
        },
        onApprove: async (dados) => {
          const resposta = await fetch('/assinar/paypal', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': tokenCsrf },
            body: JSON.stringify({ assinatura_id: dados.subscriptionID }),
          });
          const corpo = await resposta.json().catch(() => ({}));
          window.location.href = corpo.destino || `/assinatura/paypal/${encodeURIComponent(dados.subscriptionID)}`;
        },
        onError: () => window.Devacancy?.mostrarAviso(areaPaypal.dataset.erro),
      }).render(areaPaypal);
    };
    sdk.onerror = () => window.Devacancy?.mostrarAviso(areaPaypal.dataset.erro);
    document.head.appendChild(sdk);
  }
})();
