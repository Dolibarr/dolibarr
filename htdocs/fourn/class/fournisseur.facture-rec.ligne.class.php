<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			    <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Juanjo Menent			    <jmenent@2byte.es>
 * Copyright (C) 2012       Cedric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry		  	  <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2017-2024  Frédéric France       <frederic.france@free.fr>
 * Copyright (C) 2024		    MDW							      <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2023-2024  Nick Fragoulis
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
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
 *	\file       htdocs/fourn/facture/class/fournisseur.facture-rec.ligne.class.php
 *	\ingroup    invoice
 *	\brief      File for class to manage invoice template lines
 */

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.ligne.class.php';

/**
 *	Class to manage supplier invoice lines of templates.
 *  Saved into database table llx_facture_fourn_det_rec
 */
class FactureFournisseurLigneRec extends CommonInvoiceLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'invoice_supplier_det_rec';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'facture_fourn_det_rec';

	/**
	 * @var int
	 */
	public $fk_facture_fourn;

	/**
	 * @var int
	 */
	public $fk_parent;

	/**
	 * @var int
	 */
	public $fk_product;

	/**
	 * @var string
	 */
	public $ref_supplier;

	/**
	 * @var string
	 */
	public $label;
	/**
	 * @deprecated	Use desc
	 * @var string
	 */
	public $description;

	/**
	 * @var float
	 */
	public $pu_ht;

	/**
	 * @var float
	 */
	public $pu_ttc;

	/**
	 * @var float Quantity
	 */
	public $qty;

	/**
	 * @var float
	 */
	public $remise_percent;

	/**
	 * @var int
	 */
	public $fk_remise_except;

	/**
	 * @var string
	 */
	public $vat_src_code;

	/**
	 * @var string|float
	 */
	public $tva_tx;

	/**
	 * @var float
	 */
	public $localtax1_tx;

	/**
	 * @var int<0, 6>
	 */
	public $localtax1_type;

	/**
	 * @var float
	 */
	public $localtax2_tx;

	/**
	 * @var int<0, 6>
	 */
	public $localtax2_type;

	/**
	 * @var int
	 */
	public $product_type;

	/**
	 * @var int
	 */
	public $date_start;

	/**
	 * @var int
	 */
	public $date_end;

	/**
	 * @var int
	 */
	public $info_bits;

	/**
	 * @var int special code
	 */
	public $special_code;

	/**
	 * @var int
	 */
	public $rang;

	/**
	 * @var int
	 */
	public $fk_user_author;

	/**
	 * @var int
	 */
	public $fk_user_modif;

	/**
	 * @var int
	 */
	public $skip_update_total;


	/**
	 *    Delete supplier order template line in database
	 *
	 * @param User $user Object user
	 * @param int $notrigger Disable triggers
	 * @return        int                    Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		$error = 0;
		$this->db->begin();

		if (! $error) {
			if (! $notrigger) {
				// Call triggers
				$result = $this->call_trigger('LINESUPPLIERBILLREC_DELETE', $user);
				if ($result < 0) {
					$error++;
				} // Do also here what you must do to rollback action if trigger fail
				// End call triggers
			}
		}

		if (! $error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (! $error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element . ' WHERE rowid=' . (int) $this->id;

			$res = $this->db->query($sql);
			if ($res === false) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *	Get line of template invoice
	 *
	 *	@param		int 	$rowid		Id of invoice
	 *	@return     int         		1 if OK, < 0 if KO
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT l.rowid,';
		$sql .= ' l.fk_facture_fourn, l.fk_parent_line, l.fk_product,';
		$sql .= ' l.ref as ref_supplier, l.label, l.description as line_desc, l.pu_ht, l.pu_ttc, l.qty, l.remise_percent, l.fk_remise_except,';
		$sql .= ' l.vat_src_code, l.tva_tx, l.localtax1_tx, l.localtax1_type, l.localtax2_tx, l.localtax2_type,';
		$sql .= ' l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc,';
		$sql .= ' l.product_type, l.date_start, l.date_end,';
		$sql .= ' l.info_bits, l.special_code, l.rang, l.fk_unit, l.import_key,';
		$sql .= ' l.fk_user_author, l.fk_user_modif, l.fk_multicurrency,';
		$sql .= ' l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
		$sql .= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det_rec as l';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql .= ' WHERE l.rowid = '. (int) $rowid;
		$sql .= ' ORDER BY l.rang';

		dol_syslog('FactureRec::fetch', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			$this->id                       = $objp->rowid;
			$this->fk_facture_fourn         = $objp->fk_facture_fourn;
			$this->fk_parent                = $objp->fk_parent_line;
			$this->fk_product               = $objp->fk_product;
			$this->ref_supplier             = $objp->ref_supplier;
			$this->label                    = $objp->label;
			$this->description              = $objp->line_desc;
			$this->desc			            = $objp->line_desc;
			$this->pu_ht                    = $objp->pu_ht;
			$this->pu_ttc                   = $objp->pu_ttc;
			$this->qty                      = $objp->qty;
			$this->remise_percent           = $objp->remise_percent;
			$this->fk_remise_except         = $objp->fk_remise_except;
			$this->vat_src_code             = $objp->vat_src_code;
			$this->tva_tx                   = $objp->tva_tx;
			$this->localtax1_tx             = $objp->localtax1_tx;
			$this->localtax1_type           = $objp->localtax1_type;
			$this->localtax2_tx             = $objp->localtax2_tx;
			$this->localtax2_type           = $objp->localtax2_type;
			$this->total_ht                 = $objp->total_ht;
			$this->total_tva                = $objp->total_tva;
			$this->total_localtax1          = $objp->total_localtax1;
			$this->total_localtax2          = $objp->total_localtax2;
			$this->total_ttc                = $objp->total_ttc;
			$this->product_type             = $objp->product_type;
			$this->date_start               = $objp->date_start;
			$this->date_end                 = $objp->date_end;
			$this->info_bits                = $objp->info_bits;
			$this->special_code             = $objp->special_code;
			$this->rang                     = $objp->rang;
			$this->fk_unit                  = $objp->fk_unit;
			$this->import_key               = $objp->import_key;
			$this->fk_user_author           = $objp->fk_user_author;
			$this->fk_user_modif            = $objp->fk_user_modif;
			$this->fk_multicurrency         = $objp->fk_multicurrency;
			$this->multicurrency_code       = $objp->multicurrency_code;
			$this->multicurrency_subprice   = $objp->multicurrency_subprice;
			$this->multicurrency_total_ht   = $objp->multicurrency_total_ht;
			$this->multicurrency_total_tva  = $objp->multicurrency_total_tva;
			$this->multicurrency_total_ttc  = $objp->multicurrency_total_ttc;

			$this->db->free($result);
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -3;
		}
	}


	/**
	 * 	Update a line to supplier invoice template .
	 *
	 *  @param		User	$user					User
	 *  @param		int		$notrigger				No trigger
	 *	@return    	int             				Return integer <0 if KO, Id of line if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facture_fourn_det_rec SET';
		$sql .= ' fk_facture_fourn = ' . (int) $this->fk_facture_fourn;
		$sql .= ', fk_parent_line = ' . (int) $this->fk_parent;
		$sql .= ', fk_product = ' . (int) $this->fk_product;
		$sql .= ', ref = ' . (!empty($this->ref) ? "'" . $this->db->escape($this->ref) . "'" : 'NULL');
		$sql .= ", label = " . (!empty($this->label) ? "'" . $this->db->escape($this->label) . "'" : 'NULL');
		$sql .= ", description = '" . $this->db->escape($this->desc ? $this->desc : $this->description) . "'";
		$sql .= ', pu_ht = ' . price2num($this->pu_ht);
		$sql .= ', pu_ttc = ' . price2num($this->pu_ttc);
		$sql .= ', qty = ' . price2num($this->qty);
		$sql .= ", remise_percent = '" . price2num($this->remise_percent) . "'";
		$sql .= ', fk_remise_except = ' . (int) $this->fk_remise_except;
		$sql .= ", vat_src_code = '" . $this->db->escape($this->vat_src_code) . "'";
		$sql .= ', tva_tx = ' . price2num($this->tva_tx);
		$sql .= ', localtax1_tx = ' . price2num($this->localtax1_tx);
		$sql .= ", localtax1_type = '" . $this->db->escape($this->localtax1_type) . "'";
		$sql .= ', localtax2_tx = ' . price2num($this->localtax2_tx);
		$sql .= ", localtax2_type = '" . $this->db->escape($this->localtax2_type) . "'";
		if (empty($this->skip_update_total)) {
			$sql .= ', total_ht = ' . price2num($this->total_ht);
			$sql .= ', total_tva = ' . price2num($this->total_tva);
			$sql .= ', total_localtax1 = ' . price2num($this->total_localtax1);
			$sql .= ', total_localtax2 = ' . price2num($this->total_localtax2);
			$sql .= ', total_ttc = ' . price2num($this->total_ttc);
		}
		$sql .= ', product_type = ' . (int) $this->product_type;
		$sql .= ', date_start = ' . (int) $this->date_start;
		$sql .= ', date_end = ' . (int) $this->date_end;
		$sql .= ", info_bits = " . ((int) $this->info_bits);
		$sql .= ', special_code =' . (int) $this->special_code;
		$sql .= ', rang = ' . (int) $this->rang;
		$sql .= ', fk_unit = ' .($this->fk_unit ? "'".$this->db->escape($this->fk_unit)."'" : 'null');
		$sql .= ', fk_user_modif = ' . (int) $user->id;
		$sql .= ' WHERE rowid = ' . (int) $this->id;

		$this->db->begin();

		dol_syslog(get_class($this). '::updateline', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINESUPPLIERBILLREC_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if ($error) {
				$this->db->rollback();
				return -2;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -2;
		}
	}
}
