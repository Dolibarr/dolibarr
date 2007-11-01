<?php
/* Copyright (C) 2005        Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006   Regis Houssin        <regis@dolibarr.fr>
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
    \file       htdocs/includes/modules/commande/mod_commande_diamant.php
    \ingroup    commande
    \brief      Fichier contenant la classe du modèle de numérotation de référence de commande Diamant
    \version    $Revision$
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/modules_commande.php");


/**
   \class      mod_commande_diamant
   \brief      Classe du modèle de numérotation de référence de commande Diamant
*/

class mod_commande_diamant extends ModeleNumRefCommandes
{

  /**   \brief      Constructeur
   */
  function mod_commande_diamant()
  {
    $this->nom = "Diamant";
  }


  /**     \brief      Renvoi la description du modele de numérotation
   *      \return     string      Texte descripif
   */
  function info()
  {
    $texte = "Renvoie le numéro sous la forme numérique CYYNNNNN, où YY représente l'année et NNNNN Le numéro d'incrément. Ce dernier n'est PAS remis à zéro en début d'année.<br>\n";
    $texte.= "Si la constante COMMANDE_DIAMANT_DELTA est définie, un offset est appliqué sur le compteur";
    
    if (defined("COMMANDE_DIAMANT_DELTA"))
        {
          $texte .= " (Définie et vaut: ".COMMANDE_DIAMANT_DELTA.")";
        }
      else
        {
          $texte .= " (N'est pas définie)";
        }
      return $texte;
  }
  

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
    	$y = strftime("%y",time());
    	
    	if (defined("COMMANDE_DIAMANT_DELTA"))
        {
        	$num = sprintf("%02d",COMMANDE_DIAMANT_DELTA);
          return "C".$y.substr("0000".$num, strlen("0000".$num)-5,5);
        }
        else 
        {
            return "C".$y."00001";
        }            
    }
    
    /**     \brief      Renvoi prochaine valeur attribuée
     *      \return     string      Valeur
     */
    function getNextValue($objsoc=0)
    {
        global $db, $conf;

        // D'abord on récupère la valeur max (réponse immédiate car champ indéxé)
        $cyy = 'C'.strftime("%y",time());
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande";
        $sql.= " WHERE ref like '${cyy}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            $cyy='';
            if ($row) $cyy = substr($row[0],0,3);
        }
    
        //on vérifie si il y a une année précédente
        //sinon le delta sera appliqué de nouveau sur la nouvelle année
        $lastyy = 'C'.strftime("%y",dolibarr_mktime(0,0,0,date("m"),date("d"),date("Y")-1));
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande";
        $sql.= " WHERE ref like '${lastyy}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            $lastyy='';
            if ($row) $lastyy = substr($row[0],0,4);
        }
        
        // Si au moins un champ respectant le modèle a été trouvée
        if (eregi('C[0-9][0-9]',$cyy))
        {
            // Recherche rapide car restreint par un like sur champ indexé
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
        else if (!eregi('C[0-9][0-9]',$lastyy))
        {
            $max=$conf->global->COMMANDE_DIAMANT_DELTA?$conf->global->COMMANDE_DIAMANT_DELTA:0;
        }
        
        $num = sprintf("%05s",$max+1);
        $yy = strftime("%y",time());
        
        return  "C$yy$num";
    }
    
    
    /**     \brief      Renvoie la référence de commande suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
    function commande_get_num($objsoc=0)
    {
        return $this->getNextValue($objsoc);
    }
}
?>
