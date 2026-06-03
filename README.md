# WebHotelier for WordPress

A complete WordPress integration for the [WebHotelier](https://www.webhotelier.net/) Integration REST API. It wraps **every documented endpoint** in a typed PHP client, ships a full admin panel (settings, management dashboards, and a live API explorer), and renders a complete booking flow on the front end through **26 theme-overridable shortcodes**.

The frontend handles search, availability and room/rate selection natively, then hands off to WebHotelier's secure hosted booking engine for the guest's details and payment — so no card data ever touches your server (PCI-safe by design).

## Features

- **Full API client** — a typed wrapper over all 34 WebHotelier REST endpoints (property, rooms, rates, extras, availability, calendars, BAR, offers, bookings, vouchers, statistics, booking sources), with HTTP Basic auth, transient caching, locale-aware requests, and uniform error handling.
- **Admin panel**
  - Settings with a live **Test Connection** button (single- or multi-property mode).
  - **Bookings** — search, view, cancel, resend confirmation, mark-synced.
  - **Statistics** — performance summary / per-day / per-country with inline charts.
  - **Vouchers** — bundles, codes, and code management.
  - **Sync** — pending bookings, sources viewer, sync tools.
  - **API Explorer** — call any endpoint from a registry-driven form and inspect the raw response.
- **Front-end shortcodes** — search widgets, availability with a "Book" CTA, property/room content, photo galleries, price calendars, special offers, vouchers, a multi-step booking flow, and guest self-service ("find my reservation").
- **Security** — credentials live server-side only; all live front-end calls go through a nonce-protected REST proxy; inputs are sanitized and output escaped; the booking proxy rejects card fields and restricts payment methods.

## Requirements

- WordPress 6.0+
- PHP 7.4+ (tested on PHP 8.4)
- A WebHotelier API account (username + password) and a property code

## Installation

1. Copy this directory into `wp-content/plugins/` (e.g. as `wp-content/plugins/wp-hotelier/`).
2. Activate **WebHotelier for WordPress** from the Plugins screen.
3. Go to **WebHotelier → Settings**, enter your API username, password and property code, choose single- or multi-property mode, and click **Test Connection**.

> Runtime has **no Composer or build dependencies** — the `composer.json` is for development/testing only.

## Shortcodes

**Search & flow:** `[wh_search_form]`, `[wh_quick_search]`, `[wh_booking_flow]`

**Availability:** `[wh_availability]`, `[wh_search_results]`, `[wh_map]`, `[wh_calendar]`, `[wh_flex_calendar]`, `[wh_bar]`, `[wh_price_from]`

**Content:** `[wh_property]`, `[wh_property_terms]`, `[wh_rooms]`, `[wh_room]`, `[wh_gallery]`, `[wh_facilities]`, `[wh_rates]`, `[wh_rate]`, `[wh_extras]`

**Offers & vouchers:** `[wh_offers]`, `[wh_offer]`, `[wh_voucher_form]`

**Booking & post-booking:** `[wh_book_button]`, `[wh_booking_engine]`, `[wh_booking_lookup]`, `[wh_my_bookings]`

Each shortcode accepts an optional `property=""` attribute (defaults to the configured property in single-property mode) and renders through templates that can be overridden from your theme under `your-theme/webhotelier/`.

## Booking flow

`[wh_booking_flow]` is a query-param state machine: **search → results → review → complete → confirmation**. The default "complete" step redirects to the rate's secure hosted booking-engine URL (PCI-safe). An advanced setting allows a fully native, non-card booking (pay-on-arrival / bank transfer) created directly through the API.

## Development

```bash
composer install
./vendor/bin/phpunit
```

The test suite uses PHPUnit with Brain Monkey + Mockery and a pluggable HTTP transport, so no network is touched. The client and all resource classes, the error/cache/handoff layers, the admin controllers, the REST proxy, every shortcode, and the booking-flow state machine are covered.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
