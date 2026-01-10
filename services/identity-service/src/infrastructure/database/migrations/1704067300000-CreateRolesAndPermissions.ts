import { MigrationInterface, QueryRunner, Table, TableForeignKey } from 'typeorm';

export class CreateRolesAndPermissions1704067300000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Create roles table
    await queryRunner.createTable(
      new Table({
        name: 'roles',
        columns: [
          {
            name: 'id',
            type: 'uuid',
            isPrimary: true,
            generationStrategy: 'uuid',
            default: 'gen_random_uuid()',
          },
          {
            name: 'name',
            type: 'varchar',
            length: '50',
            isUnique: true,
            isNullable: false,
          },
          {
            name: 'description',
            type: 'text',
            isNullable: true,
          },
          {
            name: 'created_at',
            type: 'timestamp',
            default: 'NOW()',
          },
        ],
      }),
      true,
    );

    // Create permissions table
    await queryRunner.createTable(
      new Table({
        name: 'permissions',
        columns: [
          {
            name: 'id',
            type: 'uuid',
            isPrimary: true,
            generationStrategy: 'uuid',
            default: 'gen_random_uuid()',
          },
          {
            name: 'name',
            type: 'varchar',
            length: '100',
            isUnique: true,
            isNullable: false,
          },
          {
            name: 'resource',
            type: 'varchar',
            length: '50',
            isNullable: false,
          },
          {
            name: 'action',
            type: 'varchar',
            length: '50',
            isNullable: false,
          },
          {
            name: 'description',
            type: 'text',
            isNullable: true,
          },
          {
            name: 'created_at',
            type: 'timestamp',
            default: 'NOW()',
          },
        ],
      }),
      true,
    );

    // Create user_roles junction table
    await queryRunner.createTable(
      new Table({
        name: 'user_roles',
        columns: [
          {
            name: 'user_id',
            type: 'uuid',
            isNullable: false,
          },
          {
            name: 'role_id',
            type: 'uuid',
            isNullable: false,
          },
        ],
      }),
      true,
    );

    await queryRunner.createPrimaryKey('user_roles', ['user_id', 'role_id']);

    await queryRunner.createForeignKey(
      'user_roles',
      new TableForeignKey({
        columnNames: ['user_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'users',
        onDelete: 'CASCADE',
      }),
    );

    await queryRunner.createForeignKey(
      'user_roles',
      new TableForeignKey({
        columnNames: ['role_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'roles',
        onDelete: 'CASCADE',
      }),
    );

    // Create role_permissions junction table
    await queryRunner.createTable(
      new Table({
        name: 'role_permissions',
        columns: [
          {
            name: 'role_id',
            type: 'uuid',
            isNullable: false,
          },
          {
            name: 'permission_id',
            type: 'uuid',
            isNullable: false,
          },
        ],
      }),
      true,
    );

    await queryRunner.createPrimaryKey('role_permissions', ['role_id', 'permission_id']);

    await queryRunner.createForeignKey(
      'role_permissions',
      new TableForeignKey({
        columnNames: ['role_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'roles',
        onDelete: 'CASCADE',
      }),
    );

    await queryRunner.createForeignKey(
      'role_permissions',
      new TableForeignKey({
        columnNames: ['permission_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'permissions',
        onDelete: 'CASCADE',
      }),
    );

    // Insert default roles
    await queryRunner.query(`
      INSERT INTO roles (name, description) VALUES
        ('admin', 'Administrator with full access'),
        ('user', 'Regular user with basic access');
    `);

    // Insert default permissions
    await queryRunner.query(`
      INSERT INTO permissions (name, resource, action, description) VALUES
        ('users.read', 'users', 'read', 'Read user information'),
        ('users.write', 'users', 'write', 'Create and update users'),
        ('users.delete', 'users', 'delete', 'Delete users'),
        ('roles.manage', 'roles', 'manage', 'Manage roles and permissions');
    `);

    // Assign permissions to admin role
    await queryRunner.query(`
      INSERT INTO role_permissions (role_id, permission_id)
      SELECT r.id, p.id
      FROM roles r, permissions p
      WHERE r.name = 'admin';
    `);

    // Assign read permission to user role
    await queryRunner.query(`
      INSERT INTO role_permissions (role_id, permission_id)
      SELECT r.id, p.id
      FROM roles r, permissions p
      WHERE r.name = 'user' AND p.name = 'users.read';
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.dropTable('role_permissions');
    await queryRunner.dropTable('user_roles');
    await queryRunner.dropTable('permissions');
    await queryRunner.dropTable('roles');
  }
}
