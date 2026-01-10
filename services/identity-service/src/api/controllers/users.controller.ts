import {
  Controller,
  Get,
  Put,
  Delete,
  Param,
  Body,
  Query,
  UseGuards,
  Request,
  ParseIntPipe,
  DefaultValuePipe,
} from '@nestjs/common';
import {
  ApiTags,
  ApiOperation,
  ApiResponse,
  ApiBearerAuth,
  ApiQuery,
} from '@nestjs/swagger';
import { UserService } from '../../domain/services/user.service';
import { EventPublisherService } from '../../infrastructure/events/event-publisher.service';
import { UpdateUserDto } from '../dto/update-user.dto';
import { AssignRoleDto } from '../dto/assign-role.dto';
import { UserResponseDto } from '../dto/user.response.dto';
import { JwtAuthGuard } from '../guards/jwt-auth.guard';
import { RolesGuard } from '../guards/roles.guard';
import { Roles } from '../decorators/roles.decorator';
import { ApiResponseBuilder } from '@commercehub/shared';

@ApiTags('Users')
@Controller('api/v1/users')
@UseGuards(JwtAuthGuard, RolesGuard)
@ApiBearerAuth()
export class UsersController {
  constructor(
    private readonly userService: UserService,
    private readonly eventPublisher: EventPublisherService,
  ) {}

  @Get('me')
  @ApiOperation({ summary: 'Get current user profile' })
  @ApiResponse({
    status: 200,
    description: 'Current user profile',
    type: UserResponseDto,
  })
  async getCurrentUser(@Request() req: any) {
    const user = await this.userService.getUserById(req.user.userId);
    return ApiResponseBuilder.success(UserResponseDto.fromEntity(user));
  }

  @Get()
  @Roles('admin')
  @ApiOperation({ summary: 'Get all users' })
  @ApiQuery({ name: 'page', required: false, type: Number })
  @ApiQuery({ name: 'limit', required: false, type: Number })
  @ApiResponse({ status: 200, description: 'List of users' })
  async getAllUsers(
    @Query('page', new DefaultValuePipe(1), ParseIntPipe) page: number,
    @Query('limit', new DefaultValuePipe(20), ParseIntPipe) limit: number,
  ) {
    const result = await this.userService.getAllUsers(page, limit);
    return ApiResponseBuilder.success({
      ...result,
      data: result.data.map((user) => UserResponseDto.fromEntity(user)),
    });
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get user by ID' })
  @ApiResponse({
    status: 200,
    description: 'User found',
    type: UserResponseDto,
  })
  @ApiResponse({ status: 404, description: 'User not found' })
  async getUserById(@Param('id') id: string) {
    const user = await this.userService.getUserById(id);
    return ApiResponseBuilder.success(UserResponseDto.fromEntity(user));
  }

  @Put(':id')
  @ApiOperation({ summary: 'Update user' })
  @ApiResponse({
    status: 200,
    description: 'User updated',
    type: UserResponseDto,
  })
  @ApiResponse({ status: 404, description: 'User not found' })
  async updateUser(@Param('id') id: string, @Body() updateUserDto: UpdateUserDto) {
    const user = await this.userService.updateUser(id, updateUserDto);

    // Publish user.updated event
    await this.eventPublisher.publishUserUpdated({
      userId: user.id,
      changes: updateUserDto,
    });

    return ApiResponseBuilder.success(UserResponseDto.fromEntity(user));
  }

  @Delete(':id')
  @Roles('admin')
  @ApiOperation({ summary: 'Delete user' })
  @ApiResponse({ status: 200, description: 'User deleted' })
  @ApiResponse({ status: 404, description: 'User not found' })
  async deleteUser(@Param('id') id: string) {
    await this.userService.deleteUser(id);

    // Publish user.deleted event
    await this.eventPublisher.publishUserDeleted({ userId: id });

    return ApiResponseBuilder.success(null, 'User successfully deleted');
  }

  @Post(':id/roles')
  @Roles('admin')
  @ApiOperation({ summary: 'Assign role to user' })
  @ApiResponse({ status: 200, description: 'Role assigned' })
  async assignRole(@Param('id') id: string, @Body() assignRoleDto: AssignRoleDto) {
    const user = await this.userService.assignRole(id, assignRoleDto.roleName);
    return ApiResponseBuilder.success(UserResponseDto.fromEntity(user));
  }

  @Delete(':id/roles/:roleName')
  @Roles('admin')
  @ApiOperation({ summary: 'Remove role from user' })
  @ApiResponse({ status: 200, description: 'Role removed' })
  async removeRole(@Param('id') id: string, @Param('roleName') roleName: string) {
    const user = await this.userService.removeRole(id, roleName);
    return ApiResponseBuilder.success(UserResponseDto.fromEntity(user));
  }

  @Get(':id/roles')
  @ApiOperation({ summary: 'Get user roles' })
  @ApiResponse({ status: 200, description: 'User roles' })
  async getUserRoles(@Param('id') id: string) {
    const roles = await this.userService.getUserRoles(id);
    return ApiResponseBuilder.success(roles.map((role) => ({
      id: role.id,
      name: role.name,
      description: role.description,
    })));
  }

  @Get(':id/permissions')
  @ApiOperation({ summary: 'Get user permissions' })
  @ApiResponse({ status: 200, description: 'User permissions' })
  async getUserPermissions(@Param('id') id: string) {
    const permissions = await this.userService.getUserPermissions(id);
    return ApiResponseBuilder.success(permissions);
  }
}
