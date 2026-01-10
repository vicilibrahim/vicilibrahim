/**
 * Centralized error handling utilities
 */

export class AppError extends Error {
  constructor(
    public statusCode: number,
    public message: string,
    public code?: string,
    public details?: any,
  ) {
    super(message);
    this.name = this.constructor.name;
    Error.captureStackTrace(this, this.constructor);
  }
}

export class ValidationError extends AppError {
  constructor(message: string, details?: any) {
    super(400, message, 'VALIDATION_ERROR', details);
  }
}

export class NotFoundError extends AppError {
  constructor(resource: string, identifier?: string) {
    const message = identifier
      ? `${resource} with identifier '${identifier}' not found`
      : `${resource} not found`;
    super(404, message, 'NOT_FOUND');
  }
}

export class UnauthorizedError extends AppError {
  constructor(message: string = 'Unauthorized') {
    super(401, message, 'UNAUTHORIZED');
  }
}

export class ForbiddenError extends AppError {
  constructor(message: string = 'Forbidden') {
    super(403, message, 'FORBIDDEN');
  }
}

export class ConflictError extends AppError {
  constructor(message: string) {
    super(409, message, 'CONFLICT');
  }
}

export class InternalServerError extends AppError {
  constructor(message: string = 'Internal server error') {
    super(500, message, 'INTERNAL_SERVER_ERROR');
  }
}

export class BadRequestError extends AppError {
  constructor(message: string) {
    super(400, message, 'BAD_REQUEST');
  }
}

export class ServiceUnavailableError extends AppError {
  constructor(serviceName: string) {
    super(503, `${serviceName} is currently unavailable`, 'SERVICE_UNAVAILABLE');
  }
}

/**
 * Error response formatter
 */
export interface ErrorResponse {
  success: false;
  error: {
    code: string;
    message: string;
    details?: any;
  };
  timestamp: string;
  path?: string;
}

export function formatError(error: Error | AppError, path?: string): ErrorResponse {
  if (error instanceof AppError) {
    return {
      success: false,
      error: {
        code: error.code || 'UNKNOWN_ERROR',
        message: error.message,
        details: error.details,
      },
      timestamp: new Date().toISOString(),
      path,
    };
  }

  // Generic error
  return {
    success: false,
    error: {
      code: 'INTERNAL_SERVER_ERROR',
      message: error.message || 'An unexpected error occurred',
    },
    timestamp: new Date().toISOString(),
    path,
  };
}

/**
 * Check if error is operational (expected) vs programming error
 */
export function isOperationalError(error: Error): boolean {
  return error instanceof AppError;
}
