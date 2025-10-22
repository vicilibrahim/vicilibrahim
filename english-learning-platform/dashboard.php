<?php
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_data($user_id);

// Get user statistics
$total_lessons = $conn->query("SELECT COUNT(*) as total FROM user_progress WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['total'];
$in_progress_lessons = $conn->query("SELECT COUNT(*) as total FROM user_progress WHERE user_id = $user_id AND status = 'in_progress'")->fetch_assoc()['total'];

// Get category progress
$category_progress = $conn->query("
    SELECT c.name, c.icon, c.color, COUNT(up.id) as completed
    FROM categories c
    LEFT JOIN lessons l ON l.category_id = c.id
    LEFT JOIN user_progress up ON up.lesson_id = l.id AND up.user_id = $user_id AND up.status = 'completed'
    GROUP BY c.id
");

// Get recent achievements
$recent_achievements = $conn->query("
    SELECT a.*, ua.earned_at
    FROM user_achievements ua
    JOIN achievements a ON a.id = ua.achievement_id
    WHERE ua.user_id = $user_id
    ORDER BY ua.earned_at DESC
    LIMIT 3
");

// Get recommended lessons
$current_level_id = $conn->query("SELECT id FROM levels WHERE level_code = '{$user['current_level']}'")->fetch_assoc()['id'];
$recommended_lessons = $conn->query("
    SELECT l.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
           COALESCE(up.status, 'not_started') as progress_status
    FROM lessons l
    JOIN categories c ON c.id = l.category_id
    LEFT JOIN user_progress up ON up.lesson_id = l.id AND up.user_id = $user_id
    WHERE l.level_id = $current_level_id AND (up.status IS NULL OR up.status != 'completed')
    ORDER BY l.order_index ASC
    LIMIT 6
");

// Get today's activity
$today = date('Y-m-d');
$today_activity = $conn->query("SELECT * FROM daily_activity WHERE user_id = $user_id AND activity_date = '$today'")->fetch_assoc();
$today_lessons = $today_activity ? $today_activity['lessons_completed'] : 0;
$today_points = $today_activity ? $today_activity['points_earned'] : 0;

// Check achievements
check_achievements($user_id);

$page_title = 'Ana Sayfa';
include 'includes/header.php';
?>

<section class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div class="welcome-message">
                <h1>Hoş Geldin, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
                <p>Bugün öğrenmeye hazır mısın?</p>
            </div>
            <div class="streak-badge">
                <div class="streak-icon">🔥</div>
                <div class="streak-info">
                    <strong><?php echo $user['streak_days']; ?> Gün</strong>
                    <span>Ardışık Giriş</span>
                </div>
            </div>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $user['current_level']; ?></h3>
                    <p>Mevcut Seviye</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_lessons; ?></h3>
                    <p>Tamamlanan Ders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($user['total_points']); ?></h3>
                    <p>Toplam Puan</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $in_progress_lessons; ?></h3>
                    <p>Devam Eden Ders</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-main">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-fire"></i> Günlük Hedefin</h2>
                        <a href="settings.php" class="card-link">Ayarla</a>
                    </div>
                    <div class="card-body">
                        <div class="progress-container">
                            <div class="progress-info">
                                <span>Bugün kazanılan puan</span>
                                <strong><?php echo $today_points; ?> / <?php echo $user['daily_goal']; ?></strong>
                            </div>
                            <div class="progress-bar">
                                <?php $progress_percent = ($user['daily_goal'] > 0) ? min(($today_points / $user['daily_goal']) * 100, 100) : 0; ?>
                                <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                            </div>
                            <p class="progress-text">
                                <?php if ($progress_percent >= 100): ?>
                                    🎉 Tebrikler! Günlük hedefini tamamladın!
                                <?php else: ?>
                                    <?php echo ($user['daily_goal'] - $today_points); ?> puan daha kazanman gerekiyor!
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-book-open"></i> Önerilen Dersler</h2>
                        <a href="lessons.php" class="card-link">Tümünü Gör</a>
                    </div>
                    <div class="card-body">
                        <div class="lessons-grid">
                            <?php while ($lesson = $recommended_lessons->fetch_assoc()): ?>
                            <div class="lesson-card">
                                <div class="lesson-category" style="background: <?php echo $lesson['category_color']; ?>;">
                                    <i class="fas <?php echo $lesson['category_icon']; ?>"></i>
                                </div>
                                <div class="lesson-content">
                                    <h4><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                    <p class="lesson-meta">
                                        <span><i class="far fa-clock"></i> <?php echo $lesson['duration_minutes']; ?> dk</span>
                                        <span><i class="fas fa-star"></i> <?php echo $lesson['points']; ?> puan</span>
                                    </p>
                                    <?php if ($lesson['progress_status'] == 'in_progress'): ?>
                                        <span class="lesson-badge badge-warning">Devam Ediyor</span>
                                    <?php else: ?>
                                        <span class="lesson-badge badge-primary">Yeni</span>
                                    <?php endif; ?>
                                </div>
                                <a href="lesson-view.php?id=<?php echo $lesson['id']; ?>" class="btn btn-sm btn-primary">
                                    <?php echo ($lesson['progress_status'] == 'in_progress') ? 'Devam Et' : 'Başla'; ?>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-pie"></i> Kategori İlerlemesi</h2>
                    </div>
                    <div class="card-body">
                        <div class="category-progress-list">
                            <?php while ($cat = $category_progress->fetch_assoc()): ?>
                            <div class="category-progress-item">
                                <div class="category-info">
                                    <i class="fas <?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>;"></i>
                                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                </div>
                                <div class="category-count">
                                    <strong><?php echo $cat['completed']; ?></strong> ders
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Son Başarılar</h3>
                        <a href="achievements.php" class="card-link">Tümü</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_achievements->num_rows > 0): ?>
                            <div class="achievements-list">
                                <?php while ($achievement = $recent_achievements->fetch_assoc()): ?>
                                <div class="achievement-item">
                                    <div class="achievement-icon" style="background: <?php echo $achievement['badge_color']; ?>;">
                                        <?php echo $achievement['icon']; ?>
                                    </div>
                                    <div class="achievement-info">
                                        <strong><?php echo htmlspecialchars($achievement['name']); ?></strong>
                                        <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                                        <span class="achievement-date"><?php echo time_ago($achievement['earned_at']); ?></span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Henüz başarı kazanmadınız. Ders tamamlayarak başarı kazanabilirsiniz!</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-lightbulb"></i> İpucu</h3>
                    </div>
                    <div class="card-body">
                        <div class="tip-box">
                            <p><strong>Günlük pratik yapın!</strong></p>
                            <p>Her gün en az 15 dakika İngilizce pratik yapmak, dilinizin gelişmesini hızlandırır.</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> Hızlı Erişim</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-links">
                            <a href="vocabulary.php" class="quick-link">
                                <i class="fas fa-font"></i>
                                <span>Kelime Defteri</span>
                            </a>
                            <a href="progress.php" class="quick-link">
                                <i class="fas fa-chart-line"></i>
                                <span>Gelişimim</span>
                            </a>
                            <a href="lessons.php" class="quick-link">
                                <i class="fas fa-book-open"></i>
                                <span>Tüm Dersler</span>
                            </a>
                            <a href="achievements.php" class="quick-link">
                                <i class="fas fa-trophy"></i>
                                <span>Başarılar</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
