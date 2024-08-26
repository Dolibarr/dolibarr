<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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

		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

		$facturestatic = new FactureFournisseur($this->db);
		$thirdpartystatic = new Fournisseur($this->db);

		$text = $langs->trans("BoxTitleLast".(getDolGlobalString('MAIN_LASTBOX_ON_OBJECT_DATE') ? "" : "Modified")."SupplierBills", $max);
		$this->info_box_head = array(
			'text' => $text.'<a class="paddingleft" href="'.DOL_URL_ROOT.'/fourn/facture/list.php?mainmenu=billing&leftmenu=supplier_bills&sortfield=f.tms&sortorder=DESC"><span class="badge">...</span></a>'
		);

		if ($user->hasRight('fournisseur', 'facture', 'lire')) {
			$langs->load("bills");

			$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= ", f.rowid as facid, f.ref, f.ref_supplier";
			$sql .= ", f.total_ht";
			$sql .= ", f.total_tva";
			$sql .= ", f.total_ttc";
			$sql .= ", f.paye, f.fk_statut as status";
			$sql .= ', f.datef as date';
			$sql .= ', f.datec as datec';
			$sql .= ', f.date_lim_reglement as datelimite, f.tms, f.type';
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f";
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= " WHERE f.fk_soc = s.rowid";
			$sql .= " AND f.entity = ".$conf->entity;
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($user->socid) {
				$sql .= " AND s.rowid = ".((int) $user->socid);
			}
			if (getDolGlobalString('MAIN_LASTBOX_ON_OBJECT_DATE')) {
				$sql .= " ORDER BY f.datef DESC, f.ref DESC ";
			} else {
				$sql .= " ORDER BY f.tms DESC, f.ref DESC ";
			}
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				$l_due_date = $langs->trans('Late').' ('.$langs->trans('DateDue').': %s)';

				while ($line < $num) {
					$objp = $this->db->fetch_object($result);

					$datelimite = $this->db->jdate($objp->datelimite);
					$date = $this->db->jdate($objp->date);
					$datem = $this->db->jdate($objp->tms);

					$facturestatic->id = $objp->facid;
					$facturestatic->ref = $objp->ref;
					$facturestatic->total_ht = $objp->total_ht;
					$facturestatic->total_tva = $objp->total_tva;
					$facturestatic->total_ttc = $objp->total_ttc;
					$facturestatic->date = $date;
					$facturestatic->date_echeance = $datelimite;
					$facturestatic->statut = $objp->status;
					$facturestatic->status = $objp->status;
					$facturestatic->ref_supplier = $objp->ref_supplier;

					$alreadypaid = $facturestatic->getSommePaiement();

					$facturestatic->alreadypaid = $alreadypaid ? $alreadypaid : 0;

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
						// @phan-suppress-next-line PhanPluginPrintfVariableFormatString
						$late = img_warning(sprintf($l_due_date, dol_print_date($datelimite, 'day', 'tzuserrel')));
					}

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $facturestatic->getNomUrl(1),
						'text2' => $late,
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
						'td' => 'class="nowraponall right amount"',
						'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datem, 'day', 'tzuserrel'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $facturestatic->LibStatut($objp->paye, $objp->status, 3, $alreadypaid, $objp->type),
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => '<span class="opacitymedium">'.$langs->trans("NoModifiedSupplierBills").'</span>',
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
				'text' => '<span class="opacitymedium">'.$langs->transnoentities("ReadPermissionNotAllowed").'</span>'
			);
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	?array{text?:string,sublink?:string,subpicto:?string,nbcol?:int,limit?:int,subclass?:string,graph?:string}	$head	Array with properties of box title
	 *	@param	?array<array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:string}>>	$contents	Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
