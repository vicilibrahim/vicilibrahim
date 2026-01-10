/**
 * Standardized API response formats
 */

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
  timestamp: string;
}

export interface ApiErrorResponse {
  success: false;
  error: {
    code: string;
    message: string;
    details?: any;
  };
  timestamp: string;
}

export interface ValidationErrorResponse extends ApiErrorResponse {
  error: {
    code: 'VALIDATION_ERROR';
    message: string;
    details: Array<{
      field: string;
      message: string;
    }>;
  };
}

export class ApiResponseBuilder {
  static success<T>(data: T, message?: string): ApiResponse<T> {
    return {
      success: true,
      data,
      message,
      timestamp: new Date().toISOString(),
    };
  }

  static error(code: string, message: string, details?: any): ApiErrorResponse {
    return {
      success: false,
      error: {
        code,
        message,
        details,
      },
      timestamp: new Date().toISOString(),
    };
  }

  static validationError(
    errors: Array<{ field: string; message: string }>,
  ): ValidationErrorResponse {
    return {
      success: false,
      error: {
        code: 'VALIDATION_ERROR',
        message: 'Validation failed',
        details: errors,
      },
      timestamp: new Date().toISOString(),
    };
  }
}
