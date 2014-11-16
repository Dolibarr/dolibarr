<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014 	   Charles-Fr BENKE        <charles.fr@benke.fr>
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
 *	\file       htdocs/core/boxes/box_actions.php
 *	\ingroup    actions
 *	\brief      Module to build boxe for events
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show last events
 */
class box_actions extends ModeleBoxes
{
	var $boxcode="lastactions";
	var $boximg="object_action";
	var $boxlabel="BoxLastActions";
	var $depends = array("agenda");

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
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actionstatic=new ActionComm($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastActionsToDo",$max));

		if ($user->rights->agenda->myactions->read)
		{
			$sql = "SELECT a.id, a.label, a.datep as dp, a.percent as percentage,";
			$sql.= " ta.code, ta.libelle as type_label,";
			$sql.= " s.nom as name, s.rowid as socid";
			$sql.= " FROM (".MAIN_DB_PREFIX."c_actioncomm AS ta, ";
			$sql.= MAIN_DB_PREFIX."actioncomm AS a)";
			if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
			$sql.= " WHERE a.fk_action = ta.id";
			$sql.= " AND a.entity = ".$conf->entity;
			$sql.= " AND a.percent >= 0 AND a.percent < 100";
			if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
			if($user->societe_id)   $sql.= " AND s.rowid = ".$user->societe_id;
			if (! $user->rights->agenda->allactions->read) $sql.= " AND (a.fk_user_author = ".$user->id . " OR a.fk_user_action = ".$user->id . " OR a.fk_user_done = ".$user->id . ")";
			$sql.= " ORDER BY a.datec DESC";
			$sql.= $db->plimit($max, 0);

			dol_syslog("Box_actions::loadBox", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result)
			{
				$now=dol_now();
				$delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;

				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$late = '';
					$objp = $db->fetch_object($result);
					$datelimite=$db->jdate($objp->dp);

					if ($objp->percentage >= 0 && $objp->percentage < 100 && $datelimite  < ($now - $delay_warning)) $late=img_warning($langs->trans("Late"));

					//($langs->transnoentities("Action".$objp->code)!=("Action".$objp->code) ? $langs->transnoentities("Action".$objp->code) : $objp->label)
					$label=empty($objp->label)?$objp->type_label:$objp->label;

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
					'logo' => ("action"),
					'url' => DOL_URL_ROOT."/comm/action/card.php?id=".$objp->id);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' => dol_trunc($label,32),
					'text2'=> $late,
					'url' => DOL_URL_ROOT."/comm/action/card.php?id=".$objp->id);

					$this->info_box_contents[$i][2] = array('td' => 'align="left" width="16"',
                    'logo' => ($objp->socid?'company':''),
                    'url' => ($objp->socid?DOL_URL_ROOT."/societe/soc.php?socid=".$objp->socid:''));

					$this->info_box_contents[$i][3] = array('td' => 'align="left"',
					'text' => dol_trunc($objp->name,24),
					'url' => DOL_URL_ROOT."/societe/soc.php?socid=".$objp->socid);

					$this->info_box_contents[$i][4] = array('td' => 'align="left" class="nowrap"',
					'text' => dol_print_date($datelimite, "dayhour"));

					$this->info_box_contents[$i][5] = array('td' => 'align="right"',
					'text' => ($objp->percentage>= 0?$objp->percentage.'%':''));

					$this->info_box_contents[$i][6] = array('td' => 'align="right" width="18"',
		          	'text' => $actionstatic->LibStatut($objp->percentage,3));

					$i++;
				}

				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoActionsToDo"));

				$db->free($result);
			}
			else {
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}
		}
		else {
			$this->info_box_contents[0][0] = array('align' => 'left',
			'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
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
		global $langs, $conf;
		parent::showBox($this->info_box_head, $this->info_box_contents);
				if ($conf->global->SHOW_DIALOG_HOMEPAGE)
		{
			$actioncejour=false;
			$contents=$this->info_box_contents;
			$nblines=count($contents);
			$bcx=array();
			$bcx[0] = 'class="box_pair"';
			$bcx[1] = 'class="box_impair"';
			if ($contents[0][0]['text'] != $langs->trans("NoActionsToDo"))
			{
				print '<div id="dialog" title="'.$nblines." ".$langs->trans("ActionsToDo").'">';
				print '<table width=100%>';
				for ($i=0, $n=$nblines; $i < $n; $i++)
				{
					if (isset($contents[$i]))
					{
						// on affiche que les évènement du jours ou passé
						// qui ne sont pas à 100%
						$actioncejour=true;
						$var=!$var;
						// TR
						$logo=$contents[$i][0]['logo'];
						$label=$contents[$i][1]['text'];
						$urlevent=$contents[$i][1]['url'];
						$logosoc=$contents[$i][2]['logo'];
						$nomsoc=$contents[$i][3]['text'];
						$urlsoc=$contents[$i][3]['url'];
						$dateligne=$contents[$i][4]['text'];
						$percentage=$contents[$i][5]['text'];
						print '<tr '.$bcx[$var].'>';
						print '<td align=center>';
						print img_object("",$logo);
						print '</td>';
						print '<td align=center><a href="'.$urlevent.'">'.$label.'</a></td>';
						print '<td align=center><a href="'.$urlsoc.'">'.img_object("",$logosoc)." ".$nomsoc.'</a></td>';
						print '<td align=center>'.$dateligne.'</td>';
						print '<td align=center>'.$percentage.'</td>';
						print '</tr>';
					}
				}
				print '</table>';

			}
			print '</div>';
			if ($actioncejour)
			{
				print '<script>';
				print '$( "#dialog" ).dialog({ autoOpen: true });';
				if ($conf->global->SHOW_DIALOG_HOMEPAGE > 1)
				{
					print 'setTimeout(function(){';
					print '$("#dialog").dialog("close");';
					print '}, '.($conf->global->SHOW_DIALOG_HOMEPAGE*1000).');';
				}
				print '</script>';
			}
			else
			{
				print '<script>';
				print '$( "#dialog" ).dialog({ autoOpen: false });';
				print '</script>';
			}
		}
	}

}

