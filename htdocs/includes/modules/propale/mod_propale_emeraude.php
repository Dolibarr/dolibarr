<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
     	\file       htdocs/includes/modules/propale/mod_propale_emeraude.php
		\ingroup    propale
		\brief      Fichier contenant la classe du modèle de numérotation de référence de propale Emeraude
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");


/**	    \class      mod_propale_emeraude
		\brief      Classe du modèle de numérotation de référence de propale Emeraude
*/

class mod_propale_emeraude extends ModeleNumRefPropales
{

  /**   \brief      Constructeur
   */
  function mod_propale_emeraude()
    {
      $this->nom = "Emeraude";
    }
    
    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
    	global $conf,$langs;
    	
    	$langs->load("propal");
      
      $texte = $langs->trans('EmeraudeNumRefModelDesc1')."<br>\n";
      $texte.= $langs->trans('EmeraudeNumRefModelDesc2')."<br>\n";
      $texte.= $langs->trans('EmeraudeNumRefModelDesc3')."<br>\n";
      $texte.= $langs->trans('EmeraudeNumRefModelDesc4')."<br>\n";
      
      if ($conf->global->SOCIETE_FISCAL_MONTH_START)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.monthArrayOrSelected($conf->global->SOCIETE_FISCAL_MONTH_START).')';
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
        return "PR0500001";
    }


  /**     \brief      Renvoi prochaine valeur attribuée
   *      \return     string      Valeur
   */
    function getNextValue($objsoc=0)
    {
        global $db,$conf;
        
        // D'abord on défini l'année fiscale
        $prefix='PR';
        $current_month = date("n");
        if($conf->global->SOCIETE_FISCAL_MONTH_START > 1 && $current_month >= $conf->global->SOCIETE_FISCAL_MONTH_START)
        {
        	$yy = strftime("%y",dolibarr_mktime(0,0,0,date("m"),date("d"),date("Y")+1));
        }
        else
        {
        	$yy = strftime("%y",time());
        }
        
        // On récupère la valeur max (réponse immédiate car champ indéxé)
        $fisc=$prefix.$yy;
        $pryy='';
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."propal";
        $sql.= " WHERE ref like '${fisc}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $pryy = substr($row[0],0,4);
        }

        // Si au moins un champ respectant le modèle a été trouvé
        if (eregi('PR[0-9][0-9]',$pryy))
        {
            // Recherche rapide car restreint par un like sur champ indexé
            $date = strftime("%Y%m", time());
            $posindice=5;
            $sql = "SELECT MAX(0+SUBSTRING(ref,$posindice))";
            $sql.= " FROM ".MAIN_DB_PREFIX."propal";
            $sql.= " WHERE ref like '${pryy}%'";
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
        
        return  "PR$yy$num";
    }
    
}

?>
