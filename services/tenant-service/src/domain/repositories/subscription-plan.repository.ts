import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { SubscriptionPlan } from '../entities/subscription-plan.entity';

@Injectable()
export class SubscriptionPlanRepository {
  constructor(
    @InjectRepository(SubscriptionPlan)
    private readonly repository: Repository<SubscriptionPlan>,
  ) {}

  async create(planData: Partial<SubscriptionPlan>): Promise<SubscriptionPlan> {
    const plan = this.repository.create(planData);
    return this.repository.save(plan);
  }

  async findById(id: string): Promise<SubscriptionPlan | null> {
    return this.repository.findOne({ where: { id } });
  }

  async findBySlug(slug: string): Promise<SubscriptionPlan | null> {
    return this.repository.findOne({ where: { slug } });
  }

  async findAll(publicOnly: boolean = false): Promise<SubscriptionPlan[]> {
    const query = this.repository.createQueryBuilder('plan')
      .orderBy('plan.sort_order', 'ASC')
      .addOrderBy('plan.price_monthly', 'ASC');

    if (publicOnly) {
      query.where('plan.is_public = :isPublic', { isPublic: true });
    }

    return query.getMany();
  }

  async update(id: string, updates: Partial<SubscriptionPlan>): Promise<SubscriptionPlan | null> {
    await this.repository.update(id, updates);
    return this.findById(id);
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.repository.delete(id);
    return (result.affected ?? 0) > 0;
  }
}
