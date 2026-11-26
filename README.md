# Laravel Property Listing API

A REST API for property listings built with Laravel 13 and Sanctum token auth.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Seeded login: `test@example.com` / `password`.

Run tests: `php artisan test`

## Endpoints (prefix `/api/v1`)

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/register` | – | Create account, returns token |
| POST | `/login` | – | Returns token |
| POST | `/forgot-password` | – | Email a password reset link |
| POST | `/reset-password` | – | Set a new password (`token`, `email`, `password`, `password_confirmation`) |
| GET | `/email/verify/{id}/{hash}` | signed link | Verify email (link from the verification email) |
| POST | `/email/verification-notification` | ✓ | Resend the verification email |
| GET | `/me` | ✓ | Current user |
| POST | `/logout` | ✓ | Revoke current token |
| GET | `/properties` | – | List (paginated, filterable) |
| GET | `/properties/{id}` | – | Show |
| POST | `/properties` | ✓ | Create (you become owner) |
| PUT/PATCH | `/properties/{id}` | owner | Update |
| DELETE | `/properties/{id}` | owner | Delete (also removes its images) |
| GET | `/me/properties` | ✓ | Your own listings |
| POST | `/properties/{id}/images` | owner | Upload images (multipart `images[]`) |
| DELETE | `/properties/{id}/images/{imageId}` | owner | Delete an image |
| POST | `/properties/{id}/favorite` | ✓ | Save to favourites |
| DELETE | `/properties/{id}/favorite` | ✓ | Remove from favourites |
| GET | `/me/favorites` | ✓ | Your saved properties |

Send the token as `Authorization: Bearer <token>`. Tokens expire after `SANCTUM_EXPIRATION`
minutes (default 7 days); resetting a password revokes all of a user's tokens.

Creating, editing or deleting listings and images requires a verified email address
(unverified users get `403`). Sign-up sends the verification email automatically.
Password reset emails link to `FRONTEND_URL/reset-password?token=...&email=...`, so
your frontend should read those values and POST them to `/reset-password`.

Mail uses the `log` driver by default (see `storage/logs/laravel.log`); set the
`MAIL_*` variables in `.env` to send real email. Run `php artisan schedule:work`
(or a cron for `schedule:run`) to prune expired tokens daily.

### List filters

`search`, `type` (house, apartment, condo, land, commercial), `listing_type` (sale, rent),
`status` (available, pending, sold, rented), `city`, `min_price`, `max_price`,
`min_bedrooms`, `min_bathrooms`, `per_page` (max 100),
`sort` = `price | created_at | area_sqm | bedrooms` (prefix `-` for descending; default `-created_at`).

### Images

Up to 10 files per request and 20 per property; jpg, jpeg, png or webp, max 5 MB each.
Files are stored on the `public` disk and returned as URLs under `images` on each property.

Example: `GET /api/v1/properties?city=Makati&listing_type=rent&max_price=50000&sort=price`
