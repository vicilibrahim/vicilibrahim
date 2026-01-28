<?php
$page_title = 'Instagram Hesabi Bagla';
require_once 'config/config.php';
require_once 'includes/InstagramAPI.php';
require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if already connected
$existing_account = ig_get_account($user_id);

// Handle form submission for demo mode
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['demo_mode'])) {
        // Create a demo account for testing
        global $conn;

        $username = clean_input($_POST['username']);
        $brand_name = clean_input($_POST['brand_name']);
        $niche = clean_input($_POST['niche']);
        $brand_voice = clean_input($_POST['brand_voice']);
        $target_audience = clean_input($_POST['target_audience']);

        if (empty($username)) {
            $error = 'Kullanici adi gerekli';
        } else {
            // Insert demo account
            $query = "INSERT INTO instagram_accounts (user_id, instagram_user_id, username, access_token, business_account_id, niche, brand_name, brand_voice, target_audience, is_active)
                      VALUES ($user_id, 'demo_" . time() . "', '$username', 'demo_token', 'demo_account', '$niche', '$brand_name', '$brand_voice', '$target_audience', 1)";

            if ($conn->query($query)) {
                $success = 'Demo hesap olusturuldu! Simdi Instagram Planner\'i kullanabilirsiniz.';
                header('Location: index.php');
                exit;
            } else {
                $error = 'Hesap olusturulamadi: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Hesabi Bagla - Terra+ Instagram Planner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/instagram-planner.css">
    <style>
        .ig-connect-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px;
        }
        .ig-connect-card {
            background: white;
            border-radius: 20px;
            padding: 48px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .ig-connect-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .ig-connect-logo i {
            font-size: 4rem;
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .ig-connect-logo h1 {
            margin-top: 16px;
            font-size: 1.5rem;
            color: #1f2937;
        }
        .ig-connect-logo p {
            color: #6b7280;
            margin-top: 8px;
        }
        .ig-connect-options {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .ig-connect-option {
            padding: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .ig-connect-option:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        .ig-connect-option h3 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1rem;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .ig-connect-option p {
            font-size: 0.875rem;
            color: #6b7280;
            margin-left: 36px;
        }
        .ig-divider {
            text-align: center;
            position: relative;
            margin: 24px 0;
        }
        .ig-divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e5e7eb;
        }
        .ig-divider span {
            background: white;
            padding: 0 16px;
            position: relative;
            color: #9ca3af;
            font-size: 0.875rem;
        }
        .ig-demo-form {
            display: none;
        }
        .ig-demo-form.active {
            display: block;
        }
        .ig-form-group {
            margin-bottom: 16px;
        }
        .ig-form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #374151;
        }
        .ig-form-group input,
        .ig-form-group select,
        .ig-form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.875rem;
        }
        .ig-form-group input:focus,
        .ig-form-group select:focus,
        .ig-form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .ig-alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.875rem;
        }
        .ig-alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .ig-alert-success {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .ig-back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: #6b7280;
            text-decoration: none;
        }
        .ig-back-link:hover {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="ig-connect-page">
        <div class="ig-connect-card">
            <div class="ig-connect-logo">
                <i class="fab fa-instagram"></i>
                <h1>Instagram Hesabinizi Baglayin</h1>
                <p>Icerik planlamaya baslamak icin Instagram Business hesabinizi baglayiniz</p>
            </div>

            <?php if ($error): ?>
            <div class="ig-alert ig-alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="ig-alert ig-alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <div class="ig-connect-options">
                <a href="<?php echo InstagramAPI::getAuthUrl(bin2hex(random_bytes(16))); ?>" class="ig-connect-option">
                    <h3>
                        <i class="fab fa-facebook" style="color: #1877f2;"></i>
                        Facebook ile Baglan
                    </h3>
                    <p>Instagram Business hesabinizi Facebook sayfa uzerinden baglayiniz</p>
                </a>

                <div class="ig-divider">
                    <span>veya</span>
                </div>

                <div class="ig-connect-option" onclick="showDemoForm()">
                    <h3>
                        <i class="fas fa-flask" style="color: #667eea;"></i>
                        Demo Modu
                    </h3>
                    <p>API baglantisi olmadan sistemi test edin</p>
                </div>
            </div>

            <form method="POST" class="ig-demo-form" id="demoForm">
                <input type="hidden" name="demo_mode" value="1">

                <div class="ig-form-group">
                    <label for="username">Instagram Kullanici Adi</label>
                    <input type="text" id="username" name="username" placeholder="@kullaniciadi" required>
                </div>

                <div class="ig-form-group">
                    <label for="brand_name">Marka/Isletme Adi</label>
                    <input type="text" id="brand_name" name="brand_name" placeholder="Terra+">
                </div>

                <div class="ig-form-group">
                    <label for="niche">Nis/Sektor</label>
                    <select id="niche" name="niche">
                        <option value="business">Is / Girisimcilik</option>
                        <option value="marketing">Pazarlama</option>
                        <option value="ecommerce">E-ticaret</option>
                        <option value="lifestyle">Yasam Tarzi</option>
                        <option value="food">Yiyecek & Icecek</option>
                        <option value="fashion">Moda</option>
                        <option value="travel">Seyahat</option>
                        <option value="fitness">Fitness & Saglik</option>
                        <option value="tech">Teknoloji</option>
                        <option value="education">Egitim</option>
                        <option value="other">Diger</option>
                    </select>
                </div>

                <div class="ig-form-group">
                    <label for="brand_voice">Marka Sesi/Tonu</label>
                    <select id="brand_voice" name="brand_voice">
                        <option value="professional">Profesyonel</option>
                        <option value="friendly">Samimi & Sicak</option>
                        <option value="humorous">Eglenceli & Komik</option>
                        <option value="inspirational">Ilham Verici</option>
                        <option value="educational">Egitici</option>
                        <option value="luxurious">Luks & Premium</option>
                    </select>
                </div>

                <div class="ig-form-group">
                    <label for="target_audience">Hedef Kitle</label>
                    <textarea id="target_audience" name="target_audience" rows="2" placeholder="Ornegin: 25-45 yas arasi girisimciler, kucuk isletme sahipleri..."></textarea>
                </div>

                <button type="submit" class="ig-btn ig-btn-primary ig-btn-block ig-btn-lg">
                    <i class="fas fa-rocket"></i> Demo Hesap Olustur
                </button>
            </form>

            <a href="../dashboard.php" class="ig-back-link">
                <i class="fas fa-arrow-left"></i> Ana Platforma Don
            </a>
        </div>
    </div>

    <script>
        function showDemoForm() {
            document.getElementById('demoForm').classList.add('active');
        }
    </script>
</body>
</html>
