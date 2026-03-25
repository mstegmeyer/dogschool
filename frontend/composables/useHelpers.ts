import type { ContractState, ContractType, CourseLevel, CreditTransactionType, NotificationCourseRef } from '~/types'

const DAY_NAMES: readonly string[] = ['', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag']
const DAY_NAMES_SHORT: readonly string[] = ['', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']

export const useHelpers = () => {
  function dayName(dayOfWeek: number): string {
    return DAY_NAMES[dayOfWeek] || ''
  }

  function dayNameShort(dayOfWeek: number): string {
    return DAY_NAMES_SHORT[dayOfWeek] || ''
  }

  function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }

  function formatDateTime(iso: string): string {
    return new Date(iso).toLocaleDateString('de-DE', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    })
  }

  /** Today as YYYY-MM-DD (Europe/Berlin). Used for API `from` parameter. */
  function todayIso(): string {
    return new Date().toLocaleDateString('en-CA', { timeZone: 'Europe/Berlin' })
  }

  function contractStateLabel(state: ContractState): string {
    const map: Record<ContractState, string> = {
      REQUESTED: 'Angefragt',
      ACTIVE: 'Aktiv',
      DECLINED: 'Abgelehnt',
      CANCELLED: 'Gekündigt',
    }
    return map[state] ?? state
  }

  function contractStateColor(state: ContractState): string {
    const map: Record<ContractState, string> = {
      REQUESTED: 'amber',
      ACTIVE: 'primary',
      DECLINED: 'red',
      CANCELLED: 'gray',
    }
    return map[state] ?? 'gray'
  }

  function creditTypeLabel(type: CreditTransactionType): string {
    const map: Record<CreditTransactionType, string> = {
      WEEKLY_GRANT: 'Wöchentlich',
      BOOKING: 'Buchung',
      CANCELLATION: 'Stornierung',
      MANUAL_ADJUSTMENT: 'Korrektur',
    }
    return map[type] ?? type
  }

  function levelLabel(level: CourseLevel | number): string {
    if (level === 0) return 'Einsteiger'
    return `Stufe ${level}`
  }

  function getWeekMonday(date: Date = new Date()): string {
    const d = new Date(date)
    const day = d.getDay()
    const diff = d.getDate() - day + (day === 0 ? -6 : 1)
    d.setDate(diff)
    return d.toISOString().split('T')[0]
  }

  function formatContractMonthlyPrice(price: string, type: ContractType): string {
    if (type === 'PERPETUAL') {
      return `${price} € / Monat`
    }
    return `${price} €`
  }

  function formatNotificationCourse(course: Pick<NotificationCourseRef, 'typeName' | 'typeCode' | 'dayOfWeek' | 'startTime'> | null | undefined): string {
    if (!course) return '–'
    const name = course.typeName || course.typeCode || 'Kurs'
    return `${name} · ${dayName(course.dayOfWeek)} ${course.startTime}`
  }

  /** Summarise the courses array for a notification, with optional "(+ N weitere)" overflow. */
  function formatNotificationCourses(
    courses: Pick<NotificationCourseRef, 'typeName' | 'typeCode' | 'dayOfWeek' | 'startTime'>[],
    maxVisible = 1,
  ): string {
    if (courses.length === 0) return 'Alle Kurse'
    const labels = courses.map(c => formatNotificationCourse(c))
    if (labels.length <= maxVisible) return labels.join(', ')
    const rest = labels.length - maxVisible
    return `${labels.slice(0, maxVisible).join(', ')} (+\u00A0${rest} weitere)`
  }

  return {
    dayName, dayNameShort,
    formatDate, formatDateTime,
    todayIso,
    contractStateLabel, contractStateColor,
    creditTypeLabel, levelLabel,
    getWeekMonday,
    formatContractMonthlyPrice,
    formatNotificationCourse,
    formatNotificationCourses,
  }
}
