from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from datetime import datetime, timedelta

from app.core.database import get_db
from app.models.order import Order, OrderStatus

router = APIRouter()


@router.get("/")
async def get_orders(
    skip: int = 0,
    limit: int = 100,
    customer_id: int = None,
    db: Session = Depends(get_db)
):
    """Get all orders"""
    query = db.query(Order)

    if customer_id:
        query = query.filter(Order.customer_id == customer_id)

    orders = query.order_by(Order.created_at.desc()).offset(skip).limit(limit).all()
    total = query.count()

    return {
        "total": total,
        "orders": orders
    }


@router.get("/{order_id}")
async def get_order(order_id: int, db: Session = Depends(get_db)):
    """Get single order by ID"""
    order = db.query(Order).filter(Order.id == order_id).first()

    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    return order


@router.get("/analytics/revenue")
async def get_revenue_analytics(
    days: int = 30,
    db: Session = Depends(get_db)
):
    """Get revenue analytics for last N days"""
    start_date = datetime.now() - timedelta(days=days)

    orders = db.query(Order).filter(
        Order.created_at >= start_date,
        Order.status != OrderStatus.CANCELLED
    ).all()

    total_revenue = sum(order.total_price for order in orders)
    total_orders = len(orders)
    avg_order_value = total_revenue / total_orders if total_orders > 0 else 0

    return {
        "period_days": days,
        "total_revenue": total_revenue,
        "total_orders": total_orders,
        "average_order_value": avg_order_value,
        "currency": "TRY"
    }
