from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from typing import Optional

from app.core.database import get_db
from app.models.product import Product

router = APIRouter()


@router.get("/")
async def get_products(
    skip: int = 0,
    limit: int = 100,
    category: Optional[str] = None,
    db: Session = Depends(get_db)
):
    """Get all products"""
    query = db.query(Product).filter(Product.is_active == True)

    if category:
        query = query.filter(Product.category == category)

    products = query.offset(skip).limit(limit).all()
    total = query.count()

    return {
        "total": total,
        "products": products
    }


@router.get("/{product_id}")
async def get_product(product_id: int, db: Session = Depends(get_db)):
    """Get single product by ID"""
    product = db.query(Product).filter(Product.id == product_id).first()

    if not product:
        raise HTTPException(status_code=404, detail="Product not found")

    return product


@router.get("/search/{query}")
async def search_products(query: str, db: Session = Depends(get_db)):
    """Search products by name"""
    products = db.query(Product).filter(
        Product.name.ilike(f"%{query}%")
    ).limit(20).all()

    return {"results": products}
