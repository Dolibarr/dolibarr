<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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
	\file       htdocs/includes/modules/facture/neptune/titan.modules.php
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

    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
function info()
    {
      $texte = "Renvoie le numéro sous la forme FAYYNNNN où YY est l'année et NNNN le numéro d'incrément qui commence à 1.<br>\n";
      $texte.= "L'année s'incrémente de 1 et le numéro d'incrément se remet à zero en début d'année d'exercice.<br>\n";
      $texte.= "Définir la variable FISCAL_MONTH_START avec le mois du début d'exercice, ex: 9 pour septembre.<br>\n";
      $texte.= "Dans cette exemple nous aurons au 1er septembre 2006 une facture nommée FA070001.<br>\n";
      
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
        return "FA060001";           
    }

    /**     \brief      Renvoie la référence de facture suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0)
    { 
      global $db;
    
      $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."facture WHERE fk_statut > 0";
    
      if ( $db->query($sql) ) 
        {
          $row = $db->fetch_row(0);
          
          $num = $row[0];
        }
    
      $num = $num + 1;
    	$current_month = date("n");
		if($current_month >= FISCAL_MONTH_START)
        $y = strftime("%y",mktime(0,0,0,date("m"),date("d"),date("Y")+1));
		else
      	$y = strftime("%y",time());
      return  "FA" . "$y" . substr("000".$num, strlen("000".$num)-4,4);
    
    }
    
}    

?>
