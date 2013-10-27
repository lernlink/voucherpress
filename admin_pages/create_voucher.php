<?php
// shows the create voucher page

//check install
voucherpress_include( 'setup/installer.php' );
voucherpress_check_install();

// include the required managers
voucherpress_include( 'managers/manager-fonts.php' );
$fontManager = new VoucherPress_FontManager();
voucherpress_include( 'managers/manager-templates.php' );
$templateManager = new VoucherPress_TemplateManager();

voucherpress_report_header();

$defaultTemplateSize = split('x', voucherpress_default_size());

// get the fonts available
$fonts = $fontManager->GetAllFonts();

// set the font string for use in TinyMCE
$fontstring = '';
foreach($fonts as $font) {
	$fontstring .= "{$font->name}={$font->filename};";
}
$fontstring = trim($fontstring, ';');

echo '
<h2>' . __( 'Create a voucher', 'voucherpress' ) . '</h2>
';

if ( '' != @$_GET['result'] ) {
	if ( '1' == @$_GET['result'] ) {
		echo '
		<div id="message" class="error">
			<p><strong>' . __( 'Sorry, your voucher could not be created. Please click back and try again.', 'voucherpress' ) . '</strong></p>
		</div>
		';
	}
}

echo '
<form action="admin.php?page=vouchers-create" method="post" id="voucherform">

<p class="alignright"><input type="button" name="preview" id="previewbutton" class="button" value="' . __( 'Preview', 'voucherpress' ) . '" />
<input type="submit" name="save" id="savebutton" class="button-primary" value="' . __( 'Save', 'voucherpress' ) . '" />
<input type="hidden" name="template" id="template" value="1" />';
wp_nonce_field( 'voucherpress_create' );
echo '</p>

<ul class="vptabs">
	<li><a href="#designer" class="vptablink active button button-primary">' . __( 'Designer', 'voucherpress' ) . '</a></li>
	<li><a href="#settings" class="vptablink button">' . __( 'Settings', 'voucherpress' ) . '</a></li>
	<li><a href="#codes" class="vptablink button">' . __( 'Voucher codes', 'voucherpress' ) . '</a></li>
</ul>

<div id="designer" class="vptab">

<h3>' . __( 'Voucher designer', 'voucherpress' ) . '</h3>

<h2><label for="name">' . __( 'Voucher name', 'voucherpress' ) . '</label> <input type="text" name="name" id="name" value="" /></h2>

';

wp_editor( '<h1>' . __( 'Voucher name', 'voucherpress' ) . '</h1><p>' . __( 'Type the voucher text here', 'voucherpress' ) . '</p><p>[' . __( 'code', 'voucherpress' ) . ']</p><h6>' . __( 'Type the voucher terms and conditions here', 'voucherpress' ) . '</h6>', 'voucherpreview',
	array(
		'textarea_name' => 'html',
		'media_buttons' => true,
		'editor_css' => '<style>#voucherpreview, #voucherpreview_ifr { background: #fff url(' . plugins_url() . '/voucherpress/templates/' . voucherpress_default_size() . '/default_preview.jpg) no-repeat top left; }</style>',
		'wpautop' => false,
		'tinymce' => array(
				'content_css' => plugins_url() . '/voucherpress/css/editor_content.css',
				'force_p_newlines' => true,
				'verify_html' => false,
				'width' => $defaultTemplateSize[0] . 'px',
				'height' => ($defaultTemplateSize[1] + 44) . 'px', // this accounts for the toolbar height
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
				'oninit' => 'vpTinymceInitialised'
			)
		)
	);

echo '
<p>' . __( 'The PDF version of this voucher may look slightly different to this preview. Please test the voucher to check it displays correctly.', 'voucherpress' ) . '</p>
<p><a href="#" id="toggledetails">' . __( 'Special words you can use in your voucher', 'voucherpress' ) . '</a></p>
<div id="detailsbox">
    <p>' . __( 'You can use the following special words to display unique information on your voucher:', 'voucherpress' ) . '</p>
    <ul>
        <li><input type="text" value="[code]" /> ' . __( 'The code for this voucher, if you use codes', 'voucherpress' ) . '</li>
		<li><input type="text" value="[date]" /> ' . __( 'The date the voucher is downloaded', 'voucherpress' ) . '</li>
        <li><input type="text" value="[name]" /> ' . __( 'The name of the person who registered for this voucher, if you enable registration', 'voucherpress' ) . '</li>
        <li><input type="text" value="[email]" /> ' . __( 'The email address of the person who registered for this voucher, if you enable registration', 'voucherpress' ) . '</li>
        <li><input type="text" value="[expiry]" /> ' . __( 'The expiry date for this voucher, if one is set', 'voucherpress' ) . '</li>
    </ul>
</div>
<p><a href="#" id="toggledescription">' . __( 'Voucher description (optional): enter a longer description here which will go in the email sent to a user registering for this voucher.', 'voucherpress' ) . '</a></p>
<div id="descriptionbox">
<p><textarea name="description" id="description" rows="3" cols="100"></textarea></p>
</div>

';
$templates = $templateManager->GetAllTemplates( voucherpress_default_size() );
if ( $templates && is_array( $templates ) && count( $templates ) > 0 )
{
	echo '
	<h3>' . __( 'Template', 'voucherpress' ) . '</h3>

    <p>' . __( "The template is the background for your voucher. You can use the templates below, or add your own (click 'Templates' in the menu). Click a template below to load it", 'voucherpress' ) . '</p>

    <p><label for="size">' . __( 'Choose a size for your new voucher', 'voucherpress' ) . '</label>
    ' . $templateManager->GetTemplateSizeList() . '
    <input type="hidden" id="templateroot" value="' . WP_PLUGIN_URL . '/voucherpress/templates/" /></p>

	<div id="voucherthumbs">
	';
	foreach( $templates as $template )
	{
		echo '
		<span><img src="' . plugins_url() . '/voucherpress/templates/' . voucherpress_default_size() . '/' . $template->id . '_thumb.jpg" id="template_' . $template->id . '" alt="' . $template->name . '" /></span>
		';
	}
	echo '
	</div>
	';
} else {
	echo '
	<p>' . __( 'Sorry, no templates found', 'voucherpress' ) . '</p>
	';
}

echo '
</div>

<div id="settings" class="hide vptab">
<h3>' . __( 'Settings', 'voucherpress' ) . '</h3>

<p><label for="requireemail">' . __( 'Require email address', 'voucherpress' ) . '</label>
<input type="checkbox" name="requireemail" id="requireemail" value="1" /> <span>' . __( 'Tick this box to require a valid email address to be given before this voucher can be downloaded', 'voucherpress' ) . '</span></p>

<p><label for="limit">' . __( 'Number of vouchers available', 'voucherpress' ) . '</label>
<input type="text" name="limit" id="limit" class="num" value="" /> <span>' . __( 'Set the number of times this voucher can be downloaded (leave blank or 0 for unlimited)', 'voucherpress' ) . '</span></p>

<p><label for="startyear">' . __( 'Date voucher starts being available', 'voucherpress' ) . '</label>
' . __( 'Year:', 'voucherpress' ) . ' <input type="text" name="startyear" id="startyear" class="num" value="" />
' . __( 'Month:', 'voucherpress' ) . ' <input type="text" name="startmonth" id="startmonth" class="num" value="" />
' . __( 'Day:', 'voucherpress' ) . ' <input type="text" name="startday" id="startday" class="num" value="" />
<span>' . __( 'Enter the date on which this voucher will become available (leave blank if this voucher is immediately available)', 'voucherpress' ) . '</span></p>

<p><label for="expiryyear">' . __( 'Date voucher expires', 'voucherpress' ) . '</label>
' . __( 'Year:', 'voucherpress' ) . ' <input type="text" name="expiryyear" id="expiryyear" class="num" value="" />
' . __( 'Month:', 'voucherpress' ) . ' <input type="text" name="expirymonth" id="expirymonth" class="num" value="" />
' . __( 'Day:', 'voucherpress' ) . ' <input type="text" name="expiryday" id="expiryday" class="num" value="" />
<span>' . __( 'Enter the date on which this voucher will expire (leave blank for never)', 'voucherpress' ) . '</span></p>

<p><label for="expirydays">' . __( 'Or enter the number of days the voucher will stay live', 'voucherpress' ) . '</label>
<input type="text" class="num" name="expirydays" id="expirydays" value="" /></p>

</div>

<div id="codes" class="hide vptab">
<h3>' . __( 'Voucher codes', 'voucherpress' ) . '</h3>

<p>' . __( 'The code prefix and suffix will only be used on random and sequential codes.', 'voucherpress' ) . '</p>

<p id="codeprefixline"><label for="codeprefix">' . __( 'Code prefix', 'voucherpress' ) . '</label>
<input type="text" name="codeprefix" id="codeprefix" /> <span>' . __( 'Text to show before the code (eg <strong>ABC</strong>123XYZ)', 'voucherpress' ) . '</span></p>

<p id="codesuffixline"><label for="codesuffix">' . __( 'Code suffix', 'voucherpress' ) . '</label>
<input type="text" name="codesuffix" id="codesuffix" /> <span>' . __( 'Text to show after the code (eg ABC123<strong>XYZ</strong>)', 'voucherpress' ) . '</span></p>

<p><label for="randomcodes">' . __( 'Use random codes', 'voucherpress' ) . '</label>
<input type="radio" name="codestype" id="randomcodes" value="random" checked="checked" /> <span>' . __( 'Tick this box to use a random character code on each voucher', 'voucherpress' ) . '</span></p>

<p class="hider" id="codelengthline"><label for="codelength">' . __( 'Random code length', 'voucherpress' ) . '</label>
<select name="codelength" id="codelength">
<option value="6">6</option>
<option value="7">7</option>
<option value="8">8</option>
<option value="9">9</option>
<option value="10">10</option>
</select> <span>' . __( 'How long would you like the random code to be?', 'voucherpress' ) . '</span></p>

<p><label for="sequentialcodes">' . __( 'Use sequential codes', 'voucherpress' ) . '</label>
<input type="radio" name="codestype" id="sequentialcodes" value="sequential" /> <span>' . __( 'Tick this box to use sequential codes (1, 2, 3 etc) on each voucher', 'voucherpress' ) . '</span></p>

<p><label for="customcodes">' . __( 'Use custom codes', 'voucherpress' ) . '</label>
<input type="radio" name="codestype" id="customcodes" value="custom" /> <span>' . __( 'Tick this box to use your own codes on each download of this voucher. You must enter all the codes you want to use below:', 'voucherpress' ) . '</span></p>

<p class="hider" id="customcodelistline"><label for="customcodelist">' . __( 'Custom codes (one per line)', 'voucherpress' ) . '</label>
<textarea name="customcodelist" id="customcodelist" rows="6" cols="100"></textarea></p>

<p><label for="singlecode">' . __( 'Use a single code', 'voucherpress' ) . '</label>
<input type="radio" name="codestype" id="singlecode" value="single" /> <span>' . __( 'Tick this box to use one code on all downloads of this voucher. Enter the code you want to use below:', 'voucherpress' ) . '</span></p>

<p class="hider" id="singlecodetextline"><label for="singlecodetext">' . __( 'Single code', 'voucherpress' ) . '</label>
<input type="text" name="singlecodetext" id="singlecodetext" /></p>

</div>

</form>

<script type="text/javascript">
jQuery(document).ready(vpShowRandom);
</script>
';

voucherpress_report_footer();
?>