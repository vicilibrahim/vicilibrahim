import { Injectable } from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';
import { ConfigService } from '@nestjs/config';
import * as bcrypt from 'bcrypt';
import { SuperAdminRepository } from '../repositories/super-admin.repository';
import { SuperAdmin } from '../entities/super-admin.entity';
import { UnauthorizedError } from '@commercehub/shared';

export interface SuperAdminTokenPayload {
  sub: string;
  email: string;
  role: string;
  isSuperAdmin: true;
}

export interface AuthTokens {
  accessToken: string;
  expiresIn: number;
}

@Injectable()
export class SuperAdminAuthService {
  constructor(
    private readonly superAdminRepository: SuperAdminRepository,
    private readonly jwtService: JwtService,
    private readonly configService: ConfigService,
  ) {}

  /**
   * Validate super admin credentials
   */
  async validateSuperAdmin(email: string, password: string): Promise<SuperAdmin> {
    const admin = await this.superAdminRepository.findByEmail(email);
    if (!admin) {
      throw new UnauthorizedError('Invalid credentials');
    }

    if (!admin.isActive) {
      throw new UnauthorizedError('Account is inactive');
    }

    const isPasswordValid = await bcrypt.compare(password, admin.passwordHash);
    if (!isPasswordValid) {
      throw new UnauthorizedError('Invalid credentials');
    }

    return admin;
  }

  /**
   * Login super admin and generate token
   */
  async login(admin: SuperAdmin): Promise<AuthTokens> {
    // Update last login timestamp
    await this.superAdminRepository.updateLastLogin(admin.id);

    return this.generateToken(admin);
  }

  /**
   * Generate JWT token for super admin
   */
  private generateToken(admin: SuperAdmin): AuthTokens {
    const payload: SuperAdminTokenPayload = {
      sub: admin.id,
      email: admin.email,
      role: admin.role,
      isSuperAdmin: true,
    };

    const accessToken = this.jwtService.sign(payload, {
      secret: this.configService.get<string>('JWT_SECRET'),
      expiresIn: this.configService.get<string>('JWT_EXPIRES_IN') || '1h',
    });

    const expiresIn = 60 * 60; // 1 hour in seconds

    return {
      accessToken,
      expiresIn,
    };
  }

  /**
   * Create a new super admin
   */
  async createSuperAdmin(
    email: string,
    password: string,
    name?: string,
    role: 'super_admin' | 'support' | 'billing' = 'super_admin',
  ): Promise<SuperAdmin> {
    const passwordHash = await bcrypt.hash(password, 10);

    return this.superAdminRepository.create({
      email,
      passwordHash,
      name,
      role,
      isActive: true,
    });
  }

  /**
   * Change super admin password
   */
  async changePassword(adminId: string, oldPassword: string, newPassword: string): Promise<void> {
    const admin = await this.superAdminRepository.findById(adminId);
    if (!admin) {
      throw new UnauthorizedError('Admin not found');
    }

    const isOldPasswordValid = await bcrypt.compare(oldPassword, admin.passwordHash);
    if (!isOldPasswordValid) {
      throw new UnauthorizedError('Current password is incorrect');
    }

    const newPasswordHash = await bcrypt.hash(newPassword, 10);
    await this.superAdminRepository.update(adminId, { passwordHash: newPasswordHash });
  }
}
