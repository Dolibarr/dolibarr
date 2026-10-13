<?php
/* Copyright (C) 2026	Dolibarr contributors
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
 * \file       htdocs/core/boxes/box_expensereports_to_approve.php
 * \ingroup    expensereport
 * \brief      Module that generates the box to show expense reports waiting for approval
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show expense reports waiting for approval
 */
class box_expensereports_to_approve extends ModeleBoxes
{
	public $boxcode  = "expensereportstoapprove";
	public $boximg   = "object_trip";
	public $boxlabel = "BoxExpenseReportsToApprove";
	public $depends  = array("expensereport");

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)  // @phpstan-ignore constructor.unusedParameter
	{
		global $user;

		$this->db = $db;

		$this->hidden = !($user->hasRight('expensereport', 'lire') && $user->hasRight('expensereport', 'approve'));
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs;
		$langs->loadLangs(array("boxes", "trips"));

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
		$expensereportstatic = new ExpenseReport($this->db);
		include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$userstatic = new User($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleExpenseReportsToApprove", $max));

		if ($user->hasRight('expensereport', 'lire') && $user->hasRight('expensereport', 'approve')) {
			$childids = $user->getAllChildIds(1);

			$sql = "SELECT d.rowid, d.ref, d.date_debut, d.date_fin, d.total_ttc, d.fk_statut as status";
			$sql .= ", u.rowid as user_id, u.lastname, u.firstname, u.login, u.photo, u.statut as user_status, u.entity as user_entity";
			$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as d";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON d.fk_user_author = u.rowid";
			$sql .= " WHERE d.fk_statut = ".ExpenseReport::STATUS_VALIDATED;
			$sql .= " AND d.entity IN (".getEntity('expensereport').")";
			if (!$user->hasRight('expensereport', 'readall')) {
				$sql .= " AND (d.fk_user_author IN (".$this->db->sanitize(implode(',', $childids)).")";
				$sql .= " OR d.fk_user_validator = ".((int) $user->id).")";
			}
			$sql .= " ORDER BY d.date_valid ASC";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				while ($line < $num) {
					$objp = $this->db->fetch_object($result);

					$expensereportstatic->id = $objp->rowid;
					$expensereportstatic->ref = $objp->ref;
					$expensereportstatic->statut = $objp->status;
					$expensereportstatic->status = $objp->status;

					$userstatic->id = $objp->user_id;
					$userstatic->lastname = $objp->lastname;
					$userstatic->firstname = $objp->firstname;
					$userstatic->login = $objp->login;
					$userstatic->photo = $objp->photo;
					$userstatic->statut = $objp->user_status;
					$userstatic->status = $objp->user_status;
					$userstatic->entity = $objp->user_entity;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $expensereportstatic->getNomUrl(1),
						'asis' => 1
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $userstatic->getNomUrl(-1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right"',
						'text' => dol_print_date($this->db->jdate($objp->date_debut), 'day').' - '.dol_print_date($this->db->jdate($objp->date_fin), 'day'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($objp->total_ttc),
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][] = array(
						'td' => 'class="center"',
						'text' => '<span class="opacitymedium">'.$langs->trans("NoExpenseReportToApprove").'</span>',
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->error().' sql='.$sql),
				);
			}
		} else {
			$this->info_box_contents[0][] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
			);
		}
	}

	/**
	 *	Method to show box.  Called when the box needs to be displayed.
	 *
	 *	@param	?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:?string,picto?:string,nbcol?:int,limit?:int,subclass?:string,graph?:int<0,1>,target?:string}>   $head       Array with properties of box title
	 *	@param	?array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:int,asis?:int<0,1>}>   $contents   Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
