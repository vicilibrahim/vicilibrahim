import { ApiProperty } from '@nestjs/swagger';
import { UserResponseDto } from './user.response.dto';
import { AuthTokens } from '../../domain/services/auth.service';

export class AuthResponseDto {
  @ApiProperty()
  user: UserResponseDto;

  @ApiProperty()
  accessToken: string;

  @ApiProperty()
  refreshToken: string;

  @ApiProperty()
  expiresIn: number;
}

export class TokenResponseDto {
  @ApiProperty()
  accessToken: string;

  @ApiProperty()
  refreshToken: string;

  @ApiProperty()
  expiresIn: number;

  static fromTokens(tokens: AuthTokens): TokenResponseDto {
    return {
      accessToken: tokens.accessToken,
      refreshToken: tokens.refreshToken,
      expiresIn: tokens.expiresIn,
    };
  }
}
