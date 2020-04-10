<?php
/* Copyright (C) 2005-2009 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005	   Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015	   Francis Appels		<francis.appels@yahoo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *		\file		htdocs/core/modules/barcode/doc/tcpdfbarcode.modules.php
 *		\ingroup	barcode
 *		\brief		File of class to manage barcode numbering with tcpdf library
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/modules_barcode.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/barcode.lib.php'; // This is to include def like $genbarcode_loc and $font_loc

/**
 *	Class to generate barcode images using tcpdf barcode generator
 */
class modTcpdfbarcode extends ModeleBarCode
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

	public $is2d = false;

	/**
	 *	Return description of numbering model
	 *
	 *	@return		string		Text with description
	 */
	public function info()
	{
		global $langs;

		return 'TCPDF-barcode';
	}

	/**
	 *	Return if a module can be used or not
	 *
	 *	@return		boolean		true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *	@return		boolean		false if conflict, true if ok
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
		$tcpdfEncoding = $this->getTcpdfEncodingType($encoding);
		if (empty($tcpdfEncoding)) {
			return 0;
		} else {
			return 1;
		}
	}

	/**
	 *	Return an image file on the fly (no need to write on disk)
	 *
	 *	@param	   string	    $code		      Value to encode
	 *	@param	   string	    $encoding	      Mode of encoding
	 *	@param	   string	    $readable	      Code can be read
	 *	@param	   integer		$scale			  Scale (not used with this engine)
	 *  @param     integer      $nooutputiferror  No output if error (not used with this engine)
	 *	@return	   int			                  <0 if KO, >0 if OK
	 */
	public function buildBarCode($code, $encoding, $readable = 'Y', $scale = 1, $nooutputiferror = 0)
	{
		global $_GET;

		$tcpdfEncoding = $this->getTcpdfEncodingType($encoding);
		if (empty($tcpdfEncoding)) return -1;

		$color = array(0, 0, 0);

		$_GET["code"] = $code;
		$_GET["type"] = $encoding;
		$_GET["readable"] = $readable;

		if ($code) {
			// Load the tcpdf barcode class
			if ($this->is2d) {
				$height = 3;
				$width = 3;
				require_once TCPDF_PATH.'tcpdf_barcodes_2d.php';
				$barcodeobj = new TCPDF2DBarcode($code, $tcpdfEncoding);
			} else {
				$height = 50;
				$width = 1;
				require_once TCPDF_PATH.'tcpdf_barcodes_1d.php';
				$barcodeobj = new TCPDFBarcode($code, $tcpdfEncoding);
			}

			dol_syslog("buildBarCode::TCPDF.getBarcodePNG");
			$barcodeobj->getBarcodePNG($width, $height, $color);

			return 1;
		} else {
			return -2;
		}
	}

	/**
	 *	Save an image file on disk (with no output)
	 *
	 *	@param	   string	    $code		      Value to encode
	 *	@param	   string	    $encoding	      Mode of encoding
	 *	@param	   string	    $readable	      Code can be read
	 *	@param	   integer		$scale			  Scale (not used with this engine)
	 *  @param     integer      $nooutputiferror  No output if error (not used with this engine)
	 *	@return	   int			                  <0 if KO, >0 if OK
	 */
	public function writeBarCode($code, $encoding, $readable = 'Y', $scale = 1, $nooutputiferror = 0)
	{
		global $conf, $_GET;

		dol_mkdir($conf->barcode->dir_temp);
		$file = $conf->barcode->dir_temp.'/barcode_'.$code.'_'.$encoding.'.png';

		$tcpdfEncoding = $this->getTcpdfEncodingType($encoding);
		if (empty($tcpdfEncoding)) return -1;

		$color = array(0, 0, 0);

		$_GET["code"] = $code;
		$_GET["type"] = $encoding;
		$_GET["readable"] = $readable;

		if ($code) {
			// Load the tcpdf barcode class
			if ($this->is2d) {
				$height = 1;
				$width = 1;
				require_once TCPDF_PATH.'tcpdf_barcodes_2d.php';
				$barcodeobj = new TCPDF2DBarcode($code, $tcpdfEncoding);
			} else {
				$height = 50;
				$width = 1;
				require_once TCPDF_PATH.'tcpdf_barcodes_1d.php';
				$barcodeobj = new TCPDFBarcode($code, $tcpdfEncoding);
			}

			dol_syslog("writeBarCode::TCPDF.getBarcodePngData");
			if ($imageData = $barcodeobj->getBarcodePngData($width, $height, $color)) {
				if (function_exists('imagecreate')) {
					$imageData = imagecreatefromstring($imageData);
				}
				if (imagepng($imageData, $file)) {
					return 1;
				} else {
					return -3;
				}
			} else {
				return -4;
			}
		} else {
			return -2;
		}
	}

	/**
	 *	get available output_modes for tcpdf class wth its translated description
	 *
	 * @param	string $dolEncodingType dolibarr barcode encoding type
	 * @return	string tcpdf encoding type
	 */
	public function getTcpdfEncodingType($dolEncodingType)
	{
		$tcpdf1dEncodingTypes = array(
						'C39' => 'C39',
						'C39+' => 'C39+',
						'C39E' => 'C39E',
						'C39E+' => 'C39E+',
						'S25' => 'S25',
						'S25+' => 'S25+',
						'I25' => 'I25',
						'I25+' => 'I25+',
						'C128' => 'C128',
						'C128A' => 'C128A',
						'C128B' => 'C128B',
						'C128C' => 'C128C',
						'EAN2' => 'EAN2',
						'EAN5' => 'EAN5',
						'EAN8' => 'EAN8',
						'EAN13' => 'EAN13',
						'ISBN' => 'EAN13',
						'UPC' => 'UPCA',
						'UPCE' => 'UPCE',
						'MSI' => 'MSI',
						'MSI+' => 'MSI+',
						'POSTNET' => 'POSTNET',
						'PLANET' => 'PLANET',
						'RMS4CC' => 'RMS4CC',
						'KIX' => 'KIX',
						'IMB' => 'IMB',
						'CODABAR' => 'CODABAR',
						'CODE11' => 'CODE11',
						'PHARMA' => 'PHARMA',
						'PHARMA2T' => 'PHARMA2T'
		);

		$tcpdf2dEncodingTypes = array(
						'DATAMATRIX' => 'DATAMATRIX',
						'PDF417' => 'PDF417',
						'QRCODE' => 'QRCODE,L',
						'QRCODE,L' => 'QRCODE,L',
						'QRCODE,M' => 'QRCODE,M',
						'QRCODE,Q' => 'QRCODE,Q',
						'QRCODE,H' => 'QRCODE,H'
		);

		if (array_key_exists($dolEncodingType, $tcpdf1dEncodingTypes)) {
			$this->is2d = false;
			return $tcpdf1dEncodingTypes[$dolEncodingType];
		} elseif (array_key_exists($dolEncodingType, $tcpdf2dEncodingTypes)) {
			$this->is2d = true;
			return $tcpdf2dEncodingTypes[$dolEncodingType];
		} else {
			return '';
		}
	}
}
