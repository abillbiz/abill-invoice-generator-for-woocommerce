=== ABill Invoice Generator for WooCommerce ===
Contributors: abillin
Tags: woocommerce invoices, pdf invoice, order invoice, print invoice, email invoice
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create, edit, email, print, and download professional PDF invoices for WooCommerce orders.

== Description ==

ABill Invoice Generator for WooCommerce provides a simple invoice workflow for WooCommerce store owners.

Current features:

* Create an invoice from a WooCommerce order.
* View and edit invoice details without changing the original order.
* Print a clean invoice.
* Download a formatted PDF invoice.
* Email the invoice using the WordPress and WooCommerce email system.
* Manage invoices from a dedicated WordPress admin screen.

The plugin is designed to store invoice records inside WordPress and does not require an ABill account.

Invoice and email templates can be customized from a theme by copying them into the `abill-invoices` folder inside the active theme.

Documentation and product information:
https://abill.in/invoice-generator-for-woocommerce/

== Installation ==

1. Install and activate WooCommerce.
2. Upload the `abill-invoice-generator-for-woocommerce` folder to `/wp-content/plugins/`, or install the plugin ZIP from WordPress admin.
3. Activate "ABill Invoice Generator for WooCommerce".
4. Open ABill Invoices in WordPress admin and configure your business details.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. WooCommerce is declared as a required plugin dependency.

= Does this plugin send store data to ABill? =

No. This plugin does not connect to ABill servers and does not require an ABill account.

= Does uninstalling remove invoices automatically? =

No. Business records are preserved by default. Data is removed only when the "Delete data on uninstall" setting is explicitly enabled.

== Changelog ==

= 1.0.0 =

* Added the plugin foundation, invoice engine, administration screens, assets, translations directory, and override-ready invoice and email templates.
