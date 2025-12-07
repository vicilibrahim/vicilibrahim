"""
Celery tasks for campaign operations
Background jobs for sending messages
"""

from celery import shared_task
from app.core.database import SessionLocal
from app.models.campaign import Campaign
from app.models.customer import Customer
from app.models.whatsapp_session import WhatsAppSession, SessionStatus
from app.services.whatsapp_service import send_campaign_message, send_abandoned_cart_reminder
from datetime import datetime, timedelta


@shared_task(name="app.tasks.campaign_tasks.send_campaign")
def send_campaign(campaign_id: int):
    """
    Send campaign to targeted customers
    """

    db = SessionLocal()

    try:
        campaign = db.query(Campaign).filter(Campaign.id == campaign_id).first()

        if not campaign:
            return {"status": "error", "message": "Campaign not found"}

        # Get targeted customers based on segment filter
        query = db.query(Customer)

        if campaign.segment_filter:
            # Apply filters
            if "segment" in campaign.segment_filter:
                query = query.filter(Customer.segment.in_(campaign.segment_filter["segment"]))

            if "min_ltv" in campaign.segment_filter:
                query = query.filter(Customer.lifetime_value_prediction >= campaign.segment_filter["min_ltv"])

        customers = query.all()

        sent_count = 0
        delivered_count = 0

        for customer in customers:
            try:
                result = send_campaign_message(customer, campaign.message_template)

                if result.get("status") == "sent":
                    sent_count += 1
                    delivered_count += 1

            except Exception as e:
                print(f"Error sending to customer {customer.id}: {e}")

        # Update campaign stats
        campaign.sent_count = sent_count
        campaign.delivered_count = delivered_count
        campaign.customer_count = len(customers)

        db.commit()

        return {
            "status": "completed",
            "campaign_id": campaign_id,
            "sent": sent_count,
            "total_customers": len(customers)
        }

    finally:
        db.close()


@shared_task(name="app.tasks.campaign_tasks.send_abandoned_cart_reminders")
def send_abandoned_cart_reminders():
    """
    Send reminders for abandoned carts
    Run this hourly via cron
    """

    db = SessionLocal()

    try:
        # Find sessions with items but no order, inactive for 1+ hours
        one_hour_ago = datetime.now() - timedelta(hours=1)

        sessions = db.query(WhatsAppSession).filter(
            WhatsAppSession.status == SessionStatus.ACTIVE,
            WhatsAppSession.cart_items != None,
            WhatsAppSession.order_created == False,
            WhatsAppSession.last_message_at < one_hour_ago
        ).all()

        sent_count = 0

        for session in sessions:
            try:
                send_abandoned_cart_reminder(session)
                sent_count += 1
            except Exception as e:
                print(f"Error sending reminder for session {session.id}: {e}")

        return {
            "status": "completed",
            "reminders_sent": sent_count,
            "total_abandoned": len(sessions)
        }

    finally:
        db.close()
