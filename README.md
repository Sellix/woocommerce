# WooCommerce Plugin

![](https://img.shields.io/badge/Sellix-WooCommerce-black) ![](https://img.shields.io/badge/Version-v2.0.0-black)

<p align="center">
  <img src="https://cdn.sellix.io/static/previews/woocommerce.jpeg" alt="Sellix Logo"/>
</p>

WooCommerce plugin to use Sellix as a Payment Gateway.

# Standard Installation

0. Visit our WooCommerce plugin page at [wordpress.org/plugins/sellix-pay](https://wordpress.org/plugins/sellix-pay/), click the Download button and you're done!

# Manual Installation

0. Download the latest release ZIP [on GitHub](https://github.com/Sellix/woocommerce/releases).

1. Upload the ZIP to your WooCommerce dashboard. ([take a look at this guide](https://docs.presscustomizr.com/article/318-how-to-upload-a-wordpress-plugin-from-your-wordpress-admin-dashboard))

2. Go to the payment tab and enable Sellix as a payment gateway.

3. Fill the API details in the payment settings as well as the enabled Gateways (PayPal, Bitcoin, Litecoin..).

4. Dark mode. If you would like to use a dark version of our WooCommerce plugin, please download and use the `dark-sellix.css` file included in this repository.

== Changelog ==

= 1.9.7 =
* Jan 03, 2025
* Applied various security fixes
* Applied Plugin Check Recemmendations
* Plugin tested on WooCommerce 9.5.1
* Plugin tested on WordPress 6.7.1

= 1.9.6 =
* Jan 04, 2024
* Now it is compatible to High-Performance Order Storage
* Plugin tested on WooCommerce 8.4.0
* Plugin tested on WordPress 6.4.5

= 1.9.5 =
* Added Origin Param
* Logo Changed

= 1.9.4 =
* Now it is compatible to Blocks(React) based checkout

= 1.9.3 =
* Added a new option: If merchant have more than one shop under their Sellix account, send API requests with their authorization by passing the X-Sellix-Merchant header to each request.
* Plugin tested on WordPress 6.2.1
* Plugin tested on WooCommerce 7.5.1

= 1.9.2 =
* Fixed logo image missing

= 1.9 =
* Removed payment gateway selection.
* Removed confirmations settings
* Updated logo dimensions.

= 1.8 =
* Added a new option: merchant can enable their own branded sellix pay checked url to their customers.

= 1.7 =
* Bug fixed when getting the gateway redirect url
* And updated perfectmoney gateway value

= 1.0 =
* Initial release.