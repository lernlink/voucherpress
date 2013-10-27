<?php
// a collection of responses that may be sent when a voucher is requested

// show a 404 page
function voucherpress_404( $found = true ) {
	global $wp_query;
	$wp_query->set_404();
	if ( $found ) {
		wp_die( __( "Sorry, that item is not available", "voucherpress" ) );
	} else {
		wp_die( __( "Sorry, that item was not found", "voucherpress" ) );
	}
	exit();
}

// show an expired voucher page
function voucherpress_expired() {
	global $wp_query;
	$wp_query->set_404();
	wp_die( __( "Sorry, that item has expired", "voucherpress" ) );
	exit();
}

// show an expired voucher page
function voucherpress_notyetavailable() {
	global $wp_query;
	$wp_query->set_404();
	wp_die( __( "Sorry, that item is not yet available", "voucherpress" ) );
	exit();
}

// show a run out voucher page
function voucherpress_runout() {
	global $wp_query;
	$wp_query->set_404();
	wp_die( __( "Sorry, that item has run out", "voucherpress" ) );
	exit();
}

// show a downloaded voucher page
function voucherpress_downloaded() {
	global $wp_query;
	$wp_query->set_404();
	wp_die( __( "You have already downloaded this voucher", "voucherpress" ) );
	exit();
}
?>