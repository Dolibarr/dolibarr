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
 *  \file       htdocs/core/boxes/box_project_opportunities.php
 *  \ingroup    project
 *  \brief      Module to show Project opportunities of the current Year
 */
include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box to show project opportunities
 */
class box_project_opportunities extends ModeleBoxes
{
	public $boxcode = "project_opportunities";
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
		$this->boxlabel = "OpenedProjectsOpportunities";

		$this->enabled = getDolGlobalInt('PROJECT_USE_OPPORTUNITIES');
		$this->hidden = !$user->hasRight('projet', 'lire');
		$this->urltoaddentry = DOL_URL_ROOT.'/projet/card.php?action=create';
		$this->msgNoRecords = 'NoOpenedProjectsOpportunities';
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param   int		$max        Maximum number of records to load
	 *  @return  void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs;

		$this->max = $max;

		$textHead = $langs->trans("OpenedProjectsOpportunities");
		$this->info_box_head = array('text' => $textHead, 'limit' => dol_strlen($textHead));

		$num = 0;
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

			$sql = "SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut as status, p.fk_opp_status as opp_status, p.opp_percent, p.opp_amount, p.public,";
			$sql .= " s.nom as name, s.name_alias,";
			$sql .= " cls.code as opp_status_code";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_lead_status as cls on p.fk_opp_status = cls.rowid";
			$sql .= " WHERE p.entity IN (".getEntity('project').")"; // Only current entity or severals if permission ok
			$sql .= " AND p.usage_opportunity = 1";
			$sql .= " AND p.fk_opp_status > 0";
			$sql .= " AND p.fk_statut IN (".$this->db->sanitize($projectstatic::STATUS_DRAFT.",".$projectstatic::STATUS_VALIDATED).")"; // draft and open projects
			//$sql .= " AND p.fk_statut = ".((int) $projectstatic::STATUS_VALIDATED); // Only open projects
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
					$projectstatic->opp_status = $objp->opp_status;
					$projectstatic->opp_status_code = $objp->opp_status_code;
					$projectstatic->opp_percent = $objp->opp_percent;
					$projectstatic->opp_amount = $objp->opp_amount;

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

					$this->info_box_contents[$i][] = array('td' => 'class="amount right nowraponall"', 'text' => ($projectstatic->opp_amount ? price($projectstatic->opp_amount) : ''));

					$this->info_box_contents[$i][] = array('td' => 'class="nowraponall"', 'asis' => 1, 'text' => ($projectstatic->opp_status_code ? $langs->trans("OppStatus".$projectstatic->opp_status_code).' ' : '').'<span class="opacitymedium small">('.round($projectstatic->opp_percent).'%)</span>');

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
