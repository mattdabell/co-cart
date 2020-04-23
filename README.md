# [CoCart Lite](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/)

[![Financial Contributors on Open Collective](https://opencollective.com/cocart/all/badge.svg?label=financial+contributors)](https://opencollective.com/cocart) [![WordPress Plugin Page](https://img.shields.io/badge/WordPress-%E2%86%92-lightgrey.svg?style=flat-square)](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/)
[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/cart-rest-api-for-woocommerce.svg?style=flat)](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/)
[![WordPress Tested Up To](https://img.shields.io/wordpress/v/cart-rest-api-for-woocommerce.svg?style=flat)](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/cart-rest-api-for-woocommerce.svg)](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/#reviews)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/cart-rest-api-for-woocommerce.svg)](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/)
[![License](https://img.shields.io/badge/license-GPL--3.0%2B-red.svg)](https://github.com/co-cart/co-cart/blob/master/LICENSE.md)

**Contributors:** sebd86, cocartforwc  
**Donate link:** https://opencollective.com/cocart  
**Tags:** woocommerce, cart, rest, rest-api, JSON  
**Requires at least:** 5.0  
**Requires PHP:** 7.0  
**Tested up to:** 5.4  
**WC requires at least:** 3.6.0  
**WC tested up to:** 4.1.0  
**Stable tag:** 2.0.13  
**License:** GPL v2 or later  

## 🔔 Overview

A REST API designed to handle the frontend of your WooCommerce store. Control and manage the shopping cart in any framework of your choosing. Powerful and developer friendly, ready to build your headless store.

## Why should I use CoCart?

Well, even though the WooCommerce REST API is created with developers in mind, it’s only designed for controlling the backend of the store.

Most developers have to fork out further custom development to enable the frontend to meet their client’s specifications and that can be costly. CoCart provides that missing component to bridge 🌉 the gap between your WooCommerce store and the app your building by enabling the features of the frontend at a fraction of the cost.

If you are wanting to build a headless WooCommerce store then CoCart is your solution.

## Features

* **NEW**: Guest carts are now fully supported.
* Add simple and variable products to the cart (including simple and variable subscription products).
* Update items in the cart.
* Remove items from the cart.
* Restore items to the cart.
* Calculate the totals.
* Retrieve the cart totals.
* View the cart contents.
* Retrieve the item count.
* Empty the cart.
* Supports [authentication via WooCommerce's method](https://cocart.xyz/authenticating-with-woocommerce-heres-how-you-can-do-it/).
* Supports basic authentication without the need to cookie authenticate.

Included with these features are **[filters](https://docs.cocart.xyz/#filters)** and **[action hooks](https://docs.cocart.xyz/#hooks)** for developers.

* **[CoCart Product Support Boilerplate](https://github.com/co-cart/cocart-product-support-boilerplate)** provides a basic boilerplate for supporting a different product type to add to the cart with validation including adding your own parameters.
* **[CoCart Tools](https://github.com/co-cart/cocart-tools)** provides tools to help with development testing with CoCart.
* **[CoCart Tweaks](https://github.com/co-cart/co-cart-tweaks)** provides a starting point for developers to tweak CoCart to their needs.

This plugin is just the tip of the iceberg. CoCart Pro completes it with the following [features](https://cocart.xyz/features/?utm_medium=github.com&utm_source=github&utm_campaign=readme&utm_content=cocart):

* Add and Remove Coupons to Cart
* Retrieve Applied Coupons
* Retrieve Coupon Discount Total
* Retrieve Cart Total Weight
* Retrieve Cross Sells
* Retrieve and Set Payment Method
* Retrieve and Set Shipping Methods
* Retrieve and Set Fees
* Calculate Shipping Fees
* Calculate Totals and Fees
* **NEW** Retrieve Checkout Fields (In Development)
* **NEW** Create Order (In Development)

[Buy CoCart Pro Now](https://cocart.xyz/pricing/?utm_medium=github.com&utm_source=github&utm_campaign=readme&utm_content=cocart)

### Add-ons to further enhance your cart.

We also have **[add-ons](https://cocart.xyz/add-ons/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart)** that extend CoCart to enhance your development and your customers shopping experience.

* **[CoCart - Get Cart Enhanced](https://wordpress.org/plugins/cocart-get-cart-enhanced/)** enhances the cart response returned with the cart totals, coupons applied, additional product details and more. - **FREE**
* **[CoCart Products](https://cocart.xyz/add-ons/products/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart)** provides a public version of WooCommerce REST API for accessing products, categories, tags, attributes and even reviews without the need to authenticate.
* **[CoCart Yoast SEO](https://cocart.xyz/add-ons/yoast-seo/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart)** extends CoCart Products add-on by returning Yoast SEO data for products, product categories and product tags. - **REQUIRES COCART PRODUCTS**
* and more add-ons in development.

## 📘 Guide

### 📖 Documentation

[View documentation for CoCart](https://docs.cocart.xyz/). If you are interested to share how to use CoCart in other languages, please follow the [contributing guidelines in the documentation repository](https://github.com/co-cart/co-cart-docs/blob/master/CONTRIBUTING.md).

#### 💽 Installation

##### Manual

1. Download the [latest version](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/).
2. Go to **WordPress Admin > Plugins > Add New**.
3. Click **Upload Plugin** at the top.
4. **Choose File** and select the `.zip` file you downloaded in **Step 1**.
5. Click **Install Now** and **Activate** the plugin.

##### Automatic

1. Go to **WordPress Admin > Plugins > Add New**.
2. Search for **CoCart**
3. Click **Install Now** on the plugin and **Activate** the plugin.

### Usage

To view the cart endpoint, go to `yourdomainname.xyz/wp-json/cocart/v1/get-cart/`

See [documentation](#-documentation) on how to use all endpoints.

## ⭐ Support

CoCart is released freely and openly. Feedback or ideas and approaches to solving limitations in CoCart is greatly appreciated.

CoCart is not supported via the [WooCommerce Helpdesk](https://woocommerce.com/). As the plugin is not sold via WooCommerce.com, the support team at WooCommerce.com is not familiar with it and may not be able to assist.

If you are in need of support, you can get it with a [purchase of CoCart Pro](https://cocart.xyz/pricing/?utm_medium=github.com&utm_source=github&utm_campaign=readme&utm_content=cocart).

### 📝 Reporting Issues

If you think you have found a bug in the plugin, a problem with the documentation, please [open a new issue](https://github.com/co-cart/co-cart/issues/new) and I will do my best to help you out.

## Contribute

If you or your company use CoCart or appreciate the work I’m doing in open source, please consider donating on [the open collective](https://opencollective.com/cocart) or [purchasing CoCart Pro](https://cocart.xyz/pricing/?utm_medium=github.com&utm_source=github&utm_campaign=readme&utm_content=cocart) where you not just get the full cart experience but also support me directly so I can continue maintaining CoCart and keep evolving the project.

Please also consider starring ✨ and sharing 👍 the project repo! This helps the project getting known and grow with the community. 🙏

Thank you for your support! 🙌

---

## Contributors

### Code Contributors

This project exists thanks to all the people who contribute. [[Contribute](CONTRIBUTING.md)].
<a href="https://github.com/co-cart/co-cart/graphs/contributors"><img src="https://opencollective.com/cocart/contributors.svg?width=890&button=false" /></a>

### Financial Contributors

Become a financial contributor and help us sustain our community. [[Contribute](https://opencollective.com/cocart/contribute)]

#### Individuals

<a href="https://opencollective.com/cocart"><img src="https://opencollective.com/cocart/individuals.svg?width=890"></a>

#### Organizations

Support this project with your organization. Your logo will show up here with a link to your website. [[Contribute](https://opencollective.com/cocart/contribute)]

<a href="https://opencollective.com/cocart/organization/0/website"><img src="https://opencollective.com/cocart/organization/0/avatar.svg"></a>
<a href="https://opencollective.com/cocart/organization/1/website"><img src="https://opencollective.com/cocart/organization/1/avatar.svg"></a>


##### License

CoCart is released under [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl-3.0.html).

##### Credits

CoCart is developed and maintained by [Sébastien Dumont](https://github.com/seb86).

---

<p align="center">
    <img src="https://raw.githubusercontent.com/seb86/my-open-source-readme-template/master/a-sebastien-dumont-production.png" width="353">
</p>
