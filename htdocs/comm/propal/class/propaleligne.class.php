<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley           <marc@ocebo.com>
 * Copyright (C) 2005-2013  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2008       Raphael Bertrand        <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2020  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2010-2022  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2014  Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2022       ATM Consulting          <contact@atm-consulting.fr>
 * Copyright (C) 2022       OpenDSI                 <support@open-dsi.fr>
 * Copyright (C) 2022      	Gauthier VERDOL     	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023		William Mead			<william.mead@manchenumerique.fr>
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
 *	\file       htdocs/comm/propal/class/propaleligne.class.php
 *	\ingroup    propale
 *	\brief      File of class to manage proposal lines
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

/**
 *	Class to manage commercial proposal lines
 */
class PropaleLigne extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'propaldet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'propaldet';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'propal';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_propal';

	/**
	 * @var PropaleLigne
	 */
	public $oldline;

	// From llx_propaldet
	/**
	 * @var int
	 */
	public $fk_propal;

	/**
	 * @var int
	 */
	public $fk_parent_line;

	/**
	 * @var string Line description
	 */
	public $desc;

	/**
	 * @var int Predefined product Id
	 */
	public $fk_product;

	/**
	 * @var int
	 * @deprecated
	 * @see $product_type
	 */

	public $fk_product_type;
	/**
	 * Product type.
	 * @var int
	 * @see Product::TYPE_PRODUCT, Product::TYPE_SERVICE
	 */
	public $product_type = Product::TYPE_PRODUCT;

	/**
	 * @var float Quantity
	 */
	public $qty;

	/**
	 * @var float|string
	 */
	public $tva_tx;

	/**
	 * @var string
	 */
	public $vat_src_code;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;

	/**
	 * @var int|string|float
	 */
	public $remise_percent;

	/**
	 * @var int ID
	 */
	public $fk_remise_except;

	/**
	 * @var int line rank
	 */
	public $rang = 0;

	/**
	 * @var int
	 */
	public $fk_fournprice;

	/**
	 * @var float|int|string
	 */
	public $pa_ht;

	/**
	 * @var int|float|string
	 */
	public $marge_tx;

	/**
	 * @var float|string
	 */
	public $marque_tx;

	/**
	 * Tag for special lines (exclusive tags)
	 * 1: shipping costs
	 * 2: ecotaxe
	 * 3: option line (when qty = 0)
	 * @var int special code
	 */
	public $special_code;

	/**
	 * Some other info:
	 * Bit 0: 	0 si TVA normal - 1 if TVA NPR
	 * Bit 1:	0 ligne normal - 1 if line with fixed discount
	 * @var int
	 */
	public $info_bits = 0;

	/**
	 * Total amount excluding taxes (HT = "Hors Taxe" in French) including discounts
	 * @var float
	 */
	public $total_ht;

	/**
	 * Total VAT amount (TVA = "Taxe sur la Valeur Ajoutée" in French)
	 * @var float
	 */
	public $total_tva;

	/**
	 * Total amount including taxes (TTC = "Toutes Taxes Comprises" in French)
	 * @var float
	 */
	public $total_ttc;

	/**
	 * @var float|string
	 * @deprecated
	 * @see $remise_percent, $fk_remise_except
	 */
	public $remise;

	/**
	 * @var float|string
	 * @deprecated
	 * @see $subprice
	 */
	public $price;

	// From llx_product
	/**
	 * @var string
	 * @deprecated
	 * @see $product_ref
	 */
	public $ref;

	/**
	 * Product reference
	 * @var string
	 */
	public $product_ref;

	/**
	 * @var string
	 * @deprecated
	 * @see $product_label
	 */
	public $libelle;

	/**
	 * @var string
	 * @deprecated
	 * @see $product_label
	 */
	public $label;

	/**
	 * Product label
	 * @var string
	 */
	public $product_label;

	/**
	 * Product description
	 * @var string
	 */
	public $product_desc;

	/**
	 * Product use lot
	 * @var string
	 */
	public $product_tobatch;

	/**
	 * Product barcode
	 * @var string
	 */
	public $product_barcode;

	/**
	 * @var string|float
	 */
	public $localtax1_tx;

	/**
	 * @var string|float
	 */
	public $localtax2_tx;

	/**
	 * @var int|string Local tax 1 type
	 */
	public $localtax1_type;

	/**
	 * @var int|string Local tax 2 type
	 */
	public $localtax2_type;

	/**
	 * @var float Line total local tax 1
	 */
	public $total_localtax1;

	/**
	 * @var float Line total local tax 2
	 */
	public $total_localtax2;

	/**
	 * @var int|string
	 */
	public $date_start;

	/**
	 * @var int|string
	 */
	public $date_end;

	/**
	 * @var int Skip update price total for special lines
	 */
	public $skip_update_total;

	// Multicurrency
	/**
	 * @var int multicurrency id
	 */
	public $fk_multicurrency;

	/**
	 * @var string Multicurrency code
	 */
	public $multicurrency_code;

	/**
	 * @var float Multicurrency subprice
	 */
	public $multicurrency_subprice;

	/**
	 * @var float Multicurrency total without tax
	 */
	public $multicurrency_total_ht;

	/**
	 * @var float Multicurrency total vat
	 */
	public $multicurrency_total_tva;

	/**
	 * @var float Multicurrency total with tax
	 */
	public $multicurrency_total_ttc;


	/**
	 * 	Class line Constructor
	 *
	 * 	@param	DoliDB	$db	Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the propal line object
	 *
	 *	@param	int		$rowid		Propal line id
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT pd.rowid, pd.fk_propal, pd.fk_parent_line, pd.fk_product, pd.label as custom_label, pd.description, pd.price, pd.qty, pd.vat_src_code, pd.tva_tx,';
		$sql .= ' pd.remise, pd.remise_percent, pd.fk_remise_except, pd.subprice,';
		$sql .= ' pd.info_bits, pd.total_ht, pd.total_tva, pd.total_ttc, pd.fk_product_fournisseur_price as fk_fournprice, pd.buy_price_ht as pa_ht, pd.special_code, pd.rang,';
		$sql .= ' pd.fk_unit,';
		$sql .= ' pd.localtax1_tx, pd.localtax2_tx, pd.total_localtax1, pd.total_localtax2,';
		$sql .= ' pd.fk_multicurrency, pd.multicurrency_code, pd.multicurrency_subprice, pd.multicurrency_total_ht, pd.multicurrency_total_tva, pd.multicurrency_total_ttc,';
		$sql .= ' p.ref as product_ref, p.label as product_label, p.description as product_desc,';
		$sql .= ' pd.date_start, pd.date_end, pd.product_type';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'propaldet as pd';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pd.fk_product = p.rowid';
		$sql .= ' WHERE pd.rowid = '.((int) $rowid);

		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			if ($objp) {
				$this->id = $objp->rowid;
				$this->rowid			= $objp->rowid; // deprecated
				$this->fk_propal = $objp->fk_propal;
				$this->fk_parent_line = $objp->fk_parent_line;
				$this->label			= $objp->custom_label;
				$this->desc				= $objp->description;
				$this->qty = $objp->qty;
				$this->price			= $objp->price; // deprecated
				$this->subprice = $objp->subprice;
				$this->vat_src_code = $objp->vat_src_code;
				$this->tva_tx			= $objp->tva_tx;
				$this->remise			= $objp->remise; // deprecated
				$this->remise_percent = $objp->remise_percent;
				$this->fk_remise_except = $objp->fk_remise_except;
				$this->fk_product = $objp->fk_product;
				$this->info_bits		= $objp->info_bits;

				$this->total_ht			= $objp->total_ht;
				$this->total_tva		= $objp->total_tva;
				$this->total_ttc		= $objp->total_ttc;

				$this->fk_fournprice = $objp->fk_fournprice;

				$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
				$this->pa_ht			= $marginInfos[0];
				$this->marge_tx			= $marginInfos[1];
				$this->marque_tx		= $marginInfos[2];

				$this->special_code		= $objp->special_code;
				$this->product_type		= $objp->product_type;
				$this->rang = $objp->rang;

				$this->ref = $objp->product_ref; // deprecated
				$this->product_ref = $objp->product_ref;
				$this->libelle = $objp->product_label; // deprecated
				$this->product_label	= $objp->product_label;
				$this->product_desc		= $objp->product_desc;
				$this->fk_unit          = $objp->fk_unit;

				$this->date_start       = $this->db->jdate($objp->date_start);
				$this->date_end         = $this->db->jdate($objp->date_end);

				// Multicurrency
				$this->fk_multicurrency = $objp->fk_multicurrency;
				$this->multicurrency_code = $objp->multicurrency_code;
				$this->multicurrency_subprice 	= $objp->multicurrency_subprice;
				$this->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;

				$this->fetch_optionals();

				$this->db->free($result);

				return 1;
			} else {
				return 0;
			}
		} else {
			return -1;
		}
	}

	/**
	 *  Insert object line propal in database
	 *
	 *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0)
	{
		global $conf, $user;

		$error = 0;

		dol_syslog(get_class($this)."::insert rang=".$this->rang);

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
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->rang)) {
			$this->rang = 0;
		}
		if (empty($this->remise_percent) || !is_numeric($this->remise_percent)) {
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
		if (empty($this->fk_fournprice)) {
			$this->fk_fournprice = 0;
		}
		if (!is_numeric($this->qty)) {
			$this->qty = 0;
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
			if (($result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product)) < 0) {
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

		// Insert line into database
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'propaldet';
		$sql .= ' (fk_propal, fk_parent_line, label, description, fk_product, product_type,';
		$sql .= ' fk_remise_except, qty, vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql .= ' subprice, remise_percent, ';
		$sql .= ' info_bits, ';
		$sql .= ' total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, fk_product_fournisseur_price, buy_price_ht, special_code, rang,';
		$sql .= ' fk_unit,';
		$sql .= ' date_start, date_end';
		$sql .= ', fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc)';
		$sql .= " VALUES (".$this->fk_propal.",";
		$sql .= " ".($this->fk_parent_line > 0 ? "'".$this->db->escape($this->fk_parent_line)."'" : "null").",";
		$sql .= " ".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null").",";
		$sql .= " '".$this->db->escape($this->desc)."',";
		$sql .= " ".($this->fk_product ? "'".$this->db->escape($this->fk_product)."'" : "null").",";
		$sql .= " '".$this->db->escape($this->product_type)."',";
		$sql .= " ".($this->fk_remise_except ? "'".$this->db->escape($this->fk_remise_except)."'" : "null").",";
		$sql .= " ".price2num($this->qty, 'MS').",";
		$sql .= " ".(empty($this->vat_src_code) ? "''" : "'".$this->db->escape($this->vat_src_code)."'").",";
		$sql .= " ".price2num($this->tva_tx).",";
		$sql .= " ".price2num($this->localtax1_tx).",";
		$sql .= " ".price2num($this->localtax2_tx).",";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= " ".(price2num($this->subprice) !== '' ? price2num($this->subprice, 'MU') : "null").",";
		$sql .= " ".price2num($this->remise_percent).",";
		$sql .= " ".(isset($this->info_bits) ? ((int) $this->info_bits) : "null").",";
		$sql .= " ".price2num($this->total_ht, 'MT').",";
		$sql .= " ".price2num($this->total_tva, 'MT').",";
		$sql .= " ".price2num($this->total_localtax1, 'MT').",";
		$sql .= " ".price2num($this->total_localtax2, 'MT').",";
		$sql .= " ".price2num($this->total_ttc, 'MT').",";
		$sql .= " ".(!empty($this->fk_fournprice) ? "'".$this->db->escape($this->fk_fournprice)."'" : "null").",";
		$sql .= " ".(isset($this->pa_ht) ? "'".price2num($this->pa_ht)."'" : "null").",";
		$sql .= ' '.((int) $this->special_code).',';
		$sql .= ' '.((int) $this->rang).',';
		$sql .= ' '.(empty($this->fk_unit) ? 'NULL' : ((int) $this->fk_unit)).',';
		$sql .= " ".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null").',';
		$sql .= " ".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", ".($this->fk_multicurrency > 0 ? ((int) $this->fk_multicurrency) : 'null');
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".price2num($this->multicurrency_subprice, 'CU');
		$sql .= ", ".price2num($this->multicurrency_total_ht, 'CT');
		$sql .= ", ".price2num($this->multicurrency_total_tva, 'CT');
		$sql .= ", ".price2num($this->multicurrency_total_ttc, 'CT');
		$sql .= ')';

		dol_syslog(get_class($this).'::insert', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX.'propaldet');

			if (!$error) {
				$this->id = $this->rowid;
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEPROPAL_INSERT', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * 	Delete line in database
	 *
	 *  @param	User	$user		Object user
	 *	@param 	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	 int  				Return integer <0 if ko, >0 if ok
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;
		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('LINEPROPAL_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
		}
		// End call triggers

		if (!$error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "propaldet WHERE rowid = " . ((int) $this->rowid);
			dol_syslog("PropaleLigne::delete", LOG_DEBUG);
			if ($this->db->query($sql)) {
				// Remove extrafields
				if (!$error) {
					$this->id = $this->rowid;
					$result = $this->deleteExtraFields();
					if ($result < 0) {
						$error++;
						dol_syslog(get_class($this) . "::delete error -4 " . $this->error, LOG_ERR);
					}
				}
			} else {
				$this->error = $this->db->error() . " sql=" . $sql;
				$error++;
			}
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Update propal line object into DB
	 *
	 *	@param 	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int					Return integer <0 if ko, >0 if ok
	 */
	public function update($notrigger = 0)
	{
		global $conf, $user;

		$error = 0;

		$pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

		if (empty($this->id) && !empty($this->rowid)) {
			$this->id = $this->rowid;
		}

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
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->localtax1_type)) {
			$this->localtax1_type = 0;
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = 0;
		}
		if (empty($this->marque_tx)) {
			$this->marque_tx = 0;
		}
		if (empty($this->marge_tx)) {
			$this->marge_tx = 0;
		}
		if (empty($this->price)) {
			$this->price = 0; // TODO A virer
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
		if (empty($this->fk_fournprice)) {
			$this->fk_fournprice = 0;
		}
		if (empty($this->subprice)) {
			$this->subprice = 0;
		}
		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0 && $pa_ht_isemptystring) {
			if (($result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product)) < 0) {
				return $result;
			} else {
				$this->pa_ht = $result;
			}
		}

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet SET";
		$sql .= " description='".$this->db->escape($this->desc)."'";
		$sql .= ", label=".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null");
		$sql .= ", product_type=".$this->product_type;
		$sql .= ", vat_src_code = '".(empty($this->vat_src_code) ? '' : $this->vat_src_code)."'";
		$sql .= ", tva_tx='".price2num($this->tva_tx)."'";
		$sql .= ", localtax1_tx=".price2num($this->localtax1_tx);
		$sql .= ", localtax2_tx=".price2num($this->localtax2_tx);
		$sql .= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= ", qty='".price2num($this->qty)."'";
		$sql .= ", subprice=".price2num($this->subprice);
		$sql .= ", remise_percent=".price2num($this->remise_percent);
		$sql .= ", price=".(float) price2num($this->price); // TODO A virer
		$sql .= ", remise=".(float) price2num($this->remise); // TODO A virer
		$sql .= ", info_bits='".$this->db->escape($this->info_bits)."'";
		if (empty($this->skip_update_total)) {
			$sql .= ", total_ht=".price2num($this->total_ht);
			$sql .= ", total_tva=".price2num($this->total_tva);
			$sql .= ", total_ttc=".price2num($this->total_ttc);
			$sql .= ", total_localtax1=".price2num($this->total_localtax1);
			$sql .= ", total_localtax2=".price2num($this->total_localtax2);
		}
		$sql .= ", fk_product_fournisseur_price=".(!empty($this->fk_fournprice) ? "'".$this->db->escape($this->fk_fournprice)."'" : "null");
		$sql .= ", buy_price_ht=".price2num($this->pa_ht);
		$sql .= ", special_code=".((int) $this->special_code);
		$sql .= ", fk_parent_line=".($this->fk_parent_line > 0 ? (int) $this->fk_parent_line : "null");
		if (!empty($this->rang)) {
			$sql .= ", rang=".((int) $this->rang);
		}
		$sql .= ", date_start=".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
		$sql .= ", date_end=".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", fk_unit=".(!$this->fk_unit ? 'NULL' : $this->fk_unit);

		// Multicurrency
		$sql .= ", multicurrency_subprice=".price2num($this->multicurrency_subprice);
		$sql .= ", multicurrency_total_ht=".price2num($this->multicurrency_total_ht);
		$sql .= ", multicurrency_total_tva=".price2num($this->multicurrency_total_tva);
		$sql .= ", multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc);

		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
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
				$result = $this->call_trigger('LINEPROPAL_MODIFY', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
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

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet SET";
		$sql .= " total_ht=".price2num($this->total_ht, 'MT');
		$sql .= ",total_tva=".price2num($this->total_tva, 'MT');
		$sql .= ",total_ttc=".price2num($this->total_ttc, 'MT');
		$sql .= " WHERE rowid = ".((int) $this->rowid);

		dol_syslog("PropaleLigne::update_total", LOG_DEBUG);

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
