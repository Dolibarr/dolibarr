<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
	\file       htdocs/includes/modules/facture/neptune/neptune.modules.php
	\ingroup    facture
	\brief      Fichier contenant la classe du modèle de numérotation de référence de facture Neptune
	\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");

/**
	\class      mod_facture_neptune
	\brief      Classe du modèle de numérotation de référence de facture Neptune
*/
class mod_facture_neptune extends ModeleNumRefFactures
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'

    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
	   global $langs;

		$langs->load("bills");
		
      $texte = $langs->trans('NeptuneNumRefModelDesc1')."<br>\n";
      $texte.= $langs->trans('NeptuneNumRefModelDesc2');
      if (defined("FACTURE_NEPTUNE_DELTA"))
        {
          $texte .= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.FACTURE_NEPTUNE_DELTA.')';
        }
      else
        {
          $texte .= ' ('.$langs->trans('IsNotDefined').')';
        }
      return $texte;
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
    	global $conf;
        if ($conf->global->FACTURE_NEPTUNE_DELTA)
        {
            return "FA04".sprintf("%04d",$conf->global->FACTURE_NEPTUNE_DELTA);
        }
        else 
        {
            return "FA040001";
        }            
    }

    /**     \brief      Renvoie la référence de facture suivante non utilisée
     *      \param      objsoc      Objet société
     *      \param      facture		Objet facture
     *      \return     string      Texte descripif
     */
	function getNextValue($objsoc,$facture)
	{
		global $db,$conf;
	
        // D'abord on récupère la valeur max (réponse immédiate car champ indéxé)
        $fayy = 'FA'.strftime("%y",time());
        $sql = "SELECT MAX(facnumber)";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture";
        $sql.= " WHERE facnumber like '${fayy}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            $fayy='';
            if ($row) $fayy = substr($row[0],0,4);
        }

        //on vérifie si il y a une année précédente
        //sinon le delta sera appliqué de nouveau sur la nouvelle année
        $lastyy = 'FA'.strftime("%y",dolibarr_mktime(0,0,0,date("m"),date("d"),date("Y")-1));
        $sql = "SELECT MAX(facnumber)";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture";
        $sql.= " WHERE facnumber like '${lastyy}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            $lastyy='';
            if ($row) $lastyy = substr($row[0],0,4);
        }
        
        // Si champ respectant le modèle a été trouvée
        if (eregi('^FA[0-9][0-9]',$fayy))
        {
            // Recherche rapide car restreint par un like sur champ indexé
            $posindice=5;
            $sql = "SELECT MAX(0+SUBSTRING(facnumber,$posindice))";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture";
            $sql.= " WHERE facnumber like '${fayy}%'";
            $resql=$db->query($sql);
            if ($resql)
            {
                $row = $db->fetch_row($resql);
                $max = $row[0];
            }
        }
        else if (!eregi('FA[0-9][0-9]',$lastyy))
        {
        	$max=$conf->global->FACTURE_NEPTUNE_DELTA?$conf->global->FACTURE_NEPTUNE_DELTA:0;
        }
        else
        {
        	$max=0;
        }        
        $yy = strftime("%y",time());
        $num = sprintf("%04s",$max+1);
        
        return  "FA$yy$num";
	}

    /**     \brief      Renvoie la référence de facture suivante non utilisée
     *      \param      objsoc      Objet société
     *      \param      facture		Objet facture
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0,$facture)
    { 
        return $this->getNextValue($objsoc,$facture);
    }
    
}    

?>
