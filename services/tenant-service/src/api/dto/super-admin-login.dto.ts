import { IsEmail, IsString } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';

export class SuperAdminLoginDto {
  @ApiProperty({ example: 'admin@commercehub.com' })
  @IsEmail()
  email: string;

  @ApiProperty({ example: 'SuperSecure123!' })
  @IsString()
  password: string;
}
