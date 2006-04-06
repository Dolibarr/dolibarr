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
 */


/**
     	\file       htdocs/includes/modules/propale/mod_propale_emeraude.php
		\ingroup    propale
		\brief      Fichier contenant la classe du modèle de numérotation de référence de propale Emeraude
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");

/**	    \class      mod_propale_emeraude
		\brief      Classe du modèle de numérotation de référence de propale Emeraude
*/

class mod_propale_emeraude extends ModeleNumRefPropales
{

    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
    	global $conf;
    	
      $texte = "Renvoie le numéro sous la forme PRYYNNNNN où YY est l'année et NNNNN le numéro d'incrément qui commence à 1.<br>\n";
      $texte.= "L'année s'incrémente de 1 et le numéro d'incrément se remet à zero en début d'année d'exercice.<br>\n";
      $texte.= "Définir la variable SOCIETE_FISCAL_MONTH_START avec le mois du début d'exercice, ex: 9 pour septembre.<br>\n";
      $texte.= "Dans cette exemple nous aurons au 1er septembre 2006 une propale nommée PR0700001.<br>\n";
      
      if ($conf->global->SOCIETE_FISCAL_MONTH_START)
      {
      	$texte.= "SOCIETE_FISCAL_MONTH_START est définie et vaut: ".$conf->global->SOCIETE_FISCAL_MONTH_START."";
      }
      else
      {
      	$texte.= "SOCIETE_FISCAL_MONTH_START n'est pas définie.";
      }
      return $texte;
    }


    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        return "PR0500001";
    }


    /**     \brief      Renvoi prochaine valeur attribuée
     *      \param      objsoc      Objet société
     *      \return     string      Valeur
     */
    function getNextValue($objsoc=0)
    {
        global $db,$conf;
    
        $sql = "SELECT count(*) FROM  ".MAIN_DB_PREFIX."propal";
    
        if ( $db->query($sql) )
        {
            $row = $db->fetch_row(0);
    
            $num = $row[0];
        }
		$current_month = date("n");
		if($current_month >= $conf->global->FISCAL_MONTH_START)
        	$y = strftime("%y",mktime(0,0,0,date("m"),date("d"),date("Y")+1));
		else
			$y = strftime("%y",time());
			$num = $num + 1;   
        return  "PR" . "$y" . substr("0000".$num, strlen("0000".$num)-5,5);
    }
    
}

?>
