/**
 * Gera os PNGs dos criativos a partir de criativos.html.
 * Uso (precisa do Playwright instalado): node marketing/anuncios/gerar.js
 */
const { chromium } = require('playwright');
const path = require('path');

const pagina = 'file:///' + path.join(__dirname, 'criativos.html').replace(/\\/g, '/');
const pecas = { a: 'marca', b: 'curiosidade', c: 'dor-tempo' };
const formatos = { feed: [1080, 1350], story: [1080, 1920] };

(async () => {
  const browser = await chromium.launch();
  for (const [letra, nome] of Object.entries(pecas)) {
    for (const [formato, [largura, altura]] of Object.entries(formatos)) {
      const page = await browser.newPage({ viewport: { width: largura, height: altura }, deviceScaleFactor: 1 });
      await page.goto(`${pagina}?c=${letra}&f=${formato}`, { waitUntil: 'networkidle' });
      await page.evaluate(() => document.fonts.ready);
      const arquivo = path.join(__dirname, `${letra}-${nome}-${formato}.png`);
      await page.locator('#peca').screenshot({ path: arquivo });
      console.log('gerado', path.basename(arquivo));
      await page.close();
    }
  }
  await browser.close();
})();
