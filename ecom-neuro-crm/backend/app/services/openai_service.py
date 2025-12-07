"""
OpenAI GPT-4 Integration
AI-powered customer service and product recommendations
"""

from openai import OpenAI
from typing import Dict, List
from sqlalchemy.orm import Session

from app.core.config import settings
from app.models.customer import Customer
from app.models.whatsapp_session import WhatsAppSession
from app.models.product import Product


client = OpenAI(api_key=settings.OPENAI_API_KEY) if settings.OPENAI_API_KEY else None


def get_ai_response(
    customer: Customer,
    session: WhatsAppSession,
    user_message: str,
    db: Session
) -> str:
    """
    Get AI-powered response for customer message
    Handles product search, recommendations, cart operations
    """

    if not client:
        return "AI servisi şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin."

    # Build context for AI
    system_prompt = f"""
Sen bir e-ticaret alışveriş asistanısın. Görevin müşterilere ürün bulmak, öneri yapmak ve alışveriş yapmalarına yardımcı olmak.

Müşteri Bilgileri:
- İsim: {customer.first_name} {customer.last_name}
- Segment: {customer.segment.value}
- Toplam Alışveriş: {customer.total_spent} TL ({customer.total_orders} sipariş)
- Tercih Ettiği Kategoriler: {', '.join(customer.preferred_categories) if customer.preferred_categories else 'Bilinmiyor'}

Mevcut Sepet:
{format_cart_for_ai(session.cart_items)}

Kurallar:
1. Samimi ve yardımsever ol
2. Türkçe konuş
3. Emoji kullan ama abartma
4. Kısa ve öz yanıtlar ver
5. Ürün önerirken müşterinin geçmiş alışverişlerini göz önünde bulundur
6. Sepet işlemleri için net talimatlar ver

Müşteri şunları yapabilir:
- Ürün aramak
- Sepete ürün eklemek
- Sepeti görmek
- Sipariş vermek
- Kampanya sormak
"""

    # Get conversation history
    conversation = [{"role": "system", "content": system_prompt}]

    # Add last 5 messages for context
    recent_messages = (session.messages or [])[-5:]
    for msg in recent_messages:
        conversation.append({
            "role": msg["role"],
            "content": msg["content"]
        })

    # Add current message
    conversation.append({"role": "user", "content": user_message})

    try:
        response = client.chat.completions.create(
            model=settings.OPENAI_MODEL,
            messages=conversation,
            temperature=0.7,
            max_tokens=500
        )

        ai_response = response.choices[0].message.content

        # Check if user wants to search products
        if should_search_products(user_message):
            products = search_products_for_message(user_message, db)
            if products:
                ai_response += "\n\n" + format_product_list(products)

        return ai_response

    except Exception as e:
        print(f"OpenAI API Error: {e}")
        return "Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin."


def should_search_products(message: str) -> bool:
    """Determine if message is asking for product search"""

    search_keywords = [
        "ara", "bul", "göster", "var mı", "ürün",
        "search", "show", "product", "öneri", "recommend"
    ]

    message_lower = message.lower()
    return any(keyword in message_lower for keyword in search_keywords)


def search_products_for_message(message: str, db: Session, limit: int = 3) -> List[Product]:
    """Search products based on message content"""

    # Extract keywords from message
    # Simple implementation - can be improved with NLP

    products = db.query(Product).filter(
        Product.is_active == True
    ).limit(limit).all()

    return products


def format_product_list(products: List[Product]) -> str:
    """Format product list for WhatsApp message"""

    if not products:
        return "Üzgünüm, uygun ürün bulamadım."

    result = "🛍️ *Önerilenler:*\n\n"

    for i, product in enumerate(products, 1):
        result += f"{i}. *{product.name}*\n"
        result += f"   Fiyat: {product.price} TL\n"
        if product.description:
            # First 100 chars
            desc = product.description[:100] + "..." if len(product.description) > 100 else product.description
            result += f"   {desc}\n"
        result += "\n"

    result += "Hangi ürünü sepete eklemek istersiniz?"

    return result


def format_cart_for_ai(cart_items: List[Dict]) -> str:
    """Format cart items for AI context"""

    if not cart_items:
        return "Boş"

    items_str = ""
    for item in cart_items:
        items_str += f"- {item['name']} x{item['quantity']} ({item['price']} TL)\n"

    return items_str


def extract_product_intent(message: str) -> Dict:
    """
    Use GPT to extract structured product intent from message
    Returns: {action: "search|add|remove|checkout", product: str, quantity: int}
    """

    if not client:
        return {"action": "unknown"}

    system_prompt = """
Kullanıcı mesajından alışveriş niyetini çıkar.

Dön:
{
  "action": "search|add_to_cart|remove_from_cart|view_cart|checkout|question",
  "product_query": "ürün ismi veya kategori",
  "quantity": sayı veya null
}
"""

    try:
        response = client.chat.completions.create(
            model="gpt-4-turbo-preview",
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": message}
            ],
            temperature=0.3,
            response_format={"type": "json_object"}
        )

        import json
        intent = json.loads(response.choices[0].message.content)
        return intent

    except:
        return {"action": "unknown"}


def generate_campaign_content(campaign_type: str, customer: Customer) -> str:
    """
    Generate personalized campaign content using AI
    """

    if not client:
        return "Merhaba! Özel bir teklifimiz var."

    prompt = f"""
Bir e-ticaret kampanya mesajı oluştur.

Kampanya Tipi: {campaign_type}
Müşteri: {customer.first_name}
Müşteri Segmenti: {customer.segment.value}
Toplam Harcama: {customer.total_spent} TL
Tercih Kategorileri: {', '.join(customer.preferred_categories) if customer.preferred_categories else 'Bilinmiyor'}

WhatsApp için kısa (max 2 paragraf), çekici ve kişiselleştirilmiş bir mesaj yaz.
Emoji kullan. Türkçe yaz.
"""

    try:
        response = client.chat.completions.create(
            model=settings.OPENAI_MODEL,
            messages=[{"role": "user", "content": prompt}],
            temperature=0.8,
            max_tokens=200
        )

        return response.choices[0].message.content

    except Exception as e:
        print(f"Error generating campaign: {e}")
        return f"Merhaba {customer.first_name}! Size özel fırsatlarımız var. İnceleyin!"
