jQuery( document ).ready( function() {
    vpSetPreviewFont();
    jQuery( '#size' ).bind( 'change', vpSetTemplateSize );
    jQuery( '#voucherthumbs img' ).live( 'click', vpSetPreview );
    jQuery( '.checkbox' ).bind( 'click', vpSetTemplateDeleted );
    jQuery( '#font' ).bind( 'change', vpSetPreviewFont );
    jQuery( '#name' ).bind( 'keyup', vpLimitText );
    jQuery( '#text' ).bind( 'keyup', vpLimitText );
    jQuery( '#terms' ).bind( 'keyup', vpLimitText );
    jQuery( '#previewbutton' ).bind( 'click', vpPreviewVoucher );
    jQuery( '#savebutton' ).bind( 'click', vpSaveVoucher );
    jQuery( 'a.templatepreview' ).bind( 'click', vpNewWindow );
    vpHideFormOptions();
    jQuery( '#randomcodes' ).bind( 'click', vpCheckRandom );
    jQuery( '#sequentialcodes' ).bind( 'click', vpCheckSequential );
    jQuery( '#customcodes' ).bind( 'click', vpCheckCustom );
    jQuery( '#singlecode' ).bind( 'click', vpCheckSingle );
    jQuery( '#showshortcodes' ).bind( 'click', vpToggleShortcodes );
    jQuery( '#delete' ).bind( 'change', vpToggleDeletion );
    jQuery( '.vptabs a' ).click( vpTab );
    jQuery( '#toggledetails' ).click( vpToggleDetails );
    jQuery( '#toggledescription' ).click( vpToggleDescription );
    jQuery( '#detailsbox,#descriptionbox,#voucherpreview-html,#voucherpreview-tmce' ).hide();
    jQuery( '.addregistrationfield' ).live( 'click', vpAddRegistrationField );
    jQuery( '.deleteregistrationfield' ).live( 'click', vpDeleteRegistrationField );
    jQuery( '#requireemail' ).live( 'click', vpToggleRegistrationFields );
    vpToggleRegistrationFields();
} );
function vpToggleRegistrationFields() {
    if ( jQuery( '#requireemail' ).is( ':checked' ) ){
        jQuery( '#registrationfields' ).show();
    } else {
        jQuery( '#registrationfields' ).hide();
    }
}
function vpAddRegistrationField() {
    var btn = jQuery( this ),
        row = btn.parents( 'tr' ),
        newrow = row.clone(),
        tbody = btn.parents( 'tbody' );
    jQuery( 'input', newrow ).val( '' );
    btn.removeClass( 'addregistrationfield' ).addClass( 'deleteregistrationfield' ).html( 'Delete' );
    tbody.append(newrow);
}
function vpDeleteRegistrationField() {
    var row = jQuery( this ).parents( 'tr' );
    row.remove();
}
function vpToggleDetails() {
    jQuery( '#detailsbox' ).toggle();
    return false;
}
function vpToggleDescription() {
    jQuery( '#descriptionbox' ).toggle();
    return false;
}
function vpTab( e ) {
	var a = jQuery( this );
	jQuery( '.vptab' ).hide();
	jQuery( a.attr( 'href' ) ).show();
	jQuery( '.vptablink' ).removeClass( 'button-primary' ).removeClass( 'active' );
	a.addClass( 'button-primary' ).addClass( 'active' );
	return false;
}
function vpToggleDeletion( e ) {
	if( this.checked ) {
		jQuery( '#previewbutton' ).hide();
		jQuery( '#savebutton' ).val( 'Delete voucher' );
	} else {
		jQuery( '#previewbutton' ).show();
		jQuery( '#savebutton' ).val( 'Save' );
	}
}
function vpToggleShortcodes( e ) {
	jQuery( '#shortcodes' ).toggle();
	e.preventDefault();
	return false;
}
function vpHideFormOptions() {
	jQuery( '.hider' ).hide();
}
function vpCheckRandom( e ) {
	vpHideFormOptions();
	if ( this.checked ) {
		vpShowRandom();
	}
}
function vpShowRandom() {
	jQuery( '#codelengthline' ).show();
	jQuery( '#codeprefixline' ).show();
	jQuery( '#codesuffixline' ).show();
}
function vpCheckSequential( e ) {
	vpHideFormOptions();
	if ( this.checked ) {
		vpShowSequential();
	}
}
function vpShowSequential() {
	jQuery( '#codeprefixline' ).show();
	jQuery( '#codesuffixline' ).show();
}
function vpCheckCustom( e ) {
	vpHideFormOptions();
	if ( this.checked ) {
		vpShowCustom();
	}
}
function vpShowCustom() {
	jQuery( '#customcodelistline' ).show();
	jQuery( '#codeprefixline' ).hide();
	jQuery( '#codesuffixline' ).hide();
}
function vpCheckSingle( e ) {
	vpHideFormOptions();
	if ( this.checked ) {
		vpShowSingle();
	}
}
function vpShowSingle() {
	jQuery( '#singlecodetextline' ).show();
	jQuery( '#codeprefixline' ).hide();
	jQuery( '#codesuffixline' ).hide();
}
function vpNewWindow( e ) {
	jQuery( this ).attr( 'target', '_blank' );
}
function vpPreviewVoucher( e ) {
	var form = jQuery( '#voucherform' ),
		action = form.attr( 'action' );
	form.attr( 'action', action + '&preview=voucher' );
	form.attr( 'target', '_blank' );
	form.submit();
	form.attr( 'action', action );
}
function vpSaveVoucher( e ) {
	var form = jQuery( '#voucherform' );
	form.attr( 'action', form.attr( 'action' ).replace( '&preview=voucher', '' ) );
	form.attr( 'target', '_self' );
	form.submit();
}
function vpSetPreview( e ) {
	var id = this.id.replace( 'template_', '' ),
		img = jQuery( this ),
		src = img.attr( 'src' ),
		root = src.substring( 0, src.lastIndexOf( '/' ) ),
		preview = '#fff url(' + root + '/' + id + '_preview.jpg) no-repeat top left';
	jQuery( '#voucherpreview' ).css( 'background', preview );
	jQuery( '#voucherpreview_ifr' ).css( 'background', preview );
	jQuery( '#template' ).val( id );
}
function vpGetTemplateSize() {
	return jQuery( '#size' ).val();
}
function vpSetPreviewSize( size ) {
	var size = size.split( 'x' ),
		preview = jQuery( '#voucherpreview' ),
		table = jQuery( '#voucherpreview_tbl'),
		iframe = jQuery( '#voucherpreview_ifr' );
	preview.width( size[0] );
	preview.height( size[1] );
	table.width( size[0] );
	table.height( parseInt( size[1] ) + 44 );
	iframe.width( size[0] );
	iframe.height( size[1] );
}
function vpSetTemplateSize() {
	var preview = jQuery( '#voucherpreview' );
	if ( preview.length ) {
		var size = vpGetTemplateSize(),
			root = jQuery( '#templateroot' ).val() + size,
			url = 'url(' + root + '/default_preview.jpg)';
		vpSetPreviewSize( size );
		preview.css( 'background', url + ' no-repeat top left' );
		vpLoadThumbs( size );
	}
}
function vpTinymceInitialised() {
	var size = vpGetTemplateSize();
	vpSetPreviewSize( size );
}
function vpLoadThumbs( size ) {
	if ( size ) {
		jQuery.post(
			ajaxurl, { action : 'voucherpress_loadthumbs', size : size },
			function( response ) {
				jQuery( '#voucherthumbs' ).html( response );
			}
		);
	}
}
function vpSetTemplateDeleted( e ) {
	var td = jQuery( this ).parent().get( 0 );
	var tr = jQuery( td ).parent().get( 0 );
	jQuery( tr ).toggleClass( 'deleted' );
}
function vpSetPreviewFont( e ) {
	var font = jQuery( '#font :selected' ).val();
	jQuery( '#voucherpreview h2 textarea' ).attr( 'class', font );
	jQuery( '#voucherpreview p textarea' ).attr( 'class', font );
	jQuery( '#voucherpreview p' ).attr( 'class', font );
}
function vpLimitText( e ) {
	var limit = 30;
	var el = jQuery( this );
	if ( 'text' === el.attr( 'id' ) ) limit = 200;
	if ( 'terms' === el.attr( 'id' ) ) limit = 300;
	var length = el.val().length;
	if ( parseFloat( length ) >= parseFloat( limit ) ) {
		// if this is a character key, stop it being entered
		var key = vpKeycode( e ) || e.code;
		if ( 8 !== key && 46 !== key && 37 !== key && 39 !== key ) {
			el.val( el.val().substr( 0, limit) );
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	}
}
// return the keycode for this event
function vpKeycode( e ) {
	if ( window.event ) {
		return window.event.keyCode;
	} else if ( e ) {
		return e.which;
	} else {
		return false;
	}
}