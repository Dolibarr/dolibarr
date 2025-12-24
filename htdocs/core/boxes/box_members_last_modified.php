<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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

	public $enabled = 1;

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

		$this->hidden = !(isModEnabled('member') && $user->hasRight('adherent', 'lire'));
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

		if ($user->hasRight('adherent', 'lire')) {
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherentstats.class.php';
			$stats = new AdherentStats($this->db, $user->socid, $user->id);
			// Show array
			$listOfMembers = $stats->getLastModifiedMembers($max);
			$line = 0;
			$num = count($listOfMembers);
			foreach ($listOfMembers as $data) {
				$memberstatic->lastname = $data['lastname'];
				$memberstatic->firstname = $data['firstname'];
				$memberstatic->id = $data['id'];
				$memberstatic->ref = $data['ref'];
				$memberstatic->photo = $data['photo'];
				$memberstatic->gender = $data['gender'];
				$memberstatic->email = $data['email'];
				$memberstatic->morphy = $data['morphy'];
				$memberstatic->company = $data['company'];
				$memberstatic->status = $data['status'];
				$memberstatic->date_creation = $data['datec'];
				$memberstatic->date_modification = $data['datem'];
				$memberstatic->need_subscription = (int) $data['need_subscription'];
				$memberstatic->datefin = $data['date_end_subscription'];
				if (!empty($data['fk_soc'])) {
					$memberstatic->socid = $data['fk_soc'];
					$memberstatic->fetch_thirdparty();
					$memberstatic->name = $memberstatic->thirdparty->name;
				} else {
					$memberstatic->name = $data['company'];
				}
				$statictype->id = $data['typeid'];
				$statictype->label = $data['label'];
				$statictype->subscription = $data['subscription'];

				$this->info_box_contents[$line][] = array(
					'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
					'text' => $memberstatic->getNomUrl(-1),
					'asis' => 1,
				);

				$this->info_box_contents[$line][] = array(
					'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
					'text' => $memberstatic->company,
				);

				$this->info_box_contents[$line][] = array(
					'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
					'text' => $statictype->getNomUrl(1, 32),
					'asis' => 1,
				);

				$this->info_box_contents[$line][] = array(
					'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($data['datem'], 'dayhour', 'tzuserrel')).'"',
					'text' => dol_print_date($data['datem'], "day", 'tzuserrel'),
				);

				$this->info_box_contents[$line][] = array(
					'td' => 'class="right" width="18"',
					'text' => $memberstatic->LibStatut($data['status'], $data['need_subscription'], $data['date_end_subscription'], 3),
				);
				$line ++;
			}

			if ($num == 0) {
				$this->info_box_contents[$line][0] = array(
					'td' => 'class="center"',
					'text' => $langs->trans("NoRecordedCustomers"),
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
