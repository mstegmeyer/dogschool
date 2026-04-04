import type { Page } from '@playwright/test'

export async function freezeBrowserClock(page: Page, fixedNowIso: string): Promise<void> {
  await page.addInitScript(({ fixedNowIso }) => {
    const fixedTime = new Date(fixedNowIso).valueOf()
    const NativeDate = Date

    class MockDate extends NativeDate {
      constructor(...args: ConstructorParameters<DateConstructor>) {
        if (args.length === 0) {
          super(fixedTime)
          return
        }

        super(...args)
      }

      static now(): number {
        return fixedTime
      }
    }

    Object.setPrototypeOf(MockDate, NativeDate)
    // @ts-expect-error init script runs in the browser
    window.Date = MockDate

    const style = document.createElement('style')
    style.textContent = `
      *,
      *::before,
      *::after {
        animation-duration: 0s !important;
        animation-delay: 0s !important;
        transition-duration: 0s !important;
        transition-delay: 0s !important;
        caret-color: transparent !important;
      }
    `

    const attachStyle = () => {
      if (!document.head.contains(style)) {
        document.head.appendChild(style)
      }
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', attachStyle, { once: true })
    } else {
      attachStyle()
    }
  }, { fixedNowIso })
}

export async function installClipboardStub(page: Page): Promise<void> {
  await page.addInitScript(() => {
    const writes: string[] = []

    Object.defineProperty(window, '__clipboardWrites', {
      configurable: true,
      value: writes,
      writable: false,
    })

    Object.defineProperty(navigator, 'clipboard', {
      configurable: true,
      value: {
        writeText: async (value: string) => {
          writes.push(value)
        },
      },
    })
  })
}

export async function installPushStub(page: Page): Promise<void> {
  await page.addInitScript(() => {
    let permission: NotificationPermission = 'default'
    let subscription: {
      endpoint: string
      expirationTime: null
      options: { applicationServerKey: ArrayBuffer | Uint8Array | null }
      toJSON: () => Record<string, string>
      unsubscribe: () => Promise<boolean>
    } | null = null

    function createSubscription(applicationServerKey: ArrayBuffer | Uint8Array | null) {
      return {
        endpoint: 'https://push.example.test/subscription',
        expirationTime: null,
        options: {
          applicationServerKey,
        },
        toJSON() {
          return {
            endpoint: this.endpoint,
          }
        },
        async unsubscribe() {
          subscription = null
          return true
        },
      }
    }

    function FakeNotification() {}

    Object.defineProperty(FakeNotification, 'permission', {
      configurable: true,
      get() {
        return permission
      },
    })

    Object.defineProperty(FakeNotification, 'requestPermission', {
      configurable: true,
      value: async () => {
        permission = 'granted'
        return permission
      },
    })

    Object.defineProperty(window, 'Notification', {
      configurable: true,
      value: FakeNotification,
    })

    Object.defineProperty(window, 'PushManager', {
      configurable: true,
      value: function PushManager() {},
    })

    Object.defineProperty(navigator, 'serviceWorker', {
      configurable: true,
      value: {
        register: async () => ({
          pushManager: {
            getSubscription: async () => subscription,
            subscribe: async (options: { applicationServerKey?: ArrayBuffer | Uint8Array | null }) => {
              subscription = createSubscription(options.applicationServerKey ?? null)
              return subscription
            },
          },
        }),
      },
    })
  })
}

export async function waitForFontsAndNetwork(page: Page): Promise<void> {
  await page.waitForLoadState('networkidle')
  await page.evaluate(async () => {
    if ('fonts' in document) {
      await document.fonts.ready
    }
  })
  await page.evaluate(() => {
    const activeElement = document.activeElement
    if (activeElement instanceof HTMLElement) {
      activeElement.blur()
    }
  })
  await page.waitForTimeout(50)
}
