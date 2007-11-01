<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
	\file       htdocs/includes/modules/facture/titan/titan.modules.php
	\ingroup    facture
	\brief      Fichier contenant la classe du modèle de numérotation de référence de facture Titan
	\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");

/**
	\class      mod_facture_titan
	\brief      Classe du modèle de numérotation de référence de facture Titan
*/
class mod_facture_titan extends ModeleNumRefFactures
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'

	
    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
	function info()
    {
    	global $conf,$langs;

		$langs->load("bills");
    	
      $texte = $langs->trans('TitanNumRefModelDesc1')."<br>\n";
      $texte.= $langs->trans('TitanNumRefModelDesc2')."<br>\n";
      $texte.= $langs->trans('TitanNumRefModelDesc3')."<br>\n";
      $texte.= $langs->trans('TitanNumRefModelDesc4')."<br>\n";
      
      if ($conf->global->SOCIETE_FISCAL_MONTH_START)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.$conf->global->SOCIETE_FISCAL_MONTH_START.')';
      }
      else
      {
      	$texte.= ' ('.$langs->trans('IsNotDefined').')';
      }
      return $texte;
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        return "FA0600001";           
    }

	/**		\brief      Renvoi prochaine valeur attribuée
	*      	\param      objsoc      Objet société
	*      	\param      facture		Objet facture
	*      	\return     string      Valeur
	*/
    function getNextValue($objsoc,$facture)
    {
        global $db,$conf;
        
        // On défini l'année fiscale
        $prefix='FA';
        $current_month = date("n");
        
        if (is_object($facture) && $facture->date)
        {
        	$create_month = strftime("%m",$facture->date);
        }
        else
        {
        	$create_month = $current_month;
        }
        
        if($conf->global->SOCIETE_FISCAL_MONTH_START > 1 && $current_month >= $conf->global->SOCIETE_FISCAL_MONTH_START && $create_month >= $conf->global->SOCIETE_FISCAL_MONTH_START)
        {
        	$yy = strftime("%y",dolibarr_mktime(0,0,0,date("m"),date("d"),date("Y")+1));
        }
        else
        {
        	$yy = strftime("%y",time());
        }
        
        // On récupère la valeur max (réponse immédiate car champ indéxé)
        $fisc=$prefix.$yy;
        $fayy='';
        $sql = "SELECT MAX(facnumber)";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture";
        $sql.= " WHERE facnumber like '${fisc}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $fayy = substr($row[0],0,4);
        }

        // Si au moins un champ respectant le modèle a été trouvée
        if (eregi('FA[0-9][0-9]',$fayy))
        {
            // Recherche rapide car restreint par un like sur champ indexé
            $date = strftime("%Y%m", time());
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
        else
        {
            $max=0;
        }
        
        $num = sprintf("%05s",$max+1);
        
        return  "FA$yy$num";
    }
    
  
    /**     \brief      Renvoie la référence de commande suivante non utilisée
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
