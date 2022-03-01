<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2021 Frederic France      <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/boxes/box_members_subscriptions_by_year.php
 *	\ingroup    adherent
 *	\brief      Module to show box of members
 */

include_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last modofied members
 */
class box_members_subscriptions_by_year extends ModeleBoxes
{
	public $boxcode = "box_members_subscriptions_by_year";
	public $boximg = "object_user";
	public $boxlabel = "BoxTitleMembersSubscriptionsByYear";
	public $depends = array("adherent");

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
		$listofmodulesforexternal = explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL);
		if (!in_array('adherent', $listofmodulesforexternal) && !empty($user->socid)) {
			$this->enabled = 0; // disabled for external users
		}

		$this->hidden = !(!empty($conf->adherent->enabled) && $user->rights->adherent->lire);
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
		$langs->load("boxes");

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
		require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent_type.class.php';
		$memberstatic = new Adherent($this->db);
		$statictype = new AdherentType($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleMembersSubscriptionsByYear", $max));

		if ($user->rights->adherent->lire) {
			$num = 0;
			$line = 0;
			// List of subscription by year
			$Total = array();
			$Number = array();
			$tot = 0;
			$numb = 0;

			$sql = "SELECT c.subscription, c.dateadh as dateh";
			$sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d, " . MAIN_DB_PREFIX . "subscription as c";
			$sql .= " WHERE d.entity IN (" . getEntity('adherent') . ")";
			$sql .= " AND d.rowid = c.fk_adherent";


			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num) {
					$objp = $this->db->fetch_object($result);
					$year = dol_print_date($this->db->jdate($objp->dateh), "%Y");
					$Total[$year] = (isset($Total[$year]) ? $Total[$year] : 0) + $objp->subscription;
					$Number[$year] = (isset($Number[$year]) ? $Number[$year] : 0) + 1;
					$tot += $objp->subscription;
					$numb += 1;
					$i++;
				}


				$line = 0;
				$this->info_box_contents[$line][] = array(
					'td' => 'class=""',
					'text' => $langs->trans("Year"),
				);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right"',
					'text' => $langs->trans("Subscriptions"),
				);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right"',
					'text' => $langs->trans("AmountTotal"),
				);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right"',
					'text' => $langs->trans("AmountAverage"),
				);

				$line++;

				krsort($Total);
				$i = 0;
				foreach ($Total as $key => $value) {
					if ($i >= 8) {
						// print '<tr class="oddeven">';
						// print "<td>...</td>";
						// print "<td class=\"right\"></td>";
						// print "<td class=\"right\"></td>";
						// print "<td class=\"right\"></td>";
						// print "</tr>\n";
						$this->info_box_contents[$line][] = array(
							'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
							'text' => '...',
						);
						$this->info_box_contents[$line][] = array(
							'td' => 'class="right"',
							'text' => '',
						);
						$this->info_box_contents[$line][] = array(
							'td' => 'class="right"',
							'text' => '',
						);
						$this->info_box_contents[$line][] = array(
							'td' => 'class="right"',
							'text' => '',
						);
						$line++;
						break;
					}
					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => '<a href="./subscription/list.php?date_select='.$key.'">'.$key.'</a>',
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => $Number[$key],
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($value),
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price(price2num($value / $Number[$key], 'MT')),
					);
					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => $langs->trans("NoRecordedMembers"),
					);
				} else {
					$this->info_box_contents[$line][] = array(
						'tr' => 'class="liste_total"',
						'td' => 'class="liste_total"',
						'text' => $langs->trans("Total"),
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $numb,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total nowraponall right amount"',
						'text' => price($tot),
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total nowraponall right amount"',
						'text' => price(price2num($numb > 0 ? ($tot / $numb) : 0, 'MT')),
					);
				}
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->error() . ' sql=' . $sql),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover opacitymedium left"',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
