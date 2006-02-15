<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

include_once("modules_commande.php");


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
    $texte = "Renvoie le numéro sous la forme numérique CYY00001, CYY00002, CYY00003, ... où YY représente l'année. Le numéro d'incrément qui suit l'année n'est PAS remis à zéro en début d'année.<br>\n";
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
        if (defined("COMMANDE_DIAMANT_DELTA"))
        {
            return "C0400".sprintf("%02d",COMMANDE_DIAMANT_DELTA);
        }
        else 
        {
            return "C040001";
        }            
    }
    
  
  /**   \brief      Renvoie le prochaine numéro de référence de commande non utilisé
        \param      obj_soc     objet société
        \return     string      numéro de référence de commande non utilisé
   */
  function commande_get_num($obj_soc=0)
  { 
    global $db;
    
    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."commande WHERE fk_statut <> 0";
    
    $resql = $db->query($sql);

    if ( $resql ) 
      {
	      $row = $db->fetch_row($resql);
	
	      $num = $row[0];
      }
      
      if (!defined("COMMANDE_DIAMANT_DELTA"))
        {
          define("COMMANDE_DIAMANT_DELTA", 0);
        }
    
      $num = $num + FACTURE_NEPTUNE_DELTA;
    
    $y = strftime("%y",time());

    return 'C'.$y.substr("0000".$num, strlen("0000".$num)-5,5);
  }
}
?>
