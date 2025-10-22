# English Learning Platform - İngilizce Öğrenme Platformu

Modern ve kapsamlı bir İngilizce öğrenme platformu. PHP ve MySQL kullanılarak geliştirilmiştir.

## Özellikler

### Temel Özellikler
- ✅ Kullanıcı kayıt ve giriş sistemi
- ✅ 6 Seviye desteği (A1, A2, B1, B2, C1, C2)
- ✅ 5 Kategori (Grammar, Vocabulary, Reading, Writing, Listening)
- ✅ İnteraktif quiz sistemi
- ✅ Gelişim takip sistemi
- ✅ Kişisel profil yönetimi
- ✅ Admin paneli

### İleri Özellikler
- 🎯 Günlük hedef sistemi
- 🔥 Ardışık gün (streak) takibi
- 🏆 Başarı rozetleri
- 📊 Detaylı istatistikler ve grafikler
- 📚 Kelime defteri
- ⭐ Puan sistemi
- 📈 Kategori ve seviye bazlı ilerleme takibi

## Teknolojiler

- **Backend:** PHP 7.4+
- **Veritabanı:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **CSS Framework:** Custom CSS with modern design
- **Icons:** Font Awesome 6.4.0

## Kurulum

### Gereksinimler
- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache/Nginx web sunucusu
- XAMPP, WAMP veya benzeri bir PHP geliştirme ortamı (önerilir)

### Adım 1: Dosyaları İndirin
```bash
git clone <repository-url>
cd english-learning-platform
```

### Adım 2: Veritabanını Oluşturun
1. phpMyAdmin'i açın (genellikle http://localhost/phpmyadmin)
2. Yeni bir veritabanı oluşturun (önerilen isim: `english_learning_platform`)
3. `database.sql` dosyasını içe aktarın:
   - phpMyAdmin'de veritabanınızı seçin
   - "Import" (İçe Aktar) sekmesine tıklayın
   - `database.sql` dosyasını seçin ve "Go" butonuna tıklayın

### Adım 3: Veritabanı Bağlantısını Yapılandırın
`config/database.php` dosyasını açın ve veritabanı bilgilerinizi girin:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // MySQL kullanıcı adınız
define('DB_PASS', '');              // MySQL şifreniz
define('DB_NAME', 'english_learning_platform');
```

### Adım 4: Web Sunucusunu Yapılandırın

#### XAMPP Kullanıyorsanız:
1. Proje klasörünü `htdocs` dizinine kopyalayın
2. Apache'yi başlatın
3. Tarayıcınızda `http://localhost/english-learning-platform` adresine gidin

#### Yerleşik PHP Sunucusu Kullanıyorsanız:
```bash
cd english-learning-platform
php -S localhost:8000
```
Tarayıcınızda `http://localhost:8000` adresine gidin

### Adım 5: Giriş Yapın
Demo admin hesabı:
- **Kullanıcı Adı:** admin
- **Şifre:** admin123

## Proje Yapısı

```
english-learning-platform/
│
├── admin/                      # Admin paneli dosyaları
│   └── index.php              # Admin ana sayfası
│
├── api/                       # API endpoint'leri
│   ├── save-quiz.php         # Quiz sonuçlarını kaydet
│   └── add-vocabulary.php    # Kelime ekle
│
├── assets/                    # Statik dosyalar
│   ├── css/
│   │   └── style.css         # Ana CSS dosyası
│   ├── js/
│   │   └── main.js           # Ana JavaScript dosyası
│   └── images/
│       └── default.jpg       # Varsayılan profil resmi
│
├── config/                    # Yapılandırma dosyaları
│   └── database.php          # Veritabanı bağlantısı ve yardımcı fonksiyonlar
│
├── includes/                  # Ortak dosyalar
│   ├── header.php            # Header komponenti
│   └── footer.php            # Footer komponenti
│
├── lessons/                   # Ders içerikleri (gelecekte kullanılabilir)
├── quiz/                      # Quiz dosyaları (gelecekte kullanılabilir)
│
├── index.php                  # Ana sayfa
├── register.php              # Kayıt sayfası
├── login.php                 # Giriş sayfası
├── logout.php                # Çıkış
├── dashboard.php             # Kullanıcı paneli
├── profile.php               # Profil sayfası
├── progress.php              # Gelişim takibi
├── lessons.php               # Dersler listesi
├── lesson-view.php           # Ders görüntüleme ve quiz
├── vocabulary.php            # Kelime defteri
├── database.sql              # Veritabanı yapısı
└── README.md                 # Bu dosya
```

## Veritabanı Yapısı

### Tablolar:
1. **users** - Kullanıcı bilgileri
2. **levels** - Seviye tanımları (A1-C2)
3. **categories** - Kategori tanımları
4. **lessons** - Ders içerikleri
5. **quiz_questions** - Quiz soruları
6. **quiz_options** - Quiz seçenekleri
7. **user_progress** - Kullanıcı ders ilerlemesi
8. **user_quiz_attempts** - Quiz denemeleri
9. **vocabulary** - Kelime havuzu
10. **user_vocabulary** - Kullanıcı kelime defteri
11. **achievements** - Başarı tanımları
12. **user_achievements** - Kazanılan başarılar
13. **daily_activity** - Günlük aktivite kayıtları

## Kullanım

### Yeni Kullanıcı Olarak:
1. "Kayıt Ol" butonuna tıklayın
2. Formu doldurun ve seviyenizi seçin
3. Giriş yapın
4. Dashboard'dan derslerinize başlayın

### Ders Tamamlama:
1. "Dersler" menüsünden bir ders seçin
2. Ders içeriğini okuyun
3. Quiz'e geçin ve soruları cevaplayın
4. Sonuçlarınızı görün ve puan kazanın

### Kelime Öğrenme:
1. "Kelime Defteri" menüsüne gidin
2. "Yeni Kelimeler" sekmesinden kelime ekleyin
3. "Kelimelerim" sekmesinden pratik yapın

### Gelişimi Takip Etme:
1. "Gelişimim" menüsüne gidin
2. İstatistiklerinizi ve grafiklerinizi görün
3. Kategorilere göre ilerlemenizi kontrol edin
4. Başarılarınızı görüntüleyin

### Admin Paneli:
1. Admin hesabıyla giriş yapın
2. "Admin" menüsüne tıklayın
3. Kullanıcıları, dersleri ve kelimeleri yönetin

## Özelleştirme

### Renkleri Değiştirmek:
`assets/css/style.css` dosyasındaki CSS değişkenlerini düzenleyin:
```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --success-color: #28a745;
    /* ... */
}
```

### Yeni Ders Eklemek:
1. Admin paneline giriş yapın
2. Veritabanından `lessons` tablosuna yeni kayıt ekleyin
3. İlgili quiz sorularını `quiz_questions` tablosuna ekleyin

### Yeni Başarı Eklemek:
`achievements` tablosuna yeni kayıt ekleyin:
```sql
INSERT INTO achievements (name, description, icon, requirement_type, requirement_value, badge_color)
VALUES ('Başarı Adı', 'Açıklama', '🎯', 'lessons_completed', 100, '#FFD700');
```

## Güvenlik

### Öneriler:
- ✅ Parolalar `password_hash()` ile şifrelenir
- ✅ SQL injection koruması (`real_escape_string`)
- ✅ XSS koruması (`htmlspecialchars`)
- ✅ Session tabanlı kimlik doğrulama
- ⚠️ Production ortamında `.env` dosyası kullanın
- ⚠️ Veritabanı şifrelerini güçlü tutun
- ⚠️ HTTPS kullanın
- ⚠️ Dosya yükleme güvenliği ekleyin

## Geliştirme Planları

### Yakında Gelecek Özellikler:
- [ ] Ses dosyaları desteği (Listening alıştırmaları)
- [ ] Resim yükleme ve yönetimi
- [ ] Forum ve tartışma alanı
- [ ] Öğrenci-öğretmen mesajlaşma
- [ ] Canlı sohbet sistemi
- [ ] Video ders desteği
- [ ] Sertifika sistemi
- [ ] Sosyal medya entegrasyonu
- [ ] Mobil uygulama (PWA)
- [ ] Çoklu dil desteği

### Teknik İyileştirmeler:
- [ ] RESTful API
- [ ] JWT authentication
- [ ] Redis cache
- [ ] Docker desteği
- [ ] Unit testler
- [ ] CI/CD pipeline

## Sorun Giderme

### Veritabanı Bağlantı Hatası:
- MySQL servisinin çalıştığından emin olun
- `config/database.php` dosyasındaki bilgileri kontrol edin
- Veritabanı kullanıcısının yeterli izinleri olduğundan emin olun

### Sayfa Görünmüyor:
- Apache/PHP servisinin çalıştığından emin olun
- Proje yolunun doğru olduğunu kontrol edin
- PHP error log'larını kontrol edin

### CSS/JS Yüklenmiyor:
- Dosya yollarını kontrol edin
- Tarayıcı cache'ini temizleyin
- Developer tools'da console hatalarını kontrol edin

## Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add some AmazingFeature'`)
4. Branch'inizi push edin (`git push origin feature/AmazingFeature`)
5. Pull Request oluşturun

## Lisans

Bu proje eğitim amaçlı geliştirilmiştir.

## İletişim

Sorularınız için:
- Email: info@englishlearning.com
- GitHub: [Your GitHub Profile]

## Teşekkürler

- Font Awesome - İkonlar için
- Google Fonts - Fontlar için
- Tüm katkıda bulunanlara

---

**Not:** Bu proje eğitim amaçlı geliştirilmiştir. Production ortamında kullanmadan önce güvenlik önlemlerini artırın ve kapsamlı testler yapın.

## Ekran Görüntüleri

### Ana Sayfa
Modern ve kullanıcı dostu ana sayfa tasarımı

### Dashboard
Kullanıcı paneli ile gelişim takibi

### Dersler
İnteraktif ders ve quiz sistemi

### Gelişim
Detaylı istatistikler ve grafikler

### Kelime Defteri
Kişisel kelime öğrenme sistemi

---

Geliştirici: [Your Name]
Versiyon: 1.0.0
Tarih: 2025
