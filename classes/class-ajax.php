<?php
class VoucherPress_Ajax {

	// load the thumbnails for the selected size
	function LoadThumbs() {

        // load and create the template manager
		require_once( WP_PLUGIN_DIR . '/voucherpress/managers/manager-templates.php' );
		$templateManager = new VoucherPress_TemplateManager();

		$size = @$_POST['size'];
		$root = plugins_url() . '/voucherpress/templates/';
		$templates = $templateManager->GetAllTemplates( $size );
		if ( $templates && is_array( $templates ) && count( $templates ) > 0 ) {
			foreach( $templates as $template ) {
				echo '<span><img src="' . plugins_url() . '/voucherpress/templates/' . $size . '/' . $template->id . '_thumb.jpg" id="template_' . $template->id . '" alt="' . $template->name . '" /></span>';
			}
		} else {
			echo __( 'No templates for the selected size', 'voucherpress' );
		}
		exit();
	}

}
?>