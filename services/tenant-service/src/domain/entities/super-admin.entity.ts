import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
} from 'typeorm';

export type SuperAdminRole = 'super_admin' | 'support' | 'billing';

@Entity('super_admins')
export class SuperAdmin {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ unique: true })
  email: string;

  @Column({ name: 'password_hash' })
  passwordHash: string;

  @Column({ nullable: true })
  name: string;

  @Column({ default: 'super_admin' })
  role: SuperAdminRole;

  @Column({ name: 'is_active', default: true })
  isActive: boolean;

  @Column({ name: 'last_login_at', type: 'timestamp', nullable: true })
  lastLoginAt: Date;

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  // Helper methods
  isSuperAdmin(): boolean {
    return this.role === 'super_admin';
  }

  canManageTenants(): boolean {
    return this.role === 'super_admin';
  }

  canManageBilling(): boolean {
    return this.role === 'super_admin' || this.role === 'billing';
  }

  canProvideSupport(): boolean {
    return this.isActive;
  }
}
