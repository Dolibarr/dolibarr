<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/includes/modules/facture/mercure/mercure.modules.php
  \ingroup    facture
  \brief      Fichier contenant la classe du modèle de numérotation de référence de facture Mercure
  \version    $Revision$
*/


/*!	\class mod_facture_mercure
  \brief      Classe du modèle de numérotation de référence de facture Mercure
*/

class mod_facture_mercure extends ModeleNumRefFactures
{

    /*!     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
      return '
    Renvoie le numéro de facture sous une forme numérique simple, la première facture porte le numéro 1, la quinzième facture ayant le numéro 15, le numéro est préfixé par la lettre F, ce module peut être utilisé avec dans le cas d\'une numérotaion double.';
    }

    /*!     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        return "F0400001";
    }

    /*!     \brief      Renvoie la référence de facture suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0)
    { 
      global $db;
    
      $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."facture WHERE fk_statut > 0";
      $sql .= " AND facnumber LIKE 'F%'"; 

      if ( $db->query($sql) ) 
        {
          $row = $db->fetch_row(0);
          
          $num = $row[0] + 1;
        }
    
    
      $y = strftime("%y",time());
    
      return  "F" . "$y" . substr("0000".$num, -5 );
    
    }
    
}

?>
