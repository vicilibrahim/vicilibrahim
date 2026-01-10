# CommerceHub AI - E-Commerce Management Platform

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Node.js](https://img.shields.io/badge/node-%3E%3D18.0.0-brightgreen.svg)](https://nodejs.org/)
[![TypeScript](https://img.shields.io/badge/typescript-5.1-blue.svg)](https://www.typescriptlang.org/)

Modern microservices-based e-commerce management platform for businesses to manage multiple marketplaces, e-commerce sites, products, inventory, and orders from a single centralized system.

## 🎯 Features

- **Multi-Channel Management**: Integrate with multiple marketplaces (Trendyol, Hepsiburada, N11) and e-commerce platforms (Ideasoft, Shopify, WooCommerce)
- **Centralized Product Catalog**: Manage products once, distribute everywhere
- **Unified Order Management**: Handle orders from all channels in one place
- **Smart Inventory Management**: Real-time stock tracking across multiple warehouses
- **Event-Driven Architecture**: Asynchronous communication between services
- **API-First Design**: RESTful APIs with OpenAPI documentation
- **Scalable Microservices**: Independent services that can scale individually

## 🏗️ Architecture

### Microservices

The platform consists of the following microservices:

1. **Identity Service** - Authentication and user management
2. **Product Service** - Product catalog and categories
3. **Inventory Service** - Stock management and warehouses
4. **Order Service** - Order processing and fulfillment
5. **Channel Service** - Marketplace and e-commerce integrations

### Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         API Gateway (Future)                     │
└───────────────────────────┬─────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
┌───────▼────────┐  ┌──────▼───────┐  ┌───────▼────────┐
│   Identity     │  │   Product    │  │   Inventory    │
│   Service      │  │   Service    │  │   Service      │
│  (PostgreSQL)  │  │  (MongoDB)   │  │  (PostgreSQL)  │
└────────────────┘  └──────────────┘  └────────────────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            │
                    ┌───────▼────────┐
                    │   RabbitMQ     │
                    │  Event Bus     │
                    └────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
┌───────▼────────┐  ┌──────▼───────┐  ┌───────▼────────┐
│     Order      │  │   Channel    │  │  Elasticsearch │
│    Service     │  │   Service    │  │ (Product Search)│
│  (PostgreSQL)  │  │  (PostgreSQL)│  └────────────────┘
└────────────────┘  └──────────────┘
```

### Technology Stack

- **Backend**: Node.js, NestJS, TypeScript
- **Databases**:
  - PostgreSQL (Identity, Inventory, Order, Channel)
  - MongoDB (Product catalog)
  - Redis (Caching)
  - Elasticsearch (Product search)
- **Message Queue**: RabbitMQ
- **API Documentation**: Swagger/OpenAPI
- **Testing**: Jest
- **Containerization**: Docker, Docker Compose

## 📋 Prerequisites

- **Node.js** 18+ and npm
- **Docker** and Docker Compose
- **Git**

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/commercehub-microservices.git
cd commercehub-microservices
```

### 2. Install Dependencies

```bash
# Install root dependencies
npm install

# Install shared package dependencies
cd shared && npm install && cd ..

# Install Identity Service dependencies
cd services/identity-service && npm install && cd ../..
```

### 3. Start Infrastructure Services

```bash
# Start all services with Docker Compose
npm run dev

# Or manually:
cd infrastructure/docker-compose
docker-compose up -d
```

This will start:
- PostgreSQL (Identity, Inventory, Order, Channel databases)
- MongoDB
- Redis
- RabbitMQ
- Elasticsearch
- Identity Service

### 4. Access Services

- **Identity Service API**: http://localhost:3001
- **Identity Service Docs**: http://localhost:3001/api/docs
- **RabbitMQ Management**: http://localhost:15672 (rabbitmq/rabbitmq)
- **Elasticsearch**: http://localhost:9200

### 5. Test the API

#### Register a new user

```bash
curl -X POST http://localhost:3001/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123",
    "firstName": "John",
    "lastName": "Doe"
  }'
```

#### Login

```bash
curl -X POST http://localhost:3001/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123"
  }'
```

## 📁 Project Structure

```
commercehub-microservices/
├── services/                    # Microservices
│   ├── identity-service/       # Authentication & users
│   ├── product-service/        # Products & categories (Coming soon)
│   ├── inventory-service/      # Stock management (Coming soon)
│   ├── order-service/          # Order processing (Coming soon)
│   └── channel-service/        # Channel integrations (Coming soon)
│
├── shared/                      # Shared code
│   ├── types/                  # TypeScript types
│   │   ├── common.ts
│   │   ├── events.ts
│   │   ├── product.types.ts
│   │   └── order.types.ts
│   └── utils/                  # Utilities
│       ├── logger.ts
│       ├── error-handler.ts
│       └── validators.ts
│
├── infrastructure/              # Infrastructure config
│   └── docker-compose/
│       ├── docker-compose.yml
│       └── .env.example
│
├── docs/                        # Documentation
│   └── architecture.md
│
├── package.json                 # Root package (workspace)
├── .gitignore
├── .eslintrc.js
├── .prettierrc
└── README.md
```

## 🔧 Development

### Install Workspace Dependencies

```bash
npm install
```

### Build All Services

```bash
npm run build
```

### Run Tests

```bash
npm run test
```

### Lint Code

```bash
npm run lint
```

### Format Code

```bash
npm run format
```

## 🐳 Docker Commands

```bash
# Start all services
npm run dev
# or
docker-compose -f infrastructure/docker-compose/docker-compose.yml up

# Start with rebuild
npm run dev:build
# or
docker-compose -f infrastructure/docker-compose/docker-compose.yml up --build

# Stop all services
npm run down
# or
docker-compose -f infrastructure/docker-compose/docker-compose.yml down

# View logs
docker-compose -f infrastructure/docker-compose/docker-compose.yml logs -f identity-service

# View all service logs
docker-compose -f infrastructure/docker-compose/docker-compose.yml logs -f
```

## 📚 API Documentation

Each service provides interactive API documentation via Swagger UI:

- **Identity Service**: http://localhost:3001/api/docs

## 🎯 Roadmap

### Phase 1: Core Services ✅
- [x] Identity Service (Authentication & User Management)
- [x] Shared types and utilities
- [x] Docker infrastructure setup
- [x] Event-driven architecture foundation

### Phase 2: Product & Inventory (In Progress)
- [ ] Product Service (Catalog, Categories, Variants)
- [ ] Inventory Service (Stock, Warehouses, Reservations)
- [ ] Elasticsearch integration for product search

### Phase 3: Orders & Fulfillment
- [ ] Order Service (Orders, Returns, Timeline)
- [ ] Payment integration
- [ ] Shipping integration

### Phase 4: Channel Integrations
- [ ] Channel Service base
- [ ] Trendyol adapter
- [ ] Hepsiburada adapter
- [ ] N11 adapter
- [ ] Ideasoft adapter
- [ ] Shopify adapter
- [ ] WooCommerce adapter

### Phase 5: Advanced Features
- [ ] API Gateway (Kong/NGINX)
- [ ] Analytics Service
- [ ] Notification Service
- [ ] Admin Dashboard (React)
- [ ] Mobile App (React Native)

## 🔐 Security

- JWT authentication with access and refresh tokens
- Password hashing with bcrypt
- Role-based access control (RBAC)
- Input validation with class-validator
- SQL injection prevention
- CORS configuration
- Environment-based secrets

## 🧪 Testing

```bash
# Run unit tests for all services
npm run test

# Run tests for specific service
cd services/identity-service
npm run test

# Run tests with coverage
npm run test:cov

# Run e2e tests
npm run test:e2e
```

## 📊 Monitoring & Health Checks

Each service provides health check endpoints:

```bash
# Identity Service health
curl http://localhost:3001/health

# Identity Service readiness
curl http://localhost:3001/health/ready
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License.

## 🆘 Support

For issues, questions, or contributions:
- Create an issue on GitHub
- Contact the development team

## 🙏 Acknowledgments

- NestJS for the amazing framework
- TypeORM for database ORM
- RabbitMQ for message queuing
- The open-source community

---

**Made with ❤️ by the CommerceHub Team**
