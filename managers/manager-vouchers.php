<?php
// manages voucher, for instance getting multiple vouchers

class VoucherPress_VoucherManager {

	// get a list of vouchers for a blog/user
	function GetVouchers( $num = 25, $all = false ) {

        // include the voucher class
		voucherpress_include( 'classes/class-voucher.php' );

		$blog_id = voucherpress_blog_id();
		$user_id = voucherpress_user_id();
		global $wpdb;

        // set limits for pagination
		$showall = '0';
		if ( $all )
			$showall = '1';

		$limit = 'limit ' . absint( $num );
		if ( $num == 0 )
			$limit = '';

		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare( "SELECT v.id, v.name, v.`html`, v.`text`, v.`description`, v.terms, v.require_email, v.font, v.template,
			v.codestype, v.codeprefix, v.codesuffix, v.codelength, v.codes, '' as registered_name, v.user_id, v.blog_id,
			v.`limit`, v.live, v.startdate, v.expiry, v.guid,
			(SELECT COUNT(d.id) FROM {prefix}voucherpress_downloads d WHERE d.voucherid = v.id AND d.downloaded > 0) AS downloads
			FROM {$prefix}voucherpress_vouchers v
			WHERE (%s = '1' OR v.live = 1)
			AND (%s = '1' OR (expiry = '' OR expiry = 0 OR expiry > %d))
			AND (%s = '1' OR (startdate = '' OR startdate = 0 OR startdate <= %d))
			AND v.blog_id = %d
			AND (v.user_id = %d OR v.user_id = 0)
			AND v.deleted = 0
			ORDER BY v.time DESC
			$limit;", $showall, $showall, time(), $showall, time(), $blog_id, $user_id );

		voucherpress_debug( $sql );

		$rows = $wpdb->get_results( $sql );

		$vouchers = array();
		foreach( $rows as $row ) {
			$voucher = new VoucherPress_Voucher();
			$voucher->MapRow( $row );
			$vouchers[] = $voucher;
		}
		return $vouchers;
	}

	// get a list of all vouchers
	function GetAllVouchers( $num = 25, $all = false ) {

        // include the voucher class
		voucherpress_include( 'classes/class-voucher.php' );

		$blog_id = voucherpress_blog_id();
		$user_id = voucherpress_user_id();
		global $wpdb;

        // set limits for pagination
		$showall = '0';
		if ( $all )
			$showall = '1';

		$limit = 'limit ' . absint( $num );
		if ($num == 0)
			$limit = '';

		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare( "SELECT v.id, v.name, v.`html`, v.`text`, v.`description`, v.terms, v.require_email, v.font, v.template,
			v.`limit`, v.live, v.startdate, v.expiry, v.guid, '' as registered_name, v.user_id, v.blog_id,
			v.codestype, v.codeprefix, v.codesuffix, v.codelength, v.codes,
			(SELECT COUNT(d.id) FROM {$prefix}voucherpress_downloads d WHERE d.voucherid = v.id AND d.downloaded > 0) AS downloads
			FROM {$prefix}voucherpress_vouchers v
			WHERE (%s = '1' OR v.live = 1)
			AND (%s = '1' OR (expiry = '' OR expiry = 0 OR expiry > %d))
			AND (%s = '1' OR (startdate = '' OR startdate = 0 OR startdate <= %d))
			AND v.deleted = 0
			ORDER BY v.time DESC
			$limit;", $showall, $showall, time(), $showall, time() );

		voucherpress_debug( $sql );

		$rows = $wpdb->get_results( $sql );

		$vouchers = array();
		foreach( $rows as $row ) {
			$voucher = new VoucherPress_Voucher();
			$voucher->MapRow( $row );
			$vouchers[] = $voucher;
		}
		return $vouchers;
	}

	// get a list of popular vouchers by download for a blog/user
	function GetPopularVouchers( $num = 25 ) {

        // include the voucher class
		voucherpress_include( 'classes/class-voucher.php' );

		$blog_id = voucherpress_blog_id();
		$user_id = voucherpress_user_id();
		global $wpdb;

        // set limits for pagination
		$limit = 'limit ' . absint( $num );
		if ($num == 0)
			$limit = '';

		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare( "SELECT v.id, v.name, v.`html`, v.`text`, v.`description`, v.terms, v.require_email, v.font, v.template,
			v.`limit`, v.live, v.startdate, v.expiry, v.guid, '' as registered_name, v.user_id, v.blog_id,
			v.codestype, v.codeprefix, v.codesuffix, v.codelength, v.codes,
			COUNT(d.id) AS downloads
			FROM {$prefix}voucherpress_downloads d
			INNER JOIN {$prefix}voucherpress_vouchers v ON v.id = d.voucherid
			WHERE v.blog_id = %d
			AND (v.user_id = %d OR v.user_id = 0)
			AND v.deleted = 0
			AND d.downloaded > 0
			GROUP BY v.id, v.name, v.`text`, v.terms, v.require_email, v.`limit`, v.live, v.expiry, v.guid
			ORDER BY count(d.id) DESC
			$limit;", $blog_id, $user_id );

		voucherpress_debug( $sql );

		$rows = $wpdb->get_results( $sql );

		$vouchers = array();
		foreach( $rows as $row ) {
			$voucher = new VoucherPress_Voucher();
			$voucher->MapRow( $row );
			$vouchers[] = $voucher;
		}
		return $vouchers;
	}

	// get a list of all popular vouchers by download
	function GetAllPopularVouchers( $num = 25, $start = 0 ) {

        // include the voucher class
		voucherpress_include( 'classes/class-voucher.php' );

		$blog_id = voucherpress_blog_id();
		$user_id = voucherpress_user_id();
		global $wpdb;

        // set limits for pagination
		$limit = 'limit ' . absint( $start ) . ', ' . absint( $num );
		if ($num == 0)
			$limit = '';

		$prefix = voucherpress_get_db_prefix();

		$sql = "SELECT b.domain, b.path, v.id, v.name, v.`html`, v.`text`, v.`description`, v.terms, v.require_email, v.font, v.template,
			v.`limit`, v.live, v.startdate, v.expiry, v.guid, '' as registered_name, v.user_id, v.blog_id,
			v.codestype, v.codeprefix, v.codesuffix, v.codelength, v.codes,
			COUNT(d.id) AS downloads
			FROM {$prefix}voucherpress_downloads d
			INNER JOIN {$prefix}voucherpress_vouchers v ON v.id = d.voucherid
			INNER JOIN {$wpdb->base_prefix}blogs b ON b.blog_id = v.blog_id
			GROUP BY b.domain, b.path, v.id, v.name, v.`text`, v.terms, v.require_email, v.`limit`, v.live, v.expiry, v.guid
			WHERE v.deleted = 0
			AND d.downloaded > 0
			ORDER BY COUNT(d.id) DESC
			$limit;";

		voucherpress_debug( $sql );

		$rows = $wpdb->get_results( $sql );

		$vouchers = array();
		foreach( $rows as $row ) {
			$voucher = new VoucherPress_Voucher();
			$voucher->MapRow( $row );
			$vouchers[] = $voucher;
		}
		return $vouchers;
	}
	// check a voucher exists and can be downloaded
	function VoucherExists( $guid ) {

		$blog_id = voucherpress_blog_id();
		
		global $wpdb;
		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare( "SELECT v.id, v.`limit`,
		(SELECT COUNT(d.id) FROM {$prefix}voucherpress_downloads d WHERE d.voucherid = v.id AND d.downloaded > 0) AS downloads
		FROM {$prefix}voucherpress_vouchers v
		WHERE
		v.guid = %s
		AND v.deleted = 0
		AND v.blog_id = %d",
		$guid, $blog_id );

		voucherpress_debug( $sql );

		$row = $wpdb->get_row( $sql );
		if ( $row ) {
			return true;
		}
		return false;
	}
}
?>