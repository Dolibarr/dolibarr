<?PHP
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

/*!	\file htdocs/includes/modules/facture/saturne/saturne.modules.php
		\ingroup    facture
		\brief      Fichier contenant la classe du modèle de numérotation de référence de facture Saturne
		\version    $Revision$
*/


/*!	\class mod_facture_saturne
		\brief      Classe du modèle de numérotation de référence de facture Saturne
*/

class mod_facture_saturne extends ModeleNumRefFactures
{

    /*!     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
      return '
    Renvoie le numéro de facture avec un préfixe suivi du mois sur 2 digits et l\'année sur un digit.';
    }

    /*!     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        return "PREFIX084";
    }

    /*!     \brief      Renvoie la référence de facture suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0, $prefixe_additionnel)
    { 
       
      $y = strftime("%y",time());
      $m = strftime("%m",time());
    
      return  $prefixe_additionnel . $m . substr($y, -1);
    
    }    
}

?>
