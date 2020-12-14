<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2019 Frederic France      <frederic.france@netlogic.fr>
 * Copyright (C) 2020      Pierre Ardoin        <mapiolca@me.com>
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
 * \file       htdocs/core/boxes/box_propales.php
 * \ingroup    propales
 * \brief      Module de generation de l'affichage de la box propales
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last proposals
 */
class box_propales extends ModeleBoxes
{
	public $boxcode = "lastpropals";
	public $boximg = "object_propal";
	public $boxlabel = "BoxLastProposals";
	public $depends = array("propal"); // conf->propal->enabled

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $param;

	public $info_box_head = array();
	public $info_box_contents = array();


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

		$this->hidden = !($user->rights->propale->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$propalstatic = new Propal($this->db);
		$societestatic = new Societe($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLast".($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE ? "" : "Modified")."Propals", $max));

		if ($user->rights->propale->lire)
		{
			$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_client, s.code_compta, s.client";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= ", p.rowid, p.ref, p.fk_statut, p.datep as dp, p.datec, p.fin_validite, p.date_cloture, p.total_ht, p.tva as total_tva, p.total as total_ttc, p.tms";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql .= ", ".MAIN_DB_PREFIX."propal as p";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE p.fk_soc = s.rowid";
			$sql .= " AND p.entity IN (".getEntity('propal').")";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
			if ($user->socid) $sql .= " AND s.rowid = ".$user->socid;
			if ($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE) $sql .= " ORDER BY p.datep DESC, p.ref DESC ";
			else $sql .= " ORDER BY p.tms DESC, p.ref DESC ";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$now = dol_now();

				$line = 0;

				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$date = $this->db->jdate($objp->dp);
					$datec = $this->db->jdate($objp->datec);
					$datem = $this->db->jdate($objp->tms);
					$dateterm = $this->db->jdate($objp->fin_validite);
					$dateclose = $this->db->jdate($objp->date_cloture);

					$propalstatic->id = $objp->rowid;
					$propalstatic->ref = $objp->ref;
					$propalstatic->total_ht = $objp->total_ht;
					$propalstatic->total_tva = $objp->total_tva;
					$propalstatic->total_ttc = $objp->total_ttc;

					$societestatic->id = $objp->socid;
					$societestatic->name = $objp->name;
					//$societestatic->name_alias = $objp->name_alias;
					$societestatic->code_client = $objp->code_client;
					$societestatic->code_compta = $objp->code_compta;
					$societestatic->client = $objp->client;
					$societestatic->logo = $objp->logo;
					$societestatic->email = $objp->email;
					$societestatic->entity = $objp->entity;

					$late = '';
					if ($objp->fk_statut == 1 && $dateterm < ($now - $conf->propal->cloture->warning_delay)) {
						$late = img_warning($langs->trans("Late"));
					}

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $propalstatic->getNomUrl(1),
						'text2'=> $late,
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $societestatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right nowraponall"',
						'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => dol_print_date($date, 'day'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $propalstatic->LibStatut($objp->fk_statut, 3),
					);

					$line++;
				}

				if ($num == 0)
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text'=>$langs->trans("NoRecordedProposals"),
					);

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength'=>500,
					'text' => ($this->db->error().' sql='.$sql),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover opacitymedium left"',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}

	/**
	 *  Method to show box
	 *
	 *	@param  array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
