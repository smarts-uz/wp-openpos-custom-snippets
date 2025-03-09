<?php
/**
 * Template file for ignoring WooCommerce taxes notice after plugin activate.
 *
 * @package Stripe\StripeTaxForWooCommerce\templates
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="notice notice-warning is-dismissible">
	<p><strong>Warning:</strong> if you enable the Stripe Tax collection, the tax rates specified in the WooCommerce tax
		classes (in the Tax tab) will be ignored in the calculation of taxes.</p>
</div>
