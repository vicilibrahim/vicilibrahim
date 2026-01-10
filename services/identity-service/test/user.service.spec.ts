import { Test, TestingModule } from '@nestjs/testing';
import { UserService } from '../src/domain/services/user.service';
import { UserRepository } from '../src/domain/repositories/user.repository';
import { RoleRepository } from '../src/domain/repositories/role.repository';
import { User } from '../src/domain/entities/user.entity';
import { Role } from '../src/domain/entities/role.entity';
import { NotFoundError, ConflictError } from '@commercehub/shared';

describe('UserService', () => {
  let service: UserService;
  let userRepository: UserRepository;
  let roleRepository: RoleRepository;

  const mockUser: Partial<User> = {
    id: '123',
    email: 'test@example.com',
    firstName: 'Test',
    lastName: 'User',
    isActive: true,
    roles: [{ id: '1', name: 'user' } as Role],
    hasRole: jest.fn((roleName: string) => roleName === 'user'),
  };

  const mockUserRepository = {
    findById: jest.fn(),
    findByEmail: jest.fn(),
    findAll: jest.fn(),
    update: jest.fn(),
    delete: jest.fn(),
    assignRole: jest.fn(),
    removeRole: jest.fn(),
    existsByEmail: jest.fn(),
  };

  const mockRoleRepository = {
    findByName: jest.fn(),
  };

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [
        UserService,
        { provide: UserRepository, useValue: mockUserRepository },
        { provide: RoleRepository, useValue: mockRoleRepository },
      ],
    }).compile();

    service = module.get<UserService>(UserService);
    userRepository = module.get<UserRepository>(UserRepository);
    roleRepository = module.get<RoleRepository>(RoleRepository);

    jest.clearAllMocks();
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });

  describe('getUserById', () => {
    it('should return user if found', async () => {
      mockUserRepository.findById.mockResolvedValue(mockUser);

      const result = await service.getUserById('123');

      expect(result).toEqual(mockUser);
      expect(mockUserRepository.findById).toHaveBeenCalledWith('123');
    });

    it('should throw NotFoundError if user not found', async () => {
      mockUserRepository.findById.mockResolvedValue(null);

      await expect(service.getUserById('nonexistent')).rejects.toThrow(NotFoundError);
    });
  });

  describe('getAllUsers', () => {
    it('should return paginated users', async () => {
      const users = [mockUser];
      mockUserRepository.findAll.mockResolvedValue({ users, total: 1 });

      const result = await service.getAllUsers(1, 20);

      expect(result).toEqual({
        data: users,
        pagination: {
          page: 1,
          limit: 20,
          total: 1,
          totalPages: 1,
        },
      });
    });
  });

  describe('updateUser', () => {
    it('should update user successfully', async () => {
      const updates = { firstName: 'Updated' };
      const updatedUser = { ...mockUser, ...updates };

      mockUserRepository.findById.mockResolvedValue(mockUser);
      mockUserRepository.update.mockResolvedValue(updatedUser);

      const result = await service.updateUser('123', updates);

      expect(result).toEqual(updatedUser);
      expect(mockUserRepository.update).toHaveBeenCalledWith('123', updates);
    });

    it('should check email uniqueness when updating email', async () => {
      const updates = { email: 'newemail@example.com' };

      mockUserRepository.findById.mockResolvedValue(mockUser);
      mockUserRepository.existsByEmail.mockResolvedValue(false);
      mockUserRepository.update.mockResolvedValue({ ...mockUser, ...updates });

      await service.updateUser('123', updates);

      expect(mockUserRepository.existsByEmail).toHaveBeenCalledWith('newemail@example.com');
    });

    it('should throw ConflictError if new email already exists', async () => {
      const updates = { email: 'existing@example.com' };

      mockUserRepository.findById.mockResolvedValue(mockUser);
      mockUserRepository.existsByEmail.mockResolvedValue(true);

      await expect(service.updateUser('123', updates)).rejects.toThrow(ConflictError);
    });
  });

  describe('assignRole', () => {
    it('should assign role to user', async () => {
      const role = { id: '2', name: 'admin' } as Role;
      const userWithoutRole = {
        ...mockUser,
        hasRole: jest.fn(() => false),
      };

      mockUserRepository.findById
        .mockResolvedValueOnce(userWithoutRole)
        .mockResolvedValueOnce({ ...mockUser, roles: [mockUser.roles[0], role] });
      mockRoleRepository.findByName.mockResolvedValue(role);

      const result = await service.assignRole('123', 'admin');

      expect(mockUserRepository.assignRole).toHaveBeenCalledWith('123', '2');
      expect(result.roles).toHaveLength(2);
    });

    it('should throw ConflictError if user already has role', async () => {
      mockUserRepository.findById.mockResolvedValue(mockUser);
      mockRoleRepository.findByName.mockResolvedValue({ id: '1', name: 'user' });

      await expect(service.assignRole('123', 'user')).rejects.toThrow(ConflictError);
    });
  });

  describe('removeRole', () => {
    it('should remove role from user', async () => {
      const role = { id: '1', name: 'user' } as Role;

      mockUserRepository.findById
        .mockResolvedValueOnce(mockUser)
        .mockResolvedValueOnce({ ...mockUser, roles: [] });
      mockRoleRepository.findByName.mockResolvedValue(role);

      const result = await service.removeRole('123', 'user');

      expect(mockUserRepository.removeRole).toHaveBeenCalledWith('123', '1');
    });
  });
});
