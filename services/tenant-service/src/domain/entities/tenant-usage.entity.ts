import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  ManyToOne,
  JoinColumn,
  Unique,
} from 'typeorm';
import { Tenant } from './tenant.entity';

@Entity('tenant_usage')
@Unique(['tenantId', 'periodStart'])
export class TenantUsage {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ name: 'tenant_id', type: 'uuid' })
  tenantId: string;

  @ManyToOne(() => Tenant)
  @JoinColumn({ name: 'tenant_id' })
  tenant: Tenant;

  @Column({ name: 'period_start', type: 'date' })
  periodStart: Date;

  @Column({ name: 'period_end', type: 'date' })
  periodEnd: Date;

  // Usage metrics
  @Column({ name: 'products_count', default: 0 })
  productsCount: number;

  @Column({ name: 'orders_count', default: 0 })
  ordersCount: number;

  @Column({ name: 'users_count', default: 0 })
  usersCount: number;

  @Column({ name: 'channels_count', default: 0 })
  channelsCount: number;

  @Column({ name: 'api_calls_count', default: 0 })
  apiCallsCount: number;

  @Column({ name: 'ai_requests_count', default: 0 })
  aiRequestsCount: number;

  @Column({ name: 'storage_used_mb', default: 0 })
  storageUsedMb: number;

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  // Helper methods
  getTotalUsage(): Record<string, number> {
    return {
      products: this.productsCount,
      orders: this.ordersCount,
      users: this.usersCount,
      channels: this.channelsCount,
      apiCalls: this.apiCallsCount,
      aiRequests: this.aiRequestsCount,
      storageMb: this.storageUsedMb,
    };
  }
}
