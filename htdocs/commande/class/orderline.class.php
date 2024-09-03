<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2010-2020 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Jean Heimburger      <jean@tiaris.info>
 * Copyright (C) 2012-2014 Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Nicolas ZABOURI	    <info@inovea-conseil.com>
 * Copyright (C) 2016-2022 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2021-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/commande/class/orderline.class.php
 *  \ingroup    order
 *  \brief      class for order lines
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
//require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 *  Class to manage order lines
 */
class OrderLine extends CommonOrderLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'commandedet';

	public $table_element = 'commandedet';

	public $oldline;

	/**
	 * Id of parent order
	 * @var int
	 */
	public $fk_commande;

	/**
	 * Id of parent order
	 * @var int
	 * @deprecated Use fk_commande
	 * @see $fk_commande
	 */
	public $commande_id;

	public $fk_parent_line;

	/**
	 * @var int Id of invoice
	 */
	public $fk_facture;

	/**
	 * @var string External ref
	 */
	public $ref_ext;

	public $fk_remise_except;

	/**
	 * @var int line rank
	 */
	public $rang = 0;
	public $fk_fournprice;

	/**
	 * Buy price without taxes
	 * @var float
	 */
	public $pa_ht;
	public $marge_tx;
	public $marque_tx;

	/**
	 * @deprecated
	 * @see $remise_percent, $fk_remise_except
	 */
	public $remise;

	// Start and end date of the line
	public $date_start;
	public $date_end;

	public $skip_update_total; // Skip update price total for special lines


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
	 *  Load line order
	 *
	 *  @param  int		$rowid          Id line order
	 *  @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_parent_line, cd.fk_product, cd.product_type, cd.label as custom_label, cd.description, cd.price, cd.qty, cd.tva_tx, cd.localtax1_tx, cd.localtax2_tx,';
		$sql .= ' cd.remise, cd.remise_percent, cd.fk_remise_except, cd.subprice, cd.ref_ext,';
		$sql .= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_localtax1, cd.total_localtax2, cd.total_ttc, cd.fk_product_fournisseur_price as fk_fournprice, cd.buy_price_ht as pa_ht, cd.rang, cd.special_code,';
		$sql .= ' cd.fk_unit,';
		$sql .= ' cd.fk_multicurrency, cd.multicurrency_code, cd.multicurrency_subprice, cd.multicurrency_total_ht, cd.multicurrency_total_tva, cd.multicurrency_total_ttc,';
		$sql .= ' p.ref as product_ref, p.label as product_label, p.description as product_desc, p.tobatch as product_tobatch,';
		$sql .= ' cd.date_start, cd.date_end, cd.vat_src_code';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON cd.fk_product = p.rowid';
		$sql .= ' WHERE cd.rowid = '.((int) $rowid);
		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			if (!$objp) {
				$this->error = 'OrderLine with id '. $rowid .' not found sql='.$sql;
				return 0;
			}

			$this->rowid            = $objp->rowid;
			$this->id = $objp->rowid;
			$this->fk_commande      = $objp->fk_commande;
			$this->fk_parent_line   = $objp->fk_parent_line;
			$this->label            = $objp->custom_label;
			$this->desc             = $objp->description;
			$this->qty              = $objp->qty;
			$this->price            = $objp->price;
			$this->subprice         = $objp->subprice;
			$this->ref_ext          = $objp->ref_ext;
			$this->vat_src_code     = $objp->vat_src_code;
			$this->tva_tx           = $objp->tva_tx;
			$this->localtax1_tx		= $objp->localtax1_tx;
			$this->localtax2_tx		= $objp->localtax2_tx;
			$this->remise           = $objp->remise;
			$this->remise_percent   = $objp->remise_percent;
			$this->fk_remise_except = $objp->fk_remise_except;
			$this->fk_product       = $objp->fk_product;
			$this->product_type     = $objp->product_type;
			$this->info_bits        = $objp->info_bits;
			$this->special_code = $objp->special_code;
			$this->total_ht         = $objp->total_ht;
			$this->total_tva        = $objp->total_tva;
			$this->total_localtax1  = $objp->total_localtax1;
			$this->total_localtax2  = $objp->total_localtax2;
			$this->total_ttc        = $objp->total_ttc;
			$this->fk_fournprice = $objp->fk_fournprice;
			$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
			$this->pa_ht			= $marginInfos[0];
			$this->marge_tx			= $marginInfos[1];
			$this->marque_tx		= $marginInfos[2];
			$this->special_code = $objp->special_code;
			$this->rang = $objp->rang;

			$this->ref = $objp->product_ref; // deprecated

			$this->product_ref      = $objp->product_ref;
			$this->product_label    = $objp->product_label;
			$this->product_desc     = $objp->product_desc;
			$this->product_tobatch  = $objp->product_tobatch;
			$this->fk_unit          = $objp->fk_unit;

			$this->date_start       = $this->db->jdate($objp->date_start);
			$this->date_end         = $this->db->jdate($objp->date_end);

			$this->fk_multicurrency = $objp->fk_multicurrency;
			$this->multicurrency_code = $objp->multicurrency_code;
			$this->multicurrency_subprice	= $objp->multicurrency_subprice;
			$this->multicurrency_total_ht	= $objp->multicurrency_total_ht;
			$this->multicurrency_total_tva	= $objp->multicurrency_total_tva;
			$this->multicurrency_total_ttc	= $objp->multicurrency_total_ttc;

			$this->fetch_optionals();

			$this->db->free($result);

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Delete line in database
	 *
	 *	@param      User	$user        	User that modify
	 *  @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return	 int  Return integer <0 si ko, >0 si ok
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		if (empty($this->id) && !empty($this->rowid)) {        // For backward compatibility
			$this->id = $this->rowid;
		}

		// check if order line is not in a shipment line before deleting
		$sqlCheckShipmentLine = "SELECT";
		$sqlCheckShipmentLine .= " ed.rowid";
		$sqlCheckShipmentLine .= " FROM " . MAIN_DB_PREFIX . "expeditiondet ed";
		$sqlCheckShipmentLine .= " WHERE ed.fk_elementdet = " . ((int) $this->id);

		$resqlCheckShipmentLine = $this->db->query($sqlCheckShipmentLine);
		if (!$resqlCheckShipmentLine) {
			$error++;
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
		} else {
			$langs->load('errors');
			$num = $this->db->num_rows($resqlCheckShipmentLine);
			if ($num > 0) {
				$error++;
				$objCheckShipmentLine = $this->db->fetch_object($resqlCheckShipmentLine);
				$this->error = $langs->trans('ErrorRecordAlreadyExists') . ' : ' . $langs->trans('ShipmentLine') . ' ' . $objCheckShipmentLine->rowid;
				$this->errors[] = $this->error;
			}
			$this->db->free($resqlCheckShipmentLine);
		}
		if ($error) {
			dol_syslog(__METHOD__ . 'Error ; ' . $this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('LINEORDER_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . "commandedet WHERE rowid = " . ((int) $this->id);

			dol_syslog("OrderLine::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		// Remove extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this) . "::delete error -4 " . $this->error, LOG_ERR);
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		}

		foreach ($this->errors as $errmsg) {
			dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
			$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
		}
		$this->db->rollback();
		return -1 * $error;
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      User	$user        	User that modify
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function insert($user = null, $notrigger = 0)
	{
		$error = 0;

		$pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

		dol_syslog(get_class($this)."::insert rang=".$this->rang);

		// Clean parameters
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
		if (empty($this->special_code)) {
			$this->special_code = 0;
		}
		if (empty($this->fk_parent_line)) {
			$this->fk_parent_line = 0;
		}
		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}
		if (empty($this->ref_ext)) {
			$this->ref_ext = '';
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
			return -1;
		}

		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet';
		$sql .= ' (fk_commande, fk_parent_line, label, description, qty, ref_ext,';
		$sql .= ' vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql .= ' fk_product, product_type, remise_percent, subprice, price, fk_remise_except,';
		$sql .= ' special_code, rang, fk_product_fournisseur_price, buy_price_ht,';
		$sql .= ' info_bits, total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, date_start, date_end,';
		$sql .= ' fk_unit,';
		$sql .= ' fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
		$sql .= ')';
		$sql .= " VALUES (".$this->fk_commande.",";
		$sql .= " ".($this->fk_parent_line > 0 ? "'".$this->db->escape($this->fk_parent_line)."'" : "null").",";
		$sql .= " ".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null").",";
		$sql .= " '".$this->db->escape($this->desc)."',";
		$sql .= " '".price2num($this->qty)."',";
		$sql .= " '".$this->db->escape($this->ref_ext)."',";
		$sql .= " ".(empty($this->vat_src_code) ? "''" : "'".$this->db->escape($this->vat_src_code)."'").",";
		$sql .= " '".price2num($this->tva_tx)."',";
		$sql .= " '".price2num($this->localtax1_tx)."',";
		$sql .= " '".price2num($this->localtax2_tx)."',";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= ' '.((!empty($this->fk_product) && $this->fk_product > 0) ? $this->fk_product : "null").',';
		$sql .= " '".$this->db->escape($this->product_type)."',";
		$sql .= " '".price2num($this->remise_percent)."',";
		$sql .= " ".(price2num($this->subprice) !== '' ? price2num($this->subprice) : "null").",";
		$sql .= " ".($this->price != '' ? "'".price2num($this->price)."'" : "null").",";
		$sql .= ' '.(!empty($this->fk_remise_except) ? $this->fk_remise_except : "null").',';
		$sql .= ' '.((int) $this->special_code).',';
		$sql .= ' '.((int) $this->rang).',';
		$sql .= ' '.(!empty($this->fk_fournprice) ? $this->fk_fournprice : "null").',';
		$sql .= ' '.price2num($this->pa_ht).',';
		$sql .= " ".((int) $this->info_bits).",";
		$sql .= " ".price2num($this->total_ht, 'MT').",";
		$sql .= " ".price2num($this->total_tva, 'MT').",";
		$sql .= " ".price2num($this->total_localtax1, 'MT').",";
		$sql .= " ".price2num($this->total_localtax2, 'MT').",";
		$sql .= " ".price2num($this->total_ttc, 'MT').",";
		$sql .= " ".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null").',';
		$sql .= " ".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null").',';
		$sql .= ' '.(!$this->fk_unit ? 'NULL' : ((int) $this->fk_unit));
		$sql .= ", ".(!empty($this->fk_multicurrency) ? ((int) $this->fk_multicurrency) : 'NULL');
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".price2num($this->multicurrency_subprice, 'CU');
		$sql .= ", ".price2num($this->multicurrency_total_ht, 'CT');
		$sql .= ", ".price2num($this->multicurrency_total_tva, 'CT');
		$sql .= ", ".price2num($this->multicurrency_total_ttc, 'CT');
		$sql .= ')';

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'commandedet');
			$this->rowid = $this->id;

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEORDER_INSERT', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			}

			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::insert ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *	Update the line object into db
	 *
	 *	@param      User	$user        	User that modify
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int		Return integer <0 si ko, >0 si ok
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		$pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

		// Clean parameters
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
		if (empty($this->qty)) {
			$this->qty = 0;
		}
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->marque_tx)) {
			$this->marque_tx = 0;
		}
		if (empty($this->marge_tx)) {
			$this->marge_tx = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}
		if (empty($this->remise)) {
			$this->remise = 0;
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
		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}
		if (empty($this->ref_ext)) {
			$this->ref_ext = '';
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

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
		$sql .= " description='".$this->db->escape($this->desc)."'";
		$sql .= " , label=".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null");
		$sql .= " , vat_src_code=".(!empty($this->vat_src_code) ? "'".$this->db->escape($this->vat_src_code)."'" : "''");
		$sql .= " , tva_tx=".price2num($this->tva_tx);
		$sql .= " , localtax1_tx=".price2num($this->localtax1_tx);
		$sql .= " , localtax2_tx=".price2num($this->localtax2_tx);
		$sql .= " , localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= " , localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= " , qty=".price2num($this->qty);
		$sql .= " , ref_ext='".$this->db->escape($this->ref_ext)."'";
		$sql .= " , subprice=".price2num($this->subprice);
		$sql .= " , remise_percent=".price2num($this->remise_percent);
		$sql .= " , price=".price2num($this->price); // TODO A virer
		$sql .= " , remise=".price2num($this->remise); // TODO A virer
		if (empty($this->skip_update_total)) {
			$sql .= " , total_ht=".price2num($this->total_ht);
			$sql .= " , total_tva=".price2num($this->total_tva);
			$sql .= " , total_ttc=".price2num($this->total_ttc);
			$sql .= " , total_localtax1=".price2num($this->total_localtax1);
			$sql .= " , total_localtax2=".price2num($this->total_localtax2);
		}
		$sql .= " , fk_product_fournisseur_price=".(!empty($this->fk_fournprice) ? $this->fk_fournprice : "null");
		$sql .= " , buy_price_ht='".price2num($this->pa_ht)."'";
		$sql .= " , info_bits=".((int) $this->info_bits);
		$sql .= " , special_code=".((int) $this->special_code);
		$sql .= " , date_start=".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
		$sql .= " , date_end=".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= " , product_type=".$this->product_type;
		$sql .= " , fk_parent_line=".(!empty($this->fk_parent_line) ? $this->fk_parent_line : "null");
		if (!empty($this->rang)) {
			$sql .= ", rang=".((int) $this->rang);
		}
		$sql .= " , fk_unit=".(!$this->fk_unit ? 'NULL' : $this->fk_unit);

		// Multicurrency
		$sql .= " , multicurrency_subprice=".price2num($this->multicurrency_subprice);
		$sql .= " , multicurrency_total_ht=".price2num($this->multicurrency_total_ht);
		$sql .= " , multicurrency_total_tva=".price2num($this->multicurrency_total_tva);
		$sql .= " , multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc);

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
				$result = $this->call_trigger('LINEORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			}

			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
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

		// Clean parameters
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
		$sql .= " total_ht='".price2num($this->total_ht)."'";
		$sql .= ",total_tva='".price2num($this->total_tva)."'";
		$sql .= ",total_localtax1='".price2num($this->total_localtax1)."'";
		$sql .= ",total_localtax2='".price2num($this->total_localtax2)."'";
		$sql .= ",total_ttc='".price2num($this->total_ttc)."'";
		$sql .= " WHERE rowid = ".((int) $this->rowid);

		dol_syslog("OrderLine::update_total", LOG_DEBUG);

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
