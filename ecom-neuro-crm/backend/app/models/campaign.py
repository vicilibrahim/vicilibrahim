from sqlalchemy import Column, Integer, String, Float, DateTime, JSON, Text, Enum as SQLEnum
from sqlalchemy.sql import func
import enum

from app.core.database import Base


class CampaignType(enum.Enum):
    ABANDONED_CART = "abandoned_cart"
    WIN_BACK = "win_back"
    UPSELL = "upsell"
    CROSS_SELL = "cross_sell"
    BIRTHDAY = "birthday"
    LOYALTY_REWARD = "loyalty_reward"
    NEW_PRODUCT = "new_product"
    FLASH_SALE = "flash_sale"


class CampaignChannel(enum.Enum):
    WHATSAPP = "whatsapp"
    EMAIL = "email"
    SMS = "sms"
    INSTAGRAM = "instagram"


class CampaignStatus(enum.Enum):
    DRAFT = "draft"
    SCHEDULED = "scheduled"
    RUNNING = "running"
    COMPLETED = "completed"
    PAUSED = "paused"


class Campaign(Base):
    __tablename__ = "campaigns"

    id = Column(Integer, primary_key=True, index=True)

    # Campaign Info
    name = Column(String, index=True)
    description = Column(Text, nullable=True)
    campaign_type = Column(SQLEnum(CampaignType))
    channel = Column(SQLEnum(CampaignChannel))
    status = Column(SQLEnum(CampaignStatus), default=CampaignStatus.DRAFT)

    # AI Configuration
    ai_enabled = Column(String, default=True)
    ai_personalization = Column(String, default=True)
    ai_send_time_optimization = Column(String, default=True)

    # Targeting
    segment_filter = Column(JSON)  # {"segment": ["vip", "loyal"], "min_ltv": 1000}
    customer_count = Column(Integer, default=0)

    # Message Template
    message_template = Column(Text)  # Support {{first_name}}, {{product_name}} etc.
    subject = Column(String, nullable=True)  # For email

    # Scheduling
    scheduled_at = Column(DateTime, nullable=True)
    send_immediately = Column(String, default=False)

    # Results (Real-time tracking)
    sent_count = Column(Integer, default=0)
    delivered_count = Column(Integer, default=0)
    opened_count = Column(Integer, default=0)
    clicked_count = Column(Integer, default=0)
    conversion_count = Column(Integer, default=0)
    revenue_generated = Column(Float, default=0.0)

    # Metrics
    open_rate = Column(Float, default=0.0)
    click_rate = Column(Float, default=0.0)
    conversion_rate = Column(Float, default=0.0)
    roi = Column(Float, default=0.0)

    # Metadata
    metadata = Column(JSON)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

    def __repr__(self):
        return f"<Campaign {self.name} - {self.status.value}>"
