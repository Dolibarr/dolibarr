<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *   \file       htdocs/core/modules/livraison/mod_livraison_jade.php
 *   \ingroup    delivery
 *   \brief      Fichier contenant la classe du modele de numerotation de reference de bon de livraison Jade
 */

require_once(DOL_DOCUMENT_ROOT ."/core/modules/livraison/modules_livraison.php");


/**
 *  \class      mod_livraison_jade
 *  \brief      Classe du modele de numerotation de reference de bon de livraison Jade
 */

class mod_livraison_jade extends ModeleNumRefDeliveryOrder
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = "Jade";

    var $prefix='BL';


	/**
	 *     \brief      Renvoi la description du modele de numerotation
	 *     \return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
	}

	/**
	 *      \brief      Renvoi un exemple de numerotation
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
        global $langs,$conf;

        $langs->load("bills");

        // Check invoice num
        $fayymm=''; $max='';

        $posindice=8;
        $sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";   // This is standard SQL
        $sql.= " FROM ".MAIN_DB_PREFIX."livraison";
        $sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
        $sql.= " AND entity = ".$conf->entity;

        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) { $fayymm = substr($row[0],0,6); $max=$row[0]; }
        }
        if ($fayymm && ! preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$fayymm))
        {
            $langs->load("errors");
            $this->error=$langs->trans('ErrorNumRefModel',$max);
            return false;
        }

        return true;
    }

    /**
	 * 		\brief      Return next value
	 *    	\param      objsoc      Object third party
	 *    	\param      delivery	Object delivery
	 *    	\return     string      Value if OK, 0 if KO
	 */
    function getNextValue($objsoc=0,$delivery='')
    {
        global $db,$conf;

        // D'abord on recupere la valeur max
        $posindice=8;
        $sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";   // This is standard SQL
        $sql.= " FROM ".MAIN_DB_PREFIX."livraison";
        $sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
        $sql.= " AND entity = ".$conf->entity;

        $resql=$db->query($sql);
        dol_syslog("mod_livraison_jade::getNextValue sql=".$sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = intval($obj->max);
            else $max=0;
        }
        else
        {
            dol_syslog("mod_livraison_jade::getNextValue sql=".$sql, LOG_ERR);
            return -1;
        }

        $date=$delivery->date_delivery;
        if (empty($date)) $date=dol_now();
        $yymm = strftime("%y%m",$date);
        $num = sprintf("%04s",$max+1);

        dol_syslog("mod_livraison_jade::getNextValue return ".$this->prefix.$yymm."-".$num);
        return $this->prefix.$yymm."-".$num;
    }


    /**
     * 		\brief      Renvoie la reference de commande suivante non utilisee
     *      \param      objsoc      Objet societe
     *      \param      livraison	Objet livraison
     *      \return     string      Texte descripif
     */
    function livraison_get_num($objsoc=0,$livraison='')
    {
        return $this->getNextValue($objsoc,$livraison);
    }
}
?>
