/**
 * Common validation utilities
 */

/**
 * Validates email format
 */
export function isValidEmail(email: string): boolean {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

/**
 * Validates UUID format
 */
export function isValidUUID(uuid: string): boolean {
  const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
  return uuidRegex.test(uuid);
}

/**
 * Validates password strength
 * Rules: At least 8 characters, contains uppercase, lowercase, number
 */
export function isStrongPassword(password: string): boolean {
  if (password.length < 8) return false;
  const hasUpperCase = /[A-Z]/.test(password);
  const hasLowerCase = /[a-z]/.test(password);
  const hasNumber = /[0-9]/.test(password);
  return hasUpperCase && hasLowerCase && hasNumber;
}

/**
 * Validates phone number (Turkish format)
 */
export function isValidPhoneNumber(phone: string): boolean {
  // Accepts formats: 05XX XXX XXXX, +90 5XX XXX XXXX, 5XXXXXXXXX
  const phoneRegex = /^(\+90|0)?5\d{9}$/;
  const cleanPhone = phone.replace(/\s/g, '');
  return phoneRegex.test(cleanPhone);
}

/**
 * Validates SKU format
 * Allows alphanumeric with hyphens and underscores
 */
export function isValidSKU(sku: string): boolean {
  const skuRegex = /^[A-Z0-9_-]+$/i;
  return skuRegex.test(sku);
}

/**
 * Validates price (positive number with max 2 decimal places)
 */
export function isValidPrice(price: number): boolean {
  if (price < 0) return false;
  const decimalPlaces = (price.toString().split('.')[1] || '').length;
  return decimalPlaces <= 2;
}

/**
 * Validates date is not in the past
 */
export function isNotPastDate(date: Date): boolean {
  return date.getTime() >= Date.now();
}

/**
 * Validates string is not empty after trimming
 */
export function isNotEmptyString(str: string): boolean {
  return typeof str === 'string' && str.trim().length > 0;
}

/**
 * Validates array is not empty
 */
export function isNotEmptyArray<T>(arr: T[]): boolean {
  return Array.isArray(arr) && arr.length > 0;
}

/**
 * Sanitizes string to create URL-friendly slug
 */
export function createSlug(text: string): string {
  return text
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '') // Remove special characters
    .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
    .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
}

/**
 * Validates Turkish identity number (TC Kimlik No)
 */
export function isValidTCIdentity(tcNo: string): boolean {
  if (tcNo.length !== 11 || tcNo[0] === '0') return false;

  const digits = tcNo.split('').map(Number);

  // Check if all characters are digits
  if (digits.some(isNaN)) return false;

  // Calculate 10th digit
  const sum1to9 = digits.slice(0, 9).reduce((sum, digit) => sum + digit, 0);
  if (sum1to9 % 10 !== digits[9]) return false;

  // Calculate 11th digit
  const oddSum = digits[0] + digits[2] + digits[4] + digits[6] + digits[8];
  const evenSum = digits[1] + digits[3] + digits[5] + digits[7];
  if ((oddSum * 7 - evenSum) % 10 !== digits[9]) return false;

  const sum1to10 = digits.slice(0, 10).reduce((sum, digit) => sum + digit, 0);
  if (sum1to10 % 10 !== digits[10]) return false;

  return true;
}
