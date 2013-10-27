<?php
// general functions

// get the currently installed version
function voucherpress_get_version() {
	if ( function_exists( 'get_site_option' ) ) {
		return get_site_option( 'voucherpress_version' );
	} else {
		return get_option( 'voucherpress_version' );
	}
}

// update the currently installed version
function voucherpress_update_version() {
	$version = voucherpress_current_version();
	if ( function_exists( 'get_site_option' ) ) {
		update_site_option( 'voucherpress_version', $version );
	} else {
		return update_option( 'voucherpress_version', $version );
	}
}

// delete the currently installed version flag
function voucherpress_delete_version() {
	if ( function_exists( 'get_site_option' ) ) {
		delete_site_option( 'voucherpress_version' );
	} else {
		return delete_option( 'voucherpress_version' );
	}
}

// return the database table prefix
function voucherpress_get_db_prefix() {
	global $wpdb;
	$prefix = $wpdb->prefix;

	if ( isset( $wpdb->base_prefix ) )
		$prefix = $wpdb->base_prefix;

	return $prefix;
}

// check if the site is using pretty URLs
function voucherpress_pretty_urls() {
	$structure = get_option( 'permalink_structure' );
	if ( '' != $structure || false === strpos( $structure, '?' ) ) return true;
	return false;
}

// create a URL to a voucherpress page
function voucherpress_link( $voucher_guid, $download_guid = '', $encode = true ) {

	if ( voucherpress_pretty_urls() ) {

        // check the guid is set
		if ( $download_guid != '' ) {
			if ( $encode ) {
				$download_guid = '&amp;guid=' . urlencode( $download_guid );
			} else {
				$download_guid = '&guid=' . urlencode( $download_guid );
			}
		}
		return get_option( 'siteurl' ) . '/?voucher=' . $voucher_guid . $download_guid;
	}

    // check the guid is set
	if ( '' != $download_guid ) {
		if ( $encode ) {
			$download_guid = '&amp;guid=' . urlencode( $download_guid );
		} else {
			$download_guid = '&guid=' . urlencode( $download_guid );
		}
	}
	return get_option( 'siteurl' ) . '/?voucher=' . $voucher_guid . $download_guid;
}

// create an md5 hash of a guid
// from http://php.net/manual/en/function.com-create-guid.php
function voucherpress_guid( $length = 6 ) {

    // use the com_create_guid function if it exists
    if ( function_exists( 'com_create_guid' ) ) {
        return substr( md5( str_replace( '{', '', str_replace( '}', '', com_create_guid() ) ) ), 0, $length );
    }

    // manually create the guid
    mt_srand( (double) microtime() * 10000 );
    $charid = strtoupper( md5( uniqid( rand(), true ) ) );
    $hyphen = chr(45);
    $uuid =
            substr( $charid, 0, 8 ).$hyphen
            .substr( $charid, 8, 4 ).$hyphen
            .substr( $charid,12, 4 ).$hyphen
            .substr( $charid,16, 4 ).$hyphen
            .substr( $charid,20,12 );
    return substr( md5( str_replace( '{', '', str_replace( '}', '', $uuid ) ) ), 0, $length );
}

// get the users IP address
// from http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
function voucherpress_ip() {

    // check ip from share internet
	if( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];

    // to check ip is pass from proxy
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// get the URL of the current page
function voucherpress_page_url() {
	$pageURL = 'http';

	if ( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] )
		$pageURL .= 's';

	$pageURL .= '://';

	if ( '80' != $_SERVER['SERVER_PORT'] ) {
		$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
	} else {
		$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	}
	return $pageURL;
}

// get the current blog ID (for WP Multisite) or '1' for standard WP
function voucherpress_blog_id() {
	global $current_blog;

	if ( is_object( $current_blog ) && '' != $current_blog->blog_id )
		return $current_blog->blog_id;

	return 1;
}

// get the current user ID
function voucherpress_user_id() {
	global $current_user;
	return $current_user->ID;
}

// return yes or no
function voucherpress_yes_no( $val ) {
	if ( ! $val || '' == $val || '0' == $val )
		return __( 'No', 'voucherpress' );

	return __( 'Yes', 'voucherpress' );
}
?>