import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
} from 'typeorm';

@Entity('subscription_plans')
export class SubscriptionPlan {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  @Column({ unique: true })
  slug: string;

  @Column({ type: 'text', nullable: true })
  description: string;

  // Pricing
  @Column({ name: 'price_monthly', type: 'decimal', precision: 10, scale: 2 })
  priceMonthly: number;

  @Column({ name: 'price_yearly', type: 'decimal', precision: 10, scale: 2 })
  priceYearly: number;

  @Column({ default: 'TRY', length: 3 })
  currency: string;

  // Features (stored as JSONB)
  @Column({ type: 'jsonb' })
  features: {
    products_limit: number;
    orders_limit_monthly: number;
    users_limit: number;
    channels_limit: number;
    warehouses_limit: number;
    api_calls_daily: number;
    ai_requests_monthly: number;
    storage_gb: number;
    features: {
      multi_channel: boolean;
      advanced_analytics: boolean;
      ai_content: boolean;
      whatsapp: boolean;
      priority_support: boolean;
      custom_integrations: boolean;
      white_label: boolean;
    };
  };

  // Display
  @Column({ name: 'is_public', default: true })
  isPublic: boolean;

  @Column({ name: 'sort_order', default: 0 })
  sortOrder: number;

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;

  // Helper methods
  isUnlimited(feature: string): boolean {
    return this.features[feature] === -1;
  }

  hasFeature(featureName: string): boolean {
    return this.features.features?.[featureName] === true;
  }
}
