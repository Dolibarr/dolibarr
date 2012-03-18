<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/core/modules/livraison/modules_livraison.php
 *	\ingroup    expedition
 *	\brief      Fichier contenant la classe mere de generation de bon de livraison en PDF
 *				et la classe mere de numerotation des bons de livraisons
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");


/**
 *	\class      ModelePDFDeliveryOrder
 *	\brief      Classe mere des modeles de bon de livraison
 */
abstract class ModelePDFDeliveryOrder extends CommonDocGenerator
{
	var $error='';

	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  string	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='delivery';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}
}



/**
 *	\class      ModeleNumRefDeliveryOrder
 *	\brief      Classe mere des modeles de numerotation des references de bon de livraison
 */
abstract class ModeleNumRefDeliveryOrder
{
	var $error='';

	/**
	 * Return if a module can be used or not
	 *
	 * @return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 * Renvoi la description par defaut du modele de numerotation
	 *
	 * @return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("deliveries");
		return $langs->trans("NoDescription");
	}

	/**
	 * Renvoi un exemple de numerotation
	 *
	 * @return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("deliveries");
		return $langs->trans("NoExample");
	}

	/**
	 * Test si les numeros deja en vigueur dans la base ne provoquent pas d
	 * de conflits qui empechera cette numerotation de fonctionner.
	 *
	 * @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**
	 * Renvoi prochaine valeur attribuee
	 *
	 * @return     string      Valeur
	 */
	function getNextValue()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 * Renvoi version du module numerotation
	 *
	 * @return     string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}



/**
 *	Create object on disk
 *
 *	@param	DoliDB		$db  			objet base de donnee
 *	@param	Object		$object			object delivery
 *	@param	string		$model			force le modele a utiliser ('' to not force)
 *	@param	Translate	$outputlangs	objet lang a utiliser pour traduction
 *  @return int         				0 if KO, 1 if OK
 */
function delivery_order_pdf_create($db, $object, $model='', $outputlangs='')
{
	global $conf,$langs;
	$langs->load("deliveries");

	$dir = "/core/modules/livraison/pdf/";

	// Positionne modele sur le nom du modele de bon de livraison a utiliser
	if (! dol_strlen($model))
	{
		if ($conf->global->LIVRAISON_ADDON_PDF)
		{
			$model = $conf->global->LIVRAISON_ADDON_PDF;
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("Error_LIVRAISON_ADDON_PDF_NotDefined");
			return 0;
		}
	}
	// Charge le modele
	$file = "pdf_".$model.".modules.php";
	// On verifie l'emplacement du modele
	$file = dol_buildpath($dir.$file);
	if (file_exists($file))
	{
		$classname = "pdf_".$model;
		require_once($file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object,$outputlangs) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// we delete preview files
        	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
			dol_delete_preview($object);
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans delivery_order_pdf_create");
			dol_print_error($db,$obj->error);
			return 0;
		}
	}
	else
	{
		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file);
		return 0;
	}
}

?>
