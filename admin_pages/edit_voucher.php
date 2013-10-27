<?php
// show the edit voucher page

//check install
voucherpress_include( "setup/installer.php" );
voucherpress_check_install();

// include the required managers
voucherpress_include( "managers/manager-fonts.php" );
$fontManager = new VoucherPress_FontManager();
voucherpress_include( "managers/manager-templates.php" );
$templateManager = new VoucherPress_TemplateManager();
voucherpress_include( "classes/class-template.php" );

// get the voucher
voucherpress_include( "classes/class-voucher.php" );
$voucher = new VoucherPress_Voucher();
$voucher->Load( @$_GET["id"], 0 );

voucherpress_report_header();

if ( $voucher && is_object( $voucher ) )
{

	// get the template for this voucher
	$template = new VoucherPress_Template();
	$template->Load( $voucher->template );
	
	// get the fonts available
	$fonts = $fontManager->GetAllFonts();
	
	// set the font string for use in TinyMCE
	$fontstring = '';
	foreach($fonts as $font) {
		$fontstring .= $font->name . '=' . $font->filename . ';';
	}
	$fontstring = trim($fontstring, ';');

	echo '
	<h2>' . __( "Edit voucher:", "voucherpress" ) . ' ' . htmlspecialchars( stripslashes( $voucher->name ) ) . ' <span class="r">';
	
	if ( $voucher->downloads > 0 ) {
		echo __( "Downloads:", "voucherpress" ) . " " . $voucher->downloads;
		echo ' | <a href="' . wp_nonce_url( "admin.php?page=vouchers&amp;download=emails&amp;voucher=" . $voucher->id, "voucherpress_download_csv" ) . '">' . __( "CSV", "voucherpress" ) . '</a>';
		echo ' | ';
	}
	
	echo '</h2>
	';
	
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
		if ( @$_GET["result"] == "4" ) {
			echo '
			<div id="message" class="updated fade">
				<p><strong>' . __( "The voucher has been deleted.", "voucherpress" ) . '</strong></p>
			</div>
			';
		}
		if ( @$_GET["result"] == "5" ) {
			echo '
			<div id="message" class="error">
				<p><strong>' . __( "The voucher could not be deleted.", "voucherpress" ) . '</strong></p>
			</div>
			';
		}
	}
	
	// if this voucher has an expiry date which has passed
	if ( $voucher->expiry != "" && (int)$voucher->expiry != 0 && (int)$voucher->expiry <= time() ) {
		echo '
		<div id="message" class="updated fade">
			<p><strong>' . sprintf( __( "This voucher expired on %s. Change the expiry date below to allow this voucher to be downloaded.", "voucherpress" ), date( "Y/m/d", $voucher->expiry ) ) . '</strong></p>
		</div>
		';
	}
	
	// if this voucher has a start date not yet reached
	if ( $voucher->startdate != "" && (int)$voucher->startdate != 0 && (int)$voucher->startdate > time() ) {
		echo '
		<div id="message" class="updated fade">
			<p><strong>' . __( "This voucher is not yet available. Change the start date below to allow this voucher to be downloaded.", "voucherpress" ) . '</strong></p>
		</div>
		';
	}
	
	echo '
	<form action="admin.php?page=vouchers&amp;id=' . $_GET["id"] . '" method="post" id="voucherform">
	
	<p class="alignright textright">
		<input type="button" name="preview" id="previewbutton" class="button" value="' . __( "Preview", "voucherpress" ) . '" />
		<input type="submit" name="save" id="savebutton" class="button-primary" value="' . __( "Save", "voucherpress" ) . '" /><br />
		<label><input type="checkbox" name="delete" id="delete" value="1" /> ' . __( "Delete?", "voucherpress" ) . '</label>
		<input type="hidden" name="template" id="template" value="' . $voucher->template . '" />';
		wp_nonce_field( "voucherpress_edit" );
	echo '</p>
	
	<ul class="vptabs">
		<li><a href="#designer" class="vptablink active button button-primary">' . __( "Designer", "voucherpress" ) . '</a></li>
		<li><a href="#settings" class="vptablink button">' . __( "Settings", "voucherpress" ) . '</a></li>
        <li><a href="#registration" class="vptablink button">' . __( "Registration", "voucherpress" ) . '</a></li>
		<li><a href="#codes" class="vptablink button">' . __( "Voucher codes", "voucherpress" ) . '</a></li>
		<li><a href="#shortcodes" class="vptablink button">' . __( "Shortcodes", "voucherpress" ) . '</a></li>
		<li><a href="#stats" class="vptablink button">' . __( "Statistics", "voucherpress" ) . '</a></li>
	</ul>
	
	<div id="designer" class="vptab">
		
	<h3>' . __( "Voucher designer", "voucherpress" ) . '</h3>
	
	<h2><label for="name">' . __( "Voucher name", "voucherpress" ) . '</label><input type="text" name="name" id="name" value="' . stripslashes( $voucher->name ) . '" /></h2>
	
	<!--textarea id="voucherpreview" name="html" style="background-image:url(' . plugins_url() . '/voucherpress/templates/' . $voucher->template . '_preview.jpg)">' . $voucher->html . '</textarea-->
	
	';

wp_editor( $voucher->html, "voucherpreview", 
	array(
		'textarea_name' => 'html', 
		'media_buttons' => true, 
		'editor_css' => '<style>#voucherpreview, #voucherpreview_ifr { background: #fff url(' . plugin_url() . '/voucherpress/templates/' . $template->size . '/' . $voucher->template . '_preview.jpg) no-repeat top left; }</style>',
		'wpautop' => false,
		'tinymce' => array(
				'content_css' => plugins_url() . '/voucherpress/css/editor_content.css',
				'force_p_newlines' => true,
				'verify_html' => false,
				'width' => $template->width . 'px',
				'height' => ($template->height + 44) . 'px', // this accounts for the toolbar height
				'mode' => 'exact',
				'elements' => 'voucherpreview',
				'theme' => 'advanced',
				'theme_advanced_path' => false,
				'theme_advanced_resizing' => false,
				'theme_advanced_buttons1' => 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,formatselect,fontselect,|,forecolor,backcolor,|,undo,redo',
				'theme_advanced_buttons2' => '',
				'theme_advanced_buttons3' => '',
				'theme_advanced_blockformats' => 'p,h1,h2,h3,h4,h5,h6',
				'theme_advanced_fonts' => $fontstring,
				'oninit' => 'vp_tinymce_initialised'
			)
		) 
	);

echo '
    <p>' . __( "The PDF version of this voucher may look slightly different to this preview. Please test the voucher to check it displays correctly.", "voucherpress" ) . '</p>
	<p><a href="#" id="toggledetails">' . __( "Special words you can use in your voucher", "voucherpress" ) . '</a></p>
<div id="detailsbox">
    <p>' . __( "You can use the following special words to display unique information on your voucher:", "voucherpress" ) . '</p>
    <ul>
        <li><input type="text" value="[code]" /> ' . __( "The code for this voucher, if you use codes", "voucherpress" ) . '</li>
		<li><input type="text" value="[date]" /> ' . __( 'The date the voucher is downloaded', 'voucherpress' ) . '</li>
        <li><input type="text" value="[name]" /> ' . __( "The name of the person who registered for this voucher, if you enable registration", "voucherpress" ) . '</li>
        <li><input type="text" value="[email]" /> ' . __( "The email address of the person who registered for this voucher, if you enable registration", "voucherpress" ) . '</li>
        <li><input type="text" value="[expiry]" /> ' . __( "The expiry date for this voucher, if one is set", "voucherpress" ) . '</li>
    </ul>
</div>
	<p><a href="#" id="toggledescription">' . __( "Voucher description (optional): enter a longer description here which will go in the email sent to a user registering for this voucher.", "voucherpress" ) . '</a></p>
    <div id="descriptionbox">
    <p><textarea name="description" id="description" rows="3" cols="100">' . $voucher->description . '</textarea></p>
    </div>
	
	';
	$templates = $templateManager->GetAllTemplates( $template->size );
	if ( $templates && is_array( $templates ) && count( $templates ) > 0 )
	{
		echo '
		<h3>' . __( "Template", "voucherpress" ) . '</h3>

        <p>' . __( "The template is the background for your voucher. You can use the templates below, or add your own (click 'Templates' in the menu). Click a template below to load it.", "voucherpress" ) . '</p>

        <p><label for="size">' . __( "Choose a size for this voucher", "voucherpress" ) . '</label>
	    ' . $templateManager->GetTemplateSizeList( $template->size ) . '
	    <input type="hidden" id="templateroot" value="' . WP_PLUGIN_URL . '/voucherpress/templates/" /></p>
		<div id="voucherthumbs">
		';
		foreach( $templates as $template )
		{
			echo '
			<span><img src="' . plugins_url() . '/voucherpress/templates/' . $template->size . '/' . $template->id . '_thumb.jpg" id="template_' . $template->id . '" alt="' . $template->name . '" /></span>
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
	</div>
	
	<div id="settings" class="hide vptab">
	
	<h3>' . __( "Settings", "voucherpress" ) . '</h3>
    ';
	
	if ( $voucher->limit == "0" ) {
		$voucher->limit = "";
	}
	echo '
	<p><label for="limit">' . __( "Number of vouchers available", "voucherpress" ) . '</label>
	<input type="text" name="limit" id="limit" class="num" value="' . $voucher->limit . '" /> <span>' . __( "Set the number of times this voucher can be downloaded (leave blank for unlimited)", "voucherpress" ) . '</span></p>
	
	<p><label for="startyear">' . __( "Date voucher starts being available", "voucherpress" ) . '</label>
	' . __( "Year:", "voucherpress" ) . ' <input type="text" name="startyear" id="startyear" class="num" value="';
	if ( $voucher->startdate != "" && $voucher->startdate > 0 ) {
		echo date( "Y", $voucher->startdate  );
	}
	echo '" />
	' . __( "Month:", "voucherpress" ) . ' <input type="text" name="startmonth" id="startmonth" class="num" value="';
	if ( $voucher->startdate != "" && $voucher->startdate > 0 ) {
		echo date( "n", $voucher->startdate  );
	}
	echo '" />
	' . __( "Day:", "voucherpress" ) . ' <input type="text" name="startday" id="startday" class="num" value="';
	if ( $voucher->startdate != "" && $voucher->startdate > 0 ) {
		echo date( "j", $voucher->startdate  );
	}
	echo '" /> 
	<span>' . __( "Enter the date on which this voucher will become available (leave blank if this voucher is available immediately)", "voucherpress" ) . '</span></p>
	
	<p><label for="expiry">' . __( "Date voucher expires", "voucherpress" ) . '</label>
	' . __( "Year:", "voucherpress" ) . ' <input type="text" name="expiryyear" id="expiryyear" class="num" value="';
	if ( $voucher->expiry != "" && $voucher->expiry > 0 ) {
		echo date( "Y", $voucher->expiry  );
	}
	echo '" />
	' . __( "Month:", "voucherpress" ) . ' <input type="text" name="expirymonth" id="expirymonth" class="num" value="';
	if ( $voucher->expiry != "" && $voucher->expiry > 0 ) {
		echo date( "n", $voucher->expiry  );
	}
	echo '" />
	' . __( "Day:", "voucherpress" ) . ' <input type="text" name="expiryday" id="expiryday" class="num" value="';
	if ( $voucher->expiry != "" && $voucher->expiry > 0 ) {
		echo date( "j", $voucher->expiry  );
	}
	echo '" /> 
	<span>' . __( "Enter the date on which this voucher will expire (leave blank for never)", "voucherpress" ) . '</span></p>
	
	<p><label for="expirydays">' . __( "Expiry (days after start)", "voucherpress" ) . '</label>
    <input type="text" class="num" name="expirydays" id="expirydays" value="" />
    <span>Enter the number of days the voucher will stay live from the start date</span></p>
	
	<p><label for="expirydownload">' . __( "Expiry (days after download)", "voucherpress" ) . '</label>
    <input type="text" class="num" name="expirydownload" id="expirydownload" value="';
    if ( $voucher->settings["days_after_download"] != "" && $voucher->settings["days_after_download"] != "0" ) {
        echo $voucher->settings["days_after_download"];
    }
    echo '" />
    <span>Enter the number of days the voucher will stay live from the date the user downloads it</span></p>
    
    <p><label for="live">' . __( "Voucher available", "voucherpress" ) . '</label>
	<input type="checkbox" name="live" id="live" value="1"';
	if ( $voucher->live == "1" ) {
		echo ' checked="checked"';
	}
	echo '/> <span>' . __( "Tick this box to allow this voucher to be downloaded", "voucherpress" ) . '</span></p>
    <p><strong>' . __( "This box MUST be ticked for this voucher to be available on your site.", "voucherpress" ) . '</strong></p>
	
	</div>

    <div id="registration" class="hide vptab">
	
	<h3>' . __( "Registration", "voucherpress" ) . '</h3>
	
	<p><label for="requireemail">' . __( "Require email address", "voucherpress" ) . '</label>
	<input type="checkbox" name="requireemail" id="requireemail" value="1"';
	if ( $voucher->require_email == "1" ) {
		echo ' checked="checked"';
	}
	echo '/> <span>' . __( "Tick this box to require a valid email address to be given before this voucher can be downloaded", "voucherpress" ) . '</span></p>

    <div id="registrationfields">

    <p>' . __( "If you want to collect other information from people who download this voucher, enter the fields below. Deleting or changing the name of a field that has already collected information will lose all information for that field.", "voucherpress" ) . '</p>

    <table class="widefat">
    <thead>
        <tr>
            <th>' . __( "Field name", "voucherpress" ) . '</th>
            <th>' . __( "Help text", "voucherpress" ) . '</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    ';

    if ( $voucher->settings && count( $voucher->settings ) > 0 ) {
        $fields = $voucher->settings["registration_fields"];
        if ( is_array( $fields ) ) {
            foreach($fields as $field) {
                echo '
                <tr>
                    <td><input type="text" name="fieldname[]" value="' . $field["name"] . '" /></td>
                    <td><input type="text" name="fieldhelptext[]" value="' . $field["helptext"] . '" /></td>
                    <td><button type="button" class="button delete deleteregistrationfield">' . __( "Delete" ) . '</td>
                </tr>
                ';
            }
        }
    }

    echo '
        <tr>
            <td><input type="text" name="fieldname[]" /></td>
            <td><input type="text" name="fieldhelptext[]" /></td>
            <td><button type="button" class="button add addregistrationfield">' . __( "Add" ) . '</td>
        </tr>
    </tbody>
    </table>

    </div>

    </div>
	
	<div id="codes" class="hide vptab">
	
	<h3>' . __( "Voucher codes", "voucherpress" ) . '</h3>

	<p>' . __("The code prefix and suffix will only be used on random and sequential codes.", "voucherpress") . '</p>

	<p id="codeprefixline"><label for="codeprefix">' . __( "Code prefix", "voucherpress" ) . '</label>
<input type="text" name="codeprefix" id="codeprefix" value="' . $voucher->codeprefix . '" /> <span>' . __( "Text to show before the sequential code (eg <strong>ABC</strong>123XYZ)", "voucherpress" ) . '</span></p>

	<p id="codesuffixline"><label for="codesuffix">' . __( "Code suffix", "voucherpress" ) . '</label>
<input type="text" name="codesuffix" id="codesuffix" value="' . $voucher->codesuffix . '" /> <span>' . __( "Text to show after the sequential code (eg ABC123<strong>XYZ</strong>)", "voucherpress" ) . '</span></p>

	<p><label for="randomcodes">' . __( "Use random codes", "voucherpress" ) . '</label>
	<input type="radio" name="codestype" id="randomcodes" value="random"';
	if ( $voucher->codestype == "random" || $voucher->codestype == "" ) {
		echo ' checked="checked"';
	}
	echo ' /> <span>' . __( "Tick this box to use a random code on each voucher", "voucherpress" ) . '</span></p>
	
	<p class="hider" id="codelengthline"><label for="codelength">' . __( "Random code length", "voucherpress" ) . '</label>
	<select name="codelength" id="codelength">
	<option value="6"';
	if ( $voucher->codelength == "6" ) {
		echo ' selected="selected"';
	}
	echo '>6</option>
	<option value="7"';
	if ( $voucher->codelength == "7" ) {
		echo ' selected="selected"';
	}
	echo '>7</option>
	<option value="8"';
	if ( $voucher->codelength == "8" ) {
		echo ' selected="selected"';
	}
	echo '>8</option>
	<option value="9"';
	if ( $voucher->codelength == "9" ) {
		echo ' selected="selected"';
	}
	echo '>9</option>
	<option value="10"';
	if ( $voucher->codelength == "10" ) {
		echo ' selected="selected"';
	}
	echo '>10</option>
	</select> <span>' . __( "How long would you like the random code to be?", "voucherpress" ) . '</span></p>
	
	<p><label for="sequentialcodes">' . __( "Use sequential codes", "voucherpress" ) . '</label>
	<input type="radio" name="codestype" id="sequentialcodes" value="sequential"';
	if ( $voucher->codestype == "sequential" ) {
		echo ' checked="checked"';
	}
	echo ' /> <span>' . __( "Tick this box to use sequential codes (1, 2, 3 etc) on each voucher", "voucherpress" ) . '</span></p>
	
	<p><label for="customcodes">' . __( "Use custom codes", "voucherpress" ) . '</label>
	<input type="radio" name="codestype" id="customcodes" value="custom"';
	if ( $voucher->codestype == "custom" ) {
		echo ' checked="checked"';
	}
	echo ' /> <span>' . __( "Tick this box to use your own codes on each voucher. You must enter all the codes you want to use below:", "voucherpress" ) . '</span></p>
	
	<p class="hider" id="customcodelistline"><label for="customcodelist">' . __( "Custom codes (one per line)", "voucherpress" ) . '</label>
	<textarea name="customcodelist" id="customcodelist" rows="6" cols="100">';
	if ( $voucher->codestype == "custom" ) {
		echo $voucher->codes;
	}
	echo '</textarea></p>
	
	<p><label for="singlecode">' . __( "Use a single code", "voucherpress" ) . '</label>
	<input type="radio" name="codestype" id="singlecode" value="single"';
	if ( $voucher->codestype == "single" ) {
		echo ' checked="checked"';
	}
	echo ' /> <span>' . __( "Tick this box to use one code on all downloads of this voucher. Enter the code you want to use below:", "voucherpress" ) . '</span></p>
	
	<p class="hider" id="singlecodetextline"><label for="singlecodetext">' . __( "Single code", "voucherpress" ) . '</label>
	<input type="text" name="singlecodetext" id="singlecodetext" value="';
	if ( $voucher->codestype == "single" ) {
		echo $voucher->codes;
	}
	echo '" /></p>
	
	</div>
	
	<div class="vptab hider" id="shortcodes">

        <h3>' . __( "Shortcodes", "voucherpress" ) . '</h3>

        <p>' . __( "The following shortcodes can be used in your posts and pages to give links to this voucher. Just copy the shortcode from the boxes below.", "voucherpress" ) . '</p>
	
		<h3>' . __( "Basic shortcode (name only):", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot;]" /></h3>
		<p><a href="' . voucherpress_link( $voucher->guid ) . '">' . htmlspecialchars( stripslashes( $voucher->name ) ) . '</a></p>
		
		<h3>' . __( "Name and vouchers remaining:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . ' &quot; count=&quot;true&quot;]" /></h3>
		<p><a href="' . voucherpress_link( $voucher->guid ) . '">' . htmlspecialchars( stripslashes( $voucher->name ) ) . '</a> (5 vouchers left)</p>
        
        <h3>' . __( "Name and description:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot; description=&quot;true&quot;]" /></h3>
		<p><a href="' . voucherpress_link( $voucher->guid ) . '">' . htmlspecialchars( stripslashes( $voucher->name ) ) . '</a> ' . htmlspecialchars( stripslashes( $voucher->description ) ) . '</p>

        <h3>' . __( "Name, description and vouchers remaining:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot; description=&quot;true&quot; count=&quot;true&quot;]" /></h3>
		<p><a href="' . voucherpress_link( $voucher->guid ) . '">' . htmlspecialchars( stripslashes( $voucher->name ) ) . '</a> ' . htmlspecialchars( stripslashes( $voucher->description ) ) . ' (5 vouchers remaining)</p>

		';
		
		if ( $voucher->require_email == "1" )
		{
		echo '
		<h3>' . __( "Voucher registration form:", "voucherpress" ) . '</h3>
		<p><input type="text" value="[voucherform id=&quot;' . $voucher->id . '&quot;]" /></p>
		';
		}
		
		echo '
		
		<h3>' . __( "Thumbnail:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot; preview=&quot;true&quot;]" /></h3>
		<p><a href="' . voucherpress_link( $voucher->guid ) . '"><img src="' . plugins_url() . '/voucherpress/templates/' . $template->size . '/' . $voucher->template . '_thumb.jpg" alt="' . htmlspecialchars( stripslashes( $voucher->name ) ) . '" /></a></p>
        <p>' . __("It is not possible to display the text on a voucher thumbnail as the text is only put on the voucher at the point it is downloaded") . '</p>
		
        <h3>' . __( "Thumbnail with vouchers remaining:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot; preview=&quot;true&quot; count=&quot;true&quot;]" /></h3>
		<p><a href="' . voucherpress_link( $voucher->guid ) . '"><img src="' . plugins_url() . '/voucherpress/templates/' . $template->size . '/' . $voucher->template . '_thumb.jpg" alt="' . htmlspecialchars( stripslashes( $voucher->name ) ) . '" /></a> (5 vouchers remaining)</p>
        <p>' . __("It is not possible to display the text on a voucher thumbnail as the text is only put on the voucher at the point it is downloaded") . '</p>

		<h3>' . __( "Thumbnail with description:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot; preview=&quot;true&quot; description=&quot;true&quot;]" /></h3>
		<p><a href="' . voucherpress_link( $voucher->guid ) . '"><img src="' . plugins_url() . '/voucherpress/templates/' . $template->size . '/' . $voucher->template . '_thumb.jpg" alt="' . htmlspecialchars( stripslashes( $voucher->name ) ) . '" /></a><br />' . htmlspecialchars( stripslashes( $voucher->description ) ) . '</p>
        <p>' . __( "It is not possible to display the text on a voucher thumbnail as the text is only put on the voucher at the point it is downloaded", "voucherpress" ) . '</p>

        <h3>' . __( "Thumbnail with description and vouchers remaining:", "voucherpress" ) . ' <input type="text" value="[voucher id=&quot;' . $voucher->id . '&quot; preview=&quot;true&quot; description=&quot;true&quot; count=&quot;true&quot;]" /></h3>
		<p><a href="' . voucherpress_link( $voucher->guid ) . '"><img src="' . plugins_url() . '/voucherpress/templates/' . $template->size . '/' . $voucher->template . '_thumb.jpg" alt="' . htmlspecialchars( stripslashes( $voucher->name ) ) . '" /></a><br />' . htmlspecialchars( stripslashes( $voucher->description ) ) . ' (5 vouchers remaining)</p>
        <p>' . __( "It is not possible to display the text on a voucher thumbnail as the text is only put on the voucher at the point it is downloaded", "voucherpress" ) . '</p>
	
		<h3>' . __( "Link for this voucher:", "voucherpress" ) . ' <input type="text" value="' . voucherpress_link( $voucher->guid ) . '" /></h3>
	
	</div>
	
	<div id="stats" class="hide vptab">
	
	<h3>' . __( "Statistics", "voucherpress" ) . '</h3>
	
	';
	
	if ( $voucher->downloads > 0 ) {
	
		echo '<p>' . __( "Downloads of this voucher:", "voucherpress" ) . ': ' . $voucher->downloads . '</p>';
	
	} else {
	
		echo '<p>' . __( "There are no downloads for this voucher yet.", "voucherpress" ) . '</p>';
	
	}
	
	echo '
	
	</div>
	
	</form>
	
	<script type="text/javascript">
	jQuery(document).ready(vp_show_' . $voucher->codestype . ');
	</script>
	';
	
} else {

	if ( @$_GET["result"] == "4" ) {
		echo '
		<h2>' . __( "Voucher deleted", "voucherpress" ) . '</h2>
		<div id="message" class="updated fade">
			<p><strong>' . __( "The voucher has been deleted.", "voucherpress" ) . '</strong></p>
		</div>
		';
	} else {
		echo '
		<h2>' . __( "Voucher not found", "voucherpress" ) . '</h2>
		<p>' . __( "Sorry, that voucher was not found.", "voucherpress" ) . '</p>
		';
	}
}
?>