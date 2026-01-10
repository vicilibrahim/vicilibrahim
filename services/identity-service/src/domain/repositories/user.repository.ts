import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { User } from '../entities/user.entity';

@Injectable()
export class UserRepository {
  constructor(
    @InjectRepository(User)
    private readonly repository: Repository<User>,
  ) {}

  async create(userData: Partial<User>): Promise<User> {
    const user = this.repository.create(userData);
    return this.repository.save(user);
  }

  async findById(id: string): Promise<User | null> {
    return this.repository.findOne({
      where: { id },
      relations: ['roles', 'roles.permissions'],
    });
  }

  async findByEmail(email: string): Promise<User | null> {
    return this.repository.findOne({
      where: { email },
      relations: ['roles', 'roles.permissions'],
    });
  }

  async findAll(
    page: number = 1,
    limit: number = 20,
  ): Promise<{ users: User[]; total: number }> {
    const [users, total] = await this.repository.findAndCount({
      skip: (page - 1) * limit,
      take: limit,
      relations: ['roles'],
      order: { createdAt: 'DESC' },
    });
    return { users, total };
  }

  async update(id: string, updates: Partial<User>): Promise<User | null> {
    await this.repository.update(id, updates);
    return this.findById(id);
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.repository.delete(id);
    return (result.affected ?? 0) > 0;
  }

  async assignRole(userId: string, roleId: string): Promise<void> {
    await this.repository
      .createQueryBuilder()
      .relation(User, 'roles')
      .of(userId)
      .add(roleId);
  }

  async removeRole(userId: string, roleId: string): Promise<void> {
    await this.repository
      .createQueryBuilder()
      .relation(User, 'roles')
      .of(userId)
      .remove(roleId);
  }

  async existsByEmail(email: string): Promise<boolean> {
    const count = await this.repository.count({ where: { email } });
    return count > 0;
  }
}
