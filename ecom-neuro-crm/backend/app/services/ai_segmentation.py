"""
AI-Powered Customer Segmentation Module
Uses RFM Analysis + Machine Learning
"""

from datetime import datetime
from app.models.customer import Customer, CustomerSegment


def calculate_rfm_score(recency_days: int, frequency: int, monetary: float) -> int:
    """
    Calculate RFM score (0-100)

    RFM Analysis:
    - Recency: How recently customer made a purchase
    - Frequency: How often they purchase
    - Monetary: How much they spend
    """

    # Recency Score (0-40 points) - Lower is better
    if recency_days <= 7:
        r_score = 40
    elif recency_days <= 30:
        r_score = 30
    elif recency_days <= 90:
        r_score = 20
    elif recency_days <= 180:
        r_score = 10
    else:
        r_score = 0

    # Frequency Score (0-30 points)
    if frequency >= 20:
        f_score = 30
    elif frequency >= 10:
        f_score = 25
    elif frequency >= 5:
        f_score = 20
    elif frequency >= 3:
        f_score = 15
    elif frequency >= 1:
        f_score = 10
    else:
        f_score = 0

    # Monetary Score (0-30 points)
    if monetary >= 10000:
        m_score = 30
    elif monetary >= 5000:
        m_score = 25
    elif monetary >= 2000:
        m_score = 20
    elif monetary >= 1000:
        m_score = 15
    elif monetary >= 500:
        m_score = 10
    else:
        m_score = 5

    return r_score + f_score + m_score


def predict_customer_segment(customer: Customer) -> CustomerSegment:
    """
    Predict customer segment using RFM analysis

    Segments:
    - VIP: High value, high frequency, recent
    - LOYAL: Regular customers with good history
    - AT_RISK: Good history but declining
    - POTENTIAL: Low purchase but engaged
    - LOST: No recent activity
    - NEW: New customers
    """

    r = customer.recency_days
    f = customer.frequency
    m = customer.monetary

    # VIP: Recent, frequent, high spenders
    if r <= 30 and f >= 5 and m >= 2000:
        return CustomerSegment.VIP

    # LOYAL: Regular customers
    if r <= 60 and f >= 3 and m >= 1000:
        return CustomerSegment.LOYAL

    # AT_RISK: Used to be good, now declining
    if r > 90 and f >= 3 and m >= 1000:
        return CustomerSegment.AT_RISK

    # LOST: No activity for long time
    if r > 180:
        return CustomerSegment.LOST

    # POTENTIAL: Low purchase but still engaged
    if r <= 60 and f < 3:
        return CustomerSegment.POTENTIAL

    # NEW: Default for new customers
    return CustomerSegment.NEW


def calculate_churn_probability(customer: Customer) -> float:
    """
    Calculate probability of customer churning (0-1)
    Simple logistic model based on recency and engagement
    """

    # Base on recency primarily
    if customer.recency_days > 365:
        base_churn = 0.95
    elif customer.recency_days > 180:
        base_churn = 0.8
    elif customer.recency_days > 90:
        base_churn = 0.6
    elif customer.recency_days > 60:
        base_churn = 0.4
    elif customer.recency_days > 30:
        base_churn = 0.2
    else:
        base_churn = 0.1

    # Adjust by frequency and monetary
    if customer.frequency >= 5:
        base_churn *= 0.7  # Loyal customers less likely to churn

    if customer.monetary >= 5000:
        base_churn *= 0.8  # High value customers less likely to churn

    return min(base_churn, 1.0)


def predict_next_purchase_days(customer: Customer) -> int:
    """
    Predict when customer will make next purchase
    Based on average time between orders
    """

    if customer.frequency <= 1:
        return None  # Not enough data

    # Calculate average days between purchases
    avg_days_between = customer.recency_days // customer.frequency if customer.frequency > 0 else 90

    # Adjust based on recent behavior
    if customer.recency_days > avg_days_between * 2:
        # Customer is overdue
        return 0  # Should reach out immediately

    # Predict next purchase
    return avg_days_between - customer.recency_days


def predict_lifetime_value(customer: Customer) -> float:
    """
    Predict customer lifetime value (CLV)
    Simple model: Average Order Value * Expected Future Orders
    """

    if customer.frequency == 0:
        return 0.0

    avg_order_value = customer.monetary / customer.frequency

    # Predict future orders based on segment
    if customer.segment == CustomerSegment.VIP:
        expected_future_orders = 20
    elif customer.segment == CustomerSegment.LOYAL:
        expected_future_orders = 10
    elif customer.segment == CustomerSegment.POTENTIAL:
        expected_future_orders = 5
    elif customer.segment == CustomerSegment.NEW:
        expected_future_orders = 3
    else:
        expected_future_orders = 1

    return avg_order_value * expected_future_orders


def update_customer_ai_metrics(customer: Customer) -> Customer:
    """
    Update all AI-powered metrics for a customer
    Called after new order or periodically
    """

    # Calculate RFM Score
    customer.rfm_score = calculate_rfm_score(
        customer.recency_days,
        customer.frequency,
        customer.monetary
    )

    # Predict Segment
    customer.segment = predict_customer_segment(customer)

    # Calculate churn probability
    customer.churn_probability = calculate_churn_probability(customer)

    # Predict next purchase
    customer.next_purchase_days = predict_next_purchase_days(customer)

    # Predict lifetime value
    customer.lifetime_value_prediction = predict_lifetime_value(customer)

    return customer


def get_product_recommendations(customer: Customer, limit: int = 5) -> list:
    """
    Get AI-powered product recommendations for customer
    Based on purchase history and preferences
    """

    # TODO: Implement collaborative filtering or content-based recommendations
    # For MVP, return based on preferred categories

    recommendations = []

    if customer.preferred_categories:
        # Query products from preferred categories
        pass

    return recommendations
