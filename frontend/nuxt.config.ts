export default defineNuxtConfig({
  modules: ['@nuxt/ui'],

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

  app: {
    head: {
      title: 'Komm! Hundeschule',
      meta: [
        { name: 'description', content: 'Komm! Hundeschule & Hundehotel – Verwaltung' },
      ],
      link: [
        { rel: 'icon', type: 'image/png', href: '/favicon.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap' },
      ],
    },
  },

  compatibilityDate: '2025-01-01',
})
