from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from pydantic import BaseModel
from typing import Optional, List

from app.core.database import get_db
from app.models.campaign import Campaign, CampaignType, CampaignChannel, CampaignStatus

router = APIRouter()


class CampaignCreate(BaseModel):
    name: str
    description: Optional[str] = None
    campaign_type: str
    channel: str
    message_template: str
    segment_filter: dict = {}
    send_immediately: bool = False


@router.post("/")
async def create_campaign(campaign_data: CampaignCreate, db: Session = Depends(get_db)):
    """Create new campaign"""
    campaign = Campaign(
        name=campaign_data.name,
        description=campaign_data.description,
        campaign_type=CampaignType[campaign_data.campaign_type.upper()],
        channel=CampaignChannel[campaign_data.channel.upper()],
        message_template=campaign_data.message_template,
        segment_filter=campaign_data.segment_filter,
        send_immediately=campaign_data.send_immediately
    )

    db.add(campaign)
    db.commit()
    db.refresh(campaign)

    return campaign


@router.get("/")
async def get_campaigns(
    skip: int = 0,
    limit: int = 100,
    status: Optional[str] = None,
    db: Session = Depends(get_db)
):
    """Get all campaigns"""
    query = db.query(Campaign)

    if status:
        query = query.filter(Campaign.status == status)

    campaigns = query.offset(skip).limit(limit).all()
    total = query.count()

    return {
        "total": total,
        "campaigns": campaigns
    }


@router.get("/{campaign_id}")
async def get_campaign(campaign_id: int, db: Session = Depends(get_db)):
    """Get single campaign"""
    campaign = db.query(Campaign).filter(Campaign.id == campaign_id).first()

    if not campaign:
        raise HTTPException(status_code=404, detail="Campaign not found")

    return campaign


@router.get("/{campaign_id}/analytics")
async def get_campaign_analytics(campaign_id: int, db: Session = Depends(get_db)):
    """Get campaign performance analytics"""
    campaign = db.query(Campaign).filter(Campaign.id == campaign_id).first()

    if not campaign:
        raise HTTPException(status_code=404, detail="Campaign not found")

    return {
        "campaign_id": campaign.id,
        "name": campaign.name,
        "status": campaign.status.value,
        "metrics": {
            "sent": campaign.sent_count,
            "delivered": campaign.delivered_count,
            "opened": campaign.opened_count,
            "clicked": campaign.clicked_count,
            "conversions": campaign.conversion_count,
            "revenue": campaign.revenue_generated
        },
        "rates": {
            "open_rate": campaign.open_rate,
            "click_rate": campaign.click_rate,
            "conversion_rate": campaign.conversion_rate,
            "roi": campaign.roi
        }
    }
