"""
Shopify Integration Service
Handles webhooks and data sync
"""

from datetime import datetime
from sqlalchemy.orm import Session
from typing import Dict

from app.models.customer import Customer
from app.models.order import Order, OrderStatus
from app.models.product import Product
from app.services.ai_segmentation import update_customer_ai_metrics


def process_shopify_order(order_data: Dict, db: Session):
    """
    Process Shopify order webhook
    Creates/updates order and customer data
    """

    # Extract order info
    shopify_order_id = str(order_data.get("id"))
    customer_data = order_data.get("customer", {})

    # Get or create customer
    customer = get_or_create_customer_from_shopify(customer_data, db)

    # Create or update order
    order = db.query(Order).filter(Order.external_id == shopify_order_id).first()

    if not order:
        order = Order(
            external_id=shopify_order_id,
            platform="shopify",
            customer_id=customer.id
        )
        db.add(order)

    # Update order details
    order.order_number = order_data.get("order_number")
    order.total_price = float(order_data.get("total_price", 0))
    order.subtotal = float(order_data.get("subtotal_price", 0))
    order.tax = float(order_data.get("total_tax", 0))
    order.shipping = float(order_data.get("shipping_lines", [{}])[0].get("price", 0))
    order.discount = float(order_data.get("total_discounts", 0))
    order.currency = order_data.get("currency", "TRY")

    # Order items
    items = []
    for item in order_data.get("line_items", []):
        items.append({
            "product_id": item.get("product_id"),
            "variant_id": item.get("variant_id"),
            "name": item.get("name"),
            "quantity": item.get("quantity"),
            "price": item.get("price"),
            "sku": item.get("sku")
        })

    order.items = items
    order.item_count = len(items)

    # Addresses
    if order_data.get("shipping_address"):
        order.shipping_address = order_data["shipping_address"]

    if order_data.get("billing_address"):
        order.billing_address = order_data["billing_address"]

    # Status mapping
    financial_status = order_data.get("financial_status", "pending")
    fulfillment_status = order_data.get("fulfillment_status")

    if financial_status == "paid" and fulfillment_status == "fulfilled":
        order.status = OrderStatus.DELIVERED
    elif financial_status == "paid":
        order.status = OrderStatus.CONFIRMED
    elif financial_status == "refunded":
        order.status = OrderStatus.REFUNDED
    else:
        order.status = OrderStatus.PENDING

    # Check if first order
    existing_orders = db.query(Order).filter(Order.customer_id == customer.id).count()
    order.is_first_order = existing_orders == 0

    db.commit()

    # Update customer metrics
    update_customer_metrics_from_orders(customer, db)

    return order


def process_shopify_customer(customer_data: Dict, db: Session):
    """
    Process Shopify customer webhook
    Creates/updates customer data
    """

    customer = get_or_create_customer_from_shopify(customer_data, db)

    return customer


def get_or_create_customer_from_shopify(customer_data: Dict, db: Session) -> Customer:
    """
    Get existing or create new customer from Shopify data
    """

    shopify_customer_id = str(customer_data.get("id"))

    # Try to find existing customer
    customer = db.query(Customer).filter(
        Customer.external_id == shopify_customer_id
    ).first()

    if not customer:
        # Create new customer
        customer = Customer(
            external_id=shopify_customer_id,
            platform="shopify"
        )
        db.add(customer)

    # Update customer details
    customer.email = customer_data.get("email")
    customer.phone = customer_data.get("phone")
    customer.first_name = customer_data.get("first_name")
    customer.last_name = customer_data.get("last_name")

    # Marketing consent
    if customer_data.get("accepts_marketing"):
        customer.email_opt_in = "opted_in"

    # Metadata
    customer.metadata = {
        "shopify_created_at": customer_data.get("created_at"),
        "shopify_updated_at": customer_data.get("updated_at"),
        "tags": customer_data.get("tags", "").split(",")
    }

    db.commit()
    db.refresh(customer)

    return customer


def update_customer_metrics_from_orders(customer: Customer, db: Session):
    """
    Recalculate customer metrics based on their orders
    """

    orders = db.query(Order).filter(
        Order.customer_id == customer.id,
        Order.status.in_([OrderStatus.CONFIRMED, OrderStatus.DELIVERED])
    ).all()

    if not orders:
        return

    # Calculate metrics
    customer.total_orders = len(orders)
    customer.frequency = len(orders)
    customer.total_spent = sum(order.total_price for order in orders)
    customer.monetary = customer.total_spent
    customer.average_order_value = customer.total_spent / customer.total_orders

    # Get last order date
    last_order = max(orders, key=lambda x: x.created_at)
    customer.last_order_date = last_order.created_at

    # Calculate recency (days since last order)
    customer.recency_days = (datetime.now() - last_order.created_at).days

    # Extract preferences from orders
    categories = []
    brands = []

    for order in orders:
        for item in order.items or []:
            # TODO: Extract from product data
            pass

    customer.preferred_categories = list(set(categories))[:5] if categories else None
    customer.preferred_brands = list(set(brands))[:5] if brands else None

    # Update AI metrics
    update_customer_ai_metrics(customer)

    db.commit()
    db.refresh(customer)

    return customer


def sync_shopify_products(shop_url: str, access_token: str, db: Session):
    """
    Sync products from Shopify store
    """

    # TODO: Implement Shopify API product sync
    # Use shopify-python-api library

    pass
