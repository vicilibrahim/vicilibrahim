/**
 * Event types for inter-service communication via RabbitMQ
 */

export interface BaseEvent {
  eventId: string;
  eventType: string;
  timestamp: Date;
  tenantId: string;
  metadata?: Record<string, any>;
}

// Identity Service Events
export interface UserCreatedEvent extends BaseEvent {
  eventType: 'user.created';
  payload: {
    userId: string;
    email: string;
    firstName: string;
    lastName: string;
  };
}

export interface UserUpdatedEvent extends BaseEvent {
  eventType: 'user.updated';
  payload: {
    userId: string;
    changes: Record<string, any>;
  };
}

export interface UserDeletedEvent extends BaseEvent {
  eventType: 'user.deleted';
  payload: {
    userId: string;
  };
}

export interface UserLoginEvent extends BaseEvent {
  eventType: 'user.login';
  payload: {
    userId: string;
    email: string;
    ip?: string;
    userAgent?: string;
  };
}

// Product Service Events
export interface ProductCreatedEvent extends BaseEvent {
  eventType: 'product.created';
  payload: {
    productId: string;
    sku: string;
    name: string;
  };
}

export interface ProductUpdatedEvent extends BaseEvent {
  eventType: 'product.updated';
  payload: {
    productId: string;
    sku: string;
    changes: Record<string, any>;
  };
}

export interface ProductDeletedEvent extends BaseEvent {
  eventType: 'product.deleted';
  payload: {
    productId: string;
    sku: string;
  };
}

export interface ProductPublishedEvent extends BaseEvent {
  eventType: 'product.published';
  payload: {
    productId: string;
    sku: string;
    channels: string[];
  };
}

// Inventory Service Events
export interface InventoryUpdatedEvent extends BaseEvent {
  eventType: 'inventory.updated';
  payload: {
    inventoryItemId: string;
    sku: string;
    warehouseId: string;
    availableQuantity: number;
    reservedQuantity: number;
  };
}

export interface InventoryLowStockEvent extends BaseEvent {
  eventType: 'inventory.low_stock';
  payload: {
    inventoryItemId: string;
    sku: string;
    warehouseId: string;
    currentQuantity: number;
    threshold: number;
  };
}

export interface InventoryReservedEvent extends BaseEvent {
  eventType: 'inventory.reserved';
  payload: {
    reservationId: string;
    inventoryItemId: string;
    orderId: string;
    quantity: number;
    warehouseId: string;
  };
}

export interface InventoryReleasedEvent extends BaseEvent {
  eventType: 'inventory.released';
  payload: {
    reservationId: string;
    inventoryItemId: string;
    orderId: string;
    quantity: number;
  };
}

// Order Service Events
export interface OrderCreatedEvent extends BaseEvent {
  eventType: 'order.created';
  payload: {
    orderId: string;
    orderNumber: string;
    customerId: string;
    total: number;
    currency: string;
    items: Array<{
      sku: string;
      quantity: number;
    }>;
  };
}

export interface OrderConfirmedEvent extends BaseEvent {
  eventType: 'order.confirmed';
  payload: {
    orderId: string;
    orderNumber: string;
  };
}

export interface OrderShippedEvent extends BaseEvent {
  eventType: 'order.shipped';
  payload: {
    orderId: string;
    orderNumber: string;
    trackingNumber: string;
    carrier: string;
  };
}

export interface OrderDeliveredEvent extends BaseEvent {
  eventType: 'order.delivered';
  payload: {
    orderId: string;
    orderNumber: string;
    deliveredAt: Date;
  };
}

export interface OrderCancelledEvent extends BaseEvent {
  eventType: 'order.cancelled';
  payload: {
    orderId: string;
    orderNumber: string;
    reason: string;
  };
}

// Channel Service Events
export interface ChannelConnectedEvent extends BaseEvent {
  eventType: 'channel.connected';
  payload: {
    channelId: string;
    channelType: string;
    provider: string;
  };
}

export interface ChannelSyncStartedEvent extends BaseEvent {
  eventType: 'channel.sync.started';
  payload: {
    channelId: string;
    syncJobId: string;
    syncType: 'products' | 'orders' | 'inventory';
  };
}

export interface ChannelSyncCompletedEvent extends BaseEvent {
  eventType: 'channel.sync.completed';
  payload: {
    channelId: string;
    syncJobId: string;
    syncType: 'products' | 'orders' | 'inventory';
    totalItems: number;
    processedItems: number;
    failedItems: number;
  };
}

export interface ChannelOrderReceivedEvent extends BaseEvent {
  eventType: 'channel.order.received';
  payload: {
    channelId: string;
    channelOrderId: string;
    orderData: any;
  };
}

// Union type of all events
export type DomainEvent =
  | UserCreatedEvent
  | UserUpdatedEvent
  | UserDeletedEvent
  | UserLoginEvent
  | ProductCreatedEvent
  | ProductUpdatedEvent
  | ProductDeletedEvent
  | ProductPublishedEvent
  | InventoryUpdatedEvent
  | InventoryLowStockEvent
  | InventoryReservedEvent
  | InventoryReleasedEvent
  | OrderCreatedEvent
  | OrderConfirmedEvent
  | OrderShippedEvent
  | OrderDeliveredEvent
  | OrderCancelledEvent
  | ChannelConnectedEvent
  | ChannelSyncStartedEvent
  | ChannelSyncCompletedEvent
  | ChannelOrderReceivedEvent;
