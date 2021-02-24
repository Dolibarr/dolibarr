<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2019 Frederic France      <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/boxes/box_factures_imp.php
 *	\ingroup    factures
 *	\brief      Module de generation de l'affichage de la box factures impayees
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';


/**
 * Class to manage the box to show last invoices
 */
class box_factures_imp extends ModeleBoxes
{
	public $boxcode = "oldestunpaidcustomerbills";
	public $boximg = "object_bill";
	public $boxlabel = "BoxOldestUnpaidCustomerBills";
	public $depends = array("facture");

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

		$this->hidden = !($user->rights->facture->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

		$facturestatic = new Facture($this->db);
		$societestatic = new Societe($this->db);

		$langs->load("bills");

		$this->info_box_head = array('text' => $langs->trans("BoxTitleOldestUnpaidCustomerBills", $max));

		if ($user->rights->facture->lire)
		{
			$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_client, s.code_compta, s.client";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= ", s.tva_intra, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4, s.idprof5, s.idprof6";
			$sql .= ", f.ref, f.date_lim_reglement as datelimite";
			$sql .= ", f.type";
			$sql .= ", f.datef as df";
			$sql .= ", f.total as total_ht";
			$sql .= ", f.tva as total_tva";
			$sql .= ", f.total_ttc";
			$sql .= ", f.paye, f.fk_statut, f.rowid as facid";
			$sql .= ", sum(pf.amount) as am";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= ", ".MAIN_DB_PREFIX."facture as f";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid=pf.fk_facture ";
			$sql .= " WHERE f.fk_soc = s.rowid";
			$sql .= " AND f.entity IN (".getEntity('invoice').")";
			$sql .= " AND f.paye = 0";
			$sql .= " AND fk_statut = 1";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
			if ($user->socid) $sql .= " AND s.rowid = ".$user->socid;
			$sql .= " GROUP BY s.rowid, s.nom, s.name_alias, s.code_client, s.code_compta, s.client, s.logo, s.email, s.entity, s.tva_intra, s.siren, s.siret, s.ape, s.idprof4, s.idprof5, s.idprof6,";
			$sql .= " f.ref, f.date_lim_reglement,";
			$sql .= " f.type, f.datef, f.total, f.tva, f.total_ttc, f.paye, f.fk_statut, f.rowid";
			//$sql.= " ORDER BY f.datef DESC, f.ref DESC ";
			$sql .= " ORDER BY datelimite ASC, f.ref ASC ";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$now = dol_now();

				$line = 0;
				$l_due_date = $langs->trans('Late').' ('.strtolower($langs->trans('DateDue')).': %s)';

				while ($line < $num)
				{
					$objp = $this->db->fetch_object($result);
					$datelimite = $this->db->jdate($objp->datelimite);
					$facturestatic->id = $objp->facid;
					$facturestatic->ref = $objp->ref;
					$facturestatic->type = $objp->type;
					$facturestatic->total_ht = $objp->total_ht;
					$facturestatic->total_tva = $objp->total_tva;
					$facturestatic->total_ttc = $objp->total_ttc;
					$facturestatic->statut = $objp->fk_statut;
					$facturestatic->date_lim_reglement = $this->db->jdate($objp->datelimite);

					$societestatic->id = $objp->socid;
					$societestatic->name = $objp->name;
					//$societestatic->name_alias = $objp->name_alias;
					$societestatic->code_client = $objp->code_client;
					$societestatic->code_compta = $objp->code_compta;
					$societestatic->client = $objp->client;
					$societestatic->logo = $objp->logo;
					$societestatic->email = $objp->email;
					$societestatic->entity = $objp->entity;
					$societestatic->tva_intra = $objp->tva_intra;
					$societestatic->idprof1 = $objp->idprof1;
					$societestatic->idprof2 = $objp->idprof2;
					$societestatic->idprof3 = $objp->idprof3;
					$societestatic->idprof4 = $objp->idprof4;
					$societestatic->idprof5 = $objp->idprof5;
					$societestatic->idprof6 = $objp->idprof6;

					$late = '';
					if ($facturestatic->hasDelay()) {
						$late = img_warning(sprintf($l_due_date, dol_print_date($datelimite, 'day')));
					}

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $facturestatic->getNomUrl(1),
						'text2'=> $late,
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $societestatic->getNomUrl(1, '', 44),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right"',
						'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => dol_print_date($datelimite, 'day'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $facturestatic->LibStatut($objp->paye, $objp->fk_statut, 3, $objp->am),
					);

					$line++;
				}

				if ($num == 0) $this->info_box_contents[$line][0] = array(
					'td' => 'class="center opacitymedium"',
					'text'=>$langs->trans("NoUnpaidCustomerBills")
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
