<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2020 Frederic France      <frederic.france@netlogic.fr>
 * Copyright (C) 2021      Waël Almoman         <info@almoman.com>
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
 *	\file       htdocs/core/boxes/box_members_by_type.php
 *	\ingroup    adherent
 *	\brief      Module to show box of members
 */

include_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last modofied members
 */
class box_members_by_type extends ModeleBoxes
{
	public $boxcode = "box_members_by_type";
	public $boximg = "object_user";
	public $boxlabel = "BoxTitleMembersByType";
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
		$staticmember = new Adherent($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleMembersByType", $max));

		if ($user->rights->adherent->lire) {
			$MembersToValidate = array();
			$MembersValidated = array();
			$MembersUpToDate = array();
			$MembersExcluded = array();
			$MembersResiliated = array();

			$SumToValidate = 0;
			$SumValidated = 0;
			$SumUpToDate = 0;
			$SumResiliated = 0;
			$SumExcluded = 0;

			$AdherentType = array();

			// Type of membership
			$sql = "SELECT t.rowid, t.libelle as label, t.subscription,";
			$sql .= " d.statut, count(d.rowid) as somme";
			$sql .= " FROM " . MAIN_DB_PREFIX . "adherent_type as t";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "adherent as d";
			$sql .= " ON t.rowid = d.fk_adherent_type";
			$sql .= " AND d.entity IN (" . getEntity('adherent') . ")";
			$sql .= " WHERE t.entity IN (" . getEntity('member_type') . ")";
			$sql .= " GROUP BY t.rowid, t.libelle, t.subscription, d.statut";

			dol_syslog("box_members_by_type::select nb of members per type", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num) {
					$objp = $this->db->fetch_object($result);

					$adhtype = new AdherentType($this->db);
					$adhtype->id = $objp->rowid;
					$adhtype->subscription = $objp->subscription;
					$adhtype->label = $objp->label;
					$AdherentType[$objp->rowid] = $adhtype;

					if ($objp->statut == Adherent::STATUS_DRAFT) {
						$MembersToValidate[$objp->rowid] = $objp->somme;
					}
					if ($objp->statut == Adherent::STATUS_VALIDATED) {
						$MembersValidated[$objp->rowid] = $objp->somme;
					}
					if ($objp->statut == Adherent::STATUS_EXCLUDED) {
						$MembersExcluded[$objp->rowid] = $objp->somme;
					}
					if ($objp->statut == Adherent::STATUS_RESILIATED) {
						$MembersResiliated[$objp->rowid] = $objp->somme;
					}

					$i++;
				}
				$this->db->free($result);
				$now = dol_now();

				// Members up to date list
				// current rule: uptodate = the end date is in future whatever is type
				// old rule: uptodate = if type does not need payment, that end date is null, if type need payment that end date is in future)
				$sql = "SELECT count(*) as somme , d.fk_adherent_type";
				$sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d, " . MAIN_DB_PREFIX . "adherent_type as t";
				$sql .= " WHERE d.entity IN (" . getEntity('adherent') . ")";
				$sql .= " AND d.statut = 1 AND (d.datefin >= '" . $this->db->idate($now) . "' OR t.subscription = 0)";
				$sql .= " AND t.rowid = d.fk_adherent_type";
				$sql .= " GROUP BY d.fk_adherent_type";

				dol_syslog("index.php::select nb of uptodate members by type", LOG_DEBUG);
				$result = $this->db->query($sql);
				if ($result) {
					$num2 = $this->db->num_rows($result);
					$i = 0;
					while ($i < $num2) {
						$objp = $this->db->fetch_object($result);
						$MembersUpToDate[$objp->fk_adherent_type] = $objp->somme;
						$i++;
					}
					$this->db->free($result);
				}

				$line = 0;
				$this->info_box_contents[$line][] = array(
					'td' => 'class=""',
					'text' => '',
				);
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_DRAFT, 0, 0, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="15%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus
				);
				$labelstatus = $langs->trans("UpToDate");
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_VALIDATED, 1, dol_now() + 86400, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="15%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus,
				);
				$labelstatus = $langs->trans("OutOfDate");
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_VALIDATED, 1, dol_now() - 86400, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="15%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus
				);
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_EXCLUDED, 0, 0, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="15%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus
				);
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_RESILIATED, 0, 0, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="15%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus
				);
				$line++;
				foreach ($AdherentType as $key => $adhtype) {
					$SumToValidate += isset($MembersToValidate[$key]) ? $MembersToValidate[$key] : 0;
					$SumValidated += isset($MembersValidated[$key]) ? $MembersValidated[$key] - (isset($MembersUpToDate[$key]) ? $MembersUpToDate[$key] : 0) : 0;
					$SumUpToDate += isset($MembersUpToDate[$key]) ? $MembersUpToDate[$key] : 0;
					$SumExcluded += isset($MembersExcluded[$key]) ? $MembersExcluded [$key] : 0;
					$SumResiliated += isset($MembersResiliated[$key]) ? $MembersResiliated[$key] : 0;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $adhtype->getNomUrl(1, dol_size(32)),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($MembersToValidate[$key]) && $MembersToValidate[$key] > 0 ? $MembersToValidate[$key] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_DRAFT, 1, 0, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($MembersUpToDate[$key]) && $MembersUpToDate[$key] > 0 ? $MembersUpToDate[$key] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_VALIDATED, 1, $now, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($MembersValidated[$key]) && ($MembersValidated[$key] - (isset($MembersUpToDate[$key]) ? $MembersUpToDate[$key] : 0) > 0) ? $MembersValidated[$key] - (isset($MembersUpToDate[$key]) ? $MembersUpToDate[$key] : 0) : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_VALIDATED, 1, 1, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($MembersExcluded[$key]) && $MembersExcluded[$key] > 0 ? $MembersExcluded[$key] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_EXCLUDED, 1, $now, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($MembersResiliated[$key]) && $MembersResiliated[$key] > 0 ? $MembersResiliated[$key] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_RESILIATED, 1, 0, 3),
						'asis' => 1,
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => $langs->trans("NoRecordedMembersByType")
					);
				} else {
					$this->info_box_contents[$line][] = array(
						'tr' => 'class="liste_total"',
						'td' => 'class="liste_total"',
						'text' => $langs->trans("Total")
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $SumToValidate.' '.$staticmember->LibStatut(Adherent::STATUS_DRAFT, 1, 0, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $SumUpToDate.' '.$staticmember->LibStatut(Adherent::STATUS_VALIDATED, 1, $now, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $SumValidated.' '.$staticmember->LibStatut(Adherent::STATUS_VALIDATED, 1, 1, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $SumExcluded.' '.$staticmember->LibStatut(Adherent::STATUS_EXCLUDED, 1, 0, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $SumResiliated.' '.$staticmember->LibStatut(Adherent::STATUS_RESILIATED, 1, 0, 3),
						'asis' => 1
					);
				}
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->error() . ' sql=' . $sql)
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
