<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *  \file       htdocs/includes/modules/expedition/pdf/ModelePdfExpedition.class.php
 *  \ingroup    shipping
 *  \brief      Fichier contenant la classe mere de generation des expeditions
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");


/**
 *  \class      ModelePdfExpedition
 *  \brief      Parent class of sending receipts models
 */
abstract class ModelePdfExpedition extends CommonDocGenerator
{
    var $error='';


	/**
	 *      \brief      Return list of active generation modules
	 * 		\param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='shipping';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}
}


/**
 * 	Cree un bon d'expedition sur disque
 * 	@param	    db  			objet base de donnee
 * 	@param	    object			object expedition
 * 	@param	    modele			force le modele a utiliser ('' to not force)
 * 	@param		outputlangs		objet lang a utiliser pour traduction
 *  @return     int             <=0 if KO, >0 if OK
 */
function expedition_pdf_create($db, $object, $modele, $outputlangs)
{
	global $conf,$langs;
	$langs->load("sendings");

	$dir = "/includes/modules/expedition/pdf/";
	$modelisok=0;

	// Positionne modele sur le nom du modele de commande a utiliser
	$file = "pdf_expedition_".$modele.".modules.php";

	// On verifie l'emplacement du modele
	$file = dol_buildpath($dir.$file);

	if ($modele && file_exists($file)) $modelisok=1;

    // Si model pas encore bon
	if (! $modelisok)
	{
		if ($conf->global->EXPEDITION_ADDON_PDF) $modele = $conf->global->EXPEDITION_ADDON_PDF;
      	$file = "pdf_expedition_".$modele.".modules.php";
      	// On verifie l'emplacement du modele
		$file = dol_buildpath($dir.$file);
    	if (file_exists($file)) $modelisok=1;
    }

    // Si model pas encore bon
	if (! $modelisok)
	{
		$liste=ModelePDFExpedition::liste_modeles($db);
        $modele=key($liste);        // Renvoie premiere valeur de cle trouve dans le tableau
      	$file = "pdf_expedition_".$modele.".modules.php";
      	// On verifie l'emplacement du modele
		$file = dol_buildpath($dir.$file);
    	if (file_exists($file)) $modelisok=1;
	}

	// Charge le modele
    if ($modelisok)
	{
	    dol_syslog("expedition_pdf_create ".$modele);
		$classname = "pdf_expedition_".$modele;
		require_once($file);

		$obj = new $classname($db);

		$result=$object->fetch_origin();

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			// on supprime l'image correspondant au preview
			//expedition_delete_preview($db, $id);
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans expedition_pdf_create");
			dol_print_error($db,$obj->error);
			return 0;
		}
	}
	else
	{
        if (! $conf->global->EXPEDITION_ADDON_PDF)
        {
			print $langs->trans("Error")." ".$langs->trans("Error_EXPEDITION_ADDON_PDF_NotDefined");
        }
        else
        {
    		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
        }
		return 0;
   }
}

?>
