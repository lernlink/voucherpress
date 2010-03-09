<?php
/**
 * @package VoucherPress
 * @author Chris Taylor
 * @version 0.6
 */
/*
Plugin Name: VoucherPress
Plugin URI: http://www.stillbreathing.co.uk/projects/voucherpress/
Description: VoucherPress allows you to offer downloadable, printable vouchers from your Wordpress site. Vouchers can be available to anyone, or require a name and email address before they can be downloaded.
Author: Chris Taylor
Version: 0.6
Author URI: http://www.stillbreathing.co.uk/
*/

// set the current version
function voucherpress_current_version() {
	return "0.6";
}

// set activation hook
register_activation_hook( __FILE__, voucherpress_activate );
register_deactivation_hook( __FILE__, voucherpress_deactivate );

// initialise the plugin
voucherpress_init();

// ==========================================================================================
// initialisation functions

function voucherpress_init() {
	if ( function_exists( "add_action" ) ) {
		// add template redirect action
		add_action( "template_redirect", "voucherpress_template" );
		// add the admin menu
		add_action( "admin_menu", "voucherpress_add_admin" );
		// add the create voucher function
		add_action( "admin_menu", "voucherpress_check_create_voucher" );
		// add the edit voucher function
		add_action( "admin_menu", "voucherpress_check_edit_voucher" );
		// add the admin preview function
		add_action( "admin_menu", "voucherpress_check_preview" );
		// add the admin email download function
		add_action( "admin_menu", "voucherpress_check_download" );
		// add the admin head includes
		add_action( "admin_head", "voucherpress_admin_css" );
		add_action( "admin_head", "voucherpress_admin_js" );
		// setup shortcodes
		// [voucher id="" name="" slug=""]
		add_shortcode( 'voucher', 'voucher_do_voucher_shortcode' );
		// [voucherlist]
		add_shortcode( 'voucherlist', 'voucher_do_list_shortcode' );
	}
}

function voucherpress_template() {
	// if requesting a voucher
	if ( $_GET["voucher"] != "" )
	{
		// get the details
		$voucher_guid = $_GET["voucher"];
		$code = @$_GET["code"];

		// check the template exists
		if ( voucherpress_voucher_exists( $voucher_guid ) ) {
			// if the email addres supplied is valid
			if ( voucherpress_code_is_valid( $voucher_guid, $code ) ) {
				// download the voucher
				voucherpress_download_voucher( $voucher_guid, $code );
			} else {
				// show the form
				voucherpress_register_form( $voucher_guid );
			}
			exit();
		}
		voucherpress_404();
	}
}

// show a 404 page
function voucherpress_404() {
	global $wp_query;
	$wp_query->set_404();
	if ( file_exists( TEMPLATEPATH.'/404.php' ) ) {
		require TEMPLATEPATH.'/404.php';
	} else {
		wp_die( __( "Sorry, that item was not found", "voucherpress" ) );
	}
	exit();
}

// ==========================================================================================
// activation functions

// activate the plugin
function voucherpress_activate() {
	// if PHP is less than version 5
	if ( version_compare( PHP_VERSION, '5.0.0', '<' ) )
	{
		echo '
		<div id="message" class="error">
			<p><strong>' . __( "Sorry, your PHP version must be 5 or above. Please contact your server administrator for help.", "voucherpress" ) . '</strong></p>
		</div>
		';
	} else {
		// create tables
		voucherpress_create_tables();
		// save options
		$data = array(
			"register_title" => "Enter your email address",
			"register_message" => "You must supply your name and email address to download this voucher. Please enter your details below, a link will be sent to your email address for you to download the voucher.",
			"email_label" => "Your email address",
			"name_label" => "Your name",
			"button_text" => "Request voucher",
			"bad_email_message" => "Sorry, your email address seems to be invalid. Please try again.",
			"thanks_message" => "Thank you, a link has been sent to your email address for you to download this voucher.",
			"voucher_not_found_message" => "Sorry, the voucher you are looking for cannot be found."
			);
		// add options
		add_option ( "voucherpress_data", maybe_serialize( $data ) );
		add_option ( "voucherpress_version", voucherpress_current_version() );
	}
}

// deactivate the plugin
function voucherpress_deactivate() {
	// delete options
	delete_option( "voucherpress_data" );
	delete_option( "voucherpress_version" );
}

// get the currently installed version
function voucherpress_get_version() {
	if ( function_exists( "get_site_option" ) ) {
		return get_site_option( "voucherpress_version" );
	} else {
		return get_option( "voucherpress_version" );
	}
}

// update the currently installed version
function voucherpress_update_version() {
	$version = voucherpress_current_version();
	if ( function_exists( "get_site_option" ) ) {
		update_site_option( "voucherpress_version", $version );
	} else {
		return update_option( "voucherpress_version", $version );
	}
}

// create the tables
function voucherpress_create_tables() {

	// get current voucherpress version
	$this_version = voucherpress_get_version();
	$current_version = voucherpress_current_version();
	
	if ( version_compare( $this_version, $current_version, "<" ) )
	{
	
		global $wpdb;
		$prefix = $wpdb->prefix;
		if ( $wpdb->base_prefix != "" ) { $prefix = $wpdb->base_prefix; }

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		// table to store the vouchers
		$sql = "CREATE TABLE " . $prefix . "voucherpress_vouchers (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  blog_id mediumint NOT NULL,
			  time bigint(11) DEFAULT '0' NOT NULL,
			  name VARCHAR(50) NOT NULL,
			  `text` varchar(250) NOT NULL,
			  terms varchar(500) NOT NULL,
			  template varchar(55) NOT NULL,
			  font varchar(55) DEFAULT 'helvetica' NOT NULL,
			  require_email TINYINT DEFAULT 1 NOT NULL,
			  `limit` MEDIUMINT(9) NOT NULL DEFAULT 0,
			  guid varchar(36) NOT NULL,
			  live TINYINT DEFAULT '0',
			  PRIMARY KEY  id (id)
			);";
		dbDelta( $sql );
		
		// table to store downloads
		$sql = "CREATE TABLE " . $prefix . "voucherpress_downloads (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  voucherid mediumint(9) NOT NULL,
			  time bigint(11) DEFAULT '0' NOT NULL,
			  ip VARCHAR(15) NOT NULL,
			  name VARCHAR(55) NULL,
			  email varchar(255) NULL,
			  guid varchar(36) NOT NULL,
			  downloaded TINYINT DEFAULT '0',
			  PRIMARY KEY  id (id)
			);";
		dbDelta( $sql );
		
		// table to store templates
		$sql = "CREATE TABLE " . $prefix . "voucherpress_templates (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  blog_id mediumint NOT NULL,
			  time bigint(11) DEFAULT '0' NOT NULL,
			  name VARCHAR(55) NOT NULL,
			  live tinyint DEFAULT '1',
			  PRIMARY KEY  id (id)
			);";
		dbDelta( $sql );
		
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Plain black border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Mint chocolate', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Red floral border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Single red rose (top left)', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Red flowers', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Pink flowers', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Abstract green bubbles', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('International post', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Gold ribbon', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Monochrome bubble border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Colourful swirls', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Red gift bag', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Blue ribbon', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Autumn floral border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Yellow gift boxes', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Wrought iron border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Abstract rainbow flowers', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Christmas holly border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Small gold ribbon', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Small red ribbon', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('White gift boxes', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Glass flowers border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Single red rose (bottom centre)', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Fern border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Blue floral watermark', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Monochrome ivy border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Ornate border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Winter flower corners', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Spring flower corners', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Pattern border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Orange flower with bar', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Small coat of arms', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Grunge border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Coffee beans', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Blue gift boxes', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Spring flowers border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Ornate magenta border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Mexico border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Chalk border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Thick border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Dark chalk border', 1, 0);");
		$wpdb->query("insert into " . $prefix . "voucherpress_templates (name, live, blog_id) values ('Ink border', 1, 0);");
	
		// update the version
		voucherpress_update_version();
	
	}

}

// ==========================================================================================
// general admin function

// add the menu items
function voucherpress_add_admin()
{
	add_menu_page( __( "Vouchers", "voucherpress" ), __( "Vouchers", "voucherpress" ), 1, "vouchers", "vouchers_admin" ); 
	add_submenu_page( "vouchers", __( "Create a voucher", "voucherpress" ), __( "Create", "voucherpress" ), 1, "vouchers-create", "voucherpress_create_voucher_page" );
	add_submenu_page( "vouchers", __( "Voucher reports", "voucherpress" ), __( "Reports", "voucherpress" ), 1, "vouchers-reports", "voucherpress_reports_page" ); 
	add_submenu_page( "vouchers", __( "Voucher templates", "voucherpress" ), __( "Templates", "voucherpress" ), 1, "vouchers-templates", "voucherpress_templates_page" ); 
	
	// for WPMU site admins
	if ( function_exists( 'is_site_admin' ) && is_site_admin() ) {
		add_submenu_page('wpmu-admin.php', __('Vouchers'), __('Vouchers'), 10, 'voucherpress-admin', 'voucherpress_site_admin');
	}
}

// show the general site admin page
function voucherpress_site_admin()
{
	voucherpress_report_header();
	
	echo '<h2>' . __( "Vouchers", "voucherpress" ) . '</h2>';
	
	echo '<div class="voucherpress_col1">
	<h3>' . __( "25 most recent vouchers", "voucherpress" ) . '</h3>
	';
	$vouchers = voucherpress_get_all_vouchers( 25, 0 );
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
	$vouchers = voucherpress_get_all_popular_vouchers( 25, 0 );
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
}

// show the general admin page
function vouchers_admin()
{
	voucherpress_report_header();
	
	// if a voucher has not been chosen
	if ( !isset( $_GET["id"] ) )
	{
	
		echo '<h2>' . __( "Vouchers", "voucherpress" ) . '</h2>';
		
		echo '<div class="voucherpress_col1">
		<h3>' . __( "Your vouchers", "voucherpress" ) . '</h3>
		';
		$vouchers = voucherpress_get_vouchers( 10, true );
		if ( $vouchers && is_array( $vouchers ) && count( $vouchers ) > 0 )
		{
			voucherpress_table_header( array( "Name", "Downloads", "Email required" ) );
			foreach( $vouchers as $voucher )
			{
				echo '
				<tr>
					<td><a href="admin.php?page=vouchers&amp;id=' . $voucher->id . '">' . $voucher->name . '</a></td>
					<td>' . $voucher->downloads . '</td>
					<td>' . voucherpress_yes_no( $voucher->require_email ) . '</td>
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
		<h3>' . __( "Popular vouchers", "voucherpress" ) . '</h3>
		';
		$vouchers = voucherpress_get_popular_vouchers();
		if ( $vouchers && is_array( $vouchers ) && count( $vouchers ) > 0 )
		{
			voucherpress_table_header( array( "Name", "Downloads", "Email required" ) );
			foreach( $vouchers as $voucher )
			{
				echo '
				<tr>
					<td><a href="admin.php?page=vouchers&amp;id=' . $voucher->id . '">' . $voucher->name . '</a></td>
					<td>' . $voucher->downloads . '</td>
					<td>' . voucherpress_yes_no( $voucher->require_email ) . '</td>
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
		<p><a href="admin.php?page=vouchers&amp;download=emails">' . __( "Download all registered email addresses", "voucherpress" ) . '</a></p>
		</div>';
	
	// if a voucher has been chosen
	} else {
	
		voucherpress_edit_voucher_page();
	
	}
	
	voucherpress_report_footer();
}

// show the create voucher page
function voucherpress_create_voucher_page()
{
	voucherpress_report_header();
	
	echo '
	<h2>' . __( "Create a voucher", "voucherpress" ) . '</h2>
	';
	
	if ( @$_GET["result"] != "" ) {
		if ( @$_GET["result"] == "1" ) {
			echo '
			<div id="message" class="error">
				<p><strong>' . __( "Sorry, your voucher could not be created. Please click back and try again.", "voucherpress" ) . '</strong></p>
			</div>
			';
		}
	}
	
	echo '
	<form action="admin.php?page=vouchers-create" method="post" id="voucherform">
	
	<div id="voucherpreview">
	
		<h2><textarea name="name" id="name" rows="2" cols="100">' . __( "Voucher name (30 characters)", "voucherpress" ) . '</textarea></h2>
		<p><textarea name="text" id="text" rows="3" cols="100">' . __( "Type the voucher text here (200 characters)", "voucherpress" ) . '</textarea></p>
		<p>[' . __( "Unique voucher code here", "voucherpress" ) . ']</p>
		<p id="voucherterms"><textarea name="terms" id="terms" rows="4" cols="100">' . __( "Type the voucher terms and conditions here (300 characters)", "voucherpress" ) . '</textarea></p>
	
	</div>
	
	';
	$fonts = voucherpress_fonts();
	echo '
	<h3>' . __( "Font", "voucherpress" ) . '</h3>
	<p><label for="font">' . __( "Font", "voucherpress" ) . '</label>
	<select name="font" id="font">
	';
	foreach ( $fonts as $font )
	{
		echo '
		<option value="' . $font[0] . '">' . $font[1] . '</option>
		';
	}
	echo '
	</select> <span>' . __( "Set the font for this voucher", "voucherpress" ) . '</span></p>
	';
	$templates = voucherpress_get_templates();
	if ( $templates && is_array( $templates ) && count( $templates ) > 0 )
	{
		echo '
		<h3>' . __( "Template", "voucherpress" ) . '</h3>
		<div id="voucherthumbs">
		';
		foreach( $templates as $template )
		{
			echo '
			<span><img src="' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/templates/' . $template->id . '_thumb.jpg" id="template_' . $template->id . '" alt="' . $template->name . '" /></span>
			';
		}
		echo '
		</div>
		';
	} else {
		echo '
		<p>' . __( "Sorry, no templates found", "voucherpress" ) . '</p>
		';
	}
	
	echo '
	<h3>' . __( "Settings", "voucherpress" ) . '</h3>
	<p><label for="requireemail">' . __( "Require email address", "voucherpress" ) . '</label>
	<input type="checkbox" name="requireemail" id="requireemail" value="1" /> <span>' . __( "Tick this box to require a valid email address to be given before this voucher can be downloaded", "voucherpress" ) . '</span></p>
	<p><label for="limit">' . __( "Number of vouchers available", "voucherpress" ) . '</label>
	<input type="text" name="limit" id="limit" class="num" value="" /> <span>' . __( "Set the number of times this voucher can be downloaded (leave blank or 0 for unlimited)", "voucherpress" ) . '</span></p>
	<p><input type="button" name="preview" id="previewbutton" class="button" value="' . __( "Preview", "voucherpress" ) . '" />
	<input type="submit" name="save" id="savebutton" class="button-primary" value="' . __( "Save", "voucherpress" ) . '" />
	<input type="hidden" name="template" id="template" value="1" /></p>
	
	</form>
	';
	
	voucherpress_report_footer();
}

// show the edit voucher page
function voucherpress_edit_voucher_page()
{
	$voucher = voucherpress_get_voucher( @$_GET["id"], 0 );
	if ( $voucher && is_object( $voucher ) )
	{
		echo '
		<h2>' . __( "Edit voucher:", "voucherpress" ) . ' ' . htmlspecialchars( stripslashes( $voucher->name ) ) . '</h2>
		
		<h3>' . __( "Shortcode for this voucher:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot;]" /> = <a href="' . voucherpress_link( $voucher->guid ) . '">' . htmlspecialchars( stripslashes( $voucher->name ) ) . '</a></h3>
		
		<h3>' . __( "Shortcode for this voucher:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot; preview=&quot;true&quot;]" /> = <a href="' . voucherpress_link( $voucher->guid ) . '"><img src="' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/templates/' . $voucher->template . '_thumb.jpg" alt="' . htmlspecialchars( stripslashes( $voucher->name ) ) . '" /></a></h3>
		
		<h3>' . __( "Link for this voucher:", "voucherpress" ) . ' <input type="text" value="' . voucherpress_link( $voucher->guid ) . '" /></h3>
		';
		
		if ( $voucher->downloads > 0 ) {
			echo '
			<p>' . __( "Downloads:", "voucherpress" ) . " " . $voucher->downloads . '.';
			if ( $voucher->require_email == "1" ) {
				echo ' <a href="admin.php?page=vouchers&amp;download=emails&amp;voucher=' . $voucher->id . '">' . __( "Download registered email addresses here.", "voucherpress" ) . '</a>';
			}
			echo '</p>
			';
		}
		
		if ( @$_GET["result"] != "" ) {
			if ( @$_GET["result"] == "1" ) {
				echo '
				<div id="message" class="updated fade">
					<p><strong>' . __( "Your voucher has been created.", "voucherpress" ) . '</strong></p>
				</div>
				';
			}
			if ( @$_GET["result"] == "2" ) {
				echo '
				<div id="message" class="error">
					<p><strong>' . __( "Sorry, your voucher could not be edited.", "voucherpress" ) . '</strong></p>
				</div>
				';
			}
			if ( @$_GET["result"] == "3" ) {
				echo '
				<div id="message" class="updated fade">
					<p><strong>' . __( "Your voucher has been edited.", "voucherpress" ) . '</strong></p>
				</div>
				';
			}
		}
		
		echo '
		<form action="admin.php?page=vouchers&amp;id=' . $_GET["id"] . '" method="post" id="voucherform">
		
		<div id="voucherpreview" style="background-image:url(' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/templates/' . $voucher->template . '_preview.jpg)">
		
			<h2><textarea name="name" id="name" rows="2" cols="100">' . stripslashes( $voucher->name ) . '</textarea></h2>
			<p><textarea name="text" id="text" rows="3" cols="100">' . stripslashes( $voucher->text ) . '</textarea></p>
			<p>[' . __( "The unique voucher code will be inserted automatically here", "voucherpress" ) . ']</p>
			<p id="voucherterms"><textarea name="terms" id="terms" rows="4" cols="100">' . stripslashes( $voucher->terms ) . '</textarea></p>
		
		</div>
		
		';
		$fonts = voucherpress_fonts();
		echo '
		<h3>' . __( "Font", "voucherpress" ) . '</h3>
		<p><label for="font">' . __( "Font", "voucherpress" ) . '</label>
		<select name="font" id="font">
		';
		foreach ( $fonts as $font )
		{
			if ( $voucher->font == $font[0] ) {
				$selected = ' selected="selected"';
			}
			echo '
			<option value="' . $font[0] . '"' . $selected . '>' . $font[1] . '</option>
			';
			$selected  = "";
		}
		echo '
		</select> <span>' . __( "Set the font for this voucher", "voucherpress" ) . '</span></p>
		';
		$templates = voucherpress_get_templates();
		if ( $templates && is_array( $templates ) && count( $templates ) > 0 )
		{
			echo '
			<h3>' . __( "Template", "voucherpress" ) . '</h3>
			<div id="voucherthumbs">
			';
			foreach( $templates as $template )
			{
				echo '
				<span><img src="' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/templates/' . $template->id . '_thumb.jpg" id="template_' . $template->id . '" alt="' . $template->name . '" /></span>
				';
			}
			echo '
			</div>
			';
		} else {
			echo '
			<p>' . __( "Sorry, no templates found", "voucherpress" ) . '</p>
			';
		}
		
		echo '
		<h3>' . __( "Settings", "voucherpress" ) . '</h3>
		<p><label for="requireemail">' . __( "Require email address", "voucherpress" ) . '</label>
		<input type="checkbox" name="requireemail" id="requireemail" value="1"';
		if ( $voucher->require_email == "1" ) {
			echo ' checked="checked"';
		}
		echo '/> <span>' . __( "Tick this box to require a valid email address to be given before this voucher can be downloaded", "voucherpress" ) . '</span></p>
		';
		if ( $voucher->limit == "0" ) {
			$voucher->limit = "0";
		}
		echo '
		<p><label for="limit">' . __( "Number of vouchers available", "voucherpress" ) . '</label>
		<input type="text" name="limit" id="limit" class="num" value="' . $voucher->limit . '" /> <span>' . __( "Set the number of times this voucher can be downloaded (leave blank for unlimited)", "voucherpress" ) . '</span></p>
		<p><label for="live">' . __( "Voucher available", "voucherpress" ) . '</label>
		<input type="checkbox" name="live" id="live" value="1"';
		if ( $voucher->live == "1" ) {
			echo ' checked="checked"';
		}
		echo '/> <span>' . __( "Tick this box to allow this voucher to be downloaded", "voucherpress" ) . '</span></p>
		<p><input type="button" name="preview" id="previewbutton" class="button" value="' . __( "Preview", "voucherpress" ) . '" />
		<input type="submit" name="save" id="savebutton" class="button-primary" value="' . __( "Save", "voucherpress" ) . '" />
		<input type="hidden" name="template" id="template" value="' . $voucher->template . '" /></p>
		
		</form>
		';
		
	} else {
		echo '
		<h2>' . __( "Voucher not found", "voucherpress" ) . '</h2>
		<p>' . __( "Sorry, that voucher was not found.", "voucherpress" ) . '</p>
		';
	}
}

// show the voucher reports page
function voucherpress_reports_page()
{
	voucherpress_report_header();
	
	echo '
	<h2>' . __( "Voucher reports", "voucherpress" ) . '</h2>

	';
	
	voucherpress_report_footer();
}

// show the templates page
function voucherpress_templates_page()
{
	voucherpress_report_header();
	
	echo '
	<h2>' . __( "Voucher templates", "voucherpress" ) . '</h2>
	';
	
	// get templates
	$templates = voucherpress_get_templates();
	
	// if submitting a form
	if ( $_POST && is_array( $_POST ) && count( $_POST) > 0 )
	{
		// if updating templates
		if ( @$_POST["action"] == "update" )
		{
			// loop templates
			foreach( $templates as $template )
			{
				$live = 1;
				if ( @$_POST["delete" . $template->id] == "1" ) {
					$live = 0;
				}
				// edit this template
				voucherpress_edit_template( $template->id, @$_POST["name" . $template->id], $live );
			}
			
			// get the new templates
			$templates = voucherpress_get_templates();
			
			echo '
			<div id="message" class="updated fade">
				<p><strong>' . __( "Templates updated", "voucherpress" ) . '</strong></p>
			</div>
			';
		}
		// if adding a template
		if ( @$_POST["action"] == "add" )
		{

			if ( @$_FILES && is_array( $_FILES ) && count( $_FILES ) > 0 && $_FILES["file"]["name"] != "" && (int)$_FILES["file"]["size"] > 0 )
			{
				// check the GD functions exist
				if ( function_exists( "imagecreatetruecolor" ) && function_exists( "getimagesize" ) && function_exists( "imagejpeg" ) ) 
				{
				
					$name = $_POST["name"];
					if ( $name == "" ) { $name = "New template " . date( "F j, Y, g:i a" ); }
				
					// try to save the template name
					$id = voucherpress_add_template( $name );
					
					// if the id can be fetched
					if ( $id )
					{
				
						$uploaded = voucherpress_upload_template( $id, $_FILES["file"] );
						
						if ( $uploaded )
						{
						
							echo '
							<div id="message" class="updated fade">
								<p><strong>' . __( "Your template has been uploaded.", "voucherpress" ) . '</strong></p>
							</div>
							';
							
							// get templates
							$templates = voucherpress_get_templates();
						
						} else {
						
							echo '
							<div id="message" class="error">
								<p><strong>' . __( "Sorry, the template file you uploaded was not in the correct format (JPEG), or was not the correct size (2362 x 1063 pixels). Please upload a correct template file.", "voucherpress" ) . '</strong></p>
							</div>
							';
						
						}
					
					} else {
					
						echo '
						<div id="message" class="error">
							<p><strong>' . __( "Sorry, your template could not be saved. Please try again.", "voucherpress" ) . '</strong></p>
						</div>
						';
					
					}
					
				} else {
					echo '
					<div id="message" class="error">
						<p><strong>' . __( "Sorry, your host does not support GD image functions, so you cannot add your own templates.", "voucherpress" ) . '</strong></p>
					</div>
					';
				}
			} else {
				echo '
				<div id="message" class="error">
					<p><strong>' . __( "Please attach a template file", "voucherpress" ) . '</strong></p>
				</div>
				';
			}
		}
	}
	
	if ( function_exists( "imagecreatetruecolor" ) && function_exists( "getimagesize" ) && function_exists( "imagejpeg" ) ) {
	echo '
	<h3>' . __( "Add a template", "voucherpress" ) . '</h3>
	
	<form action="admin.php?page=vouchers-templates" method="post" enctype="multipart/form-data" id="templateform">
	
	<p>' . __( sprintf( 'To create your own templates use <a href="%s">this empty template</a>.', get_option( "siteurl" ) . "/wp-content/plugins/voucherpress/templates/1.jpg" ), 'voucherpress' ) . '</p>
	
	<p><label for="file">' . __( "Template file", "voucherpress" ) . '</label>
	<input type="file" name="file" id="file" /></p>
	
	<p><label for="name">' . __( "Template name", "voucherpress" ) . '</label>
	<input type="text" name="name" id="name" /></p>
	
	<p><input type="submit" class="button-primary" value="' . __( "Add template", "voucherpress" ) . '" />
	<input type="hidden" name="action" value="add" /></p>
	
	</form>
	';
	} else {
		echo '
		<p>' . __( "Sorry, your host does not support GD image functions, so you cannot add your own templates.", "voucherpress" )  . '</p>
		';
	}
	
	if ( $templates && is_array( $templates ) && count( $templates ) > 0 )
	{
		echo '
		<form id="templatestable" method="post" action="">
		';
		voucherpress_table_header( array( "Preview", "Name", "Delete" ) );
		foreach( $templates as $template )
		{
			echo '
			<tr>
				<td><a href="' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/templates/' . $template->id . '_preview.jpg" class="templatepreview"><img src="' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/templates/' . $template->id . '_thumb.jpg" alt="' . $template->name . '" /></a></td>
				';
				if ( $template->blog_id != "0" )
				{
				echo '
				<td><input type="text" name="name' . $template->id . '" value="' . $template->name . '" /></td>
				<td><input class="checkbox" type="checkbox" value="1" name="delete' . $template->id . '" /></td>
				';
				} else {
				echo '
				<td colspan="2">' . __( "This template cannot be edited", "voucherpress" ) . '</td>
				';
				}
			echo '
			</tr>
			';
		}
		voucherpress_table_footer();
		echo '
		<p><input type="submit" class="button-primary" value="' . __( "Save templates", "voucherpress" ) . '" />
		<input type="hidden" name="action" value="update" /></p>
		</form>
		';
	} else {
		echo '
		<p>' . __( "Sorry, no templates found", "voucherpress" ) . '</p>
		';
	}
	
	voucherpress_report_footer();
}

// include the voucherpress CSS file
function voucherpress_admin_css()
{
	echo '
	<link rel="stylesheet" href="' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/voucherpress.css" type="text/css" media="all" />
	';
}

// include the voucherpress JS file
function voucherpress_admin_js()
{
	echo '
	<script type="text/javascript">
		jQuery(document).ready(function(){
		vp_set_preview_font();
		jQuery("#voucherthumbs img").bind("click", vp_set_preview);
		jQuery(".checkbox").bind("click", vp_set_template_deleted);
		jQuery("#font").bind("change", vp_set_preview_font);
		jQuery("#name").bind("keyup", vp_limit_text);
		jQuery("#text").bind("keyup", vp_limit_text);
		jQuery("#terms").bind("keyup", vp_limit_text);
		jQuery("#previewbutton").bind("click", vp_preview_voucher);
		jQuery("#savebutton").bind("click", vp_save_voucher);
		jQuery("a.templatepreview").bind("click", vp_new_window);
	});
	function vp_new_window(e) {
		jQuery(this).attr("target", "_blank");
	}
	function vp_preview_voucher(e) {
		var form = jQuery("#voucherform");
		form.attr("action", form.attr("action") + "&preview=voucher");
		form.attr("target", "_blank");
		form.submit();
	}
	function vp_save_voucher(e) {
		var form = jQuery("#voucherform");
		form.attr("action", form.attr("action").replace("&preview=voucher", ""));
		form.attr("target", "");
		form.submit();
	}
	function vp_set_preview(e) {
		var id = this.id.replace("template_", "");
		var preview = "url(' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/templates/" + id + "_preview.jpg)";
		jQuery("#voucherpreview").css("background-image", preview);
		jQuery("#template").val(id);
	}
	function vp_set_template_deleted(e) {
		var td = jQuery(this).parent().get(0);
		var tr = jQuery(td).parent().get(0);
		jQuery(tr).toggleClass("deleted");
	}
	function vp_set_preview_font(e) {
		var font = jQuery("#font :selected").val();
		jQuery("#voucherpreview h2 textarea").attr("class", font);
		jQuery("#voucherpreview p textarea").attr("class", font);
		jQuery("#voucherpreview p").attr("class", font);
	}
	function vp_limit_text(e) {
		var limit = 30;
		var el = jQuery(this);
		if (el.attr("id") == "text") limit = 200;
		if (el.attr("id") == "terms") limit = 300;
		var length = el.val().length;
		if (parseFloat(length) >= parseFloat(limit)) {
			// if this is a character key, stop it being entered
			var key = vp_keycode(e) || e.code;
			if (key != 8 && key != 46 && key != 37 && key != 39) {
				el.val(el.val().substr(0, limit));
				e.preventDefault(); e.stopPropagation(); return false;
			}
		}
	}
	// return the keycode for this event
    function vp_keycode(e) {
        if (window.event) {
            return window.event.keyCode;
        } else if (e) {
            return e.which;
        } else {
            return false;
        }
    }
	</script>
	';
}

// to display above every report
function voucherpress_report_header() {
	echo '
	<div id="voucherpress" class="wrap">
	';
	voucherpress_wp_plugin_standard_header( "GBP", "VoucherPress", "Chris Taylor", "chris@stillbreathing.co.uk", "http://wordpress.org/extend/plugins/voucherpress/" );
}

// to display below every report
function voucherpress_report_footer() {
	voucherpress_wp_plugin_standard_footer( "GBP", "VoucherPress", "Chris Taylor", "chris@stillbreathing.co.uk", "http://wordpress.org/extend/plugins/voucherpress/" );
	echo '
	</div>
	';
}

// display the header of a data table
function voucherpress_table_header( $headings ) {
	echo '
	<table class="widefat post fixed">
	<thead>
	<tr>
	';
	foreach( $headings as $heading ) {
		echo '<th>' . __( $heading, "voucherpress" ) . '</th>
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
// general functions

// return a list of safe fonts
function voucherpress_fonts()
{
	return array( 
		array( "times", "Serif 1"),
		array( "timesb", "Serif 1 (bold)"),
		array( "almohanad", "Serif 2"),
		array( "helvetica", "Sans-serif 1"),
		array( "helveticab", "Sans-serif 1 (bold)"),
		array( "dejavusans", "Sans-serif 2"),
		array( "dejavusansb", "Sans-serif 2 (bold)"),
		array( "courier", "Monotype" ),
		array( "courierb", "Monotype (bold)")
	);
}

// check if the site is using pretty URLs
function voucherpress_pretty_urls() {
	$structure = get_option( "permalink_structure" );
	if ( $structure != "" || strpos( $structure, "?" ) === false ) {
		return true;
	}
	return false;
}

// create a URL to a voucherpress page
function voucherpress_link( $voucher_guid, $code = "", $encode = true ) {
	if ( voucherpress_pretty_urls() ) {
		if ( $code != "" ) {
			if ( $encode )
			{
				$code = "&amp;code=" . urlencode( $code );
			} else {
				$code = "&code=" . urlencode( $code );
			}
		}
		return get_option( "siteurl" ) . "/?voucher=" . $voucher_guid . $code;
	}
	if ( $code != "" ) {
		if ( $encode )
		{
			$code = "&amp;code=" . urlencode( $code );
		} else {
			$code = "&code=" . urlencode( $code );
		}
	}
	return get_option( "siteurl" ) . "/?voucher=" . $voucher_guid . $code;
}

// create an md5 hash of a guid
// from http://php.net/manual/en/function.com-create-guid.php
function voucherpress_guid(){
    if (function_exists('com_create_guid')){
        return str_replace( "{", "", str_replace( "}", "", com_create_guid() ) );
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = 
                substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
        return md5( str_replace( "{", "", str_replace( "}", "", $uuid ) ) );
    }
}

// get the users IP address
// from http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
function voucherpress_ip() {
	if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;

}

// get the current blog ID (for WP Multisite) or '1' for standard WP
function voucherpress_blog_id(){
	global $current_blog;
	if ( $current_blog->blog_id != "" ) {
		return $current_blog->blog_id;
	} else {
		return 1;
	}
}

// return yes or no
function voucherpress_yes_no( $val )
{
	if ( !$val || $val == "" || $val == "0" )
	{
		return __( "No", "voucherpress" );
	} else {
		return __( "Yes", "voucherpress" );
	}
}

// create slug
// Bramus! pwnge! : simple method to create a post slug (http://www.bram.us/)
function voucherpress_slug( $string ) {
	$slug = preg_replace( "/[^a-zA-Z0-9 -]/", "", $string );
	$slug = str_replace( " ", "-", $slug );
	$slug = strtolower( $slug );
	return $slug;
}

// process a shortcode for a voucher
function voucher_do_voucher_shortcode( $atts ) {

	extract( shortcode_atts( array( 
		'id' => '',
		'preview' => ''
	), $atts ) );

	if ( $id != "" ) {
	
		$voucher = voucherpress_get_voucher( $id );
		if ( $voucher ) {
		
			if ( $preview == 'true' ) {
			
				return '<a href="' . voucherpress_link( $voucher->guid ) . '"><img src="' . get_option( "siteurl" ) . '/wp-content/plugins/voucherpress/templates/' . $voucher->template . '_thumb.jpg" alt="' . htmlspecialchars( $voucher->name ) . '" /></a>';
			
			} else {
			
				return '<a href="' . voucherpress_link( $voucher->guid ) . '">' . htmlspecialchars( $voucher->name ) . '</a>';
			
			}
			
		}
	
	}
	
}

// process a shortcode for a list of vouchers
function voucher_do_list_shortcode() {
	
	$vouchers = voucherpress_get_vouchers();
	if ( $vouchers && is_array( $vouchers ) && count( $vouchers ) > 0 ) {
		
		$r = "<ul class=\"voucherlist\">\n";
		
		foreach( $vouchers as $voucher ) {
		
			$r .= '<li><a href="' . voucherpress_link( $voucher->guid ) . '">' . htmlspecialchars( $voucher->name ) . '</a></li>';
		
		}
		
		$r .= '</ul>';
		
		return $r;
	}
	
}

// ==========================================================================================
// voucher administration functions

// listen for downloads of email addresses
function voucherpress_check_download() {
	// download all unique email addresses
	if ( ( @$_GET["page"] == "vouchers" ) && @$_GET["download"] == "emails" && @$_GET["voucher"] == "" ) {
		if ( !voucherpress_download_emails() ) {
			wp_die( __("Sorry, the list could not be downloaded. Please click back and try again.", "voucherpress" ) );
		}
	}
	// download unique email addresses for a voucher
	if ( ( @$_GET["page"] == "vouchers" ) && @$_GET["download"] == "emails" && @$_GET["voucher"] != "" ) {
		if ( !voucherpress_download_emails($_GET["voucher"]) ) {
			wp_die( __("Sorry, the list could not be downloaded. Please click back and try again.", "voucherpress" ) );
		}
	}
}

// listen for previews of a voucher
function voucherpress_check_preview() {
	if ( ( @$_GET["page"] == "vouchers" || @$_GET["page"] == "vouchers-create" ) && @$_GET["preview"] == "voucher" ) {
		voucherpress_preview_voucher( $_POST["template"], $_POST["font"], $_POST["name"], $_POST["text"], $_POST["terms"] );
	}
}

// listen for creation of a voucher
function voucherpress_check_create_voucher() {
	if ( @$_GET["page"] == "vouchers-create" && @$_GET["preview"] == "" && @$_POST && is_array( $_POST ) && count( $_POST ) > 0 ) {
		$require_email = 0;
		if ( $_POST["requireemail"] == "1" ) { $require_email = 1; }
		$limit = 0;
		if ( $_POST["limit"] != "" && $_POST["limit"] != "0" ) { $limit = (int)$_POST["limit"]; }
		$array = voucherpress_create_voucher( $_POST["name"], $require_email, $limit, $_POST["text"], $_POST["template"], $_POST["font"], $_POST["terms"] );
		if ( $array && is_array( $array ) && $array[0] == true && $array[1] > 0 ) {
			header( "Location: admin.php?page=vouchers&id=" . $array[1] . "&result=1" );
			exit();
		} else {
			header( "Location: admin.php?page=vouchers-create&result=1" );
			exit();
		}
	}
}

// listen for editing of a voucher
function voucherpress_check_edit_voucher() {
	if ( @$_GET["page"] == "vouchers" && @$_GET["preview"] == "" && @$_POST && is_array( $_POST ) && count( $_POST ) > 0 ) {
		$require_email = 0;
		if ( $_POST["requireemail"] == "1" ) { $require_email = 1; }
		$live = 0;
		if ( $_POST["live"] == "1" ) { $live = 1; }
		$limit = 0;
		if ( $_POST["limit"] != "" && $_POST["limit"] != "0" ) { $limit = (int)$_POST["limit"]; }
		$done = voucherpress_edit_voucher( $_GET["id"], $_POST["name"], $require_email, $limit, $_POST["text"], $_POST["template"], $_POST["font"], $_POST["terms"], $live );
		if ( $done ) {
			header( "Location: admin.php?page=vouchers&id=" . $_GET["id"] . "&result=3" );
			exit();
		} else {
			header( "Location: admin.php?page=vouchers&id=" . $_GET["id"] . "&result=2" );
			exit();
		}
	}
}

// download a list of email addresses
function voucherpress_download_emails( $voucherid = 0 ) {
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "select email, name from " . $prefix . "voucherpress_downloads
	where %d = 0
	or voucherid = %d
	group by email;",
	$voucherid, $voucherid );
	$emails = $wpdb->get_results( $sql );
	if ( $emails && is_array( $emails ) && count( $emails ) > 0 ) {
		header( 'Content-type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="voucher-emails.csv"' );
		echo "Name,Email\n";
		foreach( $emails as $email ) {
			echo htmlspecialchars( $email->name ) . "," . htmlspecialchars( $email->email ) . "\n";
		}
		exit();
	} else {
		return false;
	}
}

// preview a voucher
function voucherpress_preview_voucher( $template, $font, $name, $text, $terms ) {

	global $current_user;
	
	$voucher->template = $template;
	$voucher->font = $font;
	$voucher->name = $name;
	$voucher->text = $text;
	$voucher->terms = $terms;
	
	voucherpress_render_voucher( $voucher, "[" . __( "Unique voucher code here", "voucherpress" ) . "]" );
	
}

// create a new voucher
function voucherpress_create_voucher( $name, $require_email, $limit, $text, $template, $font, $terms ) {
	$blog_id = voucherpress_blog_id();
	$guid = voucherpress_guid();
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "insert into " . $prefix . "voucherpress_vouchers 
	(blog_id, name, `text`, terms, template, font, require_email, `limit`, guid, time, live) 
	values 
	(%d, %s, %s, %s, %d, %s, %d, %d, %s, %d, %d);", 
	$blog_id, $name, $text, $terms, $template, $font, $require_email, $limit, $guid, time(), 1 );
	$done = $wpdb->query( $sql );
	$id = 0;
	if ( $done ) {
		$id = $wpdb->insert_id;
	}
	return array( $done, $id );
}

// edit a voucher
function voucherpress_edit_voucher( $id, $name, $require_email, $limit, $text, $template, $font, $terms, $live ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "update " . $prefix . "voucherpress_vouchers set 
	time = %d,
	name = %s, 
	`text` = %s, 
	terms = %s,
	template = %d, 
	font = %s, 
	require_email = %d,
	`limit` = %d,
	live = %d
	where id = %d 
	and blog_id = %d;", 
	time(), $name, $text, $terms, $template, $font, $require_email, $limit, $live, $id, $blog_id );
	return $wpdb->query( $sql );
}

// ==========================================================================================
// template functions

function voucherpress_upload_template( $id, $file ) {

	$file = $file["tmp_name"];

	// get the image size
	$imagesize = getimagesize( $file );
	$width = $imagesize[0];
	$height = $imagesize[1];
	$imagetype = $imagesize[2];
	
	// if the imagesize could be fetched and is JPG, PNG or GIF
	if ( $imagetype == 2 && $width == 2362 && $height == 1063 )
	{

		$path = ABSPATH . "/wp-content/plugins/voucherpress/templates/";
		
		// move the temporary file to the full-size image (2362 x 1063 px @ 72dpi)
		$fullpath = $path . $id . ".jpg";
		move_uploaded_file( $file, $fullpath );
		
		// get the image
		$image = imagecreatefromjpeg( $fullpath );
		
		// create the preview image (800 x 360 px @ 72dpi)
		$preview = imagecreatetruecolor( 800, 360 );
		imagecopyresampled( $preview, $image, 0, 0, 0, 0, 800, 360, $width, $height );
		$previewpath = $path . $id . "_preview.jpg";
		imagejpeg( $preview, $previewpath, 80 );
		
		// create the thumbnail image (200 x 90 px @ 72dpi)
		$thumb = imagecreatetruecolor( 200, 90 );
		imagecopyresampled( $thumb, $image, 0, 0, 0, 0, 200, 90, $width, $height );
		$thumbpath = $path . $id . "_thumb.jpg";
		imagejpeg( $thumb, $thumbpath, 70 );
		
		return true;

		
	} else {
	
		return false;
	
	}
}

// add a new template
function voucherpress_add_template( $name ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "insert into " . $prefix . "voucherpress_templates 
	(blog_id, name, time) 
	values 
	(%d, %s, %d);", 
	$blog_id, $name, time() );
	if ( $wpdb->query( $sql ) )
	{
		return $wpdb->insert_id;
	} else {
		return false;
	}
}

// edit a template
function voucherpress_edit_template( $id, $name, $live ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "update " . $prefix . "voucherpress_templates set 
	name = %s, 
	live = %d 
	where id = %d and blog_id = %d;", 
	$name, $live, $id, $blog_id );
	return $wpdb->query( $sql );
}

// get a list of templates
function voucherpress_get_templates() {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "select id, blog_id, name from " . $prefix . "voucherpress_templates where live = 1 and (blog_id = 0 or blog_id = %d);", $blog_id );
	return $wpdb->get_results( $sql );
}

// ==========================================================================================
// vouchers functions

// get a list of all blog vouchers
function voucherpress_get_all_vouchers( $num = 25, $start = 0 ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$showall = "0";
	if ($all) { $showall = "1"; }
	$limit = "limit " . (int)$start . ", " . (int)$num;
	if ($num == 0) { $limit = ""; }
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = "select b.domain, b.path, v.id, v.name, v.`text`, v.terms, v.require_email, v.`limit`, v.live, v.guid, 
(select count(d.id) from " . $prefix . "voucherpress_downloads d where d.voucherid = v.id) as downloads
from " . $prefix . "voucherpress_vouchers v
inner join " . $wpdb->base_prefix . "blogs b on b.blog_id = v.blog_id
where v.live = 1
order by v.time desc 
" . $limit . ";";
	return $wpdb->get_results( $sql );
}

// get a list of vouchers
function voucherpress_get_vouchers( $num = 25, $all=false ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$showall = "0";
	if ($all) { $showall = "1"; }
	$limit = "limit " . (int)$num;
	if ($num == 0) { $limit = ""; }
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "select v.id, v.name, v.`text`, v.terms, v.require_email, v.`limit`, v.live, v.guid, 
(select count(d.id) from " . $prefix . "voucherpress_downloads d where d.voucherid = v.id) as downloads
from " . $prefix . "voucherpress_vouchers v
where (%s = '1' or v.live = 1)
and v.blog_id = %d
order by v.time desc 
" . $limit . ";", $showall, $blog_id );
	return $wpdb->get_results( $sql );
}

// get a list of all popular vouchers by download
function voucherpress_get_all_popular_vouchers( $num = 25, $start = 0 ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$limit = "limit " . (int)$start . ", " . (int)$num;
	if ($num == 0) { $limit = ""; }
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = "select b.domain, b.path, v.id, v.name, v.`text`, v.terms, v.require_email, v.`limit`, v.live, v.guid, 
count(d.id) as downloads
from " . $prefix . "voucherpress_downloads d 
inner join " . $prefix . "voucherpress_vouchers v on v.id = d.voucherid
inner join " . $wpdb->base_prefix . "blogs b on b.blog_id = v.blog_id
group by v.id
order by count(d.id) desc
" . $limit . ";";
	return $wpdb->get_results( $sql );
}

// get a list of popular vouchers by download
function voucherpress_get_popular_vouchers( $num = 25 ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$limit = "limit " . (int)$num;
	if ($num == 0) { $limit = ""; }
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "select v.id, v.name, v.`text`, v.terms, v.require_email, v.`limit`, v.live, v.guid, 
count(d.id) as downloads
from " . $prefix . "voucherpress_downloads d 
inner join " . $prefix . "voucherpress_vouchers v on v.id = d.voucherid
where v.blog_id = %d
group by v.id
order by count(d.id) desc
" . $limit . ";", $blog_id );
	return $wpdb->get_results( $sql );
}

// ==========================================================================================
// individual voucher functions

// get a voucher by id or guid
function voucherpress_get_voucher( $voucher, $live = 1, $code = "" ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	// get by id
	if ( is_numeric( $voucher ) ) {
		$sql = $wpdb->prepare( "select v.id, v.name, v.`text`, v.terms, v.font, v.template, v.require_email, v.`limit`, v.guid, v.live, '' as registered_email, '' as registered_name,
		(select count(d.id) from " . $prefix . "voucherpress_downloads d where d.voucherid = v.id) as downloads
		from " . $prefix . "voucherpress_vouchers v
		where 
		(%d = 0 or v.live = 1)
		and v.id = %d
		and v.blog_id = %d", 
		$live, $voucher, $blog_id );
	// get by guid
	} else {
		// if a download code has been specified
		if ( $code != "")
		{
			$sql = $wpdb->prepare( "select v.id, v.name, v.`text`, v.terms, v.font, v.template, v.require_email, v.`limit`, v.guid, v.live, r.email as registered_email, r.name as registered_name,
			(select count(d.id) from " . $prefix . "voucherpress_downloads d where d.voucherid = v.id) as downloads
			from " . $prefix . "voucherpress_vouchers v
			left outer join " . $prefix . "voucherpress_downloads r on r.voucherid = v.id and r.guid = %s
			where 
			v.live = 1
			and v.guid = %s
			and v.blog_id = %d", 
			$code, $voucher, $blog_id );
		} else {
			$sql = $wpdb->prepare( "select v.id, v.name, v.`text`, v.terms, v.font, v.template, v.require_email, v.`limit`, v.guid, v.live, '' as registered_email, '' as registered_name,
			(select count(d.id) from " . $prefix . "voucherpress_downloads d where d.voucherid = v.id) as downloads
			from " . $prefix . "voucherpress_vouchers v
			where 
			(%d = 0 or v.live = 1)
			and v.guid = %s
			and v.blog_id = %d", 
			$live, $voucher, $blog_id );
		}
	}
	$row = $wpdb->get_row( $sql );
	if ( $row->id != "" ) {
		return $row;
	} else {
		return false;
	}
}

// check a voucher exists and can be downloaded
function voucherpress_voucher_exists( $guid ) {
	$blog_id = voucherpress_blog_id();
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	$sql = $wpdb->prepare( "select v.id, v.`limit`,
	(select count(d.id) from " . $prefix . "voucherpress_downloads d where d.voucherid = v.id) as downloads
	from " . $prefix . "voucherpress_vouchers v
	where 
	v.guid = %s
	and v.blog_id = %d", 
	$guid, $blog_id );
	$row = $wpdb->get_row( $sql );
	if ( $row && ( (int)$row->limit == 0 || (int)$row->limit > (int)$row->downloads ) ) {
		return true;
	}
	return false;
}

// download a voucher
function voucherpress_download_voucher( $voucher_guid, $code = "" ) {
	$voucher = voucherpress_get_voucher( $voucher_guid, 1, $code );
	if (
	$voucher 
	&& $voucher->live == 1 
	&& $voucher->id != "" 
	&& $voucher->name != "" 
	&& $voucher->text != "" 
	&& $voucher->terms != "" 
	&& ( (int)$voucher->limit == 0 || (int)$voucher->downloads < (int)$voucher->limit ) 
	&& $voucher->template != "" 
	&& voucherpress_template_exists( $voucher->template ) 
	)
	{
		if ( voucherpress_code_is_valid( $voucher_guid, $code ) )
		{
			// set this download as completed
			$code = voucherpress_create_download_code( $voucher->id, $code );
			// render the voucher
			voucherpress_render_voucher( $voucher, $code );
		} else {
			voucherpress_404();
		}
	} else {
		global $wp_query;
		$wp_query->set_404();
	    require TEMPLATEPATH.'/404.php';
	    exit();
	}
}

// render a voucher
function voucherpress_render_voucher( $voucher, $code ) {

	global $current_user;
	// get the voucher template image
	if( voucherpress_template_exists( $voucher->template ) )
	{
		$slug = voucherpress_slug( $voucher->name );
	
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header( 'Content-type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $slug . '.pdf"' );
	
		// include the TCPDF class and VoucherPress PDF class
		require_once("voucherpress_pdf.php");
		
		// create new PDF document
		$pdf = new voucherpress_pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		// set the properties
		$pdf->voucher_image = ABSPATH . 'wp-content/plugins/voucherpress/templates/' . $voucher->template . '.jpg';
		$pdf->voucher_image_w = 200;
		$pdf->voucher_image_h = 90;
		$pdf->voucher_image_dpi = 300;
		$pdf->setPageFormat(array(200, 90));
		
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor($current_user->user_nicename);
		$pdf->SetTitle($voucher->name);
		
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		
		//set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(0);
		$pdf->SetFooterMargin(0);
		
		// remove default footer
		$pdf->setPrintFooter(false);
		
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		
		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 
		
		//set some language-dependent strings
		$pdf->setLanguageArray($l); 
		
		// set top margin
		$pdf->SetTopMargin(15);
		
		// add a page
		$pdf->AddPage();
		
		// set title font
		$pdf->SetFont($voucher->font, '', 32);
		// print title
		$pdf->writeHTML( stripslashes( $voucher->name ), $ln=true, $fill=false, $reseth=false, $cell=false, $align='C');
		
		// set text font
		$pdf->SetFont($voucher->font, '', 18);
		// print text
		$pdf->Write( 5,  stripslashes( $voucher->text ), $link = '', $fill = 0, $align = 'C', $ln = true);

		$registered_name = "";
		if ( $voucher->registered_name != "" )
		{
			$registered_name =  stripslashes( $voucher->registered_name ) . ": ";
		}
		
		// set code font
		$pdf->SetFont($voucher->font, '', 14);
		// print code
		$pdf->Write( 10, $registered_name . $code, $link = '', $fill = 0, $align = 'C', $ln = true);

		// set terms font
		$pdf->SetFont($voucher->font, '', 10);
		// print terms
		$pdf->Write( 5,  stripslashes( $voucher->terms ), $link = '', $fill = 0, $align = 'C', $ln = true);

		// close and output PDF document
		$pdf->Output( $slug . '.pdf', 'D' );
		
	} else {
	
		return false;
		
	}
}

// check a template exists
function voucherpress_template_exists( $template ) {
	$file = ABSPATH . "wp-content/plugins/voucherpress/templates/" . $template . ".jpg";
	if ( file_exists( $file ) ) {
		return true;
	}
	return false;
}

// ==========================================================================================
// person functions

// show the registration form
function voucherpress_register_form( $voucher_guid ) {

	$showform = true;

	get_header();
	
	echo '
	<div id="content" class="narrowcolumn" role="main">
	<div class="post category-uncategorized" id="voucher-' . $voucher_guid . '">
	';
	
	// if registering
	if ( @$_POST["voucher_email"] != "" && @$_POST["voucher_name"] != "" )
	{
	
		// if the email address is valid
		if ( is_email( trim($_POST["voucher_email"]) ) )
		{
	
			// register the email address
			$guid = voucherpress_register_person( $voucher_guid, trim($_POST["voucher_email"]), trim($_POST["voucher_name"]) );
			
			// if the guid has been generated
			if ( $guid ) {
			
				$message = __( "You have successfully registered to download a voucher, please download the voucher from here:", "voucherpress" ) . "\n\n" . voucherpress_link( $voucher_guid, $guid, false );
			
				// send the email
				wp_mail( trim($_POST["voucher_email"]), "Voucher download for " . trim($_POST["voucher_name"]), $message );
			
				echo '
				<p>' .  __( "Thank you for registering. You will shortly receive an email sent to '" . trim($_POST["voucher_email"]) . "' with a link to your personalised voucher.", "voucherpress" ) . '</p>
				';
				$showform = false;
			
			} else {
			
				echo '
				<p>' .  __( "Sorry, your email address and name could not be saved. Please try again.", "voucherpress" ) . '</p>
				';
			
			}
			
		} else {
		
			echo '
			<p>' .  __( "Sorry, your email address was not valid. Please try again.", "voucherpress" ) . '</p>
			';
		
		}
	
	}
	
	if ( $showform )
	{
		echo '
		<h2>' . __( "Please provide some details", "voucherpress" ) . '</h2>
		<p>' .  __( "To download this voucher you must provide your name and email address. You will then receive a link by email to download your personalised voucher.", "voucherpress" ) . '</p>
		<form action="' . voucherpress_link( $voucher_guid ) . '" method="post" class="voucherpress_form">
		<p><label for="voucher_email">' .  __( "Your email address", "voucherpress" ) . '</label>
		<input type="text" name="voucher_email" id="voucher_email" value="' . trim(@$_POST["voucher_email"]) . '" /></p>
		<p><label for="voucher_name">' .  __( "Your name", "voucherpress" ) . '</label>
		<input type="text" name="voucher_name" id="voucher_name" value="' . trim(@$_POST["voucher_name"]) . '" /></p>
		<p><input type="submit" name="voucher_submit" id="voucher_submit" value="' .  __( "Register for this voucher", "voucherpress" ) . '" /></p>
		</form>
	';
	
	}
	
	echo '
	</div>
	</div>
	';
	
	get_footer();
}

// register a persons name and email address
function voucherpress_register_person( $voucher_guid, $email, $name ) {
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	// get the voucher id
	$sql = $wpdb->prepare( "select id from " . $prefix . "voucherpress_vouchers where guid = %s;", $voucher_guid );
	$voucherid = $wpdb->get_var( $sql ); 
	// if the id has been found
	if ( $voucherid != "" ) {
		// get the IP address
		$ip = voucherpress_ip();
		// create the guid
		$guid = voucherpress_guid();
		// insert the new download
		$sql = $wpdb->prepare( "insert into " . $prefix . "voucherpress_downloads 
		(voucherid, time, email, name, ip, guid, downloaded)
		values
		(%d, %d, %s, %s, %s, %s, 0)", 
		$voucherid, time(), $email, $name, $ip, $guid );
		$wpdb->query( $sql );
		return $guid;
	}
	return false;
}

// check a code address is valid for a voucher
function voucherpress_code_is_valid( $voucher_guid, $code ) {
	if ( $voucher_guid == "" && $code == "" ) {
		return false;
	} else {
		global $wpdb;
		$prefix = $wpdb->prefix;
		if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
		$blog_id = voucherpress_blog_id();
		global $wpdb;
		$sql = $wpdb->prepare( "select v.require_email, ifnull( d.email, '' ) as email, ifnull( d.downloaded, 0 ) as downloaded from
				" . $prefix . "voucherpress_vouchers v
				left outer join " . $prefix . "voucherpress_downloads d on d.voucherid = v.id and d.guid = %s
				where v.guid = %s
				and v.blog_id = %d;", 
				$code, $voucher_guid, $blog_id );
		$row = $wpdb->get_row( $sql );
		// if the voucher has been found
		if ( $row )
		{
			// if emails are not required
			if ( $row->require_email != "1" )
			{
				return true;
			} else {
				// if this email is registered and the voucher not yet downloaded
				if ( $row->email != "" && $row->downloaded == "0" ) 
				{
					return true;
				}
			}
		}
		return false;
	}
}

// create a download code for a voucher
function voucherpress_create_download_code( $voucherid, $guid = "" ) {
	global $wpdb;
	$prefix = $wpdb->prefix;
	if ( $wpdb->base_prefix != "") { $prefix = $wpdb->base_prefix; }
	if ( $guid != "" )
	{
		// set this voucher as being downloaded
		$sql = $wpdb->prepare( "update " . $prefix . "voucherpress_downloads set downloaded = 1 where voucherid = %d and guid = %s;", $voucherid, $guid );
		$wpdb->query( $sql );
	} else {
		// get the IP address
		$ip = voucherpress_ip();
		// create the guid
		$guid = voucherpress_guid();
		// insert the download
		$sql = $wpdb->prepare( "insert into " . $prefix . "voucherpress_downloads 
		(voucherid, time, ip, guid, downloaded)
		values
		(%d, %d, %s, %s, 1)", 
		$voucherid, time(), $ip, $guid );
		$wpdb->query( $sql );
	}
	// return this code
	return $guid;
}

// a standard header for your plugins, offers a PayPal donate button and link to a support page
function voucherpress_wp_plugin_standard_header( $currency = "", $plugin_name = "", $author_name = "", $paypal_address = "", $bugs_page ) {
	$r = "";
	$option = get_option( $plugin_name . " header" );
	if ( $_GET[ "header" ] != "" || $_GET["thankyou"] == "true" ) {
		update_option( $plugin_name . " header", "hide" );
		$option = "hide";
	}
	if ( $_GET["thankyou"] == "true" ) {
		$r .= '<div class="updated"><p>' . __( "Thank you for donating" ) . '</p></div>';
	}
	if ( $currency != "" && $plugin_name != "" && $_GET[ "header" ] != "hide" && $option != "hide" )
	{
		$r .= '<div class="updated">';
		$pageURL = 'http';
		if ( $_SERVER["HTTPS"] == "on" ) { $pageURL .= "s"; }
		$pageURL .= "://";
		if ( $_SERVER["SERVER_PORT"] != "80" ) {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		if ( strpos( $pageURL, "?") === false ) {
			$pageURL .= "?";
		} else {
			$pageURL .= "&";
		}
		$pageURL = htmlspecialchars( $pageURL );
		if ( $bugs_page != "" ) {
			$r .= '<p>' . sprintf ( __( 'To report bugs please visit <a href="%s">%s</a>.' ), $bugs_page, $bugs_page ) . '</p>';
		}
		if ( $paypal_address != "" && is_email( $paypal_address ) ) {
			$r .= '
			<form id="wp_plugin_standard_header_donate_form" action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_donations" />
			<input type="hidden" name="item_name" value="Donation: ' . $plugin_name . '" />
			<input type="hidden" name="business" value="' . $paypal_address . '" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="no_shipping" value="1" />
			<input type="hidden" name="rm" value="1" />
			<input type="hidden" name="currency_code" value="' . $currency . '">
			<input type="hidden" name="return" value="' . $pageURL . 'thankyou=true" />
			<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" />
			<p>';
			if ( $author_name != "" ) {
				$r .= sprintf( __( 'If you found %1$s useful please consider donating to help %2$s to continue writing free Wordpress plugins.' ), $plugin_name, $author_name );
			} else {
				$r .= sprintf( __( 'If you found %s useful please consider donating.' ), $plugin_name );
			}
			$r .= '
			<p><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="" /></p>
			</form>
			';
		}
		$r .= '<p><a href="' . $pageURL . 'header=hide" class="button">' . __( "Hide this") . '</a></p>';
		$r .= '</div>';
	}
	print $r;
}
function voucherpress_wp_plugin_standard_footer( $currency = "", $plugin_name = "", $author_name = "", $paypal_address = "", $bugs_page ) {
	$r = "";
	if ( $currency != "" && $plugin_name != "" )
	{
		$r .= '<form id="wp_plugin_standard_footer_donate_form" action="https://www.paypal.com/cgi-bin/webscr" method="post" style="clear:both;padding-top:50px;"><p>';
		if ( $bugs_page != "" ) {
			$r .= sprintf ( __( '<a href="%s">Bugs</a>' ), $bugs_page );
		}
		if ( $paypal_address != "" && is_email( $paypal_address ) ) {
			$r .= '
			<input type="hidden" name="cmd" value="_donations" />
			<input type="hidden" name="item_name" value="Donation: ' . $plugin_name . '" />
			<input type="hidden" name="business" value="' . $paypal_address . '" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="no_shipping" value="1" />
			<input type="hidden" name="rm" value="1" />
			<input type="hidden" name="currency_code" value="' . $currency . '">
			<input type="hidden" name="return" value="' . $pageURL . 'thankyou=true" />
			<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" />
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="' . __( "Donate" ) . ' ' . $plugin_name . '" />
			';
		}
		$r .= '</p></form>';
	}
	print $r;
}
?>