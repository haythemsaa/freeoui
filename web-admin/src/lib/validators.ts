/**
 * Validation utilities for form inputs
 */

export const validators = {
  /**
   * Validate email format
   */
  email: (value: string): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(value)
  },

  /**
   * Validate Tunisian phone number
   */
  phoneNumber: (value: string): boolean => {
    // Accepts formats: 20123456, 50123456, 90123456, etc.
    const phoneRegex = /^[2-9]\d{7}$/
    return phoneRegex.test(value)
  },

  /**
   * Validate password strength
   * At least 8 characters, one uppercase, one lowercase, one number
   */
  password: (value: string): boolean => {
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/
    return passwordRegex.test(value)
  },

  /**
   * Validate URL format
   */
  url: (value: string): boolean => {
    try {
      new URL(value)
      return true
    } catch {
      return false
    }
  },

  /**
   * Validate numeric range
   */
  range: (value: number, min: number, max: number): boolean => {
    return value >= min && value <= max
  },

  /**
   * Validate required field
   */
  required: (value: any): boolean => {
    if (typeof value === 'string') {
      return value.trim().length > 0
    }
    if (Array.isArray(value)) {
      return value.length > 0
    }
    return value !== null && value !== undefined
  },

  /**
   * Validate minimum length
   */
  minLength: (value: string, length: number): boolean => {
    return value.length >= length
  },

  /**
   * Validate maximum length
   */
  maxLength: (value: string, length: number): boolean => {
    return value.length <= length
  },

  /**
   * Validate latitude coordinate
   */
  latitude: (value: number): boolean => {
    return value >= -90 && value <= 90
  },

  /**
   * Validate longitude coordinate
   */
  longitude: (value: number): boolean => {
    return value >= -180 && value <= 180
  },

  /**
   * Validate percentage (0-100)
   */
  percentage: (value: number): boolean => {
    return value >= 0 && value <= 100
  },

  /**
   * Validate date is in future
   */
  futureDate: (value: string | Date): boolean => {
    const date = typeof value === 'string' ? new Date(value) : value
    return date > new Date()
  },

  /**
   * Validate date is in past
   */
  pastDate: (value: string | Date): boolean => {
    const date = typeof value === 'string' ? new Date(value) : value
    return date < new Date()
  },
}

/**
 * Get error message for validation
 */
export const getValidationMessage = (field: string, rule: string, params?: any): string => {
  const messages: Record<string, string> = {
    required: `${field} est requis`,
    email: `${field} doit être un email valide`,
    phoneNumber: `${field} doit être un numéro de téléphone valide`,
    password: `${field} doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre`,
    url: `${field} doit être une URL valide`,
    minLength: `${field} doit contenir au moins ${params} caractères`,
    maxLength: `${field} ne peut pas dépasser ${params} caractères`,
    latitude: `${field} doit être une latitude valide (-90 à 90)`,
    longitude: `${field} doit être une longitude valide (-180 à 180)`,
    percentage: `${field} doit être un pourcentage valide (0 à 100)`,
    futureDate: `${field} doit être une date future`,
    pastDate: `${field} doit être une date passée`,
    range: `${field} doit être entre ${params?.min} et ${params?.max}`,
  }

  return messages[rule] || `${field} n'est pas valide`
}
