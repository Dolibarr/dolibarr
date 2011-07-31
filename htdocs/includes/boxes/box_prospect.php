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
 *   \file       htdocs/includes/boxes/box_prospect.php
 *   \ingroup    societe
 *   \brief      Module to generate the last prospects box.
 *	\version	$Id: box_prospect.php,v 1.43 2011/07/31 23:29:10 eldy Exp $
 */


include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");
include_once(DOL_DOCUMENT_ROOT."/comm/prospect/class/prospect.class.php");


class box_prospect extends ModeleBoxes {

	var $boxcode="lastprospects";
	var $boximg="object_company";
	var $boxlabel;
	var $depends = array("societe");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

	/**
	 *      \brief      Constructeur de la classe
	 */
	function box_prospect($DB,$param)
	{
		global $langs;
		$langs->load("boxes");

		$this->db=$DB;
		$this->param=$param;

		$this->boxlabel=$langs->trans("BoxLastProspects");
	}

	/**
	 *      \brief      Charge les donnees en memoire pour affichage ulterieur
	 *      \param      $max        Nombre maximum d'enregistrements a charger
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

        include_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
        $thirdpartystatic=new Societe($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedProspects",$max));

		if ($user->rights->societe->lire)
		{
			$sql = "SELECT s.nom, s.rowid as socid, s.fk_stcomm, s.datec, s.tms, s.status";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= " WHERE s.client IN (2, 3)";
			$sql.= " AND s.entity = ".$conf->entity;
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if ($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
			$sql.= " ORDER BY s.tms DESC";
			$sql.= $db->plimit($max, 0);

			dol_syslog("box_prospect::loadBox sql=".$sql,LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);

				$i = 0;
				$prospectstatic=new Prospect($db);
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					$datec=$db->jdate($objp->datec);
					$datem=$db->jdate($objp->tms);

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
			            'logo' => $this->boximg,
			            'url' => DOL_URL_ROOT."/comm/prospect/fiche.php?socid=".$objp->socid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
			            'text' => $objp->nom,
			            'url' => DOL_URL_ROOT."/comm/prospect/fiche.php?socid=".$objp->socid);

					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
 			           'text' => dol_print_date($datem, "day"));

					$this->info_box_contents[$i][3] = array('td' => 'align="right" width="18"',
     			       'text' => str_replace('img ','img height="14" ',$prospectstatic->LibProspStatut($objp->fk_stcomm,3)));

                    $this->info_box_contents[$i][4] = array('td' => 'align="right" width="18"',
                    'text' => $thirdpartystatic->LibStatut($objp->status,3));

                    $i++;
				}

				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedProspects"));
			}
			else
			{
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}
		}
		else {
			dol_syslog("box_prospect::loadBox not allowed de read this box content",LOG_ERR);
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
        'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
