import { Injectable, OnModuleInit, OnModuleDestroy } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import * as amqp from 'amqplib';
import { v4 as uuidv4 } from 'uuid';
import { createLogger } from '@commercehub/shared';
import {
  UserCreatedEvent,
  UserUpdatedEvent,
  UserDeletedEvent,
  UserLoginEvent,
} from '@commercehub/shared';

@Injectable()
export class EventPublisherService implements OnModuleInit, OnModuleDestroy {
  private connection: amqp.Connection;
  private channel: amqp.Channel;
  private readonly logger = createLogger('EventPublisherService');
  private readonly exchange: string;
  private readonly tenantId = 'default'; // In production, get from context

  constructor(private readonly configService: ConfigService) {
    this.exchange = this.configService.get<string>('rabbitmq.exchange')!;
  }

  async onModuleInit() {
    try {
      const rabbitmqUrl = this.configService.get<string>('rabbitmq.url')!;
      this.connection = await amqp.connect(rabbitmqUrl);
      this.channel = await this.connection.createChannel();
      await this.channel.assertExchange(this.exchange, 'topic', { durable: true });
      this.logger.info('Connected to RabbitMQ and exchange asserted');
    } catch (error) {
      this.logger.error('Failed to connect to RabbitMQ', error);
      // In production, you might want to retry or handle this differently
    }
  }

  async onModuleDestroy() {
    try {
      await this.channel?.close();
      await this.connection?.close();
      this.logger.info('RabbitMQ connection closed');
    } catch (error) {
      this.logger.error('Error closing RabbitMQ connection', error);
    }
  }

  private async publishEvent(routingKey: string, event: any): Promise<void> {
    try {
      if (!this.channel) {
        this.logger.warn('RabbitMQ channel not available, event not published');
        return;
      }

      const message = Buffer.from(JSON.stringify(event));
      this.channel.publish(this.exchange, routingKey, message, {
        persistent: true,
        contentType: 'application/json',
      });

      this.logger.info(`Event published: ${routingKey}`, { eventId: event.eventId });
    } catch (error) {
      this.logger.error(`Failed to publish event: ${routingKey}`, error);
    }
  }

  async publishUserCreated(payload: UserCreatedEvent['payload']): Promise<void> {
    const event: UserCreatedEvent = {
      eventId: uuidv4(),
      eventType: 'user.created',
      timestamp: new Date(),
      tenantId: this.tenantId,
      payload,
    };
    await this.publishEvent('user.created', event);
  }

  async publishUserUpdated(payload: UserUpdatedEvent['payload']): Promise<void> {
    const event: UserUpdatedEvent = {
      eventId: uuidv4(),
      eventType: 'user.updated',
      timestamp: new Date(),
      tenantId: this.tenantId,
      payload,
    };
    await this.publishEvent('user.updated', event);
  }

  async publishUserDeleted(payload: UserDeletedEvent['payload']): Promise<void> {
    const event: UserDeletedEvent = {
      eventId: uuidv4(),
      eventType: 'user.deleted',
      timestamp: new Date(),
      tenantId: this.tenantId,
      payload,
    };
    await this.publishEvent('user.deleted', event);
  }

  async publishUserLogin(payload: UserLoginEvent['payload']): Promise<void> {
    const event: UserLoginEvent = {
      eventId: uuidv4(),
      eventType: 'user.login',
      timestamp: new Date(),
      tenantId: this.tenantId,
      payload,
    };
    await this.publishEvent('user.login', event);
  }
}
