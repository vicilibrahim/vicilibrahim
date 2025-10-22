<?php
require_once 'config/database.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Lütfen kullanıcı adı ve şifrenizi girin!';
    } else {
        // Check user credentials
        $query = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];

                // Update last login and streak
                update_user_streak($user['id']);

                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı!';
            }
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı!';
        }
    }
}

$page_title = 'Giriş Yap';
include 'includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-left">
                <div class="auth-image">
                    <i class="fas fa-book-reader"></i>
                    <h2>Tekrar Hoş Geldiniz!</h2>
                    <p>Kaldığınız yerden devam edin ve hedeflerinize ulaşın</p>
                    <ul class="auth-features">
                        <li><i class="fas fa-chart-line"></i> İlerlemenizi Takip Edin</li>
                        <li><i class="fas fa-trophy"></i> Rozetler Kazanın</li>
                        <li><i class="fas fa-fire"></i> Streak'inizi Koruyun</li>
                        <li><i class="fas fa-star"></i> Puan Toplayın</li>
                    </ul>
                </div>
            </div>

            <div class="auth-right">
                <div class="auth-card">
                    <h2><i class="fas fa-sign-in-alt"></i> Giriş Yap</h2>
                    <p>Hesabınıza giriş yapın</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="auth-form">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Kullanıcı Adı veya E-posta</label>
                            <input type="text" id="username" name="username" class="form-control"
                                   placeholder="Kullanıcı adınız veya e-posta"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Şifre</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Şifreniz" required>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember_me"> Beni Hatırla
                            </label>
                            <a href="forgot-password.php" class="forgot-link">Şifremi Unuttum?</a>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Giriş Yap
                        </button>
                    </form>

                    <div class="auth-divider">
                        <span>veya</span>
                    </div>

                    <div class="auth-demo">
                        <p><strong>Demo Hesap:</strong></p>
                        <p>Kullanıcı: <code>admin</code> / Şifre: <code>admin123</code></p>
                    </div>

                    <div class="auth-footer">
                        <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
