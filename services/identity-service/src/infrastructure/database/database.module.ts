import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { User } from '../../domain/entities/user.entity';
import { Role } from '../../domain/entities/role.entity';
import { Permission } from '../../domain/entities/permission.entity';

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
        entities: [User, Role, Permission],
        synchronize: configService.get('nodeEnv') === 'development',
        logging: configService.get('nodeEnv') === 'development',
        ssl: configService.get('database.ssl')
          ? { rejectUnauthorized: false }
          : false,
      }),
    }),
    TypeOrmModule.forFeature([User, Role, Permission]),
  ],
  exports: [TypeOrmModule],
})
export class DatabaseModule {}
