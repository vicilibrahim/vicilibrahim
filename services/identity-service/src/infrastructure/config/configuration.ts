export default () => ({
  port: parseInt(process.env.PORT || '3000', 10),
  serviceName: process.env.SERVICE_NAME || 'identity-service',
  nodeEnv: process.env.NODE_ENV || 'development',
  database: {
    host: process.env.DATABASE_HOST || 'localhost',
    port: parseInt(process.env.DATABASE_PORT || '5432', 10),
    username: process.env.DATABASE_USER || 'postgres',
    password: process.env.DATABASE_PASSWORD || 'postgres',
    database: process.env.DATABASE_NAME || 'identity_db',
    ssl: process.env.DATABASE_SSL === 'true',
  },
  jwt: {
    secret: process.env.JWT_SECRET || 'your-secret-key',
    expiresIn: process.env.JWT_EXPIRES_IN || '15m',
    refreshSecret: process.env.JWT_REFRESH_SECRET || 'your-refresh-secret',
    refreshExpiresIn: process.env.JWT_REFRESH_EXPIRES_IN || '7d',
  },
  rabbitmq: {
    url: process.env.RABBITMQ_URL || 'amqp://localhost:5672',
    exchange: process.env.RABBITMQ_EXCHANGE || 'commercehub_events',
    queue: process.env.RABBITMQ_QUEUE || 'identity_service_queue',
  },
  logging: {
    level: process.env.LOG_LEVEL || 'info',
  },
  bcrypt: {
    saltRounds: parseInt(process.env.BCRYPT_SALT_ROUNDS || '10', 10),
  },
});
