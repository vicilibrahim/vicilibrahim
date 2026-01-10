import {
  Controller,
  Post,
  Body,
  HttpCode,
  HttpStatus,
  UseGuards,
  Request,
} from '@nestjs/common';
import {
  ApiTags,
  ApiOperation,
  ApiResponse,
  ApiBearerAuth,
} from '@nestjs/swagger';
import { AuthService } from '../../domain/services/auth.service';
import { EventPublisherService } from '../../infrastructure/events/event-publisher.service';
import { RegisterDto } from '../dto/register.dto';
import { LoginDto } from '../dto/login.dto';
import { RefreshTokenDto } from '../dto/refresh-token.dto';
import { ChangePasswordDto } from '../dto/change-password.dto';
import { AuthResponseDto, TokenResponseDto } from '../dto/auth.response.dto';
import { UserResponseDto } from '../dto/user.response.dto';
import { JwtAuthGuard } from '../guards/jwt-auth.guard';
import { ApiResponseBuilder } from '@commercehub/shared';

@ApiTags('Authentication')
@Controller('api/v1/auth')
export class AuthController {
  constructor(
    private readonly authService: AuthService,
    private readonly eventPublisher: EventPublisherService,
  ) {}

  @Post('register')
  @HttpCode(HttpStatus.CREATED)
  @ApiOperation({ summary: 'Register a new user' })
  @ApiResponse({
    status: 201,
    description: 'User successfully registered',
    type: AuthResponseDto,
  })
  @ApiResponse({ status: 409, description: 'User already exists' })
  async register(@Body() registerDto: RegisterDto) {
    const user = await this.authService.register(
      registerDto.email,
      registerDto.password,
      registerDto.firstName,
      registerDto.lastName,
    );

    const tokens = await this.authService.login(user);

    // Publish user.created event
    await this.eventPublisher.publishUserCreated({
      userId: user.id,
      email: user.email,
      firstName: user.firstName,
      lastName: user.lastName,
    });

    return ApiResponseBuilder.success<AuthResponseDto>({
      user: UserResponseDto.fromEntity(user),
      ...tokens,
    });
  }

  @Post('login')
  @HttpCode(HttpStatus.OK)
  @ApiOperation({ summary: 'Login user' })
  @ApiResponse({
    status: 200,
    description: 'User successfully logged in',
    type: AuthResponseDto,
  })
  @ApiResponse({ status: 401, description: 'Invalid credentials' })
  async login(@Body() loginDto: LoginDto) {
    const user = await this.authService.validateUser(
      loginDto.email,
      loginDto.password,
    );

    const tokens = await this.authService.login(user);

    // Publish user.login event
    await this.eventPublisher.publishUserLogin({
      userId: user.id,
      email: user.email,
    });

    return ApiResponseBuilder.success<AuthResponseDto>({
      user: UserResponseDto.fromEntity(user),
      ...tokens,
    });
  }

  @Post('refresh')
  @HttpCode(HttpStatus.OK)
  @ApiOperation({ summary: 'Refresh access token' })
  @ApiResponse({
    status: 200,
    description: 'Token successfully refreshed',
    type: TokenResponseDto,
  })
  @ApiResponse({ status: 401, description: 'Invalid refresh token' })
  async refreshToken(@Body() refreshTokenDto: RefreshTokenDto) {
    const tokens = await this.authService.refreshToken(refreshTokenDto.refreshToken);
    return ApiResponseBuilder.success(TokenResponseDto.fromTokens(tokens));
  }

  @Post('change-password')
  @HttpCode(HttpStatus.OK)
  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Change user password' })
  @ApiResponse({ status: 200, description: 'Password successfully changed' })
  @ApiResponse({ status: 401, description: 'Unauthorized' })
  async changePassword(
    @Request() req: any,
    @Body() changePasswordDto: ChangePasswordDto,
  ) {
    await this.authService.changePassword(
      req.user.userId,
      changePasswordDto.oldPassword,
      changePasswordDto.newPassword,
    );

    return ApiResponseBuilder.success(null, 'Password successfully changed');
  }

  @Post('logout')
  @HttpCode(HttpStatus.OK)
  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Logout user' })
  @ApiResponse({ status: 200, description: 'User successfully logged out' })
  async logout() {
    // In a production system, you would invalidate the token here
    // For now, we just return success (client should delete the token)
    return ApiResponseBuilder.success(null, 'Successfully logged out');
  }
}
