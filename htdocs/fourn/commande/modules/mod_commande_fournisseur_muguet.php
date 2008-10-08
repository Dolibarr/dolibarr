<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
     	\file       htdocs/fourn/commande/modules/mod_commande_fournisseur_muguet.php
		\ingroup    commande
		\brief      Fichier contenant la classe du mod�le de num�rotation de r�f�rence de commande fournisseur Muguet
		\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/fourn/commande/modules/modules_commandefournisseur.php");


/**	    \class      mod_commande_fournisseur_muguet
		\brief      Classe du mod�le de num�rotation de r�f�rence de commande fournisseur Muguet
*/
class mod_commande_fournisseur_muguet extends ModeleNumRefSuppliersOrders
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Muguet';
	var $prefix='CF';
    
    
    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
		return "Renvoie le numero sous la forme ".$this->prefix."yymm-nnnn ou yy est l'annee, mm le mois et nnnn un compteur sequentiel sans rupture et sans remise a 0";
    }


    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        return $this->prefix."0501-0001";
    }


    /**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numerotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        $coyymm='';
        
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $coyymm = substr($row[0],0,6);
        }
        if (! $coyymm || eregi($this->prefix.'[0-9][0-9][0-9][0-9]',$coyymm))
        {
            return true;
        }
        else
        {
            $this->error='Une commande commencant par $coyymm existe en base et est incompatible avec cette numerotation. Supprimer la ou renommer la pour activer ce module.';
            return false;    
        }
    }

    /**     \brief      Return next value
	*      	\param      objsoc      Object third party
	*      	\param      object		Object
	*       \return     string      Valeur
    */
    function getNextValue($objsoc=0,$object='')
    {
        global $db;

        // D'abord on recupere la valeur max (reponse immediate car champ indexe)
        $posindice=8;
        $sql = "SELECT MAX(0+SUBSTRING(ref,".$posindice.")) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur";
		$sql.= " WHERE ref like '".$this->prefix."%'";

		$resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = $obj->max;
            else $max=0;
        }
    
		//$date=time();
        $date=$object->date_commande;
        $yymm = strftime("%y%m",$date);
        $num = sprintf("%04s",$max+1);
        
        return $this->prefix.$yymm."-".$num;
    }


    /**     \brief      Renvoie la r�f�rence de commande suivante non utilis�e
	*      	\param      objsoc      Object third party
	*      	\param      object		Object
    *      	\return     string      Texte descripif
    */
    function commande_get_num($objsoc=0,$object='')
    {
        return $this->getNextValue($objsoc,$object);
    }
}

?>
