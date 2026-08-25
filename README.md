# Dice Roll API

A simple single-player dice game API built with Laravel + Sanctum + SQLite.

## Setup

```bash
git clone <repo-url>
cd dice-roll-api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/register | No | Register and get token |
| POST | /api/login | No | Login and get token |
| POST | /api/logout | Yes | Revoke token |
| POST | /api/rolls | Yes | Play a round |
| GET | /api/rolls | Yes | List your game history |
| GET | /api/me/balance | Yes | Check your balance |
| GET | /api/me/transactions | Yes | List your transactions |

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

**Check balance:**
```bash
curl http://localhost:8000/api/me/balance \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Run Tests

```bash
php artisan test
```
