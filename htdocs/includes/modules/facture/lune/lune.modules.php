<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005 Sylvain SCATTOLINI   <sylvain@s-infoservices.com>
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
    	\file       htdocs/includes/modules/facture/lune/lune.modules.php
		\ingroup    facture
		\brief      Fichier contenant la classe du modèle de numérotation de référence de facture Lune
		\version    $Revision$
*/


/**
    	\class      mod_facture_lune
		\brief      Classe du modèle de numérotation de référence de facture Lune
*/
class mod_facture_lune extends ModeleNumRefFactures
{
    
    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
      return '
    Système de numérotation mensuel sous la forme F0501015, qui correspond à la 15ème facture du mois de Janvier 2005';
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        return "F0501015";
    }

    /**     \brief      Renvoie la référence de facture suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0)
    { 
      global $db;
      global $fac;
      
      $prefix='F';
      $date = strftime("%y%m", $fac->date);
      $num=0;
      
      $sql = "SELECT max(0+substring(facnumber,6,8)) FROM ".MAIN_DB_PREFIX."facture";
      $sql .= " WHERE facnumber like '$prefix".$date."%'";
    
      if ( $db->query($sql) ) 
        {
          $row = $db->fetch_row(0);
       
          $num = $row[0];
        }
      $num++;
      $num=sprintf("%03s",$num);
      return  "$prefix" . $date . $num;
    }
    
}

?>
