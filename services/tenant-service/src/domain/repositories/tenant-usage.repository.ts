import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { TenantUsage } from '../entities/tenant-usage.entity';

@Injectable()
export class TenantUsageRepository {
  constructor(
    @InjectRepository(TenantUsage)
    private readonly repository: Repository<TenantUsage>,
  ) {}

  async findOrCreate(tenantId: string, periodStart: Date, periodEnd: Date): Promise<TenantUsage> {
    let usage = await this.repository.findOne({
      where: { tenantId, periodStart },
    });

    if (!usage) {
      usage = this.repository.create({
        tenantId,
        periodStart,
        periodEnd,
        productsCount: 0,
        ordersCount: 0,
        usersCount: 0,
        channelsCount: 0,
        apiCallsCount: 0,
        aiRequestsCount: 0,
        storageUsedMb: 0,
      });
      usage = await this.repository.save(usage);
    }

    return usage;
  }

  async getCurrentPeriodUsage(tenantId: string): Promise<TenantUsage> {
    const today = new Date();
    const periodStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const periodEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    return this.findOrCreate(tenantId, periodStart, periodEnd);
  }

  async increment(tenantId: string, metric: string, amount: number = 1): Promise<void> {
    const usage = await this.getCurrentPeriodUsage(tenantId);

    const columnMap: Record<string, string> = {
      products: 'products_count',
      orders: 'orders_count',
      users: 'users_count',
      channels: 'channels_count',
      apiCalls: 'api_calls_count',
      aiRequests: 'ai_requests_count',
      storage: 'storage_used_mb',
    };

    const column = columnMap[metric];
    if (!column) {
      throw new Error(`Invalid metric: ${metric}`);
    }

    await this.repository
      .createQueryBuilder()
      .update(TenantUsage)
      .set({ [column]: () => `${column} + ${amount}` })
      .where('id = :id', { id: usage.id })
      .execute();
  }

  async findByTenant(tenantId: string, limit: number = 12): Promise<TenantUsage[]> {
    return this.repository.find({
      where: { tenantId },
      order: { periodStart: 'DESC' },
      take: limit,
    });
  }

  async getTotalUsageForAllTenants(): Promise<any> {
    const result = await this.repository
      .createQueryBuilder('usage')
      .select('SUM(usage.products_count)', 'totalProducts')
      .addSelect('SUM(usage.orders_count)', 'totalOrders')
      .addSelect('SUM(usage.api_calls_count)', 'totalApiCalls')
      .addSelect('SUM(usage.storage_used_mb)', 'totalStorageMb')
      .getRawOne();

    return {
      totalProducts: parseInt(result.totalProducts) || 0,
      totalOrders: parseInt(result.totalOrders) || 0,
      totalApiCalls: parseInt(result.totalApiCalls) || 0,
      totalStorageMb: parseInt(result.totalStorageMb) || 0,
    };
  }
}
