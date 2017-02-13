<?php
/* Copyright (C) 2013 Florian Henry  <florian.henry@open-concept.pro>
 * Copyright (C) 2013 Juanjo Menent  <jmenent@2byte.es>
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
 * 		\file       htdocs/core/boxes/box_ficheinter.php
 * 		\ingroup    ficheinter
 * 		\brief      Box to show last interventions
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last interventions
 */
class box_ficheinter extends ModeleBoxes
{
	var $boxcode="ficheinter";
	var $boximg="object_intervention";
	var $boxlabel="BoxFicheInter";
	var $depends = array("ficheinter");	// conf->contrat->enabled

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Load data for box to show them later
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	*/
	function loadBox($max=10)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
		$ficheinterstatic=new Fichinter($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastFicheInter",$max));

		if ($user->rights->ficheinter->lire)
		{
			$sql = "SELECT f.rowid, f.ref, f.fk_soc, f.fk_statut,";
			$sql.= " f.datec,";
			$sql.= " f.date_valid as datev,";
			$sql.= " f.tms as datem,";
			$sql.= " s.nom as name, s.rowid as socid, s.client";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (! $user->rights->societe->client->voir) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= ", ".MAIN_DB_PREFIX."fichinter as f";
			$sql.= " WHERE f.fk_soc = s.rowid ";
			$sql.= " AND f.entity = ".$conf->entity;
			if (! $user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if($user->societe_id)	$sql.= " AND s.rowid = ".$user->societe_id;
			$sql.= " ORDER BY f.tms DESC";
			$sql.= $db->plimit($max, 0);

			dol_syslog(get_class($this).'::loadBox', LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$now=dol_now();

				$i = 0;

				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					$datec=$db->jdate($objp->datec);

					$ficheinterstatic->statut=$objp->fk_statut;
					$ficheinterstatic->id=$objp->rowid;

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
					'logo' => $this->boximg,
					'url' => DOL_URL_ROOT."/fichinter/card.php?id=".$objp->rowid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' => ($objp->ref?$objp->ref:$objp->rowid),	// Some interventions have no ref
					'url' => DOL_URL_ROOT."/fichinter/card.php?id=".$objp->rowid);

					$this->info_box_contents[$i][2] = array('td' => 'align="left" width="16"',
					'logo' => 'company',
					'url' => DOL_URL_ROOT."/comm/card.php?socid=".$objp->socid);

					$this->info_box_contents[$i][3] = array('td' => 'align="left"',
					'text' => dol_trunc($objp->name,40),
					'url' => DOL_URL_ROOT."/comm/card.php?socid=".$objp->socid);

					$this->info_box_contents[$i][4] = array('td' => 'align="right"',
					'text' => dol_print_date($datec,'day'));

					$this->info_box_contents[$i][5] = array('td' => 'align="right" class="nowrap"',
					'text' => $ficheinterstatic->getLibStatut(6),
					'asis'=>1
					);

					$i++;
				}

				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedInterventions"));

				$db->free($resql);
			}
			else
			{
				$this->info_box_contents[0][0] = array(  'td' => 'align="left"',
				'maxlength'=>500,
				'text' => ($db->error().' sql='.$sql));
			}
		}
		else
		{
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
			'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	void
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

