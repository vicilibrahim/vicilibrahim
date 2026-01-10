import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { PassportModule } from '@nestjs/passport';
import { JwtModule } from '@nestjs/jwt';
import { TypeOrmModule } from '@nestjs/typeorm';
import configuration from './infrastructure/config/configuration';
import { DatabaseModule } from './infrastructure/database/database.module';
import { EventsModule } from './infrastructure/events/events.module';
import { User } from './domain/entities/user.entity';
import { Role } from './domain/entities/role.entity';
import { Permission } from './domain/entities/permission.entity';
import { UserRepository } from './domain/repositories/user.repository';
import { RoleRepository } from './domain/repositories/role.repository';
import { PermissionRepository } from './domain/repositories/permission.repository';
import { AuthService } from './domain/services/auth.service';
import { UserService } from './domain/services/user.service';
import { AuthController } from './api/controllers/auth.controller';
import { UsersController } from './api/controllers/users.controller';
import { JwtStrategy } from './api/strategies/jwt.strategy';
import { HealthController } from './health.controller';

@Module({
  imports: [
    ConfigModule.forRoot({
      isGlobal: true,
      load: [configuration],
    }),
    DatabaseModule,
    EventsModule,
    PassportModule,
    JwtModule.register({}), // Configuration done via ConfigService
    TypeOrmModule.forFeature([User, Role, Permission]),
  ],
  controllers: [AuthController, UsersController, HealthController],
  providers: [
    UserRepository,
    RoleRepository,
    PermissionRepository,
    AuthService,
    UserService,
    JwtStrategy,
  ],
})
export class AppModule {}
