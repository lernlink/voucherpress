<?php
// manages an individual VocuherPress template
class VoucherPress_Template {

	// properties, which map to table columns
	var $id;
	var $blog_id;
	var $user_id;
	var $name;
	var $size;
	var $width;
	var $height;
	var $live;

	function Load( $id ) {
		global $wpdb;

		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare( "SELECT id, blog_id, user_id, name, size, time, live
				FROM {$prefix}voucherpress_templates
				WHERE id = %d;",
				$id);

		voucherpress_debug( $sql );

		$row = $wpdb->get_row( $sql );
		if ( is_object( $row ) && $row->id != "" ) {
			$this->MapRow($row);
			return $this;
		} else {
			return false;
		}
	}

	function Save() {
		global $wpdb;

		$prefix = voucherpress_get_db_prefix();

		$blog_id = voucherpress_blog_id();
		$user_id = voucherpress_user_id();

		if ( $this->id == 0 ) {

			$sql = $wpdb->prepare( "INSERT INTO {$prefix}voucherpress_templates
			(blog_id, user_id, name, size, time, live)
			VALUES
			(%d, %d, %s, %s, %d, 1);",
			$this->blog_id, $this->user_id, $this->name, $this->size, time() );

		} else {

			$sql = $wpdb->prepare( "UPDATE {$prefix}voucherpress_templates SET
			name = %s,
			size = %s,
			live = %d
			WHERE id = %d
			AND blog_id = %d
			AND (user_id = 0 OR user_id = %d);",
			$this->name, $this->live, $this->id, $this->blog_id, $this->user_id );
		}
		voucherpress_debug( $sql );
		$done = $wpdb->query( $sql );
		if ( $done ) return true;
		return false;
	}

	// uploads the given file to be the background for this template
	function Upload( $file ) {
		$file = $file['tmp_name'];

		// get the image size
		$imagesize = getimagesize( $file );
		$width = $imagesize[0];
		$height = $imagesize[1];
		$imagetype = $imagesize[2];

        // work out what the size of the voucher will be, taking into consideration 150dpi
        $voucherwidth = round( $width * 0.677 );
        $voucherheight = round( $height * 0.677 );
        $this->size = $voucherwidth . 'x' . $voucherheight;

		// if the imagesize could be fetched and is JPG, PNG or GIF
		if ( $imagetype == 2 ) {
			$path = WP_PLUGIN_DIR . '/voucherpress/templates/' . $this->size;

			// move the temporary file to the full-size image
			$fullpath = $path . $this->id . '.jpg';
			move_uploaded_file( $file, $fullpath );

			// get the image
			$image = imagecreatefromjpeg( $fullpath );

			// create the preview image
			$preview = imagecreatetruecolor( $voucherwidth, $voucherheight );
			imagecopyresampled( $preview, $image, 0, 0, 0, 0, $voucherwidth, $voucherheight, $width, $height );
			$previewpath = $path . $this->id . '_preview.jpg';
			imagejpeg( $preview, $previewpath, 80 );

			// create the thumbnail image
			$thumb = imagecreatetruecolor( $voucherwidth/4, $voucherheight/4 );
			imagecopyresampled( $thumb, $image, 0, 0, 0, 0, $voucherwidth/4, $voucherheight/4, $width, $height );
			$thumbpath = $path . $this->id . '_thumb.jpg';
			imagejpeg( $thumb, $thumbpath, 70 );

			return true;
		}
		return false;
	}

	// maps a database row to this template object
	function MapRow( $row ) {
		$this->id = $row->id;
		$this->blog_id = $row->blog_id;
		$this->user_id = $row->user_id;
		$this->name = $row->name;
		$this->size = $row->size;
		if ( $this->size == "" ) $this->size = '800x360';
		$parts = explode( "x", $row->size );
		$this->width = $parts[0];
		$this->height = $parts[1];
		$this->live = $row->live;

        // call the post maprow action
        do_action( 'voucherpress_post_template_maprow', $this );
	}
}
?>