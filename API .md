# RESTful API Documentation

This document describes how to integrate the **Digital Credit Ledger** with your external applications (Mobile, POS, or ERP systems) using its RESTful API.

## 🛡️ Authentication (Sanctum)

The API is secured using **Laravel Sanctum**. Every request must include a secret "Bearer Token" in the header.

### 1. Generate an API Token
Your external system must first authenticate to get a token.
- **Endpoint**: `POST /api/tokens/create`
- **Payload**:
  ```json
  {
    "email": "admin@example.com",
    "password": "your-password",
    "device_name": "My_External_System"
  }
  ```
- **Response**: `{"token": "1|Abc123..."}`

### 2. Use the Token
For all subsequent requests, include the token in the `Authorization` header:
`Authorization: Bearer 1|Abc123...`

---

## 📡 Endpoints

### Get Customer Financial Health
Retrieve a customer's real-time balance and trust score.
- **Method**: `GET`
- **Endpoint**: `/api/customers/{id}`
- **Success Response (200 OK)**:
  ```json
  {
    "id": 1,
    "name": "John Doe",
    "phone": "255712345678",
    "current_balance": 5000,
    "trust_score": 85,
    "active_credits_count": 2
  }
  ```

### Issue Credit (Remote)
Record a new credit transaction from your external system.
- **Method**: `POST`
- **Endpoint**: `/api/credits/issue`
- **Payload**:
  ```json
  {
    "customer_id": 1,
    "amount": 2500,
    "due_date": "2026-03-01",
    "type": "item",
    "description": "3 bags of rice"
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "message": "Credit issued successfully via API.",
    "credit_id": 14,
    "new_balance": 7500
  }
  ```

---

## 🚀 Performance & Security
- **JSON Driven**: Optimized for fast data exchange.
- **Internal Verification**: Protected by a robust test suite (`ApiIntegrationTest.php`).
- **Rate Limited**: Throttling enabled to prevent brute-force attacks.

> [!TIP]
> Save the generated API token in your main system's environment variables (`.env`) for persistent authenticated access.
