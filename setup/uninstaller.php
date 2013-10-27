<?php
// manages deactivation and uninstallation of VoucherPress

// deactivate the plugin
function voucherpress_deactivate() {

	// delete options
	delete_option( 'voucherpress_data' );
	delete_option( 'voucherpress_version' );

	// we don't delete the tables here as the user may have deactivated the plugin by
	// accident, and would be upset to find all their vouchers deleted
}
?>