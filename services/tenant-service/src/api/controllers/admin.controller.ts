import {
  Controller,
  Get,
  Post,
  Put,
  Delete,
  Body,
  Param,
  Query,
  HttpCode,
  HttpStatus,
  DefaultValuePipe,
  ParseIntPipe,
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { TenantService } from '../../domain/services/tenant.service';
import { UsageTrackerService } from '../../domain/services/usage-tracker.service';
import { SuperAdminAuthService } from '../../domain/services/super-admin-auth.service';
import { CreateTenantDto } from '../dto/create-tenant.dto';
import { SuperAdminLoginDto } from '../dto/super-admin-login.dto';
import { ApiResponseBuilder } from '@commercehub/shared';

@ApiTags('Super Admin')
@Controller('api/v1/admin')
export class AdminController {
  constructor(
    private readonly tenantService: TenantService,
    private readonly usageService: UsageTrackerService,
    private readonly authService: SuperAdminAuthService,
  ) {}

  @Post('auth/login')
  @HttpCode(HttpStatus.OK)
  @ApiOperation({ summary: 'Super admin login' })
  async login(@Body() loginDto: SuperAdminLoginDto) {
    const admin = await this.authService.validateSuperAdmin(
      loginDto.email,
      loginDto.password,
    );

    const tokens = await this.authService.login(admin);

    return ApiResponseBuilder.success({
      admin: {
        id: admin.id,
        email: admin.email,
        name: admin.name,
        role: admin.role,
      },
      ...tokens,
    });
  }

  @Post('tenants')
  @ApiOperation({ summary: 'Create new tenant' })
  @ApiBearerAuth()
  async createTenant(@Body() createDto: CreateTenantDto) {
    const tenant = await this.tenantService.createTenant(createDto);
    return ApiResponseBuilder.success(tenant);
  }

  @Get('tenants')
  @ApiOperation({ summary: 'Get all tenants' })
  @ApiBearerAuth()
  async getAllTenants(
    @Query('page', new DefaultValuePipe(1), ParseIntPipe) page: number,
    @Query('limit', new DefaultValuePipe(20), ParseIntPipe) limit: number,
    @Query('status') status?: string,
  ) {
    const result = await this.tenantService.getAllTenants(page, limit, status);
    return ApiResponseBuilder.success(result);
  }

  @Get('tenants/:id')
  @ApiOperation({ summary: 'Get tenant by ID' })
  @ApiBearerAuth()
  async getTenantById(@Param('id') id: string) {
    const tenant = await this.tenantService.getTenantById(id);
    return ApiResponseBuilder.success(tenant);
  }

  @Post('tenants/:id/suspend')
  @ApiOperation({ summary: 'Suspend tenant' })
  @ApiBearerAuth()
  async suspendTenant(@Param('id') id: string) {
    const tenant = await this.tenantService.suspendTenant(id);
    return ApiResponseBuilder.success(tenant);
  }

  @Post('tenants/:id/activate')
  @ApiOperation({ summary: 'Activate tenant' })
  @ApiBearerAuth()
  async activateTenant(@Param('id') id: string) {
    const tenant = await this.tenantService.activateTenant(id);
    return ApiResponseBuilder.success(tenant);
  }

  @Delete('tenants/:id')
  @ApiOperation({ summary: 'Delete tenant' })
  @ApiBearerAuth()
  async deleteTenant(@Param('id') id: string) {
    await this.tenantService.deleteTenant(id);
    return ApiResponseBuilder.success(null, 'Tenant deleted successfully');
  }

  @Get('analytics/overview')
  @ApiOperation({ summary: 'Get platform overview' })
  @ApiBearerAuth()
  async getOverview() {
    const [tenantStats, platformUsage] = await Promise.all([
      this.tenantService.getTenantStats(),
      this.usageService.getTotalPlatformUsage(),
    ]);

    return ApiResponseBuilder.success({
      tenants: tenantStats,
      usage: platformUsage,
    });
  }
}
