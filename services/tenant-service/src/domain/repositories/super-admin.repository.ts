import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { SuperAdmin } from '../entities/super-admin.entity';

@Injectable()
export class SuperAdminRepository {
  constructor(
    @InjectRepository(SuperAdmin)
    private readonly repository: Repository<SuperAdmin>,
  ) {}

  async create(adminData: Partial<SuperAdmin>): Promise<SuperAdmin> {
    const admin = this.repository.create(adminData);
    return this.repository.save(admin);
  }

  async findById(id: string): Promise<SuperAdmin | null> {
    return this.repository.findOne({ where: { id } });
  }

  async findByEmail(email: string): Promise<SuperAdmin | null> {
    return this.repository.findOne({ where: { email } });
  }

  async findAll(): Promise<SuperAdmin[]> {
    return this.repository.find({
      order: { createdAt: 'DESC' },
    });
  }

  async update(id: string, updates: Partial<SuperAdmin>): Promise<SuperAdmin | null> {
    await this.repository.update(id, updates);
    return this.findById(id);
  }

  async updateLastLogin(id: string): Promise<void> {
    await this.repository.update(id, { lastLoginAt: new Date() });
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.repository.delete(id);
    return (result.affected ?? 0) > 0;
  }
}
