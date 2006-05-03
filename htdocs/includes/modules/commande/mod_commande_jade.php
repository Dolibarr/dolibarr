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
    \file       htdocs/includes/modules/commande/mod_commande_jade.php
    \ingroup    commande
    \brief      Fichier contenant la classe du modèle de numérotation de référence de commande Jade
    \version    $Revision$
*/

include_once("modules_commande.php");


/**
   \class      mod_commande_jade
   \brief      Classe du modèle de numérotation de référence de commande Jade
*/

class mod_commande_jade extends ModeleNumRefCommandes
{

  /**   \brief      Constructeur
   */
  function mod_commande_jade()
  {
    $this->nom = "Jade";
  }


  /**     \brief      Renvoi la description du modele de numérotation
   *      \return     string      Texte descripif
   */
  function info()
  {
    return "Renvoie le numéro sous la forme numérique CYY00001, CYY00002, CYY00003, ... où YY représente l'année. Le numéro d'incrément qui suit l'année n'est PAS remis à zéro en début d'année.";
  }
  
  /**     \brief      Renvoi prochaine valeur attribuée
     *      \return     string      Valeur
     */
    function getNextValue()
    {
        global $db;

        // D'abord on récupère la valeur max (réponse immédiate car champ indéxé)
        $cyy='';
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $cyy = substr($row[0],0,4);
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
        else
        {
            $max=0;
        }        
        $yy = strftime("%y",time());
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
