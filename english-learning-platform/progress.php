<?php
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_data($user_id);

// Get overall statistics
$total_lessons = $conn->query("SELECT COUNT(*) as total FROM user_progress WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['total'];
$total_time = $conn->query("SELECT SUM(time_spent) as total FROM user_progress WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;

// Get progress by level
$level_progress = $conn->query("
    SELECT l.level_code, l.level_name,
           COUNT(DISTINCT les.id) as total_lessons,
           COUNT(DISTINCT CASE WHEN up.status = 'completed' THEN les.id END) as completed_lessons
    FROM levels l
    LEFT JOIN lessons les ON les.level_id = l.id
    LEFT JOIN user_progress up ON up.lesson_id = les.id AND up.user_id = $user_id
    GROUP BY l.id
    ORDER BY l.order_index
");

// Get progress by category
$category_progress = $conn->query("
    SELECT c.name, c.icon, c.color,
           COUNT(DISTINCT l.id) as total_lessons,
           COUNT(DISTINCT CASE WHEN up.status = 'completed' THEN l.id END) as completed_lessons,
           COALESCE(AVG(up.score), 0) as avg_score
    FROM categories c
    LEFT JOIN lessons l ON l.category_id = c.id
    LEFT JOIN user_progress up ON up.lesson_id = l.id AND up.user_id = $user_id
    GROUP BY c.id
");

// Get last 7 days activity
$last_7_days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $activity = $conn->query("
        SELECT lessons_completed, points_earned
        FROM daily_activity
        WHERE user_id = $user_id AND activity_date = '$date'
    ")->fetch_assoc();

    $last_7_days[] = [
        'date' => $date,
        'lessons' => $activity ? $activity['lessons_completed'] : 0,
        'points' => $activity ? $activity['points_earned'] : 0
    ];
}

// Get all achievements
$all_achievements = $conn->query("SELECT * FROM achievements ORDER BY requirement_value ASC");
$user_achievement_ids = [];
$user_achievements_result = $conn->query("SELECT achievement_id FROM user_achievements WHERE user_id = $user_id");
while ($row = $user_achievements_result->fetch_assoc()) {
    $user_achievement_ids[] = $row['achievement_id'];
}

// Get recent completed lessons
$recent_lessons = $conn->query("
    SELECT l.title, c.name as category, c.icon, c.color, up.completed_at, up.score
    FROM user_progress up
    JOIN lessons l ON l.id = up.lesson_id
    JOIN categories c ON c.id = l.category_id
    WHERE up.user_id = $user_id AND up.status = 'completed'
    ORDER BY up.completed_at DESC
    LIMIT 10
");

$page_title = 'Gelişimim';
include 'includes/header.php';
?>

<section class="progress-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Gelişim Takibi</h1>
            <p>İlerlemenizi ve istatistiklerinizi görüntüleyin</p>
        </div>

        <div class="overview-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_lessons; ?></h3>
                    <p>Toplam Ders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($user['total_points']); ?></h3>
                    <p>Toplam Puan</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo round($total_time / 60); ?></h3>
                    <p>Saat Çalışma</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $user['streak_days']; ?></h3>
                    <p>Gün Streak</p>
                </div>
            </div>
        </div>

        <div class="progress-grid">
            <div class="progress-main">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-calendar-week"></i> Son 7 Gün Aktivite</h2>
                    </div>
                    <div class="card-body">
                        <div class="activity-chart">
                            <?php foreach ($last_7_days as $day): ?>
                            <div class="activity-bar">
                                <div class="bar-container">
                                    <div class="bar-fill" style="height: <?php echo $day['lessons'] > 0 ? min(($day['lessons'] / 10) * 100, 100) : 5; ?>%"
                                         title="<?php echo $day['lessons']; ?> ders">
                                    </div>
                                </div>
                                <span class="bar-label"><?php echo date('d.m', strtotime($day['date'])); ?></span>
                                <span class="bar-value"><?php echo $day['points']; ?>p</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-layer-group"></i> Seviye İlerlemesi</h2>
                    </div>
                    <div class="card-body">
                        <div class="level-progress-list">
                            <?php
                            $colors = ['#28a745', '#17a2b8', '#007bff', '#6f42c1', '#fd7e14', '#dc3545'];
                            $index = 0;
                            while ($level = $level_progress->fetch_assoc()):
                                $percentage = $level['total_lessons'] > 0 ? ($level['completed_lessons'] / $level['total_lessons']) * 100 : 0;
                            ?>
                            <div class="level-progress-item">
                                <div class="level-info">
                                    <span class="level-badge" style="background: <?php echo $colors[$index]; ?>;">
                                        <?php echo $level['level_code']; ?>
                                    </span>
                                    <span class="level-name"><?php echo htmlspecialchars($level['level_name']); ?></span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $colors[$index]; ?>;"></div>
                                    </div>
                                    <span class="progress-text"><?php echo $level['completed_lessons']; ?>/<?php echo $level['total_lessons']; ?></span>
                                </div>
                            </div>
                            <?php
                            $index++;
                            endwhile;
                            ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-tasks"></i> Kategori İlerlemesi</h2>
                    </div>
                    <div class="card-body">
                        <div class="category-progress-grid">
                            <?php while ($cat = $category_progress->fetch_assoc()):
                                $percentage = $cat['total_lessons'] > 0 ? ($cat['completed_lessons'] / $cat['total_lessons']) * 100 : 0;
                            ?>
                            <div class="category-progress-card" style="border-color: <?php echo $cat['color']; ?>;">
                                <div class="category-icon" style="color: <?php echo $cat['color']; ?>;">
                                    <i class="fas <?php echo $cat['icon']; ?>"></i>
                                </div>
                                <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                                <div class="circular-progress" data-percentage="<?php echo round($percentage); ?>">
                                    <svg viewBox="0 0 36 36" class="circular-chart">
                                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                        <path class="circle" stroke="<?php echo $cat['color']; ?>"
                                              stroke-dasharray="<?php echo $percentage; ?>, 100"
                                              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                        <text x="18" y="20.35" class="percentage"><?php echo round($percentage); ?>%</text>
                                    </svg>
                                </div>
                                <p><?php echo $cat['completed_lessons']; ?>/<?php echo $cat['total_lessons']; ?> ders</p>
                                <p class="avg-score">Ort. Puan: <?php echo round($cat['avg_score']); ?>%</p>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Son Tamamlanan Dersler</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_lessons->num_rows > 0): ?>
                        <div class="recent-lessons-list">
                            <?php while ($lesson = $recent_lessons->fetch_assoc()): ?>
                            <div class="recent-lesson-item">
                                <div class="lesson-icon" style="background: <?php echo $lesson['color']; ?>;">
                                    <i class="fas <?php echo $lesson['icon']; ?>"></i>
                                </div>
                                <div class="lesson-info">
                                    <h4><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($lesson['category']); ?> • <?php echo time_ago($lesson['completed_at']); ?></p>
                                </div>
                                <div class="lesson-score">
                                    <span class="score-badge"><?php echo $lesson['score']; ?>%</span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Henüz ders tamamlamadınız.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="progress-sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Başarılar</h3>
                    </div>
                    <div class="card-body">
                        <div class="achievements-grid">
                            <?php while ($achievement = $all_achievements->fetch_assoc()):
                                $earned = in_array($achievement['id'], $user_achievement_ids);
                            ?>
                            <div class="achievement-badge <?php echo $earned ? 'earned' : 'locked'; ?>"
                                 title="<?php echo htmlspecialchars($achievement['description']); ?>">
                                <div class="badge-icon" style="background: <?php echo $earned ? $achievement['badge_color'] : '#cccccc'; ?>;">
                                    <?php echo $achievement['icon']; ?>
                                </div>
                                <p><?php echo htmlspecialchars($achievement['name']); ?></p>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-target"></i> Hedefler</h3>
                    </div>
                    <div class="card-body">
                        <div class="goals-list">
                            <div class="goal-item">
                                <div class="goal-icon">🎯</div>
                                <div class="goal-info">
                                    <strong>Günlük Hedef</strong>
                                    <p><?php echo $user['daily_goal']; ?> puan</p>
                                </div>
                            </div>
                            <div class="goal-item">
                                <div class="goal-icon">🔥</div>
                                <div class="goal-info">
                                    <strong>Streak Hedefi</strong>
                                    <p>30 gün (<?php echo $user['streak_days']; ?>/30)</p>
                                </div>
                            </div>
                            <div class="goal-item">
                                <div class="goal-icon">⭐</div>
                                <div class="goal-info">
                                    <strong>Puan Hedefi</strong>
                                    <p>1000 puan (<?php echo $user['total_points']; ?>/1000)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
