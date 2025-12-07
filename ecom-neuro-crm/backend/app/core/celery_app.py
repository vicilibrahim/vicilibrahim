from celery import Celery
from app.core.config import settings

celery_app = Celery(
    "ecom_neuro_crm",
    broker=settings.REDIS_URL,
    backend=settings.REDIS_URL,
    include=["app.tasks"]
)

# Configuration
celery_app.conf.update(
    task_serializer="json",
    accept_content=["json"],
    result_serializer="json",
    timezone="Europe/Istanbul",
    enable_utc=True,
    task_track_started=True,
    task_time_limit=30 * 60,  # 30 minutes
)

# Task routing
celery_app.conf.task_routes = {
    "app.tasks.ai_tasks.*": {"queue": "ai"},
    "app.tasks.campaign_tasks.*": {"queue": "campaigns"},
    "app.tasks.webhook_tasks.*": {"queue": "webhooks"},
}

if __name__ == "__main__":
    celery_app.start()
