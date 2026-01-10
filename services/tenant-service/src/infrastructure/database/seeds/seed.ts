import { DataSource } from 'typeorm';
import * as bcrypt from 'bcrypt';
import { SubscriptionPlan } from '../../../domain/entities/subscription-plan.entity';
import { SuperAdmin } from '../../../domain/entities/super-admin.entity';

const AppDataSource = new DataSource({
  type: 'postgres',
  host: process.env.DATABASE_HOST || 'localhost',
  port: parseInt(process.env.DATABASE_PORT || '5432'),
  username: process.env.DATABASE_USER || 'postgres',
  password: process.env.DATABASE_PASSWORD || 'postgres',
  database: process.env.DATABASE_NAME || 'master_db',
  entities: [SubscriptionPlan, SuperAdmin],
  synchronize: false,
});

async function seed() {
  console.log('🌱 Starting database seeding...\n');

  try {
    await AppDataSource.initialize();
    console.log('✅ Database connection established\n');

    // Seed Subscription Plans
    await seedSubscriptionPlans();

    // Seed Super Admin
    await seedSuperAdmin();

    console.log('\n✅ Database seeding completed successfully!');
    process.exit(0);
  } catch (error) {
    console.error('❌ Error during seeding:', error);
    process.exit(1);
  }
}

async function seedSubscriptionPlans() {
  console.log('📦 Seeding subscription plans...');

  const planRepository = AppDataSource.getRepository(SubscriptionPlan);

  const plans = [
    {
      name: 'Starter',
      slug: 'starter',
      description: 'Perfect for small businesses getting started with multi-channel selling',
      priceMonthly: 999,
      priceYearly: 9990,
      currency: 'TRY',
      features: {
        products_limit: 1000,
        orders_limit_monthly: 500,
        users_limit: 3,
        channels_limit: 2,
        warehouses_limit: 1,
        api_calls_daily: 10000,
        ai_requests_monthly: 100,
        storage_gb: 10,
        features: {
          multi_channel: true,
          advanced_analytics: false,
          ai_content: true,
          whatsapp: false,
          priority_support: false,
          custom_integrations: false,
          white_label: false,
        },
      },
      isPublic: true,
      sortOrder: 1,
    },
    {
      name: 'Professional',
      slug: 'professional',
      description: 'For growing businesses needing advanced features and higher limits',
      priceMonthly: 2999,
      priceYearly: 29990,
      currency: 'TRY',
      features: {
        products_limit: 10000,
        orders_limit_monthly: 2000,
        users_limit: 10,
        channels_limit: 5,
        warehouses_limit: 3,
        api_calls_daily: 50000,
        ai_requests_monthly: 500,
        storage_gb: 50,
        features: {
          multi_channel: true,
          advanced_analytics: true,
          ai_content: true,
          whatsapp: true,
          priority_support: true,
          custom_integrations: false,
          white_label: false,
        },
      },
      isPublic: true,
      sortOrder: 2,
    },
    {
      name: 'Enterprise',
      slug: 'enterprise',
      description: 'Unlimited power for large enterprises with custom requirements',
      priceMonthly: 7999,
      priceYearly: 79990,
      currency: 'TRY',
      features: {
        products_limit: -1, // -1 means unlimited
        orders_limit_monthly: -1,
        users_limit: -1,
        channels_limit: -1,
        warehouses_limit: -1,
        api_calls_daily: -1,
        ai_requests_monthly: -1,
        storage_gb: 500,
        features: {
          multi_channel: true,
          advanced_analytics: true,
          ai_content: true,
          whatsapp: true,
          priority_support: true,
          custom_integrations: true,
          white_label: true,
        },
      },
      isPublic: true,
      sortOrder: 3,
    },
  ];

  for (const planData of plans) {
    const existing = await planRepository.findOne({ where: { slug: planData.slug } });

    if (existing) {
      console.log(`   ⏭️  Plan "${planData.name}" already exists, skipping...`);
      continue;
    }

    const plan = planRepository.create(planData);
    await planRepository.save(plan);
    console.log(`   ✅ Created plan: ${planData.name} (${planData.priceMonthly} ${planData.currency}/month)`);
  }

  console.log('✅ Subscription plans seeded\n');
}

async function seedSuperAdmin() {
  console.log('👤 Seeding super admin...');

  const superAdminRepository = AppDataSource.getRepository(SuperAdmin);

  const adminEmail = 'admin@commercehub.com';
  const adminPassword = 'SuperSecure123!';

  const existing = await superAdminRepository.findOne({ where: { email: adminEmail } });

  if (existing) {
    console.log('   ⏭️  Super admin already exists, skipping...');
    console.log('   📧 Email: admin@commercehub.com');
    console.log('   🔒 Use existing password\n');
    return;
  }

  const passwordHash = await bcrypt.hash(adminPassword, 10);

  const superAdmin = superAdminRepository.create({
    email: adminEmail,
    passwordHash,
    name: 'Super Admin',
    role: 'super_admin',
    isActive: true,
  });

  await superAdminRepository.save(superAdmin);

  console.log('   ✅ Super admin created!');
  console.log('   📧 Email: admin@commercehub.com');
  console.log('   🔒 Password: SuperSecure123!');
  console.log('   ⚠️  CHANGE THIS PASSWORD IN PRODUCTION!\n');
}

// Run seed
seed();
