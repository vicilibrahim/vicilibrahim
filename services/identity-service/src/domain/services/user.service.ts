import { Injectable } from '@nestjs/common';
import { UserRepository } from '../repositories/user.repository';
import { RoleRepository } from '../repositories/role.repository';
import { User } from '../entities/user.entity';
import { Role } from '../entities/role.entity';
import { NotFoundError, ConflictError } from '@commercehub/shared';
import { PaginatedResponse } from '@commercehub/shared';

@Injectable()
export class UserService {
  constructor(
    private readonly userRepository: UserRepository,
    private readonly roleRepository: RoleRepository,
  ) {}

  /**
   * Get user by ID
   */
  async getUserById(id: string): Promise<User> {
    const user = await this.userRepository.findById(id);
    if (!user) {
      throw new NotFoundError('User', id);
    }
    return user;
  }

  /**
   * Get user by email
   */
  async getUserByEmail(email: string): Promise<User> {
    const user = await this.userRepository.findByEmail(email);
    if (!user) {
      throw new NotFoundError('User', email);
    }
    return user;
  }

  /**
   * Get all users with pagination
   */
  async getAllUsers(
    page: number = 1,
    limit: number = 20,
  ): Promise<PaginatedResponse<User>> {
    const { users, total } = await this.userRepository.findAll(page, limit);
    return {
      data: users,
      pagination: {
        page,
        limit,
        total,
        totalPages: Math.ceil(total / limit),
      },
    };
  }

  /**
   * Update user
   */
  async updateUser(id: string, updates: Partial<User>): Promise<User> {
    const user = await this.userRepository.findById(id);
    if (!user) {
      throw new NotFoundError('User', id);
    }

    // Check if email is being changed and if it's already taken
    if (updates.email && updates.email !== user.email) {
      const emailExists = await this.userRepository.existsByEmail(updates.email);
      if (emailExists) {
        throw new ConflictError('Email already in use');
      }
    }

    const updatedUser = await this.userRepository.update(id, updates);
    if (!updatedUser) {
      throw new NotFoundError('User', id);
    }

    return updatedUser;
  }

  /**
   * Delete user
   */
  async deleteUser(id: string): Promise<void> {
    const user = await this.userRepository.findById(id);
    if (!user) {
      throw new NotFoundError('User', id);
    }

    await this.userRepository.delete(id);
  }

  /**
   * Assign role to user
   */
  async assignRole(userId: string, roleName: string): Promise<User> {
    const user = await this.userRepository.findById(userId);
    if (!user) {
      throw new NotFoundError('User', userId);
    }

    const role = await this.roleRepository.findByName(roleName);
    if (!role) {
      throw new NotFoundError('Role', roleName);
    }

    // Check if user already has this role
    if (user.hasRole(roleName)) {
      throw new ConflictError('User already has this role');
    }

    await this.userRepository.assignRole(userId, role.id);

    return (await this.userRepository.findById(userId))!;
  }

  /**
   * Remove role from user
   */
  async removeRole(userId: string, roleName: string): Promise<User> {
    const user = await this.userRepository.findById(userId);
    if (!user) {
      throw new NotFoundError('User', userId);
    }

    const role = await this.roleRepository.findByName(roleName);
    if (!role) {
      throw new NotFoundError('Role', roleName);
    }

    if (!user.hasRole(roleName)) {
      throw new ConflictError('User does not have this role');
    }

    await this.userRepository.removeRole(userId, role.id);

    return (await this.userRepository.findById(userId))!;
  }

  /**
   * Get user roles
   */
  async getUserRoles(userId: string): Promise<Role[]> {
    const user = await this.userRepository.findById(userId);
    if (!user) {
      throw new NotFoundError('User', userId);
    }

    return user.roles || [];
  }

  /**
   * Get user permissions
   */
  async getUserPermissions(userId: string): Promise<string[]> {
    const user = await this.userRepository.findById(userId);
    if (!user) {
      throw new NotFoundError('User', userId);
    }

    const permissions = new Set<string>();
    user.roles?.forEach((role) => {
      role.permissions?.forEach((permission) => {
        permissions.add(permission.name);
      });
    });

    return Array.from(permissions);
  }

  /**
   * Activate user
   */
  async activateUser(userId: string): Promise<User> {
    return this.updateUser(userId, { isActive: true });
  }

  /**
   * Deactivate user
   */
  async deactivateUser(userId: string): Promise<User> {
    return this.updateUser(userId, { isActive: false });
  }
}
