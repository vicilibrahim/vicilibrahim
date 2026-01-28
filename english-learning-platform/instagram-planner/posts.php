<?php
$page_title = 'Icerikler';
require_once 'includes/header.php';

if (!$current_account) {
    header('Location: connect.php');
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query
global $conn;
$where = "account_id = {$current_account['id']}";

if ($status_filter !== 'all') {
    $status_filter = $conn->real_escape_string($status_filter);
    $where .= " AND status = '$status_filter'";
}

if ($type_filter !== 'all') {
    $type_filter = $conn->real_escape_string($type_filter);
    $where .= " AND post_type = '$type_filter'";
}

$query = "SELECT p.*, c.name as category_name, c.color as category_color,
          (SELECT media_url FROM ig_post_media WHERE post_id = p.id ORDER BY order_index LIMIT 1) as thumbnail
          FROM ig_posts p
          LEFT JOIN ig_content_categories c ON p.content_category_id = c.id
          WHERE $where
          ORDER BY COALESCE(scheduled_at, created_at) DESC";

$result = $conn->query($query);
$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}

// Count by status
$count_query = "SELECT status, COUNT(*) as count FROM ig_posts WHERE account_id = {$current_account['id']} GROUP BY status";
$count_result = $conn->query($count_query);
$status_counts = ['draft' => 0, 'scheduled' => 0, 'published' => 0, 'failed' => 0];
while ($row = $count_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
$total_count = array_sum($status_counts);
?>

<div class="ig-posts-page">
    <!-- Header -->
    <div class="ig-page-header">
        <div class="ig-page-title">
            <h1><i class="fas fa-images"></i> Icerikler</h1>
            <span class="ig-count-badge"><?php echo $total_count; ?> icerik</span>
        </div>
        <a href="create-post.php" class="ig-btn ig-btn-primary">
            <i class="fas fa-plus"></i> Yeni Icerik
        </a>
    </div>

    <!-- Status Tabs -->
    <div class="ig-status-tabs">
        <a href="?status=all" class="ig-status-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
            Tumu <span><?php echo $total_count; ?></span>
        </a>
        <a href="?status=draft" class="ig-status-tab <?php echo $status_filter === 'draft' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Taslak <span><?php echo $status_counts['draft']; ?></span>
        </a>
        <a href="?status=scheduled" class="ig-status-tab <?php echo $status_filter === 'scheduled' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Planli <span><?php echo $status_counts['scheduled']; ?></span>
        </a>
        <a href="?status=published" class="ig-status-tab <?php echo $status_filter === 'published' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Yayinda <span><?php echo $status_counts['published']; ?></span>
        </a>
        <a href="?status=failed" class="ig-status-tab <?php echo $status_filter === 'failed' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Hata <span><?php echo $status_counts['failed']; ?></span>
        </a>
    </div>

    <!-- Type Filter -->
    <div class="ig-type-filter">
        <span>Icerik Tipi:</span>
        <a href="?status=<?php echo $status_filter; ?>&type=all" class="<?php echo $type_filter === 'all' ? 'active' : ''; ?>">Tumu</a>
        <a href="?status=<?php echo $status_filter; ?>&type=feed" class="<?php echo $type_filter === 'feed' ? 'active' : ''; ?>">
            <i class="fas fa-image"></i> Feed
        </a>
        <a href="?status=<?php echo $status_filter; ?>&type=carousel" class="<?php echo $type_filter === 'carousel' ? 'active' : ''; ?>">
            <i class="fas fa-images"></i> Carousel
        </a>
        <a href="?status=<?php echo $status_filter; ?>&type=story" class="<?php echo $type_filter === 'story' ? 'active' : ''; ?>">
            <i class="fas fa-circle-dot"></i> Story
        </a>
        <a href="?status=<?php echo $status_filter; ?>&type=reel" class="<?php echo $type_filter === 'reel' ? 'active' : ''; ?>">
            <i class="fas fa-film"></i> Reel
        </a>
    </div>

    <!-- Posts Grid -->
    <?php if (empty($posts)): ?>
    <div class="ig-empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Henuz icerik yok</h3>
        <p>Ilk iceriginizi olusturmaya baslayin</p>
        <a href="create-post.php" class="ig-btn ig-btn-primary">
            <i class="fas fa-plus"></i> Yeni Icerik Olustur
        </a>
    </div>
    <?php else: ?>
    <div class="ig-posts-grid">
        <?php foreach ($posts as $post):
            $thumbnail = $post['thumbnail'] ?: 'assets/images/placeholder.jpg';
            $media_count = 0;
            if ($post['post_type'] === 'carousel') {
                $media_result = $conn->query("SELECT COUNT(*) as cnt FROM ig_post_media WHERE post_id = {$post['id']}");
                $media_count = $media_result->fetch_assoc()['cnt'];
            }
        ?>
        <div class="ig-post-card" data-id="<?php echo $post['id']; ?>">
            <div class="ig-post-thumbnail">
                <img src="<?php echo htmlspecialchars($thumbnail); ?>" alt="Post">
                <div class="ig-post-overlay">
                    <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="ig-overlay-btn">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button onclick="deletePost(<?php echo $post['id']; ?>)" class="ig-overlay-btn ig-overlay-delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <span class="ig-post-type-badge">
                    <?php
                    switch($post['post_type']) {
                        case 'feed': echo '<i class="fas fa-image"></i>'; break;
                        case 'carousel': echo '<i class="fas fa-images"></i> ' . $media_count; break;
                        case 'story': echo '<i class="fas fa-circle-dot"></i>'; break;
                        case 'reel': echo '<i class="fas fa-film"></i>'; break;
                    }
                    ?>
                </span>
                <?php if ($post['category_color']): ?>
                <span class="ig-post-category-dot" style="background-color: <?php echo $post['category_color']; ?>"></span>
                <?php endif; ?>
            </div>
            <div class="ig-post-info">
                <p class="ig-post-caption"><?php echo htmlspecialchars(mb_substr($post['caption'] ?? 'Captionsiz', 0, 80)); ?>...</p>
                <div class="ig-post-meta">
                    <span class="ig-post-status status-<?php echo $post['status']; ?>">
                        <?php
                        switch($post['status']) {
                            case 'draft': echo '<i class="fas fa-file-alt"></i> Taslak'; break;
                            case 'scheduled': echo '<i class="fas fa-clock"></i> ' . date('d M H:i', strtotime($post['scheduled_at'])); break;
                            case 'published': echo '<i class="fas fa-check-circle"></i> Yayinda'; break;
                            case 'failed': echo '<i class="fas fa-exclamation-triangle"></i> Hata'; break;
                        }
                        ?>
                    </span>
                </div>
                <?php if ($post['status'] === 'published'): ?>
                <div class="ig-post-stats">
                    <span><i class="fas fa-heart"></i> <?php echo ig_format_number($post['likes']); ?></span>
                    <span><i class="fas fa-comment"></i> <?php echo ig_format_number($post['comments']); ?></span>
                    <span><i class="fas fa-bookmark"></i> <?php echo ig_format_number($post['saves']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.ig-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.ig-page-title {
    display: flex;
    align-items: center;
    gap: 16px;
}
.ig-page-title h1 {
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.ig-count-badge {
    background: var(--ig-gray-200);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    color: var(--ig-gray-600);
}
.ig-status-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--ig-gray-200);
    padding-bottom: 16px;
}
.ig-status-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--ig-gray-100);
    border-radius: 10px;
    text-decoration: none;
    color: var(--ig-gray-600);
    font-size: 0.875rem;
    transition: all 0.2s ease;
}
.ig-status-tab:hover {
    background: var(--ig-gray-200);
}
.ig-status-tab.active {
    background: var(--ig-primary);
    color: white;
}
.ig-status-tab span {
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
}
.ig-type-filter {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}
.ig-type-filter span {
    color: var(--ig-gray-500);
    font-size: 0.875rem;
}
.ig-type-filter a {
    padding: 6px 12px;
    border-radius: 20px;
    text-decoration: none;
    color: var(--ig-gray-600);
    font-size: 0.75rem;
    background: var(--ig-gray-100);
    display: flex;
    align-items: center;
    gap: 6px;
}
.ig-type-filter a:hover,
.ig-type-filter a.active {
    background: var(--ig-primary);
    color: white;
}
.ig-post-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.ig-post-card:hover .ig-post-overlay {
    opacity: 1;
}
.ig-overlay-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--ig-gray-700);
    text-decoration: none;
    transition: all 0.2s ease;
}
.ig-overlay-btn:hover {
    transform: scale(1.1);
}
.ig-overlay-delete:hover {
    background: var(--ig-danger);
    color: white;
}
.ig-post-category-dot {
    position: absolute;
    top: 8px;
    left: 8px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
}
.ig-post-stats {
    display: flex;
    gap: 12px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--ig-gray-200);
}
.ig-post-stats span {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: var(--ig-gray-500);
}
</style>

<script>
async function deletePost(postId) {
    if (!confirm('Bu icerigi silmek istediginize emin misiniz?')) return;

    try {
        const response = await fetch('api/delete-post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId })
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert(result.error || 'Silme basarisiz');
        }
    } catch (error) {
        alert('Bir hata olustu');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
