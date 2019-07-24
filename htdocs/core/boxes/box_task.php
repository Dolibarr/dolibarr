<?php
/* Copyright (C) 2012-2018 Charlene BENKE 	<charlie@patas-monkey.com>
 * Copyright (C) 2015-2019  Frederic France      <frederic.france@netlogic.fr>
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

include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";


/**
 * Class to manage the box to show last task
 */
class box_task extends ModeleBoxes
{
    public $boxcode="projet";
    public $boximg="object_projecttask";
    public $boxlabel;
    //public $depends = array("projet");

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;
    public $enabled = 0;		// Disabled because bugged.

    public $info_box_head = array();
    public $info_box_contents = array();


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

        $this->boxlabel="Tasks";
        $this->db = $db;

        $this->hidden = ! ($user->rights->projet->lire);
    }

	/**
	 *  Load data for box to show them later
	 *
	 *  @param  int     $max        Maximum number of records to load
	 *  @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		$totalMnt = 0;
		$totalnb = 0;
		$totalDuree=0;
		$totalplannedtot=0;
		$totaldurationtot=0;

		include_once DOL_DOCUMENT_ROOT."/projet/class/task.class.php";
		$taskstatic=new Task($db);


		$textHead = $langs->trans("Tasks")."&nbsp;".date("Y");
		$this->info_box_head = array('text' => $textHead, 'limit'=> dol_strlen($textHead));

		// list the summary of the orders
		if ($user->rights->projet->lire) {
			// FIXME fk_statut on a task is not be used. We use the percent. This means this box is useless.
			$sql = "SELECT pt.fk_statut, count(DISTINCT pt.rowid) as nb, sum(ptt.task_duration) as durationtot, sum(pt.planned_workload) as plannedtot";
			$sql.= " FROM ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet_task_time as ptt";
			$sql.= " WHERE pt.datec BETWEEN '".$this->db->idate(dol_get_first_day(date("Y"), 1))."' AND '".$this->db->idate(dol_get_last_day(date("Y"), 12))."'";
			$sql.= " AND pt.rowid = ptt.fk_task";
			$sql.= " GROUP BY pt.fk_statut ";
			$sql.= " ORDER BY pt.fk_statut DESC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			$i = 0;
			if ($result) {
				$num = $db->num_rows($result);
                while ($i < $num) {
                    $objp = $db->fetch_object($result);
                    $this->info_box_contents[$i][] = array(
                        'td' => '',
                        'text' =>$langs->trans("Task")." ".$taskstatic->LibStatut($objp->fk_statut, 0),
                    );

                    $this->info_box_contents[$i][] = array(
                        'td' => 'class="right"',
                        'text' => $objp->nb."&nbsp;".$langs->trans("Tasks"),
                        'url' => DOL_URL_ROOT."/projet/tasks/list.php?leftmenu=projects&viewstatut=".$objp->fk_statut,
                    );
					$totalnb += $objp->nb;
					$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => ConvertSecondToTime($objp->plannedtot, 'all', 25200, 5));
					$totalplannedtot += $objp->plannedtot;
					$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => ConvertSecondToTime($objp->durationtot, 'all', 25200, 5));
					$totaldurationtot += $objp->durationtot;

					$this->info_box_contents[$i][] = array('td' => 'class="right" width="18"', 'text' => $taskstatic->LibStatut($objp->fk_statut, 3));

					$i++;
				}
			} else {
                dol_print_error($this->db);
            }
		}

		// Add the sum at the bottom of the boxes
		$this->info_box_contents[$i][] = array('tr' => 'class="liste_total"', 'td' => '', 'text' => $langs->trans("Total")."&nbsp;".$textHead);
		$this->info_box_contents[$i][] = array('td' => 'class="right" ', 'text' => number_format($totalnb, 0, ',', ' ')."&nbsp;".$langs->trans("Tasks"));
		$this->info_box_contents[$i][] = array('td' => 'class="right" ', 'text' => ConvertSecondToTime($totalplannedtot, 'all', 25200, 5));
		$this->info_box_contents[$i][] = array('td' => 'class="right" ', 'text' => ConvertSecondToTime($totaldurationtot, 'all', 25200, 5));
		$this->info_box_contents[$i][] = array('td' => '', 'text' => "");
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
