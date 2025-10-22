<?php
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_data($user_id);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $daily_goal = (int)$_POST['daily_goal'];
    $current_level = clean_input($_POST['current_level']);

    if ($daily_goal < 10 || $daily_goal > 200) {
        $error = 'Günlük hedef 10-200 arasında olmalıdır!';
    } else {
        $update = "UPDATE users SET daily_goal = $daily_goal, current_level = '$current_level' WHERE id = $user_id";
        if ($conn->query($update)) {
            $success = 'Ayarlarınız güncellendi!';
            $user = get_user_data($user_id);
        } else {
            $error = 'Güncelleme sırasında bir hata oluştu!';
        }
    }
}

$page_title = 'Ayarlar';
include 'includes/header.php';
?>

<section class="settings-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Ayarlar</h1>
            <p>Hesap ayarlarınızı yönetin</p>
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

        <div class="settings-grid">
            <div class="settings-main">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-target"></i> Öğrenme Ayarları</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="settings-form">
                            <div class="form-group">
                                <label for="daily_goal"><i class="fas fa-bullseye"></i> Günlük Puan Hedefi</label>
                                <input type="number" id="daily_goal" name="daily_goal" class="form-control"
                                       value="<?php echo $user['daily_goal']; ?>" min="10" max="200" required>
                                <small>Her gün kazanmak istediğiniz puan miktarı (10-200)</small>
                            </div>

                            <div class="form-group">
                                <label for="current_level"><i class="fas fa-layer-group"></i> Mevcut Seviyeniz</label>
                                <select id="current_level" name="current_level" class="form-control" required>
                                    <?php
                                    $levels = $conn->query("SELECT * FROM levels ORDER BY order_index ASC");
                                    while ($level = $levels->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $level['level_code']; ?>"
                                                <?php echo $user['current_level'] == $level['level_code'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($level['level_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small>Seviyenizi güncellediğinizde size uygun dersler gösterilecektir</small>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-bell"></i> Bildirim Ayarları</h2>
                    </div>
                    <div class="card-body">
                        <div class="notification-settings">
                            <div class="notification-item">
                                <div class="notification-info">
                                    <strong>E-posta Bildirimleri</strong>
                                    <p>Yeni dersler ve güncellemeler hakkında bilgi alın</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="notification-item">
                                <div class="notification-info">
                                    <strong>Başarı Bildirimleri</strong>
                                    <p>Yeni rozet kazandığınızda bildirim alın</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="notification-item">
                                <div class="notification-info">
                                    <strong>Günlük Hatırlatıcılar</strong>
                                    <p>Günlük hedefiniz için hatırlatma alın</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Hesap Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-item">
                                <span>Kullanıcı Adı:</span>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            </div>
                            <div class="info-item">
                                <span>E-posta:</span>
                                <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Üyelik Tarihi:</span>
                                <strong><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Toplam Puan:</span>
                                <strong><?php echo number_format($user['total_points']); ?></strong>
                            </div>
                        </div>
                        <a href="profile.php" class="btn btn-outline btn-block" style="margin-top: 1rem;">
                            <i class="fas fa-user"></i> Profili Düzenle
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-shield-alt"></i> Güvenlik</h3>
                    </div>
                    <div class="card-body">
                        <a href="profile.php" class="btn btn-warning btn-block">
                            <i class="fas fa-key"></i> Şifre Değiştir
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.settings-section {
    padding: 2rem 0;
}

.settings-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.settings-form .form-group {
    margin-bottom: 2rem;
}

.settings-form small {
    display: block;
    margin-top: 0.5rem;
    color: var(--gray-color);
}

.notification-settings {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.notification-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--light-color);
    border-radius: var(--border-radius);
}

.notification-info strong {
    display: block;
    margin-bottom: 0.25rem;
}

.notification-info p {
    font-size: 0.9rem;
    color: var(--gray-color);
    margin: 0;
}

/* Toggle Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: var(--primary-color);
}

input:checked + .slider:before {
    transform: translateX(26px);
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
