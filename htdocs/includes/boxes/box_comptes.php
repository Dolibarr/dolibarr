<?php
/* Copyright (C) 2005      Christophe
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *      \file       htdocs/includes/boxes/box_comptes.php
 *      \ingroup    banque
 *      \brief      Module de generation de l'affichage de la box comptes
 *		\version	$Id$
 */

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");
include_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");


class box_comptes extends ModeleBoxes {

	var $boxcode="currentaccounts";
	var $boximg="object_bill";
	var $boxlabel;
	var $depends = array("banque");     // Box active if module banque active

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *      \brief      Constructor for class
	 */
	function box_comptes()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel=$langs->trans('BoxCurrentAccounts');
	}

	/**
	 *      \brief      Load data into info_box_contents array to show array later.
	 *      \param      $max        Maximum number of records to load
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
			$sql.= " domiciliation, proprio, adresse_proprio,";
			$sql.= " account_number, currency_code,";
			$sql.= " min_allowed, min_desired, comment";
			$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
			$sql.= " WHERE entity = ".$conf->entity;
			$sql.= " AND clos = 0";
			$sql.= " AND courant = 1";
			$sql.= " ORDER BY label";
			$sql.= $db->plimit($max, 0);

			dol_syslog("Box_comptes::loadBox sql=".$sql);
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
					'text' => price($solde).' '.$langs->trans("Currency".$objp->currency_code)
					);

					$listofcurrencies[$objp->currency_code]=1;
					$i++;
				}

				// Total
				if (sizeof($listofcurrencies) < 1)
				{
					$this->info_box_contents[$i][0] = array('tr' => 'class="liste_total"', 'td' => 'align="right" colspan="3" class="liste_total"',
					'text' => $langs->trans('Total')
					);
					$totalamount=price($solde_total).' '.$langs->trans("Currency".$conf->monnaie);
					$this->info_box_contents[$i][1] = array('td' => 'align="right" class="liste_total"',
					'text' => $totalamount
					);
				}
			}
			else {
				dol_print_error($db);
			}
		}
		else {
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
			'text' => $langs->trans("ReadPermissionNotAllowed"));
		}

	}

	function showBox()
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
