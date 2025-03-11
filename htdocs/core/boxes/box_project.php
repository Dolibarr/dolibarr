<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2014      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2016      Juan José Menent       <jmenent@2byte.es>
 * Copyright (C) 2020      Pierre Ardoin          <mapiolca@me.com>
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
 *  \file       htdocs/core/boxes/box_project.php
 *  \ingroup    project
 *  \brief      Module to show Project activity of the current Year
 */
include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box to show last project
 */
class box_project extends ModeleBoxes
{
	public $boxcode = "project";
	public $boximg  = "object_projectpub";
	/**
	 * @var string
	 */
	public $boxlabel;
	// var $depends = array("projet");

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $user, $langs;

		// Load translation files required by the page
		$langs->loadLangs(array('boxes', 'projects'));

		$this->db = $db;
		$this->boxlabel = "OpenedProjects";

		$this->hidden = !$user->hasRight('projet', 'lire');
		$this->urltoaddentry = DOL_URL_ROOT.'/projet/card.php?action=create';
		$this->msgNoRecords = 'NoOpenedProjects';
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param   int		$max        Maximum number of records to load
	 *  @return  void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		$this->max = $max;

		$totalMnt = 0;
		$totalnb = 0;
		$totalnbTask = 0;
		$num = 0;

		$textHead = $langs->trans("OpenedProjects");
		$this->info_box_head = array('text' => $textHead, 'limit' => dol_strlen($textHead));

		$i = 0;
		// list the summary of the orders
		if ($user->hasRight('projet', 'lire')) {
			include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$projectstatic = new Project($this->db);
			$companystatic = new Societe($this->db);

			$socid = 0;
			//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.

			// Get list of project id allowed to user (in a string list separated by coma)
			$projectsListId = '';
			if (!$user->hasRight('projet', 'all', 'lire')) {
				$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, $socid);
			}

			$sql = "SELECT p.rowid, p.ref, p.title, p.fk_statut as status, p.public, p.fk_soc,";
			$sql .= " s.nom as name, s.name_alias";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
			$sql .= " WHERE p.entity IN (".getEntity('project').")"; // Only current entity or severals if permission ok
			$sql .= " AND p.fk_statut = ".((int) $projectstatic::STATUS_VALIDATED); // Only open projects
			if (!$user->hasRight('projet', 'all', 'lire')) {
				$sql .= " AND p.rowid IN (".$this->db->sanitize($projectsListId).")"; // public and assigned to, or restricted to company for external users
			}

			$sql .= " ORDER BY p.datec DESC";
			//$sql.= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);

			if ($result) {
				$num = $this->db->num_rows($result);
				while ($i < min($num, $max)) {
					$objp = $this->db->fetch_object($result);

					$projectstatic->id = $objp->rowid;
					$projectstatic->ref = $objp->ref;
					$projectstatic->title = $objp->title;
					$projectstatic->public = $objp->public;
					$projectstatic->statut = $objp->status;

					$companystatic->id = $objp->fk_soc;
					$companystatic->name = $objp->name;
					$companystatic->name_alias = $objp->name_alias;

					$this->info_box_contents[$i][] = array(
						'td' => 'class="nowraponall"',
						'text' => $projectstatic->getNomUrl(1),
						'asis' => 1
					);

					$this->info_box_contents[$i][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth200onsmartphone"',
						'text' => $objp->title,
					);

					$this->info_box_contents[$i][] = array(
						'td' => 'class="tdoverflowmax100"',
						'text' => ($objp->fk_soc > 0 ? $companystatic->getNomUrl(1) : ''),
						'asis' => 1
					);

					$sql = "SELECT count(*) as nb, sum(progress) as totprogress";
					$sql .= " FROM ".MAIN_DB_PREFIX."projet as p LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt on pt.fk_projet = p.rowid";
					$sql .= " WHERE p.entity IN (".getEntity('project').')';
					$sql .= " AND p.rowid = ".((int) $objp->rowid);

					$resultTask = $this->db->query($sql);
					if ($resultTask) {
						$objTask = $this->db->fetch_object($resultTask);
						$this->info_box_contents[$i][] = array(
							'td' => 'class="right"',
							'text' => $objTask->nb."&nbsp;".$langs->trans("Tasks"),
						);
						if ($objTask->nb > 0) {
							$this->info_box_contents[$i][] = array(
								'td' => 'class="right"',
								'text' => round($objTask->totprogress / $objTask->nb, 0)."%",
							);
						} else {
							$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => "N/A&nbsp;");
						}
						$totalnbTask += $objTask->nb;
					} else {
						$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => round(0));
						$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => "N/A&nbsp;");
					}
					$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => $projectstatic->getLibStatut(3));

					$i++;
				}
				if ($max < $num) {
					$this->info_box_contents[$i][] = array('td' => 'colspan="6"', 'text' => '...');
					$i++;
				}
			}
		}

		if ($num > 0) {
			// Add the sum à the bottom of the boxes
			$this->info_box_contents[$i][] = array(
				'tr' => 'class="liste_total_wrap"',
				'td' => 'class="liste_total"',
				'text' => $langs->trans("Total")."&nbsp;".$textHead,
			);
			$this->info_box_contents[$i][] = array(
				'td' => 'class="right liste_total" ',
				'text' => round($num, 0)."&nbsp;".$langs->trans("Projects"),
			);
			$this->info_box_contents[$i][] = array(
				'td' => 'class="right liste_total" ',
				'text' => (($max < $num) ? '' : (round($totalnbTask, 0)."&nbsp;".$langs->trans("Tasks"))),
			);
			$this->info_box_contents[$i][] = array(
				'td' => 'class="liste_total"',
				'text' => "&nbsp;",
			);
			$this->info_box_contents[$i][] = array(
				'td' => 'class="liste_total"',
				'text' => "&nbsp;",
			);
			$this->info_box_contents[$i][] = array(
				'td' => 'class="liste_total"',
				'text' => "&nbsp;",
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
