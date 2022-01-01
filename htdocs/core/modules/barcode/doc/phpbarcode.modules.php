<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/barcode/doc/phpbarcode.modules.php
 *	\ingroup    barcode
 *	\brief      File with class to generate barcode images using php internal lib barcode generator
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/modules_barcode.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/barcode.lib.php'; // This is to include def like $genbarcode_loc and $font_loc


/**
 *	Class to generate barcode images using php barcode generator
 */
class modPhpbarcode extends ModeleBarCode
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 * 	Return if a module can be used or not
	 *
	 *  @return		boolean     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}


	/**
	 * 	Return description
	 *
	 * 	@return     string      Texte descripif
	 */
	public function info()
	{
		global $langs;

		$key = 'BarcodeInternalEngine';
		$trans = $langs->trans('BarcodeInternalEngine');

		return ($trans != $key) ? $trans : 'Internal engine';
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *	@return     boolean     false if conflict, true if ok
	 */
	public function canBeActivated()
	{
		global $langs;

		return true;
	}


	/**
	 *	Return true if encoding is supported
	 *
	 *	@param	string	$encoding		Encoding norm
	 *	@return	int						>0 if supported, 0 if not
	 */
	public function encodingIsSupported($encoding)
	{
		global $genbarcode_loc;
		//print 'genbarcode_loc='.$genbarcode_loc.' encoding='.$encoding;exit;

		$supported = 0;
		if ($encoding == 'EAN13') {
			$supported = 1;
		}
		if ($encoding == 'ISBN') {
			$supported = 1;
		}
		if ($encoding == 'UPC') {
			$supported = 1;
		}
		// Formats that hangs on Windows (when genbarcode.exe for Windows is called, so they are not
		// activated on Windows)
		if (file_exists($genbarcode_loc) && empty($_SERVER["WINDIR"])) {
			if ($encoding == 'EAN8') {
				$supported = 1;
			}
			if ($encoding == 'C39') {
				$supported = 1;
			}
			if ($encoding == 'C128') {
				$supported = 1;
			}
		}
		return $supported;
	}

	/**
	 *	Return an image file on the fly (no need to write on disk)
	 *
	 *	@param	string   	$code			  Value to encode
	 *	@param  string	 	$encoding		  Mode of encoding
	 *	@param  string	 	$readable		  Code can be read (What is this ? is this used ?)
	 *	@param	integer		$scale			  Scale
	 *  @param  integer     $nooutputiferror  No output if error
	 *	@return	int							  <0 if KO, >0 if OK
	 */
	public function buildBarCode($code, $encoding, $readable = 'Y', $scale = 1, $nooutputiferror = 0)
	{
		global $_GET, $_SERVER;
		global $conf;
		global $genbarcode_loc, $bar_color, $bg_color, $text_color, $font_loc;

		if (!$this->encodingIsSupported($encoding)) {
			return -1;
		}

		if ($encoding == 'EAN8' || $encoding == 'EAN13') {
			$encoding = 'EAN';
		}
		if ($encoding == 'C39' || $encoding == 'C128') {
			$encoding = substr($encoding, 1);
		}

		$mode = 'png';

		$_GET["code"] = $code;
		$_GET["encoding"] = $encoding;
		$_GET["scale"] = $scale;
		$_GET["mode"] = $mode;

		dol_syslog(get_class($this)."::buildBarCode $code,$encoding,$scale,$mode");
		if ($code) {
			$result = barcode_print($code, $encoding, $scale, $mode);
		}

		if (!is_array($result)) {
			$this->error = $result;
			if (empty($nooutputiferror)) {
				print dol_escape_htmltag($this->error);
			}
			return -1;
		}

		return 1;
	}

	/**
	 *	Save an image file on disk (with no output)
	 *
	 *	@param	string   	$code			  Value to encode
	 *	@param	string   	$encoding		  Mode of encoding
	 *	@param  string	 	$readable		  Code can be read
	 *	@param	integer		$scale			  Scale
	 *  @param  integer     $nooutputiferror  No output if error
	 *	@return	int							  <0 if KO, >0 if OK
	 */
	public function writeBarCode($code, $encoding, $readable = 'Y', $scale = 1, $nooutputiferror = 0)
	{
		global $conf, $filebarcode;

		dol_mkdir($conf->barcode->dir_temp);
		if (!is_writable($conf->barcode->dir_temp)) {
			$this->error = "Failed to write in temp directory ".$conf->barcode->dir_temp;
			dol_syslog('Error in write_file: '.$this->error, LOG_ERR);
			return -1;
		}

		$file = $conf->barcode->dir_temp.'/barcode_'.$code.'_'.$encoding.'.png';

		$filebarcode = $file; // global var to be used in barcode_outimage called by barcode_print in buildBarCode

		$result = $this->buildBarCode($code, $encoding, $readable, $scale, $nooutputiferror);

		return $result;
	}
}
