import { IsEmail, IsString, IsBoolean, IsOptional } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';

export class UpdateUserDto {
  @ApiProperty({
    example: 'user@example.com',
    description: 'User email address',
    required: false,
  })
  @IsOptional()
  @IsEmail({}, { message: 'Invalid email format' })
  email?: string;

  @ApiProperty({
    example: 'John',
    description: 'User first name',
    required: false,
  })
  @IsOptional()
  @IsString()
  firstName?: string;

  @ApiProperty({
    example: 'Doe',
    description: 'User last name',
    required: false,
  })
  @IsOptional()
  @IsString()
  lastName?: string;

  @ApiProperty({
    example: true,
    description: 'User active status',
    required: false,
  })
  @IsOptional()
  @IsBoolean()
  isActive?: boolean;
}
