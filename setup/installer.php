<?php
// manages the installation and upgrading of VoucherPress

// activate the plugin
function voucherpress_activate() {

	// if PHP is less than version 5
	if ( version_compare( PHP_VERSION, '5.0.0', '<' ) )	{
		echo '
		<div id="message" class="error">
			<p><strong>' . __( "Sorry, your PHP version must be 5 or above. Please contact your server administrator for help.", "voucherpress" ) . '</strong></p>
		</div>
		';

    // PHP is version 5 or greater
	} else {

		//check install
		voucherpress_check_install();

		// save default options
		$data = array(
			'register_title' => 'Enter your email address',
			'register_message' => 'You must supply your name and email address to download this voucher. Please enter your details below, a link will be sent to your email address for you to download the voucher.',
			'email_label' => 'Your email address',
			'name_label' => 'Your name',
			'button_text' => 'Request voucher',
			'bad_email_message' => 'Sorry, your email address seems to be invalid. Please try again.',
			'thanks_message' => 'Thank you, a link has been sent to your email address for you to download this voucher.',
			'voucher_not_found_message' => 'Sorry, the voucher you are looking for cannot be found.'
			);

		// add options
		add_option ( 'voucherpress_data', maybe_serialize( $data ) );
		add_option ( 'voucherpress_version', voucherpress_current_version() );
	}
}

// check voucherpress is installed correctly
function voucherpress_check_install() {

	// create tables
	$newly_installed = voucherpress_create_tables();

    // if the plugin has been newly installed
	if ( $newly_installed ) {

		// sleep for 1 second to allow the tables to be created
		sleep(1);

		// get the template manager
		voucherpress_include( 'managers/manager-templates.php' );
		$templateManager = new VoucherPress_TemplateManager();

		// if there are no templates saved insert the default templates
		$templates = $templateManager->TemplateCount();
		if ( !$templates || $templates == 0 ) {
			$templateManager->CreateDefaultTemplates();
		}

		// check the templates directory is writeable
		if ( !@is_writable( WP_PLUGIN_DIR . '/voucherpress/templates/' ) ) {
			echo '
			<div id="message" class="warning">
				<p><strong>' . sprintf( __( "The system does not have write permissions on the folder (%s) where your custom templates are stored. You may not be able to upload your own templates. Please contact your system administrator for more information.", "voucherpress" ), WP_PLUGIN_DIR . "/voucherpress/templates/" ) . '</strong></p>
			</div>
			';
		}
	}
}

// create the tables
function voucherpress_create_tables() {

	// check the current version
	if ( version_compare( voucherpress_get_version(), voucherpress_current_version() ) == -1 ) { // || WP_DEBUG ) {

		voucherpress_debug( 'Creating tables' );

		global $wpdb;
		$prefix = $wpdb->prefix;
		if ( isset( $wpdb->base_prefix )  ) { $prefix = $wpdb->base_prefix; }

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// table to store the vouchers
		$sql = "CREATE TABLE {$prefix}voucherpress_vouchers (
			  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
			  blog_id MEDIUMINT NOT NULL,
			  user_id MEDIUMINT NOT NULL,
			  time BIGINT(11) DEFAULT '0' NOT NULL,
			  name VARCHAR(50) NOT NULL,
			  `html` TEXT NULL,
			  `text` VARCHAR(250) NOT NULL,
			  `description` TEXT NULL,
			  terms VARCHAR(500) NOT NULL,
			  template VARCHAR(55) NOT NULL,
			  font VARCHAR(55) DEFAULT 'helvetica' NOT NULL,
			  require_email TINYINT DEFAULT 1 NOT NULL,
			  `limit` MEDIUMINT(9) NOT NULL DEFAULT 0,
			  guid VARCHAR(36) NOT NULL,
			  live TINYINT DEFAULT '0',
			  startdate INT DEFAULT '0',
			  expiry INT DEFAULT '0',
			  codestype VARCHAR(12) DEFAULT 'random',
			  codeprefix VARCHAR(6) DEFAULT '',
			  codesuffix VARCHAR(6) DEFAULT '',
			  codelength INT DEFAULT 6,
			  codes MEDIUMTEXT NOT NULL DEFAULT '',
			  deleted TINYINT DEFAULT '0',
			  PRIMARY KEY  id (id)
			) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
		voucherpress_debug( $sql );
		dbDelta( $sql );

		// table to store downloads
		$sql = "CREATE TABLE {$prefix}voucherpress_downloads (
			  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
			  voucherid MEDIUMINT(9) NOT NULL,
			  time BIGINT(11) DEFAULT '0' NOT NULL,
			  ip VARCHAR(15) NOT NULL,
			  name VARCHAR(255) NULL,
			  email VARCHAR(255) NULL,
			  guid VARCHAR(36) NOT NULL,
			  code VARCHAR(255) NOT NULL,
              data TEXT NULL,
			  downloaded TINYINT DEFAULT '0',
			  PRIMARY KEY  id (id)
			) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
		voucherpress_debug( $sql );
		dbDelta( $sql );

		// table to store templates
		$sql = "CREATE TABLE {$prefix}voucherpress_templates (
			  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
			  blog_id MEDIUMINT NOT NULL,
			  user_id MEDIUMINT NOT NULL,
			  time BIGINT(11) DEFAULT '0' NOT NULL,
			  name VARCHAR(55) NOT NULL,
			  size VARCHAR(9) NOT NULL,
			  live TINYINT DEFAULT '1',
			  PRIMARY KEY  id (id)
			) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
		voucherpress_debug( $sql );
		dbDelta( $sql );

		// if there were no voucher download guids found, move the codes
		$sql = "SELECT COUNT(id) FROM {$prefix}voucherpress_downloads WHERE code <> '';";
		voucherpress_debug( $sql );
		$codes = (int)$wpdb->get_var( $sql );
		if ( $codes == 0 ) {
			$sql = "UPDATE {$prefix}voucherpress_downloads SET code = guid;";
			voucherpress_debug( $sql );
			$wpdb->query( $sql );
			$sql = "UPDATE {$prefix}voucherpress_downloads SET guid = '';";
			voucherpress_debug( $sql );
			$wpdb->query( $sql );
		}

		// update the version
		voucherpress_update_version();

		// the system has just been installed
		return true;

	} else {
		voucherpress_debug( 'Skipped creating tables because version compare: ' . voucherpress_get_version() . ' ? ' . voucherpress_current_version() . ' = ' . version_compare( voucherpress_get_version(), voucherpress_current_version() ) );
		voucherpress_debug( 'Skipped creating tables because WP_DEBUG = ' . WP_DEBUG );
	}

	return false;
}
?>