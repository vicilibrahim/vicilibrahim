import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Permission } from '../entities/permission.entity';

@Injectable()
export class PermissionRepository {
  constructor(
    @InjectRepository(Permission)
    private readonly repository: Repository<Permission>,
  ) {}

  async create(permissionData: Partial<Permission>): Promise<Permission> {
    const permission = this.repository.create(permissionData);
    return this.repository.save(permission);
  }

  async findById(id: string): Promise<Permission | null> {
    return this.repository.findOne({ where: { id } });
  }

  async findByName(name: string): Promise<Permission | null> {
    return this.repository.findOne({ where: { name } });
  }

  async findAll(): Promise<Permission[]> {
    return this.repository.find({
      order: { resource: 'ASC', action: 'ASC' },
    });
  }

  async findByResource(resource: string): Promise<Permission[]> {
    return this.repository.find({
      where: { resource },
      order: { action: 'ASC' },
    });
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.repository.delete(id);
    return (result.affected ?? 0) > 0;
  }
}
