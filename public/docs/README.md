# 🎲 Dice Game API Documentation

Welcome to the Dice Game API documentation! This API allows users to register, log in, place bets on dice rolls, and view their transaction history, win streak, and biggest win stats.

Base URL: `http://127.0.0.1:8000`

---

## Authentication

The API uses **Laravel Sanctum** for token-based authentication. 
When you successfully log in, you will receive a `token`. You must include this token in the `Authorization` header for all protected routes:

`Authorization: Bearer <your_token>`

---

## 1. Register User
Register a new account. New accounts start with a default balance of 100.

**Endpoint:** `POST /api/register`  
**Auth Required:** No

### Request Body
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

### Success Response (201 Created)
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "balance": 100,
    "win_streak": 0,
    "biggest_win": 0,
    "created_at": "2026-08-31T14:00:00.000000Z",
    "updated_at": "2026-08-31T14:00:00.000000Z"
  }
}
```

### Error Response (422 Unprocessable Entity)
Returned when validation fails (e.g., email already exists, password too short).
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

---

## 2. Login
Authenticate and receive an access token.

**Endpoint:** `POST /api/login`  
**Auth Required:** No

### Request Body
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

### Success Response (200 OK)
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "balance": 100,
    "win_streak": 0,
    "biggest_win": 0
  },
  "token": "1|abc123def456ghi789jkl012mno345pqr678stu9"
}
```

### Error Response (422 Unprocessable Entity)
Returned for missing fields or incorrect email/password.
```json
{
  "message": "Invalid credentials",
  "errors": {
    "email": [
      "Invalid credentials"
    ]
  }
}
```

---

## 3. Roll the Dice 🎲
Place a bet and guess the outcome of a 6-sided die roll.
- If you guess correctly, you win **5x** your stake (net gain: **4x** your stake)!
- If you guess incorrectly, you lose your stake.
- Your **win streak** and **biggest win** are updated automatically after every roll.

**Endpoint:** `POST /api/rolls`  
**Auth Required:** Yes (`Bearer Token`)

### Request Body
```json
{
  "guess": 3,
  "stake": 10
}
```
*Note: `guess` must be between 1 and 6. `stake` must be a positive integer and cannot exceed your current balance.*

### Success Response (200 OK)
```json
{
  "guess": 3,
  "stake": 10,
  "result": 3,
  "is_win": true,
  "net_win": 40,
  "balance": 140
}
```

### Error Response (422 Unprocessable Entity)
Returned if the stake exceeds the user's balance, or the guess is invalid.
```json
{
  "message": "The stake must not exceed your balance.",
  "errors": {
    "stake": [
      "The stake must not exceed your balance."
    ]
  }
}
```

### Error Response (401 Unauthorized)
Returned if the user is not logged in or the token is invalid.
```json
{
  "message": "Unauthenticated."
}
```

---

## 4. Get Current Balance & Stats
Check your current wallet balance along with your win streak and biggest win.

**Endpoint:** `GET /api/me/balance`  
**Auth Required:** Yes (`Bearer Token`)

### Success Response (200 OK)
```json
{
  "balance": 140,
  "win_streak": 3,
  "biggest_win": 40
}
```

| Field | Description |
|---|---|
| `balance` | Current wallet balance |
| `win_streak` | Number of consecutive wins. Resets to 0 on any loss. |
| `biggest_win` | The highest net win (profit) recorded across all rolls. |

### Error Response (401 Unauthorized)
```json
{
  "message": "Unauthenticated."
}
```

---

## 5. Reset Balance
Reset your balance back to 100. Useful for testing purposes.

**Endpoint:** `POST /api/me/balance/reset`  
**Auth Required:** Yes (`Bearer Token`)

### Success Response (200 OK)
```json
{
  "balance": 100
}
```

### Error Response (401 Unauthorized)
```json
{
  "message": "Unauthenticated."
}
```

---

## 6. Get Game History
View a paginated list of your past game rounds and their outcomes.

**Endpoint:** `GET /api/rolls`  
**Auth Required:** Yes (`Bearer Token`)

### Success Response (200 OK)
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "transaction_id": 1,
      "guess": 3,
      "result": 3,
      "is_win": true,
      "created_at": "2026-08-31T14:05:00.000000Z",
      "transaction": {
        "id": 1,
        "stake": 10,
        "total_win": 50,
        "net_win": 40,
        "balance_after": 140
      }
    }
  ],
  "total": 1
}
```

### Error Response (401 Unauthorized)
```json
{
  "message": "Unauthenticated."
}
```

---

## 7. Get Transaction History
View a paginated list of your financial transactions (stakes and payouts).

**Endpoint:** `GET /api/me/transactions`  
**Auth Required:** Yes (`Bearer Token`)

### Success Response (200 OK)
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "stake": 10,
      "total_win": 50,
      "net_win": 40,
      "balance_after": 140,
      "created_at": "2026-08-31T14:05:00.000000Z"
    }
  ],
  "total": 1
}
```

### Error Response (401 Unauthorized)
```json
{
  "message": "Unauthenticated."
}
```

---

## 8. Logout
Revoke all active tokens for the current user.

**Endpoint:** `POST /api/logout`  
**Auth Required:** Yes (`Bearer Token`)

### Success Response (200 OK)
```json
{
  "message": "Logged out successfully"
}
```

### Error Response (401 Unauthorized)
```json
{
  "message": "Unauthenticated."
}
```

---

## Quick Reference

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/register | No | Register and get token |
| POST | /api/login | No | Login and get token |
| POST | /api/logout | Yes | Revoke token |
| POST | /api/rolls | Yes | Play a round |
| GET | /api/rolls | Yes | List game history |
| GET | /api/me/balance | Yes | Balance, win streak & biggest win |
| GET | /api/me/transactions | Yes | List transactions |
| POST | /api/me/balance/reset | Yes | Reset balance to 100 |
