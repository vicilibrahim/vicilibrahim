import { ApiProperty } from '@nestjs/swagger';
import { User } from '../../domain/entities/user.entity';

export class UserResponseDto {
  @ApiProperty()
  id: string;

  @ApiProperty()
  email: string;

  @ApiProperty()
  firstName: string;

  @ApiProperty()
  lastName: string;

  @ApiProperty()
  isActive: boolean;

  @ApiProperty()
  roles: string[];

  @ApiProperty()
  createdAt: Date;

  @ApiProperty()
  updatedAt: Date;

  static fromEntity(user: User): UserResponseDto {
    return {
      id: user.id,
      email: user.email,
      firstName: user.firstName,
      lastName: user.lastName,
      isActive: user.isActive,
      roles: user.roles?.map((role) => role.name) ?? [],
      createdAt: user.createdAt,
      updatedAt: user.updatedAt,
    };
  }
}
