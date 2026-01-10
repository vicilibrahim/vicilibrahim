import { IsString, IsNotEmpty } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';

export class AssignRoleDto {
  @ApiProperty({
    example: 'admin',
    description: 'Role name to assign',
  })
  @IsString()
  @IsNotEmpty()
  roleName: string;
}
