# Identity Service

Authentication and User Management Service for CommerceHub platform.

## Features

- User registration and authentication
- JWT-based token authentication (access + refresh tokens)
- Role-based access control (RBAC)
- Password hashing with bcrypt
- User management (CRUD operations)
- Event publishing via RabbitMQ
- OpenAPI/Swagger documentation
- Health check endpoints

## Tech Stack

- **Framework:** NestJS
- **Language:** TypeScript
- **Database:** PostgreSQL
- **ORM:** TypeORM
- **Authentication:** JWT (passport-jwt)
- **Message Queue:** RabbitMQ (amqplib)
- **Validation:** class-validator
- **Documentation:** Swagger/OpenAPI
- **Testing:** Jest

## Prerequisites

- Node.js 18+
- PostgreSQL 15+
- RabbitMQ 3+
- npm or yarn

## Installation

```bash
# Install dependencies
npm install

# Copy environment variables
cp .env.example .env

# Edit .env with your configuration
```

## Configuration

Create a `.env` file in the root directory:

```env
# Application
NODE_ENV=development
PORT=3000
SERVICE_NAME=identity-service

# Database
DATABASE_HOST=localhost
DATABASE_PORT=5432
DATABASE_USER=postgres
DATABASE_PASSWORD=postgres
DATABASE_NAME=identity_db
DATABASE_SSL=false

# JWT
JWT_SECRET=your-secret-key-change-this-in-production
JWT_EXPIRES_IN=15m
JWT_REFRESH_SECRET=your-refresh-secret-key-change-this-in-production
JWT_REFRESH_EXPIRES_IN=7d

# RabbitMQ
RABBITMQ_URL=amqp://rabbitmq:rabbitmq@localhost:5672
RABBITMQ_EXCHANGE=commercehub_events
RABBITMQ_QUEUE=identity_service_queue

# Logging
LOG_LEVEL=info

# Security
BCRYPT_SALT_ROUNDS=10
```

## Database Setup

```bash
# Create database
createdb identity_db

# Run migrations (in development, auto-sync is enabled)
npm run migration:run
```

## Running the Service

```bash
# Development mode
npm run start:dev

# Production mode
npm run build
npm run start:prod

# Debug mode
npm run start:debug
```

## Docker

```bash
# Build image
docker build -t commercehub/identity-service .

# Run container
docker run -p 3000:3000 \
  -e DATABASE_URL=postgresql://user:pass@host:5432/db \
  -e RABBITMQ_URL=amqp://user:pass@host:5672 \
  commercehub/identity-service
```

## API Endpoints

### Authentication

```
POST   /api/v1/auth/register       - Register a new user
POST   /api/v1/auth/login          - Login user
POST   /api/v1/auth/refresh        - Refresh access token
POST   /api/v1/auth/change-password - Change password (authenticated)
POST   /api/v1/auth/logout         - Logout (authenticated)
```

### Users

```
GET    /api/v1/users/me            - Get current user profile
GET    /api/v1/users               - Get all users (admin only)
GET    /api/v1/users/:id           - Get user by ID
PUT    /api/v1/users/:id           - Update user
DELETE /api/v1/users/:id           - Delete user (admin only)
POST   /api/v1/users/:id/roles     - Assign role to user (admin only)
DELETE /api/v1/users/:id/roles/:roleName - Remove role from user (admin only)
GET    /api/v1/users/:id/roles     - Get user roles
GET    /api/v1/users/:id/permissions - Get user permissions
```

### Health

```
GET    /health                     - Health check
GET    /health/ready               - Readiness check
```

## API Documentation

Access Swagger UI documentation:

```
http://localhost:3000/api/docs
```

## Testing

```bash
# Unit tests
npm run test

# E2E tests
npm run test:e2e

# Test coverage
npm run test:cov
```

## Events Published

The service publishes the following events to RabbitMQ:

- `user.created` - When a new user is registered
- `user.updated` - When user information is updated
- `user.deleted` - When a user is deleted
- `user.login` - When a user logs in

Event format:
```json
{
  "eventId": "uuid",
  "eventType": "user.created",
  "timestamp": "2024-01-01T00:00:00.000Z",
  "tenantId": "tenant-uuid",
  "payload": {
    "userId": "user-uuid",
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe"
  }
}
```

## Default Roles

The service comes with two default roles:

1. **admin** - Full access to all resources
2. **user** - Basic access (read user information)

## Security

- Passwords are hashed using bcrypt with 10 salt rounds
- JWT tokens use RS256 algorithm (configurable)
- All endpoints (except auth) require authentication
- Admin endpoints require admin role
- CORS is enabled for cross-origin requests
- Input validation using class-validator
- SQL injection prevention via parameterized queries

## Architecture

The service follows Clean Architecture principles with the following layers:

- **API Layer** (`src/api/`) - Controllers, DTOs, Guards, Strategies
- **Domain Layer** (`src/domain/`) - Entities, Repositories, Services (business logic)
- **Infrastructure Layer** (`src/infrastructure/`) - Database, Events, Configuration

## Error Handling

The service uses custom error classes from `@commercehub/shared`:

- `ValidationError` (400) - Invalid input
- `UnauthorizedError` (401) - Authentication failed
- `ForbiddenError` (403) - Insufficient permissions
- `NotFoundError` (404) - Resource not found
- `ConflictError` (409) - Resource already exists
- `InternalServerError` (500) - Unexpected errors

## Logging

The service uses Winston for structured logging:

```typescript
logger.info('User logged in', { userId: '123', email: 'user@example.com' });
logger.error('Authentication failed', error, { email: 'user@example.com' });
```

Log levels: `error`, `warn`, `info`, `http`, `debug`

## Performance

- Connection pooling for PostgreSQL
- JWT stateless authentication (no session storage)
- Efficient database queries with proper indexing
- Health checks for liveness and readiness probes

## Troubleshooting

### Cannot connect to database

```bash
# Check if PostgreSQL is running
pg_isready -h localhost -p 5432

# Check connection from service
psql -h localhost -p 5432 -U postgres -d identity_db
```

### Cannot connect to RabbitMQ

```bash
# Check if RabbitMQ is running
rabbitmqctl status

# Check connection
telnet localhost 5672
```

### JWT token expired

- Access tokens expire after 15 minutes (configurable)
- Use refresh token endpoint to get new access token
- Refresh tokens expire after 7 days (configurable)

## License

MIT

## Support

For issues and questions, please contact the development team.
