import { Injectable } from '@nestjs/common';
import * as slugify from 'slugify';
import { TenantRepository } from '../repositories/tenant.repository';
import { SubscriptionPlanRepository } from '../repositories/subscription-plan.repository';
import { ProvisioningService } from './provisioning.service';
import { Tenant } from '../entities/tenant.entity';
import { NotFoundError, ConflictError } from '@commercehub/shared';
import { PaginatedResponse } from '@commercehub/shared';

@Injectable()
export class TenantService {
  constructor(
    private readonly tenantRepository: TenantRepository,
    private readonly planRepository: SubscriptionPlanRepository,
    private readonly provisioningService: ProvisioningService,
  ) {}

  /**
   * Create a new tenant with automatic provisioning
   */
  async createTenant(data: {
    name: string;
    slug?: string;
    contactEmail: string;
    companyName?: string;
    contactPhone?: string;
    planSlug: string;
    trialDays?: number;
  }): Promise<Tenant> {
    // Generate slug if not provided
    const slug = data.slug || this.generateSlug(data.name);

    // Check if slug is already taken
    const slugExists = await this.tenantRepository.existsBySlug(slug);
    if (slugExists) {
      throw new ConflictError(`Tenant with slug '${slug}' already exists`);
    }

    // Get subscription plan
    const plan = await this.planRepository.findBySlug(data.planSlug);
    if (!plan) {
      throw new NotFoundError('Subscription plan', data.planSlug);
    }

    // Calculate trial end date
    const trialDays = data.trialDays || 14;
    const trialEndsAt = new Date();
    trialEndsAt.setDate(trialEndsAt.getDate() + trialDays);

    // Extract limits from plan
    const limits = {
      products: plan.features.products_limit,
      orders_per_month: plan.features.orders_limit_monthly,
      users: plan.features.users_limit,
      channels: plan.features.channels_limit,
      warehouses: plan.features.warehouses_limit,
      api_calls_per_day: plan.features.api_calls_daily,
      ai_requests_monthly: plan.features.ai_requests_monthly,
      storage_gb: plan.features.storage_gb,
    };

    // Create tenant record
    const tenant = await this.tenantRepository.create({
      name: data.name,
      slug,
      companyName: data.companyName,
      contactEmail: data.contactEmail,
      contactPhone: data.contactPhone,
      planId: plan.id,
      status: 'trial',
      trialEndsAt,
      limits,
      settings: {},
      metadata: {},
    });

    // Provision tenant infrastructure (create database schemas/collections)
    try {
      await this.provisioningService.provisionTenant(tenant.id, slug);
    } catch (error) {
      // Rollback tenant creation if provisioning fails
      await this.tenantRepository.delete(tenant.id);
      throw error;
    }

    return tenant;
  }

  /**
   * Get tenant by ID
   */
  async getTenantById(id: string): Promise<Tenant> {
    const tenant = await this.tenantRepository.findById(id);
    if (!tenant) {
      throw new NotFoundError('Tenant', id);
    }
    return tenant;
  }

  /**
   * Get tenant by slug
   */
  async getTenantBySlug(slug: string): Promise<Tenant> {
    const tenant = await this.tenantRepository.findBySlug(slug);
    if (!tenant) {
      throw new NotFoundError('Tenant', slug);
    }
    return tenant;
  }

  /**
   * Get all tenants with pagination
   */
  async getAllTenants(
    page: number = 1,
    limit: number = 20,
    status?: string,
  ): Promise<PaginatedResponse<Tenant>> {
    const { tenants, total } = await this.tenantRepository.findAll(page, limit, status);
    return {
      data: tenants,
      pagination: {
        page,
        limit,
        total,
        totalPages: Math.ceil(total / limit),
      },
    };
  }

  /**
   * Update tenant
   */
  async updateTenant(id: string, updates: Partial<Tenant>): Promise<Tenant> {
    const tenant = await this.getTenantById(id);

    // Check slug uniqueness if changing
    if (updates.slug && updates.slug !== tenant.slug) {
      const slugExists = await this.tenantRepository.existsBySlug(updates.slug);
      if (slugExists) {
        throw new ConflictError('Slug already in use');
      }
    }

    // Check domain uniqueness if changing
    if (updates.domain && updates.domain !== tenant.domain) {
      const domainExists = await this.tenantRepository.existsByDomain(updates.domain);
      if (domainExists) {
        throw new ConflictError('Domain already in use');
      }
    }

    const updated = await this.tenantRepository.update(id, updates);
    if (!updated) {
      throw new NotFoundError('Tenant', id);
    }

    return updated;
  }

  /**
   * Suspend tenant
   */
  async suspendTenant(id: string, reason?: string): Promise<Tenant> {
    const tenant = await this.getTenantById(id);

    if (tenant.status === 'suspended') {
      throw new ConflictError('Tenant is already suspended');
    }

    return this.updateTenant(id, {
      status: 'suspended',
      suspendedAt: new Date(),
      metadata: {
        ...tenant.metadata,
        suspensionReason: reason,
      },
    });
  }

  /**
   * Activate tenant (unsuspend or activate from trial)
   */
  async activateTenant(id: string): Promise<Tenant> {
    const tenant = await this.getTenantById(id);

    const subscriptionEndsAt = new Date();
    subscriptionEndsAt.setMonth(subscriptionEndsAt.getMonth() + 1); // 1 month subscription

    return this.updateTenant(id, {
      status: 'active',
      subscriptionEndsAt,
      suspendedAt: null,
    });
  }

  /**
   * Cancel tenant subscription
   */
  async cancelTenant(id: string): Promise<Tenant> {
    return this.updateTenant(id, {
      status: 'cancelled',
      cancelledAt: new Date(),
    });
  }

  /**
   * Delete tenant (hard delete)
   */
  async deleteTenant(id: string): Promise<void> {
    const tenant = await this.getTenantById(id);

    // Deprovision tenant infrastructure
    await this.provisioningService.deprovisionTenant(tenant.id, tenant.slug);

    // Delete tenant record
    await this.tenantRepository.delete(id);
  }

  /**
   * Change tenant subscription plan
   */
  async changePlan(tenantId: string, newPlanSlug: string): Promise<Tenant> {
    const tenant = await this.getTenantById(tenantId);
    const newPlan = await this.planRepository.findBySlug(newPlanSlug);

    if (!newPlan) {
      throw new NotFoundError('Subscription plan', newPlanSlug);
    }

    // Update limits based on new plan
    const newLimits = {
      products: newPlan.features.products_limit,
      orders_per_month: newPlan.features.orders_limit_monthly,
      users: newPlan.features.users_limit,
      channels: newPlan.features.channels_limit,
      warehouses: newPlan.features.warehouses_limit,
      api_calls_per_day: newPlan.features.api_calls_daily,
      ai_requests_monthly: newPlan.features.ai_requests_monthly,
      storage_gb: newPlan.features.storage_gb,
    };

    return this.updateTenant(tenantId, {
      planId: newPlan.id,
      limits: newLimits,
    });
  }

  /**
   * Get tenant statistics for super admin dashboard
   */
  async getTenantStats(): Promise<{
    total: number;
    active: number;
    trial: number;
    suspended: number;
    cancelled: number;
  }> {
    const [total, active, trial, suspended, cancelled] = await Promise.all([
      this.tenantRepository.countByStatus(''),
      this.tenantRepository.countByStatus('active'),
      this.tenantRepository.countByStatus('trial'),
      this.tenantRepository.countByStatus('suspended'),
      this.tenantRepository.countByStatus('cancelled'),
    ]);

    return { total, active, trial, suspended, cancelled };
  }

  /**
   * Generate unique slug from name
   */
  private generateSlug(name: string): string {
    return slugify(name, {
      lower: true,
      strict: true,
      remove: /[*+~.()'"!:@]/g,
    });
  }

  /**
   * Process expired trials (background job)
   */
  async processExpiredTrials(): Promise<void> {
    const expiredTrials = await this.tenantRepository.findExpiredTrials();

    for (const tenant of expiredTrials) {
      await this.suspendTenant(tenant.id, 'Trial period expired');
    }
  }

  /**
   * Process expired subscriptions (background job)
   */
  async processExpiredSubscriptions(): Promise<void> {
    const expiredSubs = await this.tenantRepository.findExpiredSubscriptions();

    for (const tenant of expiredSubs) {
      await this.suspendTenant(tenant.id, 'Subscription expired');
    }
  }
}
