/**
 * Common types used across all microservices
 */

export interface PaginationParams {
  page?: number;
  limit?: number;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
  };
}

export interface BaseEntity {
  id: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface Address {
  firstName: string;
  lastName: string;
  company?: string;
  address1: string;
  address2?: string;
  city: string;
  province: string;
  country: string;
  postalCode: string;
  phone: string;
  email?: string;
}

export interface Money {
  amount: number;
  currency: string;
}

export type EntityStatus = 'active' | 'inactive' | 'archived' | 'deleted';

export interface Metadata {
  [key: string]: any;
}

export interface ErrorResponse {
  statusCode: number;
  message: string;
  error?: string;
  timestamp: string;
  path?: string;
}
