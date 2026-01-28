<?php
$page_title = 'Icerik Takvimi';
require_once 'includes/header.php';

if (!$current_account) {
    header('Location: connect.php');
    exit;
}

// Get current month/year from query params or default to current
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Validate month/year
if ($current_month < 1 || $current_month > 12) {
    $current_month = (int)date('n');
}
if ($current_year < 2020 || $current_year > 2030) {
    $current_year = (int)date('Y');
}

// Calculate first and last day of month
$first_day_of_month = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = (int)date('t', $first_day_of_month);
$first_day_weekday = (int)date('N', $first_day_of_month); // 1 = Monday

// Get previous/next month links
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get posts for this month
$start_date = date('Y-m-01', $first_day_of_month);
$end_date = date('Y-m-t', $first_day_of_month);
$month_posts = ig_get_scheduled_posts($current_account['id'], $start_date, $end_date . ' 23:59:59');

// Also get published posts
global $conn;
$published_query = "SELECT p.*, c.name as category_name, c.color as category_color,
                   (SELECT media_url FROM ig_post_media WHERE post_id = p.id ORDER BY order_index LIMIT 1) as thumbnail
                   FROM ig_posts p
                   LEFT JOIN ig_content_categories c ON p.content_category_id = c.id
                   WHERE p.account_id = {$current_account['id']}
                   AND p.status = 'published'
                   AND p.published_at BETWEEN '$start_date' AND '$end_date 23:59:59'";
$published_result = $conn->query($published_query);
$published_posts = [];
while ($row = $published_result->fetch_assoc()) {
    $published_posts[] = $row;
}

// Merge and organize posts by date
$all_posts = array_merge($month_posts, $published_posts);
$posts_by_date = [];
foreach ($all_posts as $post) {
    $date = date('Y-m-d', strtotime($post['scheduled_at'] ?? $post['published_at']));
    if (!isset($posts_by_date[$date])) {
        $posts_by_date[$date] = [];
    }
    $posts_by_date[$date][] = $post;
}

// Get content categories for filter
$content_categories = ig_get_content_categories();

// Month names in Turkish
$month_names_tr = [
    1 => 'Ocak', 2 => 'Subat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayis', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Agustos',
    9 => 'Eylul', 10 => 'Ekim', 11 => 'Kasim', 12 => 'Aralik'
];
?>

<div class="ig-calendar-page">
    <!-- Calendar Header -->
    <div class="ig-calendar-header">
        <div class="ig-calendar-nav">
            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="ig-btn ig-btn-icon">
                <i class="fas fa-chevron-left"></i>
            </a>
            <h2><?php echo $month_names_tr[$current_month] . ' ' . $current_year; ?></h2>
            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="ig-btn ig-btn-icon">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <div class="ig-calendar-actions">
            <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="ig-btn ig-btn-outline">
                <i class="fas fa-calendar-day"></i> Bugune Don
            </a>
            <button class="ig-btn ig-btn-ai" onclick="openAICalendarGenerator()">
                <i class="fas fa-robot"></i> AI Takvim Olustur
            </button>
            <a href="create-post.php" class="ig-btn ig-btn-primary">
                <i class="fas fa-plus"></i> Yeni Icerik
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="ig-calendar-filters">
        <div class="ig-filter-group">
            <label>Icerik Tipi:</label>
            <div class="ig-filter-buttons">
                <button class="ig-filter-btn active" data-filter="all">Tumu</button>
                <button class="ig-filter-btn" data-filter="feed"><i class="fas fa-image"></i> Feed</button>
                <button class="ig-filter-btn" data-filter="carousel"><i class="fas fa-images"></i> Carousel</button>
                <button class="ig-filter-btn" data-filter="story"><i class="fas fa-circle-dot"></i> Story</button>
                <button class="ig-filter-btn" data-filter="reel"><i class="fas fa-film"></i> Reel</button>
            </div>
        </div>
        <div class="ig-filter-group">
            <label>Durum:</label>
            <div class="ig-filter-buttons">
                <button class="ig-filter-btn active" data-status="all">Tumu</button>
                <button class="ig-filter-btn" data-status="scheduled">Planli</button>
                <button class="ig-filter-btn" data-status="published">Yayinda</button>
                <button class="ig-filter-btn" data-status="draft">Taslak</button>
            </div>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="ig-calendar-container">
        <div class="ig-calendar-weekdays">
            <div class="ig-weekday">Pazartesi</div>
            <div class="ig-weekday">Sali</div>
            <div class="ig-weekday">Carsamba</div>
            <div class="ig-weekday">Persembe</div>
            <div class="ig-weekday">Cuma</div>
            <div class="ig-weekday ig-weekend">Cumartesi</div>
            <div class="ig-weekday ig-weekend">Pazar</div>
        </div>

        <div class="ig-calendar-grid">
            <?php
            // Empty cells before first day
            for ($i = 1; $i < $first_day_weekday; $i++):
                $prev_month_day = $days_in_month - ($first_day_weekday - $i - 1);
            ?>
            <div class="ig-calendar-day ig-other-month">
                <span class="ig-day-number"><?php echo $prev_month_day; ?></span>
            </div>
            <?php endfor; ?>

            <?php
            // Days of the month
            for ($day = 1; $day <= $days_in_month; $day++):
                $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                $is_today = $date === date('Y-m-d');
                $is_past = $date < date('Y-m-d');
                $day_posts = isset($posts_by_date[$date]) ? $posts_by_date[$date] : [];
                $weekday = (int)date('N', strtotime($date));
                $is_weekend = $weekday >= 6;
            ?>
            <div class="ig-calendar-day <?php echo $is_today ? 'ig-today' : ''; ?> <?php echo $is_past ? 'ig-past' : ''; ?> <?php echo $is_weekend ? 'ig-weekend' : ''; ?>" data-date="<?php echo $date; ?>">
                <div class="ig-day-header">
                    <span class="ig-day-number"><?php echo $day; ?></span>
                    <?php if (!$is_past): ?>
                    <button class="ig-add-post-btn" onclick="openQuickAdd('<?php echo $date; ?>')">
                        <i class="fas fa-plus"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="ig-day-content">
                    <?php if (empty($day_posts)): ?>
                        <?php if (!$is_past): ?>
                        <div class="ig-day-empty" onclick="openQuickAdd('<?php echo $date; ?>')">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($day_posts as $post): ?>
                        <div class="ig-calendar-post"
                             data-type="<?php echo $post['post_type']; ?>"
                             data-status="<?php echo $post['status']; ?>"
                             data-id="<?php echo $post['id']; ?>"
                             onclick="openPostDetail(<?php echo $post['id']; ?>)"
                             style="border-left-color: <?php echo $post['category_color'] ?? '#667eea'; ?>">
                            <span class="ig-post-time">
                                <?php echo date('H:i', strtotime($post['scheduled_at'] ?? $post['published_at'])); ?>
                            </span>
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
                            <span class="ig-post-caption-short">
                                <?php echo htmlspecialchars(mb_substr($post['caption'] ?? 'Basliksiz', 0, 25)); ?>
                            </span>
                            <span class="ig-post-status-dot status-<?php echo $post['status']; ?>"></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>

            <?php
            // Empty cells after last day
            $total_cells = $first_day_weekday - 1 + $days_in_month;
            $remaining = 7 - ($total_cells % 7);
            if ($remaining < 7):
                for ($i = 1; $i <= $remaining; $i++):
            ?>
            <div class="ig-calendar-day ig-other-month">
                <span class="ig-day-number"><?php echo $i; ?></span>
            </div>
            <?php
                endfor;
            endif;
            ?>
        </div>
    </div>

    <!-- Legend -->
    <div class="ig-calendar-legend">
        <div class="ig-legend-section">
            <h4>Icerik Tipleri:</h4>
            <div class="ig-legend-items">
                <span class="ig-legend-item"><i class="fas fa-image"></i> Feed</span>
                <span class="ig-legend-item"><i class="fas fa-images"></i> Carousel</span>
                <span class="ig-legend-item"><i class="fas fa-circle-dot"></i> Story</span>
                <span class="ig-legend-item"><i class="fas fa-film"></i> Reel</span>
            </div>
        </div>
        <div class="ig-legend-section">
            <h4>Durumlar:</h4>
            <div class="ig-legend-items">
                <span class="ig-legend-item"><span class="ig-status-dot status-scheduled"></span> Planli</span>
                <span class="ig-legend-item"><span class="ig-status-dot status-published"></span> Yayinda</span>
                <span class="ig-legend-item"><span class="ig-status-dot status-draft"></span> Taslak</span>
                <span class="ig-legend-item"><span class="ig-status-dot status-failed"></span> Hata</span>
            </div>
        </div>
        <div class="ig-legend-section">
            <h4>Kategoriler:</h4>
            <div class="ig-legend-items">
                <?php foreach ($content_categories as $cat): ?>
                <span class="ig-legend-item">
                    <span class="ig-category-dot" style="background-color: <?php echo $cat['color']; ?>"></span>
                    <?php echo htmlspecialchars($cat['name_tr']); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Modal -->
<div class="ig-modal" id="quickAddModal" style="display:none;">
    <div class="ig-modal-content">
        <div class="ig-modal-header">
            <h3><i class="fas fa-plus-circle"></i> Hizli Icerik Ekle</h3>
            <button type="button" class="ig-modal-close" onclick="closeModal('quickAddModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ig-modal-body">
            <p class="ig-modal-date" id="quickAddDate"></p>
            <div class="ig-quick-add-options">
                <a href="#" id="quickAddFeed" class="ig-quick-add-option">
                    <i class="fas fa-image"></i>
                    <span>Feed Post</span>
                </a>
                <a href="#" id="quickAddCarousel" class="ig-quick-add-option">
                    <i class="fas fa-images"></i>
                    <span>Carousel</span>
                </a>
                <a href="#" id="quickAddStory" class="ig-quick-add-option">
                    <i class="fas fa-circle-dot"></i>
                    <span>Story</span>
                </a>
                <a href="#" id="quickAddReel" class="ig-quick-add-option">
                    <i class="fas fa-film"></i>
                    <span>Reel</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Post Detail Modal -->
<div class="ig-modal" id="postDetailModal" style="display:none;">
    <div class="ig-modal-content ig-modal-lg">
        <div class="ig-modal-header">
            <h3><i class="fas fa-eye"></i> Icerik Detayi</h3>
            <button type="button" class="ig-modal-close" onclick="closeModal('postDetailModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ig-modal-body" id="postDetailContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<!-- AI Calendar Generator Modal -->
<div class="ig-modal" id="aiCalendarModal" style="display:none;">
    <div class="ig-modal-content">
        <div class="ig-modal-header">
            <h3><i class="fas fa-robot"></i> AI Takvim Olusturucu</h3>
            <button type="button" class="ig-modal-close" onclick="closeModal('aiCalendarModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ig-modal-body">
            <form id="aiCalendarForm">
                <div class="ig-form-group">
                    <label>Haftalik Post Sayisi</label>
                    <select id="aiPostsPerWeek" class="ig-select">
                        <option value="3">3 post/hafta</option>
                        <option value="5" selected>5 post/hafta</option>
                        <option value="7">7 post/hafta (her gun)</option>
                    </select>
                </div>
                <div class="ig-form-group">
                    <label>Icerik Karisimi</label>
                    <div class="ig-checkbox-group">
                        <label><input type="checkbox" name="include_feed" checked> Feed Post</label>
                        <label><input type="checkbox" name="include_carousel" checked> Carousel</label>
                        <label><input type="checkbox" name="include_reels" checked> Reels</label>
                        <label><input type="checkbox" name="include_stories"> Stories</label>
                    </div>
                </div>
                <div class="ig-form-group">
                    <label>Odak Konular (virgul ile ayirin)</label>
                    <input type="text" id="aiFocusTopics" class="ig-input" placeholder="urun tanitimi, tips, bts">
                </div>
                <div class="ig-form-group">
                    <label>Kac Haftalik Plan?</label>
                    <select id="aiWeeksCount" class="ig-select">
                        <option value="1">1 Hafta</option>
                        <option value="2" selected>2 Hafta</option>
                        <option value="4">1 Ay</option>
                    </select>
                </div>
                <button type="button" class="ig-btn ig-btn-primary ig-btn-block" onclick="generateAICalendar()">
                    <i class="fas fa-wand-magic-sparkles"></i> Takvim Olustur
                </button>
            </form>
            <div class="ig-ai-result" id="aiCalendarResult" style="display:none;">
                <h4>Onerilen Takvim:</h4>
                <div class="ig-ai-calendar-preview" id="aiCalendarPreview"></div>
                <div class="ig-modal-actions">
                    <button type="button" class="ig-btn ig-btn-outline" onclick="closeModal('aiCalendarModal')">
                        Iptal
                    </button>
                    <button type="button" class="ig-btn ig-btn-primary" onclick="applyAICalendar()">
                        <i class="fas fa-check"></i> Takvime Uygula
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openQuickAdd(date) {
    document.getElementById('quickAddDate').textContent = new Date(date).toLocaleDateString('tr-TR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('quickAddFeed').href = 'create-post.php?type=feed&date=' + date;
    document.getElementById('quickAddCarousel').href = 'create-post.php?type=carousel&date=' + date;
    document.getElementById('quickAddStory').href = 'create-post.php?type=story&date=' + date;
    document.getElementById('quickAddReel').href = 'create-post.php?type=reel&date=' + date;
    document.getElementById('quickAddModal').style.display = 'flex';
}

function openPostDetail(postId) {
    document.getElementById('postDetailModal').style.display = 'flex';
    document.getElementById('postDetailContent').innerHTML = '<div class="ig-loading"><i class="fas fa-spinner fa-spin"></i> Yukleniyor...</div>';

    fetch('api/get-post.php?id=' + postId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPostDetail(data.post);
            } else {
                document.getElementById('postDetailContent').innerHTML = '<p class="ig-error">Icerik yuklenemedi.</p>';
            }
        });
}

function openAICalendarGenerator() {
    document.getElementById('aiCalendarModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal on outside click
document.querySelectorAll('.ig-modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
