/**
 * Gera o PNG do criativo (feed do Instagram, 1080x1350) a partir de criativos.html.
 * Uso (precisa do Playwright instalado): node marketing/anuncios/gerar.js
 */
const { chromium } = require('playwright');
const path = require('path');

const pagina = 'file:///' + path.join(__dirname, 'criativo.html').replace(/\\/g, '/');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1080, height: 1350 }, deviceScaleFactor: 1 });
  await page.goto(pagina, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  const arquivo = path.join(__dirname, 'anuncio-feed.png');
  await page.locator('#peca').screenshot({ path: arquivo });
  console.log('gerado', path.basename(arquivo));
  await browser.close();
})();
