<?php
/* Copyright (C) 2005      Christophe
 * Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/boxes/box_comptes.php
 *      \ingroup    banque
 *      \brief      Module to generate box for bank accounts
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';


/**
 * Class to manage the box to show bank accounts
 */
class box_comptes extends ModeleBoxes
{
	public $boxcode  = "currentaccounts";
	public $boximg   = "bank_account";
	public $boxlabel = "BoxCurrentAccounts";
	public $depends  = array("banque"); // Box active if module banque active

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $param;
	public $enabled = 1;

	public $info_box_head = array();
	public $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $conf, $user;

		$this->db = $db;

		// disable module for such cases
		$listofmodulesforexternal = explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL'));
		if (!in_array('banque', $listofmodulesforexternal) && !empty($user->socid)) {
			$this->enabled = 0; // disabled for external users
		}

		$this->hidden = empty($user->rights->banque->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;

		$this->max = $max;

		$this->info_box_head = array('text' => $langs->trans("BoxTitleCurrentAccounts"));

		if ($user->hasRight('banque', 'lire')) {
			$sql = "SELECT b.rowid, b.ref, b.label, b.bank,b.number, b.courant, b.clos, b.rappro, b.url";
			$sql .= ", b.code_banque, b.code_guichet, b.cle_rib, b.bic, b.iban_prefix as iban";
			$sql .= ", b.domiciliation, b.proprio, b.owner_address";
			$sql .= ", b.account_number, b.currency_code";
			$sql .= ", b.min_allowed, b.min_desired, comment";
			$sql .= ', b.fk_accountancy_journal';
			$sql .= ', aj.code as accountancy_journal';
			$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as b";
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'accounting_journal as aj ON aj.rowid=b.fk_accountancy_journal';
			$sql .= " WHERE b.entity = ".$conf->entity;
			$sql .= " AND clos = 0";
			//$sql.= " AND courant = 1";
			$sql .= " ORDER BY label";
			$sql .= $this->db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				$solde_total = array();

				$account_static = new Account($this->db);
				while ($line < $num) {
					$objp = $this->db->fetch_object($result);

					$account_static->id = $objp->rowid;
					$account_static->ref = $objp->ref;
					$account_static->label = $objp->label;
					$account_static->number = $objp->number;
					$account_static->account_number = $objp->account_number;
					$account_static->currency_code = $objp->currency_code;
					$account_static->accountancy_journal = $objp->accountancy_journal;
					$solde = $account_static->solde(0);

					if (!array_key_exists($objp->currency_code, $solde_total)) {
						$solde_total[$objp->currency_code] = $solde;
					} else {
						$solde_total[$objp->currency_code] += $solde;
					}


					$this->info_box_contents[$line][] = array(
						'td' => '',
						'text' => $account_static->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => '',
						'text' => $objp->number,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?id='.$account_static->id.'">'
									.price($solde, 0, $langs, 1, -1, -1, $objp->currency_code)
									.'</a>',
						'asis' => 1,
					);

					$line++;
				}

				// Total
				foreach ($solde_total as $key => $solde) {
					$this->info_box_contents[$line][] = array(
						'tr' => 'class="liste_total"',
						'td' => 'class="liste_total left"',
						'text' => $langs->trans('Total').' '.$key,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => '&nbsp;'
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total nowraponall right amount"',
						'text' => '<span class="amount">'.price($solde, 0, $langs, 0, -1, -1, $key).'</span>'
					);
					$line++;
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength'=>500,
					'text' => ($this->db->error().' sql='.$sql),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
			);
		}
	}

	/**
	 *  Method to show box
	 *
	 *  @param  array   $head       Array with properties of box title
	 *  @param  array   $contents   Array with properties of box lines
	 *  @param  int     $nooutput   No print, only return string
	 *  @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
