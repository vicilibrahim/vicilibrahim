"""
WhatsApp Service - Twilio Integration
Handles cart building and customer conversations
"""

from twilio.rest import Client
from datetime import datetime
from sqlalchemy.orm import Session
from typing import Dict
import uuid
import json

from app.core.config import settings
from app.models.customer import Customer
from app.models.whatsapp_session import WhatsAppSession, SessionStatus
from app.models.product import Product
from app.services.openai_service import get_ai_response, extract_product_intent


# Initialize Twilio client
def get_twilio_client():
    if not settings.TWILIO_ACCOUNT_SID or not settings.TWILIO_AUTH_TOKEN:
        return None

    return Client(settings.TWILIO_ACCOUNT_SID, settings.TWILIO_AUTH_TOKEN)


def send_whatsapp_message(to_number: str, message: str) -> Dict:
    """Send WhatsApp message via Twilio"""

    client = get_twilio_client()

    if not client:
        print("Twilio not configured, message not sent")
        return {"status": "skipped", "message": "Twilio not configured"}

    try:
        # Ensure number has whatsapp: prefix
        if not to_number.startswith("whatsapp:"):
            to_number = f"whatsapp:{to_number}"

        message = client.messages.create(
            from_=settings.TWILIO_WHATSAPP_NUMBER,
            body=message,
            to=to_number
        )

        return {
            "status": "sent",
            "sid": message.sid,
            "to": to_number
        }

    except Exception as e:
        print(f"Error sending WhatsApp message: {e}")
        return {"status": "error", "error": str(e)}


def handle_whatsapp_message(phone_number: str, message_body: str, db: Session):
    """
    Handle incoming WhatsApp message
    Main conversation handler with AI
    """

    # Find or create customer
    customer = db.query(Customer).filter(Customer.phone == phone_number).first()

    if not customer:
        # Unknown customer - ask for email or create basic profile
        response = "Merhaba! 👋 Sizi tanımıyoruz. Email adresinizi paylaşır mısınız?"
        send_whatsapp_message(phone_number, response)
        return

    # Get or create active session
    session = db.query(WhatsAppSession).filter(
        WhatsAppSession.customer_id == customer.id,
        WhatsAppSession.status == SessionStatus.ACTIVE
    ).first()

    if not session:
        session = create_cart_session(customer, db)

    # Add message to conversation
    messages = session.messages or []
    messages.append({
        "role": "user",
        "content": message_body,
        "timestamp": datetime.now().isoformat()
    })
    session.messages = messages
    session.last_message_at = datetime.now()

    # Get AI response
    ai_response = get_ai_response(
        customer=customer,
        session=session,
        user_message=message_body,
        db=db
    )

    # Add AI response to conversation
    messages.append({
        "role": "assistant",
        "content": ai_response,
        "timestamp": datetime.now().isoformat()
    })
    session.messages = messages

    db.commit()

    # Send response
    send_whatsapp_message(phone_number, ai_response)

    return session


def create_cart_session(customer: Customer, db: Session) -> WhatsAppSession:
    """Create new WhatsApp cart session"""

    session = WhatsAppSession(
        customer_id=customer.id,
        phone_number=customer.phone,
        session_id=str(uuid.uuid4()),
        status=SessionStatus.ACTIVE,
        cart_items=[],
        cart_total=0.0,
        messages=[]
    )

    db.add(session)
    db.commit()
    db.refresh(session)

    return session


def add_to_cart(session: WhatsAppSession, product: Product, quantity: int, db: Session):
    """Add product to WhatsApp session cart"""

    cart_items = session.cart_items or []

    # Check if product already in cart
    existing_item = next((item for item in cart_items if item["product_id"] == product.id), None)

    if existing_item:
        existing_item["quantity"] += quantity
    else:
        cart_items.append({
            "product_id": product.id,
            "name": product.name,
            "price": product.price,
            "quantity": quantity,
            "image": product.images[0] if product.images else None
        })

    session.cart_items = cart_items

    # Recalculate total
    session.cart_total = sum(item["price"] * item["quantity"] for item in cart_items)

    db.commit()
    db.refresh(session)

    return session


def get_cart_summary(session: WhatsAppSession) -> str:
    """Generate cart summary message"""

    if not session.cart_items:
        return "Sepetiniz boş 🛒"

    summary = "🛒 *Sepetiniz:*\n\n"

    for item in session.cart_items:
        summary += f"• {item['name']} x{item['quantity']} - {item['price']} TL\n"

    summary += f"\n*Toplam: {session.cart_total} TL*"

    return summary


def send_abandoned_cart_reminder(session: WhatsAppSession):
    """Send reminder for abandoned cart"""

    if not session.cart_items or session.order_created:
        return

    message = f"""
Merhaba! 👋

Sepetinizde {len(session.cart_items)} ürün var ama alışverişinizi tamamlamadınız.

{get_cart_summary(session)}

Alışverişi tamamlamak ister misiniz? 🛍️
"""

    send_whatsapp_message(session.phone_number, message.strip())


def send_campaign_message(customer: Customer, campaign_message: str):
    """Send campaign message to customer"""

    if customer.whatsapp_opt_in != "opted_in":
        return {"status": "skipped", "reason": "Customer not opted in"}

    # Personalize message
    personalized = campaign_message.replace("{{first_name}}", customer.first_name or "Değerli Müşterimiz")

    return send_whatsapp_message(customer.phone, personalized)
