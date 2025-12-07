from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from sqlalchemy import func
from datetime import datetime, timedelta

from app.core.database import get_db
from app.models.customer import Customer, CustomerSegment
from app.models.order import Order, OrderStatus

router = APIRouter()


@router.get("/dashboard")
async def get_dashboard_stats(db: Session = Depends(get_db)):
    """Get main dashboard statistics"""

    # Customer stats
    total_customers = db.query(Customer).count()
    active_customers = db.query(Customer).filter(Customer.recency_days <= 30).count()

    # Order stats (last 30 days)
    thirty_days_ago = datetime.now() - timedelta(days=30)
    recent_orders = db.query(Order).filter(
        Order.created_at >= thirty_days_ago,
        Order.status != OrderStatus.CANCELLED
    ).all()

    total_revenue = sum(order.total_price for order in recent_orders)
    total_orders = len(recent_orders)

    # Segment distribution
    segment_stats = {}
    for segment in CustomerSegment:
        count = db.query(Customer).filter(Customer.segment == segment).count()
        segment_stats[segment.value] = count

    # High risk customers
    high_risk = db.query(Customer).filter(Customer.churn_probability > 0.7).count()

    return {
        "customers": {
            "total": total_customers,
            "active": active_customers,
            "high_risk": high_risk,
            "segments": segment_stats
        },
        "revenue": {
            "last_30_days": total_revenue,
            "orders_count": total_orders,
            "average_order_value": total_revenue / total_orders if total_orders > 0 else 0
        },
        "ai_insights": {
            "predicted_churn_count": high_risk,
            "vip_customers": segment_stats.get("vip", 0),
            "at_risk_customers": segment_stats.get("at_risk", 0)
        }
    }


@router.get("/customer-lifetime-value")
async def get_ltv_distribution(db: Session = Depends(get_db)):
    """Get customer lifetime value distribution"""

    customers = db.query(Customer).all()

    ltv_ranges = {
        "0-500": 0,
        "500-1000": 0,
        "1000-5000": 0,
        "5000+": 0
    }

    for customer in customers:
        if customer.total_spent < 500:
            ltv_ranges["0-500"] += 1
        elif customer.total_spent < 1000:
            ltv_ranges["500-1000"] += 1
        elif customer.total_spent < 5000:
            ltv_ranges["1000-5000"] += 1
        else:
            ltv_ranges["5000+"] += 1

    return ltv_ranges


@router.get("/churn-prediction")
async def get_churn_prediction(db: Session = Depends(get_db)):
    """Get churn prediction analytics"""

    churn_ranges = {
        "low_risk": db.query(Customer).filter(Customer.churn_probability < 0.3).count(),
        "medium_risk": db.query(Customer).filter(
            Customer.churn_probability >= 0.3,
            Customer.churn_probability < 0.7
        ).count(),
        "high_risk": db.query(Customer).filter(Customer.churn_probability >= 0.7).count()
    }

    return churn_ranges
