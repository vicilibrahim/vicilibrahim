<?php
$page_title = 'Analitik';
require_once 'includes/header.php';

if (!$current_account) {
    header('Location: connect.php');
    exit;
}

// Date range filter
$date_range = isset($_GET['range']) ? $_GET['range'] : '7';
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-$date_range days"));

// Get analytics data
global $conn;

// Weekly comparison
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$last_week_start = date('Y-m-d', strtotime('monday last week'));
$last_week_end = date('Y-m-d', strtotime('sunday last week'));

// This week stats
$this_week_query = "SELECT
    COUNT(*) as posts_count,
    SUM(reach) as total_reach,
    SUM(impressions) as total_impressions,
    SUM(likes) as total_likes,
    SUM(comments) as total_comments,
    SUM(saves) as total_saves,
    SUM(shares) as total_shares,
    AVG(engagement_rate) as avg_engagement
FROM ig_posts
WHERE account_id = {$current_account['id']}
AND status = 'published'
AND published_at >= '$this_week_start'";
$this_week_result = $conn->query($this_week_query)->fetch_assoc();

// Last week stats
$last_week_query = "SELECT
    COUNT(*) as posts_count,
    SUM(reach) as total_reach,
    SUM(impressions) as total_impressions,
    SUM(likes) as total_likes,
    SUM(comments) as total_comments,
    SUM(saves) as total_saves,
    SUM(shares) as total_shares,
    AVG(engagement_rate) as avg_engagement
FROM ig_posts
WHERE account_id = {$current_account['id']}
AND status = 'published'
AND published_at BETWEEN '$last_week_start' AND '$last_week_end 23:59:59'";
$last_week_result = $conn->query($last_week_query)->fetch_assoc();

// Calculate growth percentages
function calcGrowth($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

$reach_growth = calcGrowth($this_week_result['total_reach'] ?? 0, $last_week_result['total_reach'] ?? 0);
$engagement_growth = calcGrowth($this_week_result['avg_engagement'] ?? 0, $last_week_result['avg_engagement'] ?? 0);
$likes_growth = calcGrowth($this_week_result['total_likes'] ?? 0, $last_week_result['total_likes'] ?? 0);

// Top performing posts
$top_posts_query = "SELECT p.*, c.name as category_name, c.color as category_color,
                   (SELECT media_url FROM ig_post_media WHERE post_id = p.id ORDER BY order_index LIMIT 1) as thumbnail
FROM ig_posts p
LEFT JOIN ig_content_categories c ON p.content_category_id = c.id
WHERE p.account_id = {$current_account['id']}
AND p.status = 'published'
AND p.published_at >= '$start_date'
ORDER BY (p.likes + p.comments + p.saves + p.shares) DESC
LIMIT 5";
$top_posts = [];
$top_posts_result = $conn->query($top_posts_query);
while ($row = $top_posts_result->fetch_assoc()) {
    $top_posts[] = $row;
}

// Posts by type
$posts_by_type_query = "SELECT post_type, COUNT(*) as count,
                        SUM(likes) as total_likes,
                        AVG(engagement_rate) as avg_engagement
FROM ig_posts
WHERE account_id = {$current_account['id']}
AND status = 'published'
AND published_at >= '$start_date'
GROUP BY post_type";
$posts_by_type = [];
$posts_by_type_result = $conn->query($posts_by_type_query);
while ($row = $posts_by_type_result->fetch_assoc()) {
    $posts_by_type[$row['post_type']] = $row;
}

// Posts by category
$posts_by_category_query = "SELECT c.name, c.name_tr, c.color, c.icon,
                            COUNT(p.id) as count,
                            AVG(p.engagement_rate) as avg_engagement
FROM ig_posts p
LEFT JOIN ig_content_categories c ON p.content_category_id = c.id
WHERE p.account_id = {$current_account['id']}
AND p.status = 'published'
AND p.published_at >= '$start_date'
GROUP BY p.content_category_id";
$posts_by_category = [];
$posts_by_category_result = $conn->query($posts_by_category_query);
while ($row = $posts_by_category_result->fetch_assoc()) {
    $posts_by_category[] = $row;
}

// Daily analytics
$daily_analytics_query = "SELECT date, followers_count, new_followers, lost_followers,
                          total_reach, total_impressions, profile_views
FROM ig_analytics_daily
WHERE account_id = {$current_account['id']}
AND date >= '$start_date'
ORDER BY date ASC";
$daily_analytics = [];
$daily_result = $conn->query($daily_analytics_query);
while ($row = $daily_result->fetch_assoc()) {
    $daily_analytics[] = $row;
}

// Best performing hashtags
$hashtag_performance_query = "SELECT h.hashtag, h.category, h.media_count,
                              COUNT(ph.id) as usage_count,
                              AVG(p.engagement_rate) as avg_engagement
FROM ig_post_hashtags ph
JOIN ig_hashtags h ON ph.hashtag_id = h.id
JOIN ig_posts p ON ph.post_id = p.id
WHERE p.account_id = {$current_account['id']}
AND p.status = 'published'
GROUP BY h.id
ORDER BY avg_engagement DESC
LIMIT 10";
$hashtag_performance = [];
$hashtag_result = $conn->query($hashtag_performance_query);
while ($row = $hashtag_result->fetch_assoc()) {
    $hashtag_performance[] = $row;
}

// Best posting times
$best_times = ig_get_best_times($current_account['id']);
?>

<div class="ig-analytics-page">
    <!-- Date Range Filter -->
    <div class="ig-analytics-header">
        <h2><i class="fas fa-chart-line"></i> Performans Analizi</h2>
        <div class="ig-date-filter">
            <a href="?range=7" class="ig-filter-btn <?php echo $date_range == '7' ? 'active' : ''; ?>">Son 7 Gun</a>
            <a href="?range=14" class="ig-filter-btn <?php echo $date_range == '14' ? 'active' : ''; ?>">Son 14 Gun</a>
            <a href="?range=30" class="ig-filter-btn <?php echo $date_range == '30' ? 'active' : ''; ?>">Son 30 Gun</a>
            <a href="?range=90" class="ig-filter-btn <?php echo $date_range == '90' ? 'active' : ''; ?>">Son 90 Gun</a>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="ig-stats-overview">
        <div class="ig-stat-card ig-stat-large">
            <div class="ig-stat-header">
                <i class="fas fa-eye"></i>
                <span class="ig-stat-growth <?php echo $reach_growth >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $reach_growth >= 0 ? '+' : ''; ?><?php echo $reach_growth; ?>%
                </span>
            </div>
            <div class="ig-stat-value"><?php echo ig_format_number($this_week_result['total_reach'] ?? 0); ?></div>
            <div class="ig-stat-label">Toplam Erisim</div>
            <div class="ig-stat-comparison">Gecen hafta: <?php echo ig_format_number($last_week_result['total_reach'] ?? 0); ?></div>
        </div>

        <div class="ig-stat-card ig-stat-large">
            <div class="ig-stat-header">
                <i class="fas fa-heart"></i>
                <span class="ig-stat-growth <?php echo $engagement_growth >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $engagement_growth >= 0 ? '+' : ''; ?><?php echo $engagement_growth; ?>%
                </span>
            </div>
            <div class="ig-stat-value"><?php echo round($this_week_result['avg_engagement'] ?? 0, 2); ?>%</div>
            <div class="ig-stat-label">Ort. Etkilesim Orani</div>
            <div class="ig-stat-comparison">Gecen hafta: <?php echo round($last_week_result['avg_engagement'] ?? 0, 2); ?>%</div>
        </div>

        <div class="ig-stat-card ig-stat-large">
            <div class="ig-stat-header">
                <i class="fas fa-thumbs-up"></i>
                <span class="ig-stat-growth <?php echo $likes_growth >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $likes_growth >= 0 ? '+' : ''; ?><?php echo $likes_growth; ?>%
                </span>
            </div>
            <div class="ig-stat-value"><?php echo ig_format_number($this_week_result['total_likes'] ?? 0); ?></div>
            <div class="ig-stat-label">Toplam Begeni</div>
            <div class="ig-stat-comparison">Gecen hafta: <?php echo ig_format_number($last_week_result['total_likes'] ?? 0); ?></div>
        </div>

        <div class="ig-stat-card ig-stat-large">
            <div class="ig-stat-header">
                <i class="fas fa-pen-to-square"></i>
            </div>
            <div class="ig-stat-value"><?php echo $this_week_result['posts_count'] ?? 0; ?></div>
            <div class="ig-stat-label">Paylasim Sayisi</div>
            <div class="ig-stat-comparison">Gecen hafta: <?php echo $last_week_result['posts_count'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Engagement Breakdown -->
    <div class="ig-analytics-row">
        <section class="ig-section ig-section-half">
            <div class="ig-section-header">
                <h3><i class="fas fa-hand-pointer"></i> Etkilesim Dagilimi</h3>
            </div>
            <div class="ig-engagement-breakdown">
                <div class="ig-engagement-item">
                    <div class="ig-engagement-icon"><i class="fas fa-heart"></i></div>
                    <div class="ig-engagement-info">
                        <span class="ig-engagement-value"><?php echo ig_format_number($this_week_result['total_likes'] ?? 0); ?></span>
                        <span class="ig-engagement-label">Begeni</span>
                    </div>
                </div>
                <div class="ig-engagement-item">
                    <div class="ig-engagement-icon"><i class="fas fa-comment"></i></div>
                    <div class="ig-engagement-info">
                        <span class="ig-engagement-value"><?php echo ig_format_number($this_week_result['total_comments'] ?? 0); ?></span>
                        <span class="ig-engagement-label">Yorum</span>
                    </div>
                </div>
                <div class="ig-engagement-item">
                    <div class="ig-engagement-icon"><i class="fas fa-bookmark"></i></div>
                    <div class="ig-engagement-info">
                        <span class="ig-engagement-value"><?php echo ig_format_number($this_week_result['total_saves'] ?? 0); ?></span>
                        <span class="ig-engagement-label">Kaydetme</span>
                    </div>
                </div>
                <div class="ig-engagement-item">
                    <div class="ig-engagement-icon"><i class="fas fa-share"></i></div>
                    <div class="ig-engagement-info">
                        <span class="ig-engagement-value"><?php echo ig_format_number($this_week_result['total_shares'] ?? 0); ?></span>
                        <span class="ig-engagement-label">Paylasim</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="ig-section ig-section-half">
            <div class="ig-section-header">
                <h3><i class="fas fa-layer-group"></i> Icerik Tipi Performansi</h3>
            </div>
            <div class="ig-content-type-stats">
                <?php
                $type_icons = ['feed' => 'fa-image', 'carousel' => 'fa-images', 'story' => 'fa-circle-dot', 'reel' => 'fa-film'];
                $type_colors = ['feed' => '#667eea', 'carousel' => '#f093fb', 'story' => '#4facfe', 'reel' => '#43e97b'];
                foreach (['feed', 'carousel', 'story', 'reel'] as $type):
                    $data = isset($posts_by_type[$type]) ? $posts_by_type[$type] : ['count' => 0, 'avg_engagement' => 0];
                ?>
                <div class="ig-type-stat">
                    <div class="ig-type-icon" style="background-color: <?php echo $type_colors[$type]; ?>20; color: <?php echo $type_colors[$type]; ?>">
                        <i class="fas <?php echo $type_icons[$type]; ?>"></i>
                    </div>
                    <div class="ig-type-info">
                        <span class="ig-type-name"><?php echo ucfirst($type); ?></span>
                        <span class="ig-type-count"><?php echo $data['count']; ?> post</span>
                    </div>
                    <div class="ig-type-engagement">
                        <span><?php echo round($data['avg_engagement'], 2); ?>%</span>
                        <small>engagement</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Category Performance & Best Times -->
    <div class="ig-analytics-row">
        <section class="ig-section ig-section-half">
            <div class="ig-section-header">
                <h3><i class="fas fa-tags"></i> Kategori Performansi</h3>
            </div>
            <div class="ig-category-stats">
                <?php foreach ($posts_by_category as $cat): ?>
                <div class="ig-category-stat-item">
                    <div class="ig-category-bar-container">
                        <div class="ig-category-bar" style="width: <?php echo min(100, $cat['avg_engagement'] * 10); ?>%; background-color: <?php echo $cat['color']; ?>"></div>
                    </div>
                    <div class="ig-category-stat-info">
                        <span class="ig-category-name">
                            <i class="fas <?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>"></i>
                            <?php echo htmlspecialchars($cat['name_tr'] ?? $cat['name'] ?? 'Diger'); ?>
                        </span>
                        <span class="ig-category-count"><?php echo $cat['count']; ?> post</span>
                        <span class="ig-category-engagement"><?php echo round($cat['avg_engagement'], 2); ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="ig-section ig-section-half">
            <div class="ig-section-header">
                <h3><i class="fas fa-clock"></i> En Iyi Paylasim Zamanlari</h3>
            </div>
            <?php if (!empty($best_times)): ?>
            <div class="ig-best-times-chart">
                <table class="ig-times-table">
                    <thead>
                        <tr>
                            <th>Gun</th>
                            <th>Saat</th>
                            <th>Engagement</th>
                            <th>Post Sayisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($best_times as $time): ?>
                        <tr>
                            <td><?php echo ig_get_day_name($time['day_of_week']); ?></td>
                            <td><?php echo sprintf('%02d:00', $time['hour']); ?></td>
                            <td>
                                <div class="ig-engagement-bar">
                                    <div class="ig-engagement-fill" style="width: <?php echo min(100, $time['avg_engagement'] * 10); ?>%"></div>
                                    <span><?php echo round($time['avg_engagement'], 2); ?>%</span>
                                </div>
                            </td>
                            <td><?php echo $time['post_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="ig-empty-state">
                <i class="fas fa-chart-area"></i>
                <p>Henuz yeterli veri yok</p>
                <span>Daha fazla paylasim yaptikca en iyi zamanlarinizi ogreneceginiz</span>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Top Performing Posts -->
    <section class="ig-section">
        <div class="ig-section-header">
            <h3><i class="fas fa-trophy"></i> En Basarili Icerikler</h3>
        </div>
        <?php if (!empty($top_posts)): ?>
        <div class="ig-top-posts-list">
            <?php foreach ($top_posts as $index => $post):
                $total_engagement = ($post['likes'] ?? 0) + ($post['comments'] ?? 0) + ($post['saves'] ?? 0) + ($post['shares'] ?? 0);
            ?>
            <div class="ig-top-post-item">
                <span class="ig-post-rank">#<?php echo $index + 1; ?></span>
                <div class="ig-post-thumbnail">
                    <img src="<?php echo htmlspecialchars($post['thumbnail'] ?? 'assets/images/placeholder.jpg'); ?>" alt="Post">
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
                <div class="ig-post-details">
                    <p class="ig-post-caption"><?php echo htmlspecialchars(mb_substr($post['caption'] ?? '', 0, 80)); ?>...</p>
                    <span class="ig-post-date"><?php echo date('d M Y', strtotime($post['published_at'])); ?></span>
                </div>
                <div class="ig-post-metrics">
                    <div class="ig-metric">
                        <i class="fas fa-heart"></i>
                        <span><?php echo ig_format_number($post['likes'] ?? 0); ?></span>
                    </div>
                    <div class="ig-metric">
                        <i class="fas fa-comment"></i>
                        <span><?php echo ig_format_number($post['comments'] ?? 0); ?></span>
                    </div>
                    <div class="ig-metric">
                        <i class="fas fa-bookmark"></i>
                        <span><?php echo ig_format_number($post['saves'] ?? 0); ?></span>
                    </div>
                    <div class="ig-metric">
                        <i class="fas fa-eye"></i>
                        <span><?php echo ig_format_number($post['reach'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="ig-post-engagement-rate">
                    <span class="ig-rate"><?php echo round($post['engagement_rate'], 2); ?>%</span>
                    <small>engagement</small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="ig-empty-state">
            <i class="fas fa-images"></i>
            <p>Henuz yayinlanmis icerik yok</p>
            <a href="create-post.php" class="ig-btn ig-btn-primary">Ilk Icerigi Olustur</a>
        </div>
        <?php endif; ?>
    </section>

    <!-- Hashtag Performance -->
    <section class="ig-section">
        <div class="ig-section-header">
            <h3><i class="fas fa-hashtag"></i> Hashtag Performansi</h3>
            <a href="hashtags.php" class="ig-link">Tum Hashtagler <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php if (!empty($hashtag_performance)): ?>
        <div class="ig-hashtag-performance">
            <?php foreach ($hashtag_performance as $hashtag): ?>
            <div class="ig-hashtag-item">
                <span class="ig-hashtag-name"><?php echo htmlspecialchars($hashtag['hashtag']); ?></span>
                <span class="ig-hashtag-category category-<?php echo $hashtag['category']; ?>">
                    <?php echo ucfirst($hashtag['category']); ?>
                </span>
                <span class="ig-hashtag-usage"><?php echo $hashtag['usage_count']; ?>x kullanildi</span>
                <div class="ig-hashtag-engagement">
                    <div class="ig-mini-bar" style="width: <?php echo min(100, $hashtag['avg_engagement'] * 10); ?>%"></div>
                    <span><?php echo round($hashtag['avg_engagement'], 2); ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="ig-empty-state ig-empty-sm">
            <p>Henuz hashtag verisi yok</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- AI Insights -->
    <section class="ig-section ig-ai-section">
        <div class="ig-section-header">
            <h3><i class="fas fa-robot"></i> AI Oneriler</h3>
            <span class="ig-badge-ai"><i class="fas fa-sparkles"></i> Yapay Zeka</span>
        </div>
        <div class="ig-ai-insights">
            <div class="ig-insight-card">
                <div class="ig-insight-icon"><i class="fas fa-clock"></i></div>
                <div class="ig-insight-content">
                    <h4>Optimal Paylasim Zamani</h4>
                    <p>Verilerinize gore <strong>Carsamba 18:00-20:00</strong> arasi en yuksek etkilesim aliyorsunuz. Bu saatlerde paylasiminizi planlayin.</p>
                </div>
            </div>
            <div class="ig-insight-card">
                <div class="ig-insight-icon"><i class="fas fa-film"></i></div>
                <div class="ig-insight-content">
                    <h4>Reels Onerileri</h4>
                    <p>Carousel postlariniz feed postlariniza gore <strong>%23 daha fazla</strong> etkilesim aliyor. Carousel icerik sayinizi artirmayi deneyin.</p>
                </div>
            </div>
            <div class="ig-insight-card">
                <div class="ig-insight-icon"><i class="fas fa-hashtag"></i></div>
                <div class="ig-insight-content">
                    <h4>Hashtag Stratejisi</h4>
                    <p><strong>#businesstips</strong> hashtag'i size en yuksek etkilesimi getiriyor. Benzer niche hashtagler kullanmaya devam edin.</p>
                </div>
            </div>
            <div class="ig-insight-card">
                <div class="ig-insight-icon"><i class="fas fa-lightbulb"></i></div>
                <div class="ig-insight-content">
                    <h4>Icerik Onerisi</h4>
                    <p>Egitici icerikler (%40) stratejinizin temelini olusturuyor. <strong>Behind-the-scenes</strong> iceriklerle samimiyeti artirin.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
