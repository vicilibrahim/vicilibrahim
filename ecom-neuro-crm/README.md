# 🧠 E-comNeuro CRM

**AI-Powered E-commerce CRM System** - Yapay zeka destekli, e-ticaret işletmelerine özel müşteri ilişkileri yönetim sistemi.

## 🎯 Proje Özeti

E-comNeuro CRM, e-ticaret yapan işletmelerin müşterilerini daha iyi yönetmelerine, satışları artırmalarına ve müşteri sadakatini güçlendirmelerine yardımcı olan yapay zeka tabanlı bir CRM sistemidir.

### 🌟 Temel Özellikler

#### 🤖 AI-Powered (Yapay Zeka)
- **RFM Analizi**: Müşterileri Recency (Yenilik), Frequency (Sıklık), Monetary (Parasal Değer) skorlarına göre segmentlere ayırır
- **Müşteri Segmentasyonu**: VIP, Loyal, At-Risk, Potential, Lost, New
- **Churn Prediction**: Hangi müşterilerin kaybolma riski olduğunu tahmin eder
- **Lifetime Value Prediction**: Müşterinin gelecekteki toplam değerini tahmin eder
- **GPT-4 Entegrasyonu**: Akıllı sohbet, otomatik yanıtlar, kişiselleştirilmiş kampanyalar

#### 📱 WhatsApp Sepet Hazırlama Botu
- Müşteriler WhatsApp üzerinden ürün arayabilir
- AI asistan ürün önerileri yapar
- Sepet oluşturma ve yönetimi
- Terk edilmiş sepet hatırlatmaları

#### 🛒 E-ticaret Platform Entegrasyonları
- **Shopify** ✅ (Hazır)
- **IdeaSoft** 🔄 (Altyapı hazır)
- **Ticimax** 🔄 (Altyapı hazır)

#### 📊 Dashboard & Analytics
- Gerçek zamanlı müşteri metrikleri
- Segment dağılımı
- Gelir analitiği
- Kampanya performans takibi

#### 🎯 Akıllı Kampanya Yönetimi
- Segment bazlı hedefleme
- WhatsApp, Email, SMS kanalları
- AI destekli kişiselleştirme
- Otomatik gönderim zamanı optimizasyonu

---

## 🏗️ Teknoloji Stack

| Katman | Teknoloji | Açıklama |
|--------|-----------|----------|
| **Backend** | FastAPI | Modern, hızlı Python web framework |
| **Database** | PostgreSQL | İlişkisel veritabanı |
| **Cache & Queue** | Redis | Önbellekleme ve mesaj kuyruğu |
| **Task Queue** | Celery | Asenkron görev işleme |
| **AI Engine** | OpenAI GPT-4 | Doğal dil işleme ve akıllı yanıtlar |
| **ML** | scikit-learn, pandas | Müşteri segmentasyonu ve tahminleme |
| **WhatsApp API** | Twilio | WhatsApp Business API |
| **E-commerce** | Shopify API | E-ticaret platformu entegrasyonu |
| **Container** | Docker | Kolay dağıtım ve kurulum |

---

## 🚀 Kurulum

### Ön Gereksinimler

- Docker ve Docker Compose
- Python 3.11+ (Docker kullanmıyorsanız)
- PostgreSQL 15+ (Docker kullanmıyorsanız)

### 1. Repository'yi Clone'layın

```bash
git clone <repository-url>
cd ecom-neuro-crm
```

### 2. Environment Dosyasını Yapılandırın

```bash
cp .env.example .env
```

`.env` dosyasını düzenleyip API anahtarlarınızı ekleyin:

```env
SECRET_KEY=your-secret-key-here
OPENAI_API_KEY=sk-your-openai-key
TWILIO_ACCOUNT_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-token
TWILIO_WHATSAPP_NUMBER=whatsapp:+14155238886
SHOPIFY_API_KEY=your-shopify-key
SHOPIFY_API_SECRET=your-shopify-secret
```

### 3. Docker ile Başlatın

```bash
docker-compose up -d
```

Bu komut şunları başlatır:
- PostgreSQL veritabanı (port 5432)
- Redis (port 6379)
- FastAPI backend (port 8000)
- Celery worker (arka plan görevleri)
- Celery beat (zamanlı görevler)

### 4. API Dokümantasyonuna Erişin

Tarayıcınızda açın:
- **Swagger UI**: http://localhost:8000/docs
- **ReDoc**: http://localhost:8000/redoc

---

## 📖 Kullanım Kılavuzu

### API Endpoint'leri

#### Müşteriler

```bash
# Tüm müşterileri listele
GET /api/customers

# Segment bazlı filtreleme
GET /api/customers?segment=vip

# Müşteri detayları
GET /api/customers/{customer_id}

# AI insights
GET /api/customers/{customer_id}/insights

# Metrikleri yeniden hesapla
POST /api/customers/{customer_id}/recalculate
```

#### Siparişler

```bash
# Tüm siparişler
GET /api/orders

# Müşteriye özel siparişler
GET /api/orders?customer_id=123

# Gelir analitiği
GET /api/orders/analytics/revenue?days=30
```

#### Kampanyalar

```bash
# Kampanya oluştur
POST /api/campaigns
{
  "name": "VIP Flash Sale",
  "campaign_type": "flash_sale",
  "channel": "whatsapp",
  "message_template": "Merhaba {{first_name}}! Sadece VIP'ler için...",
  "segment_filter": {"segment": ["vip", "loyal"]}
}

# Kampanya listesi
GET /api/campaigns

# Kampanya analitikleri
GET /api/campaigns/{campaign_id}/analytics
```

#### WhatsApp

```bash
# Sepet oturumu başlat
POST /api/whatsapp/start-cart-session
{
  "customer_id": 123
}

# Mesaj gönder
POST /api/whatsapp/send
{
  "phone_number": "+905551234567",
  "message": "Merhaba!"
}
```

#### Webhooks

```bash
# Shopify sipariş webhook'u
POST /api/webhooks/shopify/orders

# Shopify müşteri webhook'u
POST /api/webhooks/shopify/customers
```

#### Analytics Dashboard

```bash
# Ana dashboard istatistikleri
GET /api/analytics/dashboard

# Müşteri yaşam boyu değeri dağılımı
GET /api/analytics/customer-lifetime-value

# Churn tahmin analizi
GET /api/analytics/churn-prediction
```

---

## 🧪 Shopify Entegrasyonu

### Webhook Kurulumu

Shopify Admin panelinde **Settings > Notifications > Webhooks** bölümüne gidin:

1. **Order creation** webhook'u ekleyin:
   - Event: `Order creation`
   - Format: `JSON`
   - URL: `https://your-domain.com/api/webhooks/shopify/orders`

2. **Customer creation** webhook'u ekleyin:
   - Event: `Customer creation`
   - Format: `JSON`
   - URL: `https://your-domain.com/api/webhooks/shopify/customers`

### Ürün Senkronizasyonu

```python
from app.services.shopify_service import sync_shopify_products

# Ürünleri senkronize et
sync_shopify_products(
    shop_url="your-shop.myshopify.com",
    access_token="your-access-token",
    db=db
)
```

---

## 🤖 AI Özellikleri

### 1. Müşteri Segmentasyonu

Sistem otomatik olarak müşterileri şu segmentlere ayırır:

- **VIP**: Yüksek harcama, sık alışveriş, yakın zamanda alışveriş
- **Loyal**: Düzenli müşteriler
- **At-Risk**: Eskiden iyi, şimdi azalan aktivite
- **Potential**: Düşük alışveriş ama aktif
- **Lost**: Uzun süredir alışveriş yok
- **NEW**: Yeni müşteriler

### 2. RFM Analizi

Her müşteri için otomatik hesaplanan skorlar:

```python
# RFM Skoru (0-100)
- Recency (0-40 puan): Son alışverişten bu yana geçen gün
- Frequency (0-30 puan): Toplam sipariş sayısı
- Monetary (0-30 puan): Toplam harcama
```

### 3. Churn Prediction

Hangi müşterilerin kaybolma riski olduğunu tahmin eder:

```json
{
  "customer_id": 123,
  "churn_probability": 0.85,  // 0-1 arası
  "risk_level": "high"         // low, medium, high
}
```

### 4. WhatsApp AI Asistan

GPT-4 destekli akıllı sohbet:

```
Müşteri: "Erkek ayakkabı arıyorum"
AI: "Tabii! Size birkaç harika seçenek buldum 👟

1. Nike Air Max - 1,299 TL
2. Adidas Ultraboost - 1,499 TL
3. Puma RS-X - 899 TL

Hangi modeli sepete eklemek istersiniz?"
```

---

## ⚙️ Celery Görevleri

### Periyodik Görevler

Celery Beat ile otomatik çalışan görevler:

```python
# Günlük: Tüm müşterilerin AI metriklerini güncelle
@celery_app.task
def recalculate_all_customers()

# Saatlik: Terk edilmiş sepet hatırlatmaları
@celery_app.task
def send_abandoned_cart_reminders()

# Günlük: Churn riski yüksek müşterileri tespit et
@celery_app.task
def predict_churn_batch()
```

### Manuel Görev Çalıştırma

```bash
# Celery worker'ı başlat
celery -A app.core.celery_app worker --loglevel=info

# Celery beat'i başlat (zamanlı görevler)
celery -A app.core.celery_app beat --loglevel=info
```

---

## 📊 Veritabanı Modelleri

### Customer (Müşteri)
- Temel bilgiler (email, telefon, ad)
- RFM metrikleri (recency, frequency, monetary)
- AI skorları (rfm_score, churn_probability, lifetime_value)
- Segment bilgisi
- Tercihler (kategoriler, markalar)

### Order (Sipariş)
- Sipariş detayları
- Müşteri ilişkisi
- Finansal bilgiler
- Ürün listesi

### Product (Ürün)
- Ürün bilgileri
- Fiyatlandırma
- Envanter
- AI metrikleri (popularity, conversion_rate)

### Campaign (Kampanya)
- Kampanya bilgileri
- Hedefleme ayarları
- Performans metrikleri
- ROI takibi

### WhatsAppSession (WhatsApp Oturumu)
- Sohbet geçmişi
- Sepet bilgileri
- AI önerileri

---

## 🔒 Güvenlik

- API anahtarlarını `.env` dosyasında saklayın
- Production'da `SECRET_KEY` değerini değiştirin
- Webhook imzalarını doğrulayın
- HTTPS kullanın
- Rate limiting ekleyin (production için)

---

## 🛠️ Geliştirme

### Lokal Geliştirme (Docker olmadan)

```bash
# Virtual environment oluştur
python -m venv venv
source venv/bin/activate  # Linux/Mac
# veya
venv\Scripts\activate  # Windows

# Bağımlılıkları yükle
cd backend
pip install -r requirements.txt

# PostgreSQL ve Redis'i başlat (ayrı terminallerde)
# Veya Docker ile sadece bunları çalıştır:
docker-compose up postgres redis

# Backend'i başlat
uvicorn app.main:app --reload

# Celery worker'ı başlat (ayrı terminal)
celery -A app.core.celery_app worker --loglevel=info
```

### Test

```bash
# Unit testler
pytest

# Coverage raporu
pytest --cov=app tests/
```

---

## 🗺️ Roadmap

### ✅ Tamamlanan
- [x] Temel CRM yapısı
- [x] AI müşteri segmentasyonu
- [x] Shopify entegrasyonu
- [x] WhatsApp sepet botu
- [x] Campaign yönetimi
- [x] Dashboard API'leri

### 🔄 Geliştiriliyor
- [ ] Frontend (React + TypeScript)
- [ ] IdeaSoft entegrasyonu
- [ ] Ticimax entegrasyonu
- [ ] Instagram DM entegrasyonu
- [ ] Email kampanya modülü

### 📋 Planlanan
- [ ] SMS entegrasyonu
- [ ] Gelişmiş product recommendation (collaborative filtering)
- [ ] A/B testing kampanyalar
- [ ] Multi-tenant (SaaS) desteği
- [ ] Mobile app
- [ ] Advanced analytics & reporting

---

## 🤝 Katkıda Bulunma

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit edin (`git commit -m 'Add amazing feature'`)
4. Push edin (`git push origin feature/amazing-feature`)
5. Pull Request açın

---

## 📝 Lisans

Bu proje MIT lisansı altındadır.

---

## 📧 İletişim

Sorularınız için issue açabilirsiniz.

---

## 🙏 Teşekkürler

Bu projeyi mümkün kılan harika teknolojilere teşekkürler:
- FastAPI
- OpenAI
- Twilio
- Shopify
- PostgreSQL
- Redis
- Celery
