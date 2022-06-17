<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2020 Frederic France      <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/boxes/box_members_last_modified.php
 *	\ingroup    adherent
 *	\brief      Module to show box of members
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last modified members
 */
class box_members_last_modified extends ModeleBoxes
{
	public $boxcode = "box_members_last_modified";
	public $boximg = "object_user";
	public $boxlabel = "BoxLastModifiedMembers";
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

		include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
		$memberstatic = new Adherent($this->db);
		$statictype = new AdherentType($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedMembers", $max));

		if ($user->rights->adherent->lire) {
			$sql = "SELECT a.rowid, a.ref, a.lastname, a.firstname, a.societe as company, a.fk_soc,";
			$sql .= " a.datec, a.tms as datem, a.statut as status, a.datefin as date_end_subscription,";
			$sql .= ' a.photo, a.email, a.gender, a.morphy,';
			$sql .= " t.rowid as typeid, t.subscription, t.libelle as label";
			$sql .= " FROM ".MAIN_DB_PREFIX."adherent as a, ".MAIN_DB_PREFIX."adherent_type as t";
			$sql .= " WHERE a.entity IN (".getEntity('member').")";
			$sql .= " AND a.fk_adherent_type = t.rowid";
			$sql .= " ORDER BY a.tms DESC";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$datec = $this->db->jdate($objp->datec);
					$datem = $this->db->jdate($objp->datem);

					$memberstatic->lastname = $objp->lastname;
					$memberstatic->firstname = $objp->firstname;
					$memberstatic->id = $objp->rowid;
					$memberstatic->ref = $objp->ref;
					$memberstatic->photo = $objp->photo;
					$memberstatic->gender = $objp->gender;
					$memberstatic->email = $objp->email;
					$memberstatic->morphy = $objp->morphy;
					$memberstatic->company = $objp->company;
					$memberstatic->statut = $objp->status;
					$memberstatic->date_creation = $datec;
					$memberstatic->date_modification = $datem;
					$memberstatic->need_subscription = $objp->subscription;
					$memberstatic->datefin = $this->db->jdate($objp->date_end_subscription);
					if (!empty($objp->fk_soc)) {
						$memberstatic->socid = $objp->fk_soc;
						$memberstatic->fetch_thirdparty();
						$memberstatic->name = $memberstatic->thirdparty->name;
					} else {
						$memberstatic->name = $objp->company;
					}
					$statictype->id = $objp->typeid;
					$statictype->label = $objp->label;
					$statictype->subscription = $objp->subscription;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $memberstatic->getNomUrl(-1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' =>$memberstatic->company,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $statictype->getNomUrl(1, 32),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datem, "day", 'tzuserrel'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $memberstatic->LibStatut($objp->status, $objp->subscription, $this->db->jdate($objp->date_end_subscription), 3),
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text'=>$langs->trans("NoRecordedCustomers"),
					);
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
