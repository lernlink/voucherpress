<?php
// process a shortcode for a voucher
function voucher_do_voucher_shortcode( $atts ) {

	extract( shortcode_atts( array(
		'id' => '',
		'preview' => '',
		'description' => 'false',
        'count' => 'false'
	), $atts ) );

	if ( '' != $id ) {

        // load the voucher with this id
		voucherpress_include( 'classes/class-voucher.php' );
		$voucher = new VoucherPress_Voucher();
		$voucher->Load( $id );

        // if the voucher could be found and the start/expiry is correct
		if ( $voucher &&
			( '' == $voucher->expiry || 0 == (int)$voucher->expiry || (int)$voucher->expiry > time() )
			&& ( '' == $voucher->startdate || 0 == (int)$voucher->startdate || (int)$voucher->startdate < time() ) ) {

            // if showing the preview
			if ( 'true' == $preview ) {

                // get the template
		        voucherpress_include( 'classes/class-template.php' );
		        $template = new VoucherPress_Template();
		        $template->Load( $voucher->template );

				$r = '<a href="' . voucherpress_link( $voucher->guid ) . '" class="voucher-link"><img src="' . $this->size . '/voucherpress/templates/' . $template->size . '/' . $template->id . '_thumb.jpg" alt="' . htmlspecialchars( $voucher->name ) . '" /></a>';

            // just show the link
			} else {

				$r = '<a href="' . voucherpress_link( $voucher->guid ) . '" class="voucher-link">' . htmlspecialchars( $voucher->name ) . '</a>';

			}

            // if showing the description
			if ( 'true' == strtolower( $description ) ) {
				$r .= ' <span class="voucher-description">' . $voucher->description . '</span>';
			}

            // if showing the count
            if ( 'true' == strtolower( $count ) && $voucher->limit > 0 ) {
                $remaining = $voucher->limit - $voucher->downloads;
                $r .= ' <span class="voucher-remaining">' . sprintf( _n( '%d voucher remaining', '%d vouchers remaining', $remaining ), $remaining ) . '</span>';
            }

			return $r;
        }

        // the voucher was not found, or the start/expiry dates are not valid
		$r = "<!-- The shortcode for voucher $id is displaying nothing because the voucher was not found, or the expiry date has passed, or the start date is still in the future";

        // if the voucher has been found the start/expiry dates must be invalid
		if ( $voucher ) {
			$r .= ". Voucher found, expiry: {$voucher->expiry}, start date: {$voucher->startdate}";
		}

		$r .= ' -->';
		return $r;
	}
}

// process a shortcode for a voucher form
function voucher_do_voucher_form_shortcode( $atts ) {

	extract( shortcode_atts( array(
		'id' => ''
	), $atts ) );

	if ( '' != $id ) {

        // load the voucher
		voucherpress_include( 'classes/class-voucher.php' );
		$voucher = new VoucherPress_Voucher();
		$voucher->Load( $id );

        // if the voucher is valid
		if ( $voucher && $voucher->isValid ) {
            voucherpress_include( 'public_pages/register.php' );
			return voucherpress_form_content( "", $voucher->guid, true );
        }

        // if the voucher is not valid
		$r = "<!-- The form shortcode for voucher {$id} is displaying nothing because the voucher was not found, or the expiry date has passed, or the start date is still in the future";
		if ( $voucher ) {
			$r .= ". Voucher found, expiry: {$voucher->expiry}, start date: {$voucher->startdate}";
		}
		$r .= ' -->';
		return $r;
	}
}

// process a shortcode for a list of vouchers
function voucher_do_list_shortcode( $atts ) {

	extract( shortcode_atts( array(
		'description' => 'false'
	), $atts ) );

	voucherpress_include( 'managers/manager-vouchers.php' );
	$voucherManager = new VoucherPress_VoucherManager();
	$vouchers = $voucherManager->GetVouchers( 0 );

    // if there are vouchers to display
	if ( $vouchers && is_array( $vouchers ) && count( $vouchers ) > 0 ) {

		$r = '<ul class=\"voucherlist\">\n';

		foreach( $vouchers as $voucher ) {

			$r .= '<li><a href="' . voucherpress_link( $voucher->guid ) . '">' . htmlspecialchars( $voucher->name ) . '</a>';
			if ( 'true' == strtolower( $description ) ) {
				$r .= '<br />' . $voucher->description;
			}
			$r .= '</li>';

		}

		$r .= '</ul>';

		return $r;
	}
}

// how the number of downloads for a voucher
function voucher_do_downloads_shortcode( $atts ) {

	extract( shortcode_atts( array(
		'id' => ''
	), $atts ) );

	if ( '' != $id ) {

        // load the voucher
		voucherpress_include( 'classes/class-voucher.php' );
		$voucher = new VoucherPress_Voucher();
		$voucher->Load( $id );

        // if the voucher is valid
		if ( $voucher
				&& ( '' == $voucher->expiry || (int)$voucher->expiry == 0 || (int)$voucher->expiry > time() )
				&& ( '' == $voucher->startdate || (int)$voucher->startdate == 0 || (int)$voucher->startdate < time() ) ) {
			return $voucher->downloads;
		}

        // if the voucher is not valid
		$r = "<!-- The number of downloads for voucher {$id} is not available because the voucher was not found, or the expiry date has passed, or the start date is still in the future";
		if ( $voucher ) {
			$r .= ". Voucher found, expiry: {$voucher->expiry}, start date: {$voucher->startdate}";
		}
		$r .= ' -->';
		return $r;
	}
}
?>