from fastapi import APIRouter, Depends, Request, BackgroundTasks, HTTPException
from sqlalchemy.orm import Session
from pydantic import BaseModel
from typing import Optional

from app.core.database import get_db
from app.models.whatsapp_session import WhatsAppSession, SessionStatus
from app.models.customer import Customer
from app.services.whatsapp_service import (
    handle_whatsapp_message,
    send_whatsapp_message,
    create_cart_session
)

router = APIRouter()


class WhatsAppMessage(BaseModel):
    From: str  # Phone number
    Body: str  # Message content


@router.post("/webhook")
async def whatsapp_webhook(
    request: Request,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    """Handle incoming WhatsApp messages from Twilio"""

    form_data = await request.form()
    phone_number = form_data.get("From")
    message_body = form_data.get("Body")

    if not phone_number or not message_body:
        raise HTTPException(status_code=400, detail="Missing required fields")

    # Process message in background
    background_tasks.add_task(
        handle_whatsapp_message,
        phone_number,
        message_body,
        db
    )

    return {"status": "received"}


@router.post("/send")
async def send_message(
    phone_number: str,
    message: str,
    db: Session = Depends(get_db)
):
    """Send WhatsApp message to customer"""

    result = send_whatsapp_message(phone_number, message)

    return {
        "status": "sent",
        "phone_number": phone_number,
        "message_sid": result.get("sid")
    }


@router.post("/start-cart-session")
async def start_cart_session(
    customer_id: int,
    db: Session = Depends(get_db)
):
    """Start WhatsApp cart building session for customer"""

    customer = db.query(Customer).filter(Customer.id == customer_id).first()

    if not customer:
        raise HTTPException(status_code=404, detail="Customer not found")

    if not customer.phone:
        raise HTTPException(status_code=400, detail="Customer has no phone number")

    # Create session
    session = create_cart_session(customer, db)

    # Send initial message
    welcome_message = f"""
Merhaba {customer.first_name}! 👋

Size özel alışveriş asistanınız burada! 🛍️

Ne aramak istersiniz? Size yardımcı olabilirim:
- Ürün önerileri
- Sepet oluşturma
- Kampanyalar
- Sipariş takibi

Sadece ne istediğinizi yazın!
"""

    send_whatsapp_message(customer.phone, welcome_message.strip())

    return {
        "status": "started",
        "session_id": session.session_id,
        "customer": customer.email
    }


@router.get("/sessions/{session_id}")
async def get_session(session_id: str, db: Session = Depends(get_db)):
    """Get WhatsApp session details"""

    session = db.query(WhatsAppSession).filter(
        WhatsAppSession.session_id == session_id
    ).first()

    if not session:
        raise HTTPException(status_code=404, detail="Session not found")

    return session


@router.get("/customer/{customer_id}/sessions")
async def get_customer_sessions(
    customer_id: int,
    db: Session = Depends(get_db)
):
    """Get all WhatsApp sessions for a customer"""

    sessions = db.query(WhatsAppSession).filter(
        WhatsAppSession.customer_id == customer_id
    ).order_by(WhatsAppSession.created_at.desc()).all()

    return {"sessions": sessions}
