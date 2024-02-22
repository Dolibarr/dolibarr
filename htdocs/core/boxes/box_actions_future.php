<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014 	   Charles-Fr BENKE        <charles.fr@benke.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/boxes/box_actions_future.php
 *	\ingroup    actions
 *	\brief      Module to build box for events
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show events in future
 */
class box_actions_future extends ModeleBoxes
{
	public $boxcode = "futureactions";
	public $boximg = "object_action";
	public $boxlabel = "BoxTitleFutureActions";
	public $depends = array("agenda");

	public $enabled = 1;

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;

		$this->enabled = isModEnabled('agenda');

		$this->hidden = !($user->hasRight('agenda', 'myactions', 'read'));
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;

		$this->max = $max;

		$now = dol_now();

		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$societestatic = new Societe($this->db);
		$actionstatic = new ActionComm($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleFutureActions", $max));

		if ($user->hasRight('agenda', 'myactions', 'read')) {
			$sql = "SELECT a.id, a.label, a.datep as dp, a.percent as percentage";
			$sql .= ", ta.code";
			$sql .= ", ta.libelle as type_label";
			$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_client, s.code_compta, s.client";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_actioncomm AS ta, ".MAIN_DB_PREFIX."actioncomm AS a";
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
			}
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
			$sql .= " WHERE a.fk_action = ta.id";
			$sql .= " AND a.entity IN (".getEntity('actioncomm').")";
			//$sql .= " AND a.percent >= 0 AND a.percent < 100";
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql .= " AND (a.fk_soc IS NULL OR sc.fk_user = ".((int) $user->id).")";
			}
			if ($user->socid) {
				$sql .= " AND s.rowid = ".((int) $user->socid);
			}
			if (!$user->hasRight('agenda', 'allactions', 'read')) {
				$sql .= " AND (a.fk_user_author = ".((int) $user->id)." OR a.fk_user_action = ".((int) $user->id)." OR a.fk_user_done = ".((int) $user->id).")";
			}
			$sql .= " AND a.datep > '".$this->db->idate($now)."'";
			$sql .= " ORDER BY a.datep ASC";
			$sql .= $this->db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$now = dol_now();
				$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

				$num = $this->db->num_rows($result);

				$line = 0;
				while ($line < $num) {
					$late = '';
					$objp = $this->db->fetch_object($result);
					$datelimite = $this->db->jdate($objp->dp);

					$actionstatic->id = $objp->id;
					$actionstatic->label = $objp->label;
					$actionstatic->type_label = $objp->type_label;
					$actionstatic->code = $objp->code;

					$societestatic->id = $objp->socid;
					$societestatic->name = $objp->name;
					//$societestatic->name_alias = $objp->name_alias;
					$societestatic->code_client = $objp->code_client;
					$societestatic->code_compta = $objp->code_compta;
					$societestatic->client = $objp->client;
					$societestatic->logo = $objp->logo;
					$societestatic->email = $objp->email;
					$societestatic->entity = $objp->entity;

					if ($objp->percentage >= 0 && $objp->percentage < 100 && $datelimite < ($now - $delay_warning)) {
						$late = img_warning($langs->trans("Late"));
					}

					//($langs->transnoentities("Action".$objp->code)!=("Action".$objp->code) ? $langs->transnoentities("Action".$objp->code) : $objp->label)
					//$label = empty($objp->label) ? $objp->type_label : $objp->label;

					$this->info_box_contents[$line][0] = array(
						'td' => 'class="tdoverflowmax200"',
						'text' => $actionstatic->getNomUrl(1),
						'text2'=> $late,
						'asis' => 1
					);

					$this->info_box_contents[$line][1] = array(
						'td' => 'class="tdoverflowmax100"',
						'text' => ($societestatic->id > 0 ? $societestatic->getNomUrl(1) : ''),
						'asis' => 1
					);

					$this->info_box_contents[$line][2] = array(
						'td' => 'class="center nowraponall"',
						'text' => $datelimite ? dol_print_date($datelimite, "dayhour", 'tzuserrel') : '',
						'asis' => 1
					);

					$this->info_box_contents[$line][3] = array(
						'td' => 'class="right"',
						'text' => ($objp->percentage >= 0 ? $objp->percentage.'%' : ''),
						'asis' => 1
					);

					$this->info_box_contents[$line][4] = array(
						'td' => 'class="right" width="18"',
						'text' => $actionstatic->LibStatut($objp->percentage, 3),
						'asis' => 1
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text'=> '<span class="opacitymedium">'.$langs->trans("NoActionsToDo").'</span>'
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength'=>500,
					'text' => ($this->db->error().' sql='.$sql)
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
	 *	Method to show box
	 *
	 *	@param  array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		global $langs, $conf;
		$out = parent::showBox($this->info_box_head, $this->info_box_contents, 1);

		if (getDolGlobalString('SHOW_DIALOG_HOMEPAGE')) {
			$actioncejour = false;
			$contents = $this->info_box_contents;
			if (is_countable($contents) && count($contents) > 0) {
				$nblines = count($contents);
			}
			if ($contents[0][0]['text'] != $langs->trans("NoActionsToDo")) {
				$out .= '<div id="dialogboxaction" title="'.$nblines." ".$langs->trans("ActionsToDo").'">';
				$out .= '<table width=100%>';
				for ($line = 0, $n = $nblines; $line < $n; $line++) {
					if (isset($contents[$line])) {
						// on affiche que les évènement du jours ou passé
						// qui ne sont pas à 100%
						$actioncejour = true;

						// TR
						$logo = $contents[$line][0]['logo'];
						$label = $contents[$line][1]['text'];
						$urlevent = $contents[$line][1]['url'];
						$logosoc = $contents[$line][2]['logo'];
						$nomsoc = $contents[$line][3]['text'];
						$urlsoc = $contents[$line][3]['url'];
						$dateligne = $contents[$line][4]['text'];
						$percentage = $contents[$line][5]['text'];
						$out .= '<tr class="oddeven">';
						$out .= '<td class="center">';
						$out .= img_object("", $logo);
						$out .= '</td>';
						$out .= '<td class="center"><a href="'.$urlevent.'">'.$label.'</a></td>';
						$out .= '<td class="center"><a href="'.$urlsoc.'">'.img_object("", $logosoc)." ".$nomsoc.'</a></td>';
						$out .= '<td class="center">'.$dateligne.'</td>';
						$out .= '<td class="center">'.$percentage.'</td>';
						$out .= '</tr>';
					}
				}
				$out .= '</table>';
			}
			$out .= '</div>';
			if ($actioncejour) {
				$out .= '<script nonce="'.getNonce().'">';
				$out .= '$("#dialogboxaction").dialog({ autoOpen: true });';
				if (getDolGlobalInt('SHOW_DIALOG_HOMEPAGE') > 1) {    // autoclose after this delay
					$out .= 'setTimeout(function(){';
					$out .= '$("#dialogboxaction").dialog("close");';
					$out .= '}, '.($conf->global->SHOW_DIALOG_HOMEPAGE * 1000).');';
				}
				$out .= '</script>';
			} else {
				$out .= '<script nonce="'.getNonce().'">';
				$out .= '$("#dialogboxaction").dialog({ autoOpen: false });';
				$out .= '</script>';
			}
		}

		if ($nooutput) {
			return $out;
		} else {
			print $out;
		}

		return '';
	}
}
