<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$current_user = get_user_data($_SESSION['user_id']);
$current_account = ig_get_account($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Terra+ Instagram Planner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/instagram-planner.css">
    <?php if (isset($extra_css)): foreach($extra_css as $css): ?>
    <link rel="stylesheet" href="<?php echo $css; ?>">
    <?php endforeach; endif; ?>
</head>
<body>
    <div class="ig-app">
        <!-- Sidebar Navigation -->
        <aside class="ig-sidebar">
            <div class="ig-sidebar-header">
                <div class="ig-logo">
                    <i class="fab fa-instagram"></i>
                    <span>Terra+ Planner</span>
                </div>
            </div>

            <?php if ($current_account): ?>
            <div class="ig-account-info">
                <img src="<?php echo htmlspecialchars($current_account['profile_picture'] ?? 'assets/images/default-profile.png'); ?>" alt="Profile" class="ig-account-avatar">
                <div class="ig-account-details">
                    <span class="ig-account-username">@<?php echo htmlspecialchars($current_account['username']); ?></span>
                    <span class="ig-account-followers"><?php echo ig_format_number($current_account['followers_count']); ?> takipci</span>
                </div>
            </div>
            <?php endif; ?>

            <nav class="ig-nav">
                <a href="index.php" class="ig-nav-item <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="create-post.php" class="ig-nav-item <?php echo $current_page === 'create-post' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Yeni Icerik</span>
                </a>
                <a href="calendar.php" class="ig-nav-item <?php echo $current_page === 'calendar' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Takvim</span>
                </a>
                <a href="posts.php" class="ig-nav-item <?php echo $current_page === 'posts' ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    <span>Icerikler</span>
                </a>
                <a href="analytics.php" class="ig-nav-item <?php echo $current_page === 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analitik</span>
                </a>
                <a href="hashtags.php" class="ig-nav-item <?php echo $current_page === 'hashtags' ? 'active' : ''; ?>">
                    <i class="fas fa-hashtag"></i>
                    <span>Hashtag'ler</span>
                </a>
                <a href="ai-assistant.php" class="ig-nav-item <?php echo $current_page === 'ai-assistant' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i>
                    <span>AI Asistan</span>
                </a>

                <div class="ig-nav-divider"></div>

                <a href="settings.php" class="ig-nav-item <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Ayarlar</span>
                </a>
                <a href="../dashboard.php" class="ig-nav-item">
                    <i class="fas fa-arrow-left"></i>
                    <span>Ana Platforma Don</span>
                </a>
            </nav>

            <div class="ig-sidebar-footer">
                <div class="ig-user-menu">
                    <img src="../assets/images/<?php echo htmlspecialchars($current_user['profile_image']); ?>" alt="User" class="ig-user-avatar">
                    <span><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ig-main">
            <header class="ig-header">
                <button class="ig-menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="ig-header-actions">
                    <button class="ig-btn ig-btn-primary" onclick="window.location.href='create-post.php'">
                        <i class="fas fa-plus"></i>
                        <span>Yeni Post</span>
                    </button>

                    <div class="ig-notifications">
                        <button class="ig-icon-btn" id="notificationsBtn">
                            <i class="fas fa-bell"></i>
                            <span class="ig-badge">3</span>
                        </button>
                    </div>
                </div>
            </header>

            <div class="ig-content">
