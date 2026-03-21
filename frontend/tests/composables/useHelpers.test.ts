import { describe, it, expect } from 'vitest'
import { useHelpers } from '../../composables/useHelpers'

describe('useHelpers', () => {
  const {
    dayName,
    dayNameShort,
    formatDate,
    formatDateTime,
    todayIso,
    contractStateLabel,
    contractStateColor,
    creditTypeLabel,
    levelLabel,
    getWeekMonday,
    formatContractMonthlyPrice,
    formatNotificationCourse,
    formatNotificationCourses,
  } = useHelpers()

  describe('dayName', () => {
    it('returns German day names for valid indices', () => {
      expect(dayName(1)).toBe('Montag')
      expect(dayName(2)).toBe('Dienstag')
      expect(dayName(3)).toBe('Mittwoch')
      expect(dayName(4)).toBe('Donnerstag')
      expect(dayName(5)).toBe('Freitag')
      expect(dayName(6)).toBe('Samstag')
      expect(dayName(7)).toBe('Sonntag')
    })

    it('returns empty string for out-of-range index', () => {
      expect(dayName(0)).toBe('')
      expect(dayName(8)).toBe('')
    })
  })

  describe('dayNameShort', () => {
    it('returns abbreviated German day names', () => {
      expect(dayNameShort(1)).toBe('Mo')
      expect(dayNameShort(5)).toBe('Fr')
      expect(dayNameShort(7)).toBe('So')
    })

    it('returns empty string for out-of-range index', () => {
      expect(dayNameShort(0)).toBe('')
    })
  })

  describe('formatDate', () => {
    it('formats ISO date to German dd.MM.yyyy', () => {
      const result = formatDate('2026-03-21T10:00:00Z')
      expect(result).toMatch(/21\.03\.2026/)
    })
  })

  describe('formatDateTime', () => {
    it('includes date and time components', () => {
      const result = formatDateTime('2026-03-21T14:30:00Z')
      expect(result).toMatch(/21\.03\.2026/)
    })
  })

  describe('todayIso', () => {
    it('returns a YYYY-MM-DD string', () => {
      const result = todayIso()
      expect(result).toMatch(/^\d{4}-\d{2}-\d{2}$/)
    })
  })

  describe('contractStateLabel', () => {
    it('maps REQUESTED to Angefragt', () => {
      expect(contractStateLabel('REQUESTED')).toBe('Angefragt')
    })

    it('maps ACTIVE to Aktiv', () => {
      expect(contractStateLabel('ACTIVE')).toBe('Aktiv')
    })

    it('maps DECLINED to Abgelehnt', () => {
      expect(contractStateLabel('DECLINED')).toBe('Abgelehnt')
    })

    it('maps CANCELLED to Gekündigt', () => {
      expect(contractStateLabel('CANCELLED')).toBe('Gekündigt')
    })

    it('returns the raw value for unknown states', () => {
      expect(contractStateLabel('UNKNOWN')).toBe('UNKNOWN')
    })
  })

  describe('contractStateColor', () => {
    it('maps states to correct colors', () => {
      expect(contractStateColor('REQUESTED')).toBe('amber')
      expect(contractStateColor('ACTIVE')).toBe('green')
      expect(contractStateColor('DECLINED')).toBe('red')
      expect(contractStateColor('CANCELLED')).toBe('gray')
    })

    it('returns gray for unknown states', () => {
      expect(contractStateColor('UNKNOWN')).toBe('gray')
    })
  })

  describe('creditTypeLabel', () => {
    it('maps WEEKLY_GRANT to Wöchentlich', () => {
      expect(creditTypeLabel('WEEKLY_GRANT')).toBe('Wöchentlich')
    })

    it('maps BOOKING to Buchung', () => {
      expect(creditTypeLabel('BOOKING')).toBe('Buchung')
    })

    it('maps CANCELLATION to Stornierung', () => {
      expect(creditTypeLabel('CANCELLATION')).toBe('Stornierung')
    })

    it('maps MANUAL_ADJUSTMENT to Korrektur', () => {
      expect(creditTypeLabel('MANUAL_ADJUSTMENT')).toBe('Korrektur')
    })

    it('returns raw value for unknown types', () => {
      expect(creditTypeLabel('OTHER')).toBe('OTHER')
    })
  })

  describe('levelLabel', () => {
    it('returns Einsteiger for level 0', () => {
      expect(levelLabel(0)).toBe('Einsteiger')
    })

    it('returns Stufe N for levels above 0', () => {
      expect(levelLabel(1)).toBe('Stufe 1')
      expect(levelLabel(3)).toBe('Stufe 3')
    })
  })

  describe('getWeekMonday', () => {
    it('returns previous Monday for a Wednesday', () => {
      const wed = new Date('2026-03-25') // Wednesday
      const monday = getWeekMonday(wed)
      expect(monday).toBe('2026-03-23')
    })

    it('returns same date for a Monday', () => {
      const mon = new Date('2026-03-23') // Monday
      const monday = getWeekMonday(mon)
      expect(monday).toBe('2026-03-23')
    })

    it('returns previous Monday for a Sunday', () => {
      const sun = new Date('2026-03-29') // Sunday
      const monday = getWeekMonday(sun)
      expect(monday).toBe('2026-03-23')
    })
  })

  describe('formatContractMonthlyPrice', () => {
    it('appends / Monat for PERPETUAL contracts', () => {
      expect(formatContractMonthlyPrice('79.00', 'PERPETUAL')).toBe('79.00 € / Monat')
    })

    it('shows plain price for other types', () => {
      expect(formatContractMonthlyPrice('199.00', 'ONE_TIME')).toBe('199.00 €')
    })
  })

  describe('formatNotificationCourse', () => {
    it('formats course with type name and schedule', () => {
      const result = formatNotificationCourse({
        typeName: 'Agility',
        typeCode: 'AGI',
        dayOfWeek: 3,
        startTime: '18:00',
      })
      expect(result).toBe('Agility · Mittwoch 18:00')
    })

    it('falls back to typeCode when typeName is null', () => {
      const result = formatNotificationCourse({
        typeName: null,
        typeCode: 'AGI',
        dayOfWeek: 1,
        startTime: '10:00',
      })
      expect(result).toBe('AGI · Montag 10:00')
    })

    it('returns dash for null course', () => {
      expect(formatNotificationCourse(null)).toBe('–')
    })
  })

  describe('formatNotificationCourses', () => {
    const courses = [
      { typeName: 'Agility', typeCode: 'AGI', dayOfWeek: 3, startTime: '18:00' },
      { typeName: 'Mensch-Hund', typeCode: 'MH', dayOfWeek: 1, startTime: '10:00' },
      { typeName: 'Trickkurs', typeCode: 'TK', dayOfWeek: 5, startTime: '16:00' },
    ]

    it('returns "Alle Kurse" for empty array', () => {
      expect(formatNotificationCourses([])).toBe('Alle Kurse')
    })

    it('shows single course without overflow', () => {
      expect(formatNotificationCourses([courses[0]], 1)).toBe('Agility · Mittwoch 18:00')
    })

    it('shows overflow count when more than maxVisible', () => {
      const result = formatNotificationCourses(courses, 1)
      expect(result).toContain('Agility · Mittwoch 18:00')
      expect(result).toContain('2 weitere')
    })

    it('shows all courses when count equals maxVisible', () => {
      const result = formatNotificationCourses(courses, 3)
      expect(result).not.toContain('weitere')
      expect(result).toContain('Agility')
      expect(result).toContain('Mensch-Hund')
      expect(result).toContain('Trickkurs')
    })
  })
})
