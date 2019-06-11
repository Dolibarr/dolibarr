<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
    public $boxcode="birthdays";
    public $boximg="object_user";
    public $boxlabel="BoxBirthdays";
    public $depends = array("user");

	/**
     * @var DoliDB Database handler.
     */
    public $db;

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

		$this->hidden = ! ($user->rights->user->user->lire && empty($user->socid));
	}

	/**
     *  Load data for box to show them later
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	public function loadBox($max = 20)
	{
		global $user, $langs, $db, $conf;
		$langs->load("boxes");

		$this->max=$max;

        include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
        $userstatic=new User($db);

        $this->info_box_head = array('text' => $langs->trans("BoxTitleUserBirthdaysOfMonth"));

		if ($user->rights->user->user->lire)
		{
			$sql = "SELECT u.rowid, u.firstname, u.lastname";
            $sql.= ", u.birth";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.entity IN (".getEntity('user').")";
            $sql.= " AND MONTH(u.birth) = ".date('m');
			$sql.= " ORDER BY u.birth ASC";
			$sql.= $db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);

				$line = 0;
				while ($line < $num)
				{
					$objp = $db->fetch_object($result);
                    $userstatic->id = $objp->rowid;
                    $userstatic->firstname = $objp->firstname;
                    $userstatic->lastname = $objp->lastname;
                    $userstatic->email = $objp->email;
                    $dateb=$db->jdate($objp->birth);
                    $age = date('Y', dol_now()) - date('Y', $dateb);

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $userstatic->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => dol_print_date($dateb, "day") . ' - ' . $age . ' ' . $langs->trans('DurationYears')
                    );

                    /*$this->info_box_contents[$line][] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $userstatic->LibStatut($objp->status, 3)
                    );*/

					$line++;
				}

				if ($num==0) $this->info_box_contents[$line][0] = array('td' => 'class="center"','text'=>$langs->trans("NoRecordedUsers"));

				$db->free($result);
			}
			else {
				$this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql)
                );
			}
		}
		else {
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
