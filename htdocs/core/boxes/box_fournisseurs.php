<?php
/* Copyright (C) 2004-2006 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2019 Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2020      Pierre Ardoin      <mapiolca@me.com>
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
 * \file       htdocs/core/boxes/box_fournisseurs.php
 * \ingroup    fournisseurs
 * \brief      Module to generate box of suppliers
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last suppliers
 */
class box_fournisseurs extends ModeleBoxes
{
	public $boxcode = "lastsuppliers";
	public $boximg = "object_company";
	public $boxlabel = "BoxLastSuppliers";
	public $depends = array("fournisseur");

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
		$langs->load("boxes");

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
		$thirdpartystatic = new Fournisseur($this->db);

		$this->info_box_head = array(
			'text' => $langs->trans("BoxTitleLastModifiedSuppliers", $max).'<a class="paddingleft" href="'.DOL_URL_ROOT.'/societe/list.php?type=f&sortfield=s.tms&sortorder=DESC"><span class="badge">...</span></a>'
		);

		if ($user->hasRight('societe', 'lire')) {
			$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= ", s.datec, s.tms, s.status";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= " WHERE s.fournisseur = 1";
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
			$sql .= " ORDER BY s.tms DESC ";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$datec = $this->db->jdate($objp->datec);
					$datem = $this->db->jdate($objp->tms);

					$thirdpartystatic->id = $objp->socid;
					$thirdpartystatic->name = $objp->name;
					$thirdpartystatic->name_alias = $objp->name_alias;
					$thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
					$thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
					$thirdpartystatic->fournisseur = $objp->fournisseur;
					$thirdpartystatic->logo = $objp->logo;
					$thirdpartystatic->email = $objp->email;
					$thirdpartystatic->entity = $objp->entity;

					$this->info_box_contents[$line][] = array(
						'td' => '',
						'text' => $thirdpartystatic->getNomUrl(1, '', 40),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datem, "day", 'tzuserrel'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $thirdpartystatic->LibStatut($objp->status, 3),
					);

					$line++;
				}

				if ($num == 0) {
					$langs->load("suppliers");
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => '<span class="opacitymedium">'.$langs->trans("NoRecordedSuppliers").'</span>'
					);
				}

				$this->db->free($result);
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
