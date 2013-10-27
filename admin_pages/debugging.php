<?php
// shows the templates admin page

//check install
voucherpress_include( 'setup/installer.php' );
voucherpress_check_install();

voucherpress_report_header();

echo '
<h2>' . __( 'Debugging', 'voucherpress' ) . '</h2>

<h3>' . __( 'Create test voucher', 'voucherpress' ) . '</h3>

<form action="admin.php?page=vouchers-debug&amp;test=voucher" method="post" target="_blank" id="voucherform">
<p>
	<label for="width">' . __( 'Width', 'voucherpress' ) . '</label>
	<input type="text" name="width" id="width" value="200" />
</p>
<p>
	<label for="height">' . __( 'Height', 'voucherpress' ) . '</label>
	<input type="text" name="height" id="height" value="150" />
</p>
<p>
	<label for="html">' . __( 'HTML content', 'voucherpress' ) . '</label>
	<textarea name="html" id="html" cols="100" rows="12"></textarea>
</p>
<p>
	<button class="button" type="submit">' . __( 'Create', 'voucherpress' ) . '</button>
</p>
</form>
';

voucherpress_report_footer();
?>