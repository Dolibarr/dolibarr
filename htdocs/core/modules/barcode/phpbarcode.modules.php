<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/barcode/phpbarcode.modules.php
 *	\ingroup    facture
 *	\brief      Fichier contenant la classe du modele de generation code barre phpbarcode
 */

require_once(DOL_DOCUMENT_ROOT ."/core/modules/barcode/modules_barcode.php");

/**		\class      modPhpbarcode
 *		\brief      Classe du modele de numerotation de generation code barre phpbarcode
 */
class modPhpbarcode extends ModeleBarCode
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error='';


	/**
	 * 	Return if a module can be used or not
	 *
	 *  @return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}


	/**
	 * 	Return description
	 *
	 * 	@return     string      Texte descripif
	 */
	function info()
	{
		global $langs;

		return 'Internal engine';
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *	@return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $langs;

		return true;
	}


	/**
	 *	Return true if encodinf is supported
	 *
	 *	@return		int		>0 if supported, 0 if not
	 */
	function encodingIsSupported($encoding)
	{
		global $genbarcode_loc;

		$supported=0;
		if ($encoding == 'EAN13') $supported=1;
		if ($encoding == 'ISBN')  $supported=1;
		// Formats that hangs on Windows (when genbarcode.exe for Windows is called, so they are not
		// activated on Windows)
		if (file_exists($genbarcode_loc) && empty($_SERVER["WINDIR"]))
		{
			if ($encoding == 'EAN8')  $supported=1;
			if ($encoding == 'UPC')   $supported=1;
			if ($encoding == 'C39')   $supported=1;
			if ($encoding == 'C128')  $supported=1;
		}
		return $supported;
	}

    /**
	 *	Return an image file on the fly (no need to write on disk)
	 *
	 *	@param	string   	$code			Value to encode
	 *	@param  string	 	$encoding		Mode of encoding
	 *	@param  string	 	$readable		Code can be read
     */
	function buildBarCode($code,$encoding,$readable='Y')
	{
		global $_GET,$_SERVER;
		global $conf;
		global $genbarcode_loc, $bar_color, $bg_color, $text_color, $font_loc;

		if (! $this->encodingIsSupported($encoding)) return -1;

		if ($encoding == 'EAN8' || $encoding == 'EAN13') $encoding = 'EAN';
		if ($encoding == 'C39' || $encoding == 'C128')   $encoding = substr($encoding,1);

		$scale=1; $mode='png';

		$_GET["code"]=$code;
		$_GET["encoding"]=$encoding;
		$_GET["scale"]=$scale;
		$_GET["mode"]=$mode;

		require_once(DOL_DOCUMENT_ROOT.'/core/lib/phpbarcode.php');
		dol_syslog("modPhpbarcode::buildBarCode $code,$encoding,$scale,$mode");
		if ($code) $result=barcode_print($code,$encoding,$scale,$mode);

		if (! is_array($result))
		{
			$this->error=$result;
			print $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 *	Save an image file on disk (with no output)
	 *
	 *	@param	string   	$code			Value to encode
	 *	@param	string   	$encoding		Mode of encoding
	 *	@param  string	 	$readable		Code can be read
	 */
	function writeBarCode($code,$encoding,$readable='Y')
	{
		global $conf,$filebarcode;

		create_exdir($conf->barcode->dir_temp);

		$file=$conf->barcode->dir_temp.'/barcode_'.$code.'_'.$encoding.'.png';

		$filebarcode=$file;	// global var to be used in barcode_outimage called by barcode_print in buildBarCode

		$result=$this->buildBarCode($code,$encoding,$readable);

		return $result;
	}

}

?>
