<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2021-2023  Waël Almoman            <info@almoman.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
		global $user, $langs;
		$langs->loadLangs(array("boxes", "members"));

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
		require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent_type.class.php';
		$staticmember = new Adherent($this->db);

		$now = dol_now();
		$year = idate('Y');
		$numberyears = getDolGlobalInt("MAIN_NB_OF_YEAR_IN_MEMBERSHIP_WIDGET_GRAPH");

		$this->info_box_head = array('text' => $langs->trans("BoxTitleMembersByType").($numberyears ? ' ('.($year - $numberyears).' - '.$year.')' : ''));

		if ($user->hasRight('adherent', 'lire')) {
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherentstats.class.php';
			$stats = new AdherentStats($this->db, $user->socid, $user->id);
			// Show array
			$sumMembers = $stats->countMembersByTypeAndStatus($numberyears);
			if ($sumMembers) {
				$line = 0;
				$this->info_box_contents[$line][] = array(
					'td' => 'class=""',
					'text' => '',
				);
				// Members Status To Valid
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_DRAFT, 0, 0, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="10%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus
				);
				// Waiting for subscription
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_VALIDATED, 1, 0, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="10%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus,
				);
				// Up to date
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_VALIDATED, 1, $now + 86400, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="10%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus,
				);
				// Expired
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_VALIDATED, 1, $now - 86400, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="10%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus
				);
				// Excluded
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_EXCLUDED, 0, 0, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="10%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus
				);
				// Resiliated
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_RESILIATED, 0, 0, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="10%" title="'.dol_escape_htmltag($labelstatus).'"',
					'text' => $labelstatus
				);
				// Total row
				$labelstatus = $staticmember->LibStatut($staticmember::STATUS_RESILIATED, 0, 0, 1);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right tdoverflowmax100" width="10%" title="'.dol_escape_htmltag($langs->trans("Total")).'"',
					'text' => $langs->trans("Total")
				);
				$line++;
				$AdherentType = array();
				foreach ($sumMembers as $key => $data) {
					if ($key == 'total') {
						break;
					}
					$adhtype = new AdherentType($this->db);
					$adhtype->id = (int) $key;
					$adhtype->label = $data['label'];
					$AdherentType[$key] = $adhtype;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $adhtype->getNomUrl(1, dol_size(32)),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($data['members_draft']) && $data['members_draft'] > 0 ? $data['members_draft'] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_DRAFT, 1, 0, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($data['members_pending']) && $data['members_pending'] > 0 ? $data['members_pending'] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_VALIDATED, 1, 0, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($data['members_uptodate']) && $data['members_uptodate'] > 0 ? $data['members_uptodate'] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_VALIDATED, 0, 0, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($data['members_expired']) && $data['members_expired'] > 0 ? $data['members_expired'] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_VALIDATED, 1, 1, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($data['members_excluded']) && $data['members_excluded'] > 0 ? $data['members_excluded'] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_EXCLUDED, 1, $now, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($data['members_resiliated']) && $data['members_resiliated'] > 0 ? $data['members_resiliated'] : '') . ' ' . $staticmember->LibStatut(Adherent::STATUS_RESILIATED, 1, 0, 3),
						'asis' => 1,
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => (isset($data['total_adhtype']) && $data['total_adhtype'] > 0 ? $data['total_adhtype'] : ''),
						'asis' => 1,
					);
					$line++;
				}

				if (count($sumMembers) == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'colspan="7" class="center"',
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
						'text' => $sumMembers['total']['members_draft'].' '.$staticmember->LibStatut(Adherent::STATUS_DRAFT, 1, 0, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $sumMembers['total']['members_pending'].' '.$staticmember->LibStatut(Adherent::STATUS_VALIDATED, 1, 0, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $sumMembers['total']['members_uptodate'].' '.$staticmember->LibStatut(Adherent::STATUS_VALIDATED, 0, 0, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $sumMembers['total']['members_expired'].' '.$staticmember->LibStatut(Adherent::STATUS_VALIDATED, 1, 1, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $sumMembers['total']['members_excluded'].' '.$staticmember->LibStatut(Adherent::STATUS_EXCLUDED, 1, 0, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $sumMembers['total']['members_resiliated'].' '.$staticmember->LibStatut(Adherent::STATUS_RESILIATED, 1, 0, 3),
						'asis' => 1
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total right"',
						'text' => $sumMembers['total']['all'],
						'asis' => 1
					);
				}
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->lasterror())
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
	 *	@param	?array{text?:string,sublink?:string,subpicto:?string,nbcol?:int,limit?:int,subclass?:string,graph?:string}	$head	Array with properties of box title
	 *	@param	?array<array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:string}>>	$contents	Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
