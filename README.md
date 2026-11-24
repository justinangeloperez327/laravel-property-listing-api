# Laravel Property Listing API

A REST API for property listings built with Laravel 13 and Sanctum token auth.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Seeded login: `test@example.com` / `password`.

Run tests: `php artisan test`

## Endpoints (prefix `/api/v1`)

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/register` | – | Create account, returns token |
| POST | `/login` | – | Returns token |
| GET | `/me` | ✓ | Current user |
| POST | `/logout` | ✓ | Revoke current token |
| GET | `/properties` | – | List (paginated, filterable) |
| GET | `/properties/{id}` | – | Show |
| POST | `/properties` | ✓ | Create (you become owner) |
| PUT/PATCH | `/properties/{id}` | owner | Update |
| DELETE | `/properties/{id}` | owner | Delete |

Send the token as `Authorization: Bearer <token>`.

### List filters

`search`, `type` (house, apartment, condo, land, commercial), `listing_type` (sale, rent),
`status` (available, pending, sold, rented), `city`, `min_price`, `max_price`,
`min_bedrooms`, `min_bathrooms`, `per_page` (max 100),
`sort` = `price | created_at | area_sqm | bedrooms` (prefix `-` for descending; default `-created_at`).

Example: `GET /api/v1/properties?city=Makati&listing_type=rent&max_price=50000&sort=price`
