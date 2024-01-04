=== Sellix Pay ===
Contributors: sellix, hdhingra 
Tags: crypto,paypal, sellix-pay, payment-gateway,trust wallet, bitcoin, cryptocurrency, crypto wallet, walletconnect
Requires at least: 4.9
Tested up to: 6.4.2
WC tested up to: 8.4.0
Stable tag: trunk
Author URI: https://sellix.io/
Author:   Sellix io
Version: 1.9.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.4

== Description ==

Accept Cryptocurrencies, Credit Cards, PayPal and regional banking methods with Sellix Pay.

### What is Sellix? 

Sellix is a SaaS eCommerce platform, very similar to Shopify. We have over 250.000 merchants and process thousands of orders daily. Many of our clients asked us to provide them with a WooCommerce plugin for their business, and we're looking to do just so.

With Sellix Pay, customers can quickly checkout and complete orders with their preferred payment method, including cryptocurrencies.

Our plugin can be configured through the Sellix dashboard, with any of the 20+ gateways we offer, along with every security feature and addons we provide.

Additionally, you can fully integrate with our other services should your business require you to, without having to choose any other plugin.

Powered by our fraud prevention software Fraud Shield, Sellix ensures the stability and security of your products all day, all night. Stop blocking legitimate customers, and start blocking fraudulent customers.

Ditch platforms that fail to protect your integrity as a digital entrepreneur. We use world-class software to power our security apparatus as well as using protection that scales with you.

### How to use the plugin? 

Sellix Pay can be configured easily, the only input required is the Sellix.io API key.

Once set, any payment method can be enabled (if configured properly on the dashboard) and will be automatically displayed during checkout.
    
Customers will then be prompted to choose their payment method amongst the ones available, like shown in the below picture.

Once chosen, the customer will be redirected to the Sellix checkout page where the order takes place, as soon as the transaction is sent the order will then be marked as completed.

### Why Sellix? 

At its core, Sellix was developed to operate as the logistics operations center of your digital empire. You upload your products and we take care of it left and right, up and down, 24/7, enabling you to accept payments from all over the world with key gateways that power the world of money. Including PayPal, Stripe, CashApp, Bitcoin, Ethereum, and much more.

What differentiates us in the crypto world and from our competitors is our own infrastructure. We do not rely on third-party providers to handle your cryptos, detect transactions and send payouts.
Everything is managed by Sellix, we host one or more nodes for each coin (BTC, ETH, SOL etc.) and securely store wallets. You will not have to validate or connect any account such as Coinbase, you’re able to accept cryptos just by setting your addresses!

* Processed over $50M in fiat & crypto payments
* Entrepreneurs, creators, and developers have created over 280K products
* Saw over 3M successful individual orders

== Installation ==

1. Upload the folder `sellix-pay` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. To display the Sellix Pay Settings, go to WooCommerce > Settings > Payments tab.
4. That's it!


== Frequently Asked Questions ==

= What is Sellix? =
Sellix is the most powerful (and easiest) platform to sell digital goods online. Whether you want to sell software, custom services, subscriptions, or memberships, sell it with Sellix. You’ll get access to a host of products and tools to help you reach a million dollars.

= What payment methods does Sellix support? =
Sellix supports over 10+ different cryptocurrency payment methods. This includes the most popular ones such as Bitcoin, Ethereum, Bitcoin Cash, Litecoin, Solana, and more. We also support 5+ different fiat payment methods such as PayPal, Stripe, CashApp, and more. You can learn more about the payment methods Sellix supports **[here](https://help.sellix.io/en/articles/4499372-supported-payment-processors)**.

= How powerful is Sellix’s fraud prevention? =
Fraud Shield, a fraud prevention software by Sellix is rated 9/10 amongst our customers. We believe that in this scary digital world, there must be a barrier of protection. It’s your business, so it only makes sense to set your own rules. We’ll block payments and transactions at a certain threshold you set. You can also set custom blacklists or whitelists for certain customers on your storefront.

= Is Sellix safe and secure? =
Above all else, Sellix takes data security very seriously. That’s why your data (and your customers’ data) will always be safe with Sellix. All payment transactions are encrypted and hosted on globally-distributed servers that cannot be penetrated. We’ve taken considerable standards to regularly updating our product to guarantee that it’s in compliance with international security standards like GDPR, CCPA, and more.

= How can I switch to Sellix from another platform? =
It’s simple. Tap on the “Sign Up” button on the top and start transferring your products. Our product creation flow is as simple as turning your computer on (if you’re having trouble with creating a product, don’t worry. Our support team is here to help 24/7).


== Screenshots ==
1. Sellix Pay Payment Settings
2. Sellix Pay Payment Settings
3. Sellix Pay Payment Settings
4. Sellix Pay Payment Settings
5. Sellix Pay Payment Settings.
6. Sellix Pay Front Output Checkout Page.
7. Sellix Pay Application Dashboard.
8. Sellix Pay Direct Product Setting
9. Sellix Pay Payment Gateway Configure Settings.
10. Sellix Pay Crypto Currency Transactions.

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.7 =
- Bug fixed when getting the gateway redirect url
- And updated perfectmoney gateway value

= 1.8 =
- Added a new option: merchant can enable their own branded sellix pay checked url to their customers.

= 1.9 =
- Removed payment gateway selection.
- Removed confirmations settings
- Updated logo dimensions.

= 1.9.2 =
- Fixed logo image missing

= 1.9.3 =
- Added a new option: If merchant have more than one shop under their Sellix account, send API requests with their authorization by passing the X-Sellix-Merchant header to each request.
- Plugin tested on WordPress 6.2.1
- Plugin tested on WooCommerce 7.5.1

= 1.9.4 =
- Now it is compatible to Blocks(React) based checkout

= 1.9.5 =
- Added Origin Param
- Logo Changed