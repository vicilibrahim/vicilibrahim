<?php
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Get all achievements
$all_achievements = $conn->query("
    SELECT a.*,
           (SELECT COUNT(*) FROM user_achievements WHERE achievement_id = a.id AND user_id = $user_id) as earned
    FROM achievements a
    ORDER BY a.requirement_value ASC
");

$page_title = 'Başarılar';
include 'includes/header.php';
?>

<section class="achievements-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-trophy"></i> Başarılar</h1>
            <p>Hedeflerinize ulaşarak özel rozetler kazanın</p>
        </div>

        <div class="achievements-container">
            <?php while ($achievement = $all_achievements->fetch_assoc()): ?>
            <div class="achievement-card <?php echo $achievement['earned'] ? 'earned' : 'locked'; ?>">
                <div class="achievement-badge-large" style="background: <?php echo $achievement['earned'] ? $achievement['badge_color'] : '#cccccc'; ?>;">
                    <?php echo $achievement['icon']; ?>
                </div>
                <h3><?php echo htmlspecialchars($achievement['name']); ?></h3>
                <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                <div class="achievement-requirement">
                    <?php
                    $requirement_texts = [
                        'lessons_completed' => $achievement['requirement_value'] . ' ders tamamla',
                        'quiz_score' => 'Quiz ortalaması %' . $achievement['requirement_value'],
                        'streak_days' => $achievement['requirement_value'] . ' gün ardışık giriş',
                        'total_points' => $achievement['requirement_value'] . ' puan kazan',
                        'level_completed' => 'Bir seviyeyi tamamla'
                    ];
                    echo $requirement_texts[$achievement['requirement_type']];
                    ?>
                </div>
                <?php if ($achievement['earned']): ?>
                <div class="earned-badge">
                    <i class="fas fa-check-circle"></i> Kazanıldı
                </div>
                <?php else: ?>
                <div class="locked-badge">
                    <i class="fas fa-lock"></i> Kilitli
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<style>
.achievements-section {
    padding: 2rem 0;
}

.achievements-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
}

.achievement-card {
    background: #fff;
    padding: 2rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    text-align: center;
    transition: var(--transition);
    position: relative;
}

.achievement-card.earned {
    border: 2px solid var(--success-color);
}

.achievement-card.locked {
    opacity: 0.6;
}

.achievement-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.achievement-badge-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 3rem;
}

.achievement-card h3 {
    margin-bottom: 0.5rem;
    font-size: 1.3rem;
}

.achievement-card p {
    color: var(--gray-color);
    margin-bottom: 1rem;
}

.achievement-requirement {
    background: var(--light-color);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.earned-badge {
    color: var(--success-color);
    font-weight: bold;
}

.locked-badge {
    color: var(--gray-color);
}
</style>

<?php include 'includes/footer.php'; ?>
