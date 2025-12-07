# 🚀 Kurulum Rehberi

Bu dokümanda E-comNeuro CRM'i adım adım kurmayı öğreneceksiniz.

## Hızlı Başlangıç (5 Dakika)

### 1. API Anahtarlarını Alın

#### OpenAI API Key
1. https://platform.openai.com/ adresine gidin
2. API Keys bölümünden yeni key oluşturun
3. `sk-...` ile başlayan anahtarı kopyalayın

#### Twilio (WhatsApp)
1. https://www.twilio.com/ hesap oluşturun
2. Console'dan `Account SID` ve `Auth Token` alın
3. WhatsApp Sandbox'ı aktifleştirin: https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn
4. Sandbox numaranızı alın (örn: `whatsapp:+14155238886`)

#### Shopify API
1. Shopify mağazanızın admin paneline gidin
2. **Apps > Develop apps** bölümüne gidin
3. Yeni app oluşturun
4. API credentials'ı kopyalayın

### 2. Projeyi Kurun

```bash
# Repository'yi clone'layın
git clone <repo-url>
cd ecom-neuro-crm

# Environment dosyasını oluşturun
cp .env.example .env

# .env dosyasını düzenleyin
nano .env
```

`.env` içeriği:

```env
SECRET_KEY=your-very-secret-key-here-minimum-32-characters
OPENAI_API_KEY=sk-your-openai-key-here
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your-twilio-auth-token
TWILIO_WHATSAPP_NUMBER=whatsapp:+14155238886
SHOPIFY_API_KEY=your-shopify-api-key
SHOPIFY_API_SECRET=shpss_your-shopify-secret
```

### 3. Docker ile Başlatın

```bash
# Tüm servisleri başlat
docker-compose up -d

# Logları izleyin
docker-compose logs -f
```

### 4. Test Edin

```bash
# Health check
curl http://localhost:8000/health

# API docs
open http://localhost:8000/docs
```

---

## Manuel Kurulum (Docker Kullanmadan)

### Gereksinimler

- Python 3.11+
- PostgreSQL 15+
- Redis 7+

### Adımlar

#### 1. PostgreSQL Kurulumu

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
```

**macOS:**
```bash
brew install postgresql@15
brew services start postgresql@15
```

**Veritabanı oluşturun:**
```bash
sudo -u postgres psql
CREATE DATABASE ecom_neuro_crm;
CREATE USER ecom_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE ecom_neuro_crm TO ecom_user;
\q
```

#### 2. Redis Kurulumu

**Ubuntu/Debian:**
```bash
sudo apt install redis-server
sudo systemctl start redis
```

**macOS:**
```bash
brew install redis
brew services start redis
```

#### 3. Python Backend

```bash
cd backend

# Virtual environment
python3.11 -m venv venv
source venv/bin/activate  # Linux/Mac
# veya
venv\Scripts\activate  # Windows

# Bağımlılıkları yükle
pip install -r requirements.txt

# .env dosyasını backend/ klasörüne kopyalayın
cp ../.env .env

# Veritabanı tablolarını oluştur (otomatik)
# İlk çalıştırmada FastAPI tabloları oluşturur

# Backend'i başlat
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
```

#### 4. Celery Workers

**Ayrı terminalde:**

```bash
cd backend
source venv/bin/activate

# Worker'ı başlat
celery -A app.core.celery_app worker --loglevel=info -Q ai,campaigns,webhooks
```

**Başka bir terminalde:**

```bash
cd backend
source venv/bin/activate

# Beat'i başlat (zamanlı görevler)
celery -A app.core.celery_app beat --loglevel=info
```

---

## Production Deployment

### AWS EC2 / DigitalOcean / VPS

#### 1. Sunucu Hazırlığı

```bash
# Ubuntu 22.04 sunucuda
sudo apt update
sudo apt install -y docker.io docker-compose nginx certbot python3-certbot-nginx

# Docker'ı başlat
sudo systemctl start docker
sudo systemctl enable docker
```

#### 2. Proje Deploy

```bash
# Projeyi clone'layın
git clone <repo-url>
cd ecom-neuro-crm

# .env dosyasını oluşturun (Production değerleri ile)
nano .env

# Docker Compose ile başlatın
sudo docker-compose -f docker-compose.yml up -d
```

#### 3. Nginx Reverse Proxy

```bash
sudo nano /etc/nginx/sites-available/ecom-neuro-crm
```

```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# Siteyi aktifleştir
sudo ln -s /etc/nginx/sites-available/ecom-neuro-crm /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# SSL sertifikası (Let's Encrypt)
sudo certbot --nginx -d your-domain.com
```

#### 4. Environment Variables (Production)

```env
# Güvenlik
SECRET_KEY=<generate-strong-random-key>

# Database (Production PostgreSQL)
DATABASE_URL=postgresql://user:password@db-host:5432/dbname

# Redis (Production Redis)
REDIS_URL=redis://redis-host:6379/0

# API Keys
OPENAI_API_KEY=sk-...
TWILIO_ACCOUNT_SID=AC...
TWILIO_AUTH_TOKEN=...
TWILIO_WHATSAPP_NUMBER=whatsapp:+...

# Shopify
SHOPIFY_API_KEY=...
SHOPIFY_API_SECRET=shpss_...
```

---

## Shopify Webhook Kurulumu

### 1. Public URL Gerekli

Shopify webhook'ları public bir URL'ye gönderilmelidir. Development için:

**Option A: ngrok (Test için)**

```bash
# ngrok yükleyin: https://ngrok.com/
ngrok http 8000
```

`https://abc123.ngrok.io` gibi bir URL alacaksınız.

**Option B: Production Domain**

Production'da kendi domain'inizi kullanın: `https://api.your-domain.com`

### 2. Shopify Admin'de Webhook Ekleyin

1. Shopify Admin > **Settings > Notifications**
2. Scroll down to **Webhooks**
3. **Create webhook** butonuna tıklayın

**Order Creation Webhook:**
- Event: `Order creation`
- Format: `JSON`
- URL: `https://your-domain.com/api/webhooks/shopify/orders`
- API version: `2024-01` (latest)

**Customer Creation Webhook:**
- Event: `Customer creation`
- Format: `JSON`
- URL: `https://your-domain.com/api/webhooks/shopify/customers`

### 3. Test Edin

Shopify'da test siparişi oluşturun ve logları kontrol edin:

```bash
# Docker logs
docker-compose logs -f backend

# Manuel kurulumda
tail -f backend/logs/app.log
```

---

## WhatsApp Sandbox Kurulumu (Twilio)

### Development/Test İçin

1. https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn
2. WhatsApp Sandbox'ı aktifleştirin
3. Size verilen koda WhatsApp'tan mesaj atın (örn: "join yellow-tiger")
4. Artık bu numaradan bot ile konuşabilirsiniz

### Production İçin

1. Twilio'da WhatsApp Business hesabı onayı alın
2. Facebook Business Manager ile entegre edin
3. WhatsApp Business API erişimi alın
4. Kendi WhatsApp Business numaranızı kullanın

**Webhook URL'i Twilio'da tanımlayın:**
- Console > Messaging > Settings > WhatsApp Sandbox Settings
- When a message comes in: `https://your-domain.com/api/whatsapp/webhook`

---

## İlk Test

### 1. API Health Check

```bash
curl http://localhost:8000/health
```

Beklenen yanıt:
```json
{"status": "healthy"}
```

### 2. Test Müşterisi Oluştur

```bash
curl -X POST http://localhost:8000/api/customers \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "first_name": "Test",
    "last_name": "User",
    "phone": "+905551234567"
  }'
```

### 3. WhatsApp Testi

```bash
curl -X POST http://localhost:8000/api/whatsapp/send \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "+905551234567",
    "message": "Merhaba! Test mesajı."
  }'
```

---

## Sorun Giderme

### Port 8000 zaten kullanımda

```bash
# Portu değiştirin
# docker-compose.yml'de:
ports:
  - "8001:8000"  # 8001 kullan
```

### PostgreSQL bağlantı hatası

```bash
# Veritabanının çalıştığını kontrol edin
docker-compose ps

# Logs
docker-compose logs postgres
```

### Celery çalışmıyor

```bash
# Redis bağlantısını kontrol edin
redis-cli ping
# Yanıt: PONG

# Celery logs
docker-compose logs celery-worker
```

### OpenAI API hatası

```bash
# API key'i kontrol edin
echo $OPENAI_API_KEY

# Kredinizi kontrol edin: https://platform.openai.com/usage
```

---

## Yardım

Sorunlarınız için:
1. README.md dosyasını okuyun
2. GitHub Issues'da arama yapın
3. Yeni issue açın

Happy coding! 🚀
