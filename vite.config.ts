import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import {defineConfig, loadEnv} from 'vite';

export default defineConfig(({mode}) => {
  const env = loadEnv(mode, '.', '');
  return {
    // Production build is served from /public_html/app/ on Hostinger,
    // so emitted asset paths must be prefixed with /app/. Override per
    // build with VITE_BASE_PATH if you ever change the subdirectory.
    base: env.VITE_BASE_PATH || (mode === 'production' ? '/app/' : '/'),
    plugins: [react(), tailwindcss()],
    define: {
      'process.env.GEMINI_API_KEY': JSON.stringify(env.GEMINI_API_KEY),
    },
    resolve: {
      alias: {
        '@': path.resolve(__dirname, '.'),
      },
    },
    server: {
      // HMR is disabled in AI Studio via DISABLE_HMR env var.
      // Do not modifyâfile watching is disabled to prevent flickering during agent edits.
      hmr: process.env.DISABLE_HMR !== 'true',
    },
    build: {
      // Phase 2.6b-fix — vendor chunk splitting per D-2.6b-fix-1.
      //
      // Phase 2.6b code-split routes via React.lazy() but left
      // every third-party dependency in the initial chunk, leaving
      // it at 518 KB raw (target was <300 KB). The 4 chunks below
      // are flat, eager-loaded, and independently cacheable: a
      // patch upgrade of `motion` does not bust the `react-vendor`
      // hash, so returning users get the new app shell with the
      // same long-cached React.
      //
      // Anything else under node_modules falls back to Vite's
      // default chunking (typically rolled into the per-route
      // chunk that imports it). Adding more buckets here is
      // diminishing returns — the largest remaining packages are
      // <30 KB each.
      rollupOptions: {
        output: {
          manualChunks(id: string) {
            if (!id.includes('node_modules')) return undefined;
            if (id.includes('/react-dom/') || id.includes('/react/') || id.includes('/scheduler/')) {
              return 'react-vendor';
            }
            if (id.includes('/motion/') || id.includes('/framer-motion/')) {
              return 'motion-vendor';
            }
            if (id.includes('/lucide-react/')) {
              return 'icons-vendor';
            }
            if (id.includes('/@tanstack/react-query/')) {
              return 'query-vendor';
            }
            return undefined;
          },
        },
      },
    },
  };
});
