import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Role } from '../entities/role.entity';

@Injectable()
export class RoleRepository {
  constructor(
    @InjectRepository(Role)
    private readonly repository: Repository<Role>,
  ) {}

  async create(roleData: Partial<Role>): Promise<Role> {
    const role = this.repository.create(roleData);
    return this.repository.save(role);
  }

  async findById(id: string): Promise<Role | null> {
    return this.repository.findOne({
      where: { id },
      relations: ['permissions'],
    });
  }

  async findByName(name: string): Promise<Role | null> {
    return this.repository.findOne({
      where: { name },
      relations: ['permissions'],
    });
  }

  async findAll(): Promise<Role[]> {
    return this.repository.find({
      relations: ['permissions'],
      order: { name: 'ASC' },
    });
  }

  async update(id: string, updates: Partial<Role>): Promise<Role | null> {
    await this.repository.update(id, updates);
    return this.findById(id);
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.repository.delete(id);
    return (result.affected ?? 0) > 0;
  }

  async assignPermission(roleId: string, permissionId: string): Promise<void> {
    await this.repository
      .createQueryBuilder()
      .relation(Role, 'permissions')
      .of(roleId)
      .add(permissionId);
  }

  async removePermission(roleId: string, permissionId: string): Promise<void> {
    await this.repository
      .createQueryBuilder()
      .relation(Role, 'permissions')
      .of(roleId)
      .remove(permissionId);
  }
}
