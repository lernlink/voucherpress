<?php
// manages fonts, for instance getting multiple fonts

class VoucherPress_FontManager {

	var $allFonts;

	// gets all fonts available in the VoucherPress system
	function GetAllFonts() {
		$this->GetStandardFonts();
		return $this->allFonts;
	}

	// gets the standard fonts available in the VoucherPress system
	function GetStandardFonts() {
	
		// include the font class
		voucherpress_include( 'classes/class-font.php' );
		
		$f = new VoucherPress_Font();
		$f->name = 'Serif';
		$f->filename = 'times';
		$this->allFonts[] = $f;
		unset( $f );
		
		$f = new VoucherPress_Font();
		$f->name = 'Sans-serif 1';
		$f->filename = 'helvetica';
		$this->allFonts[] = $f;
		unset( $f );
		
		$f = new VoucherPress_Font();
		$f->name = 'Sans-serif 2';
		$f->filename = 'dejavusans,arial,verdana,sans-serif';
		$this->allFonts[] = $f;
		unset( $f );

        $f = new VoucherPress_Font();
		$f->name = 'Sans-serif light';
		$f->filename = 'dejavusansextralight';
		$this->allFonts[] = $f;
		unset( $f );
		
		$f = new VoucherPress_Font();
		$f->name = 'Monotype';
		$f->filename = 'courier,monotype';
		$this->allFonts[] = $f;
		unset( $f );
	}
}
?>