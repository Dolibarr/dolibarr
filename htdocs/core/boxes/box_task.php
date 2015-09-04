<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
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
 *  \file       htdocs/core/boxes/box_task.php
 *  \ingroup    Projet
 *  \brief      Module to Task activity of the current year
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

/**
 * Class to manage the box to show last task
 */
class box_task extends ModeleBoxes
{
    var $boxcode="projet";
    var $boximg="object_projecttask";
    var $boxlabel;
    //var $depends = array("projet");
    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    function __construct($db,$param='')
    {
        global $langs;
        $langs->load("boxes");
        $langs->load("projects");
        $this->boxlabel="Tasks";
        $this->db = $db;
    }

	/**
	 *  Load data for box to show them later
	 *
	 *  @param  int     $max        Maximum number of records to load
	 *  @return void
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		$totalMnt = 0;
		$totalnb = 0;
		$totalDuree=0;
		include_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
		$taskstatic=new Task($db);


		$textHead = $langs->trans("Tasks")."&nbsp;".date("Y");
		$this->info_box_head = array('text' => $textHead, 'limit'=> dol_strlen($textHead));

		// list the summary of the orders
		if ($user->rights->projet->lire) {

			$sql = "SELECT pt.fk_statut, count(pt.rowid) as nb, sum(ptt.task_duration) as durationtot, sum(pt.planned_workload) as plannedtot";
			$sql.= " FROM ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet_task_time as ptt";
			$sql.= " WHERE DATE_FORMAT(pt.datec,'%Y') = '".date("Y")."' ";
			$sql.= " AND pt.rowid = ptt.fk_task";
			$sql.= " GROUP BY pt.fk_statut ";
			$sql.= " ORDER BY pt.fk_statut DESC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);

			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
                while ($i < $num) {
                    $this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"','logo' => 'object_projecttask');

                    $objp = $db->fetch_object($result);
                    $this->info_box_contents[$i][1] = array(
                        'td' => 'align="left"',
                        'text' =>$langs->trans("Task")."&nbsp;".$taskstatic->LibStatut($objp->fk_statut,0),
                    );

                    $this->info_box_contents[$i][2] = array(
                        'td' => 'align="right"',
                        'text' => $objp->nb."&nbsp;".$langs->trans("Tasks"),
                        'url' => DOL_URL_ROOT."/projet/tasks/index.php?leftmenu=projects&viewstatut=".$objp->fk_statut,
                    );
					$totalnb += $objp->nb;
					$this->info_box_contents[$i][3] = array('td' => 'align="right"', 'text' => ConvertSecondToTime($objp->plannedtot,'all',25200,5));
					$totalplannedtot += $objp->plannedtot;
					$this->info_box_contents[$i][4] = array('td' => 'align="right"', 'text' => ConvertSecondToTime($objp->durationtot,'all',25200,5));
					$totaldurationtot += $objp->durationtot;

					$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"', 'text' => $taskstatic->LibStatut($objp->fk_statut,3));

					$i++;
				}
			}
		}


		// Add the sum à the bottom of the boxes
		$this->info_box_contents[$i][0] = array('tr' => 'class="liste_total"', 'td' => 'align="left"', 'text' => $langs->trans("Total")."&nbsp;".$textHead);
		$this->info_box_contents[$i][1] = array('td' => '', 'text' => "");
		$this->info_box_contents[$i][2] = array('td' => 'align="right" ', 'text' => number_format($totalnb, 0, ',', ' ')."&nbsp;".$langs->trans("Tasks"));
		$this->info_box_contents[$i][3] = array('td' => 'align="right" ', 'text' => ConvertSecondToTime($totalplannedtot,'all',25200,5));
		$this->info_box_contents[$i][4] = array('td' => 'align="right" ', 'text' => ConvertSecondToTime($totaldurationtot,'all',25200,5));
		$this->info_box_contents[$i][5] = array('td' => '', 'text' => "");

	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
