<?php
/* Copyright (C) 2005       Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006  Regis Houssin        <regis@dolibarr.fr>
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
    \file       htdocs/includes/modules/commande/mod_commande_emeraude.php
    \ingroup    commande
    \brief      Fichier contenant la classe du modèle de numérotation de référence de commande Emeraude
    \version    $Revision$
*/

include_once("modules_commande.php");


/**
   \class      mod_commande_emeraude
   \brief      Classe du modèle de numérotation de référence de commande Emeraude
*/

class mod_commande_emeraude extends ModeleNumRefCommandes
{

  /**   \brief      Constructeur
   */
  function mod_commande_emeraude()
  {
    $this->nom = "Emeraude";
  }


  /**     \brief      Renvoi la description du modele de numérotation
   *      \return     string      Texte descripif
   */
  function info()
    {
    	global $conf;
    	
      $texte = "Renvoie le numéro sous la forme CYYNNNNN où YY est l'année et NNNNN le numéro d'incrément qui commence à 1.<br>\n";
      $texte.= "L'année s'incrémente de 1 et le numéro d'incrément se remet à zero en début d'année d'exercice.<br>\n";
      $texte.= "Définir la variable SOCIETE_FISCAL_MONTH_START avec le mois du début d'exercice, ex: 9 pour septembre.<br>\n";
      $texte.= "Dans cette exemple nous aurons au 1er septembre 2006 une commande nommée C0700001.<br>\n";
      
      if ($conf->global->SOCIETE_FISCAL_MONTH_START)
      {
      	$texte.= "SOCIETE_FISCAL_MONTH_START est définie et vaut: ".$conf->global->SOCIETE_FISCAL_MONTH_START."";
      }
      else
      {
      	$texte.= "SOCIETE_FISCAL_MONTH_START n'est pas définie.";
      }
      return $texte;
    }
    
   /**     \brief      Renvoi un exemple de numérotation
   *      \return     string      Example
   */
   function getExample()
   {
       return "C0600001";
   }

  
  /**     \brief      Renvoi prochaine valeur attribuée
   *      \return     string      Valeur
   */
    function getNextValue()
    {
        global $db,$conf;
        
        // D'abord on défini l'année fiscale
        $prefix='C';
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
        $cyy='';
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande";
        $sql.= " WHERE ref like '${fisc}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $cyy = substr($row[0],0,3);
        }

        // Si au moins un champ respectant le modèle a été trouvée
        if (eregi('C[0-9][0-9]',$cyy))
        {
            // Recherche rapide car restreint par un like sur champ indexé
            $date = strftime("%Y%m", time());
            $posindice=4;
            $sql = "SELECT MAX(0+SUBSTRING(ref,$posindice))";
            $sql.= " FROM ".MAIN_DB_PREFIX."commande";
            $sql.= " WHERE ref like '${cyy}%'";
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
        
        return  "C$yy$num";
    }
    
  
    /**     \brief      Renvoie la référence de commande suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
    function commande_get_num($objsoc=0)
    {
        return $this->getNextValue();
    }
}
?>
