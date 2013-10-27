<?php
// manages templates, for instance getting multiple templates

class VoucherPress_TemplateManager {

	// see if the specified template file exists on disk
	function TemplateExists( $template ) {
		$file = $this->GetTemplatePath( $template );
		if ( file_exists( $file ) ) return true;
		return false;
	}

	// get the path at which the full-size template image should be located
	function GetTemplatePath( $template ) {
		return WP_PLUGIN_DIR . '/voucherpress/templates/' . $template->size . '/' . $template->id . '.jpg';
	}

	// gets a list of all template sizes available
	function GetTemplateSizes() {
		$blog_id = voucherpress_blog_id();
		$user_id = voucherpress_user_id();

		global $wpdb;

		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare(
			"SELECT size, COUNT(id) as templates
			FROM {$prefix}voucherpress_templates
			WHERE live = 1
			AND (blog_id = 0 OR blog_id = %d)
			AND (user_id = 0 OR user_id = %d)
			GROUP BY size
			ORDER BY size;",
			$blog_id,
			$user_id);

		voucherpress_debug( $sql );

		$sizes = $wpdb->get_results( $sql );
		return $sizes;
	}

    // gets an HTML select list containing the template sizes
	function GetTemplateSizeList( $selected = '' ) {

        // get the template sizes
        $sizes = $this->GetTemplateSizes();
		$r = '';

        // if there are sizes
		if ( $sizes && is_array( $sizes ) && count( $sizes ) > 0 ) {

            // return the list
			$r = '<select name="size" id="size">';

			foreach( $sizes as $size ) {
				$text = sprintf( _n( '%s: %d template', '%s: %d templates', $size->templates, 'voucherpress' ), $size->size, $size->templates );

				$r .= "
				<option value='{$size->size}'";

				if ( ( '' != $selected && $selected == $size->size ) ||
					( '' == $selected && $size->size == voucherpress_default_size() ) )
					$r .= ' selected="selected"';

				$r .= '>' . $text . '</option>';
			}
			$r .= '
			</select>';
		}
		return $r;
	}

	// gets the number of templates in the system
	function TemplateCount() {
		global $wpdb;

		$prefix = voucherpress_get_db_prefix();

		$sql = "SELECT COUNT(name) FROM {$prefix}voucherpress_templates;";

		voucherpress_debug( $sql );

		$templates = $wpdb->get_var( $sql );
		return $templates;
	}

	// gets all templates available to the current user in the VoucherPress system
	function GetTemplates( $size ) {

        // include the template class
		voucherpress_include( 'classes/class-template.php' );

		$blog_id = voucherpress_blog_id();
		$user_id = voucherpress_user_id();

		global $wpdb;
		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare(
			"SELECT id, blog_id, user_id, name, size, live
			FROM {$prefix}voucherpress_templates
			WHERE live = 1
			AND (blog_id = 0 OR blog_id = %d)
			AND (user_id = 0 OR user_id = %d)
			AND (%s = '' or size = %s);",
			$blog_id,
			$user_id,
			$size,
			$size);

		voucherpress_debug( $sql );

		$rows = $wpdb->get_results( $sql );

		$templates = array();
		foreach( $rows as $row ) {
			$template = new VoucherPress_Template();
			$template->MapRow( $row );
			$templates[] = $template;
		}
		return $templates;
	}

	// gets all available templates in the VoucherPress system for the given size
	function GetAllTemplates( $size ) {

        // include the template class
		voucherpress_include( 'classes/class-template.php' );

		global $wpdb;

		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare(
			"SELECT id, blog_id, user_id, name, size, live
			FROM {$prefix}voucherpress_templates
			WHERE live = 1
			AND (%s = '' OR size = %s);",
			$size,
			$size);

		voucherpress_debug( $sql );

		$rows = $wpdb->get_results( $sql );

		$templates = array();
		foreach( $rows as $row ) {
			$template = new VoucherPress_Template();
			$template->MapRow( $row );
			$templates[] = $template;
		}
		return $templates;
	}

	// create the default templates
	function CreateDefaultTemplates() {

		$templates = $this->TemplateCount();

		global $wpdb;

		$prefix = voucherpress_get_db_prefix();

        // if there are no templates we need to create them
		if ($templates == 0) {

			// include the template class
			voucherpress_include( 'classes/class-template.php' );

			voucherpress_debug( 'Creating default templates' );

			// save 800 x 360 templates
			$templates800x360 = explode( ',', 'Plain black border,Mint chocolate,Red floral border,Single red rose (top left),Red flowers,Pink flowers,Abstract green bubbles,International post,Gold ribbon,Monochrome bubble border,Colourful swirls,Red gift bag,Blue ribbon,Autumn floral border,Pink flowers,Yellow gift boxes,Wrought iron border,Abstract rainbow flowers,Christmas holly border,Small gold ribbon,Small red ribbon,White gift boxes,Glass flowers border,Single red rose (bottom centre),Fern border,Blue floral watermark,Monochrome ivy border,Ornate border,Winter flower corners,Spring flower corners,Pattern border,Orange flower with bar,Small coat of arms,Grunge border,Coffee beans,Blue gift boxes,Spring flowers border,Ornate magenta border,Mexico border,Chalk border,Thick border,Dark chalk border,Ink border' );

			foreach( $templates800x360 as $template ) {
				$t = new VoucherPress_Template();
				$t->name = $template;
				$t->size = '800x360';
				$t->blog_id = 0;
				$t->user_id = 0;
				$t->live = 1;
				$t->Save();
				unset($t);
			}

            // save 360 x 360 templates
			$templates360x360 = explode( ',', 'Mint chocolate,Single red rose (top left),Red flowers,Red gift bag,Blue ribbon,Small gold ribbon,Small red ribbon' );

			foreach( $templates360x360 as $template ) {
				$t = new VoucherPress_Template();
				$t->name = $template;
				$t->size = '360x360';
				$t->blog_id = 0;
				$t->user_id = 0;
				$t->live = 1;
				$t->Save();
				unset($t);
			}
		}
	}
}
?>