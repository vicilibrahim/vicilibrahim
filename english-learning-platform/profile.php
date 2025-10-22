<?php
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_data($user_id);
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = clean_input($_POST['full_name']);
        $email = clean_input($_POST['email']);

        if (empty($full_name) || empty($email)) {
            $error = 'Ad soyad ve e-posta boş bırakılamaz!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Geçerli bir e-posta adresi girin!';
        } else {
            // Check if email already exists for another user
            $check_email = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
            $result = $conn->query($check_email);

            if ($result->num_rows > 0) {
                $error = 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor!';
            } else {
                $update = "UPDATE users SET full_name = '$full_name', email = '$email' WHERE id = $user_id";
                if ($conn->query($update)) {
                    $success = 'Profil bilgileriniz güncellendi!';
                    $user = get_user_data($user_id);
                } else {
                    $error = 'Güncelleme sırasında bir hata oluştu!';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Tüm şifre alanlarını doldurun!';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Mevcut şifre hatalı!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Yeni şifre en az 6 karakter olmalıdır!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Yeni şifreler eşleşmiyor!';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            if ($conn->query($update)) {
                $success = 'Şifreniz başarıyla değiştirildi!';
            } else {
                $error = 'Şifre değiştirme sırasında bir hata oluştu!';
            }
        }
    }
}

// Get user statistics
$total_lessons = $conn->query("SELECT COUNT(*) as total FROM user_progress WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['total'];
$total_achievements = $conn->query("SELECT COUNT(*) as total FROM user_achievements WHERE user_id = $user_id")->fetch_assoc()['total'];
$vocab_count = $conn->query("SELECT COUNT(*) as total FROM user_vocabulary WHERE user_id = $user_id")->fetch_assoc()['total'];

$page_title = 'Profilim';
include 'includes/header.php';
?>

<section class="profile-section">
    <div class="container">
        <div class="profile-header">
            <div class="profile-cover"></div>
            <div class="profile-info-header">
                <div class="profile-avatar">
                    <img src="assets/images/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                    <button class="avatar-change-btn" title="Fotoğraf Değiştir">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                    <div class="profile-badges">
                        <span class="badge badge-level"><?php echo $user['current_level']; ?> Seviye</span>
                        <span class="badge badge-points"><i class="fas fa-star"></i> <?php echo number_format($user['total_points']); ?> Puan</span>
                        <span class="badge badge-streak"><i class="fas fa-fire"></i> <?php echo $user['streak_days']; ?> Gün</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="profile-main">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-edit"></i> Profil Bilgileri</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name"><i class="fas fa-id-card"></i> Ad Soyad</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control"
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="username"><i class="fas fa-user"></i> Kullanıcı Adı</label>
                                    <input type="text" id="username" name="username" class="form-control"
                                           value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small>Kullanıcı adı değiştirilemez</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> E-posta</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Değişiklikleri Kaydet
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-lock"></i> Şifre Değiştir</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="profile-form">
                            <div class="form-group">
                                <label for="current_password"><i class="fas fa-key"></i> Mevcut Şifre</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password"><i class="fas fa-lock"></i> Yeni Şifre</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password"><i class="fas fa-lock"></i> Yeni Şifre Tekrar</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-sync"></i> Şifreyi Değiştir
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="profile-sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> İstatistikler</h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-list">
                            <div class="stat-item">
                                <div class="stat-icon" style="background: #667eea;">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="stat-details">
                                    <strong><?php echo $total_lessons; ?></strong>
                                    <span>Tamamlanan Ders</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <div class="stat-icon" style="background: #f5576c;">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="stat-details">
                                    <strong><?php echo $total_achievements; ?></strong>
                                    <span>Kazanılan Başarı</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <div class="stat-icon" style="background: #4facfe;">
                                    <i class="fas fa-font"></i>
                                </div>
                                <div class="stat-details">
                                    <strong><?php echo $vocab_count; ?></strong>
                                    <span>Kelime Bilgisi</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <div class="stat-icon" style="background: #43e97b;">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-details">
                                    <strong><?php echo number_format($user['total_points']); ?></strong>
                                    <span>Toplam Puan</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <div class="stat-icon" style="background: #fa709a;">
                                    <i class="fas fa-fire"></i>
                                </div>
                                <div class="stat-details">
                                    <strong><?php echo $user['streak_days']; ?></strong>
                                    <span>Günlük Streak</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar"></i> Hesap Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-item">
                                <span>Kayıt Tarihi:</span>
                                <strong><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Son Giriş:</span>
                                <strong>
                                    <?php echo $user['last_login'] ? time_ago($user['last_login']) : 'İlk giriş'; ?>
                                </strong>
                            </div>
                            <div class="info-item">
                                <span>Günlük Hedef:</span>
                                <strong><?php echo $user['daily_goal']; ?> puan</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
