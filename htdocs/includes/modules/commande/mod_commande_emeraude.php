<?php
/* Copyright (C) 2005       Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006  Regis Houssin        <regis.houssin@cap-networks.com>
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
      $texte = "Renvoie le numéro sous la forme CYYNNNNN où YY est l'année et NNNNN le numéro d'incrément qui commence à 1.<br>\n";
      $texte.= "L'année s'incrémente de 1 et le numéro d'incrément se remet à zero en début d'année d'exercice.<br>\n";
      $texte.= "Définir la variable FISCAL_MONTH_START avec le mois du début d'exercice, ex: 9 pour septembre.<br>\n";
      $texte.= "Dans cette exemple nous aurons au 1er septembre 2006 une commande nommée C0700001.<br>\n";
      
      if (defined("FISCAL_MONTH_START"))
      {
      	$texte.= "FISCAL_MONTH_START est définie et vaut: ".FISCAL_MONTH_START."";
      }
      else
      {
      	$texte.= "FISCAL_MONTH_START n'est pas définie.";
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
    $current_month = date("n");
	if($current_month >= FISCAL_MONTH_START)
        $y = strftime("%y",mktime(0,0,0,date("m"),date("d"),date("Y")+1));
	else
    $y = strftime("%y",time());

    return 'C'.$y.substr("0000".($num+1),-5);
  }
}
?>
