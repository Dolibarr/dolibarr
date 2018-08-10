<?php
/**
 * This capability profile is designed for non-Epson printers sold online. Without knowing
 * their character encoding table, only CP437 output is assumed, and graphics() calls will
 * be disabled, as it usually prints junk on these models.
 */
class SimpleCapabilityProfile extends DefaultCapabilityProfile {
	function getSupportedCodePages() {
		/* Use only CP437 output */
		return array(0 => CodePage::CP437);
	}
	
	public function getSupportsGraphics() {
		/* Ask the driver to use bitImage wherever possible instead of graphics */
		return false;
	}
}
