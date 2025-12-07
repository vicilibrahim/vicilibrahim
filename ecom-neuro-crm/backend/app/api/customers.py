from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from typing import List, Optional
from datetime import datetime

from app.core.database import get_db
from app.models.customer import Customer, CustomerSegment
from app.services.ai_segmentation import calculate_rfm_score, predict_customer_segment

router = APIRouter()


@router.get("/")
async def get_customers(
    skip: int = 0,
    limit: int = 100,
    segment: Optional[str] = None,
    db: Session = Depends(get_db)
):
    """Get all customers with optional filtering"""
    query = db.query(Customer)

    if segment:
        query = query.filter(Customer.segment == segment)

    customers = query.offset(skip).limit(limit).all()
    total = query.count()

    return {
        "total": total,
        "customers": customers
    }


@router.get("/{customer_id}")
async def get_customer(customer_id: int, db: Session = Depends(get_db)):
    """Get single customer by ID"""
    customer = db.query(Customer).filter(Customer.id == customer_id).first()

    if not customer:
        raise HTTPException(status_code=404, detail="Customer not found")

    return customer


@router.get("/{customer_id}/insights")
async def get_customer_insights(customer_id: int, db: Session = Depends(get_db)):
    """Get AI-powered customer insights"""
    customer = db.query(Customer).filter(Customer.id == customer_id).first()

    if not customer:
        raise HTTPException(status_code=404, detail="Customer not found")

    return {
        "customer_id": customer.id,
        "segment": customer.segment.value,
        "rfm_score": customer.rfm_score,
        "churn_probability": customer.churn_probability,
        "lifetime_value_prediction": customer.lifetime_value_prediction,
        "next_purchase_days": customer.next_purchase_days,
        "insights": {
            "risk_level": "high" if customer.churn_probability > 0.7 else "medium" if customer.churn_probability > 0.4 else "low",
            "value_tier": "high" if customer.monetary > 5000 else "medium" if customer.monetary > 1000 else "low",
            "engagement": "active" if customer.recency_days < 30 else "declining" if customer.recency_days < 90 else "inactive"
        },
        "recommendations": generate_recommendations(customer)
    }


@router.post("/{customer_id}/recalculate")
async def recalculate_customer_metrics(customer_id: int, db: Session = Depends(get_db)):
    """Recalculate AI metrics for customer"""
    customer = db.query(Customer).filter(Customer.id == customer_id).first()

    if not customer:
        raise HTTPException(status_code=404, detail="Customer not found")

    # Recalculate RFM
    customer.rfm_score = calculate_rfm_score(
        customer.recency_days,
        customer.frequency,
        customer.monetary
    )

    # Predict segment
    customer.segment = predict_customer_segment(customer)

    db.commit()
    db.refresh(customer)

    return {"message": "Metrics recalculated", "customer": customer}


@router.get("/segments/distribution")
async def get_segment_distribution(db: Session = Depends(get_db)):
    """Get customer distribution by segment"""
    segments = {}

    for segment in CustomerSegment:
        count = db.query(Customer).filter(Customer.segment == segment).count()
        total_value = db.query(Customer).filter(Customer.segment == segment).with_entities(
            db.func.sum(Customer.total_spent)
        ).scalar() or 0

        segments[segment.value] = {
            "count": count,
            "total_value": total_value,
            "avg_value": total_value / count if count > 0 else 0
        }

    return segments


def generate_recommendations(customer: Customer) -> List[str]:
    """Generate actionable recommendations for customer"""
    recommendations = []

    if customer.churn_probability > 0.7:
        recommendations.append("🚨 High churn risk - Send win-back campaign immediately")

    if customer.segment == CustomerSegment.VIP:
        recommendations.append("⭐ VIP customer - Offer exclusive early access to new products")

    if customer.recency_days > 60:
        recommendations.append("📱 Re-engage via WhatsApp with personalized offer")

    if customer.average_order_value > 500 and customer.frequency < 3:
        recommendations.append("🎯 High AOV, low frequency - Target with loyalty program")

    if customer.preferred_categories:
        recommendations.append(f"🛍️ Send product recommendations from {', '.join(customer.preferred_categories[:2])}")

    return recommendations
