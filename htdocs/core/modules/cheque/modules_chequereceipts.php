<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
<<<<<<< HEAD
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2016      Juanjo Menent		<jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/cheque/modules_chequereceipts.php
 *	\ingroup    facture
 *	\brief      File with parent class of check receipt document generators
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';   // Requis car utilise dans les classes qui heritent

/**
 *  \class      ModeleNumRefChequeReceipts
 *  \brief      Cheque Receipts numbering references mother class
 */
abstract class ModeleNumRefChequeReceipts
{
<<<<<<< HEAD
	var $error='';
=======
	/**
	 * @var string Error code (or message)
	 */
	public $error='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *	Return if a module can be used or not
	 *
	 *	@return		boolean     true if module can be used
	 */
<<<<<<< HEAD
	function isEnabled()
=======
	public function isEnabled()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return true;
	}

	/**
	 *	Return the default description of numbering module
	 *
	 *	@return     string      Texte descripif
	 */
<<<<<<< HEAD
	function info()
=======
	public function info()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Return numbering example
	 *
	 *	@return     string      Example
	 */
<<<<<<< HEAD
	function getExample()
=======
	public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoExample");
	}

	/**
	 *  Test if the existing numbers in the database do not cause conflicts that would prevent this numbering run.
	 *
	 *	@return     boolean     false si conflit, true si ok
	 */
<<<<<<< HEAD
	function canBeActivated()
=======
	public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return true;
	}

	/**
	 *	Returns the next value
	 *
	 *	@param	Societe		$objsoc     Object thirdparty
	 *	@param	Object		$object		Object we need next value for
	 *	@return	string      Valeur
	 */
<<<<<<< HEAD
	function getNextValue($objsoc,$object)
=======
	public function getNextValue($objsoc, $object)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Returns the module numbering version
	 *
	 *	@return     string      Value
	 */
<<<<<<< HEAD
	function getVersion()
=======
	public function getVersion()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
	}
}

/**
 *	\class      ModeleChequeReceipts
<<<<<<< HEAD
 *	\brief      Classe mere des modeles de 
 */
abstract class ModeleChequeReceipts extends CommonDocGenerator
{
	var $error='';

=======
 *	\brief      Classe mere des modeles de
 */
abstract class ModeleChequeReceipts extends CommonDocGenerator
{
	/**
	 * @var string Error code (or message)
	 */
	public $error='';

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
<<<<<<< HEAD
	static function liste_modeles($db,$maxfilenamelength=0)
	{
=======
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf;

		$type='chequereceipt';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
<<<<<<< HEAD
		$liste=getListOfModels($db,$type,$maxfilenamelength);
=======
		$liste=getListOfModels($db, $type, $maxfilenamelength);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		// TODO Remove this to use getListOfModels only
		$liste = array('blochet'=>'blochet');

		return $liste;
	}
}


/**
<<<<<<< HEAD
 *	Cree un bordereau remise de cheque
=======
 *  Cree un bordereau remise de cheque
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *
 * 	@param	DoliDB		$db				Database handler
 *	@param	int			$id				Object invoice (or id of invoice)
 *	@param	string		$message		Message
 *	@param	string		$modele			Force le modele a utiliser ('' to not force)
 *	@param	Translate	$outputlangs	Object lang a utiliser pour traduction
 *	@return int        					<0 if KO, >0 if OK
 * 	TODO Use commonDocGenerator
 */
function chequereceipt_pdf_create($db, $id, $message, $modele, $outputlangs)
{
	global $conf,$langs;
	$langs->load("bills");

	$dir = DOL_DOCUMENT_ROOT . "/core/modules/cheque/doc/";

	// Positionne modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
		if (! empty($conf->global->CHEQUERECEIPT_ADDON_PDF))
		{
			$modele = $conf->global->CHEQUERECEIPT_ADDON_PDF;
		}
		else
		{
			//print $langs->trans("Error")." ".$langs->trans("Error_FACTURE_ADDON_PDF_NotDefined");
			//return 0;
			$modele = 'blochet';
		}
	}

	// Charge le modele
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once $dir.$file;

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($id, $outputlangs) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
<<<<<<< HEAD
			dol_print_error($db,"chequereceipt_pdf_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file));
		return -1;
	}
}

=======
			dol_print_error($db, "chequereceipt_pdf_create Error: ".$obj->error);
			return -1;
		}
	}
	else
	{
		dol_print_error('', $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists", $dir.$file));
		return -1;
	}
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
