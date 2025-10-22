<?php
require_once 'config/database.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'Ana Sayfa';
include 'includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1>İngilizce Öğrenmenin <span class="highlight">En Etkili Yolu</span></h1>
                <p class="hero-subtitle">A1'den C2'ye kadar tüm seviyelerde Grammar, Vocabulary, Reading, Writing ve Listening pratiği yapın. Gelişiminizi takip edin ve hedeflerinize ulaşın!</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> Hemen Başla
                    </a>
                    <a href="login.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <strong>10,000+</strong>
                            <span>Aktif Öğrenci</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-book"></i>
                        <div>
                            <strong>500+</strong>
                            <span>İnteraktif Ders</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-trophy"></i>
                        <div>
                            <strong>95%</strong>
                            <span>Başarı Oranı</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-card">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Kişiselleştirilmiş Öğrenme</h3>
                    <p>Seviyenize uygun içeriklerle öğrenin</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <div class="section-header">
            <h2>Neden Bizi Seçmelisiniz?</h2>
            <p>İngilizce öğrenmeyi kolay ve eğlenceli hale getiren özelliklerimiz</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h3>6 Seviye Sistem</h3>
                <p>A1'den C2'ye kadar CEFR standartlarına uygun kapsamlı müfredat</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3>5 Kategori</h3>
                <p>Grammar, Vocabulary, Reading, Writing ve Listening alanlarında uzmanlaşın</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Gelişim Takibi</h3>
                <p>İlerlemenizi detaylı istatistikler ve grafiklerle takip edin</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3>Başarı Rozetleri</h3>
                <p>Hedeflerinize ulaştıkça özel rozetler kazanın</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <i class="fas fa-fire"></i>
                </div>
                <h3>Günlük Streak</h3>
                <p>Ardışık gün sayınızı artırarak motivasyonunuzu koruyun</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                    <i class="fas fa-book-reader"></i>
                </div>
                <h3>Kelime Defteri</h3>
                <p>Öğrendiğiniz kelimeleri kaydedin ve pratik yapın</p>
            </div>
        </div>
    </div>
</section>

<section class="levels">
    <div class="container">
        <div class="section-header">
            <h2>Seviyeler</h2>
            <p>Size en uygun seviyeyi seçin ve öğrenmeye başlayın</p>
        </div>

        <div class="levels-grid">
            <?php
            $levels_query = "SELECT * FROM levels ORDER BY order_index ASC";
            $levels_result = $conn->query($levels_query);

            $colors = ['#28a745', '#17a2b8', '#007bff', '#6f42c1', '#fd7e14', '#dc3545'];
            $index = 0;

            while ($level = $levels_result->fetch_assoc()):
            ?>
            <div class="level-card" style="border-left-color: <?php echo $colors[$index]; ?>;">
                <div class="level-badge" style="background: <?php echo $colors[$index]; ?>;">
                    <?php echo htmlspecialchars($level['level_code']); ?>
                </div>
                <h3><?php echo htmlspecialchars($level['level_name']); ?></h3>
                <p><?php echo htmlspecialchars($level['description']); ?></p>
                <a href="register.php" class="btn btn-sm btn-outline">Başla</a>
            </div>
            <?php
            $index++;
            endwhile;
            ?>
        </div>
    </div>
</section>

<section class="categories">
    <div class="container">
        <div class="section-header">
            <h2>Öğrenme Kategorileri</h2>
            <p>Her alanda kendinizi geliştirin</p>
        </div>

        <div class="categories-grid">
            <?php
            $categories_query = "SELECT * FROM categories";
            $categories_result = $conn->query($categories_query);

            while ($category = $categories_result->fetch_assoc()):
            ?>
            <div class="category-card" style="border-color: <?php echo htmlspecialchars($category['color']); ?>;">
                <div class="category-icon" style="color: <?php echo htmlspecialchars($category['color']); ?>;">
                    <i class="fas <?php echo htmlspecialchars($category['icon']); ?>"></i>
                </div>
                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                <p><?php echo htmlspecialchars($category['description']); ?></p>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<section class="cta">
    <div class="container">
        <div class="cta-content">
            <h2>Bugün İngilizce Öğrenmeye Başlayın!</h2>
            <p>Ücretsiz hesap oluşturun ve hedeflerinize ulaşmaya başlayın</p>
            <a href="register.php" class="btn btn-light btn-lg">
                <i class="fas fa-rocket"></i> Hemen Başla
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
