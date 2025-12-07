from sqlalchemy import Column, Integer, String, Float, DateTime, JSON, ForeignKey, Enum as SQLEnum
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
import enum

from app.core.database import Base


class OrderStatus(enum.Enum):
    PENDING = "pending"
    CONFIRMED = "confirmed"
    SHIPPED = "shipped"
    DELIVERED = "delivered"
    CANCELLED = "cancelled"
    REFUNDED = "refunded"


class Order(Base):
    __tablename__ = "orders"

    id = Column(Integer, primary_key=True, index=True)
    external_id = Column(String, unique=True, index=True)  # Platform order ID
    platform = Column(String)  # shopify, ideasoft, ticimax

    # Customer
    customer_id = Column(Integer, ForeignKey("customers.id"), index=True)

    # Order Details
    order_number = Column(String, index=True)
    status = Column(SQLEnum(OrderStatus), default=OrderStatus.PENDING)

    # Financial
    total_price = Column(Float)
    subtotal = Column(Float)
    tax = Column(Float, default=0.0)
    shipping = Column(Float, default=0.0)
    discount = Column(Float, default=0.0)
    currency = Column(String, default="TRY")

    # Items
    items = Column(JSON)  # [{product_id, name, quantity, price, ...}]
    item_count = Column(Integer, default=0)

    # Shipping
    shipping_address = Column(JSON)
    billing_address = Column(JSON)

    # AI Insights
    is_first_order = Column(String, default=False)
    predicted_delivery_date = Column(DateTime, nullable=True)
    satisfaction_score = Column(Float, nullable=True)  # AI predicted (0-1)

    # Metadata
    metadata = Column(JSON)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

    # Relationships
    customer = relationship("Customer", back_populates="orders")

    def __repr__(self):
        return f"<Order {self.order_number} - {self.total_price} {self.currency}>"
