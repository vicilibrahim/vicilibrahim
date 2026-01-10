import { Test, TestingModule } from '@nestjs/testing';
import { JwtService } from '@nestjs/jwt';
import { ConfigService } from '@nestjs/config';
import { AuthService } from '../src/domain/services/auth.service';
import { UserRepository } from '../src/domain/repositories/user.repository';
import { RoleRepository } from '../src/domain/repositories/role.repository';
import { User } from '../src/domain/entities/user.entity';
import { Role } from '../src/domain/entities/role.entity';
import { UnauthorizedError, ConflictError } from '@commercehub/shared';

describe('AuthService', () => {
  let service: AuthService;
  let userRepository: UserRepository;
  let roleRepository: RoleRepository;
  let jwtService: JwtService;

  const mockUser: Partial<User> = {
    id: '123',
    email: 'test@example.com',
    passwordHash: '$2b$10$abcdefghijklmnopqrstuvwxyz', // mock hash
    firstName: 'Test',
    lastName: 'User',
    isActive: true,
    roles: [{ id: '1', name: 'user' } as Role],
  };

  const mockUserRepository = {
    findByEmail: jest.fn(),
    create: jest.fn(),
    findById: jest.fn(),
    assignRole: jest.fn(),
    update: jest.fn(),
  };

  const mockRoleRepository = {
    findByName: jest.fn(),
  };

  const mockJwtService = {
    sign: jest.fn(),
    verify: jest.fn(),
  };

  const mockConfigService = {
    get: jest.fn((key: string) => {
      const config = {
        BCRYPT_SALT_ROUNDS: 10,
        JWT_SECRET: 'test-secret',
        JWT_EXPIRES_IN: '15m',
        JWT_REFRESH_SECRET: 'test-refresh-secret',
        JWT_REFRESH_EXPIRES_IN: '7d',
      };
      return config[key];
    }),
  };

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [
        AuthService,
        { provide: UserRepository, useValue: mockUserRepository },
        { provide: RoleRepository, useValue: mockRoleRepository },
        { provide: JwtService, useValue: mockJwtService },
        { provide: ConfigService, useValue: mockConfigService },
      ],
    }).compile();

    service = module.get<AuthService>(AuthService);
    userRepository = module.get<UserRepository>(UserRepository);
    roleRepository = module.get<RoleRepository>(RoleRepository);
    jwtService = module.get<JwtService>(JwtService);

    jest.clearAllMocks();
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });

  describe('register', () => {
    it('should register a new user successfully', async () => {
      const email = 'newuser@example.com';
      const password = 'Password123';

      mockUserRepository.findByEmail.mockResolvedValue(null);
      mockUserRepository.create.mockResolvedValue(mockUser);
      mockRoleRepository.findByName.mockResolvedValue({ id: '1', name: 'user' });
      mockUserRepository.findById.mockResolvedValue(mockUser);

      const result = await service.register(email, password);

      expect(result).toEqual(mockUser);
      expect(mockUserRepository.findByEmail).toHaveBeenCalledWith(email);
      expect(mockUserRepository.create).toHaveBeenCalled();
      expect(mockUserRepository.assignRole).toHaveBeenCalled();
    });

    it('should throw ConflictError if user already exists', async () => {
      const email = 'existing@example.com';
      const password = 'Password123';

      mockUserRepository.findByEmail.mockResolvedValue(mockUser);

      await expect(service.register(email, password)).rejects.toThrow(ConflictError);
    });
  });

  describe('validateUser', () => {
    it('should validate user with correct credentials', async () => {
      const email = 'test@example.com';
      const password = 'Password123';

      mockUserRepository.findByEmail.mockResolvedValue(mockUser);
      // Mock bcrypt.compare to return true
      jest.spyOn(service as any, 'comparePassword').mockResolvedValue(true);

      const result = await service.validateUser(email, password);

      expect(result).toEqual(mockUser);
    });

    it('should throw UnauthorizedError with invalid credentials', async () => {
      const email = 'test@example.com';
      const password = 'WrongPassword';

      mockUserRepository.findByEmail.mockResolvedValue(mockUser);
      jest.spyOn(service as any, 'comparePassword').mockResolvedValue(false);

      await expect(service.validateUser(email, password)).rejects.toThrow(
        UnauthorizedError,
      );
    });

    it('should throw UnauthorizedError if user not found', async () => {
      mockUserRepository.findByEmail.mockResolvedValue(null);

      await expect(service.validateUser('nonexistent@example.com', 'password')).rejects.toThrow(
        UnauthorizedError,
      );
    });

    it('should throw UnauthorizedError if user is inactive', async () => {
      const inactiveUser = { ...mockUser, isActive: false };
      mockUserRepository.findByEmail.mockResolvedValue(inactiveUser);

      await expect(service.validateUser('test@example.com', 'Password123')).rejects.toThrow(
        UnauthorizedError,
      );
    });
  });

  describe('login', () => {
    it('should generate tokens for valid user', async () => {
      const accessToken = 'access-token';
      const refreshToken = 'refresh-token';

      mockJwtService.sign
        .mockReturnValueOnce(accessToken)
        .mockReturnValueOnce(refreshToken);

      const result = await service.login(mockUser as User);

      expect(result).toEqual({
        accessToken,
        refreshToken,
        expiresIn: 900, // 15 minutes
      });
      expect(mockJwtService.sign).toHaveBeenCalledTimes(2);
    });
  });

  describe('refreshToken', () => {
    it('should refresh tokens with valid refresh token', async () => {
      const refreshToken = 'valid-refresh-token';
      const payload = { sub: '123' };

      mockJwtService.verify.mockReturnValue(payload);
      mockUserRepository.findById.mockResolvedValue(mockUser);
      mockJwtService.sign
        .mockReturnValueOnce('new-access-token')
        .mockReturnValueOnce('new-refresh-token');

      const result = await service.refreshToken(refreshToken);

      expect(result.accessToken).toBe('new-access-token');
      expect(result.refreshToken).toBe('new-refresh-token');
    });

    it('should throw UnauthorizedError with invalid refresh token', async () => {
      mockJwtService.verify.mockImplementation(() => {
        throw new Error('Invalid token');
      });

      await expect(service.refreshToken('invalid-token')).rejects.toThrow(
        UnauthorizedError,
      );
    });
  });
});
