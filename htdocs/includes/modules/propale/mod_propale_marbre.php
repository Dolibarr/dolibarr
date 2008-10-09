<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
     	\file       htdocs/includes/modules/propale/mod_propale_marbre.php
		\ingroup    propale
		\brief      Fichier contenant la classe du mod�le de num�rotation de r�f�rence de propale Marbre
		\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");


/**	    \class      mod_propale_marbre
		\brief      Classe du mod�le de num�rotation de r�f�rence de propale Marbre
*/

class mod_propale_marbre extends ModeleNumRefPropales
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefix='PR';
    var $error='';
	var $nom = "Marbre";
	

    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
    	global $langs;
      	return $langs->trans("MarbreNumRefModelDesc");
    }


    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        return "PR0501-0001";
    }


    /**     \brief      Test si les numeros deje en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numerotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        $pryymm='';
        
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."propal";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $pryymm = substr($row[0],0,6);
        }
        if (! $pryymm || eregi('PR[0-9][0-9][0-9][0-9]',$pryymm))
        {
            return true;
        }
        else
        {
            $this->error='Une propal commeneant par $pryymm existe en base et est incompatible avec cette numerotation. Supprimer la ou renommer la pour activer ce module.';
            return false;    
        }
    }

	/**		\brief      Return next value
    *      	\param      objsoc      Object third party
	* 		\param		propal		Object commercial proposal
    *   	\return     string      Valeur
    */
	function getNextValue($objsoc,$propal)
    {
        global $db;

        // D'abord on recupere la valeur max (reponse immediate car champ indexe)
        $posindice=8;
        $sql = "SELECT MAX(0+SUBSTRING(ref,".$posindice.")) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."propal";
        $sql.= " WHERE ref like '".$this->prefix."%'";
        
        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = $obj->max;
            else $max=0;
        }
        else
        {
        	dolibarr_syslog("mod_propale_marbre::getNextValue sql=".$sql);
        	return -1;
        }

        $date=$propal->date;
        //$yymm = strftime("%y%m",time());
        $yymm = strftime("%y%m",$date);
        $num = sprintf("%04s",$max+1);
        
        dolibarr_syslog("mod_propale_marbre::getNextValue return ".$this->prefix.$yymm."-".$num);
        return $this->prefix.$yymm."-".$num;
    }

	/**		\brief      Return next free value
    *      	\param      objsoc      Object third party
	* 		\param		objforref	Object for number to search
    *   	\return     string      Next free value
    */
    function getNumRef($objsoc,$objforref)
    {
        return $this->getNextValue($objsoc,$objforref);
    }
        
}

?>
