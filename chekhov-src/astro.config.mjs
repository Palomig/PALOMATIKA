import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

export default defineConfig({
  site: 'https://palomatika.ru',
  base: '/chekhov',
  trailingSlash: 'always',
  outDir: '../public/chekhov',
  build: {
    assets: '_assets',
    format: 'directory',
  },
  integrations: [
    sitemap({
      filter: (page) => !page.includes('/404'),
    }),
  ],
});
