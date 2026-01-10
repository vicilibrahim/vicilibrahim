import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { JwtModule } from '@nestjs/jwt';
import { TypeOrmModule } from '@nestjs/typeorm';
import configuration from './infrastructure/config/configuration';
import { DatabaseModule } from './infrastructure/database/database.module';
import { Tenant } from './domain/entities/tenant.entity';
import { SubscriptionPlan } from './domain/entities/subscription-plan.entity';
import { TenantUsage } from './domain/entities/tenant-usage.entity';
import { SuperAdmin } from './domain/entities/super-admin.entity';
import { TenantRepository } from './domain/repositories/tenant.repository';
import { SubscriptionPlanRepository } from './domain/repositories/subscription-plan.repository';
import { TenantUsageRepository } from './domain/repositories/tenant-usage.repository';
import { SuperAdminRepository } from './domain/repositories/super-admin.repository';
import { TenantService } from './domain/services/tenant.service';
import { ProvisioningService } from './domain/services/provisioning.service';
import { UsageTrackerService } from './domain/services/usage-tracker.service';
import { SuperAdminAuthService } from './domain/services/super-admin-auth.service';
import { AdminController } from './api/controllers/admin.controller';
import { HealthController } from './health.controller';

@Module({
  imports: [
    ConfigModule.forRoot({
      isGlobal: true,
      load: [configuration],
    }),
    DatabaseModule,
    JwtModule.register({}),
    TypeOrmModule.forFeature([Tenant, SubscriptionPlan, TenantUsage, SuperAdmin]),
  ],
  controllers: [AdminController, HealthController],
  providers: [
    TenantRepository,
    SubscriptionPlanRepository,
    TenantUsageRepository,
    SuperAdminRepository,
    TenantService,
    ProvisioningService,
    UsageTrackerService,
    SuperAdminAuthService,
  ],
  exports: [TenantService, UsageTrackerService, ProvisioningService],
})
export class AppModule {}
