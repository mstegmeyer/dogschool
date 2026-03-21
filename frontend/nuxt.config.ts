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
    },
  },

  compatibilityDate: '2025-01-01',
})
