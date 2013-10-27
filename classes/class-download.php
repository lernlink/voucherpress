<?php
// manages an individual VoucherPress download
class VoucherPress_Download {

	// properties
	var $voucher_id;
	var $voucher_name;
	var $time;
	var $downloaded;
	var $email;
	var $name;
	var $code;
	var $guid;
	var $ip;
    var $data;
    var $dataText;

	// loads a download from the database using the guid
	function Load( $guid ) {
		global $wpdb;

		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare("SELECT d.id, v.id as voucher_id, v.name as voucher_name, d.time,
			d.ip, d.name, d.email, d.guid, d.code, d.downloaded, data
			FROM {$prefix}voucherpress_downloads d
			INNER JOIN {$prefix}voucherpress_vouchers v ON v.id = d.voucherid
			WHERE d.guid = %s",
			$guid);

		voucherpress_debug( $sql );

		$row = $wpdb->get_row( $sql );
		if ( is_object( $row ) && $row->id != "" ) {
			$this->MapRow($row);
			return $this;
		} else {
			return false;
		}
	}

	// register a person to download an email-required voucher
	function Register( $voucher, $email, $name, $post ) {
		global $wpdb;

		$prefix = voucherpress_get_db_prefix();

        // call the pre register action
        do_action( 'voucherpress_pre_register_download', $this );

		// if the voucher has been found
		if ( $voucher ) {

			// if the email address has already been registered
			$sql = $wpdb->prepare( "SELECT guid FROM " . $prefix . "voucherpress_downloads WHERE voucherid = %d AND email = %s;",
				$voucher->id,
				$email );
			$guid = $wpdb->get_var( $sql );

			if ( '' == $guid )
			{

				// get the IP address
				$ip = voucherpress_ip();

				// create the code
				$code = $voucher->CreateCode();

				// create the guid
				$guid = voucherpress_guid( 36 );

                // parse any extra registration fields
                $data = NULL;
                if ( $voucher->settings && count( $voucher->settings ) > 0 ) {
                    $fields = $voucher->settings['registration_fields'];
                    foreach($fields as $field) {
                        $safename = sanitize_title( $field['name'] );
                        $data[$field['name']] = $post[$safename];
                    }
                }
                $dataText = maybe_serialize( $data );

				// insert the new download
				$sql = $wpdb->prepare( "INSERT INTO {$prefix}voucherpress_downloads
				(voucherid, time, email, name, ip, code, guid, downloaded, data)
				values
				(%d, %d, %s, %s, %s, %s, %s, 0, %s)",
				$voucher->id, time(), $email, $name, $ip, $code, $guid, $dataText );
				voucherpress_debug( $sql );
				$wpdb->query( $sql );

			}

            // call the post register action
            do_action( 'voucherpress_post_register_download', $this, $guid );

			return $guid;
		}
		return false;
	}

	// sets this download as being downloaded
	function Download() {
		global $wpdb;
		
		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare( "UPDATE {$prefix}voucherpress_downloads SET downloaded = (downloaded + 1) WHERE voucherid = %d AND guid = %s;", $this->voucher_id, $this->guid );
		voucherpress_debug( $sql );
		return $wpdb->query( $sql );
	}

	// maps a database row to this object
	function MapRow( $row ) {
		$this->voucher_id = $row->voucher_id;
		$this->voucher_name = $row->voucher_name;
		$this->time = $row->time;
		$this->downloaded = $row->downloaded;
		$this->email = $row->email;
		$this->name = $row->name;
		$this->code = $row->code;
		$this->guid = $row->guid;
		$this->ip = $row->ip;

        // other data fields are stored as a serialized object
        $this->dataText = $row->data;
        $this->data = maybe_unserialize( $row->data );

        // call the post maprow action
        do_action( 'voucherpress_post_download_maprow', $this );
	}

}
?>