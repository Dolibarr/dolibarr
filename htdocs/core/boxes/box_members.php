<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *	\file       htdocs/core/boxes/box_members.php
 *	\ingroup    adherent
 *	\brief      Module to show box of members
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last members
 */
class box_members extends ModeleBoxes
{
<<<<<<< HEAD
	var $boxcode="lastmembers";
	var $boximg="object_user";
	var $boxlabel="BoxLastMembers";
	var $depends = array("adherent");

	var $db;
	var $param;
	var $enabled = 1;

	var $info_box_head = array();
	var $info_box_contents = array();
=======
    public $boxcode="lastmembers";
    public $boximg="object_user";
    public $boxlabel="BoxLastMembers";
    public $depends = array("adherent");

	/**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;
    public $enabled = 1;

    public $info_box_head = array();
    public $info_box_contents = array();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
     *  @param	string	$param		More parameters
	 */
<<<<<<< HEAD
	function __construct($db,$param='')
=======
	public function __construct($db, $param = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $conf, $user;

		$this->db = $db;

		// disable module for such cases
<<<<<<< HEAD
		$listofmodulesforexternal=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);
		if (! in_array('adherent',$listofmodulesforexternal) && ! empty($user->societe_id)) $this->enabled=0;	// disabled for external users
=======
		$listofmodulesforexternal=explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL);
		if (! in_array('adherent', $listofmodulesforexternal) && ! empty($user->societe_id)) $this->enabled=0;	// disabled for external users
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		$this->hidden=! ($user->rights->adherent->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
<<<<<<< HEAD
	function loadBox($max=5)
=======
	public function loadBox($max = 5)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user, $langs, $db, $conf;
		$langs->load("boxes");

		$this->max=$max;

        include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
        $memberstatic=new Adherent($db);

<<<<<<< HEAD
		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedMembers",$max));
=======
		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedMembers", $max));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		if ($user->rights->adherent->lire)
		{
			$sql = "SELECT a.rowid, a.lastname, a.firstname, a.societe as company, a.fk_soc,";
			$sql.= " a.datec, a.tms, a.statut as status, a.datefin as date_end_subscription,";
			$sql.= " t.subscription";
			$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a, ".MAIN_DB_PREFIX."adherent_type as t";
			$sql.= " WHERE a.entity IN (".getEntity('member').")";
			$sql.= " AND a.fk_adherent_type = t.rowid";
			$sql.= " ORDER BY a.tms DESC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);

				$line = 0;
				while ($line < $num)
				{
					$objp = $db->fetch_object($result);
					$datec=$db->jdate($objp->datec);
					$datem=$db->jdate($objp->tms);

					$memberstatic->lastname=$objp->lastname;
					$memberstatic->firstname=$objp->firstname;
					$memberstatic->id = $objp->rowid;
                    $memberstatic->ref = $objp->rowid;
                    $memberstatic->company = $objp->company;

					if (! empty($objp->fk_soc)) {
						$memberstatic->socid = $objp->fk_soc;
						$memberstatic->fetch_thirdparty();
						$memberstatic->name=$memberstatic->thirdparty->name;
					} else {
						$memberstatic->name=$objp->company;
					}

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $memberstatic->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $memberstatic->company,
                        'url' => DOL_URL_ROOT."/adherents/card.php?rowid=".$objp->rowid,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => dol_print_date($datem, "day"),
                    );

                    $this->info_box_contents[$line][] = array(
<<<<<<< HEAD
                        'td' => 'align="right" width="18"',
                        'text' => $memberstatic->LibStatut($objp->status,$objp->subscription,$db->jdate($objp->date_end_subscription),3),
=======
                        'td' => 'class="right" width="18"',
                        'text' => $memberstatic->LibStatut($objp->status, $objp->subscription, $db->jdate($objp->date_end_subscription), 3),
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    );

                    $line++;
                }

                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
<<<<<<< HEAD
                        'td' => 'align="center"',
=======
                        'td' => 'class="center"',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        'text'=>$langs->trans("NoRecordedCustomers"),
                    );

                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }
        } else {
            $this->info_box_contents[0][0] = array(
<<<<<<< HEAD
                'td' => 'align="left" class="nohover opacitymedium"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
            );
        }

=======
                'td' => 'class="nohover opacitymedium left"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
            );
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
<<<<<<< HEAD
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

=======
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
