from fastapi import APIRouter, Depends, Request, BackgroundTasks
from sqlalchemy.orm import Session
import hmac
import hashlib

from app.core.database import get_db
from app.core.config import settings
from app.services.shopify_service import process_shopify_order, process_shopify_customer
from app.services.ai_segmentation import update_customer_ai_metrics

router = APIRouter()


@router.post("/shopify/orders")
async def shopify_order_webhook(
    request: Request,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    """Handle Shopify order webhooks (create, update, paid)"""

    # Verify webhook signature
    # hmac_header = request.headers.get('X-Shopify-Hmac-Sha256')
    # if not verify_shopify_webhook(await request.body(), hmac_header):
    #     raise HTTPException(status_code=401, detail="Invalid webhook signature")

    data = await request.json()

    # Process order in background
    background_tasks.add_task(process_shopify_order, data, db)

    return {"status": "received"}


@router.post("/shopify/customers")
async def shopify_customer_webhook(
    request: Request,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    """Handle Shopify customer webhooks (create, update)"""

    data = await request.json()

    # Process customer in background
    background_tasks.add_task(process_shopify_customer, data, db)

    return {"status": "received"}


@router.post("/ideasoft/orders")
async def ideasoft_order_webhook(
    request: Request,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    """Handle IdeaSoft order webhooks"""
    data = await request.json()

    # TODO: Implement IdeaSoft order processing
    # Similar to Shopify but with IdeaSoft data structure

    return {"status": "received"}


@router.post("/ticimax/orders")
async def ticimax_order_webhook(
    request: Request,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    """Handle Ticimax order webhooks"""
    data = await request.json()

    # TODO: Implement Ticimax order processing

    return {"status": "received"}


def verify_shopify_webhook(data: bytes, hmac_header: str) -> bool:
    """Verify Shopify webhook signature"""
    if not settings.SHOPIFY_API_SECRET:
        return True  # Skip verification in development

    digest = hmac.new(
        settings.SHOPIFY_API_SECRET.encode('utf-8'),
        data,
        hashlib.sha256
    ).hexdigest()

    return hmac.compare_digest(digest, hmac_header)
