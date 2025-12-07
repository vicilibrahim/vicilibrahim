from sqlalchemy import Column, Integer, String, Float, DateTime, JSON, Text
from sqlalchemy.sql import func

from app.core.database import Base


class Product(Base):
    __tablename__ = "products"

    id = Column(Integer, primary_key=True, index=True)
    external_id = Column(String, unique=True, index=True)
    platform = Column(String)  # shopify, ideasoft, ticimax

    # Basic Info
    name = Column(String, index=True)
    description = Column(Text, nullable=True)
    sku = Column(String, index=True)
    barcode = Column(String, nullable=True)

    # Pricing
    price = Column(Float)
    compare_at_price = Column(Float, nullable=True)
    cost = Column(Float, nullable=True)

    # Inventory
    quantity = Column(Integer, default=0)
    inventory_tracked = Column(String, default=True)

    # Categorization
    category = Column(String, nullable=True)
    tags = Column(JSON)  # ["summer", "sale", "trending"]
    brand = Column(String, nullable=True)

    # Media
    images = Column(JSON)  # [url1, url2, ...]

    # AI Insights
    popularity_score = Column(Float, default=0.0)  # Based on views/orders
    conversion_rate = Column(Float, default=0.0)
    recommended_price = Column(Float, nullable=True)  # AI suggested

    # SEO & Marketing
    embedding = Column(JSON, nullable=True)  # Vector embedding for similarity

    # Status
    is_active = Column(String, default=True)

    # Metadata
    metadata = Column(JSON)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

    def __repr__(self):
        return f"<Product {self.name} - {self.price}>"
