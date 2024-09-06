<?php
/* Copyright (C) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2014  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2020  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2014  Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Cedric Gross            <c.gross@kreiz-it.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2016-2022  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2022       Sylvain Legrand         <contact@infras.fr>
 * Copyright (C) 2023      	Gauthier VERDOL       	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023		Nick Fragoulis
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/compta/facture/class/factureligne.class.php
 *	\ingroup    invoice
 *	\brief      File of class to manage invoice lines
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonoinvoiceline.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

/**
 *	Class to manage invoice lines.
 *  Saved into database table llx_facturedet
 */
class FactureLigne extends CommonInvoiceLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'facturedet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'facturedet';

	/**
	 * @var FactureLigne
	 */
	public $oldline;

	//! From llx_facturedet
	/**
	 * @var int Id facture
	 */
	public $fk_facture;
	/**
	 * @var int Id parent line
	 */
	public $fk_parent_line;

	/**
	 * @var string Description ligne
	 */
	public $desc;
	/**
	 * @var string External reference of the line
	 */
	public $ref_ext;

	/**
	 * @var int<0,6>
	 */
	public $localtax1_type; // Local tax 1 type
	/**
	 * @var int<0,6>
	 */
	public $localtax2_type; // Local tax 2 type
	/**
	 * @var int
	 */
	public $fk_remise_except; // Link to line into llx_remise_except
	/**
	 * @var int
	 */
	public $rang = 0;
	/**
	 * @var int
	 */
	public $fk_fournprice;
	/**
	 * @var string|int|float
	 */
	public $pa_ht;
	/**
	 * @var string
	 */
	public $marge_tx;
	/**
	 * @var string
	 */
	public $marque_tx;

	/**
	 * @var int
	 */
	public $tva_npr;

	/**
	 * @var float
	 */
	public $remise_percent;

	/**
	 * @var string		To store the batch to consume in stock when using a POS module
	 */
	public $batch;
	/**
	 * @var int		To store the warehouse where to consume stock when using a POS module
	 */
	public $fk_warehouse;


	/**
	 * @var string
	 */
	public $origin;
	/**
	 * @var int
	 */
	public $origin_id;

	/**
	 * @var int		Id in table llx_accounting_bookeeping to know accounting account for product line
	 */
	public $fk_code_ventilation = 0;


	/**
	 * @var string|int
	 */
	public $date_start;
	/**
	 * @var string|int
	 */
	public $date_end;

	/**
	 * @var int<0,1>
	 */
	public $skip_update_total; // Skip update price total for special lines

	/**
	 * @var float 		Situation advance percentage (default 100 for standard invoices)
	 */
	public $situation_percent;

	/**
	 * @var int 		Previous situation line id reference
	 */
	public $fk_prev_id;


	/**
	 *      Constructor
	 *
	 *      @param     DoliDB	$db      handler d'acces base de donnee
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Load invoice line from database
	 *
	 *	@param	int		$rowid      id of invoice line to get
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT fd.rowid, fd.fk_facture, fd.fk_parent_line, fd.fk_product, fd.product_type, fd.label as custom_label, fd.description, fd.price, fd.qty, fd.vat_src_code, fd.tva_tx,';
		$sql .= ' fd.localtax1_tx, fd. localtax2_tx, fd.remise, fd.remise_percent, fd.fk_remise_except, fd.subprice, fd.ref_ext,';
		$sql .= ' fd.date_start as date_start, fd.date_end as date_end, fd.fk_product_fournisseur_price as fk_fournprice, fd.buy_price_ht as pa_ht,';
		$sql .= ' fd.info_bits, fd.special_code, fd.total_ht, fd.total_tva, fd.total_ttc, fd.total_localtax1, fd.total_localtax2, fd.rang,';
		$sql .= ' fd.fk_code_ventilation,';
		$sql .= ' fd.fk_unit, fd.fk_user_author, fd.fk_user_modif,';
		$sql .= ' fd.situation_percent, fd.fk_prev_id,';
		$sql .= ' fd.multicurrency_subprice,';
		$sql .= ' fd.multicurrency_total_ht,';
		$sql .= ' fd.multicurrency_total_tva,';
		$sql .= ' fd.multicurrency_total_ttc,';
		$sql .= ' p.ref as product_ref, p.label as product_label, p.description as product_desc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facturedet as fd';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON fd.fk_product = p.rowid';
		$sql .= ' WHERE fd.rowid = '.((int) $rowid);

		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			if (!$objp) {
				$this->error = 'InvoiceLine with id '. $rowid .' not found sql='.$sql;
				return 0;
			}

			$this->rowid = $objp->rowid;
			$this->id = $objp->rowid;
			$this->fk_facture = $objp->fk_facture;
			$this->fk_parent_line = $objp->fk_parent_line;
			$this->label				= $objp->custom_label;
			$this->desc					= $objp->description;
			$this->qty = $objp->qty;
			$this->subprice = $objp->subprice;
			$this->ref_ext = $objp->ref_ext;
			$this->vat_src_code = $objp->vat_src_code;
			$this->tva_tx = $objp->tva_tx;
			$this->localtax1_tx			= $objp->localtax1_tx;
			$this->localtax2_tx			= $objp->localtax2_tx;
			$this->remise_percent = $objp->remise_percent;
			$this->fk_remise_except = $objp->fk_remise_except;
			$this->fk_product			= $objp->fk_product;
			$this->product_type = $objp->product_type;
			$this->date_start			= $this->db->jdate($objp->date_start);
			$this->date_end				= $this->db->jdate($objp->date_end);
			$this->info_bits			= $objp->info_bits;
			$this->tva_npr = (($objp->info_bits & 1) == 1) ? 1 : 0;
			$this->special_code = $objp->special_code;
			$this->total_ht				= $objp->total_ht;
			$this->total_tva			= $objp->total_tva;
			$this->total_localtax1		= $objp->total_localtax1;
			$this->total_localtax2		= $objp->total_localtax2;
			$this->total_ttc			= $objp->total_ttc;
			$this->fk_code_ventilation  = $objp->fk_code_ventilation;
			$this->rang					= $objp->rang;
			$this->fk_fournprice = $objp->fk_fournprice;
			$marginInfos				= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
			$this->pa_ht				= $marginInfos[0];
			$this->marge_tx				= $marginInfos[1];
			$this->marque_tx			= $marginInfos[2];

			$this->ref = $objp->product_ref; // deprecated

			$this->product_ref = $objp->product_ref;
			$this->product_label		= $objp->product_label;
			$this->product_desc			= $objp->product_desc;

			$this->fk_unit = $objp->fk_unit;
			$this->fk_user_modif		= $objp->fk_user_modif;
			$this->fk_user_author = $objp->fk_user_author;

			$this->situation_percent    = $objp->situation_percent;
			$this->fk_prev_id           = $objp->fk_prev_id;

			$this->multicurrency_subprice = $objp->multicurrency_subprice;
			$this->multicurrency_total_ht = $objp->multicurrency_total_ht;
			$this->multicurrency_total_tva = $objp->multicurrency_total_tva;
			$this->multicurrency_total_ttc = $objp->multicurrency_total_ttc;

			$this->fetch_optionals();

			$this->db->free($result);

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      int		$notrigger		                 1 no triggers
	 *  @param      int     $noerrorifdiscountalreadylinked  1=Do not make error if lines is linked to a discount and discount already linked to another
	 *	@return		int						                 Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0, $noerrorifdiscountalreadylinked = 0)
	{
		global $langs, $user;

		$error = 0;

		$pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

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
			$this->localtax1_type = 0;
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = 0;
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
		if (empty($this->ref_ext)) {
			$this->ref_ext = '';
		}
		if (empty($this->special_code)) {
			$this->special_code = 0;
		}
		if (empty($this->fk_parent_line)) {
			$this->fk_parent_line = 0;
		}
		if (empty($this->fk_prev_id)) {
			$this->fk_prev_id = 0;
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

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0 && $pa_ht_isemptystring) {
			$result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
			if ($result < 0) {
				return $result;
			} else {
				$this->pa_ht = $result;
			}
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
				dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
				return -1;
			}
		}

		$this->db->begin();

		// Update line in database
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet';
		$sql .= ' (fk_facture, fk_parent_line, label, description, qty,';
		$sql .= ' vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql .= ' fk_product, product_type, remise_percent, subprice, ref_ext, fk_remise_except,';
		$sql .= ' date_start, date_end, fk_code_ventilation,';
		$sql .= ' rang, special_code, fk_product_fournisseur_price, buy_price_ht,';
		$sql .= ' info_bits, total_ht, total_tva, total_ttc, total_localtax1, total_localtax2,';
		$sql .= ' situation_percent, fk_prev_id,';
		$sql .= ' fk_unit, fk_user_author, fk_user_modif,';
		$sql .= ' fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc,';
		$sql .= ' batch, fk_warehouse';
		$sql .= ')';
		$sql .= " VALUES (".$this->fk_facture.",";
		$sql .= " ".($this->fk_parent_line > 0 ? $this->fk_parent_line : "null").",";
		$sql .= " ".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null").",";
		$sql .= " '".$this->db->escape($this->desc)."',";
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
		$sql .= " ".price2num($this->subprice).",";
		$sql .= " '".$this->db->escape($this->ref_ext)."',";
		$sql .= ' '.(!empty($this->fk_remise_except) ? $this->fk_remise_except : "null").',';
		$sql .= " ".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null").",";
		$sql .= " ".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		$sql .= ' '.((int) $this->fk_code_ventilation).',';
		$sql .= ' '.((int) $this->rang).',';
		$sql .= ' '.((int) $this->special_code).',';
		$sql .= ' '.(!empty($this->fk_fournprice) ? $this->fk_fournprice : "null").',';
		$sql .= ' '.price2num($this->pa_ht).',';
		$sql .= " '".$this->db->escape($this->info_bits)."',";
		$sql .= " ".price2num($this->total_ht).",";
		$sql .= " ".price2num($this->total_tva).",";
		$sql .= " ".price2num($this->total_ttc).",";
		$sql .= " ".price2num($this->total_localtax1).",";
		$sql .= " ".price2num($this->total_localtax2);
		$sql .= ", ".((float) $this->situation_percent);
		$sql .= ", ".(!empty($this->fk_prev_id) ? $this->fk_prev_id : "null");
		$sql .= ", ".(!$this->fk_unit ? 'NULL' : $this->fk_unit);
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".(int) $this->fk_multicurrency;
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".price2num($this->multicurrency_subprice);
		$sql .= ", ".price2num($this->multicurrency_total_ht);
		$sql .= ", ".price2num($this->multicurrency_total_tva);
		$sql .= ", ".price2num($this->multicurrency_total_ttc);
		$sql .= ", '".$this->db->escape($this->batch)."'";
		$sql .= ", ".((int) $this->fk_warehouse);
		$sql .= ')';

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facturedet');
			$this->rowid = $this->id; // For backward compatibility

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			// If fk_remise_except is defined, the discount is linked to the invoice
			// which flags it as "consumed".
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
							$result = $discount->link_to_invoice($this->rowid, 0);
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

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEBILL_INSERT', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -2;
				}
				// End call triggers
			}

			$this->db->commit();
			return $this->id;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *	Update line into database
	 *
	 *	@param		User	$user		User object
	 *	@param		int		$notrigger	Disable triggers
	 *	@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $user, $conf;

		$error = 0;

		$pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

		// Clean parameters
		$this->desc = trim($this->desc);
		if (empty($this->ref_ext)) {
			$this->ref_ext = '';
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
		if (empty($this->localtax1_type)) {
			$this->localtax1_type = 0;
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = 0;
		}
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}
		if (empty($this->info_bits)) {
			$this->info_bits = 0;
		}
		if (empty($this->special_code)) {
			$this->special_code = 0;
		}
		if (empty($this->product_type)) {
			$this->product_type = 0;
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
			return -1;
		}

		// if buy price not provided, define buyprice as configured in margin admin
		if ($this->pa_ht == 0 && $pa_ht_isemptystring) {
			// We call defineBuyPrice only if data was not provided (if input was '0', we will not go here and value will remaine '0')
			$result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
			if ($result < 0) {
				return $result;
			} else {
				$this->pa_ht = $result;
			}
		}

		$this->db->begin();

		// Update line in database
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
		$sql .= " description='".$this->db->escape($this->desc)."'";
		$sql .= ", ref_ext='".$this->db->escape($this->ref_ext)."'";
		$sql .= ", label=".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null");
		$sql .= ", subprice=".price2num($this->subprice);
		$sql .= ", remise_percent=".price2num($this->remise_percent);
		if ($this->fk_remise_except) {
			$sql .= ", fk_remise_except=".$this->fk_remise_except;
		} else {
			$sql .= ", fk_remise_except=null";
		}
		$sql .= ", vat_src_code = '".(empty($this->vat_src_code) ? '' : $this->db->escape($this->vat_src_code))."'";
		$sql .= ", tva_tx=".price2num($this->tva_tx);
		$sql .= ", localtax1_tx=".price2num($this->localtax1_tx);
		$sql .= ", localtax2_tx=".price2num($this->localtax2_tx);
		$sql .= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= ", qty=".price2num($this->qty);
		$sql .= ", date_start=".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
		$sql .= ", date_end=".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", product_type=".$this->product_type;
		$sql .= ", info_bits='".$this->db->escape($this->info_bits)."'";
		$sql .= ", special_code=" . (int) $this->special_code;
		if (empty($this->skip_update_total)) {
			$sql .= ", total_ht=".price2num($this->total_ht);
			$sql .= ", total_tva=".price2num($this->total_tva);
			$sql .= ", total_ttc=".price2num($this->total_ttc);
			$sql .= ", total_localtax1=".price2num($this->total_localtax1);
			$sql .= ", total_localtax2=".price2num($this->total_localtax2);
		}
		$sql .= ", fk_product_fournisseur_price=".(!empty($this->fk_fournprice) ? "'".$this->db->escape($this->fk_fournprice)."'" : "null");
		$sql .= ", buy_price_ht=".(($this->pa_ht || (string) $this->pa_ht === '0') ? price2num($this->pa_ht) : "null"); // $this->pa_ht should always be defined (set to 0 or to sell price depending on option)
		$sql .= ", fk_parent_line=".($this->fk_parent_line > 0 ? $this->fk_parent_line : "null");
		if (!empty($this->rang)) {
			$sql .= ", rang=".((int) $this->rang);
		}
		$sql .= ", situation_percent = ".((float) $this->situation_percent);
		$sql .= ", fk_unit = ".(!$this->fk_unit ? 'NULL' : $this->fk_unit);
		$sql .= ", fk_user_modif = ".((int) $user->id);

		// Multicurrency
		$sql .= ", multicurrency_subprice=".price2num($this->multicurrency_subprice);
		$sql .= ", multicurrency_total_ht=".price2num($this->multicurrency_total_ht);
		$sql .= ", multicurrency_total_tva=".price2num($this->multicurrency_total_tva);
		$sql .= ", multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc);

		$sql .= ", batch = '".$this->db->escape($this->batch)."'";
		$sql .= ", fk_warehouse = ".((int) $this->fk_warehouse);

		$sql .= " WHERE rowid = ".((int) $this->rowid);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$this->id = $this->rowid;
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEBILL_MODIFY', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -2;
				}
				// End call triggers
			}
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 * Delete line in database
	 *
	 * @param 	User 	$tmpuser    User that deletes
	 * @param 	int 	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return 	int		           	Return integer <0 if KO, >0 if OK
	 */
	public function delete($tmpuser = null, $notrigger = 0)
	{
		global $user;

		$this->db->begin();

		// Call trigger
		if (empty($notrigger)) {
			$result = $this->call_trigger('LINEBILL_DELETE', $user);
			if ($result < 0) {
				$this->db->rollback();
				return -1;
			}
		}
		// End call triggers

		// extrafields
		$result = $this->deleteExtraFields();
		if ($result < 0) {
			$this->db->rollback();
			return -1;
		}

		// Free discount linked to invoice line
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
		$sql .= ' SET fk_facture_line = NULL';
		$sql .= ' WHERE fk_facture_line = '.((int) $this->id);

		dol_syslog(get_class($this)."::deleteline", LOG_DEBUG);
		$result = $this->db->query($sql);
		if (!$result) {
			$this->error = $this->db->error();
			$this->errors[] = $this->error;
			$this->db->rollback();
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'element_time';
		$sql .= ' SET invoice_id = NULL, invoice_line_id = NULL';
		$sql .= ' WHERE invoice_line_id = '.((int) $this->id);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->error()." sql=".$sql;
			$this->errors[] = $this->error;
			$this->db->rollback();
			return -1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = ".((int) $this->id);

		if ($this->db->query($sql)) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->errors[] = $this->error;
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update DB line fields total_xxx
	 *	Used by migration
	 *
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 */
	public function update_total()
	{
		// phpcs:enable
		$this->db->begin();
		dol_syslog(get_class($this)."::update_total", LOG_DEBUG);

		// Clean parameters
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}

		// Update line in database
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
		$sql .= " total_ht=".price2num($this->total_ht);
		$sql .= ",total_tva=".price2num($this->total_tva);
		$sql .= ",total_localtax1=".price2num($this->total_localtax1);
		$sql .= ",total_localtax2=".price2num($this->total_localtax2);
		$sql .= ",total_ttc=".price2num($this->total_ttc);
		$sql .= " WHERE rowid = ".((int) $this->rowid);

		dol_syslog(get_class($this)."::update_total", LOG_DEBUG);

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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Returns situation_percent of the previous line. Used when INVOICE_USE_SITUATION = 1.
	 * Warning: If invoice is a replacement invoice, this->fk_prev_id is id of the replaced line.
	 *
	 * @param  int     $invoiceid      			Invoice id
	 * @param  bool    $include_credit_note		Include credit note or not
	 * @return float|int                     	Return previous situation percent, 0 or -1 if error
	 * @see get_allprev_progress()
	 **/
	public function get_prev_progress($invoiceid, $include_credit_note = true)
	{
		// phpcs:enable
		global $invoicecache;

		if (is_null($this->fk_prev_id) || empty($this->fk_prev_id) || $this->fk_prev_id == "") {
			return 0;
		} else {
			// If invoice is not a situation invoice, this->fk_prev_id is used for something else
			if (!isset($invoicecache[$invoiceid])) {
				$invoicecache[$invoiceid] = new Facture($this->db);
				$invoicecache[$invoiceid]->fetch($invoiceid);
			}
			if ($invoicecache[$invoiceid]->type != Facture::TYPE_SITUATION) {
				return 0;
			}

			$sql = "SELECT situation_percent FROM ".MAIN_DB_PREFIX."facturedet";
			$sql .= " WHERE rowid = ".((int) $this->fk_prev_id);

			$resql = $this->db->query($sql);

			if ($resql && $this->db->num_rows($resql) > 0) {
				$returnPercent = 0;

				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$returnPercent = (float) $obj->situation_percent;
				}

				if ($include_credit_note) {
					$sql = 'SELECT fd.situation_percent FROM '.MAIN_DB_PREFIX.'facturedet fd';
					$sql .= ' JOIN '.MAIN_DB_PREFIX.'facture f ON (f.rowid = fd.fk_facture) ';
					$sql .= " WHERE fd.fk_prev_id = ".((int) $this->fk_prev_id);
					$sql .= " AND f.situation_cycle_ref = ".((int) $invoicecache[$invoiceid]->situation_cycle_ref); // Prevent cycle outed
					$sql .= " AND f.type = ".Facture::TYPE_CREDIT_NOTE;

					$res = $this->db->query($sql);
					if ($res) {
						while ($obj = $this->db->fetch_object($res)) {
							$returnPercent += (float) $obj->situation_percent;
						}
					} else {
						dol_print_error($this->db);
					}
				}

				return $returnPercent;
			} else {
				$this->error = $this->db->error();
				dol_syslog(get_class($this)."::select Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Returns situation_percent of all the previous line. Used when INVOICE_USE_SITUATION = 2.
	 * Warning: If invoice is a replacement invoice, this->fk_prev_id is id of the replaced line.
	 *
	 * @param  int     $invoiceid      Invoice id
	 * @param  bool    $include_credit_note		Include credit note or not
	 * @return float                   >= 0
	 * @see get_prev_progress()
	 */
	public function get_allprev_progress($invoiceid, $include_credit_note = true)
	{
		// phpcs:enable
		global $invoicecache;

		if (is_null($this->fk_prev_id) || empty($this->fk_prev_id) || $this->fk_prev_id == "") {
			return 0;
		} else {
			// If invoice is not a situation invoice, this->fk_prev_id is used for something else
			if (!isset($invoicecache[$invoiceid])) {
				$invoicecache[$invoiceid] = new Facture($this->db);
				$invoicecache[$invoiceid]->fetch($invoiceid);
			}
			if ($invoicecache[$invoiceid]->type != Facture::TYPE_SITUATION) {
				return 0;
			}

			$all_found = false;
			$lastprevid = $this->fk_prev_id;
			$cumulated_percent = 0.0;

			while (!$all_found) {
				$sql = "SELECT situation_percent, fk_prev_id FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = ".((int) $lastprevid);
				$resql = $this->db->query($sql);

				if ($resql && $this->db->num_rows($resql) > 0) {
					$obj = $this->db->fetch_object($resql);
					$cumulated_percent += floatval($obj->situation_percent);

					if ($include_credit_note) {
						$sql_credit_note = 'SELECT fd.situation_percent FROM '.MAIN_DB_PREFIX.'facturedet fd';
						$sql_credit_note .= ' JOIN '.MAIN_DB_PREFIX.'facture f ON (f.rowid = fd.fk_facture) ';
						$sql_credit_note .= " WHERE fd.fk_prev_id = ".((int) $lastprevid);
						$sql_credit_note .= " AND f.situation_cycle_ref = ".((int) $invoicecache[$invoiceid]->situation_cycle_ref); // Prevent cycle outed
						$sql_credit_note .= " AND f.type = ".Facture::TYPE_CREDIT_NOTE;

						$res_credit_note = $this->db->query($sql_credit_note);
						if ($res_credit_note) {
							while ($cn = $this->db->fetch_object($res_credit_note)) {
								$cumulated_percent += floatval($cn->situation_percent);
							}
						} else {
							dol_print_error($this->db);
						}
					}

					// Si fk_prev_id, on continue
					if ($obj->fk_prev_id) {
						$lastprevid = $obj->fk_prev_id;
					} else { // Sinon on stoppe la boucle
						$all_found = true;
					}
				} else {
					$this->error = $this->db->error();
					dol_syslog(get_class($this)."::select Error ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -1;
				}
			}
			return $cumulated_percent;
		}
	}
}
