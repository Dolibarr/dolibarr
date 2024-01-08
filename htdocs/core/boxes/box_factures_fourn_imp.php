<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/boxes/box_factures_fourn_imp.php
 *      \ingroup    fournisseur
 *      \brief      Widget to show remain to get on purchase invoices
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show not paid suppliers invoices
 */
class box_factures_fourn_imp extends ModeleBoxes
{
	public $boxcode = "oldestunpaidsupplierbills";
	public $boximg = "object_bill";
	public $boxlabel = "BoxOldestUnpaidSupplierBills";
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

		$this->hidden = !($user->hasRight('fournisseur', 'facture', 'lire'));
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
		//$this->max = 1000;

		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

		$facturestatic = new FactureFournisseur($this->db);
		$thirdpartystatic = new Fournisseur($this->db);

		$langs->load("bills");

		$this->info_box_head = array('text' => $langs->trans("BoxTitleOldestUnpaidSupplierBills", $this->max));

		if ($user->hasRight('fournisseur', 'facture', 'lire')) {
			$sql1 = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql1 .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
			$sql1 .= ", s.logo, s.email, s.entity, s.tva_intra, s.siren, s.siret, s.ape, s.idprof4, s.idprof5, s.idprof6";
			$sql1 .= ", f.rowid as facid, f.ref, f.ref_supplier, f.date_lim_reglement as datelimite";
			$sql1 .= ", f.datef as df";
			$sql1 .= ", f.total_ht";
			$sql1 .= ", f.total_tva";
			$sql1 .= ", f.total_ttc";
			$sql1 .= ", f.paye, f.fk_statut as status, f.type";
			$sql1 .= ", f.tms";
			$sql1 .= ", SUM(pf.amount) as am";
			$sql2 = " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql2 .= ",".MAIN_DB_PREFIX."facture_fourn as f";
			$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf ON f.rowid = pf.fk_facturefourn";
			if (!$user->hasRight('societe', 'client', 'voir') && !$user->socid) {
				$sql2 .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql2 .= " WHERE f.fk_soc = s.rowid";
			$sql2 .= " AND f.entity IN (".getEntity('supplier_invoice').")";
			$sql2 .= " AND f.paye = 0";
			$sql2 .= " AND fk_statut = 1";
			if (!$user->hasRight('societe', 'client', 'voir') && !$user->socid) {
				$sql2 .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($user->socid) {
				$sql2 .= " AND s.rowid = ".((int) $user->socid);
			}
			$sql3 = " GROUP BY s.rowid, s.nom, s.name_alias, s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur, s.logo, s.email, s.entity, s.tva_intra, s.siren, s.siret, s.ape, s.idprof4, s.idprof5, s.idprof6,";
			$sql3 .= " f.rowid, f.ref, f.ref_supplier, f.date_lim_reglement,";
			$sql3 .= " f.type, f.datef, f.total_ht, f.total_tva, f.total_ttc, f.paye, f.fk_statut, f.tms";
			$sql3 .= " ORDER BY datelimite DESC, f.ref_supplier DESC ";
			$sql3 .= $this->db->plimit($this->max + 1, 0);

			$sql = $sql1.$sql2.$sql3;

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				$l_due_date = $langs->trans('Late').' ('.strtolower($langs->trans('DateDue')).': %s)';

				while ($line < min($num, $this->max)) {
					$objp = $this->db->fetch_object($result);

					$datelimite = $this->db->jdate($objp->datelimite);
					$date = $this->db->jdate($objp->df);
					$datem = $this->db->jdate($objp->tms);

					$facturestatic->id = $objp->facid;
					$facturestatic->ref = $objp->ref;
					$facturestatic->type = $objp->type;
					$facturestatic->total_ht = $objp->total_ht;
					$facturestatic->total_tva = $objp->total_tva;
					$facturestatic->total_ttc = $objp->total_ttc;
					$facturestatic->date = $date;
					$facturestatic->date_echeance = $datelimite;
					$facturestatic->statut = $objp->status;
					$facturestatic->status = $objp->status;

					//$alreadypaid = $facturestatic->getSommePaiement();

					$facturestatic->paye = $objp->paye;
					$facturestatic->alreadypaid = $objp->am;

					$thirdpartystatic->id = $objp->socid;
					$thirdpartystatic->name = $objp->name;
					$thirdpartystatic->name_alias = $objp->name_alias;
					$thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
					$thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
					$thirdpartystatic->fournisseur = $objp->fournisseur;
					$thirdpartystatic->logo = $objp->logo;
					$thirdpartystatic->email = $objp->email;
					$thirdpartystatic->entity = $objp->entity;
					$thirdpartystatic->tva_intra = $objp->tva_intra;
					$thirdpartystatic->idprof1 = !empty($objp->idprof1) ? $objp->idprof1 : '';
					$thirdpartystatic->idprof2 = !empty($objp->idprof2) ? $objp->idprof2 : '';
					$thirdpartystatic->idprof3 = !empty($objp->idprof3) ? $objp->idprof3 : '';
					$thirdpartystatic->idprof4 = !empty($objp->idprof4) ? $objp->idprof4 : '';
					$thirdpartystatic->idprof5 = !empty($objp->idprof5) ? $objp->idprof5 : '';
					$thirdpartystatic->idprof6 = !empty($objp->idprof6) ? $objp->idprof6 : '';

					$late = '';
					if ($facturestatic->hasDelay()) {
						$late = img_warning(sprintf($l_due_date, dol_print_date($datelimite, 'day', 'tzuserrel')));
					}

					$tooltip = $langs->trans('SupplierInvoice').': '.($objp->ref ? $objp->ref : $objp->facid).'<br>'.$langs->trans('RefSupplier').': '.$objp->ref_supplier;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $facturestatic->getNomUrl(1),
						'text2'=> $late,
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $thirdpartystatic->getNomUrl(1, '', 44),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateDue").': '.dol_print_date($datelimite, 'day', 'tzuserrel')).'"',
						'text' => dol_print_date($datelimite, 'day', 'tzuserrel'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $facturestatic->LibStatut($objp->paye, $objp->status, 3, $objp->am, $objp->type),
					);

					$line++;
				}
				if ($this->max < $num) {
					$this->info_box_contents[$line][] = array('td' => 'colspan="6"', 'text' => '...');
					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text'=> '<span class="opacitymedium">'.$langs->trans("NoUnpaidSupplierBills").'</span>',
					);
				}

				$sql = "SELECT SUM(f.total_ht) as total_ht ".$sql2;

				$result = $this->db->query($sql);
				$objp = $this->db->fetch_object($result);
				$totalamount = $objp->total_ht;

				// Add the sum à the bottom of the boxes
				$this->info_box_contents[$line][] = array(
					'tr' => 'class="liste_total_wrap"',
					'td' => 'class="liste_total"',
					'text' => $langs->trans("Total"),
				);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="liste_total"',
					'text' => "&nbsp;",
				);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right liste_total" ',
					'text' => price($totalamount, 0, $langs, 0, -1, -1, $conf->currency),
				);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="liste_total"',
					'text' => "&nbsp;",
				);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="liste_total"',
					'text' => "&nbsp;",
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
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
