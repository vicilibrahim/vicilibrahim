# CommerceHub Architecture

## Overview

CommerceHub is built using a microservices architecture pattern where each service is responsible for a specific business domain. Services communicate asynchronously via RabbitMQ for event-driven workflows and synchronously via REST APIs for direct requests.

## Architectural Principles

### 1. Microservices Architecture
- Each service is independently deployable
- Services own their data (Database per Service pattern)
- Loose coupling between services
- Technology agnostic (can use different tech stacks per service)

### 2. API-First Design
- Every service exposes a RESTful API
- OpenAPI 3.0 specification for all APIs
- Swagger documentation for interactive testing
- Versioned APIs (e.g., `/api/v1/...`)

### 3. Event-Driven Communication
- Services emit events for state changes
- Other services subscribe to relevant events
- Asynchronous processing for non-critical operations
- Event sourcing for audit trails

### 4. Domain-Driven Design (DDD)
- Clear bounded contexts for each service
- Rich domain models
- Repository pattern for data access
- Service layer for business logic

### 5. Clean Architecture
```
┌─────────────────────────────────────────┐
│         Presentation Layer              │
│   (Controllers, DTOs, Validators)       │
└─────────────┬───────────────────────────┘
              │
┌─────────────▼───────────────────────────┐
│         Application Layer               │
│    (Use Cases, Service Layer)           │
└─────────────┬───────────────────────────┘
              │
┌─────────────▼───────────────────────────┐
│           Domain Layer                  │
│  (Entities, Value Objects, Repos)       │
└─────────────┬───────────────────────────┘
              │
┌─────────────▼───────────────────────────┐
│       Infrastructure Layer              │
│  (Database, Events, External APIs)      │
└─────────────────────────────────────────┘
```

## Service Boundaries

### Identity Service
**Responsibility**: Authentication, authorization, user management

**API Endpoints**:
- User registration and login
- Token management (access/refresh)
- User CRUD operations
- Role and permission management

**Events Published**:
- `user.created`
- `user.updated`
- `user.deleted`
- `user.login`

**Database**: PostgreSQL (users, roles, permissions)

### Product Service
**Responsibility**: Product catalog, categories, variants, media

**API Endpoints**:
- Product CRUD operations
- Category management
- Variant management
- Media upload and management
- Product search (via Elasticsearch)

**Events Published**:
- `product.created`
- `product.updated`
- `product.deleted`
- `product.published`

**Events Consumed**:
- `channel.connected` - To map products to channel

**Database**: MongoDB (flexible schema for product attributes)

### Inventory Service
**Responsibility**: Stock management, warehouses, stock reservations

**API Endpoints**:
- Inventory item management
- Warehouse management
- Stock adjustments
- Stock reservations
- Stock transfers

**Events Published**:
- `inventory.updated`
- `inventory.low_stock`
- `inventory.reserved`
- `inventory.released`

**Events Consumed**:
- `order.created` - Reserve stock
- `order.cancelled` - Release stock

**Database**: PostgreSQL (transactional data)

### Order Service
**Responsibility**: Order processing, fulfillment, returns

**API Endpoints**:
- Order creation and management
- Order status updates
- Fulfillment operations
- Return management
- Order timeline

**Events Published**:
- `order.created`
- `order.confirmed`
- `order.shipped`
- `order.delivered`
- `order.cancelled`

**Events Consumed**:
- `inventory.reserved` - Confirm order
- `channel.order.received` - Create order from channel

**Database**: PostgreSQL (transactional data)

### Channel Service
**Responsibility**: Marketplace and e-commerce platform integrations

**API Endpoints**:
- Channel connection management
- Product sync to channels
- Order sync from channels
- Inventory sync to channels
- Category and attribute mappings

**Events Published**:
- `channel.connected`
- `channel.sync.started`
- `channel.sync.completed`
- `channel.order.received`

**Events Consumed**:
- `product.created` - Sync to channels
- `inventory.updated` - Update channel inventory

**Database**: PostgreSQL (channel configs, mappings, sync status)

## Communication Patterns

### Synchronous Communication (REST APIs)
Used for:
- Client-to-service communication
- Service-to-service queries (when immediate response needed)

Example:
```
Client → GET /api/v1/products/{id} → Product Service → Response
```

### Asynchronous Communication (Events)
Used for:
- State change notifications
- Background processing
- Service-to-service updates

Example:
```
Order Service → order.created event → RabbitMQ → Inventory Service (reserve stock)
```

## Data Management

### Database per Service
Each service has its own database:

```
Identity Service    → PostgreSQL (identity_db)
Product Service     → MongoDB (products)
Inventory Service   → PostgreSQL (inventory_db)
Order Service       → PostgreSQL (order_db)
Channel Service     → PostgreSQL (channel_db)
```

Benefits:
- Service independence
- Technology diversity
- Failure isolation
- Scalability

Challenges:
- Data consistency (eventual consistency)
- Cross-service queries (solved via events)
- Distributed transactions (saga pattern)

### Event Store
All events are stored in RabbitMQ with:
- Durable queues
- Message persistence
- Dead letter queues for failed processing

## Saga Pattern for Distributed Transactions

Example: Order Creation Saga

```
1. Order Service: Create order (status: pending)
   └─> Publish: order.created

2. Inventory Service: Reserve stock
   └─> If success: Publish inventory.reserved
   └─> If failed: Publish inventory.reservation_failed

3. Order Service: Listen to inventory.reserved
   └─> Update order (status: confirmed)
   └─> Publish: order.confirmed

4. If any step fails: Compensating transactions
   └─> Order Service: Cancel order
   └─> Inventory Service: Release reservation
```

## Security

### Authentication Flow

```
1. User → POST /auth/login → Identity Service
   └─> Validate credentials
   └─> Generate JWT (access + refresh tokens)
   └─> Return tokens

2. User → GET /products (with Bearer token) → Product Service
   └─> Validate token
   └─> Extract user info from token
   └─> Process request
```

### Token Structure

```json
{
  "sub": "user-uuid",
  "email": "user@example.com",
  "roles": ["user", "admin"],
  "iat": 1234567890,
  "exp": 1234567999
}
```

## Scalability

### Horizontal Scaling
Each service can be scaled independently:

```
Identity Service: 2 instances (authentication load)
Product Service:  3 instances (read-heavy)
Order Service:    4 instances (write-heavy)
```

### Caching Strategy
- Redis for session data
- Elasticsearch for product search
- Application-level caching for frequently accessed data

### Load Balancing
Future: API Gateway (Kong/NGINX) for:
- Load balancing
- Rate limiting
- Request routing
- Authentication
- Response caching

## Monitoring & Observability

### Health Checks
Each service provides:
- `/health` - Liveness probe
- `/health/ready` - Readiness probe

### Logging
- Structured logging (Winston)
- Centralized log aggregation (future: ELK stack)
- Correlation IDs for request tracing

### Metrics
Future implementation:
- Prometheus for metrics collection
- Grafana for visualization
- Service-level indicators (SLIs)

## Deployment

### Docker Containers
Each service:
- Has its own Dockerfile
- Multi-stage build for optimization
- Non-root user for security
- Health checks

### Docker Compose
Development environment:
- All services in one compose file
- Service dependencies
- Network isolation
- Volume persistence

### Production (Future)
- Kubernetes for orchestration
- Helm charts for deployment
- Auto-scaling based on metrics
- Rolling updates with zero downtime

## Testing Strategy

### Unit Tests
- Domain logic testing
- Service layer testing
- Repository mocking
- Target: 70%+ coverage

### Integration Tests
- API endpoint testing
- Database integration
- Event publishing/consuming
- Real dependencies

### E2E Tests
- Full workflow testing
- Cross-service scenarios
- User journey testing

## Future Enhancements

1. **API Gateway**: Centralized entry point
2. **Service Mesh**: Istio for service-to-service communication
3. **CQRS**: Command Query Responsibility Segregation
4. **GraphQL**: Alternative to REST for complex queries
5. **Serverless Functions**: For specific use cases
6. **AI/ML Integration**: For recommendations, demand forecasting
7. **Real-time Features**: WebSockets for live updates

## References

- [Microservices Patterns](https://microservices.io/patterns/)
- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [Event-Driven Architecture](https://martinfowler.com/articles/201701-event-driven.html)
- [Saga Pattern](https://microservices.io/patterns/data/saga.html)
