<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 *	Return if a module can be used or not
	 *
	 *	@return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 *	Return the default description of numbering module
	 *
	 *	@return     string      Texte descripif
	 */
	function info()
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
	function getExample()
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
	function canBeActivated()
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
	function getNextValue($objsoc,$object)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Returns the module numbering version
	 *
	 *	@return     string      Value
	 */
	function getVersion()
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
 *	\brief      Classe mere des modeles de
 */
abstract class ModeleChequeReceipts extends CommonDocGenerator
{
	/**
	 * @var string Error code (or message)
	 */
	public $error='';

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
        // phpcs:enable
		global $conf;

		$type='chequereceipt';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);
		// TODO Remove this to use getListOfModels only
		$liste = array('blochet'=>'blochet');

		return $liste;
	}
}


/**
 *	Cree un bordereau remise de cheque
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
