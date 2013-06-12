<?php
/* Copyright (C) 2005      Christophe
 * Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 */

/**
 *      \file       htdocs/core/boxes/box_comptes.php
 *      \ingroup    banque
 *      \brief      Module to generate box for bank accounts
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';


/**
 * Class to manage the box to show last users
 */
class box_comptes extends ModeleBoxes
{
	var $boxcode="currentaccounts";
	var $boximg="object_bill";
	var $boxlabel="BoxCurrentAccounts";
	var $depends = array("banque");     // Box active if module banque active

	var $db;
	var $param;
	var $enabled = 1;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
     *  @param	string	$param		More parameters
	 */
	function __construct($db,$param='')
	{
		global $conf, $user;

		$this->db = $db;

		// disable module for such cases
		$listofmodulesforexternal=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);
		if (! in_array('banque',$listofmodulesforexternal) && ! empty($user->societe_id)) $this->enabled=0;	// disabled for external users
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

		$this->info_box_head = array('text' => $langs->trans("BoxTitleCurrentAccounts"));

		if ($user->rights->banque->lire)
		{
			$sql = "SELECT rowid, ref, label, bank, number, courant, clos, rappro, url,";
			$sql.= " code_banque, code_guichet, cle_rib, bic, iban_prefix,";
			$sql.= " domiciliation, proprio, owner_address,";
			$sql.= " account_number, currency_code,";
			$sql.= " min_allowed, min_desired, comment";
			$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
			$sql.= " WHERE entity = ".$conf->entity;
			$sql.= " AND clos = 0";
			$sql.= " AND courant = 1";
			$sql.= " ORDER BY label";
			$sql.= $db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox sql=".$sql);
			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);

				$i = 0;
				$solde_total = 0;

				$listofcurrencies=array();
				$account_static = new Account($db);
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);

					$account_static->id = $objp->rowid;
					$solde=$account_static->solde(0);

					$solde_total += $solde;

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
					'logo' => $this->boximg,
					'url' => DOL_URL_ROOT."/compta/bank/account.php?account=".$objp->rowid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' => $objp->label,
					'url' => DOL_URL_ROOT."/compta/bank/account.php?account=".$objp->rowid);

					$this->info_box_contents[$i][2] = array('td' => 'align="left"',
					'text' => $objp->number
					);

					$this->info_box_contents[$i][3] = array('td' => 'align="right"',
					'text' => price($solde, 1, $langs, 0, -1, 0, $objp->currency_code)
					);

					$listofcurrencies[$objp->currency_code]=1;
					$i++;
				}

				// Total
				if (count($listofcurrencies) <= 1)
				{
					$this->info_box_contents[$i][0] = array('tr' => 'class="liste_total"', 'td' => 'align="right" class="liste_total"',
					'text' => $langs->trans('Total')
					);
					$this->info_box_contents[$i][1] = array('td' => 'align="right" class="liste_total"',
					'text' => '&nbsp;'
					);
					$this->info_box_contents[$i][2] = array('td' => 'align="right" class="liste_total"',
					'text' => '&nbsp;'
					);
					$totalamount=price($solde_total).' '.$langs->trans("Currency".$conf->currency);
					$this->info_box_contents[$i][3] = array('td' => 'align="right" class="liste_total"',
					'text' => $totalamount
					);
				}

				$db->free($result);
			}
			else {
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}
		}
		else {
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
			'text' => $langs->trans("ReadPermissionNotAllowed"));
		}

	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
