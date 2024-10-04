<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017 	   Nicolas Zabouri      <info@inovea-conseil.com>
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
 *      \file       htdocs/core/boxes/box_scheduled_jobs.php
 *		\ingroup    task
 *      \brief      Widget of scheduled jobs
 */

include_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last contracted products/services lines
 */
class box_scheduled_jobs extends ModeleBoxes
{
	public $boxcode = "scheduledjobs";
	public $boximg = "object_cron";
	public $boxlabel = "BoxScheduledJobs";
	public $depends = array("cron");

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;

		$this->hidden = !($user->hasRight('cron', 'read'));
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf, $form;

		$langs->load("cron");
		$this->info_box_head = array('text' => $langs->trans("BoxScheduledJobs", $max), 'nbcol' => 4);

		if ($user->hasRight('cron', 'read')) {
			include_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';
			$cronstatic = new Cronjob($this->db);
			$resultarray = array();

			$result = 0;
			$sql = "SELECT t.rowid, t.datelastrun, t.datenextrun, t.datestart,";
			$sql .= " t.label, t.status, t.test, t.lastresult, t.processing";
			$sql .= " FROM " . MAIN_DB_PREFIX . "cronjob as t";
			$sql .= " WHERE status <> ".$cronstatic::STATUS_DISABLED;
			$sql .= " AND entity IN (0, ".$conf->entity.")";
			$sql .= $this->db->order("t.datelastrun", "DESC");

			$result = $this->db->query($sql);
			$line = 0;
			$nbjobsinerror = 0;
			$nbjobsnotfinished = 0;
			if ($result) {
				$num = $this->db->num_rows($result);

				$i = 0;
				while ($i < $num) {
					$objp = $this->db->fetch_object($result);

					if ((int) dol_eval($objp->test, 1, 1, '2')) {
						$nextrun = $this->db->jdate($objp->datenextrun);
						if (empty($nextrun)) {
							$nextrun = $this->db->jdate($objp->datestart);
						}

						if ($line == 0 || ($nextrun < $cronstatic->datenextrun && (empty($objp->nbrun) || empty($objp->maxrun) || $objp->nbrun < $objp->maxrun))) {
							// Save in cronstatic the job if it is a job to run in future
							$cronstatic->id = $objp->rowid;
							$cronstatic->ref = $objp->rowid;
							$cronstatic->label = $langs->trans($objp->label);
							$cronstatic->status = $objp->status;
							$cronstatic->processing = $objp->processing;
							$cronstatic->lastresult = $objp->lastresult ?? '';
							$cronstatic->datenextrun = $this->db->jdate($objp->datenextrun);
							$cronstatic->datelastrun = $this->db->jdate($objp->datelastrun);
						}
						if ($line == 0) {
							// Save the first line in loop that is the most recent executed job (due to the sort on datelastrun DESC)
							$resultarray[$line] = array(
								$langs->trans("LastExecutedScheduledJob"),
								$cronstatic->getNomUrl(1),
								$cronstatic->datelastrun,
								$cronstatic->status,
								$cronstatic->getLibStatut(3)
							);
							$line++;
						}

						if ($objp->processing && $this->db->jdate($objp->datelastrun) < (dol_now() - 3600 * 24)) {
							$nbjobsnotfinished++;
						}
						if (!empty($objp->lastresult)) {
							$nbjobsinerror++;
						}
					}
					$i++;
				}

				if ($line) {
					$resultarray[$line] = array(
						$langs->trans("NextScheduledJobExecute"),
						$cronstatic->getNomUrl(1),
						$cronstatic->datenextrun,
						$cronstatic->status,
						$cronstatic->getLibStatut(3)
					);
				}

				foreach ($resultarray as $line => $value) {
					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax200"',
						'text' => $resultarray[$line][0]
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'textnoformat' => $resultarray[$line][1]
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'textnoformat' => (empty($resultarray[$line][2]) ? '' : $form->textwithpicto(dol_print_date($resultarray[$line][2], "dayhoursec", 'tzserver'), $langs->trans("CurrentTimeZone")))
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" ',
						'textnoformat' => $resultarray[$line][4]
					);
					$line++;
				}

				// Line nb job in error
				$this->info_box_contents[$line][] = array(
					'td' => 'class="tdoverflowmax300" colspan="3"',
					'text' => $langs->trans("NumberScheduledJobError")
				);
				$textnoformat = '';
				if ($nbjobsnotfinished) {
					$textnoformat .= '<a class="inline-block paddingleft paddingright marginleftonly'.($nbjobsinerror ? ' marginrightonly' : '').' minwidth25 nounderlineimp" href="'.DOL_URL_ROOT.'/cron/list.php" title="'.$langs->trans("NumberScheduledJobNeverFinished").'"><div class="center badge badge-warning nounderlineimp"><i class="fa fa-exclamation-triangle"></i> '.$nbjobsnotfinished.'</div></a>';
				}
				if ($nbjobsinerror) {
					$textnoformat .= '<a class="inline-block paddingleft paddingright marginleftonly minwidth25 nounderlineimp" href="'.DOL_URL_ROOT.'/cron/list.php?search_lastresult='.urlencode('<>0').'" title="'.$langs->trans("NumberScheduledJobError").'"><div class="badge badge-danger nounderlineimp"><i class="fa fa-exclamation-triangle"></i> '.$nbjobsinerror.'</div></a>';
				}
				if (empty($nbjobsnotfinished) && empty($nbjobsinerror)) {
					$textnoformat .= '<a class="inline-block paddingleft marginleftonly minwidth25 nounderlineimp" href="'.DOL_URL_ROOT.'/cron/list.php"><div class="center badge badge-status4 nounderline">0</div></a>';
				}
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right"',
					'textnoformat' => $textnoformat
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->lasterror() . ' sql=' . $sql)
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
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
