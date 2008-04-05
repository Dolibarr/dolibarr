<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
   \file       htdocs/expedition/mods/pdf/ModelePdfExpedition.class.php
   \ingroup    shipping
   \brief      Fichier contenant la classe mère de generation des expeditions
   \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT.'/lib/functions.lib.php');
require_once DOL_DOCUMENT_ROOT.'/includes/fpdf/DolibarrPdfBarCode.class.php';


/**
   \class      ModelePdfExpedition
   \brief      Classe mère des modèles de bon d'expedition
*/

class ModelePdfExpedition extends DolibarrPdfBarCode
{
    var $error='';


   /** 
        \brief Renvoi le dernier message d'erreur de création de PDF de commande
    */
    function pdferror()
    {
        return $this->error;
    }


    /** 
     *      \brief      Renvoi la liste des modèles actifs
     *      \return    array        Tableau des modeles (cle=id, valeur=libelle)
     */
    function liste_modeles($db)
    {
        $type='shipping';
        $liste=array();
        $sql ="SELECT nom as id, nom as lib";
        $sql.=" FROM ".MAIN_DB_PREFIX."document_model";
        $sql.=" WHERE type = '".$type."'";
        
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $db->fetch_row($resql);
                $liste[$row[0]]=$row[1];
                $i++;
            }
        }
        else
        {
            $this->error=$db->error();
            return -1;
        }
        return $liste;
    }
 
}


/*
		\brief      Crée un bon d'expedition sur disque
		\param	    db  			objet base de donnée
		\param	    id				id de la expedition à créer
		\param	    modele			force le modele à utiliser ('' par defaut)
		\param		outputlangs		objet lang a utiliser pour traduction
*/
function expedition_pdf_create($db, $id, $modele='', $outputlangs='')
{
	global $conf,$langs;
	$langs->load("sendings");
	
	$dir = DOL_DOCUMENT_ROOT."/expedition/mods/pdf/";
	$modelisok=0;

	// Positionne modele sur le nom du modele de commande à utiliser
	$file = "pdf_expedition_".$modele.".modules.php";
	if ($modele && file_exists($dir.$file)) $modelisok=1;

    // Si model pas encore bon 
	if (! $modelisok)
	{
		if ($conf->global->EXPEDITION_ADDON_PDF) $modele = $conf->global->EXPEDITION_ADDON_PDF;
      	$file = "pdf_expedition_".$modele.".modules.php";
    	if (file_exists($dir.$file)) $modelisok=1;
    }

    // Si model pas encore bon 
	if (! $modelisok)
	{
	    $liste=array();
		$model=new ModelePDFExpedition();
		$liste=$model->liste_modeles($db);
        $modele=key($liste);        // Renvoie premiere valeur de clé trouvé dans le tableau
      	$file = "pdf_expedition_".$modele.".modules.php";
    	if (file_exists($dir.$file)) $modelisok=1;
	}
	
	// Charge le modele
    if ($modelisok)
	{
		$classname = "pdf_expedition_".$modele;
		require_once($dir.$file);
	
		$obj = new $classname($db);

		$expedition = new Expedition($db);
		$result=$expedition->fetch($id);
		$result=$expedition->fetch_object($expedition->origin);
		
        if ($obj->write_file($expedition, $langs) > 0)
		{
			// on supprime l'image correspondant au preview
//			expedition_delete_preview($db, $id);
			return 1;
		}
		else
		{
			dolibarr_syslog("Erreur dans expedition_pdf_create");
			dolibarr_print_error($db,$obj->pdferror());
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
