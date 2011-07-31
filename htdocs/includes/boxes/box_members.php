<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/includes/boxes/box_members.php
 *	\ingroup    societes
 *	\brief      Module de generation de l'affichage de la box clients
 *	\version	$Id: box_members.php,v 1.4 2011/07/31 23:29:09 eldy Exp $
 */

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_members extends ModeleBoxes {

	var $boxcode="lastmembers";
	var $boximg="object_user";
	var $boxlabel;
	var $depends = array("adherent");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

	/**
	 *      \brief      Constructeur de la classe
	 */
	function box_members()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel=$langs->trans("BoxLastMembers");
	}

	/**
	 *      Load data of box into memory for a future usage
	 *      @param      $max        Maximum number of records to show
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;
		$langs->load("boxes");

		$this->max=$max;

        include_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
        $memberstatic=new Adherent($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedMembers",$max));

		if ($user->rights->societe->lire)
		{
			$sql = "SELECT s.rowid, s.nom, s.prenom, s.datec, s.tms, s.statut as status, s.datefin as date_end_subscription,";
			$sql.= " t.cotisation";
			$sql.= " FROM ".MAIN_DB_PREFIX."adherent as s, ".MAIN_DB_PREFIX."adherent_type as t";
			$sql.= " WHERE s.entity = ".$conf->entity;
			$sql.= " AND s.fk_adherent_type = t.rowid";
			$sql.= " ORDER BY s.tms DESC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);

			if ($result)
			{
				$num = $db->num_rows($result);

				$i = 0;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$datec=$db->jdate($objp->datec);
					$datem=$db->jdate($objp->tms);

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => $this->boximg,
                    'url' => DOL_URL_ROOT."/adherents/fiche.php?rowid=".$objp->rowid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->nom,
                    'url' => DOL_URL_ROOT."/adherents/fiche.php?rowid=".$objp->rowid);

					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
					'text' => dol_print_date($datem, "day"));

					$this->info_box_contents[$i][3] = array('td' => 'align="right" width="18"',
                    'text' => $memberstatic->LibStatut($objp->status,$objp->cotisation,$db->jdate($objp->date_end_subscription),3));

					$i++;
				}

				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedCustomers"));
			}
			else {
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}
		}
		else {
			$this->info_box_contents[0][0] = array('align' => 'left',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
		}

	}

	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
