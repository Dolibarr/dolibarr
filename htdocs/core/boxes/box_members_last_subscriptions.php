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
 *	\file       htdocs/core/boxes/box_members_last_subscriptions.php
 *	\ingroup    adherent
 *	\brief      Module to show box of last members subscriptions
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last members subscriptions
 */
class box_members_last_subscriptions extends ModeleBoxes
{
	public $boxcode  = "box_members_last_subscriptions";
	public $boximg   = "object_user";
	public $boxlabel = "BoxLastMembersSubscriptions";
	public $depends  = array("adherent");

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
		if (!in_array('adherent', $listofmodulesforexternal) && !empty($user->socid)) {
			$this->enabled = 0; // disabled for external users
		}

		$this->hidden = !(isModEnabled('adherent') && $user->hasRight('adherent', 'lire'));
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
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
		$staticmember = new Adherent($this->db);
		$statictype = new AdherentType($this->db);
		$subscriptionstatic = new Subscription($this->db);

		$this->info_box_head = array('text' => $langs->trans("LastSubscriptionsModified", $max));

		if ($user->hasRight('adherent', 'lire')) {
			$sql = "SELECT a.rowid, a.statut as status, a.lastname, a.firstname, a.societe as company, a.fk_soc,";
			$sql .= " a.gender, a.email, a.photo, a.morphy,";
			$sql .= " a.datefin as date_end_subscription,";
			$sql .= " ta.rowid as typeid, ta.libelle as label, ta.subscription as need_subscription,";
			$sql .= " c.rowid as cid, c.tms as datem, c.datec as datec, c.dateadh as date_start, c.datef as date_end, c.subscription";
			$sql .= " FROM ".MAIN_DB_PREFIX."adherent as a, ".MAIN_DB_PREFIX."adherent_type as ta, ".MAIN_DB_PREFIX."subscription as c";
			$sql .= " WHERE a.entity IN (".getEntity('adherent').")";
			$sql .= " AND a.fk_adherent_type = ta.rowid";
			$sql .= " AND c.fk_adherent = a.rowid";
			$sql .= $this->db->order("c.tms", "DESC");
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				while ($line < $num) {
					$obj = $this->db->fetch_object($result);
					$staticmember->id = $obj->rowid;
					$staticmember->ref = $obj->rowid;
					$staticmember->lastname = $obj->lastname;
					$staticmember->firstname = $obj->firstname;
					$staticmember->gender = $obj->gender;
					$staticmember->email = $obj->email;
					$staticmember->photo = $obj->photo;
					$staticmember->morphy = $obj->morphy;
					$staticmember->statut = $obj->status;
					$staticmember->need_subscription = $obj->need_subscription;
					$staticmember->datefin = $this->db->jdate($obj->date_end_subscription);
					if (!empty($obj->fk_soc)) {
						$staticmember->fk_soc = $obj->fk_soc;
						$staticmember->fetch_thirdparty();
						$staticmember->name = $staticmember->thirdparty->name;
					} else {
						$staticmember->name = $obj->company;
					}

					$subscriptionstatic->id = $obj->cid;
					$subscriptionstatic->ref = $obj->cid;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax100 maxwidth100onsmartphone"',
						'text' => $subscriptionstatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $staticmember->getNomUrl(-1, 32, 'card'),
						'asis' => 1,
					);

					$daterange = get_date_range($this->db->jdate($obj->date_start), $this->db->jdate($obj->date_end));
					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone" title="'.dol_escape_htmltag($daterange).'"',
						'text' => $daterange,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount" width="18"',
						'text' => price($obj->subscription),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right tdoverflowmax150 maxwidth150onsmartphone" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($obj->datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($this->db->jdate($obj->datem ? $obj->datem : $obj->datec), 'dayhour', 'tzuserrel'),
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text'=> '<span class="opacitymedium">'.$langs->trans("NoRecordedMembers").'</span>',
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
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
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
