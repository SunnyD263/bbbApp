// vite.config.js
import { defineConfig } from 'vite';
import symfony from 'vite-plugin-symfony';

export default defineConfig({
  plugins: [symfony()],
  resolve: {
    alias: { '@': '/assets' },
  },
  css: {
    preprocessorOptions: {
      scss: {
        // nic sem NEPŘIDÁVAT (žádné additionalData)
      },
    },
  },
  build: {
    rollupOptions: {
      input: { app: './assets/app.js' },
    },
  },
});