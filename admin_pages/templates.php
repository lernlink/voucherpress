<?php
// shows the templates admin page

//check install
voucherpress_include( 'setup/installer.php' );
voucherpress_check_install();

// load the required classes
voucherpress_include( 'classes/class-template.php' );

// include the required managers
voucherpress_include( 'managers/manager-templates.php' );
$templateManager = new VoucherPress_TemplateManager();

$size = voucherpress_default_size();
if ( '' != @$_GET['size'] )
	$size = $_GET['size'];

voucherpress_report_header();

echo '
<h2>' . __( 'Voucher templates', 'voucherpress' ) . '</h2>

<form action="admin.php" method="get">
<p><label for="size">' . __( 'Choose the template size', 'voucherpress' ) . '</label>' . $templateManager->GetTemplateSizeList() . '
<button class="button" type="submit">' . __( 'Load', 'voucherpress' ) . '</button>
<input type="hidden" name="page" value="vouchers-templates" /></p>
</form>
';

// get templates
$templates = $templateManager->GetTemplates( $size );

// if submitting a form
if ( $_POST && is_array( $_POST ) && count( $_POST) > 0 )
{
	// if updating templates
	if ( wp_verify_nonce(@$_POST['_wpnonce'], 'voucherpress_edit_template') && 'update' == @$_POST['action'] )
	{
		// loop templates
		foreach( $templates as $template )
		{
			// edit this template
			$live = 1;
			if ( '1' == @$_POST['delete' . $template->id] ) $live = 0;
			$template->name = @$_POST['name' . $template->id];
			$template->live = $live;
			$template->Save();
		}

		// get the new templates
		$templates = $templateManager->GetTemplates();

		echo '
		<div id="message" class="updated fade">
			<p><strong>' . __( 'Templates updated', 'voucherpress' ) . '</strong></p>
		</div>
		';
	}
	// if adding a template
	if ( 'add' == @$_POST['action'] ) {

		if ( wp_verify_nonce( @$_POST['_wpnonce'], 'voucherpress_add_template')
				&& @$_FILES
				&& is_array( $_FILES )
				&& count( $_FILES ) > 0
				&& '' != $_FILES['file']['name']
				&& absint( $_FILES['file']['size'] ) > 0 ) {

			// check the GD functions exist
			if ( function_exists( 'imagecreatetruecolor' )
					&& function_exists( 'getimagesize' )
					&& function_exists( 'imagejpeg' ) ) {

				$name = $_POST['name'];
				if ( '' == $name )
					$name = 'New template ' . date( 'F j, Y, g:i a' );

				// try to save the template name
				$template = new VoucherPress_Template();
				$template->name = $name;
				$template->live = 1;
				$template->Save();
				$id = $template->id;

				// if the id can be fetched
				if ( $id )
				{

                    // upload the file, this also sets the size of the voucher
					$uploaded = $template->Upload( $_FILES['file'] );

                    // save again so the size is saved
                    $template->Save();

					if ( $uploaded )
					{

						echo '
						<div id="message" class="updated fade">
							<p><strong>' . __( 'Your template has been uploaded.', 'voucherpress' ) . '</strong></p>
						</div>
						';

						// get templates
						$templates = voucherpress_get_templates();

					} else {

						echo '
						<div id="message" class="error">
							<p><strong>' . __( 'Sorry, the template file you uploaded was not in the correct format (JPEG), or was not the correct size. Please upload a correct template file.', 'voucherpress' ) . '</strong></p>
						</div>
						';

					}

				} else {

					echo '
					<div id="message" class="error">
						<p><strong>' . __( 'Sorry, your template could not be saved. Please try again.', 'voucherpress' ) . '</strong></p>
					</div>
					';

				}

			} else {
				echo '
				<div id="message" class="error">
					<p><strong>' . __( 'Sorry, your host does not support GD image functions, so you cannot add your own templates.', 'voucherpress' ) . '</strong></p>
				</div>
				';
			}
		} else {
			echo '
			<div id="message" class="error">
				<p><strong>' . __( 'Please attach a template file', 'voucherpress' ) . '</strong></p>
			</div>
			';
		}
	}
}

if ( function_exists( 'imagecreatetruecolor' )
		&& function_exists( 'getimagesize' )
		&& function_exists( 'imagejpeg' ) ) {
echo '
<h3>' . __( 'Add a template', 'voucherpress' ) . '</h3>

<form action="admin.php?page=vouchers-templates" method="post" enctype="multipart/form-data" id="templateform">

<p>' . __( sprintf( 'To create your own templates at this size use <a href="%s">this empty template</a>.', plugins_url() . "/voucherpress/images/" . $size . ".jpg" ), 'voucherpress' ) . '</p>

<p><label for="file">' . __( 'Template file', 'voucherpress' ) . '</label>
<input type="file" name="file" id="file" /></p>

<p><label for="name">' . __( 'Template name', 'voucherpress' ) . '</label>
<input type="text" name="name" id="name" /></p>

<p><input type="submit" class="button-primary" value="' . __( 'Add template', 'voucherpress' ) . '" />
<input type="hidden" name="action" value="add" />';
wp_nonce_field( 'voucherpress_add_template' );
echo '</p>

</form>
';
} else {
	echo '
	<p>' . __( 'Sorry, your host does not support GD image functions, so you cannot add your own templates.', 'voucherpress' )  . '</p>
	';
}

if ( $templates && is_array( $templates ) && count( $templates ) > 0 ) {
	echo '
	<form id="templatestable" method="post" action="">
	';
	voucherpress_table_header( array( 'Preview', 'Name', 'Delete' ) );
	foreach( $templates as $template )
	{
		echo '
		<tr>
			<td><a href="' . plugins_url() . '/voucherpress/templates/' . $template->size . '/' . $template->id . '_preview.jpg" class="templatepreview"><img src="' . plugins_url() . '/voucherpress/templates/' . $template->size . '/' . $template->id . '_thumb.jpg" alt="' . $template->name . '" /></a></td>
			';
			// if this is not a multisite-wide template
			if ( '0' != $template->blog_id
					|| ( !defined( 'VHOST' )
						&& ( !defined( 'MULTISITE' ) || '' == MULTISITE || MULTISITE == false ) ) ) {
				echo "
				<td><input type='text' name='name{$template->id}' value='{$template->name}' /></td>
				<td><input class='checkbox' type='checkbox' value='1' name='delete{$template->id}' /></td>
				";
			} else {
				echo '
				<td colspan="2">' . __( 'This template cannot be edited', 'voucherpress' ) . '</td>
				';
			}
		echo '
		</tr>
		';
	}
	voucherpress_table_footer();
	echo '
	<p><input type="submit" class="button-primary" value="' . __( 'Save templates', 'voucherpress' ) . '" />
	<input type="hidden" name="action" value="update" />';
	wp_nonce_field( 'voucherpress_edit_template' );
	echo '</p>
	</form>
	';
} else {
	echo '
	<p>' . __( 'Sorry, no templates found', 'voucherpress' ) . '</p>
	';
}

voucherpress_report_footer();
?>