<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2019 Frederic France      <frederic.france@netlogic.fr>
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
 *   \file       htdocs/core/boxes/box_prospect.php
 *   \ingroup    societe
 *   \brief      Module to generate the last prospects box.
 */


include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';


/**
 * Class to manage the box to show last prospects
 */
class box_prospect extends ModeleBoxes
{
	public $boxcode = "lastprospects";
	public $boximg = "object_company";
	public $boxlabel = "BoxLastProspects";
	public $depends = array("societe");

	public $enabled = 1;

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $conf, $user;

		$this->db = $db;

		// disable box for such cases
		if (getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
			$this->enabled = 0; // disabled by this option
		}

		$this->hidden = !($user->hasRight('societe', 'read') && empty($user->socid));
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $hookmanager;

		$this->max = $max;

		$thirdpartystatic = new Client($this->db);

		$this->info_box_head = array(
			'text' => $langs->trans("BoxTitleLastModifiedProspects", $max).'<a class="paddingleft" href="'.DOL_URL_ROOT.'/societe/list.php?type=p&sortfield=s.tms&sortorder=DESC"><span class="badge">...</span></a>',
		);

		if ($user->hasRight('societe', 'lire')) {
			$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_client, s.code_compta, s.client";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= ", s.fk_stcomm";
			$sql .= ", s.datec, s.tms, s.status";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= " WHERE s.client IN (2, 3)";
			$sql .= " AND s.entity IN (".getEntity('societe').")";
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			// Add where from hooks
			$parameters = array('socid' => $user->socid, 'boxcode' => $this->boxcode);
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $thirdpartystatic); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) {
				if ($user->socid > 0) {
					$sql .= " AND s.rowid = ".((int) $user->socid);
				}
			}
			$sql .= $hookmanager->resPrint;
			$sql .= " ORDER BY s.tms DESC";
			$sql .= $this->db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				$line = 0;
				while ($line < $num) {
					$objp = $this->db->fetch_object($resql);
					$datec = $this->db->jdate($objp->datec);
					$datem = $this->db->jdate($objp->tms);

					$thirdpartystatic->id = $objp->socid;
					$thirdpartystatic->name = $objp->name;
					$thirdpartystatic->name_alias = $objp->name_alias;
					$thirdpartystatic->code_client = $objp->code_client;
					$thirdpartystatic->code_compta = $objp->code_compta;
					$thirdpartystatic->code_compta_client = $objp->code_compta;
					$thirdpartystatic->client = $objp->client;
					$thirdpartystatic->logo = $objp->logo;
					$thirdpartystatic->email = $objp->email;
					$thirdpartystatic->entity = $objp->entity;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150"',
						'text' => $thirdpartystatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datem, "day", 'tzuserrel'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => str_replace('img ', 'img height="14" ', $thirdpartystatic->LibProspCommStatut($objp->fk_stcomm, 3)),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $thirdpartystatic->LibStatut($objp->status, 3),
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => '<span class="opacitymedium">'.$langs->trans("NoRecordedProspects").'</span>'
					);
				}

				$this->db->free($resql);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->error().' sql='.$sql),
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
