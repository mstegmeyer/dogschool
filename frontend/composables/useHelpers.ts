import type { ContractState, ContractType, CourseLevel, CreditTransactionType, NotificationCourseRef } from '~/types'

const DAY_NAMES: readonly string[] = ['', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag']
const DAY_NAMES_SHORT: readonly string[] = ['', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']
const DATE_ONLY_PATTERN = /^\d{4}-\d{2}-\d{2}$/

function parseDateValue(value: string): Date {
  if (DATE_ONLY_PATTERN.test(value)) {
    const [year, month, day] = value.split('-').map(Number)
    return new Date(year, month - 1, day)
  }

  return new Date(value)
}

function toIsoDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

export const useHelpers = () => {
  function dayName(dayOfWeek: number): string {
    return DAY_NAMES[dayOfWeek] || ''
  }

  function dayNameShort(dayOfWeek: number): string {
    return DAY_NAMES_SHORT[dayOfWeek] || ''
  }

  function formatDate(iso: string): string {
    return parseDateValue(iso).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }

  function formatDateShort(iso: string): string {
    return parseDateValue(iso).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' })
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

  function addDaysToIso(iso: string, days: number): string {
    const date = parseDateValue(iso)
    date.setDate(date.getDate() + days)
    return toIsoDate(date)
  }

  function getIsoDayOfWeek(iso: string): number {
    const day = parseDateValue(iso).getDay()
    return day === 0 ? 7 : day
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

  function getWeekMonday(date: Date | string = todayIso()): string {
    const d = typeof date === 'string'
      ? parseDateValue(date)
      : new Date(date.getFullYear(), date.getMonth(), date.getDate())
    const day = d.getDay() === 0 ? 7 : d.getDay()
    d.setDate(d.getDate() - (day - 1))
    return toIsoDate(d)
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
    formatDate, formatDateShort, formatDateTime,
    todayIso, addDaysToIso, getIsoDayOfWeek,
    contractStateLabel, contractStateColor,
    creditTypeLabel, levelLabel,
    getWeekMonday,
    formatContractMonthlyPrice,
    formatNotificationCourse,
    formatNotificationCourses,
  }
}
