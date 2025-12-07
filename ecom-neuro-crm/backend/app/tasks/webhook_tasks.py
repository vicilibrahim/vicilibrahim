"""
Celery tasks for webhook processing
Background processing to avoid blocking HTTP requests
"""

from celery import shared_task
from app.core.database import SessionLocal
from app.services.shopify_service import process_shopify_order, process_shopify_customer


@shared_task(name="app.tasks.webhook_tasks.process_order_webhook")
def process_order_webhook(platform: str, order_data: dict):
    """
    Process order webhook in background
    """

    db = SessionLocal()

    try:
        if platform == "shopify":
            order = process_shopify_order(order_data, db)
            return {
                "status": "completed",
                "order_id": order.id,
                "customer_id": order.customer_id
            }

        elif platform == "ideasoft":
            # TODO: Implement IdeaSoft processing
            pass

        elif platform == "ticimax":
            # TODO: Implement Ticimax processing
            pass

        return {"status": "error", "message": f"Unknown platform: {platform}"}

    finally:
        db.close()


@shared_task(name="app.tasks.webhook_tasks.process_customer_webhook")
def process_customer_webhook(platform: str, customer_data: dict):
    """
    Process customer webhook in background
    """

    db = SessionLocal()

    try:
        if platform == "shopify":
            customer = process_shopify_customer(customer_data, db)
            return {
                "status": "completed",
                "customer_id": customer.id
            }

        return {"status": "error", "message": f"Unknown platform: {platform}"}

    finally:
        db.close()
