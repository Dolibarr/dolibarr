<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
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
 *	\file       htdocs/core/boxes/box_activite.php
 *	\ingroup    projet
 *	\brief      Module to show Projet activity of the current Year
 *	\version	$Id: box_projet.php,v 1.1 2012/09/11 Charles-François BENKE
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");

class box_projet extends ModeleBoxes {

	var $boxcode="projet";
	var $boximg="object_projectpub";
	var $boxlabel;
	//var $depends = array("projet");
	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

	/**
	 *      \brief      Constructeur de la classe
	 */
	function box_projet()
	{
		global $langs;
		$langs->load("boxes");
		$langs->load("projects");

		$this->boxlabel="Projet";
	}

	/**
	 *      \brief      Charge les donnees en memoire pour affichage ulterieur
	 *      \param      $max        Nombre maximum d'enregistrements a charger
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;
		
		$this->max=$max;
		
		$totalMnt = 0;
		$totalnb = 0;
		$totalnbTask=0;
		include_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
		require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
		$projectstatic=new Project($db);
		


		$textHead = $langs->trans("Projet")."&nbsp;".date("Y");
		$this->info_box_head = array('text' => $textHead, 'limit'=> dol_strlen($textHead));

		// list the summary of the orders
		if ($user->rights->projet->lire)
		{
			
			$sql = "SELECT p.fk_statut, count(p.rowid) as nb";
			$sql.= " FROM (".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."projet as p";
			$sql.= ")";
			$sql.= " WHERE p.fk_soc = s.rowid";
			$sql.= " AND s.entity = ".$conf->entity;
			$sql.= " AND DATE_FORMAT(p.datec,'%Y') = ".date("Y")." ";
			$sql.= " GROUP BY p.fk_statut ";
			$sql.= " ORDER BY p.fk_statut DESC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);

			if ($result)
			{
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"','logo' => 'object_projectpub');

					$objp = $db->fetch_object($result);
					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' =>$langs->trans("Project")."&nbsp;".$projectstatic->LibStatut($objp->fk_statut,0)
					);

					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
					'text' => $objp->nb."&nbsp;".$langs->trans("Projects"),
					'url' => DOL_URL_ROOT."/projet/liste.php?mainmenu=project&viewstatut=".$objp->fk_statut
					);
					$totalnb += $objp->nb;
					
					$sql = "SELECT sum(pt.total_ht) as Mnttot, count(*) as nb";
					$sql.= " FROM ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet as p";
					$sql.= " WHERE pt.fk_projet = p.rowid";
					$sql.= " AND p.entity = ".$conf->entity;
					$sql.= " AND (DATE_FORMAT(p.datec,'%Y') = ".date("Y").")";
					$sql.= " AND p.fk_statut=".$objp->fk_statut;
					$resultTask = $db->query($sql);
					if ($resultTask)
					{
						$objTask = $db->fetch_object($resultTask);
						$this->info_box_contents[$i][3] = array('td' => 'align="right"', 'text' => number_format($objTask->nb , 0, ',', ' ')."&nbsp;".$langs->trans("Tasks"));
						$this->info_box_contents[$i][4] = array('td' => 'align="right"', 'text' => number_format($objTask->Mnttot, 0, ',', ' ')."&nbsp;".$langs->trans("Currency".$conf->currency));

						$totalMnt += $objTask->Mnttot;
						$totalnbTask += $objTask->nb;
					}
					else
					{
						$this->info_box_contents[$i][3] = array('td' => 'align="right"', 'text' => number_format(0 , 0, ',', ' '));
						$this->info_box_contents[$i][4] = array('td' => 'align="right"', 'text' => dol_trunc(number_format(0 , 0, ',', ' '),40)."&nbsp;".$langs->trans("Currency".$conf->currency));
					}
					$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"', 'text' => $projectstatic->LibStatut($objp->fk_statut,3));

					$i++;
				}
			}
		}


		// Add the sum à the bottom of the boxes
		$this->info_box_contents[$i][0] = array('tr' => 'class="liste_total"', 'td' => 'colspan=2 align="left" ', 'text' => $langs->trans("Total")."&nbsp;".$textHead);
		$this->info_box_contents[$i][1] = array('td' => 'align="right" ', 'text' => number_format($totalnb, 0, ',', ' ')."&nbsp;".$langs->trans("Projects"));
		$this->info_box_contents[$i][2] = array('td' => 'align="right" ', 'text' => number_format($totalnbTask, 0, ',', ' ')."&nbsp;".$langs->trans("Tasks"));
		$this->info_box_contents[$i][3] = array('td' => 'align="right" ', 'text' => number_format($totalMnt, 0, ',', ' ')."&nbsp;".$langs->trans("Currency".$conf->currency));
		$this->info_box_contents[$i][4] = array('td' => 'colspan=2', 'text' => "");	
		
	}

	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
?>
