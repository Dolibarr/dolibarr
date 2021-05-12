<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *    	\file       htdocs/core/modules/supplier_order/mod_commande_fournisseur_muguet.php
 *		\ingroup    commande
 *		\brief      Fichier contenant la classe du modele de numerotation de reference de commande fournisseur Muguet
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/supplier_order/modules_commandefournisseur.php';


/**
 *	Classe du modele de numerotation de reference de commande fournisseur Muguet
 */
class mod_commande_fournisseur_muguet extends ModeleNumRefSuppliersOrders
{
<<<<<<< HEAD
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Muguet';
	var $prefix='CF';
=======
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'

	/**
     * @var string Error code (or message)
     */
    public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see name
	 */
	public $nom='Muguet';

	/**
	 * @var string model name
	 */
	public $name='Muguet';

	public $prefix='CF';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 * Constructor
	 */
<<<<<<< HEAD
	function __construct()
=======
	public function __construct()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    global $conf;

	    if ((float) $conf->global->MAIN_VERSION_LAST_INSTALL >= 5.0) $this->prefix = 'PO';   // We use correct standard code "PO = Purchase Order"
	}

    /**
     * 	Return description of numbering module
     *
     *  @return     string      Text with description
     */
<<<<<<< HEAD
    function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
=======
    public function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


    /**
     * 	Renvoi un exemple de numerotation
     *
     *  @return     string      Example
     */
<<<<<<< HEAD
    function getExample()
=======
    public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        return $this->prefix."0501-0001";
    }


    /**
     * 	Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *  de conflits qui empechera cette numerotation de fonctionner.
     *
     *  @return     boolean     false si conflit, true si ok
     */
<<<<<<< HEAD
    function canBeActivated()
=======
    public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
    	global $conf,$langs,$db;

        $coyymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
        $sql.= " AND entity = ".$conf->entity;
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
<<<<<<< HEAD
            if ($row) { $coyymm = substr($row[0],0,6); $max=$row[0]; }
        }
        if (! $coyymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$coyymm))
=======
            if ($row) { $coyymm = substr($row[0], 0, 6); $max=$row[0]; }
        }
        if (! $coyymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        {
            return true;
        }
        else
        {
			$langs->load("errors");
<<<<<<< HEAD
			$this->error=$langs->trans('ErrorNumRefModel',$max);
=======
			$this->error=$langs->trans('ErrorNumRefModel', $max);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            return false;
        }
    }

    /**
     * 	Return next value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 *  @param  Object		$object		Object
	 *  @return string      			Value if OK, 0 if KO
     */
<<<<<<< HEAD
    function getNextValue($objsoc=0,$object='')
=======
    public function getNextValue($objsoc = 0, $object = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $db,$conf;

        // D'abord on recupere la valeur max
        $posindice=8;
        $sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
        $sql.= " AND entity = ".$conf->entity;

        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = intval($obj->max);
            else $max=0;
        }

		//$date=time();
        $date=$object->date_commande;   // Not always defined
        if (empty($date)) $date=$object->date;  // Creation date is order date for suppliers orders
<<<<<<< HEAD
        $yymm = strftime("%y%m",$date);

        if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
        else $num = sprintf("%04s",$max+1);
=======
        $yymm = strftime("%y%m", $date);

        if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
        else $num = sprintf("%04s", $max+1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        return $this->prefix.$yymm."-".$num;
    }


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * 	Renvoie la reference de commande suivante non utilisee
     *
	 *  @param	Societe		$objsoc     Object third party
	 *  @param  Object	    $object		Object
     *  @return string      			Texte descripif
     */
<<<<<<< HEAD
    function commande_get_num($objsoc=0,$object='')
    {
        return $this->getNextValue($objsoc,$object);
    }
}

=======
    public function commande_get_num($objsoc = 0, $object = '')
    {
        // phpcs:enable
        return $this->getNextValue($objsoc, $object);
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
