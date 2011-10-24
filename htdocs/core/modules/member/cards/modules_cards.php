<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/core/modules/member/cards/modules_cards.php
 *	\ingroup    member
 *	\brief      File of parent class of document generator for members cards.
 */

require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	\class      ModelePDFCards
 *	\brief      Parent class of document generator for members cards.
 */
class ModelePDFCards
{
	var $error='';


	/**
	 *      \brief      Return list of active generation modules
	 * 		\param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='members_card';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}
}


/**
 *	Cree un fichier de cartes de visites en fonction du modele de ADHERENT_CARDS_ADDON_PDF
 *	@param   	db  			Database handler
 *	@param   	arrayofmembers	Array of members
 *	@param	    modele			Force modele to use ('' to not force)
 *	@param		outputlangs		Objet langs to use for translation
 *	@return  	int        		<0 if KO, >0 if OK
 */
function members_card_pdf_create($db, $arrayofmembers, $modele, $outputlangs)
{
	global $conf,$langs;
	$langs->load("members");

	$dir = DOL_DOCUMENT_ROOT . "/core/modules/member/cards/";

	// Positionne modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
		if ($conf->global->ADHERENT_CARDS_ADDON_PDF)
		{
			$modele = $conf->global->ADHERENT_CARDS_ADDON_PDF;
		}
		else
		{
			$modele = 'standard';
		}
	}


	// Charge le modele
	$file = "pdf_".$modele.".class.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($arrayofmembers, $outputlangs) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"members_card_pdf_create Error: ".$obj->error);
			return -1;
		}
	}

	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file));
		return -1;
	}


}

?>