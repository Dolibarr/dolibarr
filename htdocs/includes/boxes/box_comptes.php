<?php
/* Copyright (C) 2005 Christophe
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/includes/boxes/box_comptes.php
        \ingroup    banque
        \brief      Module de génération de l'affichage de la box comptes
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_comptes extends ModeleBoxes {

		var $boxcode="currentaccounts";
		var $boximg="object_bill";
		var $boxlabel;
 		var $depends = array("banque");     // Box active si module banque actif

		var $info_box_head = array();
		var $info_box_contents = array();

		/**
		*      \brief      Constructeur de la classe
		*/
		function box_comptes()
		{
			global $langs;
			$langs->load("boxes");

			$this->boxlabel=$langs->trans('BoxCurrentAccounts');
		}

		/**
		*      \brief      Charge les données en mémoire pour affichage ultérieur
		*      \param      $max        Nombre maximum d'enregistrements à charger
		*/
		function loadBox($max=5)
		{
			global $user, $langs, $db;
			$langs->load("boxes");

			$this->info_box_head = array('text' => $langs->trans("BoxTitleCurrentAccounts"));

			if ($user->rights->banque->lire)
			{
				$sql  = "SELECT rowid, label, bank, number";
				$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
				$sql .= " WHERE clos = 0 AND courant = 1";
				$sql .= " ORDER BY label";
				$sql .= $db->plimit($max, 0);

				$result = $db->query($sql);

				if ($result)
				{
					$num = $db->num_rows($result);

					$i = 0;
					$solde_total = 0;

					while ($i < $num)
					{
						$objp = $db->fetch_object($result);
						$acc = new Account($db);
						$acc->fetch($objp->rowid);
						$solde_total += $acc->solde();

						$this->info_box_contents[$i][0] = array('align' => 'left',
						'logo' => $this->boximg,
						'text' => stripslashes($objp->label),
						'url' => DOL_URL_ROOT."/compta/bank/account.php?account=".$objp->rowid);

						$this->info_box_contents[$i][1] = array('align' => 'left',
						'text' => stripslashes($objp->bank)
						);

						$this->info_box_contents[$i][2] = array('align' => 'left',
						'text' => stripslashes($objp->number)
						);

						$this->info_box_contents[$i][3] = array('align' => 'right',
						'text' => price( $acc->solde() )
						);

						$i++;
					}

                    // Total
					$this->info_box_contents[$i][-1] = array('class' => 'liste_total');
					
					$this->info_box_contents[$i][0] = array('align' => 'right',
					'colspan' => '4',
					'class' => 'liste_total',
					'text' => $langs->trans('Total')
					);

					$this->info_box_contents[$i][1] = array('align' => 'right',
					'class' => 'liste_total',
					'text' => price($solde_total)
					);

				}
				else {
					dolibarr_print_error($db);
				}
			}
			else {
				$this->info_box_contents[0][0] = array('align' => 'left',
				'text' => $langs->trans("ReadPermissionNotAllowed"));
			}

		}

		function showBox()
		{
			parent::showBox($this->info_box_head, $this->info_box_contents);
		}

}

?>
