self.addEventListener('push', (event) => {
  const payload = (() => {
    try {
      return event.data ? event.data.json() : {}
    } catch {
      return {}
    }
  })()

  const title = typeof payload.title === 'string' && payload.title !== ''
    ? payload.title
    : 'Komm! Hundeschule'
  const options = {
    body: typeof payload.body === 'string' ? payload.body : '',
    icon: '/favicon.png',
    badge: '/favicon.png',
    data: {
      link: typeof payload.link === 'string' ? payload.link : '/customer/notifications',
      notificationId: payload.notificationId ?? null,
    },
  }

  event.waitUntil(self.registration.showNotification(title, options))
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()

  const relativeLink = typeof event.notification.data?.link === 'string'
    ? event.notification.data.link
    : '/customer/notifications'
  const url = new URL(relativeLink, self.location.origin).toString()

  event.waitUntil((async () => {
    const clientList = await clients.matchAll({ type: 'window', includeUncontrolled: true })
    for (const client of clientList) {
      if ('focus' in client && client.url === url) {
        return client.focus()
      }
    }

    if (clients.openWindow) {
      return clients.openWindow(url)
    }
  })())
})
