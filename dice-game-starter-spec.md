# Dice Roll API — Starter Training Project

A simple single-player Laravel API. No opponents, no turns between players — just a user rolling dice against a target or against the "house," with results logged.

---

## 1. Objective

The smallest possible project that still covers the fundamentals:

- Routing, controllers, Eloquent models, migrations
- Request validation
- Basic authentication (Sanctum)
- A simple `belongsTo` relationship (transaction/game round → user, game round → transaction)
- Returning clean JSON responses
- Trivial, self-contained business logic (rolling dice, checking a win condition) — no state machines, no turn order, no second player

---

## 2. Tech Stack

- Laravel (latest LTS or current stable)
- MySQL, PostgreSQL, or SQLite (fine for a project this size)
- Laravel Sanctum (basic token auth)
- PHPUnit or Pest (a handful of tests)

---

## 3. Game Concept

Pick one simple rule set — easiest is:

**"Guess & Roll":**
   
1. User picks a number to bet on (1–6) and a wager amount (virtual currency/points).
2. User rolls one die (server generates the random result, never trust client input for the roll itself).
3. If the die matches their guess, they win a payout (e.g. 5x wager). Otherwise they lose the wager.
4. User has a `balance` that goes up/down with each roll.

---

## 4. Entities

| Entity          | Relationship                                                    |
| --------------- | --------------------------------------------------------------- |
| **User**        | has many Transactions, has many GameRounds                      |
| **Transaction** | belongs to User, has one GameRound (the round it was staked on) |
| **GameRound**   | belongs to User, belongs to Transaction                         |

Three tables total (plus Laravel's default `users`).

This splits the "money" concern from the "gameplay" concern — a common real-world pattern in betting/gaming systems, where transaction records need to be reliable and auditable on their own, separate from the game logic that produced them.

---

## 5. Endpoints

### Auth

- `POST /api/register` — name, email, password (starting balance defaults to e.g. 100)
- `POST /api/login` — returns Sanctum token
- `POST /api/logout`

### Game     

- `POST /api/rolls` — play a round: `{ "guess": 4, "stake": 10 }`
  - Validates stake ≤ current balance
  - Server rolls the die (1–6, `random_int`)
  - Compares to guess, computes win/loss
  - Creates a `Transaction` record (stake, total_win, net_win) and a `GameRound` record (guess, result)
  - Returns the roll result, win/loss, and updated balance
- `GET /api/rolls` — list the logged-in user's game rounds (paginated), each with its transaction details
- `GET /api/me/balance` — quick check of current balance
- `GET /api/me/transactions` — list the logged-in user's transaction history (paginated)

That's the whole API. 3 auth endpoints + 4 game endpoints.

---

## 6. Game Logic

1. Validate `guess` is an integer 1–6, `stake` is a positive integer ≤ user's current balance.
2. Server generates the roll: `random_int(1, 6)` — **never accept a roll value from the client.**
3. If `guess === result`: `total_win = stake * 5`, `net_win = total_win - stake` (positive). Else: `total_win = 0`, `net_win = -stake` (negative).
4. Apply `net_win` to the user's balance.
5. Save the `Transaction` (stake, total_win, net_win, balance_after) and the linked `GameRound` (guess, result, is_win)
6. Return the updated balance so the client doesn't need a second request.

---

## 7. Database Schema

### 7.1 `users` _(default table + one added column)_

| Column                            | Type    | Constraints           |
| --------------------------------- | ------- | --------------------- |
| ...all default Laravel columns... |         |                       |
| balance                           | integer | not null, default 100 |

### 7.2 `transactions`

Records the stake and payout for a single play — the "money" side of a round.

| Column                  | Type            | Constraints                                                            |
| ----------------------- | --------------- | ---------------------------------------------------------------------- |
| id                      | bigint unsigned | PK                                                                     |
| user_id                 | bigint unsigned | FK → `users.id`, not null, on delete cascade                           |
| stake                   | integer         | not null — amount wagered                                              |
| total_win               | integer         | not null, default 0 — gross payout if won, 0 if lost                   |
| net_win                 | integer         | not null — `total_win - stake` (negative on a loss, positive on a win) |
| balance_after           | integer         | not null — user's balance immediately after this transaction           |
| created_at / updated_at | timestamp       |                                                                        |

**Indexes:** `user_id`

### 7.3 `game_rounds`

Records the gameplay outcome — the "game" side of a round.

| Column                  | Type             | Constraints                                         |
| ----------------------- | ---------------- | --------------------------------------------------- |
| id                      | bigint unsigned  | PK                                                  |
| user_id                 | bigint unsigned  | FK → `users.id`, not null, on delete cascade        |
| transaction_id          | bigint unsigned  | FK → `transactions.id`, not null, on delete cascade |
| guess                   | tinyint unsigned | not null (1–6) — the player's bet/guess             |
| result                  | tinyint unsigned | not null (1–6) — the rolled die value               |
| is_win                  | boolean          | not null                                            |
| created_at / updated_at | timestamp        |                                                     |

**Indexes:** `user_id`, `transaction_id`

### Migration order

1. `users` (default, plus a migration adding `balance`)
2. `personal_access_tokens` (Sanctum)
3. `transactions`
4. `game_rounds`

---

## 8. Build Order

1. **Auth** — register (with default balance), login, logout (Sanctum)
2. **Roll endpoint — happy path** — accept guess/stake, roll randomly, save a `Transaction` + `GameRound` pair, return the result (skip balance math at first, just get both records saving correctly together)
3. **Balance logic** — apply win/loss to balance, prevent staking more than current balance (`422` if so)
4. **History endpoints** — `GET /api/rolls` (game rounds) and `GET /api/me/transactions`, both paginated
5. **Tests** — mock/seed random results if needed to assert win and loss paths both create correct records and update balance correctly; assert over-staking is rejected; assert a `Transaction` and its `GameRound` are always created together (never one without the other)

Five steps. Every step is independently demoable.

---

## 9. Deliverables

- Laravel project
- `README.md` with setup steps
- A few passing tests (`php artisan test`) 
- `curl` or Postman examples showing register → roll → check history

---

## 10. Optional Extras (only if they finish early)

- `POST /api/me/balance/reset` — reset balance back to 100 for testing convenience
- Track and expose a "current win streak" or "biggest win" stat
- Support rolling multiple dice at once (e.g. 2d6) instead of just one
- Add a daily free balance top-up (light intro to scheduled tasks / `php artisan schedule`)
