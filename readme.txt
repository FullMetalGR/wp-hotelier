=== WP Hotelier ===
Contributors: adssolutions
Tags: hotel, booking, reservations, webhotelier, availability
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Wraps the entire WebHotelier Integration REST API: settings, admin dashboards, an API explorer, and a full frontend booking flow via shortcodes.

== Description ==

WP Hotelier is a single, self-contained plugin that integrates the WebHotelier (reserve-online.net) Integration REST API into WordPress.

* Server-side API client wrapping every documented endpoint (property, availability, offers, bookings, vouchers, statistics) with Basic Auth, caching, i18n, and uniform error handling.
* Credentials never leave the server: the browser talks only to a nonce-protected proxy.
* Single- and multi-property support.
* PCI-safe booking completion via the hosted booking engine (default), with an optional native non-card path.

This release (core) provides the plugin bootstrap, settings model, and the fully unit-tested API client. Admin screens and shortcodes ship in subsequent releases.

== Installation ==

1. Upload the `webhotelier` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to the WebHotelier settings screen and enter your API username and password.

== Frequently Asked Questions ==

= Does the browser ever see my API credentials? =

No. All credentialed calls run server-side behind a nonce-protected proxy.

= Are credit card details handled by this plugin? =

No. Booking completion is handed off to WebHotelier's secure hosted booking engine. The optional native path is restricted to non-card payment methods.

== Changelog ==

= 1.0.0 =
* Initial core release: bootstrap, settings model, and fully unit-tested API client for all endpoints.
