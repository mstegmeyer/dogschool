export default defineNuxtConfig({
  modules: ['@nuxt/ui'],
  css: ['~/assets/app.css'],

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || process.env.API_BASE_URL || '',
      appUrl: process.env.NUXT_PUBLIC_APP_URL || 'https://komm.example.com',
      webPushVapidPublicKey: process.env.NUXT_PUBLIC_WEB_PUSH_VAPID_PUBLIC_KEY || '',
    },
  },

  ssr: false,

  devServer: {
    host: '0.0.0.0',
    port: 5173,
  },

  nitro: {
    devProxy: {
      '/api': {
        target: (process.env.API_PROXY_TARGET || 'http://localhost:8080') + '/api',
        changeOrigin: true,
      },
    },
  },

  // Stub for client pre-transform: Vite resolves import("#app-manifest") even when SSR is stripped (nuxt/nuxt#33606).
  vite: {
    $client: {
      resolve: {
        alias: {
          '#app-manifest': 'unenv/mock/empty',
        },
      },
    },
  },

  app: {
    head: {
      title: 'Komm! Hundeschule',
      meta: [
        { name: 'description', content: 'Komm! Hundeschule & Hundehotel – Verwaltung' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover' },
        { name: 'theme-color', content: '#ffffff' },
        { name: 'apple-mobile-web-app-capable', content: 'yes' },
      ],
      link: [
        { rel: 'icon', type: 'image/png', href: '/favicon.png' },
        { rel: 'manifest', href: '/manifest.webmanifest' },
        { rel: 'apple-touch-icon', href: '/favicon.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap' },
      ],
    },
  },

  compatibilityDate: '2025-01-01',
})
