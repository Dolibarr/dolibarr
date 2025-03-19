<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2016      Charlie Benke        <charlie@patas-monkey.com>
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
 *	\file       htdocs/core/boxes/box_goodcustomers.php
 *	\ingroup    societes
 *	\brief      Module to generated widget of best customers (the most invoiced)
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show top-selling customers
 */
class box_goodcustomers extends ModeleBoxes
{
	public $boxcode  = "goodcustomers";
	public $boximg   = "object_company";
	public $boxlabel = "BoxGoodCustomers";
	public $depends  = array("societe");

	public $enabled = 1;

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $user;

		$this->db = $db;

		// disable box for such cases
		if (getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
			$this->enabled = 0; // disabled by this option
		}
		if (!getDolGlobalString('MAIN_BOX_ENABLE_BEST_CUSTOMERS')) {
			$this->enabled = 0; // not enabled by default. Very slow on large database
		}

		$this->hidden = !$user->hasRight('societe', 'lire');
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
		$langs->load("boxes");

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$thirdpartystatic = new Societe($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleGoodCustomers", $max));

		if ($user->hasRight('societe', 'lire')) {
			$sql = "SELECT s.rowid, s.nom as name, s.logo, s.code_client, s.code_fournisseur, s.client, s.fournisseur, s.tms as datem, s.status as status,";
			$sql .= " count(*) as nbfact, sum(".$this->db->ifsql('f.paye=1', '1', '0').") as nbfactpaye";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
			$sql .= ' WHERE s.entity IN ('.getEntity('societe').')';
			$sql .= ' AND s.rowid = f.fk_soc';
			$sql .= " GROUP BY s.rowid, s.nom, s.logo, s.code_client, s.code_fournisseur, s.client, s.fournisseur, s.tms, s.status";
			$sql .= $this->db->order("nbfact", "DESC");
			$sql .= $this->db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$datem = $this->db->jdate($objp->tms);
					$thirdpartystatic->id = $objp->rowid;
					$thirdpartystatic->name = $objp->name;
					$thirdpartystatic->code_client = $objp->code_client;
					$thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
					$thirdpartystatic->client = $objp->client;
					$thirdpartystatic->fournisseur = $objp->fournisseur;
					$thirdpartystatic->logo = $objp->logo;
					$nbfact = $objp->nbfact;
					$nbimpaye = $objp->nbfact - $objp->nbfactpaye;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150"',
						'text' => $thirdpartystatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall"',
						'text' => dol_print_date($datem, "day", 'tzuserrel')
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => $nbfact.($nbimpaye != 0 ? ' ('.$nbimpaye.')' : '')
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $thirdpartystatic->LibStatut($objp->status, 3)
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
					'td' => 'class="center"',
					'text' => '<span class="opacitymedium">'.$langs->trans("NoRecordedCustomers").'</span>'
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
