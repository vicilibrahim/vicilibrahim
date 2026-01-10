import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { DataSource } from 'typeorm';
import { createLogger } from '@commercehub/shared';

/**
 * Service responsible for provisioning tenant infrastructure
 * Creates PostgreSQL schemas and MongoDB collections for each tenant
 */
@Injectable()
export class ProvisioningService {
  private readonly logger = createLogger('ProvisioningService');
  private dataSource: DataSource;

  constructor(private readonly configService: ConfigService) {}

  /**
   * Provision complete infrastructure for a new tenant
   */
  async provisionTenant(tenantId: string, slug: string): Promise<void> {
    this.logger.info(`Provisioning tenant: ${slug} (${tenantId})`);

    try {
      // Create PostgreSQL schema for tenant
      await this.createPostgreSQLSchema(slug);

      // Create tables in tenant schema
      await this.createTenantTables(slug);

      // Create MongoDB collections for tenant
      await this.createMongoDBCollections(slug);

      this.logger.info(`Tenant provisioned successfully: ${slug}`);
    } catch (error) {
      this.logger.error(`Failed to provision tenant: ${slug}`, error);
      throw new Error(`Tenant provisioning failed: ${error.message}`);
    }
  }

  /**
   * Deprovision tenant infrastructure (when tenant is deleted)
   */
  async deprovisionTenant(tenantId: string, slug: string): Promise<void> {
    this.logger.info(`Deprovisioning tenant: ${slug} (${tenantId})`);

    try {
      // Drop PostgreSQL schema
      await this.dropPostgreSQLSchema(slug);

      // Drop MongoDB collections
      await this.dropMongoDBCollections(slug);

      this.logger.info(`Tenant deprovisioned successfully: ${slug}`);
    } catch (error) {
      this.logger.error(`Failed to deprovision tenant: ${slug}`, error);
      throw new Error(`Tenant deprovisioning failed: ${error.message}`);
    }
  }

  /**
   * Create PostgreSQL schema for tenant
   */
  private async createPostgreSQLSchema(slug: string): Promise<void> {
    const schemaName = this.sanitizeIdentifier(slug);

    this.logger.info(`Creating PostgreSQL schema: ${schemaName}`);

    const dataSource = await this.getDataSource();

    await dataSource.query(`CREATE SCHEMA IF NOT EXISTS "${schemaName}"`);

    this.logger.info(`Schema created: ${schemaName}`);
  }

  /**
   * Create tables in tenant schema (Identity Service tables)
   */
  private async createTenantTables(slug: string): Promise<void> {
    const schemaName = this.sanitizeIdentifier(slug);

    this.logger.info(`Creating tables in schema: ${schemaName}`);

    const dataSource = await this.getDataSource();

    // Set search_path to tenant schema
    await dataSource.query(`SET search_path TO "${schemaName}"`);

    // Create users table
    await dataSource.query(`
      CREATE TABLE IF NOT EXISTS "${schemaName}".users (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        tenant_id UUID NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        role VARCHAR(50) DEFAULT 'user',
        is_active BOOLEAN DEFAULT true,
        last_login_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT NOW(),
        updated_at TIMESTAMP DEFAULT NOW()
      );
    `);

    // Create indexes
    await dataSource.query(`
      CREATE INDEX IF NOT EXISTS idx_users_tenant_id ON "${schemaName}".users(tenant_id);
      CREATE INDEX IF NOT EXISTS idx_users_email ON "${schemaName}".users(email);
    `);

    // Reset search_path
    await dataSource.query(`SET search_path TO public`);

    this.logger.info(`Tables created in schema: ${schemaName}`);
  }

  /**
   * Drop PostgreSQL schema and all its tables
   */
  private async dropPostgreSQLSchema(slug: string): Promise<void> {
    const schemaName = this.sanitizeIdentifier(slug);

    this.logger.info(`Dropping PostgreSQL schema: ${schemaName}`);

    const dataSource = await this.getDataSource();

    await dataSource.query(`DROP SCHEMA IF EXISTS "${schemaName}" CASCADE`);

    this.logger.info(`Schema dropped: ${schemaName}`);
  }

  /**
   * Create MongoDB collections for tenant (Product Service)
   */
  private async createMongoDBCollections(slug: string): Promise<void> {
    this.logger.info(`Creating MongoDB collections for: ${slug}`);

    // In a real implementation, you would:
    // 1. Connect to MongoDB
    // 2. Create collections with prefix: {slug}_products, {slug}_categories
    // 3. Create indexes

    // For now, we'll log this as it requires MongoDB connection
    this.logger.info(`MongoDB collections would be created: ${slug}_products, ${slug}_categories`);

    // TODO: Implement MongoDB collection creation when MongoDB is integrated
    // const mongoClient = await MongoClient.connect(mongoUrl);
    // const db = mongoClient.db('commercehub');
    // await db.createCollection(`${slug}_products`);
    // await db.createCollection(`${slug}_categories`);
    // await db.collection(`${slug}_products`).createIndex({ sku: 1 }, { unique: true });
  }

  /**
   * Drop MongoDB collections for tenant
   */
  private async dropMongoDBCollections(slug: string): Promise<void> {
    this.logger.info(`Dropping MongoDB collections for: ${slug}`);

    // TODO: Implement MongoDB collection deletion
    this.logger.info(`MongoDB collections would be dropped: ${slug}_products, ${slug}_categories`);
  }

  /**
   * Get DataSource for PostgreSQL operations
   */
  private async getDataSource(): Promise<DataSource> {
    if (this.dataSource && this.dataSource.isInitialized) {
      return this.dataSource;
    }

    this.dataSource = new DataSource({
      type: 'postgres',
      host: this.configService.get('TENANT_DB_HOST'),
      port: this.configService.get('TENANT_DB_PORT'),
      username: this.configService.get('TENANT_DB_USER'),
      password: this.configService.get('TENANT_DB_PASSWORD'),
      database: 'postgres', // Connect to postgres database to create schemas
    });

    await this.dataSource.initialize();
    return this.dataSource;
  }

  /**
   * Sanitize identifier to prevent SQL injection
   */
  private sanitizeIdentifier(identifier: string): string {
    // Only allow alphanumeric characters, underscores, and hyphens
    return identifier.replace(/[^a-z0-9_-]/gi, '_');
  }

  /**
   * Check if tenant schema exists
   */
  async schemaExists(slug: string): Promise<boolean> {
    const schemaName = this.sanitizeIdentifier(slug);
    const dataSource = await this.getDataSource();

    const result = await dataSource.query(
      `SELECT schema_name FROM information_schema.schemata WHERE schema_name = $1`,
      [schemaName],
    );

    return result.length > 0;
  }
}
