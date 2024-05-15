<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2024  Frédéric France      <frederic.france@free.fr>
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
 *	\file       htdocs/core/boxes/box_birthdays.php
 *	\ingroup    user
 *	\brief      Box for user birthdays
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show user birthdays
 */
class box_birthdays extends ModeleBoxes
{
	public $boxcode = "birthdays";
	public $boximg = "object_user";
	public $boxlabel = "BoxTitleUserBirthdaysOfMonth";
	public $depends = array("user");

	public $enabled = 1;

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $user;

		$this->db = $db;

		$this->hidden = !($user->hasRight('user', 'user', 'read') && empty($user->socid));
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 20)
	{
		global $conf, $user, $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$userstatic = new User($this->db);


		$langs->load("boxes");

		$this->max = $max;

		$this->info_box_head = array('text' => $langs->trans("BoxTitleUserBirthdaysOfMonth"));

		if ($user->hasRight('user', 'user', 'lire')) {
			$data = array();

			$tmparray = dol_getdate(dol_now(), true);

			$sql = "SELECT u.rowid, u.firstname, u.lastname, u.birth as datea, date_format(u.birth, '%d') as daya, 'birth' as typea, u.email, u.statut as status";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql .= " WHERE u.entity IN (".getEntity('user').")";
			$sql .= " AND u.statut = ".User::STATUS_ENABLED;
			$sql .= dolSqlDateFilter('u.birth', 0, $tmparray['mon'], 0);
			$sql .= " AND u.birth < '".$this->db->idate(dol_get_first_day($tmparray['year']))."'";
			$sql .= ' UNION ';
			$sql .= "SELECT u.rowid, u.firstname, u.lastname, u.dateemployment as datea, date_format(u.dateemployment, '%d') as daya, 'employment' as typea, u.email, u.statut as status";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql .= " WHERE u.entity IN (".getEntity('user').")";
			$sql .= " AND u.statut = ".User::STATUS_ENABLED;
			$sql .= dolSqlDateFilter('u.dateemployment', 0, $tmparray['mon'], 0);
			$sql .= " AND u.dateemployment < '".$this->db->idate(dol_get_first_day($tmparray['year']))."'";
			$sql .= " ORDER BY daya ASC";	// We want to have date of the month sorted by the day without taking into consideration the year
			$sql .= $this->db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				$line = 0;
				while ($line < $num) {
					$data[$line] = $this->db->fetch_object($resql);

					$line++;
				}

				$this->db->free($resql);
			}

			if (!empty($data)) {
				$j = 0;
				while ($j < count($data)) {
					$userstatic->id = $data[$j]->rowid;
					$userstatic->firstname = $data[$j]->firstname;
					$userstatic->lastname = $data[$j]->lastname;
					$userstatic->email = $data[$j]->email;
					$userstatic->status = $data[$j]->status;

					$dateb = $this->db->jdate($data[$j]->datea);
					$age = idate('Y', dol_now()) - idate('Y', $dateb);

					$picb = '<i class="fas fa-birthday-cake inline-block"></i>';
					$pice = '<i class="fas fa-briefcase inline-block"></i>';
					$typea = ($data[$j]->typea == 'birth') ? $picb : $pice;

					$this->info_box_contents[$j][0] = array(
						'td' => '',
						'text' => $userstatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$j][1] = array(
						'td' => 'class="center nowraponall"',
						'text' => dol_print_date($dateb, "day", 'tzserver')
					);

					$this->info_box_contents[$j][2] = array(
						'td' => 'class="right nowraponall"',
						'text' => $age.' '.$langs->trans('DurationYears')
					);

					$this->info_box_contents[$j][3] = array(
						'td' => 'class="right nowraponall"',
						'text' => $typea,
						'asis' => 1
					);

					$j++;
				}
			}
			if (is_array($data) && count($data) == 0) {
				$this->info_box_contents[0][0] = array(
					'td' => 'class="center"',
					'text' => '<span class="opacitymedium">'.$langs->trans("None").'</span>',
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
