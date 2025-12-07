"""
Celery tasks for AI operations
Background jobs for customer segmentation and predictions
"""

from celery import shared_task
from app.core.database import SessionLocal
from app.models.customer import Customer
from app.services.ai_segmentation import update_customer_ai_metrics


@shared_task(name="app.tasks.ai_tasks.recalculate_all_customers")
def recalculate_all_customers():
    """
    Recalculate AI metrics for all customers
    Run this daily via cron
    """

    db = SessionLocal()

    try:
        customers = db.query(Customer).all()
        updated_count = 0

        for customer in customers:
            try:
                update_customer_ai_metrics(customer)
                updated_count += 1
            except Exception as e:
                print(f"Error updating customer {customer.id}: {e}")

        db.commit()

        return {
            "status": "completed",
            "customers_updated": updated_count,
            "total_customers": len(customers)
        }

    finally:
        db.close()


@shared_task(name="app.tasks.ai_tasks.predict_churn_batch")
def predict_churn_batch():
    """
    Batch predict churn for all customers
    Identify high-risk customers
    """

    db = SessionLocal()

    try:
        customers = db.query(Customer).all()
        high_risk = []

        for customer in customers:
            if customer.churn_probability > 0.7:
                high_risk.append({
                    "id": customer.id,
                    "email": customer.email,
                    "churn_prob": customer.churn_probability
                })

        return {
            "status": "completed",
            "high_risk_count": len(high_risk),
            "high_risk_customers": high_risk[:10]  # Top 10
        }

    finally:
        db.close()


@shared_task(name="app.tasks.ai_tasks.recalculate_customer")
def recalculate_customer(customer_id: int):
    """
    Recalculate metrics for single customer
    Called after new order
    """

    db = SessionLocal()

    try:
        customer = db.query(Customer).filter(Customer.id == customer_id).first()

        if not customer:
            return {"status": "error", "message": "Customer not found"}

        update_customer_ai_metrics(customer)
        db.commit()

        return {
            "status": "completed",
            "customer_id": customer_id,
            "segment": customer.segment.value,
            "rfm_score": customer.rfm_score
        }

    finally:
        db.close()
