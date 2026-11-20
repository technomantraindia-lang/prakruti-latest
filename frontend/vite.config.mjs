import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';

const projectRoot = fileURLToPath(new URL('.', import.meta.url));

export default defineConfig({
  plugins: [react()],
    root: projectRoot,
    resolve: {
      preserveSymlinks: true,
    },
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    fs: {
      // This repository is nested inside several copied folders. Restrict
      // Vite to the actual storefront so it does not walk up to a protected
      // Windows directory while locating the workspace root.
      allow: [projectRoot],
      strict: false,
    },
    watch: {
      usePolling: true,
      interval: 800,
    },
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      },
      '/media': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
