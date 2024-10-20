<?php
/* Copyright (C) 2002-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley			<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2020	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2019	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes			<bafbes@gmail.com>
 * Copyright (C) 2015-2022	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2016-2023	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022      	Gauthier VERDOL     	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023		Nick Fragoulis
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/fourn/class/fournisseur.facture.ligne.class.php
 *  \ingroup    fournisseur,facture
 *  \brief      File of class to manage supplier invoice lines
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}

/**
 *  Class to manage line invoices
 */
class SupplierInvoiceLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'facture_fourn_det';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'facture_fourn_det';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'facture_fourn';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_facture_fourn';

	/**
	 * @var static
	 */
	public $oldline;

	/**
	 * @var string
	 * @deprecated See $product_ref
	 * @see $product_ref
	 */
	public $ref;

	/**
	 * Internal ref
	 * @var string
	 */
	public $product_ref;

	/**
	 * Supplier reference of price when we added the line. May have been changed after line was added.
	 * TODO Rename field ref to ref_supplier into table llx_facture_fourn_det and llx_commande_fournisseurdet and update fields into updateline
	 * @var string
	 */
	public $ref_supplier;

	/**
	 * Product description
	 * @var string
	 */
	public $product_desc;

	/**
	 * Unit price before taxes
	 * @var float
	 * @deprecated Use $subprice
	 * @see $subprice
	 */
	public $pu_ht;

	/**
	 * Unit price excluded taxes
	 * @var float
	 */
	public $subprice;

	/**
	 * Unit price included taxes
	 * @var float
	 */
	public $pu_ttc;


	/**
	 * Id of the corresponding supplier invoice
	 * @var int
	 */
	public $fk_facture_fourn;

	/**
	 * This field may contains label of line (when invoice create from order)
	 * @var string
	 * @deprecated  Use $product_label
	 */
	public $label;

	/**
	 * Description of the line
	 * @var string
	 * @deprecated		Use $desc
	 */
	public $description;

	/**
	 * @var int|string
	 */
	public $date_start;
	/**
	 * @var int|string
	 */
	public $date_end;

	/**
	 * @var int
	 */
	public $fk_code_ventilation;

	/**
	 * @var int<0,1>
	 */
	public $skip_update_total; // Skip update price total for special lines

	/**
	 * @var float 	Situation progress percentage
	 */
	public $situation_percent;

	/**
	 * @var int 	Previous situation line id reference
	 */
	public $fk_prev_id;

	/**
	 * VAT code
	 * @var string
	 */
	public $vat_src_code;

	/**
	 * VAT %
	 * @var float
	 */
	public $tva_tx;

	/**
	 * Local tax 1 %
	 * @var float
	 */
	public $localtax1_tx;

	/**
	 * Local tax 2 %
	 * @var float
	 */
	public $localtax2_tx;

	/**
	 * Quantity
	 * @var float
	 */
	public $qty;

	/**
	 * Percent of discount
	 * @var float|string
	 */
	public $remise_percent;

	/**
	 * Buying price value
	 * @var float
	 */
	public $pa_ht;

	/**
	 * Total amount without taxes
	 * @var float
	 */
	public $total_ht;

	/**
	 * Total amount with taxes
	 * @var float
	 */
	public $total_ttc;

	/**
	 * Total amount of taxes
	 * @var float
	 */
	public $total_tva;

	/**
	 * Total local tax 1 amount
	 * @var float
	 */
	public $total_localtax1;

	/**
	 * Total local tax 2 amount
	 * @var float
	 */
	public $total_localtax2;

	/**
	 * @var int ID
	 */
	public $fk_product;

	/**
	 * Type of the product. 0 for product 1 for service
	 * @var int
	 */
	public $product_type;

	/**
	 * Label of the product
	 * @var string
	 */
	public $product_label;

	/**
	 * List of cumulative options:
	 * Bit 0:	0 si TVA normal - 1 si TVA NPR
	 * Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)
	 * @var int
	 */
	public $info_bits;

	/**
	 * Link to line into llx_remise_except
	 * @var int
	 */
	public $fk_remise_except;

	/**
	 * @var int ID
	 */
	public $fk_parent_line;

	/**
	 * @var int special code
	 */
	public $special_code;

	/**
	 * @var int rank of line
	 */
	public $rang;

	/**
	 * Total local tax 1 amount
	 * @var float
	 */
	public $localtax1_type;

	/**
	 * Total local tax 2 amount
	 * @var float
	 */
	public $localtax2_type;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Retrieves a supplier invoice line
	 *
	 * @param    int    $rowid    Line id
	 * @return   int              Return integer <0 KO; 0 NOT FOUND; 1 OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT f.rowid, f.ref as ref_supplier, f.description as line_desc, f.date_start, f.date_end, f.pu_ht, f.pu_ttc, f.qty, f.remise_percent, f.tva_tx';
		$sql .= ', f.localtax1_type, f.localtax2_type, f.localtax1_tx, f.localtax2_tx, f.total_localtax1, f.total_localtax2, f.fk_remise_except';
		$sql .= ', f.total_ht, f.tva as total_tva, f.total_ttc, f.fk_facture_fourn, f.fk_product, f.product_type, f.info_bits, f.rang, f.special_code, f.fk_parent_line, f.fk_unit';
		$sql .= ', p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.description as product_desc';
		$sql .= ', f.multicurrency_subprice, f.multicurrency_total_ht, f.multicurrency_total_tva, multicurrency_total_ttc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON f.fk_product = p.rowid';
		$sql .= ' WHERE f.rowid = '.((int) $rowid);
		$sql .= ' ORDER BY f.rang, f.rowid';

		$query = $this->db->query($sql);

		if (!$query) {
			$this->errors[] = $this->db->error();
			return -1;
		}

		if (!$this->db->num_rows($query)) {
			return 0;
		}

		$obj = $this->db->fetch_object($query);

		$this->id = $obj->rowid;
		$this->rowid = $obj->rowid;
		$this->fk_facture_fourn = $obj->fk_facture_fourn;
		$this->description		= $obj->line_desc;
		$this->desc				= $obj->line_desc;
		$this->date_start = $obj->date_start;
		$this->date_end = $obj->date_end;
		$this->product_ref		= $obj->product_ref;
		$this->ref_supplier		= $obj->ref_supplier;
		$this->product_desc		= $obj->product_desc;

		$this->subprice = $obj->pu_ht;
		$this->pu_ht = $this->subprice;
		$this->pu_ttc			= $obj->pu_ttc;
		$this->tva_tx			= $obj->tva_tx;
		$this->localtax1_tx		= $obj->localtax1_tx;
		$this->localtax2_tx		= $obj->localtax2_tx;
		$this->localtax1_type	= $obj->localtax1_type;
		$this->localtax2_type	= $obj->localtax2_type;

		$this->qty				= $obj->qty;
		$this->remise_percent = $obj->remise_percent;
		$this->fk_remise_except = $obj->fk_remise_except;
		//$this->tva				= $obj->total_tva; // deprecated
		$this->total_ht = $obj->total_ht;
		$this->total_tva			= $obj->total_tva;
		$this->total_localtax1	= $obj->total_localtax1;
		$this->total_localtax2	= $obj->total_localtax2;
		$this->total_ttc			= $obj->total_ttc;
		$this->fk_product		= $obj->fk_product;
		$this->product_type = $obj->product_type;
		$this->product_label		= $obj->product_label;
		$this->label		= $obj->product_label;
		$this->info_bits		    = $obj->info_bits;
		$this->fk_parent_line    = $obj->fk_parent_line;
		$this->special_code = $obj->special_code;
		$this->rang = $obj->rang;
		$this->fk_unit           = $obj->fk_unit;

		$this->multicurrency_subprice = $obj->multicurrency_subprice;
		$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
		$this->multicurrency_total_tva = $obj->multicurrency_total_tva;
		$this->multicurrency_total_ttc = $obj->multicurrency_total_ttc;

		$this->fetch_optionals();

		return 1;
	}

	/**
	 * Deletes a line
	 *
	 * @param     int   $notrigger     1=Does not execute triggers, 0=execute triggers
	 * @return    int                  0 if KO, 1 if OK
	 */
	public function delete($notrigger = 0)
	{
		global $user;

		dol_syslog(get_class($this)."::deleteline rowid=".((int) $this->id), LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (!$notrigger) {
			if ($this->call_trigger('LINEBILL_SUPPLIER_DELETE', $user) < 0) {
				$error++;
			}
		}

		$this->deleteObjectLinked();

		// Remove extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			// Supprime ligne
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det ';
			$sql .= " WHERE rowid = ".((int) $this->id);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Update a supplier invoice line
	 *
	 * @param int $notrigger Disable triggers
	 * @return int Return integer <0 if KO, >0 if OK
	 */
	public function update($notrigger = 0)
	{
		global $conf;

		$pu = price2num($this->subprice);
		$qty = price2num($this->qty);

		// Check parameters
		if (empty($this->qty)) {
			$this->qty = 0;
		}

		if ($this->product_type < 0) {
			return -1;
		}

		// Clean parameters
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}
		if (empty($this->tva_tx)) {
			$this->tva_tx = 0;
		}
		if (empty($this->localtax1_tx)) {
			$this->localtax1_tx = 0;
		}
		if (empty($this->localtax2_tx)) {
			$this->localtax2_tx = 0;
		}

		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}
		if (empty($this->multicurrency_subprice)) {
			$this->multicurrency_subprice = 0;
		}
		if (empty($this->multicurrency_total_ht)) {
			$this->multicurrency_total_ht = 0;
		}
		if (empty($this->multicurrency_total_tva)) {
			$this->multicurrency_total_tva = 0;
		}
		if (empty($this->multicurrency_total_ttc)) {
			$this->multicurrency_total_ttc = 0;
		}

		$fk_product = (int) $this->fk_product;
		$fk_unit = (int) $this->fk_unit;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn_det SET";
		$sql .= " description = '".$this->db->escape(empty($this->description) ? $this->desc : $this->description)."'";
		$sql .= ", ref = '".$this->db->escape($this->ref_supplier ? $this->ref_supplier : $this->ref)."'";
		$sql .= ", date_start = ".($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : "null");
		$sql .= ", date_end = ".($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", pu_ht = ".price2num($this->subprice);
		$sql .= ", pu_ttc = ".price2num($this->pu_ttc);
		$sql .= ", qty = ".price2num($this->qty);
		$sql .= ", remise_percent = ".price2num($this->remise_percent);
		if ($this->fk_remise_except > 0) {
			$sql .= ", fk_remise_except=".((int) $this->fk_remise_except);
		} else {
			$sql .= ", fk_remise_except=null";
		}
		$sql .= ", vat_src_code = '".$this->db->escape(empty($this->vat_src_code) ? '' : $this->vat_src_code)."'";
		$sql .= ", tva_tx = ".price2num($this->tva_tx);
		$sql .= ", localtax1_tx = ".price2num($this->localtax1_tx);
		$sql .= ", localtax2_tx = ".price2num($this->localtax2_tx);
		$sql .= ", localtax1_type = '".$this->db->escape($this->localtax1_type)."'";
		$sql .= ", localtax2_type = '".$this->db->escape($this->localtax2_type)."'";
		$sql .= ", total_ht = ".price2num($this->total_ht);
		$sql .= ", tva= ".price2num($this->total_tva);
		$sql .= ", total_localtax1= ".price2num($this->total_localtax1);
		$sql .= ", total_localtax2= ".price2num($this->total_localtax2);
		$sql .= ", total_ttc = ".price2num($this->total_ttc);
		$sql .= ", fk_product = ".($fk_product > 0 ? (int) $fk_product : 'null');
		$sql .= ", product_type = ".((int) $this->product_type);
		$sql .= ", info_bits = ".((int) $this->info_bits);
		$sql .= ", fk_unit = ".($fk_unit > 0 ? (int) $fk_unit : 'null');

		if (!empty($this->rang)) {
			$sql .= ", rang=".((int) $this->rang);
		}

		// Multicurrency
		$sql .= " , multicurrency_subprice=".price2num($this->multicurrency_subprice);
		$sql .= " , multicurrency_total_ht=".price2num($this->multicurrency_total_ht);
		$sql .= " , multicurrency_total_tva=".price2num($this->multicurrency_total_tva);
		$sql .= " , multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc);

		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}

		$this->rowid = $this->id;
		$error = 0;

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			global $langs, $user;

			// Call trigger
			if ($this->call_trigger('LINEBILL_SUPPLIER_MODIFY', $user) < 0) {
				$this->db->rollback();
				return -1;
			}
			// End call triggers
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      int		$notrigger							1 no triggers
	 *  @param      int     $noerrorifdiscountalreadylinked  	1=Do not make error if lines is linked to a discount and discount already linked to another
	 *	@return		int											Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0, $noerrorifdiscountalreadylinked = 0)
	{
		global $user, $langs;

		$error = 0;

		dol_syslog(get_class($this)."::insert rang=".$this->rang, LOG_DEBUG);

		// Clean parameters
		$this->desc = trim($this->desc);
		if (empty($this->tva_tx)) {
			$this->tva_tx = 0;
		}
		if (empty($this->localtax1_tx)) {
			$this->localtax1_tx = 0;
		}
		if (empty($this->localtax2_tx)) {
			$this->localtax2_tx = 0;
		}
		if (empty($this->localtax1_type)) {
			$this->localtax1_type = 0.0;
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = 0.0;
		}
		if (empty($this->total_tva)) {
			$this->total_tva = 0;
		}
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->rang)) {
			$this->rang = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}
		if (empty($this->info_bits)) {
			$this->info_bits = 0;
		}
		if (empty($this->subprice)) {
			$this->subprice = 0;
		}
		if (empty($this->special_code)) {
			$this->special_code = 0;
		}
		if (empty($this->fk_parent_line)) {
			$this->fk_parent_line = 0;
		}
		if (!isset($this->situation_percent) || $this->situation_percent > 100 || (string) $this->situation_percent == '') {
			$this->situation_percent = 100;
		}

		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}
		if (empty($this->multicurrency_subprice)) {
			$this->multicurrency_subprice = 0;
		}
		if (empty($this->multicurrency_total_ht)) {
			$this->multicurrency_total_ht = 0;
		}
		if (empty($this->multicurrency_total_tva)) {
			$this->multicurrency_total_tva = 0;
		}
		if (empty($this->multicurrency_total_ttc)) {
			$this->multicurrency_total_ttc = 0;
		}


		// Check parameters
		if ($this->product_type < 0) {
			$this->error = 'ErrorProductTypeMustBe0orMore';
			return -1;
		}
		if (!empty($this->fk_product) && $this->fk_product > 0) {
			// Check product exists
			$result = Product::isExistingObject('product', $this->fk_product);
			if ($result <= 0) {
				$this->error = 'ErrorProductIdDoesNotExists';
				return -1;
			}
		}

		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= ' (fk_facture_fourn, fk_parent_line, label, description, ref, qty,';
		$sql .= ' vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql .= ' fk_product, product_type, remise_percent, fk_remise_except, pu_ht, pu_ttc,';
		$sql .= ' date_start, date_end, fk_code_ventilation, rang, special_code,';
		$sql .= ' info_bits, total_ht, tva, total_ttc, total_localtax1, total_localtax2, fk_unit';
		$sql .= ', fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
		$sql .= ')';
		$sql .= " VALUES (".$this->fk_facture_fourn.",";
		$sql .= " ".($this->fk_parent_line > 0 ? "'".$this->db->escape($this->fk_parent_line)."'" : "null").",";
		$product_label
			= !empty($this->product_label)
			? $this->product_label :
			(!empty($this->label) ? $this->label : null);
		$sql .= " ".(!empty($product_label) ? "'".$this->db->escape($product_label)."'" : "null").",";
		$sql .= " '".$this->db->escape($this->desc ? $this->desc : $this->description)."',";
		$sql .= " '".$this->db->escape($this->ref_supplier)."',";
		$sql .= " ".price2num($this->qty).",";

		$sql .= " ".(empty($this->vat_src_code) ? "''" : "'".$this->db->escape($this->vat_src_code)."'").",";
		$sql .= " ".price2num($this->tva_tx).",";
		$sql .= " ".price2num($this->localtax1_tx).",";
		$sql .= " ".price2num($this->localtax2_tx).",";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= ' '.((!empty($this->fk_product) && $this->fk_product > 0) ? $this->fk_product : "null").',';
		$sql .= " ".((int) $this->product_type).",";
		$sql .= " ".price2num($this->remise_percent).",";
		$sql .= ' '.(!empty($this->fk_remise_except) ? ((int) $this->fk_remise_except) : "null").',';
		$sql .= " ".price2num($this->subprice).",";
		$sql .= " ".(!empty($this->qty) ? price2num($this->total_ttc / $this->qty) : price2num($this->total_ttc)).",";
		$sql .= " ".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null").",";
		$sql .= " ".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		$sql .= ' '.(!empty($this->fk_code_ventilation) ? $this->fk_code_ventilation : 0).',';
		$sql .= ' '.((int) $this->rang).',';
		$sql .= ' '.((int) $this->special_code).',';
		$sql .= " ".((int) $this->info_bits).",";
		$sql .= " ".price2num($this->total_ht).",";
		$sql .= " ".price2num($this->total_tva).",";
		$sql .= " ".price2num($this->total_ttc).",";
		$sql .= " ".price2num($this->total_localtax1).",";
		$sql .= " ".price2num($this->total_localtax2);
		$sql .= ", ".(!$this->fk_unit ? 'NULL' : $this->fk_unit);
		$sql .= ", ".(int) $this->fk_multicurrency;
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".price2num($this->multicurrency_subprice);
		$sql .= ", ".price2num($this->multicurrency_total_ht);
		$sql .= ", ".price2num($this->multicurrency_total_tva);
		$sql .= ", ".price2num($this->multicurrency_total_ttc);
		$sql .= ')';

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$this->rowid = $this->id; // backward compatibility

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			// Si fk_remise_except defini, on lie la remise a la facture
			// ce qui la flague comme "consommee".
			if ($this->fk_remise_except) {
				$discount = new DiscountAbsolute($this->db);
				$result = $discount->fetch($this->fk_remise_except);
				if ($result >= 0) {
					// Check if discount was found
					if ($result > 0) {
						// Check if discount not already affected to another invoice
						if ($discount->fk_facture_line > 0) {
							if (empty($noerrorifdiscountalreadylinked)) {
								$this->error = $langs->trans("ErrorDiscountAlreadyUsed", $discount->id);
								dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
								$this->db->rollback();
								return -3;
							}
						} else {
							$result = $discount->link_to_invoice($this->id, 0);
							if ($result < 0) {
								$this->error = $discount->error;
								dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
								$this->db->rollback();
								return -3;
							}
						}
					} else {
						$this->error = $langs->trans("ErrorADiscountThatHasBeenRemovedIsIncluded");
						dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
						$this->db->rollback();
						return -3;
					}
				} else {
					$this->error = $discount->error;
					dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -3;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEBILL_SUPPLIER_CREATE', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -2;
				}
				// End call triggers
			}

			$this->db->commit();
			return $this->id;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Mise a jour de l'objet ligne de commande en base
	 *
	 *  @return		int		Return integer <0 si ko, >0 si ok
	 */
	public function update_total()
	{
		// phpcs:enable
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn_det SET";
		$sql .= "  total_ht = ".price2num($this->total_ht);
		$sql .= ", tva= ".price2num($this->total_tva);
		$sql .= ", total_localtax1 = ".price2num($this->total_localtax1);
		$sql .= ", total_localtax2 = ".price2num($this->total_localtax2);
		$sql .= ", total_ttc = ".price2num($this->total_ttc);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("FactureFournisseurLigne.class.php::update_total", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}
}
