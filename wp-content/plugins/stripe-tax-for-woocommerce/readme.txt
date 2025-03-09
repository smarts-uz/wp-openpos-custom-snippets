=== Stripe Tax - Sales tax automation for WooCommerce ===
Tags: stripe, tax, taxation, shipping
Stable tag: 1.1.9
Tested up to: 6.7
Requires at least: 6.3
Requires PHP: 7.4
License: Expat

Stripe Tax for WooCommerce allows you to easily calculate and collect sales tax, VAT, and GST on WooCommerce orders.

== Description ==

https://www.youtube.com/embed/wefsKltlSsQ?si=acCn_9odo65Tlyj7

Use Stripe Tax for WooCommerce to enable automatic sales tax calculation and apply the accurate tax rate for all of the transactions of your online store. Learn more how Stripe calculates [sales tax, VAT, and GST](https://docs.stripe.com/tax/calculating).

-   Know where to register: Understand where you need to collect taxes so that you can activate tax collection in over 50 countries and all US states. Stripe Tax for WooCommerce supports both country and VAT OSS registrations in Europe, and country and provincial registrations in Canada.
    
-   Automate tax collection: Calculate and collect the correct amount of tax. Stripe Tax for WooCommerce supports sales tax collection on hundreds of products and services, and maintains updated tax rules and rates.
    
-   Reconcile transactions: Stripe Tax for WooCommerce summarizes reports to match formatting requirements for each filing location so you can file and remit taxes on your own, with your accountant, or with a preferred partner.
    

Stripe Tax pricing is usage-based. For Stripe Tax for WooCommerce pricing, see the [API integration with Stripe Tax](https://support.stripe.com/questions/understanding-stripe-tax-pricing).

Follow along below to learn how to install this connector and how to configure it.

----------

## 1. Install the Stripe Tax for WooCommerce

Install this plugin and make sure it’s activated.

## 2. Connect your Stripe account

On your website’s dashboard, navigate to WooCommerce > Settings > WooCommerce.
<img src="https://woocommerce.com/wp-content/uploads/2024/07/Screenshot-2024-07-12-at-4.05.41%E2%80%AFPM.png" alt="WooCommerce Settings">

Then on the Stripe Tax tab, click Connect with Stripe to log in to your Stripe account or create a new one.

<img src="https://woocommerce.com/wp-content/uploads/2024/07/Screenshot-2024-07-12-at-4.07.46%E2%80%AFPM.png" alt="Connect with Stripe">

After successful authentication, navigate back to the Stripe Tax settings to continue with the configuration steps.

<img src="https://woocommerce.com/wp-content/uploads/2024/07/Screenshot-2024-07-12-at-4.14.50%E2%80%AFPM.png" alt="Connect with Stripe successful">

## 3. Configure your sales tax settings

If you previously configured these tax settings in the [Stripe Dashboard](https://dashboard.stripe.com/settings/tax), the values populate automatically in the Stripe Tax connector. You can edit the values in the connector and the changes automatically update in your Stripe Dashboard.

On the Stripe Tax tab, under Configure your sales tax settings, complete the following:

1.  Enter your head office address, which is your company’s legal address.
    
2.  Choose your default [product tax code](https://docs.stripe.com/tax/tax-codes), which allows Stripe to calculate the tax rate for categories of products.
    
3.  Click Save changes.
    

On the Tax tab, you can decide if the prices you set for your products are inclusive or exclusive of tax.

## 4. Manage tax registrations

Sales tax calculation isn’t applied [until you add registrations](https://docs.stripe.com/tax/zero-tax#situations-where-stripe-calculates-zero-tax) for jurisdictions where you need to collect tax. Our [monitoring tool](https://docs.stripe.com/tax/monitoring) can help you understand where you might be registered or need to register.

If you’re already registered in certain jurisdictions and want to start collecting tax immediately, you can add those registrations to your configuration. You can only add tax registrations for [supported countries and registration types](https://docs.stripe.com/tax/supported-countries#supported-countries). Some jurisdictions might require additional information.

### Add tax registration

On the Stripe Tax tab, under Tax registrations, click Add new. Choose the jurisdiction from the dropdown, then click Save changes.

### Delete tax registration

To delete a registration, hover over the registration, then click End immediately. To delete multiple registrations, select them from the list, then click End immediately from the Bulk actions dropdown.

## 5. Configure product tax codes and customer tax settings (optional)

You can configure tax codes for your products or tax settings for your customers.

### Product tax codes

Stripe Tax can calculate the tax for each of your products based on the product tax code that you assign to it. If you don’t set a code per product, Stripe Tax uses the default [product tax code](https://docs.stripe.com/tax/tax-codes) that you selected during setup.

To set a tax code per product:

1.  Go to Products and select a product from the list.
    
2.  On the Edit product page, in the Product data section, select a Stripe Tax - Product tax code for the product.
    
3.  Click Update to save your selection.
    

### Customer tax settings

Stripe Tax can calculate the tax for each of your customers based on their tax status.

To update a user’s taxability:

1.  Go to Users and select a user from the list.
    
2.  Under Stripe Tax Exemptions, select a Tax Status option for the user.
    
3.  Click Update user to save your selection.
    

Learn more about how the customer’s [tax status](https://docs.stripe.com/tax/zero-tax#exempt-customers) impacts sales tax calculation.

## 6. Collect taxes

To start collecting taxes:

-   On the WooCommerce > General tab, select Enable tax rates and calculations, then click Save changes. This setting enables rate configuration and sales tax calculation during the checkout process.
    
-   On the WooCommerce > Stripe Tax tab, select Enable Stripe Tax, then click Save changes. This setting enables automatic sales tax calculation and collection on all transactions.
    

## 7. View tax reports

After you start collecting taxes, this Stripe Tax connector sends the transactions to Stripe Tax. You can then access tax reports and exports in the [Stripe Dashboard](https://dashboard.stripe.com/tax/registrations).

Learn more about the types of [tax reports](https://docs.stripe.com/tax/reports) available in Stripe Tax.