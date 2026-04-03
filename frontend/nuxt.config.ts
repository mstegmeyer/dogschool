import { readdirSync, statSync } from 'node:fs'
import { join, relative, resolve, sep } from 'node:path'

interface ModuleRoute {
  name: string
  path: string
  file: string
  meta: Record<string, any>
}

const MODULE_ROOT = resolve(__dirname, 'modules')
const SKIPPED_SEGMENTS = new Set(['components', 'composables', 'types', 'utils'])

function toRouteSegments(filePath: string): string[] {
  const directory = relative(MODULE_ROOT, filePath)
    .replace(/\/index\.vue$/, '')
    .split(sep)
    .filter(Boolean)

  const [namespace, ...segments] = directory

  if (!namespace) return []
  if (namespace === 'public' || namespace === 'auth') return segments
  if ((namespace === 'admin' || namespace === 'customer') && segments[0] === 'dashboard') {
    return [namespace, ...segments.slice(1)]
  }

  return [namespace, ...segments]
}

function segmentToRoutePath(segment: string): string {
  if (segment.startsWith('[') && segment.endsWith(']')) {
    return `:${segment.slice(1, -1)}`
  }

  return segment
}

function segmentToRouteName(segment: string): string {
  if (segment.startsWith('[') && segment.endsWith(']')) {
    return segment.slice(1, -1)
  }

  return segment
}

function resolveRouteMeta(filePath: string): Record<string, any> {
  const namespace = relative(MODULE_ROOT, filePath).split(sep).filter(Boolean)[0]

  switch (namespace) {
    case 'admin':
      return { layout: 'admin' }
    case 'customer':
      return { layout: 'customer' }
    case 'auth':
      return { layout: 'auth' }
    case 'public':
      return { layout: false }
    default:
      return {}
  }
}

function collectModuleRoutes(directory: string = MODULE_ROOT): ModuleRoute[] {
  const routes: ModuleRoute[] = []

  function walk(currentDirectory: string): void {
    for (const entry of readdirSync(currentDirectory)) {
      const absolutePath = join(currentDirectory, entry)
      const relativePath = relative(MODULE_ROOT, absolutePath)
      const relativeSegments = relativePath.split(sep).filter(Boolean)

      if (relativeSegments.some(segment => SKIPPED_SEGMENTS.has(segment))) {
        continue
      }

      const stats = statSync(absolutePath)
      if (stats.isDirectory()) {
        walk(absolutePath)
        continue
      }

      if (!stats.isFile() || entry !== 'index.vue') {
        continue
      }

      const routeSegments = toRouteSegments(absolutePath)
      const routePath = routeSegments.length === 0
        ? '/'
        : `/${routeSegments.map(segmentToRoutePath).join('/')}`
      const routeName = routeSegments.length === 0
        ? 'index'
        : routeSegments.map(segmentToRouteName).join('-')

      routes.push({
        name: routeName,
        path: routePath,
        file: absolutePath,
        meta: resolveRouteMeta(absolutePath),
      })
    }
  }

  walk(directory)

  return routes.sort((left, right) => {
    const leftSegments = left.path.split('/').filter(Boolean)
    const rightSegments = right.path.split('/').filter(Boolean)
    const leftDynamicCount = leftSegments.filter(segment => segment.startsWith(':')).length
    const rightDynamicCount = rightSegments.filter(segment => segment.startsWith(':')).length

    if (leftSegments.length !== rightSegments.length) {
      return leftSegments.length - rightSegments.length
    }

    if (leftDynamicCount !== rightDynamicCount) {
      return leftDynamicCount - rightDynamicCount
    }

    return left.path.localeCompare(right.path)
  })
}

export default defineNuxtConfig({
  modules: ['@nuxt/ui'],
  css: ['~/assets/app.css'],
  pages: true,

  dir: {
    modules: '_nuxt-modules',
  },

  components: [
    {
      path: '~/components',
      pathPrefix: false,
    },
  ],

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

  hooks: {
    'pages:extend'(pages) {
      pages.splice(0, pages.length, ...collectModuleRoutes())
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
