# 📁 Proje Yapısı

```
ecom-neuro-crm/
├── backend/                          # Python FastAPI Backend
│   ├── app/
│   │   ├── __init__.py
│   │   ├── main.py                   # FastAPI ana uygulama
│   │   │
│   │   ├── core/                     # Temel konfigürasyon
│   │   │   ├── __init__.py
│   │   │   ├── config.py             # Ayarlar (Environment variables)
│   │   │   ├── database.py           # Database bağlantısı
│   │   │   └── celery_app.py         # Celery konfigürasyonu
│   │   │
│   │   ├── models/                   # SQLAlchemy Database Modelleri
│   │   │   ├── __init__.py
│   │   │   ├── customer.py           # Müşteri modeli + Segmentasyon
│   │   │   ├── order.py              # Sipariş modeli
│   │   │   ├── product.py            # Ürün modeli
│   │   │   ├── campaign.py           # Kampanya modeli
│   │   │   └── whatsapp_session.py   # WhatsApp sohbet oturumları
│   │   │
│   │   ├── api/                      # API Endpoints (Routes)
│   │   │   ├── __init__.py
│   │   │   ├── customers.py          # Müşteri API'leri
│   │   │   ├── orders.py             # Sipariş API'leri
│   │   │   ├── products.py           # Ürün API'leri
│   │   │   ├── campaigns.py          # Kampanya API'leri
│   │   │   ├── analytics.py          # Dashboard & Analytics
│   │   │   ├── webhooks.py           # Platform webhook'ları
│   │   │   └── whatsapp.py           # WhatsApp endpoints
│   │   │
│   │   ├── services/                 # Business Logic & Entegrasyonlar
│   │   │   ├── __init__.py
│   │   │   ├── ai_segmentation.py    # 🧠 RFM analizi, customer segmentation
│   │   │   ├── openai_service.py     # 🤖 GPT-4 entegrasyonu
│   │   │   ├── shopify_service.py    # 🛒 Shopify entegrasyonu
│   │   │   ├── whatsapp_service.py   # 📱 Twilio WhatsApp servisi
│   │   │   ├── ideasoft_service.py   # (TODO)
│   │   │   └── ticimax_service.py    # (TODO)
│   │   │
│   │   └── tasks/                    # Celery Background Tasks
│   │       ├── __init__.py
│   │       ├── ai_tasks.py           # AI hesaplama görevleri
│   │       ├── campaign_tasks.py     # Kampanya gönderim görevleri
│   │       └── webhook_tasks.py      # Webhook işleme görevleri
│   │
│   ├── requirements.txt              # Python bağımlılıkları
│   ├── .env.example                  # Environment örneği
│   └── tests/                        # (TODO) Unit tests
│
├── frontend/                         # (TODO) React + TypeScript
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── services/
│   │   └── App.tsx
│   └── package.json
│
├── ai-engine/                        # (Future) Gelişmiş ML modelleri
│   ├── recommendation/               # Product recommendation
│   ├── churn_prediction/             # Advanced churn models
│   └── sentiment_analysis/           # Customer feedback analysis
│
├── integrations/                     # E-ticaret platform entegrasyonları
│   ├── shopify/                      # Shopify helpers
│   ├── ideasoft/                     # IdeaSoft helpers
│   ├── ticimax/                      # Ticimax helpers
│   ├── whatsapp/                     # WhatsApp helpers
│   └── instagram/                    # (TODO) Instagram DM
│
├── docker/                           # Docker konfigürasyonları
│   └── Dockerfile                    # Backend Dockerfile
│
├── docker-compose.yml                # Multi-container setup
├── .env.example                      # Environment variables örneği
├── .gitignore                        # Git ignore rules
├── README.md                         # Ana dokümantasyon
├── SETUP.md                          # Kurulum rehberi
└── PROJECT_STRUCTURE.md              # Bu dosya
```

---

## 📦 Katman Açıklamaları

### 1. `core/` - Temel Altyapı

**config.py**
- Environment variables yönetimi
- Pydantic Settings kullanarak type-safe config
- API anahtarları, database URL'leri

**database.py**
- SQLAlchemy engine ve session yönetimi
- Database connection pooling
- Dependency injection için `get_db()` fonksiyonu

**celery_app.py**
- Celery task queue konfigürasyonu
- Redis broker ayarları
- Task routing (ai, campaigns, webhooks kuyrukları)

---

### 2. `models/` - Database Şeması

Her model şunları içerir:
- SQLAlchemy ORM tanımları
- Relationships (foreign keys)
- Enums (durum kodları)
- Metadata alanları

**customer.py** - Müşteri Modeli
```python
class Customer:
    - Temel bilgiler (email, phone, name)
    - RFM metrikleri (recency, frequency, monetary)
    - AI skorları (rfm_score, churn_probability, ltv)
    - Segment (VIP, Loyal, At-Risk, etc.)
    - Preferences (kategoriler, markalar)
```

**order.py** - Sipariş Modeli
```python
class Order:
    - Sipariş detayları (order_number, status)
    - Finansal (total, tax, shipping, discount)
    - Items (ürün listesi JSON)
    - Customer ilişkisi
```

**whatsapp_session.py** - WhatsApp Oturum
```python
class WhatsAppSession:
    - Sohbet geçmişi (messages JSON)
    - Sepet (cart_items JSON)
    - AI context (intent, recommendations)
    - Session durumu (active, completed, abandoned)
```

---

### 3. `api/` - REST API Endpoints

Her dosya FastAPI router içerir:

**customers.py**
- `GET /api/customers` - Müşteri listesi
- `GET /api/customers/{id}` - Müşteri detayı
- `GET /api/customers/{id}/insights` - AI insights
- `POST /api/customers/{id}/recalculate` - Metrikleri yeniden hesapla

**campaigns.py**
- `POST /api/campaigns` - Kampanya oluştur
- `GET /api/campaigns/{id}/analytics` - Kampanya performansı

**webhooks.py**
- `POST /api/webhooks/shopify/orders` - Shopify sipariş webhook
- `POST /api/webhooks/ideasoft/orders` - IdeaSoft webhook

---

### 4. `services/` - Business Logic

**ai_segmentation.py** - AI Motor
```python
calculate_rfm_score()           # RFM skoru hesapla (0-100)
predict_customer_segment()      # Segment tahmin et (VIP, Loyal, etc.)
calculate_churn_probability()   # Churn riski (0-1)
predict_lifetime_value()        # Lifetime value tahmini
```

**openai_service.py** - GPT-4 Entegrasyon
```python
get_ai_response()               # Müşteri mesajına AI yanıtı
generate_campaign_content()     # Kişiselleştirilmiş kampanya
extract_product_intent()        # Mesajdan niyet çıkarımı
```

**whatsapp_service.py** - WhatsApp İşlemleri
```python
send_whatsapp_message()         # Mesaj gönder
handle_whatsapp_message()       # Gelen mesajı işle
create_cart_session()           # Sepet oturumu başlat
add_to_cart()                   # Sepete ekle
send_abandoned_cart_reminder()  # Terk edilmiş sepet hatırlatması
```

**shopify_service.py** - Shopify Entegrasyon
```python
process_shopify_order()         # Webhook'tan sipariş işle
process_shopify_customer()      # Webhook'tan müşteri işle
update_customer_metrics()       # RFM metriklerini güncelle
sync_shopify_products()         # Ürünleri senkronize et
```

---

### 5. `tasks/` - Background Jobs

**ai_tasks.py**
```python
@task recalculate_all_customers()   # Günlük: Tüm müşteri metrikleri
@task predict_churn_batch()         # Günlük: Churn tahmini
@task recalculate_customer(id)      # Tek müşteri güncelle
```

**campaign_tasks.py**
```python
@task send_campaign(campaign_id)         # Kampanya gönder
@task send_abandoned_cart_reminders()    # Saatlik: Sepet hatırlatmaları
```

**webhook_tasks.py**
```python
@task process_order_webhook()       # Webhook'ları background'da işle
@task process_customer_webhook()
```

---

## 🔄 Data Flow (Veri Akışı)

### 1. Shopify Sipariş Webhook Akışı

```
Shopify
  ↓ (webhook)
POST /api/webhooks/shopify/orders
  ↓
webhooks.py (endpoint)
  ↓ (background task)
Celery: process_order_webhook
  ↓
shopify_service.py: process_shopify_order()
  ↓
- Customer oluştur/güncelle
- Order oluştur
- RFM metriklerini hesapla
  ↓
ai_segmentation.py: update_customer_ai_metrics()
  ↓
Database'e kaydet
```

### 2. WhatsApp Mesaj Akışı

```
Müşteri WhatsApp mesaj gönderir
  ↓ (Twilio webhook)
POST /api/whatsapp/webhook
  ↓
whatsapp.py (endpoint)
  ↓ (background task)
whatsapp_service.py: handle_whatsapp_message()
  ↓
- Session bul/oluştur
- Mesajı kaydet
  ↓
openai_service.py: get_ai_response()
  ↓
- GPT-4'ten yanıt al
- Ürün ara (gerekirse)
- Sepete ekle (gerekirse)
  ↓
whatsapp_service.py: send_whatsapp_message()
  ↓
Twilio API → Müşteriye cevap gönderir
```

### 3. Kampanya Gönderim Akışı

```
Admin: POST /api/campaigns (kampanya oluştur)
  ↓
campaigns.py: create_campaign()
  ↓
Database'e kaydet
  ↓ (send_immediately = true ise)
Celery: send_campaign.delay(campaign_id)
  ↓
campaign_tasks.py: send_campaign()
  ↓
- Segment filtresine göre müşterileri bul
- Her müşteri için:
  ↓
  whatsapp_service.py: send_campaign_message()
  ↓
  - GPT-4 ile kişiselleştir (opsiyonel)
  - WhatsApp gönder
  ↓
Kampanya metriklerini güncelle
```

---

## 🎯 Önemli Dosyalar

| Dosya | Açıklama | Önem |
|-------|----------|------|
| `app/main.py` | FastAPI uygulaması, tüm router'ları yükler | ⭐⭐⭐ |
| `app/core/config.py` | Environment variables | ⭐⭐⭐ |
| `app/services/ai_segmentation.py` | AI segmentasyon mantığı | ⭐⭐⭐ |
| `app/services/openai_service.py` | GPT-4 entegrasyonu | ⭐⭐⭐ |
| `app/api/customers.py` | Müşteri API'leri | ⭐⭐⭐ |
| `docker-compose.yml` | Tüm servislerin orkestrasyonu | ⭐⭐⭐ |
| `requirements.txt` | Python bağımlılıkları | ⭐⭐⭐ |

---

## 🚀 Geliştirme Workflow

### Yeni Özellik Ekleme

**Örnek: Instagram DM Entegrasyonu**

1. **Model oluştur**: `app/models/instagram_session.py`
2. **Service ekle**: `app/services/instagram_service.py`
3. **API endpoint**: `app/api/instagram.py`
4. **Webhook handler**: `app/api/webhooks.py` içine ekle
5. **Background tasks**: `app/tasks/instagram_tasks.py`
6. **Router'ı main.py'ye ekle**

### Test Etme

```bash
# Unit test
pytest tests/test_ai_segmentation.py

# API test
curl -X POST http://localhost:8000/api/...

# Background task test
celery -A app.core.celery_app call app.tasks.ai_tasks.recalculate_all_customers
```

---

## 📚 Kaynaklar

- **FastAPI Docs**: https://fastapi.tiangolo.com/
- **SQLAlchemy**: https://docs.sqlalchemy.org/
- **Celery**: https://docs.celeryproject.org/
- **OpenAI API**: https://platform.openai.com/docs
- **Twilio WhatsApp**: https://www.twilio.com/docs/whatsapp
- **Shopify API**: https://shopify.dev/docs/api

---

Bu yapı **modüler**, **scalable** ve **maintainable** bir mimari sağlar. Yeni özellikler kolayca eklenebilir! 🚀
