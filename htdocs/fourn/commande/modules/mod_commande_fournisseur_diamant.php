<?php
/* Copyright (C) 2005        Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006   Regis Houssin        <regis.houssin@cap-networks.com>
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
    \file       htdocs/fourn/commande/modules/pdf/mod_commande_fournisseur_diamant.php
    \ingroup    fournisseur
    \brief      Fichier contenant la classe du modèle de numérotation de référence de commande Diamant
    \version    $Revision$
*/

include_once("modules_commandefournisseur.php");


/**
   \class      mod_commande_fournisseur_diamant
   \brief      Classe du modèle de numérotation de référence de commande fournisseur Diamant
*/

class mod_commande_fournisseur_diamant extends ModeleNumRefCommandesSuppliers
{

  /**   \brief      Constructeur
   */
  function mod_commande_fournisseur_diamant()
  {
    $this->nom = "Diamant";
  }


  /**     \brief      Renvoi la description du modele de numérotation
   *      \return     string      Texte descripif
   */
  function info()
  {
    $texte = "Renvoie le numéro sous la forme numérique CYYNNNN, où YY représente l'année et NNNN Le numéro d'incrément. Ce dernier n'est PAS remis à zéro en début d'année.<br>\n";
    $texte.= "Si la constante COMMANDE_FOURNISSEUR_DIAMANT_DELTA est définie, un offset est appliqué sur le compteur";
    
    if (defined("COMMANDE_FOURNISSEUR_DIAMANT_DELTA"))
        {
          $texte .= " (Définie et vaut: ".COMMANDE_FOURNISSEUR_DIAMANT_DELTA.")";
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
    	
    	if (defined("COMMANDE_FOURNISSEUR_DIAMANT_DELTA"))
        {
        	$num = sprintf("%02d",COMMANDE_FOURNISSEUR_DIAMANT_DELTA);
          return "C".$y.substr("000".$num, strlen("000".$num)-4,4);
        }
        else 
        {
            return "C".$y."0001";
        }            
    }
    
  
  /**   \brief      Renvoie le prochaine numéro de référence de commande non utilisé
        \param      obj_soc     objet société
        \return     string      numéro de référence de commande non utilisé
   */
  function commande_get_num($obj_soc=0)
  { 
    global $db;
    
    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE fk_statut <> 0";
    
    $resql = $db->query($sql);

    if ( $resql ) 
      {
	      $row = $db->fetch_row($resql);
	
	      $num = $row[0];
      }
      
      if (!defined("COMMANDE_FOURNISSEUR_DIAMANT_DELTA"))
        {
          define("COMMANDE_FOURNISSEUR_DIAMANT_DELTA", 0);
        }
    
      $num = $num + COMMANDE_FOURNISSEUR_DIAMANT_DELTA;
    
    $y = strftime("%y",time());

    return 'C'.$y.substr("000".$num, strlen("000".$num)-4,4);
  }
}
?>
