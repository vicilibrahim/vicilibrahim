import { NestFactory } from '@nestjs/core';
import { ValidationPipe } from '@nestjs/common';
import { SwaggerModule, DocumentBuilder } from '@nestjs/swagger';
import { ConfigService } from '@nestjs/config';
import { AppModule } from './app.module';
import { createLogger } from '@commercehub/shared';

async function bootstrap() {
  const app = await NestFactory.create(AppModule);

  const configService = app.get(ConfigService);
  const port = configService.get<number>('port') || 3000;
  const serviceName = configService.get<string>('serviceName') || 'identity-service';

  const logger = createLogger(serviceName);

  // Enable CORS
  app.enableCors({
    origin: true,
    credentials: true,
  });

  // Global validation pipe
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      transform: true,
      forbidNonWhitelisted: true,
      transformOptions: {
        enableImplicitConversion: true,
      },
    }),
  );

  // Swagger API Documentation
  const config = new DocumentBuilder()
    .setTitle('CommerceHub Identity Service')
    .setDescription('Authentication and User Management API')
    .setVersion('1.0')
    .addBearerAuth()
    .addTag('Authentication', 'User authentication endpoints')
    .addTag('Users', 'User management endpoints')
    .build();

  const document = SwaggerModule.createDocument(app, config);
  SwaggerModule.setup('api/docs', app, document);

  await app.listen(port);

  logger.info(`🚀 ${serviceName} is running on http://localhost:${port}`);
  logger.info(`📚 API Documentation: http://localhost:${port}/api/docs`);
  logger.info(`❤️  Health Check: http://localhost:${port}/health`);
}

bootstrap();
