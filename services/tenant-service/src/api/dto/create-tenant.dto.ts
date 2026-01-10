import { IsEmail, IsString, IsOptional, MinLength, IsNumber } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';

export class CreateTenantDto {
  @ApiProperty({ example: 'Acme Corporation' })
  @IsString()
  name: string;

  @ApiProperty({ example: 'acme-corp', required: false })
  @IsOptional()
  @IsString()
  slug?: string;

  @ApiProperty({ example: 'contact@acme.com' })
  @IsEmail()
  contactEmail: string;

  @ApiProperty({ example: 'Acme Corporation Ltd.', required: false })
  @IsOptional()
  @IsString()
  companyName?: string;

  @ApiProperty({ example: '+905551234567', required: false })
  @IsOptional()
  @IsString()
  contactPhone?: string;

  @ApiProperty({ example: 'starter' })
  @IsString()
  planSlug: string;

  @ApiProperty({ example: 14, required: false })
  @IsOptional()
  @IsNumber()
  trialDays?: number;
}
