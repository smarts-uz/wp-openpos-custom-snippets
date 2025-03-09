<?php
/**
 * Template part to be included into admin, profile page.
 *
 * @var object $user
 * @see \Stripe\StripeTaxForWooCommerce\Stripe\TaxExemptions::custom_user_profile_fields
 *
 * @package Stripe\StripeTaxForWooCommerce
 */

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

$tax_exemption_value = get_the_author_meta( 'tax_exemption', $user->ID );
?>
<h3>
	<?php
	echo esc_html( __( 'Stripe Tax Exemptions', 'stripe-tax-for-woocommerce' ) );
	?>
</h3>
<table class="form-table">
	<tr>
		<th><label for="tax_exemption">
				<?php
				echo esc_html( __( 'Tax Status', 'stripe-tax-for-woocommerce' ) );
				?>
			</label>
		</th>
		<td>
			<select name="tax_exemption" id="tax_exemption">
				<option value="none"
				<?php
				selected( $tax_exemption_value, 'none' );
				?>
				>
				<?php
				echo esc_html( __( 'Taxable', 'stripe-tax-for-woocommerce' ) );
				?>
				</option>
				<option value="customer_exempt"
				<?php
				selected( $tax_exemption_value, 'customer_exempt' );
				?>
				>
				<?php
				echo esc_html( __( 'Exempt', 'stripe-tax-for-woocommerce' ) );
				?>
				</option>
				<option value="reverse_charge"
				<?php
				selected( $tax_exemption_value, 'reverse_charge' );
				?>
				>
				<?php
				echo esc_html( __( 'Reverse Charge', 'stripe-tax-for-woocommerce' ) );
				?>
				</option>
			</select>
			<p class="description">
				<a href="<?php echo esc_url( $this->get_tax_exemption_link() ); ?>" target="_blank">
					<?php
					echo esc_html( __( 'Review our guide', 'stripe-tax-for-woocommerce' ) );
					?>
				</a>
				<?php
				echo esc_html( __( 'on tax statuses to select the best fit for your customer', 'stripe-tax-for-woocommerce' ) );
				?>
			</p>
		</td>
	</tr>
</table>
