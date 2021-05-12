<?php
/* Copyright (C) 2017 Maxime Kohlhaas <support@atm-consulting.fr>
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
 *  \file       htdocs/core/modules/expensereport/mod_expensereport_jade.php
 *  \ingroup    expensereport
<<<<<<< HEAD
 *  \brief      File of class to manage customer order numbering rules Jade
=======
 *  \brief      File of class to manage expensereport numbering rules Jade
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/expensereport/modules_expensereport.php';

/**
<<<<<<< HEAD
 *	Class to manage customer order numbering rules Jade
 */
class mod_expensereport_jade extends ModeleNumRefExpenseReport
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefix='ER';
	var $error='';
	var $nom='Jade';


    /**
     *  Return description of numbering module
     *
     *  @return     string      Text with description
     */
    function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
=======
 *	Class to manage expensereport numbering rules Jade
 */
class mod_expensereport_jade extends ModeleNumRefExpenseReport
{
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'

	public $prefix='ER';

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom='Jade';

	/**
	 * @var string model name
	 */
	public $name='Jade';


    /**
     *  Return description of numbering model
     *
     *  @return     string      Text with description
     */
    public function info()
    {
        global $langs;
        return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


	/**
<<<<<<< HEAD
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
	function getExample()
=======
	 *  Returns an example of numbering
	 *
	 *  @return     string      Example
	 */
    public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->prefix."0501-0001";
	}


	/**
<<<<<<< HEAD
	 *  Test si les numeros deje en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
=======
	 *  Test whether the numbers already in force in the base do not cause conflicts
	 *  that would prevent this numbering from working.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
    public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $conf,$langs,$db;

		$coyymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."expensereport";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
<<<<<<< HEAD
			if ($row) { $coyymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($coyymm && ! preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$coyymm))
=======
			if ($row) { $coyymm = substr($row[0], 0, 6); $max=$row[0]; }
		}
		if ($coyymm && ! preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  Object		$object		Object we need next value for
	 *  @return string      			Value if KO, <0 if KO
	 */
<<<<<<< HEAD
	function getNextValue($object)
=======
    public function getNextValue($object)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $db,$conf;

		// For backward compatibility and restore old behavior to get ref of expense report
		if ($conf->global->EXPENSEREPORT_USE_OLD_NUMBERING_RULE)
		{
			$fuser = null;
			if ($object->fk_user_author > 0)
			{
				$fuser=new User($db);
				$fuser->fetch($object->fk_user_author);
			}

			$expld_car = (empty($conf->global->NDF_EXPLODE_CHAR))?"-":$conf->global->NDF_EXPLODE_CHAR;
			$num_car = (empty($conf->global->NDF_NUM_CAR_REF))?"5":$conf->global->NDF_NUM_CAR_REF;

			$sql = 'SELECT MAX(de.ref_number_int) as max';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'expensereport de';

			$result = $db->query($sql);

			if($db->num_rows($result) > 0):
			$objp = $db->fetch_object($result);
			$newref = $objp->max;
			$newref++;
			while(strlen($newref) < $num_car):
				$newref = "0".$newref;
			endwhile;
			else:
				$newref = 1;
			while(strlen($newref) < $num_car):
				$newref = "0".$newref;
			endwhile;
			endif;

			$ref_number_int = ($newref+1)-1;
<<<<<<< HEAD
			$update_number_int = true;
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

			$user_author_infos = dolGetFirstLastname($fuser->firstname, $fuser->lastname);

			$prefix="ER";
			if (! empty($conf->global->EXPENSE_REPORT_PREFIX)) $prefix=$conf->global->EXPENSE_REPORT_PREFIX;
<<<<<<< HEAD
			$newref = str_replace(' ','_', $user_author_infos).$expld_car.$prefix.$newref.$expld_car.dol_print_date($object->date_debut,'%y%m%d');
=======
			$newref = str_replace(' ', '_', $user_author_infos).$expld_car.$prefix.$newref.$expld_car.dol_print_date($object->date_debut, '%y%m%d');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

			$sqlbis = 'UPDATE '.MAIN_DB_PREFIX.'expensereport SET ref_number_int = '.$ref_number_int.' WHERE rowid = '.$object->id;
			$resqlbis = $db->query($sqlbis);
			if (! $resqlbis)
			{
				dol_print_error($resqlbis);
				exit;
			}

			dol_syslog("mod_expensereport_jade::getNextValue return ".$newref);
			return $newref;
		}

<<<<<<< HEAD
		// D'abord on recupere la valeur max
=======
		// First we get the max value
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."expensereport";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			dol_syslog("mod_expensereport_jade::getNextValue", LOG_DEBUG);
			return 0;
		}

		$date=$object->date_valid;		// $object->date does not exists
		if (empty($date))
		{
			$this->error = 'Date valid not defined';
			return 0;
		}

<<<<<<< HEAD
		$yymm = strftime("%y%m",$date);

    	if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    	else $num = sprintf("%04s",$max+1);
=======
		$yymm = strftime("%y%m", $date);

    	if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    	else $num = sprintf("%04s", $max+1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		dol_syslog("mod_expensereport_jade::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}
}
