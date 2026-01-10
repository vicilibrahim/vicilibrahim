import { Injectable } from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';
import { ConfigService } from '@nestjs/config';
import * as bcrypt from 'bcrypt';
import { UserRepository } from '../repositories/user.repository';
import { RoleRepository } from '../repositories/role.repository';
import { User } from '../entities/user.entity';
import {
  UnauthorizedError,
  ConflictError,
  NotFoundError,
} from '@commercehub/shared';

export interface TokenPayload {
  sub: string;
  email: string;
  roles: string[];
}

export interface AuthTokens {
  accessToken: string;
  refreshToken: string;
  expiresIn: number;
}

@Injectable()
export class AuthService {
  private readonly saltRounds: number;

  constructor(
    private readonly userRepository: UserRepository,
    private readonly roleRepository: RoleRepository,
    private readonly jwtService: JwtService,
    private readonly configService: ConfigService,
  ) {
    this.saltRounds = this.configService.get<number>('BCRYPT_SALT_ROUNDS') || 10;
  }

  /**
   * Register a new user
   */
  async register(
    email: string,
    password: string,
    firstName?: string,
    lastName?: string,
  ): Promise<User> {
    // Check if user already exists
    const existingUser = await this.userRepository.findByEmail(email);
    if (existingUser) {
      throw new ConflictError('User with this email already exists');
    }

    // Hash password
    const passwordHash = await this.hashPassword(password);

    // Create user
    const user = await this.userRepository.create({
      email,
      passwordHash,
      firstName,
      lastName,
      isActive: true,
    });

    // Assign default role (user)
    const defaultRole = await this.roleRepository.findByName('user');
    if (defaultRole) {
      await this.userRepository.assignRole(user.id, defaultRole.id);
    }

    // Reload user with roles
    return (await this.userRepository.findById(user.id))!;
  }

  /**
   * Validate user credentials
   */
  async validateUser(email: string, password: string): Promise<User> {
    const user = await this.userRepository.findByEmail(email);
    if (!user) {
      throw new UnauthorizedError('Invalid credentials');
    }

    if (!user.isActive) {
      throw new UnauthorizedError('User account is inactive');
    }

    const isPasswordValid = await this.comparePassword(password, user.passwordHash);
    if (!isPasswordValid) {
      throw new UnauthorizedError('Invalid credentials');
    }

    return user;
  }

  /**
   * Login user and generate tokens
   */
  async login(user: User): Promise<AuthTokens> {
    return this.generateTokens(user);
  }

  /**
   * Refresh access token
   */
  async refreshToken(refreshToken: string): Promise<AuthTokens> {
    try {
      const payload = this.jwtService.verify(refreshToken, {
        secret: this.configService.get<string>('JWT_REFRESH_SECRET'),
      });

      const user = await this.userRepository.findById(payload.sub);
      if (!user || !user.isActive) {
        throw new UnauthorizedError('Invalid refresh token');
      }

      return this.generateTokens(user);
    } catch (error) {
      throw new UnauthorizedError('Invalid refresh token');
    }
  }

  /**
   * Generate access and refresh tokens
   */
  private async generateTokens(user: User): Promise<AuthTokens> {
    const payload: TokenPayload = {
      sub: user.id,
      email: user.email,
      roles: user.roles?.map((role) => role.name) ?? [],
    };

    const accessToken = this.jwtService.sign(payload, {
      secret: this.configService.get<string>('JWT_SECRET'),
      expiresIn: this.configService.get<string>('JWT_EXPIRES_IN') || '15m',
    });

    const refreshToken = this.jwtService.sign(payload, {
      secret: this.configService.get<string>('JWT_REFRESH_SECRET'),
      expiresIn: this.configService.get<string>('JWT_REFRESH_EXPIRES_IN') || '7d',
    });

    const expiresIn = 15 * 60; // 15 minutes in seconds

    return {
      accessToken,
      refreshToken,
      expiresIn,
    };
  }

  /**
   * Hash password
   */
  private async hashPassword(password: string): Promise<string> {
    return bcrypt.hash(password, this.saltRounds);
  }

  /**
   * Compare password with hash
   */
  private async comparePassword(password: string, hash: string): Promise<boolean> {
    return bcrypt.compare(password, hash);
  }

  /**
   * Change user password
   */
  async changePassword(userId: string, oldPassword: string, newPassword: string): Promise<void> {
    const user = await this.userRepository.findById(userId);
    if (!user) {
      throw new NotFoundError('User', userId);
    }

    const isOldPasswordValid = await this.comparePassword(oldPassword, user.passwordHash);
    if (!isOldPasswordValid) {
      throw new UnauthorizedError('Current password is incorrect');
    }

    const newPasswordHash = await this.hashPassword(newPassword);
    await this.userRepository.update(userId, { passwordHash: newPasswordHash });
  }
}
