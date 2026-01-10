import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { SubscriptionPlan } from './subscription-plan.entity';

export type TenantStatus = 'trial' | 'active' | 'suspended' | 'cancelled';

@Entity('tenants')
export class Tenant {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  @Column({ unique: true })
  slug: string;

  @Column({ nullable: true })
  domain: string;

  @Column({ default: 'trial' })
  status: TenantStatus;

  // Subscription
  @Column({ name: 'plan_id', type: 'uuid' })
  planId: string;

  @ManyToOne(() => SubscriptionPlan, { eager: true })
  @JoinColumn({ name: 'plan_id' })
  plan: SubscriptionPlan;

  @Column({ name: 'trial_ends_at', type: 'timestamp', nullable: true })
  trialEndsAt: Date;

  @Column({ name: 'subscription_ends_at', type: 'timestamp', nullable: true })
  subscriptionEndsAt: Date;

  // Contact
  @Column({ name: 'company_name', nullable: true })
  companyName: string;

  @Column({ name: 'contact_email' })
  contactEmail: string;

  @Column({ name: 'contact_phone', nullable: true })
  contactPhone: string;

  @Column({ name: 'tax_number', nullable: true })
  taxNumber: string;

  // Address
  @Column({ type: 'jsonb', nullable: true })
  address: {
    address1: string;
    address2?: string;
    city: string;
    province: string;
    country: string;
    postalCode: string;
  };

  // Limits (from plan but can be customized)
  @Column({ type: 'jsonb' })
  limits: {
    products: number;
    orders_per_month: number;
    users: number;
    channels: number;
    warehouses: number;
    api_calls_per_day: number;
    ai_requests_monthly: number;
    storage_gb: number;
  };

  // Settings and Metadata
  @Column({ type: 'jsonb', default: {} })
  settings: Record<string, any>;

  @Column({ type: 'jsonb', default: {} })
  metadata: Record<string, any>;

  // Tracking
  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;

  @Column({ name: 'suspended_at', type: 'timestamp', nullable: true })
  suspendedAt: Date;

  @Column({ name: 'cancelled_at', type: 'timestamp', nullable: true })
  cancelledAt: Date;

  @Column({ name: 'owner_id', type: 'uuid', nullable: true })
  ownerId: string;

  // Helper methods
  isActive(): boolean {
    return this.status === 'active';
  }

  isTrial(): boolean {
    return this.status === 'trial';
  }

  isSuspended(): boolean {
    return this.status === 'suspended';
  }

  isTrialExpired(): boolean {
    if (!this.trialEndsAt) return false;
    return new Date() > this.trialEndsAt;
  }

  isSubscriptionExpired(): boolean {
    if (!this.subscriptionEndsAt) return false;
    return new Date() > this.subscriptionEndsAt;
  }
}
