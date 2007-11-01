<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
    \file       htdocs/includes/modules/fichinter/mod_pacific.php
		\ingroup    fiche intervention
		\brief      Fichier contenant la classe du modèle de numérotation de référence de fiche intervention Pacific
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/modules_fichinter.php");

/**
    \class      mod_pacific
		\brief      Classe du modèle de numérotation de référence de fiche intervention Pacific
*/

class mod_pacific extends ModeleNumRefFicheinter
{
	var $prefix='FI';
	var $error='';
	
	
	/**   \brief      Constructeur
	*/
	function mod_pacific()
	{
		$this->nom = "pacific";
	}


    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
	 	global $langs;

		$langs->load("bills");
		
    	return $langs->trans('PacificNumRefModelDesc1',$this->prefix);
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        return $this->prefix."0501-0001";
    }

    /**     \brief      Test si les numéros déjà en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numérotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
	function canBeActivated()
	{
		global $langs;
	
		$langs->load("bills");
	
		$fayymm='';
	
		$sql = "SELECT MAX(ref)";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) $fayymm = substr($row[0],0,6);
		}
		if (! $fayymm || eregi($this->prefix.'[0-9][0-9][0-9][0-9]',$fayymm))
		{
			return true;
		}
		else
		{
			$this->error=$langs->trans('PacificNumRefModelError');
			return false;
		}
	}

    /**     \brief      Renvoi prochaine valeur attribuée
     *      \return     string      Valeur
     */
    function getNextValue()
    {
        global $db;

        // D'abord on récupère la valeur max (réponse immédiate car champ indéxé)
        $fayymm='';
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $fayymm = substr($row[0],0,6);
        }

        // Si champ respectant le modèle a été trouvée
        if (eregi('^'.$this->prefix.'[0-9][0-9][0-9][0-9]',$fayymm))
        {
            // Recherche rapide car restreint par un like sur champ indexé
            $posindice=8;
            $sql = "SELECT MAX(0+SUBSTRING(ref,$posindice))";
            $sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
            $sql.= " WHERE ref like '${fayymm}%'";
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
        $yymm = strftime("%y%m",time());
        $num = sprintf("%04s",$max+1);
        
        return $this->prefix."$yymm-$num";
    }
    
    /**     \brief      Renvoie la référence de fiche d'intervention suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0)
    { 
        return $this->getNextValue();
    }
    
}

?>
