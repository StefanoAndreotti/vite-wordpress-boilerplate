import { defineConfig, loadEnv } from 'vite';
import legacy from '@vitejs/plugin-legacy';
import fullReload from 'vite-plugin-full-reload';

export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const THEME_NAME = env.THEME_NAME || 'default-theme';
  const THEME_PATH = `wp-content/themes/${THEME_NAME}`;

  return {
    base: command === 'serve'
      ? '/'
      : `/${THEME_PATH}/assets/dist/`,

    build: {
      outDir: `${THEME_PATH}/assets/dist`,
      emptyOutDir: true,
      manifest: true,
      rollupOptions: {
        input: {
          main: `${THEME_PATH}/assets/src/js/main.js`,
          style: `${THEME_PATH}/assets/src/sass/style.scss`,
        },
        output: {
          entryFileNames: 'js/[name]-[hash].js',
          chunkFileNames: 'js/[name]-[hash].js',
          assetFileNames: ({ name }) =>
            name?.endsWith('.css')
              ? 'css/[name]-[hash][extname]'
              : 'img/[name]-[hash][extname]',
        },
      },
    },

    server: {
      host: 'localhost',
      port: 5173,
      proxy: {
        // Proxy everything to WordPress except Vite's own internal paths
        '^(?!/@vite|/@fs|/node_modules)': {
          target: `http://${env.LOCAL_DOMAIN || 'localhost'}`,
          changeOrigin: true,
          secure: false,
        },
      },
    },

    css: {
      preprocessorOptions: {
        scss: { quietDeps: true },
      },
      postcss: {
        plugins: [
          (await import('autoprefixer')).default(),
        ],
      },
    },

    plugins: [
      legacy({ targets: ['defaults', 'not IE 11'] }),
      fullReload([`${THEME_PATH}/**/*.php`]),
    ],
  };
});
