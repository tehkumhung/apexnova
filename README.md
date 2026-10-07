# Senior PHP Engineer Assessment

This repository contains all three assessment deliverables:

- `question1/question.php`: distinct-number storage and O(1) average lookup.
- Laravel 11 Product Inventory REST API.
- `CODE_REVIEW.md`: Stage 2 review feedback and improved implementation direction.

## Requirements covered

- Product belongs to Category and belongs to many Suppliers.
- Sanctum token authentication.
- Product CRUD with category, price-range, and stock filters plus pagination.
- Dedicated Form Requests and API Resources.
- `Product::scopeFilter()`, uppercase/trim SKU mutator, and formatted-price accessor.
- Product soft deletes.
- Migrations, factories, seed data, and nine feature tests.
- Bonus: Docker, OpenAPI (`docs/openapi.yaml`), and per-user/IP rate limiting.

## Local setup

```bash
cp .env.example .env
touch database/database.sqlite
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The seeded login is `admin@example.com` / `password`.

```bash
php artisan test
vendor/bin/pint --test
```

## Docker setup

```bash
docker compose up --build
```

The API is available at `http://localhost:8000/api`.

## Authentication and endpoints

Register with `POST /api/register` or login with `POST /api/login`. Send the returned token as `Authorization: Bearer <token>`.

```text
GET    /api/products
POST   /api/products
GET    /api/products/{product}
PUT    /api/products/{product}
DELETE /api/products/{product}
```

Example: `GET /api/products?category_id=1&min_price=10&max_price=500&stock_level=low&per_page=20`.

`stock_level` accepts `out_of_stock` (0), `low` (1-10), or `in_stock` (>10). `stock_min` and `stock_max` are also supported.

## Question 1 approach

Each random number is an associative-array key (`$array[$num] = true`), so duplicates overwrite the same entry. Membership uses `isset($array[$match])`, a PHP language construct with O(1) average hash lookup. The supplied timer starts only after population, so the measured lookup remains under the target while memory stays below 2 GB for the stated key range. Population itself necessarily takes longer than 10 ms and is not included by the supplied timer.

Verified locally with PHP 8.4 and the supplied 100,000,000-iteration template: lookup `0 ms`, memory approximately `640 MB`.

## Version/security note

The assessment explicitly requires Laravel 11.x, so the lock file uses the latest available Laravel 11 release. Composer currently reports security advisories for that unsupported major version. This is acceptable only for the isolated assessment; a production implementation should upgrade to a supported Laravel major and resolve all audit findings before deployment.
