from sqlalchemy import Column, Integer, String, Float, DateTime, JSON, Enum as SQLEnum
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from datetime import datetime
import enum

from app.core.database import Base


class CustomerSegment(enum.Enum):
    """AI-powered customer segmentation"""
    VIP = "vip"  # High value, high frequency
    LOYAL = "loyal"  # Regular customers
    AT_RISK = "at_risk"  # Used to buy, now inactive
    POTENTIAL = "potential"  # Low purchase, high engagement
    LOST = "lost"  # No activity for long time
    NEW = "new"  # New customers


class Customer(Base):
    __tablename__ = "customers"

    id = Column(Integer, primary_key=True, index=True)
    external_id = Column(String, unique=True, index=True)  # Shopify/IdeaSoft ID
    platform = Column(String)  # shopify, ideasoft, ticimax

    # Basic Info
    email = Column(String, unique=True, index=True)
    phone = Column(String, index=True)
    first_name = Column(String)
    last_name = Column(String)

    # RFM Metrics (AI Segmentation Base)
    recency_days = Column(Integer, default=0)  # Days since last order
    frequency = Column(Integer, default=0)  # Total number of orders
    monetary = Column(Float, default=0.0)  # Total money spent

    # AI Scores (0-100)
    rfm_score = Column(Integer, default=0)
    churn_probability = Column(Float, default=0.0)  # 0-1
    next_purchase_days = Column(Integer)  # Predicted days to next purchase
    lifetime_value_prediction = Column(Float, default=0.0)

    # Segmentation
    segment = Column(SQLEnum(CustomerSegment), default=CustomerSegment.NEW)

    # Behavioral Data
    total_orders = Column(Integer, default=0)
    total_spent = Column(Float, default=0.0)
    average_order_value = Column(Float, default=0.0)
    last_order_date = Column(DateTime, nullable=True)

    # Preferences (AI learned)
    preferred_categories = Column(JSON)  # ["electronics", "fashion"]
    preferred_brands = Column(JSON)
    price_sensitivity = Column(Float, default=0.5)  # 0-1

    # Communication
    whatsapp_opt_in = Column(String, default="pending")  # pending, opted_in, opted_out
    email_opt_in = Column(String, default="pending")
    last_contact_date = Column(DateTime, nullable=True)

    # Metadata
    metadata = Column(JSON)  # Extra data from platforms
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

    # Relationships
    orders = relationship("Order", back_populates="customer")
    whatsapp_sessions = relationship("WhatsAppSession", back_populates="customer")


    def __repr__(self):
        return f"<Customer {self.email} - {self.segment.value}>"
