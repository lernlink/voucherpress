<?php
// manages downloads, for instance getting multiple downloads

class VoucherPress_DownloadManager {

	// get the downloads for a voucher
	function GetVoucherDownloads( $voucher ) {

        $voucherid = 0;
        if ( $voucher ) $voucherid = $voucher->id;
        voucherpress_include( 'classes/class-download.php' );

		global $wpdb, $current_blog;

		$blog_id = 1;
		if ( is_object( $current_blog ) ) { $blog_id = $current_blog->blog_id; }

		$prefix = voucherpress_get_db_prefix();

		$sql = $wpdb->prepare( "SELECT v.id as voucher_id, v.name as voucher_name,
			d.time, d.downloaded, d.email, d.name, d.code, d.guid, d.data
			FROM {$prefix}voucherpress_downloads d
			INNER JOIN {$prefix}voucherpress_vouchers v ON v.id = d.voucherid
			WHERE (%d = 0 OR voucherid = %d)
			AND deleted = 0
			AND v.blog_id = %d;",
			$voucherid, $voucherid, $blog_id );

        voucherpress_debug( $sql );

        $rows = $wpdb->get_results( $sql );

		$downloads = array();
		foreach( $rows as $row ) {
			$download = new VoucherPress_Download();
			$download->MapRow( $row );
			$downloads[] = $download;
		}
		return $downloads;
	}

	// download a list of registrations
	function DownloadRegistrations( $voucher ) {

		$regs = $this->GetVoucherDownloads( $voucher );

		if ( $regs && is_array( $regs ) && count( $regs ) > 0 ) {

			header( 'Content-type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="voucher-emails.csv"' );

            // write out the basic fields, plus any additional fields for this voucher
			echo 'Voucher,Datestamp,Name,Email,';
            if ( $voucher->settings && count( $voucher->settings ) > 0 ) {
                $fields = $voucher->settings['registration_fields'];
                foreach($fields as $field) {
                    $fieldnames[] = $field['name'];
                    echo $field['name'] . ',';
                }
            }
            echo 'Code\n';

            // write out the registrations
			foreach( $regs as $reg ) {
				echo $voucher->name . ','
                    . str_replace( ',', '', date( 'r', $reg->time ) ) . ','
                    . htmlspecialchars( $reg->name ) . ','
                    . htmlspecialchars( $reg->email ) . ',';
                if ( $reg->data && count( $reg->data ) > 0 ) {
                    foreach( $fieldnames as $fieldname ) {
                        echo $reg->data[$fieldname] . ',';
                    }
                }
                echo htmlspecialchars( $reg->code ) . '\n';
			}
			exit();
		} else {
			return false;
		}
	}
}
?>