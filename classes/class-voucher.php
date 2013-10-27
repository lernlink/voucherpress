<?php
// manages an individual VoucherPress voucher
class VoucherPress_Voucher {

    // properties, which map to table columns
    var $id;
    var $guid;
    var $name;
    var $text;
    var $html;
    var $description;
    var $terms;
    var $template;
    var $font;
    var $require_email;
    var $registered_name;
        var $registered_email;
    var $limit;
    var $live;
    var $startdate;
    var $expiry;
    var $codestype;
    var $codelength;
    var $codeprefix;
    var $codesuffix;
    var $codes;
    var $blog_id;
    var $user_id;
    var $downloads;
        var $unavailable_reason;
        var $settings;
        var $settingsText;
        var $isValid;

    // loads this voucher from the database
    function Load( $voucher, $live = 1, $code = '', $unexpired = 0 ) {

        $blog_id = voucherpress_blog_id();

        global $wpdb;

        $prefix = voucherpress_get_db_prefix();

        // get by id
        if ( is_numeric( $voucher ) ) {
            $sql = $wpdb->prepare( "SELECT v.id, v.name, v.html, v.`text`, v.`description`, v.terms, v.font,
            v.template, v.require_email, v.`limit`, v.startdate, v.expiry, v.guid, v.live, '' as registered_email,
            '' as registered_name, v.user_id, v.blog_id,
            v.codestype, v.codeprefix, v.codesuffix, v.codelength, v.codes, v.settings,
            (SELECT COUNT(d.id) FROM {$prefix}voucherpress_downloads d WHERE d.voucherid = v.id AND d.downloaded > 0) AS downloads
            FROM " . $prefix . "voucherpress_vouchers v
            WHERE
            (%s = '0' OR v.live = 1)
            AND (%s = '0' OR (expiry = '' OR expiry = 0 OR expiry > %d))
            AND (%s = '0' OR (startdate = '' OR startdate = 0 OR startdate <= %d))
            AND v.id = %d
            AND v.deleted = 0
            AND v.blog_id = %d",
            $live, $live, time(), $live, time(), $voucher, $blog_id );

        // get by guid
        } else {

            // if a download code has been specified
            if ( $code != '') {
                $sql = $wpdb->prepare( "SELECT v.id, v.name, v.html, v.`text`, v.`description`, v.terms, v.font,
                v.template, v.require_email, v.`limit`, v.startdate, v.expiry, v.guid, v.live, r.email as registered_email,
                r.name as registered_name, v.user_id, v.blog_id,
                v.codestype, v.codeprefix, v.codesuffix, v.codelength, v.codes, v.settings,
                (SELECT COUNT(d.id) FROM {$prefix}voucherpress_downloads d WHERE d.voucherid = v.id AND d.downloaded > 0) AS downloads
                FROM {$prefix}voucherpress_vouchers v
                LEFT OUTER JOIN {$prefix}voucherpress_downloads r ON r.voucherid = v.id AND r.guid = %s
                WHERE
                (%s = '0' OR v.live = 1)
                AND (%s = '0' OR (expiry = '' OR expiry = 0 OR expiry > %d))
                AND (%s = '0' OR (startdate = '' OR startdate = 0 OR startdate <= %d))
                AND v.deleted = 0
                AND v.guid = %s
                AND v.blog_id = %d",
                $code, $live, $live, time(), $live, time(), $voucher, $blog_id );

            // no download code specified
            } else {
                $sql = $wpdb->prepare( "SELECT v.id, v.name, v.html, v.`text`, v.`description`, v.terms, v.font,
                v.template, v.require_email, v.`limit`, v.startdate, v.expiry, v.guid, v.live, '' as registered_email,
                '' as registered_name, v.user_id, v.blog_id,
                v.codestype, v.codeprefix, v.codesuffix, v.codelength, v.codes, v.settings,
                (SELECT COUNT(d.id) FROM {$prefix}voucherpress_downloads d WHERE d.voucherid = v.id AND d.downloaded > 0) AS downloads
                FROM {$prefix}voucherpress_vouchers v
                WHERE
                (%s = '0' OR v.live = 1)
                AND (%s = '0' OR (expiry = '' OR expiry = 0 OR expiry > %d))
                AND (%s = '0' OR (startdate = '' OR startdate = 0 OR startdate <= %d))
                AND v.deleted = 0
                AND v.guid = %s
                AND v.blog_id = %d",
                $live, $live, time(), $live, time(), $voucher, $blog_id);
            }
        }

        voucherpress_debug( $sql );

        $row = $wpdb->get_row( $sql );
        if ( is_object( $row ) && $row->id != '' ) {
            $this->MapRow($row);
            return $this;
        } else {
            return false;
        }
    }

    // get the ID of a voucher from the guid
    function GetVoucherID( $guid ) {
        $loaded = $this->Load( $guid );

        if ( $loaded )
            return $this->id;

        return false;
    }

    // creates or updates this voucher
    function Save() {
        $blog_id = voucherpress_blog_id();

        global $wpdb;

        $prefix = voucherpress_get_db_prefix();

        // call the pre save action
        do_action( 'voucherpress_pre_save_voucher', $this, $blog_id );

        // serialize the settings
        $this->settingsText = maybe_serialize($this->settings);

        // if the ID is 0 then this is a new voucher
        if ($this->id == 0) {

            $this->guid = voucherpress_guid( 36 );
            $sql = $wpdb->prepare( "INSERT INTO {$prefix}voucherpress_vouchers
            (blog_id, user_id, name, html, `description`, template, font, require_email, `limit`,
            guid, time, live, startdate, expiry, codestype, codelength, codeprefix, codesuffix, codes, deleted, settings)
            VALUES
            (%d, %s, %s, %s, %s, %d, %s, %d, %d, %s, %d, %d, %d, %d, %s, %d, %s, %s, %s, 0, %s);",
            $this->blog_id, $this->user_id, $this->name, stripslashes($this->html), $this->description, $this->template,
            $this->font, $this->require_email, $this->limit, $this->guid, time(), 1, $this->startdate, $this->expiry,
            $this->codestype, $this->codelength, $this->codeprefix, $this->codesuffix, $this->codes, $this->settingsText );

        // update an existing voucher
        } else {

            $sql = $wpdb->prepare( "UPDATE {$prefix}voucherpress_vouchers SET
                time = %d,
                name = %s,
                html = %s,
                `description` = %s,
                template = %d,
                font = %s,
                require_email = %d,
                `limit` = %d,
                live = %d,
                startdate = %d,
                expiry = %d,
                codestype = %s,
                codelength = %d,
                codeprefix = %s,
                codesuffix = %s,
                codes = %s,
                settings = %s
                WHERE id = %d
                AND blog_id = %d
                AND user_id = %d;",
                time(), $this->name, stripslashes($this->html), $this->description, $this->template, $this->font, $this->require_email, $this->limit, $this->live, $this->startdate, $this->expiry, $this->codestype, $this->codelength, $this->codeprefix, $this->codesuffix, $this->codes, $this->settingsText, $this->id, $this->blog_id, $this->user_id );
                $this->downloads = 0;

        }

        voucherpress_debug( $sql );
        $done = $wpdb->query( $sql );

        if ( $done ) {
            if ($this->id == 0) {
                $this->id = $wpdb->insert_id;
                do_action( 'voucherpress_create', $this->id, $this->name, $this->text, $this->description, $this->template, $this->require_email, $this->limit, $this->startdate, $this->expiry );
            } else {
                do_action( 'voucherpress_edit', $this->id, $this->name, $this->text, $this->description, $this->template, $this->require_email, $this->limit, $this->startdate, $this->expiry );
            }
        }

        // call the post save action
        do_action( 'voucherpress_post_save_voucher', $this, $blog_id, $done );

        return $done;
    }

    // sets this voucher as being deleted
    function Delete() {
        $blog_id = voucherpress_blog_id();

        global $wpdb;

        $prefix = voucherpress_get_db_prefix();

        // call the pre delete action
        do_action( 'voucherpress_pre_delete_voucher', $this, $blog_id );

        $sql = $wpdb->prepare( "UPDATE {$prefix}voucherpress_vouchers
        SET deleted = 1
        WHERE id = %d AND blog_id = %d AND user_id = %d;",
        $this->id, $this->blog_id, $this->user_id);
        voucherpress_debug( $sql );

        $done = $wpdb->query( $sql );

        // call the post delete action
        do_action( 'voucherpress_post_delete_voucher', $this, $blog_id, $done );

        return $done;
    }

    // check a code address is valid for a voucher
    function DownloadGuidValid( $download_guid ) {

        // a limit has been set
        if ( $this->limit > 0 ) {
            // if the limit has been reached
            if ( (int)$this->downloads >= (int)$this->limit ) {
                $this->unavailable_reason = 'runout';
                return false;
            }
        }

        // if there is an expiry and the expiry is in the past
        if ( (int)$this->expiry > 0 && (int)$this->expiry <= time() ) {
            $this->unavailable_reason = 'expired';
            return false;
        }

        // if there is a start date and the tart date is in the future
        if ( (int)$this->startdate > 0 && (int)$this->startdate > time() ) {
            $this->unavailable_reason = 'notyetavailable';
            return false;
        }

        // if emails are not required
        if ( '1' != $this->require_email ) {

            // anyone can download the voucher
            return true;
        }

        // if we get here then emails are required

        // get this download
        voucherpress_include( 'classes/class-download.php' );
        $download = new VoucherPress_Download();
        $download = $download->Load( $download_guid );

        // the user has not yet registered
        if ( !$download ) {
            $this->unavailable_reason = 'unregistered';
            return false;
        }

        // if the voucher has been downloaded using this download guid already
        if ( '0' != $download->downloaded ) {
            $this->unavailable_reason = 'downloaded';
            return false;
        }

        // if the voucher has not been downloaded by this download guid already
        return true;
    }

    // get the next custom code in the list for this voucher
    function GetCustomCode( ) {
        if ( '' != trim( $this->codes ) ) {
            $codelist = explode( '\n', $this->codes );
            if ( is_array( $codelist ) && count( $codelist ) > 0 ) {
                return trim( $codelist[0] );
            }
        }
        return voucherpress_guid();
    }

    // create a download code for thi voucher
    function CreateDownloadCode( $download_guid = '' ) {

        voucherpress_include( 'classes/class-download.php' );

        // if the download code has been set
        if ( '' != $download_guid ) {
            $download = new VoucherPress_Download();
            $loaded = $download->Load( $download_guid );
            if ( $loaded ) {
                $download->Download();
                $code = $download->code;
            }

        // no download code set
        } else {

            global $wpdb;

            $prefix = voucherpress_get_db_prefix();

            $ip = voucherpress_ip();

            $code = $this->CreateCode();

            // insert the download
            $sql = $wpdb->prepare( "INSERT INTO {$prefix}voucherpress_downloads
            (voucherid, time, ip, code, downloaded)
            VALUES
            (%d, %d, %s, %s, 1)",
            $this->id, time(), $ip, $code );
            voucherpress_debug( $sql );
            $wpdb->query( $sql );
        }

        // return this code
        return $code;
    }

    // create a code for this voucher
    function CreateCode() {
        global $wpdb;
        
        $prefix = voucherpress_get_db_prefix();

        // using custom codes
        if ( 'custom' == $this->codestype ) {

            $code = GetCustomCode();

            // set the remaining codes by removing this code
            $remaining_codes = trim( str_replace( $code, '', $this->codes ) );

            // update the codes to set this one as being used
            $sql = $wpdb->prepare( "UPDATE {$prefix}voucherpress_vouchers SET codes = %s WHERE id = %d;",
                $remaining_codes,
                $this->id );

            voucherpress_debug( $sql );
            $wpdb->query( $sql );

        // using sequential codes
        } else if ( 'sequential' == $this->codestype ) {

            // add one to the number of vouchers already downloaded
            $code = $this->codeprefix . ( (int)$this->downloads + 1 ) . $this->codesuffix;

        // using a single code
        } else if ( 'single' == $this->codestype ) {

            $code = $this->codes;

        // using random codes
        } else {

            $code = $this->codeprefix . voucherpress_guid( $this->codelength ) . $this->codesuffix;

        }

        return $code;
    }

    // checks if the template for this voucher exists on disk
    function TemplateExists() {
        voucherpress_include( 'managers/manager-templates.php' );
        $templateManager = new VoucherPress_TemplateManager();
        return $templateManager->TemplateExists( $this->template );
    }

    // downloads this voucher
    function Download( $download_guid = '' ) {

        // call the pre download action
        do_action( 'voucherpress_pre_download_voucher', $this, $download_guid );

        // check the voucher is valid for download
        if ($this->live == 1
        && $this->id != ''
        && $this->name != ''
        && $this->html != ''
        && $this->template != ''
        )
        {
            // see if this voucher can be downloaded
            $valid = $this->DownloadGuidValid( $download_guid );

            // yes, the voucher is valid
            if ( $valid ) {

                // set this download as completed
                $code = $this->CreateDownloadCode( $download_guid );

                do_action( 'voucherpress_download', $voucher->id, $voucher->name, $code );

                // render the voucher
                $this->Render( $code );

            // this voucher is not available
            } else if ( $this->unavailable_reason === 'unavailable' )  {

                print '<!-- The voucher is not available for download -->';
                voucherpress_404();

            // this voucher has run out
            } else if ( $this->unavailable_reason === 'runout' )  {

                print '<!-- The voucher has run out -->';
                voucherpress_runout();

            // this voucher has been downloaded already
            } else if ( $this->unavailable_reason === 'downloaded' )  {

                print '<!-- The voucher has already been downloaded by this person -->';
                voucherpress_downloaded();

            // this voucher has expired
            } else if ( $this->unavailable_reason === 'expired' )  {

                print '<!-- The voucher has expired -->';
                voucherpress_expired();

            // this voucher is not yet available
            } else if ( $this->unavailable_reason === 'notyetavailable' )  {

                print '<!-- The voucher is not yet available -->';
                voucherpress_notyetavailable();

            }

        // this voucher could not be found
        } else {

            print '<!-- The voucher could not be found -->';
            voucherpress_404(false);

        }
    }

    // renders this voucher
    function Render( $code ) {
        global $current_user;

        // get the template manager
        voucherpress_include( 'managers/manager-templates.php' );
        $templateManager = new VoucherPress_TemplateManager();

        // get the template
        voucherpress_include( 'classes/class-template.php' );
        $template = new VoucherPress_Template();
        $template->Load( $this->template );

        // get the voucher template image
        if( '' != $template->id )
        {
            // call the pre render action
            do_action( 'voucherpress_pre_render_voucher', $this, $code );

            $slug = $this->GenerateSlug();

            // include the TCPDF class and VoucherPress PDF class
            voucherpress_include( 'voucherpress_pdf.php' );

            // create new PDF document
            $pdf = new VoucherPressPdf( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

            voucherpress_debug( 'Voucher PDF being created' );
            voucherpress_debug( 'Orientation: ' . PDF_PAGE_ORIENTATION );
            voucherpress_debug( 'Unit: ' . PDF_UNIT );
            voucherpress_debug( 'Page format: ' . PDF_PAGE_FORMAT );

            // set the properties
            $imagepath = $templateManager->GetTemplatePath( $template );
            $pdf->voucher_image = $imagepath;
            $pdf->voucher_image_w = $template->width;
            $pdf->voucher_image_h = $template->height;
            $pdf->voucher_image_dpi = 150;

            voucherpress_debug( "Image: $imagepath ({$template->width}x{$template->height})" );

            // set document information
            $pdf->SetCreator( PDF_CREATOR );
            $pdf->SetAuthor( $current_user->user_nicename );
            $pdf->SetTitle( $this->name );

            // set header and footer fonts
            $pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );

            // set default monospaced font
            $pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

            //set margins
            $pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
            $pdf->SetHeaderMargin( 0 );
            $pdf->SetFooterMargin( 0 );

            // remove default footer
            $pdf->setPrintFooter( false );

            //set auto page breaks
            $pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );

            //set image scale factor
            $pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

            // debug the image scale ratio
            voucherpress_debug( 'Image scale ratio: ' . PDF_IMAGE_SCALE_RATIO );

            //set some language-dependent strings
            $pdf->setLanguageArray( '' ); // eh?

            // set top margin
            $pdf->SetTopMargin( 15 );

            // add a page
            $pdf->AddPage( 'L', array( $template->width, $template->height ) );

            // debug the page dimensions
            voucherpress_debug( 'Adding page: ' . $template->width . "x" . $template->height );

            // prepare the HTML for render
            $this->PrepareHTML( $code );

            $pdf->SetCellPadding( 0 );
            $pdf->SetCellHeightRatio( 0.7 );

            // print html
            $pdf->writeHTML( stripslashes( $this->html ), $ln=true, $fill=false, $reseth=false, $cell=false, $align='L');
            voucherpress_debug( 'HTML: <textarea style="width:100%;height:20em">' . $this->html . '</textarea>' );

            // if debugging stop here
            if ( voucherpress_debugging() ) exit();

            // add headers to stop caching
            header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
            header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
            header( 'Cache-Control: no-store, no-cache, must-revalidate' );
            header( 'Cache-Control: post-check=0, pre-check=0', false );
            header( 'Pragma: no-cache' );
            header( 'Content-type: application/octet-stream' );
            header( 'Content-Disposition: attachment; filename="' . $slug . '.pdf"' );

            // close and output PDF document
            $pdf->Output( $slug . '.pdf', 'D' );

            exit();

        }

        // if we get here something has gone wrong
        return false;
    }

    // prepares the HTML for render
    function PrepareHTML( $code ) {

        // replace placeholders
        if ( $this->expiry != '' && (int)$this->expiry > 0 ) {
            $this->html = str_replace( '[expiry]', date( 'Y/m/d', $this->expiry ) );
        } else if ( '' != $this->settings['days_after_download'] && '0' != $this->settings['days_after_download'] ) {
            $days = (int)$this->settings['days_after_download'];
            $this->html = str_replace( '[expiry]', date( 'Y/m/d', time() + ( $days * 24 * 60 * 60 ) ) );
        }
        $this->html = str_replace( '[code]', $code, $this->html );
        $this->html = str_replace( '[date]', date( 'Y/m/d', $this->expiry ), $this->html );
        $this->html = str_replace( '[name]', $this->registered_name, $this->html );
        $this->html = str_replace( '[email]', $this->registered_email, $this->html );

        // placeholders for the current users details
        $current_user = wp_get_current_user();
        if ( 0 == $current_user->ID ) {
            $this->html = str_replace( '[user-login]', '', $this->html );
            $this->html = str_replace( '[user-firstname]', '', $this->html );
            $this->html = str_replace( '[user-lastname]', '', $this->html );
            $this->html = str_replace( '[user-email]', '', $this->html );
            $this->html = str_replace( '[user-displayname]', '', $this->html );
        } else {
            $this->html = str_replace( '[user-login]', $current_user->user_login, $this->html );
            $this->html = str_replace( '[user-firstname]', $current_user->user_firstname, $this->html );
            $this->html = str_replace( '[user-lastname]', $current_user->user_lastname, $this->html );
            $this->html = str_replace( '[user-email]', $current_user->user_email, $this->html );
            $this->html = str_replace( '[user-displayname]', $current_user->display_name, $this->html );
        }

        // increase the size of images by 4 so they print the right size
        // this will lead to pixellated images, but hey
        //$this->html = preg_replace("width=\"([0-9]+)\'', "width=\"500\'', $this->html);
        //$this->html = preg_replace("height=\"([0-9]+)\'', "height=\"500\'', $this->html);

        // set CSS
        $this->html = '<style>
h1 {
    font-size: 162pt;
    font-weight: normal;
    margin: 0;
}
h2 {
    font-size: 144pt;
    font-weight: normal;
    margin: 0;
}
h3 {
    font-size: 120pt;
    font-weight: normal;
    margin: 0;
}
h4 {
    font-size: 96pt;
    font-weight: normal;
    margin: 0;
}
h5 {
    font-size: 72pt;
    font-weight: normal;
    margin: 0;
}
h6 {
    font-size: 60pt;
    font-weight: normal;
    margin: 0;
}
p, ul, ol {
    font-size: 48pt;
    margin: 0;
}
</style>' . $this->html;
    }

    // tests this voucher
    function Test( $width, $height ) {
        global $current_user;

        // get the template manager
        voucherpress_include( 'managers/manager-templates.php' );
        $templateManager = new VoucherPress_TemplateManager();

        // get the template
        voucherpress_include( 'classes/class-template.php' );
        $template = new VoucherPress_Template();
        $template->Load( $this->template );

        // get the voucher template image
        if( '' != $template->id )
        {
            $slug = $this->GenerateSlug();

            // include the TCPDF class and VoucherPress PDF class
            voucherpress_include( 'voucherpress_pdf.php' );

            // create new PDF document
            $pdf = new VoucherPressPdf( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

            voucherpress_debug( 'Voucher PDF being created' );
            voucherpress_debug( 'Orientation: ' . PDF_PAGE_ORIENTATION );
            voucherpress_debug( 'Unit: ' . PDF_UNIT );
            voucherpress_debug( 'Page format: ' . PDF_PAGE_FORMAT );

            // set the properties
            $imagepath = $templateManager->GetTemplatePath( $template );
            $pdf->voucher_image = $imagepath;
            $pdf->voucher_image_w = $width;
            $pdf->voucher_image_h = $height;
            $pdf->voucher_image_dpi = 150;

            voucherpress_debug( "Image: $imagepath ({$template->width}x{$template->height})" );

            // set document information
            $pdf->SetCreator( PDF_CREATOR );
            $pdf->SetAuthor( $current_user->user_nicename );
            $pdf->SetTitle( $this->name );

            // set header and footer fonts
            $pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );

            // set default monospaced font
            $pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

            //set margins
            $pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
            $pdf->SetHeaderMargin( 0 );
            $pdf->SetFooterMargin( 0 );

            // remove default footer
            $pdf->setPrintFooter( false );

            //set auto page breaks
            $pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );

            //set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // debug image scale ratio
            voucherpress_debug( 'Image scale ratio: ' . PDF_IMAGE_SCALE_RATIO );

            //set some language-dependent strings
            $pdf->setLanguageArray(''); // eh?

            // set top margin
            $pdf->SetTopMargin(15);

            // add a page
            $pdf->AddPage( 'L', array( $width,$height ) );

            // debug page dimensions
            voucherpress_debug( "Adding page: {$width}x{$height}" );

            // set font
            $pdf->SetFont( $this->font, '', 18 );

            // prepare the HTML for render
            $this->PrepareHTML( 'TEST' );

            // print html
            $pdf->writeHTML( stripslashes( $this->html ), $ln=true, $fill=false, $reseth=false, $cell=false, $align='C');
            voucherpress_debug( 'HTML: ' . $this->html );

            // if debugging stop here
            if ( voucherpress_debugging() ) exit();

            // add headers to stop caching
            header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
            header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
            header( 'Cache-Control: no-store, no-cache, must-revalidate' );
            header( 'Cache-Control: post-check=0, pre-check=0', false );
            header( 'Pragma: no-cache' );
            header( 'Content-type: application/octet-stream' );
            header( 'Content-Disposition: attachment; filename="' . $slug . '.pdf"' );

            // close and output PDF document
            $pdf->Output( 'test.pdf', 'D' );

            exit();

        } else {

            return false;

        }
    }

    // create slug
    // Bramus! pwnge! : simple method to create a post slug (http://www.bram.us/)
    function GenerateSlug() {
        $slug = preg_replace( '/[^a-zA-Z0-9 -]/', '', $this->name );
        $slug = str_replace( ' ', '-', $slug );
        $slug = strtolower( $slug );
        return $slug;
    }

    // maps a database row to this voucher object
    function MapRow($row) {
        $this->id = $row->id;
        $this->guid = $row->guid;
        $this->name = $row->name;
        $this->html = $row->html;
        $this->description = $row->description;
        $this->template = $row->template;
        $this->font = $row->font;
        $this->require_email = $row->require_email;
        $this->registered_name = $row->registered_name;
        $this->registered_email = $row->registered_email;
        $this->limit = $row->limit;
        $this->live = $row->live;
        $this->startdate = $row->startdate;
        $this->expiry = $row->expiry;
        $this->codestype = $row->codestype;
        $this->codelength = $row->codelength;
        $this->codeprefix = $row->codeprefix;
        $this->codesuffix = $row->codesuffix;
        $this->codes = $row->codes;
        $this->blog_id = $row->blog_id;
        $this->user_id = $row->user_id;
        $this->downloads = $row->downloads;

        // set whether this voucher is valid for download
        $this->isValid = false;
        if (
                (
                '' == $this->expiry ||
                (int)$this->expiry == 0 ||
                (int)$this->expiry > time()
                )
                &&
                (
                    '' == $this->startdate ||
                    (int)$this->startdate == 0 ||
                    (int)$this->startdate < time()
                )
                &&
                (
                    '1' == $this->live
                )
            ) { $this->isValid = true; }

        // other settings are stored as a serialized object
        $this->settingsText = $row->settings;
        $this->settings = maybe_unserialize( $row->settings );

        if ($this->name == '') {
            $this->name = 'Voucher';
        }

        // for old-style vouchers, create the HTML
        if ( '' == $this->html && ( '' != $this->name && '' != $this->terms ) ) {
            $this->html = "<h1>{$row->name}</h1><p>{$row->text}</p><p>[" . __( "CODE", "voucherpress" ) . "]</p><h6>{$row->terms}</h6>";
        }

        // call the post maprow action
        do_action( 'voucherpress_post_voucher_maprow', $this );
    }
}
?>