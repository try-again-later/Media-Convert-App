import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  root: './src',
  envDir: resolve(__dirname),
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src', 'index.html'),
      },
      output: {
        dir: resolve(__dirname, 'dist'),
      },
    },
  },
  plugins: [vue()],
  resolve: {
    alias: [
      {
        find: '@root',
        replacement: resolve(__dirname, 'src'),
      },
      {
        find: '@components',
        replacement: resolve(__dirname, 'src', 'components'),
      },
    ],
  },
  server: {
    cors: true,
  },
});
