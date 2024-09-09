<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2020	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2010-2018	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2015	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2018		Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2024	Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2018-2022	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2021		Josep Lluís Amador		<joseplluis@lliuretic.cat>
 * Copyright (C) 2022		Gauthier VERDOL			<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		Solution Libre SAS		<contact@solution-libre.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
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
 *	\file       htdocs/fourn/class/fournisseur.commande.ligne.class.php
 *	\ingroup    fournisseur,commande
 *	\brief      File of class to manage supplier order lines
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

/**
 *  Class to manage line orders
 */
class CommandeFournisseurLigne extends CommonOrderLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'commande_fournisseurdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'commande_fournisseurdet';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'commande_fournisseur';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_commande_fournisseur';

	/**
	 * @var CommandeFournisseurLigne
	 */
	public $oldline;

	/**
	 * Id of parent order
	 * @var int
	 */
	public $fk_commande;

	// From llx_commande_fournisseurdet
	/**
	 * @var int ID
	 */
	public $fk_parent_line;

	/**
	 * @var int ID
	 */
	public $fk_facture;

	/**
	 * @var int rank
	 */
	public $rang = 0;

	/**
	 * @var int special code
	 */
	public $special_code = 0;

	/**
	 * Unit price without taxes
	 * @var float
	 */
	public $pu_ht;

	/**
	 * @var int|string|null
	 */
	public $date_start;

	/**
	 * @var int|string|null
	 */
	public $date_end;

	/**
	 * @var int
	 */
	public $fk_fournprice;

	/**
	 * @var float
	 */
	public $packaging;

	/**
	 * @var int
	 */
	public $pa_ht;

	// From llx_product_fournisseur_price

	/**
	 * Supplier reference of price when we added the line. May have been changed after line was added.
	 * @var string
	 */
	public $ref_supplier;

	/**
	 * @var string ref supplier
	 * @deprecated
	 * @see $ref_supplier
	 */
	public $ref_fourn;

	/**
	 * @var float|string
	 */
	public $remise;


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
	 *  Load line order
	 *
	 *  @param  int		$rowid      Id line order
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_product, cd.product_type, cd.description, cd.qty, cd.tva_tx, cd.special_code,';
		$sql .= ' cd.localtax1_tx, cd.localtax2_tx, cd.localtax1_type, cd.localtax2_type, cd.ref as ref_supplier,';
		$sql .= ' cd.remise, cd.remise_percent, cd.subprice,';
		$sql .= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_ttc,';
		$sql .= ' cd.total_localtax1, cd.total_localtax2,';
		$sql .= ' p.ref as product_ref, p.label as product_label, p.description as product_desc,';
		$sql .= ' cd.date_start, cd.date_end, cd.fk_unit,';
		$sql .= ' cd.multicurrency_subprice, cd.multicurrency_total_ht, cd.multicurrency_total_tva, cd.multicurrency_total_ttc,';
		$sql .= ' c.fk_soc as socid';
		$sql .= ' FROM '.$this->db->prefix().'commande_fournisseur as c, '.$this->db->prefix().'commande_fournisseurdet as cd';
		$sql .= ' LEFT JOIN '.$this->db->prefix().'product as p ON cd.fk_product = p.rowid';
		$sql .= ' WHERE cd.fk_commande = c.rowid AND cd.rowid = '.((int) $rowid);

		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			if (!empty($objp)) {
				$this->rowid = $objp->rowid;
				$this->id               = $objp->rowid;
				$this->fk_commande      = $objp->fk_commande;
				$this->desc             = $objp->description;
				$this->qty              = $objp->qty;
				$this->ref_fourn        = $objp->ref_supplier;
				$this->ref_supplier     = $objp->ref_supplier;
				$this->subprice         = $objp->subprice;
				$this->tva_tx           = $objp->tva_tx;
				$this->localtax1_tx		= $objp->localtax1_tx;
				$this->localtax2_tx		= $objp->localtax2_tx;
				$this->localtax1_type	= $objp->localtax1_type;
				$this->localtax2_type	= $objp->localtax2_type;
				$this->remise           = $objp->remise;
				$this->remise_percent   = $objp->remise_percent;
				$this->fk_product       = $objp->fk_product;
				$this->info_bits        = $objp->info_bits;
				$this->total_ht         = $objp->total_ht;
				$this->total_tva        = $objp->total_tva;
				$this->total_localtax1	= $objp->total_localtax1;
				$this->total_localtax2	= $objp->total_localtax2;
				$this->total_ttc        = $objp->total_ttc;
				$this->product_type     = $objp->product_type;
				$this->special_code     = $objp->special_code;

				$this->ref = $objp->product_ref;

				$this->product_ref      = $objp->product_ref;
				$this->product_label    = $objp->product_label;
				$this->product_desc     = $objp->product_desc;

				if (getDolGlobalInt('PRODUCT_USE_SUPPLIER_PACKAGING')) {
					// TODO We should not fetch this properties into the fetch_lines. This is NOT properties of a line.
					// Move this into another method and call it when required.

					// Take better packaging for $objp->qty (first supplier ref quantity <= $objp->qty)
					$sqlsearchpackage = 'SELECT rowid, packaging FROM '.$this->db->prefix()."product_fournisseur_price";
					$sqlsearchpackage .= ' WHERE entity IN ('.getEntity('product_fournisseur_price').")";
					$sqlsearchpackage .= " AND fk_product = ".((int) $objp->fk_product);
					$sqlsearchpackage .= " AND ref_fourn = '".$this->db->escape($objp->ref_supplier)."'";
					$sqlsearchpackage .= " AND quantity <= ".((float) $objp->qty);	// required to be qualified
					$sqlsearchpackage .= " AND (packaging IS NULL OR packaging = 0 OR packaging <= ".((float) $objp->qty).")";	// required to be qualified
					$sqlsearchpackage .= " AND fk_soc = ".((int) $objp->socid);
					$sqlsearchpackage .= " ORDER BY packaging ASC";		// Take the smaller package first
					$sqlsearchpackage .= " LIMIT 1";

					$resqlsearchpackage = $this->db->query($sqlsearchpackage);
					if ($resqlsearchpackage) {
						$objsearchpackage = $this->db->fetch_object($resqlsearchpackage);
						if ($objsearchpackage) {
							$this->fk_fournprice = $objsearchpackage->rowid;
							$this->packaging     = $objsearchpackage->packaging;
						}
					} else {
						$this->error = $this->db->lasterror();
						return -1;
					}
				}

				$this->date_start       		= $this->db->jdate($objp->date_start);
				$this->date_end         		= $this->db->jdate($objp->date_end);
				$this->fk_unit = $objp->fk_unit;

				$this->multicurrency_subprice	= $objp->multicurrency_subprice;
				$this->multicurrency_total_ht	= $objp->multicurrency_total_ht;
				$this->multicurrency_total_tva	= $objp->multicurrency_total_tva;
				$this->multicurrency_total_ttc	= $objp->multicurrency_total_ttc;

				$this->fetch_optionals();

				$this->db->free($result);
				return 1;
			} else {
				$this->error = 'Supplier order line  with id='.$rowid.' not found';
				dol_syslog(get_class($this)."::fetch Error ".$this->error, LOG_ERR);
				return 0;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0)
	{
		global $conf, $user;

		$error = 0;

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
			$this->localtax1_type = '0';
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = '0';
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

		// Multicurrency
		if (!empty($this->multicurrency_code)) {
			list($this->fk_multicurrency, $this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

		// Check parameters
		if ($this->product_type < 0) {
			return -1;
		}

		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.$this->db->prefix().$this->table_element;
		$sql .= " (fk_commande, label, description, date_start, date_end,";
		$sql .= " fk_product, product_type, special_code, rang,";
		$sql .= " qty, vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, remise_percent, subprice, ref,";
		$sql .= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, fk_unit,";
		$sql .= " fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc,";
		$sql .= " fk_parent_line)";
		$sql .= " VALUES (".$this->fk_commande.", '".$this->db->escape($this->label)."','".$this->db->escape($this->desc)."',";
		$sql .= " ".($this->date_start ? "'".$this->db->idate($this->date_start)."'" : "null").",";
		$sql .= " ".($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		if ($this->fk_product) {
			$sql .= $this->fk_product.",";
		} else {
			$sql .= "null,";
		}
		$sql .= "'".$this->db->escape($this->product_type)."',";
		$sql .= (int) $this->special_code . ",";
		$sql .= "'".$this->db->escape($this->rang)."',";
		$sql .= "'".$this->db->escape($this->qty)."', ";
		$sql .= " ".(empty($this->vat_src_code) ? "''" : "'".$this->db->escape($this->vat_src_code)."'").",";
		$sql .= " ".price2num($this->tva_tx).", ";
		$sql .= " ".price2num($this->localtax1_tx).",";
		$sql .= " ".price2num($this->localtax2_tx).",";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= " ".((float) $this->remise_percent).", ".price2num($this->subprice, 'MU').", '".$this->db->escape($this->ref_supplier)."',";
		$sql .= " ".price2num($this->total_ht).",";
		$sql .= " ".price2num($this->total_tva).",";
		$sql .= " ".price2num($this->total_localtax1).",";
		$sql .= " ".price2num($this->total_localtax2).",";
		$sql .= " ".price2num($this->total_ttc).",";
		$sql .= ($this->fk_unit ? "'".$this->db->escape($this->fk_unit)."'" : "null");
		$sql .= ", ".($this->fk_multicurrency ? ((int) $this->fk_multicurrency) : "null");
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".($this->multicurrency_subprice ? price2num($this->multicurrency_subprice) : '0');
		$sql .= ", ".($this->multicurrency_total_ht ? price2num($this->multicurrency_total_ht) : '0');
		$sql .= ", ".($this->multicurrency_total_tva ? price2num($this->multicurrency_total_tva) : '0');
		$sql .= ", ".($this->multicurrency_total_ttc ? price2num($this->multicurrency_total_ttc) : '0');
		$sql .= ", ".((!empty($this->fk_parent_line) && $this->fk_parent_line > 0) ? $this->fk_parent_line : 'null');
		$sql .= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
			$this->rowid = $this->id;

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEORDER_SUPPLIER_CREATE', $user);
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
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->errors[] = ($this->errors ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->errors[] = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}
	/**
	 *	Update the line object into db
	 *
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int		Return integer <0 si ko, >0 si ok
	 */
	public function update($notrigger = 0)
	{
		global $user;

		$error = 0;

		$this->db->begin();

		$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
		$sql .= "  description='".$this->db->escape($this->desc)."'";
		$sql .= ", ref='".$this->db->escape($this->ref_supplier)."'";
		$sql .= ", subprice='".price2num($this->subprice)."'";
		//$sql.= ",remise='".price2num($remise)."'";
		$sql .= ", remise_percent='".price2num($this->remise_percent)."'";

		$sql .= ", vat_src_code = '".(empty($this->vat_src_code) ? '' : $this->vat_src_code)."'";
		$sql .= ", tva_tx='".price2num($this->tva_tx)."'";
		$sql .= ", localtax1_tx='".price2num($this->localtax1_tx)."'";
		$sql .= ", localtax2_tx='".price2num($this->localtax2_tx)."'";
		$sql .= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= ", qty='".price2num($this->qty)."'";
		$sql .= ", date_start=".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
		$sql .= ", date_end=".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", info_bits='".$this->db->escape($this->info_bits)."'";
		$sql .= ", total_ht='".price2num($this->total_ht)."'";
		$sql .= ", total_tva='".price2num($this->total_tva)."'";
		$sql .= ", total_localtax1='".price2num($this->total_localtax1)."'";
		$sql .= ", total_localtax2='".price2num($this->total_localtax2)."'";
		$sql .= ", total_ttc='".price2num($this->total_ttc)."'";
		$sql .= ", product_type=".$this->product_type;
		$sql .= ", special_code=".(!empty($this->special_code) ? $this->special_code : 0);
		$sql .= ($this->fk_unit ? ", fk_unit='".$this->db->escape($this->fk_unit)."'" : ", fk_unit=null");

		// Multicurrency
		$sql .= ", multicurrency_subprice=".price2num($this->multicurrency_subprice);
		$sql .= ", multicurrency_total_ht=".price2num($this->multicurrency_total_ht);
		$sql .= ", multicurrency_total_tva=".price2num($this->multicurrency_total_tva);
		$sql .= ", multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc);

		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
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
				$result = $this->call_trigger('LINEORDER_SUPPLIER_MODIFY', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * 	Delete line in database
	 *
	 *  @param		User	$user		User making the change
	 *	@param      int     $notrigger  1=Disable call to triggers
	 *	@return     int                 Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		if (empty($user)) {
			global $user;
		}

		$error = 0;

		$this->db->begin();

		// extrafields
		$result = $this->deleteExtraFields();
		if ($result < 0) {
			$this->db->rollback();
			return -1;
		}

		$sql1 = 'UPDATE '.$this->db->prefix()."commandedet SET fk_commandefourndet = NULL WHERE rowid=".((int) $this->id);
		$resql = $this->db->query($sql1);
		if (!$resql) {
			$this->db->rollback();
			return -1;
		}

		$sql2 = 'DELETE FROM '.$this->db->prefix()."commande_fournisseurdet WHERE rowid=".((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql2);
		if ($resql) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEORDER_SUPPLIER_DELETE', $user);
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
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}
}
