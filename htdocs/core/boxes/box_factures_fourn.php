<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/core/boxes/box_factures_fourn.php
 *      \ingroup    supplier
 *      \brief      Fichier de gestion d'une box des factures fournisseurs
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last supplier invoices
 */
class box_factures_fourn extends ModeleBoxes
{
	public $boxcode = "lastsupplierbills";
	public $boximg = "object_bill";
	public $boxlabel = "BoxLastSupplierBills";
	public $depends = array("facture", "fournisseur");

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

		$this->hidden = !($user->rights->fournisseur->facture->lire);
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

		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

		$facturestatic = new FactureFournisseur($this->db);
		$thirdpartystatic = new Fournisseur($this->db);

		$this->info_box_head = array(
			'text' => $langs->trans("BoxTitleLast".($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE ? "" : "Modified")."SupplierBills", $max)
		);

		if ($user->rights->fournisseur->facture->lire)
		{
			$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= ", f.rowid as facid, f.ref, f.ref_supplier";
			$sql .= ", f.total_ht";
			$sql .= ", f.total_tva";
			$sql .= ", f.total_ttc";
			$sql .= ", f.paye, f.fk_statut";
			$sql .= ', f.datef as df';
			$sql .= ', f.datec as datec';
			$sql .= ', f.date_lim_reglement as datelimite, f.tms, f.type';
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE f.fk_soc = s.rowid";
			$sql .= " AND f.entity = ".$conf->entity;
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
			if ($user->socid) $sql .= " AND s.rowid = ".$user->socid;
			if ($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE) $sql .= " ORDER BY f.datef DESC, f.ref DESC ";
			else $sql .= " ORDER BY f.tms DESC, f.ref DESC ";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);

				$line = 0;
				$l_due_date = $langs->trans('Late').' ('.$langs->trans('DateDue').': %s)';

				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$datelimite = $this->db->jdate($objp->datelimite);
					$date = $this->db->jdate($objp->df);
					$datem = $this->db->jdate($objp->tms);

					$facturestatic->id = $objp->facid;
					$facturestatic->ref = $objp->ref;
					$facturestatic->total_ht = $objp->total_ht;
					$facturestatic->total_tva = $objp->total_tva;
					$facturestatic->total_ttc = $objp->total_ttc;
					$facturestatic->date_echeance = $datelimite;
					$facturestatic->statut = $objp->fk_statut;
					$facturestatic->ref_supplier = $objp->ref_supplier;

					$thirdpartystatic->id = $objp->socid;
					$thirdpartystatic->name = $objp->name;
					$thirdpartystatic->name_alias = $objp->name_alias;
					$thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
					$thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
					$thirdpartystatic->fournisseur = $objp->fournisseur;
					$thirdpartystatic->logo = $objp->logo;
					$thirdpartystatic->email = $objp->email;
					$thirdpartystatic->entity = $objp->entity;

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
						'td' => 'class="tdoverflowmax150"',
						'text' => $objp->ref_supplier,
						'tooltip' => $langs->trans('SupplierInvoice').': '.($objp->ref ? $objp->ref : $objp->facid).'<br>'.$langs->trans('RefSupplier').': '.$objp->ref_supplier,
						'url' => DOL_URL_ROOT."/fourn/facture/card.php?facid=".$objp->facid,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150"',
						'text' => $thirdpartystatic->getNomUrl(1, 'supplier'),
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

					$fac = new FactureFournisseur($this->db);
					$fac->fetch($objp->facid);
					$alreadypaid = $fac->getSommePaiement();
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $facturestatic->LibStatut($objp->paye, $objp->fk_statut, 3, $alreadypaid, $objp->type),
					);

					$line++;
				}

				if ($num == 0)
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text'=>$langs->trans("NoModifiedSupplierBills"),
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
				'text' => $langs->transnoentities("ReadPermissionNotAllowed")
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
