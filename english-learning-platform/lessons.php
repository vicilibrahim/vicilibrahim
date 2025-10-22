<?php
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Get filters
$level_filter = isset($_GET['level']) ? clean_input($_GET['level']) : '';
$category_filter = isset($_GET['category']) ? clean_input($_GET['category']) : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query
$where = [];
if ($level_filter) {
    $level_id = $conn->query("SELECT id FROM levels WHERE level_code = '$level_filter'")->fetch_assoc()['id'] ?? 0;
    $where[] = "l.level_id = $level_id";
}
if ($category_filter) {
    $category_id = $conn->query("SELECT id FROM categories WHERE name = '$category_filter'")->fetch_assoc()['id'] ?? 0;
    $where[] = "l.category_id = $category_id";
}
if ($search) {
    $where[] = "(l.title LIKE '%$search%' OR l.description LIKE '%$search%')";
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get lessons
$lessons = $conn->query("
    SELECT l.*, lv.level_code, lv.level_name, c.name as category_name, c.icon as category_icon, c.color as category_color,
           COALESCE(up.status, 'not_started') as progress_status, up.score
    FROM lessons l
    JOIN levels lv ON lv.id = l.level_id
    JOIN categories c ON c.id = l.category_id
    LEFT JOIN user_progress up ON up.lesson_id = l.id AND up.user_id = $user_id
    $where_clause
    ORDER BY lv.order_index, l.order_index
");

// Get all levels and categories for filters
$levels = $conn->query("SELECT * FROM levels ORDER BY order_index");
$categories = $conn->query("SELECT * FROM categories");

$page_title = 'Dersler';
include 'includes/header.php';
?>

<section class="lessons-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-book-open"></i> Tüm Dersler</h1>
            <p>İhtiyacınız olan dersleri bulun ve öğrenmeye başlayın</p>
        </div>

        <div class="filters-container">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label><i class="fas fa-search"></i></label>
                    <input type="text" name="search" placeholder="Ders ara..."
                           value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-layer-group"></i></label>
                    <select name="level" class="filter-select">
                        <option value="">Tüm Seviyeler</option>
                        <?php while ($level = $levels->fetch_assoc()): ?>
                        <option value="<?php echo $level['level_code']; ?>"
                                <?php echo $level_filter == $level['level_code'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($level['level_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-th-large"></i></label>
                    <select name="category" class="filter-select">
                        <option value="">Tüm Kategoriler</option>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $category['name']; ?>"
                                <?php echo $category_filter == $category['name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrele
                </button>

                <?php if ($level_filter || $category_filter || $search): ?>
                <a href="lessons.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Temizle
                </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="lessons-list">
            <?php if ($lessons->num_rows > 0): ?>
                <?php while ($lesson = $lessons->fetch_assoc()): ?>
                <div class="lesson-item">
                    <div class="lesson-badge" style="background: <?php echo $lesson['category_color']; ?>;">
                        <i class="fas <?php echo $lesson['category_icon']; ?>"></i>
                    </div>

                    <div class="lesson-main">
                        <div class="lesson-header">
                            <h3><?php echo htmlspecialchars($lesson['title']); ?></h3>
                            <div class="lesson-tags">
                                <span class="tag tag-level"><?php echo $lesson['level_code']; ?></span>
                                <span class="tag tag-category" style="background: <?php echo $lesson['category_color']; ?>;">
                                    <?php echo htmlspecialchars($lesson['category_name']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="lesson-description"><?php echo htmlspecialchars($lesson['description']); ?></p>
                        <div class="lesson-meta">
                            <span><i class="far fa-clock"></i> <?php echo $lesson['duration_minutes']; ?> dakika</span>
                            <span><i class="fas fa-star"></i> <?php echo $lesson['points']; ?> puan</span>
                            <?php if ($lesson['progress_status'] == 'completed'): ?>
                                <span class="lesson-status completed">
                                    <i class="fas fa-check-circle"></i> Tamamlandı (<?php echo $lesson['score']; ?>%)
                                </span>
                            <?php elseif ($lesson['progress_status'] == 'in_progress'): ?>
                                <span class="lesson-status in-progress">
                                    <i class="fas fa-spinner"></i> Devam Ediyor
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="lesson-action">
                        <a href="lesson-view.php?id=<?php echo $lesson['id']; ?>" class="btn btn-primary">
                            <?php if ($lesson['progress_status'] == 'completed'): ?>
                                <i class="fas fa-redo"></i> Tekrar Yap
                            <?php elseif ($lesson['progress_status'] == 'in_progress'): ?>
                                <i class="fas fa-play"></i> Devam Et
                            <?php else: ?>
                                <i class="fas fa-play"></i> Başla
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Ders Bulunamadı</h3>
                    <p>Arama kriterlerinize uygun ders bulunamadı. Filtreleri değiştirmeyi deneyin.</p>
                    <a href="lessons.php" class="btn btn-primary">Tüm Dersleri Gör</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
