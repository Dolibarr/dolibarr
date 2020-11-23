<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2014      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2016      Juan José Menent       <jmenent@2byte.es>
 * Copyright (C) 2020      Pierre Ardoin          <mapiolca@me.com>
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
 *  \file       htdocs/core/boxes/box_validated_projects.php
 *  \ingroup    projet
 *  \brief      Module to show validated projects whose tasks are assigned to the connected person, without any time entered by the connected person
 */
include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box to show last projet
 */
class box_validated_projects extends ModeleBoxes
{
	public $boxcode = "validated_project";
	public $boximg = "object_projectpub";
	public $boxlabel;
	//var $depends = array("projet");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $param;

	public $info_box_head = array();
	public $info_box_contents = array();

	public $enabled = 1;


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $conf, $user, $langs;

		// Load translation files required by the page
		$langs->loadLangs(array('boxes', 'projects'));

		$this->db = $db;
		$this->boxlabel = "ProjectsWithTask";

		$this->hidden = !($user->rights->projet->lire);

		if ($conf->global->MAIN_FEATURES_LEVEL < 2) $this->enabled = 0;
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

		$textHead = $langs->trans("ProjectTasksWithoutTimeSpent");
		$this->info_box_head = array('text' => $textHead, 'limit'=> dol_strlen($textHead));

		// list the summary of the orders
		if ($user->rights->projet->lire) {
			include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			$projectstatic = new Project($this->db);

			$socid = 0;
			//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.

			// Get list of project id allowed to user (in a string list separated by coma)
			$projectsListId = '';
			if (!$user->rights->projet->all->lire) $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, $socid);

			// I tried to solve sql error and performance problem, rewriting sql request but it is not clear what we want.
			// Count of tasks without time spent for tasks we are assigned too or
			// Count of tasks without time spent for all tasks of projects we are allowed to read (what it does) ?
			$sql = "SELECT p.rowid, p.ref, p.fk_soc, p.dateo as startdate,";
			$sql .= " COUNT(DISTINCT t.rowid) as tasknumber";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet AS p";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."projet_task AS t ON p.rowid = t.fk_projet";
			// TODO Replace -1, -2, -3 with ID used for type of contat project_task into llx_c_type_contact. Once done, we can switch widget as stable.
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."element_contact as ec ON ec.element_id = t.rowid AND fk_c_type_contact IN (-1, -2, -3)";
			$sql .= " WHERE p.fk_statut = 1"; // Only open projects
			if ($projectsListId) $sql .= ' AND p.rowid IN ('.$this->db->sanitize($projectsListId).')'; // Only project we ara allowed
			$sql .= " AND t.rowid NOT IN (SELECT fk_task FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_user =".$user->id.")";
			$sql .= " GROUP BY p.rowid, p.ref, p.fk_soc, p.dateo";
			$sql .= " ORDER BY p.dateo ASC";

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$i = 0;
				$this->info_box_contents[$i][] = array(
					'td' => 'class="nowraponall"',
					'text' => "Reference projet",
				);
				$this->info_box_contents[$i][] = array(
					'td' => 'class="center"',
					'text' => 'Client',
				);
				$this->info_box_contents[$i][] = array(
					'td' => 'class="center"',
					'text' => 'Date debut de projet',
				);
				$this->info_box_contents[$i][] = array(
					'td' => 'class="center"',
					'text' => 'Nombre de mes tâches sans temps saisi',
				);
				$i++;

				while ($i < min($num + 1, $max + 1)) {
					$objp = $this->db->fetch_object($result);

					$projectstatic->id = $objp->rowid;
					$projectstatic->ref = $objp->ref;

					$this->info_box_contents[$i][] = array(
						'td' => 'class="nowraponall"',
						'text' => $projectstatic->getNomUrl(1),
						'asis' => 1
					);

					if ($objp->fk_soc > 0) {
						$sql = 'SELECT rowid, nom as name FROM '.MAIN_DB_PREFIX.'societe WHERE rowid ='.$objp->fk_soc;
						$resql = $this->db->query($sql);
						//$socstatic = new Societe($this->db);
						$obj2 = $this->db->fetch_object($resql);
						$this->info_box_contents[$i][] = array(
							'td' => 'class="tdoverflowmax150 maxwidth200onsmartphone"',
							'text' => $obj2->name,
							'asis' => 1,
							'url' => DOL_URL_ROOT.'/societe/card.php?socid='.$obj2->rowid
						);
					}
					else {
						$this->info_box_contents[$i][] = array(
							'td' => 'class="tdoverflowmax150 maxwidth200onsmartphone"',
							'text' => '',
							'asis' => 1,
							'url' => ''
						);
					}

					$this->info_box_contents[$i][] = array(
						'td' => 'class="center"',
						'text' => $objp->startDate,
					);

					$this->info_box_contents[$i][] = array(
						'td' => 'class="center"',
						'text' => $objp->tasknumber."&nbsp;".$langs->trans("Tasks"),
						'asis' => 1,
					);
					$i++;
				}
			} else {
				dol_print_error($this->db);
			}
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
