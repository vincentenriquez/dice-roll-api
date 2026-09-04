# Dice Roll API

A simple single-player dice game API built with Laravel + Sanctum + SQLite.

## Setup

```bash
git clone <repo-url>
cd dice-roll-api

# Install dependencies
composer install

# Setup environment variables
cp .env.example .env
php artisan key:generate

# Setup the database
touch database/database.sqlite
php artisan migrate --seed

# Start the server
php artisan serve
```

> **📚 API Documentation:** Once the server is running, visit [http://127.0.0.1:8000/docs/](http://127.0.0.1:8000/docs/) to view the full interactive API documentation.

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/register | No | Register and get token |
| POST | /api/login | No | Login and get token |
| POST | /api/logout | Yes | Revoke token |
| POST | /api/rolls | Yes | Play a round |
| GET | /api/rolls | Yes | List your game history |
| GET | /api/me/balance | Yes | Check balance, win streak & biggest win |
| GET | /api/me/transactions | Yes | List your transactions |
| POST | /api/me/balance/reset | Yes | Reset balance to 100 (for testing) |

## curl Examples

**Register:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Vincent","email":"v@test.com","password":"password123"}'
```

**Roll:**
```bash
curl -X POST http://localhost:8000/api/rolls \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"guess":4,"stake":10}'
```

**Check balance & stats:**
```bash
curl http://localhost:8000/api/me/balance \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Reset balance:**
```bash
curl -X POST http://localhost:8000/api/me/balance/reset \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Architecture

The project follows **Laravel Clean Code** principles:

```
Route → FormRequest (validation) → Controller → Service → Model
```

| Layer | Files |
|---|---|
| Controllers | `AuthController`, `RollController`, `BalTranController` |
| Services | `AuthService`, `RollService`, `BalTranService` |
| FormRequests | `RegisterRequest`, `LoginRequest`, `RollRequest` |
| Models | `User`, `GameRound`, `Transaction` |

## Game Rules

- Guess a number between **1 and 6**
- Stake any amount up to your current balance
- **Win:** Guess correctly → receive **5× your stake** (net gain: 4× stake)
- **Lose:** Guess wrong → lose your stake
- Win streak and biggest win are tracked automatically

## Run Tests

```bash
php artisan test
```
