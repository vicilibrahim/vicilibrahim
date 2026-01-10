/**
 * Product-related types shared across services
 */

export interface ProductVariant {
  id: string;
  sku: string;
  barcode?: string;
  attributes: Record<string, string>;
  price: number;
  compareAtPrice?: number;
  inventoryItemId: string;
}

export interface ProductMedia {
  id: string;
  url: string;
  type: 'image' | 'video';
  position: number;
  alt?: string;
}

export interface ProductSEO {
  metaTitle?: string;
  metaDescription?: string;
  keywords?: string[];
}

export interface ChannelMapping {
  channelId: string;
  channelType: 'marketplace' | 'ecommerce';
  externalId: string;
  status: 'active' | 'inactive' | 'error';
  lastSyncAt?: Date;
}

export interface Product {
  id: string;
  tenantId: string;
  sku: string;
  barcode?: string;
  name: string;
  slug: string;
  description?: string;
  shortDescription?: string;
  categoryId: string;
  brand?: string;
  basePrice: number;
  currency: string;
  taxRate: number;
  status: 'draft' | 'active' | 'archived';
  variants: ProductVariant[];
  media: ProductMedia[];
  seo?: ProductSEO;
  channelMappings: ChannelMapping[];
  createdAt: Date;
  updatedAt: Date;
}

export interface Category {
  id: string;
  tenantId: string;
  name: string;
  slug: string;
  description?: string;
  parentId?: string;
  path: string;
  level: number;
  image?: string;
  isActive: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export type ProductStatus = 'draft' | 'active' | 'archived';
