/**
 * Order-related types shared across services
 */

import { Address } from './common';

export interface OrderItem {
  id: string;
  productId: string;
  variantId?: string;
  sku: string;
  name: string;
  quantity: number;
  unitPrice: number;
  taxRate: number;
  discountAmount: number;
  total: number;
  metadata?: Record<string, any>;
}

export interface OrderFinancials {
  subtotal: number;
  taxTotal: number;
  shippingTotal: number;
  discountTotal: number;
  total: number;
  currency: string;
}

export type OrderStatus =
  | 'pending'
  | 'confirmed'
  | 'processing'
  | 'shipped'
  | 'delivered'
  | 'cancelled';

export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded';

export type FulfillmentStatus =
  | 'unfulfilled'
  | 'partial'
  | 'fulfilled'
  | 'returned';

export interface Order {
  id: string;
  tenantId: string;
  orderNumber: string;
  channelType: 'marketplace' | 'ecommerce';
  channelId: string;
  channelOrderId?: string;
  customerId: string;
  status: OrderStatus;
  paymentStatus: PaymentStatus;
  fulfillmentStatus: FulfillmentStatus;
  financials: OrderFinancials;
  items: OrderItem[];
  shippingAddress: Address;
  billingAddress?: Address;
  shippingMethod?: string;
  trackingNumber?: string;
  carrier?: string;
  notes?: string;
  customerNote?: string;
  tags?: string[];
  metadata?: Record<string, any>;
  createdAt: Date;
  updatedAt: Date;
  cancelledAt?: Date;
  shippedAt?: Date;
  deliveredAt?: Date;
}

export interface OrderTimelineEvent {
  id: string;
  orderId: string;
  eventType: string;
  description: string;
  metadata?: Record<string, any>;
  createdBy?: string;
  createdAt: Date;
}

export interface OrderReturn {
  id: string;
  tenantId: string;
  orderId: string;
  returnNumber: string;
  status: 'requested' | 'approved' | 'received' | 'refunded' | 'rejected';
  reason: string;
  customerNote?: string;
  adminNote?: string;
  refundAmount: number;
  items: Array<{
    orderItemId: string;
    quantity: number;
    reason: string;
  }>;
  createdAt: Date;
  updatedAt: Date;
  approvedAt?: Date;
  refundedAt?: Date;
}
