import { Injectable } from '@nestjs/common';
import { TenantUsageRepository } from '../repositories/tenant-usage.repository';
import { TenantRepository } from '../repositories/tenant.repository';
import { ForbiddenError, NotFoundError } from '@commercehub/shared';
import { TenantUsage } from '../entities/tenant-usage.entity';

@Injectable()
export class UsageTrackerService {
  constructor(
    private readonly usageRepository: TenantUsageRepository,
    private readonly tenantRepository: TenantRepository,
  ) {}

  /**
   * Check if tenant has exceeded a specific limit
   * Throws ForbiddenError if limit exceeded
   */
  async checkLimit(tenantId: string, limitType: string): Promise<void> {
    const tenant = await this.tenantRepository.findById(tenantId);
    if (!tenant) {
      throw new NotFoundError('Tenant', tenantId);
    }

    // Get current usage
    const usage = await this.usageRepository.getCurrentPeriodUsage(tenantId);

    // Map limit types to usage fields and tenant limits
    const limitMap: Record<string, { usageField: string; limitField: string }> = {
      products: { usageField: 'productsCount', limitField: 'products' },
      orders: { usageField: 'ordersCount', limitField: 'orders_per_month' },
      users: { usageField: 'usersCount', limitField: 'users' },
      channels: { usageField: 'channelsCount', limitField: 'channels' },
      apiCalls: { usageField: 'apiCallsCount', limitField: 'api_calls_per_day' },
      aiRequests: { usageField: 'aiRequestsCount', limitField: 'ai_requests_monthly' },
    };

    const mapping = limitMap[limitType];
    if (!mapping) {
      throw new Error(`Invalid limit type: ${limitType}`);
    }

    const limit = tenant.limits[mapping.limitField];
    const currentUsage = usage[mapping.usageField];

    // -1 means unlimited
    if (limit === -1) {
      return;
    }

    if (currentUsage >= limit) {
      throw new ForbiddenError(
        `${limitType} limit exceeded. Current: ${currentUsage}, Limit: ${limit}`,
      );
    }
  }

  /**
   * Track usage by incrementing a metric
   */
  async track(tenantId: string, metric: string, amount: number = 1): Promise<void> {
    await this.usageRepository.increment(tenantId, metric, amount);
  }

  /**
   * Track product creation (with limit check)
   */
  async trackProductCreation(tenantId: string): Promise<void> {
    await this.checkLimit(tenantId, 'products');
    await this.track(tenantId, 'products', 1);
  }

  /**
   * Track order creation (with limit check)
   */
  async trackOrderCreation(tenantId: string): Promise<void> {
    await this.checkLimit(tenantId, 'orders');
    await this.track(tenantId, 'orders', 1);
  }

  /**
   * Track user creation (with limit check)
   */
  async trackUserCreation(tenantId: string): Promise<void> {
    await this.checkLimit(tenantId, 'users');
    await this.track(tenantId, 'users', 1);
  }

  /**
   * Track API call (with limit check)
   */
  async trackApiCall(tenantId: string): Promise<void> {
    await this.checkLimit(tenantId, 'apiCalls');
    await this.track(tenantId, 'apiCalls', 1);
  }

  /**
   * Track AI request (with limit check)
   */
  async trackAiRequest(tenantId: string): Promise<void> {
    await this.checkLimit(tenantId, 'aiRequests');
    await this.track(tenantId, 'aiRequests', 1);
  }

  /**
   * Get current usage for tenant
   */
  async getCurrentUsage(tenantId: string): Promise<TenantUsage> {
    return this.usageRepository.getCurrentPeriodUsage(tenantId);
  }

  /**
   * Get usage history for tenant
   */
  async getUsageHistory(tenantId: string, limit: number = 12): Promise<TenantUsage[]> {
    return this.usageRepository.findByTenant(tenantId, limit);
  }

  /**
   * Get total platform usage (for super admin dashboard)
   */
  async getTotalPlatformUsage(): Promise<any> {
    return this.usageRepository.getTotalUsageForAllTenants();
  }

  /**
   * Get usage percentage for a tenant
   */
  async getUsagePercentage(tenantId: string): Promise<Record<string, number>> {
    const tenant = await this.tenantRepository.findById(tenantId);
    if (!tenant) {
      throw new NotFoundError('Tenant', tenantId);
    }

    const usage = await this.getCurrentUsage(tenantId);

    const calculatePercentage = (used: number, limit: number): number => {
      if (limit === -1) return 0; // Unlimited
      if (limit === 0) return 100;
      return Math.min(Math.round((used / limit) * 100), 100);
    };

    return {
      products: calculatePercentage(usage.productsCount, tenant.limits.products),
      orders: calculatePercentage(usage.ordersCount, tenant.limits.orders_per_month),
      users: calculatePercentage(usage.usersCount, tenant.limits.users),
      channels: calculatePercentage(usage.channelsCount, tenant.limits.channels),
      apiCalls: calculatePercentage(usage.apiCallsCount, tenant.limits.api_calls_per_day),
      aiRequests: calculatePercentage(usage.aiRequestsCount, tenant.limits.ai_requests_monthly),
    };
  }
}
