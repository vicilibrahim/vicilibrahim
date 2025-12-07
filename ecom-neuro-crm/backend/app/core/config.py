from pydantic_settings import BaseSettings
from typing import Optional


class Settings(BaseSettings):
    # App
    APP_NAME: str = "E-comNeuro CRM"
    VERSION: str = "1.0.0"
    DEBUG: bool = True

    # Database
    DATABASE_URL: str

    # Redis
    REDIS_URL: str = "redis://localhost:6379/0"

    # Security
    SECRET_KEY: str
    ALGORITHM: str = "HS256"
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 30

    # OpenAI
    OPENAI_API_KEY: str
    OPENAI_MODEL: str = "gpt-4-turbo-preview"

    # Shopify
    SHOPIFY_API_KEY: Optional[str] = None
    SHOPIFY_API_SECRET: Optional[str] = None
    SHOPIFY_ACCESS_TOKEN: Optional[str] = None

    # Twilio (WhatsApp)
    TWILIO_ACCOUNT_SID: Optional[str] = None
    TWILIO_AUTH_TOKEN: Optional[str] = None
    TWILIO_WHATSAPP_NUMBER: Optional[str] = None

    # IdeaSoft
    IDEASOFT_API_KEY: Optional[str] = None
    IDEASOFT_API_SECRET: Optional[str] = None

    # Ticimax
    TICIMAX_API_KEY: Optional[str] = None
    TICIMAX_API_SECRET: Optional[str] = None

    class Config:
        env_file = ".env"
        case_sensitive = True


settings = Settings()
