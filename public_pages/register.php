<?php
// show the registration form
function voucherpress_do_register_form() {

    query_posts( 'posts_per_page=1' );

    global $wp_query;
    $wp_query->is_home = false;
    $wp_query->is_page = true;
    $wp_query->is_single = true;
    $wp_query->is_singular = true;

    add_filter( 'the_title', 'voucherpress_form_title' );
    add_filter( 'the_content', 'voucherpress_form_content' );
    add_filter( 'comments_open', 'voucherpress_comment_status', 10 , 2 );

    if ( file_exists( TEMPLATEPATH . '/page.php' ) ) {
        include( get_page_template() );
    }
    elseif ( file_exists(TEMPLATEPATH . '/single.php' ) ) {
        include( get_single_template() );
    }
    else {
        include( TEMPLATEPATH . '/index.php' );
    }

    exit();
}

function voucherpress_comment_status( $open, $post_id ) {
    return false;
}

function voucherpress_form_title( $title ) {
    if( ! in_the_loop() )
		return $title;

    return __( 'Register for this voucher' );
}

function voucherpress_form_content( $content, $voucher_guid = '', $plain = false ) {

    if( !in_the_loop() ) return $content;
    if ( !$voucher_guid || '' == $voucher_guid ) $voucher_guid = $_GET['voucher'];

    $out = '';
	$showform = true;

    // get the voucher
    $voucher = new VoucherPress_Voucher();
	$voucher->Load( $voucher_guid );

	// if registering
	if ( '' != @$_POST['voucher_email'] && '' != @$_POST['voucher_name'] ) {

		if ( is_email( trim( $_POST['voucher_email'] ) ) ) {

			// register the email address
            voucherpress_include( 'classes/class-download.php' );
            $download = new VoucherPress_Download();
			$download_guid = $download->Register(
                $voucher,
                trim( $_POST['voucher_email'] ),
                trim( $_POST['voucher_name'] ),
                $_POST
            );

			// if the guid has been generated
			if ( $download_guid ) {

				$message = '';
				if ( $voucher->description != '' ) {
					$message .= $voucher->description . '\n\n';
				}
				$message .= __( 'You have successfully registered to download this voucher, please download the voucher from here:', 'voucherpress' ) . '\n\n' . voucherpress_link( $voucher_guid, $download_guid, false );

				// send the email
				wp_mail( trim( $_POST['voucher_email'] ), $voucher->name . ' for ' . trim( $_POST['voucher_name'] ), $message );

				do_action( 'voucherpress_register', $voucher->id, $voucher->name, $_POST['voucher_email'], $_POST['voucher_name'] );

				$out .= '
				<p>' .  __( "Thank you for registering. You will shortly receive an email sent to '" . trim($_POST["voucher_email"]) . "' with a link to your personalised voucher.", 'voucherpress' ) . '</p>
				';
				$showform = false;

			} else {

				$out .= '
				<p>' .  __( 'Sorry, your email address and name could not be registered. Have you already registered for this voucher? Please try again.', 'voucherpress' ) . '</p>
				';

			}

		} else {

			$out .= '
			<p>' .  __( 'Sorry, your email address was not valid. Please try again.', 'voucherpress' ) . '</p>
			';

		}
	}

	if ( $showform )
	{
		if ( !$plain ) {
		$out .= '
		<h2>' . __( 'Please provide some details', 'voucherpress' ) . '</h2>
		<p>' .  __( 'To download this voucher you must provide your name and email address. You will then receive a link by email to download your personalised voucher.', 'voucherpress' ) . '</p>
		<form action="' . voucherpress_link( $voucher_guid ) . '" method="post" class="voucherpress_form">
		';
		} else {
		$out .= '
		<form action="' . voucherpress_page_url() . '" method="post" class="voucherpress_form">
		';
		}

		$out .= '
		<p><label for="voucher_email">' .  __( 'Your email address', 'voucherpress' ) . '</label>
		<input type="text" name="voucher_email" id="voucher_email" value="' . trim( @$_POST['voucher_email'] ) . '" /></p>
		<p><label for="voucher_name">' .  __( 'Your name', 'voucherpress' ) . '</label>
		<input type="text" name="voucher_name" id="voucher_name" value="' . trim( @$_POST['voucher_name'] ) . '" /></p>';

        if ( $voucher->settings && count( $voucher->settings ) > 0 ) {
            $fields = $voucher->settings['registration_fields'];
            foreach($fields as $field) {

                // sanitise the field name
                $safename = sanitize_title( $field['name'] );

                if ( trim( $field['helptext'] ) != '' ){
                    $out .= '<p>' . $field['helptext'] . '</p>';
                }

                $out .= '
                <p><label for="' . $safename . '">' .  $field['name'] . '</label>
		        <input type="text" name="' . $safename . '" id="' . $safename . '" value="' . trim( @$_POST[$safename] ) . '" /></p>
                ';
            }
        }

        $out .= '
		<p><input type="submit" name="voucher_submit" id="voucher_submit" value="' .  __( 'Register for this voucher', 'voucherpress' ) . '" /></p>
		</form>
	';
	}

    remove_filter( 'the_content', 'voucherpress_form_content' );

	return $out;
}
?>