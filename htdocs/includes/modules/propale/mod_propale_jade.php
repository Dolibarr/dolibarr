<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/includes/modules/propale/mod_propale_jade.php
		\ingroup    propale
		\brief      Fichier contenant la classe du modèle de numérotation de référence de propale Jade
		\version    $Revision$
*/


/*!	\class mod_propale_jade
		\brief      Classe du modèle de numérotation de référence de propale Jade
*/

class mod_propale_jade extends ModeleNumRefPropales
{
    /*!     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
  function info()
    {
      return "Renvoie le numéro sous la forme PROP1, PROP2, ...";      
    }

    /*!     \brief      Renvoie la référence de propale suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
  function propale_get_num($objsoc=0)
    { 
      global $db;
      
      $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."propal";
      
      if ( $db->query($sql) ) 
	{
	  $row = $db->fetch_row(0);
	  
	  $num = $row[0];
	}
      
      $y = strftime("%y",time());
      
      return  "PROP" . $num;      
    }
}

?>
