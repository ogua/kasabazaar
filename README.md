# KASAROSE storefront (`kmarket`)

KASAROSE is Ghana's multi-vendor ecommerce marketplace and the parent company of the **KASAROSE Group of
Companies** (sister companies: KASAROSE LOGISTICS and Neoride Africa).

This repository is the customer-facing storefront and the vendor self-service portal:

- **Storefront** — Laravel 12 + Livewire + Tailwind CSS v4 (`resources/views/storefront`, `resources/views/livewire/storefront`).
- **Vendor portal** — Filament 4 panel at `/vendor-portal`.
- **Data** — products, carts, orders and vendors come from a backend commerce API over REST
  (`App\Services\Kasabazaar\*`, base URL `KASABAZAAR_API_URL`). There is no local product or order schema.

## Getting started

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
composer run dev          # server + queue + logs + Vite
```

Product, cart and checkout pages need the backend API reachable at `KASABAZAAR_API_URL`. Static pages (About,
Our Group, FAQ, Contact, legal) render without it.

## Everyday commands

```bash
npm run build             # production assets (required after CSS/token changes)
php artisan brand:build   # regenerate logos, favicons and the OG card from resources/brand/
php artisan test --compact
vendor/bin/pint --dirty
```

## Where things live

| What | Where |
|---|---|
| Company identity, group roster, contacts, delivery timeframes, return windows | `config/group.php` |
| Design system (tokens, layout rules, changelog) | `DESIGN.md` |
| Agent / contributor guidance, cross-repo rules | `CLAUDE.md` |
| Legal pages | `resources/views/storefront/pages/legal/` |
| Brand source art (not web-served) | `resources/brand/` |

Before launch, confirm the values in the **CONFIRM BEFORE LAUNCH** block of `.env.example` — they are published
verbatim on the legal pages.
