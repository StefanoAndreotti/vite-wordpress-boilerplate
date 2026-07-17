import fs from 'fs';
import os from 'os';
import { defineConfig, loadEnv } from 'vite';
import legacy from '@vitejs/plugin-legacy';
import fullReload from 'vite-plugin-full-reload';
import autoprefixer from 'autoprefixer';

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
        },
        output: {
          entryFileNames: 'js/[name]-[hash].js',
          chunkFileNames: 'js/[name]-[hash].js',
          assetFileNames: ({ names }) => {
            const name = names[0] ?? '';
            if (name.endsWith('.css')) return 'css/[name]-[hash][extname]';
            if (/\.(woff2?|ttf|otf|eot)$/.test(name)) return 'fonts/[name]-[hash][extname]';
            return 'img/[name]-[hash][extname]';
          },
        },
      },
    },

    // Se esiste un certificato Valet per LOCAL_DOMAIN (~/.config/valet/Certificates),
    // serve in HTTPS sul dominio locale: necessario per testare cookie cross-site,
    // GTM/consent mode, form ecc. in un contesto realistico. Altrimenti fallback su
    // http://localhost, che resta valido per lo sviluppo "semplice".
    server: (() => {
      const certDir = `${os.homedir()}/.config/valet/Certificates`;
      const certFile = `${certDir}/${env.LOCAL_DOMAIN}.crt`;
      const keyFile = `${certDir}/${env.LOCAL_DOMAIN}.key`;
      const hasSSL = env.LOCAL_DOMAIN && fs.existsSync(certFile) && fs.existsSync(keyFile);

      return {
        host: hasSSL ? env.LOCAL_DOMAIN : 'localhost',
        port: 5173,
        cors: true,
        ...(hasSSL && {
          https: {
            cert: fs.readFileSync(certFile),
            key: fs.readFileSync(keyFile),
          },
        }),
      };
    })(),

    css: {
      preprocessorOptions: {
        scss: {
          quietDeps: true,
          loadPaths: [`${THEME_PATH}/assets/src/sass`],
        },
      },
      postcss: {
        plugins: [
          autoprefixer(),
        ],
      },
    },

    plugins: [
      legacy({ targets: ['defaults', 'not IE 11'] }),
      fullReload([`${THEME_PATH}/**/*.php`]),
    ],
  };
});
