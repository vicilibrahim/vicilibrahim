from sqlalchemy import Column, Integer, String, DateTime, JSON, ForeignKey, Text, Enum as SQLEnum
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
import enum

from app.core.database import Base


class SessionStatus(enum.Enum):
    ACTIVE = "active"
    COMPLETED = "completed"
    ABANDONED = "abandoned"


class WhatsAppSession(Base):
    """Track WhatsApp conversation sessions for cart building"""
    __tablename__ = "whatsapp_sessions"

    id = Column(Integer, primary_key=True, index=True)

    # Customer
    customer_id = Column(Integer, ForeignKey("customers.id"), index=True)
    phone_number = Column(String, index=True)

    # Session
    session_id = Column(String, unique=True, index=True)
    status = Column(SQLEnum(SessionStatus), default=SessionStatus.ACTIVE)

    # Cart Building
    cart_items = Column(JSON)  # [{product_id, quantity, price, ...}]
    cart_total = Column(Float, default=0.0)

    # Conversation
    messages = Column(JSON)  # [{role: "user/assistant", content: "...", timestamp: ...}]
    last_message_at = Column(DateTime)

    # AI Context
    customer_intent = Column(String, nullable=True)  # browse, buy, support, etc.
    recommended_products = Column(JSON)  # AI recommendations

    # Conversion
    order_created = Column(String, default=False)
    order_id = Column(Integer, nullable=True)

    # Metadata
    metadata = Column(JSON)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

    # Relationships
    customer = relationship("Customer", back_populates="whatsapp_sessions")

    def __repr__(self):
        return f"<WhatsAppSession {self.session_id} - {self.status.value}>"
