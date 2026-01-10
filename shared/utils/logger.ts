/**
 * Centralized logging utility using Winston
 */

import winston from 'winston';

export interface LoggerConfig {
  serviceName: string;
  level?: string;
  enableConsole?: boolean;
  enableFile?: boolean;
  logDir?: string;
}

export class Logger {
  private logger: winston.Logger;
  private serviceName: string;

  constructor(config: LoggerConfig) {
    this.serviceName = config.serviceName;

    const transports: winston.transport[] = [];

    // Console transport
    if (config.enableConsole !== false) {
      transports.push(
        new winston.transports.Console({
          format: winston.format.combine(
            winston.format.colorize(),
            winston.format.timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
            winston.format.printf(({ timestamp, level, message, ...meta }) => {
              const metaStr = Object.keys(meta).length ? JSON.stringify(meta, null, 2) : '';
              return `${timestamp} [${config.serviceName}] ${level}: ${message} ${metaStr}`;
            }),
          ),
        }),
      );
    }

    // File transport
    if (config.enableFile) {
      const logDir = config.logDir || 'logs';
      transports.push(
        new winston.transports.File({
          filename: `${logDir}/error.log`,
          level: 'error',
          format: winston.format.combine(winston.format.timestamp(), winston.format.json()),
        }),
        new winston.transports.File({
          filename: `${logDir}/combined.log`,
          format: winston.format.combine(winston.format.timestamp(), winston.format.json()),
        }),
      );
    }

    this.logger = winston.createLogger({
      level: config.level || process.env.LOG_LEVEL || 'info',
      transports,
      exitOnError: false,
    });
  }

  info(message: string, meta?: any): void {
    this.logger.info(message, { service: this.serviceName, ...meta });
  }

  error(message: string, error?: Error | any, meta?: any): void {
    const errorMeta = error instanceof Error
      ? {
          error: error.message,
          stack: error.stack,
          ...meta,
        }
      : { error, ...meta };

    this.logger.error(message, { service: this.serviceName, ...errorMeta });
  }

  warn(message: string, meta?: any): void {
    this.logger.warn(message, { service: this.serviceName, ...meta });
  }

  debug(message: string, meta?: any): void {
    this.logger.debug(message, { service: this.serviceName, ...meta });
  }

  http(message: string, meta?: any): void {
    this.logger.http(message, { service: this.serviceName, ...meta });
  }
}

// Factory function for creating loggers
export function createLogger(serviceName: string, config?: Partial<LoggerConfig>): Logger {
  return new Logger({
    serviceName,
    enableConsole: true,
    enableFile: false,
    ...config,
  });
}
