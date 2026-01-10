/**
 * Tenant-related types for multi-tenant SaaS
 */

export type TenantStatus = 'trial' | 'active' | 'suspended' | 'cancelled';

export interface TenantLimits {
  products: number;
  orders_per_month: number;
  users: number;
  channels: number;
  warehouses: number;
  api_calls_per_day: number;
  ai_requests_monthly: number;
  storage_gb: number;
}

export interface SubscriptionPlanFeatures extends TenantLimits {
  features: {
    multi_channel: boolean;
    advanced_analytics: boolean;
    ai_content: boolean;
    whatsapp: boolean;
    priority_support: boolean;
    custom_integrations: boolean;
    white_label: boolean;
  };
}

export interface Tenant {
  id: string;
  name: string;
  slug: string;
  domain?: string;
  status: TenantStatus;

  // Subscription
  planId: string;
  trialEndsAt?: Date;
  subscriptionEndsAt?: Date;

  // Contact
  companyName?: string;
  contactEmail: string;
  contactPhone?: string;
  taxNumber?: string;

  // Address
  address?: {
    address1: string;
    address2?: string;
    city: string;
    province: string;
    country: string;
    postalCode: string;
  };

  // Limits
  limits: TenantLimits;

  // Metadata
  settings: Record<string, any>;
  metadata: Record<string, any>;

  // Tracking
  createdAt: Date;
  updatedAt: Date;
  suspendedAt?: Date;
  cancelledAt?: Date;

  ownerId?: string;
}

export interface SubscriptionPlan {
  id: string;
  name: string;
  slug: string;
  description?: string;

  // Pricing
  priceMonthly: number;
  priceYearly: number;
  currency: string;

  // Features
  features: SubscriptionPlanFeatures;

  // Display
  isPublic: boolean;
  sortOrder: number;

  createdAt: Date;
  updatedAt: Date;
}

export interface TenantUsage {
  id: string;
  tenantId: string;
  periodStart: Date;
  periodEnd: Date;

  // Usage metrics
  productsCount: number;
  ordersCount: number;
  usersCount: number;
  channelsCount: number;
  apiCallsCount: number;
  aiRequestsCount: number;
  storageUsedMb: number;

  createdAt: Date;
}

export interface SuperAdmin {
  id: string;
  email: string;
  name?: string;
  role: 'super_admin' | 'support' | 'billing';
  isActive: boolean;
  lastLoginAt?: Date;
  createdAt: Date;
}
