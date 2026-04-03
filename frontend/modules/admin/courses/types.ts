export interface CourseFormState {
  typeCode: string
  dayOfWeek: number
  startTime: string
  endTime: string
  level: number
  trainerId: string
  comment: string
}

export interface CourseTableSort {
  column: string | null
  direction: 'asc' | 'desc'
}
