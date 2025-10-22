<?php
require_once '../config/database.php';
require_login();
require_admin();

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0")->fetch_assoc()['total'];
$total_lessons = $conn->query("SELECT COUNT(*) as total FROM lessons")->fetch_assoc()['total'];
$total_vocab = $conn->query("SELECT COUNT(*) as total FROM vocabulary")->fetch_assoc()['total'];
$active_today = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM daily_activity WHERE activity_date = CURDATE()")->fetch_assoc()['total'];

// Get recent users
$recent_users = $conn->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5");

// Get recent completions
$recent_completions = $conn->query("
    SELECT u.username, l.title, up.completed_at, up.score
    FROM user_progress up
    JOIN users u ON u.id = up.user_id
    JOIN lessons l ON l.id = up.lesson_id
    WHERE up.status = 'completed'
    ORDER BY up.completed_at DESC
    LIMIT 10
");

$page_title = 'Admin Paneli';
include '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Admin Paneli</h1>
            <p>Sistem yönetimi ve istatistikler</p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Toplam Kullanıcı</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_lessons; ?></h3>
                    <p>Toplam Ders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-font"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_vocab; ?></h3>
                    <p>Kelime Sayısı</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $active_today; ?></h3>
                    <p>Bugün Aktif</p>
                </div>
            </div>
        </div>

        <div class="admin-actions">
            <a href="manage-lessons.php" class="btn btn-primary">
                <i class="fas fa-book"></i> Dersleri Yönet
            </a>
            <a href="manage-users.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Kullanıcıları Yönet
            </a>
            <a href="manage-vocabulary.php" class="btn btn-primary">
                <i class="fas fa-font"></i> Kelimeleri Yönet
            </a>
            <a href="add-lesson.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Yeni Ders Ekle
            </a>
        </div>

        <div class="admin-grid">
            <div class="admin-main">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Son Tamamlanan Dersler</h2>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>Ders</th>
                                    <th>Puan</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($completion = $recent_completions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($completion['username']); ?></td>
                                    <td><?php echo htmlspecialchars($completion['title']); ?></td>
                                    <td><?php echo $completion['score']; ?>%</td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($completion['completed_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="admin-sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Son Kayıtlar</h3>
                    </div>
                    <div class="card-body">
                        <div class="recent-users-list">
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                            <div class="recent-user-item">
                                <img src="../assets/images/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                                <div class="user-info">
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <p><?php echo time_ago($user['created_at']); ?></p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Sistem Bilgisi</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-item">
                                <span>PHP Sürümü:</span>
                                <strong><?php echo phpversion(); ?></strong>
                            </div>
                            <div class="info-item">
                                <span>MySQL Sürümü:</span>
                                <strong><?php echo $conn->server_info; ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Platform:</span>
                                <strong>English Learning v1.0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.admin-section {
    padding: 2rem 0;
}

.admin-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.admin-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table thead {
    background: var(--light-color);
}

.admin-table th,
.admin-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--light-color);
}

.admin-table tbody tr:hover {
    background: var(--light-color);
}

.recent-users-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.recent-user-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: var(--light-color);
    border-radius: 8px;
}

.recent-user-item img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.recent-user-item strong {
    display: block;
}

.recent-user-item p {
    font-size: 0.85rem;
    color: var(--gray-color);
}

@media (max-width: 768px) {
    .admin-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
