<?php
/* Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
     	\file       htdocs/includes/modules/commande/mod_commande_marbre.php
		\ingroup    commande
		\brief      Fichier contenant la classe du modèle de numérotation de référence de commande Marbre
		\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/modules_commande.php");

/**	    \class      mod_commande_marbre
		\brief      Classe du modèle de numérotation de référence de commande Marbre
*/

class mod_commande_marbre extends ModeleNumRefCommandes
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefix='CO';
    var $error='';
    var $nom='Marbre';
	
    
    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
		return "Renvoie le numéro sous la forme ".$this->prefix."yymm-nnnn où yy est l'année, mm le mois et nnnn un compteur séquentiel sans rupture et sans remise à 0";
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
        $coyymm='';
        
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande";
		$sql.= " WHERE ref like '".$this->prefix."%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $coyymm = substr($row[0],0,6);
        }
        if ($coyymm && ! eregi($this->prefix.'[0-9][0-9][0-9][0-9]',$coyymm))
        {
            $this->error='Une commande commençant par $coyymm existe en base et est incompatible avec cette numérotation. Supprimer la ou renommer la pour activer ce module.';
            return false;    
        }

        return true;
    }

	/**		\brief      Return next value
	*      	\param      objsoc      Objet third party
	*		\param		commande	Object order
	*      	\return     string      Value if OK, 0 if KO
	*/
    function getNextValue($objsoc=0,$commande)
    {
        global $db;

        // D'abord on récupère la valeur max (réponse immédiate car champ indéxé)
        $coyymm='';
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande";
		$sql.= " WHERE ref like '".$this->prefix."%'";

        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $coyymm = substr($row[0],0,6);
        }
        else
        {
        	dolibarr_syslog("mod_commande_marbre::getNextValue sql=".$sql);
        	return -1;
        }
    
        // Si champ respectant le modèle a été trouvée
        if (eregi('^'.$this->prefix.'[0-9][0-9][0-9][0-9]',$coyymm))
        {
            // Recherche rapide car restreint par un like sur champ indexé
            $posindice=8;
            $sql = "SELECT MAX(0+SUBSTRING(ref,$posindice))";
            $sql.= " FROM ".MAIN_DB_PREFIX."commande";
            $sql.= " WHERE ref like '${coyymm}%'";
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
        
        dolibarr_syslog("mod_commande_marbre::getNextValue return ".$this->prefix."$yymm-$num");
        return $this->prefix."$yymm-$num";
    }


    /**     \brief      Renvoie la référence de commande suivante non utilisée
     *      \param      objsoc      Objet société
     *      \return     string      Texte descripif
     */
    function commande_get_num($objsoc=0)
    {
        return $this->getNextValue($objsoc);
    }
}

?>
