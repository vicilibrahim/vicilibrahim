<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';

// Check if user has connected Instagram account
if (!$current_account) {
    header('Location: connect.php');
    exit;
}

// Get weekly statistics
$weekly_stats = ig_get_weekly_stats($current_account['id']);

// Get scheduled posts for this week
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$end_of_week = date('Y-m-d', strtotime('sunday this week'));
$scheduled_posts = ig_get_scheduled_posts($current_account['id'], $start_of_week, $end_of_week);

// Get best posting times
$best_times = ig_get_best_times($current_account['id']);

// Get content categories
$content_categories = ig_get_content_categories();

// Get recent posts
$recent_posts = ig_get_posts($current_account['id'], null, 5);

// Day names in Turkish
$day_names = ['Pzt', 'Sal', 'Car', 'Per', 'Cum', 'Cmt', 'Paz'];
$day_full_names = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar'];
?>

<!-- Performance Stats -->
<section class="ig-section">
    <div class="ig-section-header">
        <h2><i class="fas fa-chart-bar"></i> Bu Hafta Performans</h2>
        <span class="ig-section-badge"><?php echo date('d M', strtotime($start_of_week)); ?> - <?php echo date('d M', strtotime($end_of_week)); ?></span>
    </div>

    <div class="ig-stats-grid">
        <div class="ig-stat-card">
            <div class="ig-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-pen-to-square"></i>
            </div>
            <div class="ig-stat-content">
                <span class="ig-stat-value"><?php echo $weekly_stats['posts_count']; ?></span>
                <span class="ig-stat-label">Paylasim</span>
            </div>
        </div>

        <div class="ig-stat-card">
            <div class="ig-stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-eye"></i>
            </div>
            <div class="ig-stat-content">
                <span class="ig-stat-value"><?php echo ig_format_number($weekly_stats['total_reach']); ?></span>
                <span class="ig-stat-label">Erisim</span>
            </div>
        </div>

        <div class="ig-stat-card">
            <div class="ig-stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-heart"></i>
            </div>
            <div class="ig-stat-content">
                <span class="ig-stat-value"><?php echo $weekly_stats['avg_engagement_rate']; ?>%</span>
                <span class="ig-stat-label">Etkilesim</span>
            </div>
        </div>

        <div class="ig-stat-card">
            <div class="ig-stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="ig-stat-content">
                <span class="ig-stat-value"><?php echo ($weekly_stats['follower_change'] >= 0 ? '+' : '') . $weekly_stats['follower_change']; ?></span>
                <span class="ig-stat-label">Takipci</span>
            </div>
        </div>
    </div>
</section>

<!-- Weekly Calendar -->
<section class="ig-section">
    <div class="ig-section-header">
        <h2><i class="fas fa-calendar-alt"></i> Bu Hafta Takvimi</h2>
        <a href="calendar.php" class="ig-link">Tum takvim <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="ig-week-calendar">
        <?php
        $week_start = strtotime('monday this week');
        for ($i = 0; $i < 7; $i++):
            $day_date = date('Y-m-d', strtotime("+$i days", $week_start));
            $day_posts = array_filter($scheduled_posts, function($p) use ($day_date) {
                return date('Y-m-d', strtotime($p['scheduled_at'])) === $day_date;
            });
            $is_today = $day_date === date('Y-m-d');
            $is_past = $day_date < date('Y-m-d');
        ?>
        <div class="ig-week-day <?php echo $is_today ? 'today' : ''; ?> <?php echo $is_past ? 'past' : ''; ?>">
            <div class="ig-week-day-header">
                <span class="ig-week-day-name"><?php echo $day_names[$i]; ?></span>
                <span class="ig-week-day-date"><?php echo date('d', strtotime($day_date)); ?></span>
            </div>
            <div class="ig-week-day-content">
                <?php if (empty($day_posts)): ?>
                    <div class="ig-week-day-empty">
                        <span>-</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($day_posts as $post): ?>
                    <div class="ig-week-day-post" data-type="<?php echo $post['post_type']; ?>">
                        <span class="ig-post-type-icon">
                            <?php
                            switch($post['post_type']) {
                                case 'feed': echo '<i class="fas fa-image"></i>'; break;
                                case 'carousel': echo '<i class="fas fa-images"></i>'; break;
                                case 'story': echo '<i class="fas fa-circle-dot"></i>'; break;
                                case 'reel': echo '<i class="fas fa-film"></i>'; break;
                            }
                            ?>
                        </span>
                        <span class="ig-post-time"><?php echo date('H:i', strtotime($post['scheduled_at'])); ?></span>
                        <span class="ig-post-type-label"><?php echo ucfirst($post['post_type']); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</section>

<!-- Best Posting Times & Content Strategy -->
<div class="ig-two-columns">
    <!-- Best Posting Times -->
    <section class="ig-section">
        <div class="ig-section-header">
            <h2><i class="fas fa-clock"></i> En Iyi Paylasim Zamanlari</h2>
            <span class="ig-section-badge ig-badge-ai"><i class="fas fa-robot"></i> AI Onerisi</span>
        </div>

        <div class="ig-best-times">
            <div class="ig-best-times-section">
                <h4><i class="fas fa-briefcase"></i> Hafta Ici</h4>
                <div class="ig-time-slots">
                    <span class="ig-time-slot">10:00 - 11:00</span>
                    <span class="ig-time-slot">18:00 - 20:00</span>
                </div>
            </div>
            <div class="ig-best-times-section">
                <h4><i class="fas fa-sun"></i> Hafta Sonu</h4>
                <div class="ig-time-slots">
                    <span class="ig-time-slot">14:00 - 16:00</span>
                </div>
            </div>
        </div>

        <?php if (!empty($best_times)): ?>
        <div class="ig-personal-times">
            <h4>Sizin Icin En Iyiler</h4>
            <ul>
                <?php foreach (array_slice($best_times, 0, 3) as $time): ?>
                <li>
                    <span class="ig-time-day"><?php echo ig_get_day_name($time['day_of_week']); ?></span>
                    <span class="ig-time-hour"><?php echo sprintf('%02d:00', $time['hour']); ?></span>
                    <span class="ig-time-engagement"><?php echo $time['avg_engagement']; ?>%</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </section>

    <!-- Content Strategy -->
    <section class="ig-section">
        <div class="ig-section-header">
            <h2><i class="fas fa-lightbulb"></i> AI Icerik Stratejisi</h2>
        </div>

        <div class="ig-content-strategy">
            <?php foreach ($content_categories as $category): ?>
            <div class="ig-strategy-item">
                <div class="ig-strategy-bar" style="width: <?php echo $category['percentage']; ?>%; background-color: <?php echo $category['color']; ?>"></div>
                <div class="ig-strategy-info">
                    <span class="ig-strategy-icon" style="color: <?php echo $category['color']; ?>">
                        <i class="fas <?php echo $category['icon']; ?>"></i>
                    </span>
                    <span class="ig-strategy-name"><?php echo htmlspecialchars($category['name_tr']); ?></span>
                    <span class="ig-strategy-percent"><?php echo $category['percentage']; ?>%</span>
                </div>
                <p class="ig-strategy-desc"><?php echo htmlspecialchars($category['description']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<!-- Quick Actions -->
<section class="ig-section">
    <div class="ig-section-header">
        <h2><i class="fas fa-bolt"></i> Hizli Islemler</h2>
    </div>

    <div class="ig-quick-actions">
        <a href="create-post.php?type=feed" class="ig-quick-action">
            <div class="ig-quick-action-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-image"></i>
            </div>
            <span>Feed Post</span>
        </a>

        <a href="create-post.php?type=carousel" class="ig-quick-action">
            <div class="ig-quick-action-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-images"></i>
            </div>
            <span>Carousel</span>
        </a>

        <a href="create-post.php?type=story" class="ig-quick-action">
            <div class="ig-quick-action-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-circle-dot"></i>
            </div>
            <span>Story</span>
        </a>

        <a href="create-post.php?type=reel" class="ig-quick-action">
            <div class="ig-quick-action-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <i class="fas fa-film"></i>
            </div>
            <span>Reel</span>
        </a>

        <a href="ai-assistant.php" class="ig-quick-action">
            <div class="ig-quick-action-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <i class="fas fa-robot"></i>
            </div>
            <span>AI Asistan</span>
        </a>

        <a href="calendar.php?action=generate" class="ig-quick-action">
            <div class="ig-quick-action-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                <i class="fas fa-wand-magic-sparkles"></i>
            </div>
            <span>Takvim Olustur</span>
        </a>
    </div>
</section>

<!-- Recent Posts -->
<section class="ig-section">
    <div class="ig-section-header">
        <h2><i class="fas fa-clock-rotate-left"></i> Son Icerikler</h2>
        <a href="posts.php" class="ig-link">Tumunu Gor <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="ig-recent-posts">
        <?php if (empty($recent_posts)): ?>
        <div class="ig-empty-state">
            <i class="fas fa-images"></i>
            <p>Henuz icerik eklenmemis</p>
            <a href="create-post.php" class="ig-btn ig-btn-primary">Ilk Icerigi Olustur</a>
        </div>
        <?php else: ?>
        <div class="ig-posts-grid">
            <?php foreach ($recent_posts as $post):
                $media = ig_get_post_media($post['id']);
                $thumbnail = !empty($media) ? $media[0]['media_url'] : 'assets/images/placeholder.jpg';
            ?>
            <div class="ig-post-card" data-status="<?php echo $post['status']; ?>">
                <div class="ig-post-thumbnail">
                    <img src="<?php echo htmlspecialchars($thumbnail); ?>" alt="Post">
                    <span class="ig-post-type-badge">
                        <?php
                        switch($post['post_type']) {
                            case 'feed': echo '<i class="fas fa-image"></i>'; break;
                            case 'carousel': echo '<i class="fas fa-images"></i>'; break;
                            case 'story': echo '<i class="fas fa-circle-dot"></i>'; break;
                            case 'reel': echo '<i class="fas fa-film"></i>'; break;
                        }
                        ?>
                    </span>
                </div>
                <div class="ig-post-info">
                    <p class="ig-post-caption"><?php echo htmlspecialchars(mb_substr($post['caption'] ?? '', 0, 60)); ?>...</p>
                    <div class="ig-post-meta">
                        <span class="ig-post-status status-<?php echo $post['status']; ?>">
                            <?php
                            switch($post['status']) {
                                case 'draft': echo '<i class="fas fa-edit"></i> Taslak'; break;
                                case 'scheduled': echo '<i class="fas fa-clock"></i> Planli'; break;
                                case 'published': echo '<i class="fas fa-check"></i> Yayinda'; break;
                                case 'failed': echo '<i class="fas fa-exclamation-triangle"></i> Hata'; break;
                            }
                            ?>
                        </span>
                        <span class="ig-post-date">
                            <?php echo time_ago($post['scheduled_at'] ?? $post['created_at']); ?>
                        </span>
                    </div>
                </div>
                <div class="ig-post-actions">
                    <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="ig-icon-btn" title="Duzenle">
                        <i class="fas fa-edit"></i>
                    </a>
                    <?php if ($post['status'] === 'published'): ?>
                    <span class="ig-post-stats">
                        <i class="fas fa-heart"></i> <?php echo ig_format_number($post['likes']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
