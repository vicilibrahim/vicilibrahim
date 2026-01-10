import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToMany,
  JoinTable,
} from 'typeorm';
import { Role } from './role.entity';

@Entity('users')
export class User {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ unique: true })
  email: string;

  @Column({ name: 'password_hash' })
  passwordHash: string;

  @Column({ name: 'first_name', nullable: true })
  firstName: string;

  @Column({ name: 'last_name', nullable: true })
  lastName: string;

  @Column({ name: 'is_active', default: true })
  isActive: boolean;

  @ManyToMany(() => Role, (role) => role.users, { eager: true })
  @JoinTable({
    name: 'user_roles',
    joinColumn: { name: 'user_id', referencedColumnName: 'id' },
    inverseJoinColumn: { name: 'role_id', referencedColumnName: 'id' },
  })
  roles: Role[];

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;

  // Virtual property to get full name
  get fullName(): string {
    return `${this.firstName || ''} ${this.lastName || ''}`.trim();
  }

  // Check if user has a specific role
  hasRole(roleName: string): boolean {
    return this.roles?.some((role) => role.name === roleName) ?? false;
  }

  // Check if user has a specific permission
  hasPermission(permissionName: string): boolean {
    if (!this.roles) return false;
    return this.roles.some((role) =>
      role.permissions?.some((permission) => permission.name === permissionName),
    );
  }
}
