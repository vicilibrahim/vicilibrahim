import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Tenant } from '../entities/tenant.entity';

@Injectable()
export class TenantRepository {
  constructor(
    @InjectRepository(Tenant)
    private readonly repository: Repository<Tenant>,
  ) {}

  async create(tenantData: Partial<Tenant>): Promise<Tenant> {
    const tenant = this.repository.create(tenantData);
    return this.repository.save(tenant);
  }

  async findById(id: string): Promise<Tenant | null> {
    return this.repository.findOne({
      where: { id },
      relations: ['plan'],
    });
  }

  async findBySlug(slug: string): Promise<Tenant | null> {
    return this.repository.findOne({
      where: { slug },
      relations: ['plan'],
    });
  }

  async findAll(
    page: number = 1,
    limit: number = 20,
    status?: string,
  ): Promise<{ tenants: Tenant[]; total: number }> {
    const query = this.repository.createQueryBuilder('tenant')
      .leftJoinAndSelect('tenant.plan', 'plan')
      .orderBy('tenant.created_at', 'DESC');

    if (status) {
      query.where('tenant.status = :status', { status });
    }

    query.skip((page - 1) * limit).take(limit);

    const [tenants, total] = await query.getManyAndCount();
    return { tenants, total };
  }

  async update(id: string, updates: Partial<Tenant>): Promise<Tenant | null> {
    await this.repository.update(id, updates);
    return this.findById(id);
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.repository.delete(id);
    return (result.affected ?? 0) > 0;
  }

  async existsBySlug(slug: string): Promise<boolean> {
    const count = await this.repository.count({ where: { slug } });
    return count > 0;
  }

  async existsByDomain(domain: string): Promise<boolean> {
    const count = await this.repository.count({ where: { domain } });
    return count > 0;
  }

  async countByStatus(status: string): Promise<number> {
    return this.repository.count({ where: { status } });
  }

  async findExpiredTrials(): Promise<Tenant[]> {
    return this.repository
      .createQueryBuilder('tenant')
      .where('tenant.status = :status', { status: 'trial' })
      .andWhere('tenant.trial_ends_at < NOW()')
      .getMany();
  }

  async findExpiredSubscriptions(): Promise<Tenant[]> {
    return this.repository
      .createQueryBuilder('tenant')
      .where('tenant.status = :status', { status: 'active' })
      .andWhere('tenant.subscription_ends_at < NOW()')
      .getMany();
  }
}
