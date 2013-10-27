<?php
// shows the admin page

//check install
voucherpress_include( 'setup/installer.php' );
voucherpress_check_install();

// include the required managers
voucherpress_include( 'managers/manager-vouchers.php' );
$voucherManager = new VoucherPress_VoucherManager();

voucherpress_report_header();

// if a voucher has not been chosen
if ( !isset( $_GET['id'] ) )
{

	echo '<h2>' . __( 'Vouchers', 'voucherpress' ) . '
	<span style="float:right;font-size:80%"><a href="admin.php?page=vouchers-create" class="button">' . __( 'Create a voucher', 'voucherpress' ) . '</a></span></h2>';

	if ( @$_GET["reset"] == "true" ) {
		voucherpress_delete_version();
		echo '
		<div id="message" class="updated">
			<p><strong>' . __( 'Your VoucherPress database will be reset next time you create or edit a voucher. You will not lose any data, the tables will just be checked for all the correct fields.', 'voucherpress' ) . '</strong></p>
		</div>
		';
	}

	echo '<div class="voucherpress_col1">
	<h3>' . __( 'Your vouchers', 'voucherpress' ) . '</h3>
	';
	$vouchers = $voucherManager->GetVouchers( 10, true );
	if ( $vouchers && is_array( $vouchers ) && count( $vouchers ) > 0 )
	{
		$vouchers_found = true;
		voucherpress_table_header( array( 'Name', 'Downloads', 'Email required' ) );
		foreach( $vouchers as $voucher )
		{
			echo "
			<tr>
				<td><a href='admin.php?page=vouchers&amp;id={$voucher->id}'>{$voucher->name}</a></td>
				<td>{$voucher->downloads}</td>
				<td>' . voucherpress_yes_no( $voucher->require_email ) . '</td>
			</tr>
			";
		}
		voucherpress_table_footer();
	} else {
		$vouchers_found = false;
		echo '
		<p>' . __( 'No vouchers found. <a href="admin.php?page=vouchers-create" class="button-primary">Create your first voucher here</a>', 'voucherpress' ) . '</p>
		';
	}
	echo '
	</div>';

	// if there were vouchers found
	if ( $vouchers_found ) {

	echo '<div class="voucherpress_col2">
	<h3>' . __( 'Popular vouchers', 'voucherpress' ) . '</h3>
	';
	$vouchers = $voucherManager->GetPopularVouchers();
	if ( $vouchers && is_array( $vouchers ) && count( $vouchers ) > 0 )
	{
		voucherpress_table_header( array( 'Name', 'Downloads', 'Email required' ) );
		foreach( $vouchers as $voucher )
		{
			echo '
			<tr>
				<td><a href='admin.php?page=vouchers&amp;id={$voucher->id}'>{$voucher->name}</a></td>
				<td>{$voucher->downloads}</td>
				<td>' . voucherpress_yes_no( $voucher->require_email ) . '</td>
			</tr>
			';
		}
		voucherpress_table_footer();
	} else {
		echo '
		<p>' . __( 'No vouchers found. <a href="admin.php?page=vouchers-create">Create your first voucher here.</a>', 'voucherpress' ) . '</p>
		';
	}
	echo '
	<p><a href="' . wp_nonce_url( 'admin.php?page=vouchers&amp;download=emails', 'voucherpress_download_csv' ) . '" class="button">' . __( 'Download all registered email addresses', 'voucherpress' ) . '</a></p>
	</div>';

	}

// if a voucher has been chosen
} else {

	voucherpress_edit_voucher_page();

}

voucherpress_report_footer();
?>