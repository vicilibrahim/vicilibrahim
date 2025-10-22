<?php
require_once 'config/database.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $full_name = clean_input($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $level = clean_input($_POST['level']);

    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password) || empty($level)) {
        $error = 'Lütfen tüm alanları doldurun!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin!';
    } elseif (strlen($username) < 3) {
        $error = 'Kullanıcı adı en az 3 karakter olmalıdır!';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır!';
    } elseif ($password !== $confirm_password) {
        $error = 'Şifreler eşleşmiyor!';
    } else {
        // Check if username already exists
        $check_username = "SELECT id FROM users WHERE username = '$username'";
        $result = $conn->query($check_username);

        if ($result->num_rows > 0) {
            $error = 'Bu kullanıcı adı zaten kullanılıyor!';
        } else {
            // Check if email already exists
            $check_email = "SELECT id FROM users WHERE email = '$email'";
            $result = $conn->query($check_email);

            if ($result->num_rows > 0) {
                $error = 'Bu e-posta adresi zaten kayıtlı!';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user
                $insert = "INSERT INTO users (username, email, password, full_name, current_level)
                          VALUES ('$username', '$email', '$hashed_password', '$full_name', '$level')";

                if ($conn->query($insert)) {
                    $success = 'Kayıt başarılı! Giriş yapabilirsiniz.';
                    // Redirect to login after 2 seconds
                    header('refresh:2;url=login.php');
                } else {
                    $error = 'Kayıt sırasında bir hata oluştu!';
                }
            }
        }
    }
}

$page_title = 'Kayıt Ol';
include 'includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-left">
                <div class="auth-image">
                    <i class="fas fa-user-graduate"></i>
                    <h2>Yolculuğunuza Başlayın!</h2>
                    <p>Binlerce öğrenciye katılın ve İngilizce'nizi geliştirin</p>
                    <ul class="auth-features">
                        <li><i class="fas fa-check"></i> 500+ İnteraktif Ders</li>
                        <li><i class="fas fa-check"></i> Kişiselleştirilmiş Öğrenme</li>
                        <li><i class="fas fa-check"></i> Gelişim Takibi</li>
                        <li><i class="fas fa-check"></i> Başarı Rozetleri</li>
                    </ul>
                </div>
            </div>

            <div class="auth-right">
                <div class="auth-card">
                    <h2><i class="fas fa-user-plus"></i> Kayıt Ol</h2>
                    <p>Hesap oluşturmak için bilgilerinizi girin</p>

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

                    <form method="POST" action="" class="auth-form">
                        <div class="form-group">
                            <label for="full_name"><i class="fas fa-id-card"></i> Ad Soyad</label>
                            <input type="text" id="full_name" name="full_name" class="form-control"
                                   placeholder="Adınız ve soyadınız"
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Kullanıcı Adı</label>
                            <input type="text" id="username" name="username" class="form-control"
                                   placeholder="Kullanıcı adınızı seçin"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> E-posta</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   placeholder="ornek@email.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="level"><i class="fas fa-layer-group"></i> Seviyeniz</label>
                            <select id="level" name="level" class="form-control" required>
                                <option value="">Seviyenizi seçin</option>
                                <?php
                                $levels = $conn->query("SELECT * FROM levels ORDER BY order_index ASC");
                                while ($level = $levels->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $level['level_code']; ?>"
                                            <?php echo (isset($_POST['level']) && $_POST['level'] == $level['level_code']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($level['level_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Şifre</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="En az 6 karakter" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-lock"></i> Şifre Tekrar</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                   placeholder="Şifrenizi tekrar girin" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-user-plus"></i> Kayıt Ol
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
