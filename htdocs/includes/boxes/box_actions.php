<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 \file       htdocs/includes/boxes/box_actions.php
 \ingroup    actions
 \brief      Module de generation de l'affichage de la box actions
 \version	$Id$
 */


include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_actions extends ModeleBoxes {

	var $boxcode="lastactions";
	var $boximg="object_action";
	var $boxlabel;
	var $depends = array("agenda");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *      \brief      Constructeur de la classe
	 */
	function box_actions()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel=$langs->trans("BoxLastActions");
	}

	/**
	 *      \brief      Charge les donn�es en m�moire pour affichage ult�rieur
	 *      \param      $max        Nombre maximum d'enregistrements � charger
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;
		
		include_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
		$actionstatic=new ActionComm($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastActionsToDo",$max));

		if ($user->rights->agenda->myactions->read)
		{
			$sql = "SELECT a.id, a.label, a.datep as dp, a.percent as percentage,";
			$sql.= " ta.code,";
			$sql.= " s.nom, s.rowid as socid";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm AS ta, ";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux AS sc, ";
			$sql.= MAIN_DB_PREFIX."actioncomm AS a";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON a.fk_soc = s.rowid";
			$sql.= " WHERE a.fk_action = ta.id";
			$sql.= " AND a.percent <> 100";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if($user->societe_id)
			{
				$sql .= " AND s.rowid = ".$user->societe_id;
			}
			$sql.= " ORDER BY a.datec DESC";
			$sql.= $db->plimit($max, 0);

			dolibarr_syslog("Box_actions::loadBox boxcode=".$boxcode." sql=".$sql);
			$result = $db->query($sql);
			if ($result)
			{
				$now=gmmktime();
				$delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;
				
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$late = '';
					$objp = $db->fetch_object($result);
					$datelimite=$db->jdate($objp->dp);

					if ($datelimite  < ($now - $delay_warning)) $late=img_warning($langs->trans("Late"));

					$label=($langs->transnoentities("Action".$objp->code)!=("Action".$objp->code) ? $langs->transnoentities("Action".$objp->code) : $objp->label);

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
					'logo' => ("task"),
					'url' => DOL_URL_ROOT."/comm/action/fiche.php?id=".$objp->id);
					
					$this->info_box_contents[$i][1] = array('td' => 'align="left" nowrap="1"',
					'text' => dolibarr_trunc($label,12),
					'text2'=> $late,
					'url' => DOL_URL_ROOT."/comm/action/fiche.php?id=".$objp->id);

					$this->info_box_contents[$i][2] = array('td' => 'align="left"',
					'text' => dolibarr_trunc($objp->nom,20),
					'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

					$this->info_box_contents[$i][3] = array('td' => 'align="left"',
					'text' => dolibarr_print_date($datelimite, "dayhour"));

					$this->info_box_contents[$i][4] = array('td' => 'align="right"',
					'text' => $objp->percentage. "%");

					$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"',
		          	'text' => $actionstatic->LibStatut($objp->percentage,3));

					$i++;
				}
					
				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoActionsToDo"));
			}
			else {
				dolibarr_print_error($db);
			}
		}
		else {
			$this->info_box_contents[0][0] = array('align' => 'left',
			'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	function showBox()
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>