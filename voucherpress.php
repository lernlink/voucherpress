<?php
/**
 * @package VoucherPress
 * @author Chris Taylor
 * @version 2.0
 */
/*
Plugin Name: VoucherPress
Plugin URI: http://www.stillbreathing.co.uk/wordpress/voucherpress/
Description: VoucherPress allows you to offer downloadable, printable vouchers from your Wordpress site. Vouchers can be available to anyone, or require a name and email address before they can be downloaded.
Author: Chris Taylor
Version: 2.0
Author URI: http://www.stillbreathing.co.uk/
*/
/*
My grateful thanks go to Brent for helping make this plugin much better.
*/

// set the current version
function voucherpress_current_version() {
	return '2.0';
}

// return a value indicating if VoucherPress is in debug mode
function voucherpress_debugging() {
	$debug = true;
	if ( $debug || ( defined( WP_DEBUG ) && WP_DEBUG ) ) {
		return true;
	}
	return false;
}

// print a message if VoucherPress is in debug mode
function voucherpress_debug( $message ) {
	if ( voucherpress_debugging() ) {
		print "<div class='updated'><p>$message</p></div>";
	}
}

// set the default size of templates
function voucherpress_default_size() {
	return '800x360';
}

// ==========================================================================================
// includes required for every request

// function for making sure VoucherPress includes are include from the right folder
function voucherpress_include( $file ) {
	require_once( WP_PLUGIN_DIR . '/voucherpress/' . $file );
}

// include the required files
voucherpress_include( 'functions.php' );
voucherpress_include( 'responses.php' );
voucherpress_include( 'shortcodes.php' );
voucherpress_include( 'setup/installer.php' );
voucherpress_include( 'setup/uninstaller.php' );
voucherpress_include( 'classes/class-ajax.php' );

// ==========================================================================================
// set up AJAX requests

add_action( 'wp_ajax_voucherpress_loadthumbs', array( 'VoucherPress_Ajax', 'LoadThumbs' ) );

// ==========================================================================================
// admin pages functions

// add the menu items
function voucherpress_add_admin() {
	add_menu_page(
		__( 'Vouchers', 'voucherpress' ),
		__( 'Vouchers', 'voucherpress' ),
		'publish_posts',
		'vouchers',
		'vouchers_admin'
	);

	add_submenu_page(
		'vouchers',
		__( 'Create a voucher', 'voucherpress' ),
		__( 'Create', 'voucherpress' ),
		'publish_posts',
		'vouchers-create',
		'voucherpress_create_voucher_page'
	);

	add_submenu_page(
		'vouchers',
		__( 'Voucher templates', 'voucherpress' ),
		__( 'Templates', 'voucherpress' ),
		'publish_posts',
		'vouchers-templates',
		'voucherpress_templates_page'
	);

	// for WPMU site admins
	if ( ( function_exists( 'is_super_admin' ) && is_super_admin() ) ||
		( function_exists( 'is_site_admin' ) && is_site_admin() ) ) {

		add_submenu_page(
			'wpmu-admin.php',
			__('Vouchers'),
			__('Vouchers'),
			'edit_users',
			'voucherpress-admin',
			'voucherpress_site_admin'
		);
	}

	if ( voucherpress_debugging() ) {
		add_submenu_page(
			'vouchers',
			__( 'Debugging', 'voucherpress' ),
			__( 'Debugging', 'voucherpress' ),
			'edit_users',
			'vouchers-debug',
			'voucherpress_debug_page' );
	}
}

// show the general site admin page
function voucherpress_site_admin() {
	voucherpress_include( 'admin_pages/site_admin.php' );
}

// show the general admin page
function vouchers_admin() {
	voucherpress_include( 'admin_pages/admin.php' );
}

// show the create voucher page
function voucherpress_create_voucher_page() {
	voucherpress_include( 'admin_pages/create_voucher.php' );
}

// show the edit voucher page
function voucherpress_edit_voucher_page() {
	voucherpress_include( 'admin_pages/edit_voucher.php' );
}

// show the templates page
function voucherpress_templates_page() {
	voucherpress_include( 'admin_pages/templates.php' );
}

// show the debug page
function voucherpress_debug_page() {
	voucherpress_include( 'admin_pages/debugging.php' );
}

// include the voucherpress CSS file
function voucherpress_admin_css() {
	echo '
	<link rel="stylesheet" href="' . plugins_url() . '"/voucherpress/css/voucherpress.css" type="text/css" media="all" />
	';
}

// include the voucherpress JS file
function voucherpress_admin_js() {
	echo "
	<script type='text/javascript'>
		var vp_siteurl = '" . get_option('siteurl') . "';
	</script>
	<script type='text/javascript' src='" . plugins_url() . "/voucherpress/scripts/voucherpress.js'></script>
	";
}

// content displayed above every report
function voucherpress_report_header() {
	echo '
	<div id="voucherpress" class="wrap">
	';

	voucherpress_wp_plugin_standard_header(
		'GBP',
		'VoucherPress',
		'Chris Taylor',
		'chris@stillbreathing.co.uk',
		'http://wordpress.org/extend/plugins/voucherpress/'
	);
}

// content displayed below every report
function voucherpress_report_footer() {
	voucherpress_wp_plugin_standard_footer(
		'GBP',
		'VoucherPress',
		'Chris Taylor',
		'chris@stillbreathing.co.uk',
		'http://wordpress.org/extend/plugins/voucherpress/'
	);
}

// display the header of a data table
function voucherpress_table_header( $headings ) {
	echo '
	<table class="widefat post fixed">
	<thead>
	<tr>
	';

	foreach( $headings as $heading ) {
		echo '<th>' . __( $heading, 'voucherpress' ) . '</th>
		';
	}

	echo '
	</tr>
	</thead>
	<tbody>
	';
}

// display the footer of a data table
function voucherpress_table_footer() {
	echo '
	</tbody>
	</table>
	';
}

// ==========================================================================================
// voucher administration functions

// listen for downloads of email addresses
function voucherpress_check_download() {

	// download all unique email addresses
	if ( wp_verify_nonce( @$_GET['_wpnonce'], 'voucherpress_download_csv' )
			&& ( @$_GET['page'] == 'vouchers' )
			&& @$_GET['download'] == 'emails'
			&& @$_GET['voucher'] == '' ) {

		if ( !voucherpress_download_emails() ) {
			wp_die( __( 'Sorry, the downloads for all vouchers could not be downloaded. Perhaps there have been no downloads. Please click back and try again.', 'voucherpress' ) );
		}

	}

	// download unique email addresses for a voucher
	if ( wp_verify_nonce( @$_GET['_wpnonce'], 'voucherpress_download_csv' )
			&& ( @$_GET['page'] == 'vouchers' )
			&& @$_GET['download'] == 'emails'
			&& @$_GET['voucher'] != '' ) {

		if ( !voucherpress_download_emails($_GET['voucher']) ) {
			wp_die( __( 'Sorry, the downloads for this voucher could not be downloaded. Perhaps there have been no downloads. Please click back and try again.', 'voucherpress' ) );
		}

	}
}

// listen for previews of a voucher
function voucherpress_check_preview() {
	if ( ( @$_GET['page'] == 'vouchers' || @$_GET['page'] == 'vouchers-create' )
			&& @$_GET['preview'] == 'voucher' ) {
		voucherpress_preview_voucher(
			@$_POST['template'],
			@$_POST['font'],
			@$_POST['name'],
			@$_POST['html']
		);
	}
}

// listen for testing of a voucher
function voucherpress_check_test() {
	if ( 'vouchers-debug' == @$_GET['page']
		&& 'voucher' == @$_GET['test'] ) {

		voucherpress_test_voucher(
			$_POST['width'],
			$_POST['height'],
			$_POST['html']
		);
	}
}

// listen for creation of a voucher
function voucherpress_check_create_voucher() {

    // verify the nonce and check the querystring parameters are correct
	if ( wp_verify_nonce( @$_POST['_wpnonce'], 'voucherpress_create' )
			&& @$_GET['page'] == 'vouchers-create'
			&& @$_GET['preview'] == ''
			&& @$_POST
			&& is_array( $_POST )
			&& count( $_POST ) > 0 ) {

        // include the voucher class
		voucherpress_include( 'classes/class-voucher.php' );

        // set up the voucher properties

		$require_email = 0;
		if ( isset( $_POST['requireemail'] ) && '1' == $_POST['requireemail'] )
			$require_email = 1;

		$limit = 0;
		if ( $_POST['limit'] != '' && '0' != $_POST['limit'] )
			$limit = (int)$_POST['limit'];

		// get the start date
		$startdate = 0;
		if ( '' != $_POST['startyear']
				&& '0' != $_POST['startyear']
				&& '' != $_POST['startmonth']
				&& '0' != $_POST['startmonth']
				&& '' != $_POST['startday']
				&& '0' != $_POST['startday'] )
			$startdate = strtotime( $_POST['startyear'] . '/' . $_POST['startmonth'] . '/' . $_POST['startday']);

		// get the expiry
		$expiry = 0;
		if ( '' != $_POST['expiryyear']
				&& '0' != $_POST['expiryyear']
				&& '' != $_POST['expirymonth']
				&& '0' != $_POST['expirymonth']
				&& '' != $_POST['expiryday']
				&& '0' != $_POST['expiryday'] )
			$expiry = strtotime( $_POST['expiryyear'] . '/' . $_POST['expirymonth'] . '/' . $_POST['expiryday']);

		if ( '' != $_POST['expirydays'] && is_numeric( $_POST['expirydays'] ) > 0 )
			$expiry = time() + ( intval( $_POST['expirydays'] ) * 24 * 60 * 60 );

		if ( '' != $_POST['expirydays']
				&& is_numeric( $_POST['expirydays'] ) > 0
				&& $startdate > 0 )
			$expiry = $startdate + ( intval( $_POST['expirydays'] ) * 24 * 60 * 60 );

		// get the code type
		if ( 'random' == $_POST['codestype']
				|| 'sequential' == $_POST['codestype']
				|| 'custom' == $_POST['codestype']
				|| 'single' == $_POST['codestype'] ) {
			$codestype = $_POST['codestype'];
		} else {
			$codestype = 'random';
		}

		if ( '' != $_POST['codelength'] )
			$codelength = (int)$_POST['codelength'];

		if ( '' == $codelength || 0 == $codelength )
			$codelength = 6;

		$codeprefix = trim( $_POST['codeprefix'] );
		if ( strlen( $codeprefix ) > 6 )
			$codeprefix = substr( $codeprefix, 6 );

		$codesuffix = trim( $_POST['codesuffix'] );

		if ( strlen( $codesuffix ) > 6 )
			$codesuffix = substr( $codesuffix, 6 );

		$codes = '';
		if ( 'custom' == $_POST['codestype'] )
			$codes = trim( $_POST['customcodelist'] );

		if ( 'single' == $_POST['codestype'] )
			$codes = trim( $_POST['singlecodetext'] );

		// create the new voucher
		$voucher = new VoucherPress_Voucher();
		$voucher->name = $_POST['name'];
		$voucher->require_email = $require_email;
		$voucher->limit = $limit;
		$voucher->html = $_POST['html'];
		$voucher->description = $_POST['description'];
		$voucher->template= $_POST['template'];
		$voucher->live = 1;
		$voucher->startdate = $startdate;
		$voucher->expiry = $expiry;
		$voucher->codestype = $codestype;
		$voucher->codelength = $codelength;
		$voucher->codeprefix = $codeprefix;
		$voucher->codesuffix = $codesuffix;
		$voucher->codes = $codes;
		$voucher->blog_id = voucherpress_blog_id();
		$voucher->user_id = voucherpress_user_id();
		$done = $voucher->Save();

        // redirect based on whether the save was successful
		if ( $done ) {

			header( "Location: admin.php?page=vouchers&id={$voucher->id}&result=1" );
			exit();

		} else {

			header( 'Location: admin.php?page=vouchers-create&result=1' );
			exit();
		}
	}
}

// listen for editing of a voucher
function voucherpress_check_edit_voucher() {

    // verify the nonce and check the querystring parameters are correct
	if ( wp_verify_nonce(@$_POST['_wpnonce'], 'voucherpress_edit')
			&& 'vouchers' == @$_GET['page']
			&& '' == @$_GET['preview']
			&& @$_POST
			&& is_array( $_POST )
			&& count( $_POST ) > 0 ) {

		voucherpress_include( 'classes/class-voucher.php' );

        // if deleting the voucher
		if ( isset( $_POST['delete'] ) ) {

            // delete the voucher
			$voucher = new VoucherPress_Voucher();
			$voucher->id = (int)$_GET['id'];
			$voucher->blog_id = voucherpress_blog_id();
			$voucher->user_id = voucherpress_user_id();
			$done = $voucher->Delete();

            // redirect based on whether the delete was successful
			if ( $done ) {

				header( 'Location: admin.php?page=vouchers&id=' . $_GET['id'] . '&result=4' );
				exit();

			} else {

				header( 'Location: admin.php?page=vouchers&id=' . $_GET['id'] . '&result=5' );
				exit();
			}
		}

        // set up the voucher properties

		$require_email = 0;
		if ( isset( $_POST['requireemail'] ) && '1' == $_POST['requireemail'] )
			$require_email = 1;

		$limit = 0;
		if ( $_POST['limit'] != '' && '0' != $_POST['limit'] )
			$limit = (int)$_POST['limit'];

		// get the start date
		$startdate = 0;
		if ( '' != $_POST['startyear']
				&& '0' != $_POST['startyear']
				&& '' != $_POST['startmonth']
				&& '0' != $_POST['startmonth']
				&& '' != $_POST['startday']
				&& '0' != $_POST['startday'] )
			$startdate = strtotime( $_POST['startyear'] . '/' . $_POST['startmonth'] . '/' . $_POST['startday']);

		// get the expiry
		$expiry = 0;
		if ( '' != $_POST['expiryyear']
				&& '0' != $_POST['expiryyear']
				&& '' != $_POST['expirymonth']
				&& '0' != $_POST['expirymonth']
				&& '' != $_POST['expiryday']
				&& '0' != $_POST['expiryday'] )
			$expiry = strtotime( $_POST['expiryyear'] . '/' . $_POST['expirymonth'] . '/' . $_POST['expiryday']);

		if ( '' != $_POST['expirydays'] && is_numeric( $_POST['expirydays'] ) > 0 )
			$expiry = time() + ( intval( $_POST['expirydays'] ) * 24 * 60 * 60 );

		if ( '' != $_POST['expirydays']
				&& is_numeric( $_POST['expirydays'] ) > 0
				&& $startdate > 0 )
			$expiry = $startdate + ( intval( $_POST['expirydays'] ) * 24 * 60 * 60 );

		// get the code type
		if ( 'random' == $_POST['codestype']
				|| 'sequential' == $_POST['codestype']
				|| 'custom' == $_POST['codestype']
				|| 'single' == $_POST['codestype'] ) {
			$codestype = $_POST['codestype'];
		} else {
			$codestype = 'random';
		}

		if ( '' != $_POST['codelength'] )
			$codelength = (int)$_POST['codelength'];

		if ( '' == $codelength || 0 == $codelength )
			$codelength = 6;

		$codeprefix = trim( $_POST['codeprefix'] );
		if ( strlen( $codeprefix ) > 6 )
			$codeprefix = substr( $codeprefix, 6 );

		$codesuffix = trim( $_POST['codesuffix'] );

		if ( strlen( $codesuffix ) > 6 )
			$codesuffix = substr( $codesuffix, 6 );

		$codes = '';
		if ( 'custom' == $_POST['codestype'] )
			$codes = trim( $_POST['customcodelist'] );

		if ( 'single' == $_POST['codestype'] )
			$codes = trim( $_POST['singlecodetext'] );

        // save the voucher
		$voucher = new VoucherPress_Voucher();
		$voucher->id = (int)$_GET['id'];
		$voucher->name = $_POST['name'];
		$voucher->require_email = $require_email;
		$voucher->limit = $limit;
		$voucher->html = $_POST['html'];
		$voucher->description = $_POST['description'];
		$voucher->template= $_POST['template'];
		$voucher->live = $live;
		$voucher->startdate = $startdate;
		$voucher->expiry = $expiry;
		$voucher->codestype = $codestype;
		$voucher->codelength = $codelength;
		$voucher->codeprefix = $codeprefix;
		$voucher->codesuffix = $codesuffix;
		$voucher->codes = $codes;
		$voucher->blog_id = voucherpress_blog_id();
		$voucher->user_id = voucherpress_user_id();
		$done = $voucher->Save();

        // redirect based on whether the save was successful
		if ( $done ) {

			header( 'Location: admin.php?page=vouchers&id=' . $_GET['id'] . '&result=3' );
			exit();

		} else {

			header( 'Location: admin.php?page=vouchers&id=' . $_GET['id'] . '&result=2' );
			exit();
		}
	}
}

// listen for debugging of a voucher
function voucherpress_check_debug_voucher() {

    // verify the nonce and check the querystring parameters are correct
	if ( 'vouchers' == @$_GET['page']
			&& 'true' == @$_GET['debug']
			&& '' != @$_GET['id'] ) {

		voucherpress_include( 'classes/class-voucher.php' );

        // try to get the voucher by id
		$voucher = new VoucherPress_Voucher();
		$loaded = $voucher->Load( (int)$_GET['id'], 0 );

        // if the voucher could be loaded
		if ( $loaded ) {

            // include the download manager
			voucherpress_include( 'managers/manager-downloads.php' );

            // set HTTP headers
			header( 'Content-type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="voucher-debug.csv"' );

            // write out the details of the voucher
			echo 'ID,Name,Text,Terms,Font,Template,Require Email,Limit,Expiry,GUID,Live\n';
			echo '"' . $voucher->id . '","' .
				$voucher->name . '","' .
				$voucher->text . '","' .
				$voucher->terms . '","' .
				$voucher->font . '","' .
				$voucher->template . '","' .
				$voucher->require_email . '","' .
				$voucher->limit . '","' .
				$voucher->expiry . '","' .
				$voucher->guid . '","' .
				$voucher->live . '"\n\n';

            // create the download manager
			$downloadManager = new VoucherPress_DownloadManager();

            // get downloads for this voucher
			$downloads = $downloadManager->GetVoucherDownloads( $_GET['id'] );

            // if there are downloads
			if ( $downloads && is_array($downloads) && $downloads > 0 ) {

                // write out the downloads
				echo 'Datestamp,Email,Name,Code,GUID,Downloaded\n';
				foreach( $downloads as $download ) {
					echo '"' . date('r', $download->time) . '","' .
						$download->email . '","' .
						$download->name . '","' .
						$download->code . '","' .
						$download->guid . '","' .
						$download->downloaded . '"\n';
				}
			}
			exit();

		} else {
			voucherpress_404();
		}
	}
}

// download a list of email addresses
function voucherpress_download_emails( $voucherid = 0 ) {

    // include and set up the download manager for this voucher
	voucherpress_include( 'managers/manager-downloads.php' );
	$downloadManager = new VoucherPress_DownloadManager();
	$emails = $downloadManager->GetVoucherDownloads( $voucherid );

    // if emails have been supplied
	if ( $emails && is_array( $emails ) && count( $emails ) > 0 ) {

        // set HTTP headers
		header( 'Content-type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="voucher-emails.csv"' );

        // write out the email details
		echo 'Voucher,Datestamp,Name,Email,Code\n';
		foreach( $emails as $email ) {
			echo '"' . htmlspecialchars( $email->voucher_name ) . '","' .
				str_replace( ',', '', date( 'r', $email->time ) ) . '","' .
				htmlspecialchars( $email->name ) . '","' .
				htmlspecialchars( $email->email ) . '","' .
				htmlspecialchars( $email->code ) . '"\n';
		}
		exit();
	} else {
		return false;
	}
}

// preview a voucher
function voucherpress_preview_voucher( $template, $font, $name, $html ) {

    // include the voucher class
	voucherpress_include( 'classes/class-voucher.php' );

    // set up the voucher details
	$voucher = new VoucherPress_Voucher();
	$voucher->template = $template;
	$voucher->font = $font;
	$voucher->name = $name;
	$voucher->html = $html;

    // render the voucher with placeholder text for the code
	$voucher->Render( '[' . __( 'Voucher code inserted here', 'voucherpress' ) . ']' );
}

// test a voucher
function voucherpress_test_voucher( $width, $height, $html ) {

    // include the voucher class
	voucherpress_include( 'classes/class-voucher.php' );

    // include the voucher class
	$voucher = new VoucherPress_Voucher();
	$voucher->html = $html;
	$voucher->template = 1;
	$voucher->font = 'times';

    // test the voucher at the given width and height
	$voucher->Test( $width, $height );
}

// ==========================================================================================
// person functions

// show the registration form
function voucherpress_register_form( $voucher_guid, $plain = false ) {
	voucherpress_include( 'public_pages/register.php' );
	return voucherpress_do_register_form( $voucher_guid, $plain );
}

// ==========================================================================================
// initialisation functions


// set activation hook
register_activation_hook( __FILE__, 'voucherpress_activate' );
register_deactivation_hook( __FILE__, 'voucherpress_deactivate' );

// initialise the plugin
voucherpress_init();

// initialise the plugin
function voucherpress_init() {

    // check we're not in a strange world with no add_action function
	if ( function_exists( 'add_action' ) ) {

		// add init action
		add_action( 'init', 'voucherpress_prepare' );

		// add template redirect action
		add_action( 'template_redirect', 'voucherpress_template_redirect_handler' );

		// add the admin menu
		add_action( 'admin_menu', 'voucherpress_add_admin' );

		// add the create voucher function
		add_action( 'admin_menu', 'voucherpress_check_create_voucher' );

		// add the edit voucher function
		add_action( 'admin_menu', 'voucherpress_check_edit_voucher' );

		// add the debug voucher function
		add_action( 'admin_menu', 'voucherpress_check_debug_voucher' );

		// add the admin preview function
		add_action( 'admin_menu', 'voucherpress_check_preview' );

		// add the debug test function
		add_action( 'admin_menu', 'voucherpress_check_test' );

		// add the admin email download function
		add_action( 'admin_menu', 'voucherpress_check_download' );

		// add the admin head includes
		if ( 'vouchers' == substr( @$_GET['page'], 0, 8 ) ) {
			add_action( 'admin_head', 'voucherpress_admin_css' );
			add_action( 'admin_head', 'voucherpress_admin_js' );
		}

		// setup shortcodes
		if ( !is_admin() ) {
			//add_filter('widget_text', 'voucher_do_voucher_shortcode', 11);
			//add_filter('widget_text', 'voucher_do_voucher_form_shortcode', 11);
			//add_filter('widget_text', 'voucher_do_list_shortcode', 11);
		}

		// [voucher id='' preview='']
		add_shortcode( 'voucher', 'voucher_do_voucher_shortcode' );

		// [voucherform id='']
		add_shortcode( 'voucherform', 'voucher_do_voucher_form_shortcode' );

		// [voucherlist]
		add_shortcode( 'voucherlist', 'voucher_do_list_shortcode' );

		// [voucherdownloads id='']
		add_shortcode( 'voucherdownloads', 'voucher_do_downloads_shortcode' );
	}
}

// prepare the VoucherPress environment by loading translations
function voucherpress_prepare() {
	$plugin_dir = basename( dirname( __FILE__ ) );
	load_plugin_textdomain( 'voucherpress', null, $plugin_dir );
}

// handles template redirecting to load VoucherPress items
function voucherpress_template_redirect_handler() {

	// if requesting a voucher
	if ( isset( $_GET['voucher'] ) && '' != $_GET['voucher'] ) {

        // include the voucher class
		voucherpress_include( 'classes/class-voucher.php' );

		// get the details
		$voucher_guid = $_GET['voucher'];
		$download_guid = @$_GET['guid'];

        // load the voucher
		$voucher = new VoucherPress_Voucher();
		$loaded = $voucher->Load( $voucher_guid );

		// check the template exists
		if ( $loaded ) {

			if ( 'unregistered' != $voucher->DownloadGuidValid( $download_guid ) ) {
				$voucher->Download( $download_guid );
			} else {
				// show the form to register for this voucher
				voucherpress_register_form( $voucher_guid );
			}
			exit();
		}

        // respond with a 404
		voucherpress_404();
	}
}

// ==========================================================================================
// plugin register functions

// a standard header for your plugins, offers a PayPal donate button and link to a support page
function voucherpress_wp_plugin_standard_header( $currency = '',
	$plugin_name = '',
	$author_name = '',
	$paypal_address = '',
	$bugs_page ) {

	$r = '';
	$option = get_option( $plugin_name . ' header' );

	if ( ( isset( $_GET[ 'header' ] ) && '' != $_GET[ 'header' ] )
			|| ( isset( $_GET['thankyou'] ) && 'true' == $_GET['thankyou'] ) ) {

		update_option( $plugin_name . ' header', 'hide' );
		$option = 'hide';
	}

	if ( isset( $_GET['thankyou'] ) && 'true' == $_GET['thankyou'] ) {
		$r .= '<div class="updated"><p>' . __( 'Thank you for donating' ) . '</p></div>';
	}

	if ( '' != $currency
			&& '' != $plugin_name
			&& ( !isset( $_GET['header'] ) || 'hide' != $_GET[ 'header' ] )
			&& $option != 'hide' ) {

		$r .= '<div class="updated">';

		$pageURL = 'http';
		if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' )
			$pageURL .= 's';

		$pageURL .= '://';

		if ( $_SERVER['SERVER_PORT'] != '80' ) {
			$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}

		if ( strpos( $pageURL, '?') === false ) {
			$pageURL .= '?';
		} else {
			$pageURL .= '&';
		}

		$pageURL = htmlspecialchars( $pageURL );

		if ( $bugs_page != '' ) {
			$r .= '<p>' . sprintf ( __( 'To report bugs please visit <a href="%s">%s</a>.' ), $bugs_page, $bugs_page ) . '</p>';
		}

		if ( $paypal_address != '' && is_email( $paypal_address ) ) {
			$r .= "
			<form id='wp_plugin_standard_header_donate_form' action='https://www.paypal.com/cgi-bin/webscr' method='post'>
			<input type='hidden' name='cmd' value='_donations' />
			<input type='hidden' name='item_name' value='Donation: $plugin_name' />
			<input type='hidden' name='business' value='$paypal_address' />
			<input type='hidden' name='no_note' value='1' />
			<input type='hidden' name='no_shipping' value='1' />
			<input type='hidden' name='rm' value='1' />
			<input type='hidden' name='currency_code' value='$currency'>
			<input type='hidden' name='return' value='{$pageURL}thankyou=true' />
			<input type='hidden' name='bn' value='PP-DonationsBF:btn_donateCC_LG.gif:NonHosted' />
			<p>";

			if ( $author_name != '' ) {
				$r .= sprintf( __( 'If you found %1$s useful please consider donating to help %2$s to continue writing free Wordpress plugins.' ), $plugin_name, $author_name );
			} else {
				$r .= sprintf( __( 'If you found %s useful please consider donating.' ), $plugin_name );
			}

			$r .= '
			<p><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="" /></p>
			</form>
			';
		}
		$r .= "<p><a href='{$pageURL}header=hide' class='button'>" . __( 'Hide this') . "</a></p>";
		$r .= '</div>';
	}
	print $r;
}
function voucherpress_wp_plugin_standard_footer( $currency = '', $plugin_name = '', $author_name = '', $paypal_address = '', $bugs_page ) {
	$r = '';
	if ( $currency != '' && $plugin_name != '' ) {

		$r .= '<form id="wp_plugin_standard_footer_donate_form" action="https://www.paypal.com/cgi-bin/webscr" method="post" style="clear:both;padding-top:50px;">';

		$pageURL = 'http';
		if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' )
			$pageURL .= 's';

		$pageURL .= '://';

		if ( $_SERVER['SERVER_PORT'] != '80' ) {
			$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}

		if ( strpos( $pageURL, '?') === false ) {
			$pageURL .= '?';
		} else {
			$pageURL .= '&';
		}

		$pageURL = htmlspecialchars( $pageURL );

		if ( $paypal_address != '' && is_email( $paypal_address ) ) {
			$r .= "
			<input type='hidden' name='cmd' value='_donations' />
			<input type='hidden' name='item_name' value='Donation: $plugin_name' />
			<input type='hidden' name='business' value='$paypal_address' />
			<input type='hidden' name='no_note' value='1' />
			<input type='hidden' name='no_shipping' value='1' />
			<input type='hidden' name='rm' value='1' />
			<input type='hidden' name='currency_code' value='$currency' />
			<input type='hidden' name='return' value='{$pageURL}thankyou=true' />
			<input type='hidden' name='bn' value='PP-DonationsBF:btn_donateCC_LG.gif:NonHosted' />
			Please consider sending a donation to encourage me to keep working on this plugin:
			<input type='submit' class='button-primary' value='Donate via PayPal' />
			";
		}

		if ( $bugs_page != '' ) {
			$r .= sprintf ( __( '<a href="%s" class="button">Get support</a>' ), $bugs_page );
		}

		$r .= '</form>';
	}
	print $r;
}

// include the plugin register code
require_once( 'plugin-register.class.php' );
$register = new Plugin_Register();
$register->file = __FILE__;
$register->slug = 'voucherpress';
$register->name = 'VoucherPress';
$register->version = voucherpress_current_version();
$register->developer = 'Chris Taylor';
$register->homepage = 'http://www.stillbreathing.co.uk';
$register->Plugin_Register();
?>