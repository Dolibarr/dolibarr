<?php
/**
 * Not all printers support the same subset of available Esc/POS commands. Profiles allow you to specify
 * which features are available on your printer, so that Escpos is less likely to send unsupported commands.
 */
abstract class AbstractCapabilityProfile {
	/**
	 * Sub-classes must be retrieved via getInstance(), so that validation
	 * can be attached to guarantee that dud profiles are not used on an Escpos object.
	 */
	protected final function __construct() {
		// This space intentionally left blank.
	}

	/**
	 * If getSupportedCodePages contains custom code pages, their character maps must be provided here.
	 */
	abstract function getCustomCodePages();

	/**
	 * Return a map of code page numbers to names for this printer. Names
	 * should match iconv code page names where possible (non-matching names will not be used).
	 */
	abstract function getSupportedCodePages();

	/**
	 * True to support barcode "function b", false to use only function A.
	 */
	abstract function getSupportsBarcodeB();

	/**
	 * True for bitImage support, false for no bitImage support.
	 */
	abstract function getSupportsBitImage();

	/**
	 * True for graphics support, false for no graphics support.
	 */
	abstract function getSupportsGraphics();

	/**
	 * True for 'STAR original' commands, false for standard ESC/POS only.
	 */
	abstract function getSupportsStarCommands();

	/**
	 * True if the printer renders its own QR codes, false to send an image.
	 */
	abstract function getSupportsQrCode();

	/**
	 * @return AbstractCapabilityProfile Instance of sub-class.
	 */
	public static final function getInstance() {
		static $profile = null;
		if ($profile === null) {
			$profile = new static();
		}
		return $profile;
	}
}