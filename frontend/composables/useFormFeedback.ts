import type { FetchError } from 'ofetch'
import { ref } from 'vue'

interface ApiErrorData {
  error?: string
  errors?: Record<string, string | string[]>
  message?: string
}

function normalizeFieldErrors(input: ApiErrorData['errors']): Record<string, string> {
  if (!input) return {}

  return Object.fromEntries(
    Object.entries(input)
      .filter(([, value]) => value !== undefined && value !== null && `${value}` !== '')
      .map(([key, value]) => [key, Array.isArray(value) ? value.join(', ') : value]),
  )
}

export function extractApiFieldErrors(cause: unknown): Record<string, string> {
  const error = cause as FetchError<ApiErrorData>

  return normalizeFieldErrors(error?.data?.errors)
}

export function extractApiErrorMessage(
  cause: unknown,
  fallback = 'Es ist ein Fehler aufgetreten.',
  options: { fieldErrors?: Record<string, string>, preferFieldSummary?: boolean } = {},
): string {
  const error = cause as FetchError<ApiErrorData>
  const fieldErrors = options.fieldErrors ?? extractApiFieldErrors(cause)
  const status = error?.statusCode ?? error?.response?.status

  if (options.preferFieldSummary !== false && Object.keys(fieldErrors).length > 0) {
    return 'Bitte prüfe die markierten Felder.'
  }

  if (typeof error?.data?.error === 'string' && error.data.error !== '') {
    return error.data.error
  }

  if (typeof error?.data?.message === 'string' && error.data.message !== '') {
    return error.data.message
  }

  if (status && status >= 500) {
    return 'Der Server ist gerade nicht erreichbar. Bitte versuche es gleich noch einmal.'
  }

  if (error?.message?.includes('Failed to fetch') || error?.message?.includes('NetworkError')) {
    return 'Die App konnte den Server nicht erreichen. Bitte prüfe die Verbindung.'
  }

  return fallback
}

export const useFormFeedback = () => {
  const formError = ref('')
  const fieldErrors = ref<Record<string, string>>({})

  function clearFormErrors(): void {
    formError.value = ''
    fieldErrors.value = {}
  }

  function clearFieldError(path: string): void {
    if (!fieldErrors.value[path]) return

    const next = { ...fieldErrors.value }
    delete next[path]
    fieldErrors.value = next
  }

  function setFieldError(path: string, message: string): void {
    fieldErrors.value = {
      ...fieldErrors.value,
      [path]: message,
    }
  }

  function setFormError(message: string): void {
    formError.value = message
  }

  function applyApiError(cause: unknown, fallback = 'Es ist ein Fehler aufgetreten.'): void {
    const nextFieldErrors = extractApiFieldErrors(cause)
    fieldErrors.value = nextFieldErrors
    formError.value = extractApiErrorMessage(cause, fallback, {
      fieldErrors: nextFieldErrors,
      preferFieldSummary: true,
    })
  }

  function errorFor(path: string): string | undefined {
    return fieldErrors.value[path]
  }

  return {
    formError,
    fieldErrors,
    clearFormErrors,
    clearFieldError,
    setFieldError,
    setFormError,
    applyApiError,
    errorFor,
  }
}
