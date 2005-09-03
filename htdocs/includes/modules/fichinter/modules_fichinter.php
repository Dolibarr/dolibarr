<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 */

/**	
        \file       htdocs/includes/modules/fichinter/modules_fichinter.php
		\ingroup    ficheinter
		\brief      Fichier contenant la classe mère de generation des fiches interventions en PDF
		            et la classe mère de numérotation des fiches interventions
		\version    $Revision$
*/

require_once(FPDF_PATH.'fpdf.php');

/**
    	\class      ModelePDFFicheinter
		\brief      Classe mère des modèles de fiche intervention
*/

class ModelePDFFicheinter extends FPDF
{
    var $error='';

    /**
        \brief      Constructeur
     */
    function ModelePDFFicheinter()
    {
    
    }

    /** 
        \brief      Renvoi le dernier message d'erreur de création de fiche intervention
     */
    function pdferror()
    {
        return $this->error;
    }

    /** 
     *      \brief      Renvoi la liste des modèles actifs
     */
    function liste_modeles($db)
    {
        $liste=array();
        $sql ="";
        
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
            return -1;
        }
        return $liste;
    }

}


/**
        \class      ModeleNumRefFicheinter
		\brief      Classe mère des modèles de numérotation des références de fiches d'intervention
*/

class ModeleNumRefFicheinter
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;
        $langs->load("ficheinter");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        global $langs;
        $langs->load("ficheinter");
        return $langs->trans("NoExample");
    }

}


/**
		\brief      Crée une fiche intervention sur disque en fonction du modèle de FICHEINTER_ADDON_PDF
		\param	    db  		objet base de donnée
		\param	    facid		id de la facture à créer
        \return     int         0 si KO, 1 si OK
*/
function fichinter_pdf_create($db, $facid)
{
  global $langs;
  $langs->load("ficheinter");
  
  $dir = DOL_DOCUMENT_ROOT."/includes/modules/fichinter/";

  if (defined("FICHEINTER_ADDON_PDF") && FICHEINTER_ADDON_PDF)
    {

      $file = "pdf_".FICHEINTER_ADDON_PDF.".modules.php";

      $classname = "pdf_".FICHEINTER_ADDON_PDF;
      require_once($dir.$file);

      $obj = new $classname($db);

      if ( $obj->write_pdf_file($facid) > 0)
	{
	  return 1;
	}
      else
	{
	  dolibarr_print_error($db,$obj->pdferror());
	  return 0;
	}
    }
  else
    {
      print $langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_PDF_NotDefined");
      return 0;
    }
}

?>
