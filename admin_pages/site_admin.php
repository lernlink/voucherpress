<?php
// shows the site admin page

//check install
voucherpress_include( "setup/installer.php" );
voucherpress_check_install();

// include the required managers
voucherpress_include( "managers/manager-vouchers.php" );
$voucherManager = new VoucherPress_VoucherManager();

voucherpress_report_header();

echo '<h2>' . __( "Vouchers", "voucherpress" ) . '</h2>';

echo '<div class="voucherpress_col1">
<h3>' . __( "25 most recent vouchers", "voucherpress" ) . '</h3>
';
$vouchers = $voucherManager->GetAllVouchers( 25, 0 );
if ( $vouchers && is_array( $vouchers ) && count( $vouchers ) > 0 )
{
	voucherpress_table_header( array( "Blog", "Name", "Downloads" ) );
	foreach( $vouchers as $voucher )
	{
		echo '
		<tr>
			<td><a href="http://' . $voucher->domain . $voucher->path . '">' . $voucher->domain . $voucher->path . '</a></td>
			<td><a href="http://' . $voucher->domain . $voucher->path . '?voucher=' . $voucher->guid . '">' . $voucher->name . '</a></td>
			<td>' . $voucher->downloads . '</td>
		</tr>
		';
	}
	voucherpress_table_footer();
} else {
	echo '
	<p>' . __( 'No vouchers found. <a href="admin.php?page=vouchers-create">Create your first voucher here.</a>', "voucherpress" ) . '</p>
	';
}
echo '
</div>';

echo '<div class="voucherpress_col2">
<h3>' . __( "25 most popular vouchers", "voucherpress" ) . '</h3>
';
$vouchers = $voucherManager->GetAllPopularVouchers( 25, 0 );
if ( $vouchers && is_array( $vouchers ) && count( $vouchers ) > 0 )
{
	voucherpress_table_header( array( "Blog", "Name", "Downloads" ) );
	foreach( $vouchers as $voucher )
	{
		echo '
		<tr>
			<td><a href="http://' . $voucher->domain . $voucher->path . '">' . $voucher->domain . $voucher->path . '</a></td>
			<td><a href="http://' . $voucher->domain . $voucher->path . '?voucher=' . $voucher->guid . '">' . $voucher->name . '</a></td>
			<td>' . $voucher->downloads . '</td>
		</tr>
		';
	}
	voucherpress_table_footer();
} else {
	echo '
	<p>' . __( 'No vouchers found. <a href="admin.php?page=vouchers-create">Create your first voucher here.</a>', "voucherpress" ) . '</p>
	';
}
echo '
</div>';

voucherpress_report_footer();
?>