<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>English Learning Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="index.php">
                    <i class="fas fa-graduation-cap"></i>
                    <span>English Learning</span>
                </a>
            </div>

            <?php if (is_logged_in()): ?>
                <?php $user = get_user_data($_SESSION['user_id']); ?>
                <div class="navbar-menu">
                    <ul class="navbar-nav">
                        <li><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Ana Sayfa</a></li>
                        <li><a href="lessons.php" class="nav-link"><i class="fas fa-book"></i> Dersler</a></li>
                        <li><a href="vocabulary.php" class="nav-link"><i class="fas fa-font"></i> Kelime Defteri</a></li>
                        <li><a href="progress.php" class="nav-link"><i class="fas fa-chart-line"></i> Gelişimim</a></li>

                        <?php if (is_admin()): ?>
                        <li><a href="admin/index.php" class="nav-link"><i class="fas fa-cog"></i> Admin</a></li>
                        <?php endif; ?>

                        <li class="user-menu">
                            <a href="#" class="nav-link user-info">
                                <img src="assets/images/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="profile-img-small">
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="profile.php"><i class="fas fa-user"></i> Profilim</a></li>
                                <li><a href="settings.php"><i class="fas fa-cog"></i> Ayarlar</a></li>
                                <li class="divider"></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a></li>
                            </ul>
                        </li>

                        <li class="points-badge">
                            <span class="badge">
                                <i class="fas fa-star"></i> <?php echo number_format($user['total_points']); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="navbar-menu">
                    <ul class="navbar-nav">
                        <li><a href="index.php" class="nav-link">Ana Sayfa</a></li>
                        <li><a href="login.php" class="nav-link">Giriş Yap</a></li>
                        <li><a href="register.php" class="btn btn-primary">Kayıt Ol</a></li>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="navbar-toggle" id="navbarToggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <main class="main-content">
