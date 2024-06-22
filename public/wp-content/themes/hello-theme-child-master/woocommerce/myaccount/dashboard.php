<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

require_once get_stylesheet_directory().'/wco/classes/class-orders.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);
?>

<p>
	<?php
	printf(
		/* translators: 1: user display name 2: logout url */
		wp_kses( __( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ), $allowed_html ),
		'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
		esc_url( wc_logout_url() )
	);
	?>
</p>
<p><strong>Membership Status:</strong> 
	<?php
		$current_user = wp_get_current_user();
		if ( ! empty( $current_user->roles ) ) {
			echo esc_html( implode( ', ', array_map('role_to_title', $current_user->roles) ) );
		}
	?>
</p>


<!-- Display prescription -->
<?php 
$user_id = get_current_user_id();
$active_prescription = get_user_meta($user_id, 'active_prescription', true) ?: "";
$prescription_summary = get_user_meta($user_id, 'prescription_summary', true) ?: "";
?>

<p style="margin-bottom: 0;"><strong>Prescription:</strong></p>
<?php 
if($active_prescription){?>
	<div style="overflow-x: auto;margin-bottom: 20px;">
		<table class="shop_table" style="min-width: 900px;background: #f1f1f1;">
			<tr>
				<td><strong>#</strong></td>
				<td><strong>Dosage Limit</strong></td>
				<td><strong>Frequency</strong></td>
				<td><strong>THC Content</strong></td>
				<td><strong>Duration</strong></td>
				<td><strong>Start Date</strong></td>
				<td><strong>Expiration Date</strong></td>
			</tr>
			<?php foreach($active_prescription as $index => $prescription_item){?>
				<tr>
					<td><?php echo $index+1;?></td>
					<td><?php echo $prescription_item['dosage'];?> grams</td>
					<td><?php echo "monthly";?></td>
					<td><?php echo $prescription_item['thc_content'];?>%</td>
					<td><?php echo $prescription_item['duration'];?> months</td>
					<td><?php echo $prescription_item['created_at'] ? date("F j, Y", $prescription_item['created_at']) : "-";?></td>
					<td><?php echo $prescription_item['expiration_date'] ? date("F j, Y", $prescription_item['expiration_date']) : "-";?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
	
	<?php 
	foreach($prescription_summary as $index => $order){
		$thc = key($order);

	?>
		<p style="margin-bottom: 0;"><strong>Summary Prescription #: <?php echo $index+1;?> </strong></p>
		<div style="overflow-x: auto;margin-bottom: 20px;">
			<table class="shop_table" style="min-width: 900px;background: #f1f1f1;">
				<tr>
					<td>THC Content: <?php echo $thc;?>%</td>
					<?php foreach($order[$thc] as $month => $item){
						?>
						<td><strong>Month # <?php echo $month+1;?></strong></td>
					<?php } ?>
				</tr>
				<tr>
					<td>
						Total Ordered Qty: <br/>
						Dosage Remaining: 
					</td>
					<?php foreach($order[$thc] as $month => $item){
					?>
						<td>
							<?php echo $item['ordered_qty'] ? $item['ordered_qty']." g" : "-";?><br/>
							<?php echo $item['remaining_qty'] || $item['remaining_qty'] === 0.0 ? $item['remaining_qty']." g" : "-";?>
						</td>
					<?php } ?>
				</tr>
			</table>
		</div>
	<?php } ?>

<?php }
else{
	echo "<p>You have no prescription. </p>";
}?>

<p>
	<?php
	/* translators: 1: Orders URL 2: Address URL 3: Account URL. */
	$dashboard_desc = __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">billing address</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' );
	if ( wc_shipping_enabled() ) {
		/* translators: 1: Orders URL 2: Addresses URL 3: Account URL. */
		$dashboard_desc = __( 'From your account you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping address</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' );
	}
	printf(
		wp_kses( $dashboard_desc, $allowed_html ),
		esc_url( wc_get_endpoint_url( 'orders' ) ),
		esc_url( wc_get_endpoint_url( 'edit-address' ) ),
		esc_url( wc_get_endpoint_url( 'edit-account' ) )
	);
	?>
</p>



<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );

	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );

	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */