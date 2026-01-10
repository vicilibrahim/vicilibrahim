import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { Tenant } from '../../domain/entities/tenant.entity';
import { SubscriptionPlan } from '../../domain/entities/subscription-plan.entity';
import { TenantUsage } from '../../domain/entities/tenant-usage.entity';
import { SuperAdmin } from '../../domain/entities/super-admin.entity';

@Module({
  imports: [
    TypeOrmModule.forRootAsync({
      imports: [ConfigModule],
      inject: [ConfigService],
      useFactory: (configService: ConfigService) => ({
        type: 'postgres',
        host: configService.get('database.host'),
        port: configService.get('database.port'),
        username: configService.get('database.username'),
        password: configService.get('database.password'),
        database: configService.get('database.database'),
        entities: [Tenant, SubscriptionPlan, TenantUsage, SuperAdmin],
        synchronize: configService.get('nodeEnv') === 'development',
        logging: configService.get('nodeEnv') === 'development',
        ssl: configService.get('database.ssl') ? { rejectUnauthorized: false } : false,
      }),
    }),
    TypeOrmModule.forFeature([Tenant, SubscriptionPlan, TenantUsage, SuperAdmin]),
  ],
  exports: [TypeOrmModule],
})
export class DatabaseModule {}
