<?php
/* Copyright (C) 2014-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		Charlene Benke			<charlene@patas-monkey.com>

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
 * or see https://www.gnu.org/
 */


/**
 *
 * Trait CommonSubtotal
 *
 * Add subtotals lines
 */
trait CommonSubtotal
{
	/**
	 * @var int
	 * Type for subtotals module lines
	 */
	public static $PRODUCT_TYPE = 9;

	/**
	 * @var array<string>
	 * Options for subtotals module title lines
	 */
	public static $TITLE_OPTIONS = ['titleshowuponpdf', 'titleshowtotalexludingvatonpdf', 'titleforcepagebreak'];

	/**
	 * @var array<string>
	 * Options for subtotals module subtotal lines
	 */
	public static $SUBTOTAL_OPTIONS = ['subtotalshowtotalexludingvatonpdf'];

	/**
	 * @var string[] element of allowed module class
	 */
	public static $ALLOWED_TYPES = [
		'propal',
		'commande',
		'facture',
		'facturerec',
		'shipping',
		'supplier_proposal',
		'order_supplier',
		'invoice_supplier',
	];


	/**
	 * Adds a subtotals line to a document.
	 * This function inserts a subtotal line based on the given parameters.
	 *
	 * @param Translate						$langs  		Translation.
	 * @param string						$desc			Description of the line.
	 * @param int							$depth			Level of the line (>0 for title lines, <0 for subtotal lines)
	 * @param array<string,string>|string	$options		Subtotal options for pdf view
	 * @param int							$parent_line	ID of the parent line for shipments
	 * @return int									ID of the added line if successful, 0 on warning, -1 on error
	 *
	 * @phan-suppress PhanUndeclaredMethod
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function addSubtotalLine($langs, $desc, $depth, $options = array(), $parent_line = 0)
	{
		if (empty($desc)) {
			$this->errors[] = $langs->trans("TitleNeedDesc");
			return -1;
		}
		$current_module = $this->element;
		// Ensure the object is one of the supported types
		if (!in_array($current_module, self::$ALLOWED_TYPES)) {
			$this->errors[] = $langs->trans("UnsupportedModuleError");
			return -1; // Unsupported type
		}
		$error = 0;
		$desc = dol_html_entity_decode($desc, ENT_QUOTES);
		$rang = -1;
		$next_line = false;
		$result = 0;

		if ($depth < 0 && $current_module != 'shipping') {
			foreach ($this->lines as $line) {
				if (!$next_line && $line->desc == $desc && $line->qty == -$depth) {
					$next_line = true;
					continue;
				}
				if ($next_line && $line->desc == $desc && $line->qty == $depth) {
					$next_line = false;
					continue;
				}
				if ($next_line && $line->special_code == SUBTOTALS_SPECIAL_CODE && abs($line->qty) <= abs($depth)) {
					$rang = $line->rang;
					break;
				}
			}
		}

		if ($depth > 0 && $current_module != 'shipping') {
			$max_existing_level = 0;

			foreach ($this->lines as $line) {
				if ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty > $max_existing_level) {
					$max_existing_level = $line->qty;
				}
			}

			if ($max_existing_level+1 < $depth) {
				$depth = $max_existing_level+1;
				$this->errors[] = $langs->trans("TitleAddedLevelTooHigh", $depth);

				$error ++;
			}
		}

		// Add the line calling the right module
		if ($current_module == 'facture' && $this instanceof Facture) {
			$result = $this->addline(
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				0,						// FK product
				0,						// Discount percentage
				'',						// Date start
				'',						// Date end
				0,						// FK code ventilation
				0,						// Info bits
				0,						// FK remise except
				'',						// Price base type
				0,						// PU ttc
				self::$PRODUCT_TYPE,	// Type
				$rang,					// Rang
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'propal' && $this instanceof Propal) {
			$result = $this->addline(
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				0,						// FK product
				0,						// Discount percentage
				'',						// Price base type
				0,						// PU ttc
				0,						// Info bits
				self::$PRODUCT_TYPE,	// Type
				$rang,					// Rang
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'commande' && $this instanceof Commande) {
			$result = $this->addline(
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				0,						// FK product
				0,						// Discount percentage
				0,						// Info bits
				0,						// FK remise except
				'',						// Price base type
				0,						// PU ttc
				'',						// Date start
				'',						// Date end
				self::$PRODUCT_TYPE,	// Type
				$rang,					// Rang
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'shipping' && $this instanceof Expedition) {
			$result = $this->addline(
				0,						// Warehouse ID
				(int) $parent_line,		// Source line
				$depth					// Quantity
			);
		} elseif ($current_module == 'facturerec' && $this instanceof FactureRec) {
			$rang = $rang == -1 ? $rang : $rang-1;
			$result = $this->addline(
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				0,						// FK product
				0,						// Discount percentage
				'',						// Price base type
				0,						// Info bits
				0,						// FK remise except
				0,						// PU ttc
				self::$PRODUCT_TYPE,	// Type
				$rang,					// Rang
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
			$this->fetch_lines();
		} elseif ($current_module == 'supplier_proposal' && $this instanceof SupplierProposal) {
			$rang = $rang == -1 ? $rang : $rang-1;
			$result = $this->addline(
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				0,						// FK product
				0,						// Discount percentage
				'',						// Price base type
				0,						// PU ttc
				0,						// Info bits
				self::$PRODUCT_TYPE,	// Type
				$rang,					// Rang
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'order_supplier' && $this instanceof CommandeFournisseur) {
			$rang = $rang == -1 ? $rang : $rang-1;
			$result = $this->addline(
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				0,						// FK product
				0,						// fk fourn price
				'',						// ref supplier
				0,						// Remise percent
				'',						// Price base type
				0,						// PU ttc
				self::$PRODUCT_TYPE,	// Type
				0,						// info bits
				0,						// no trigger
				null,					// Date start
				null,					// Date end
				[],						// array_options
				null,					// fk_unit
				0,						// pu ht devise
				'',						// origin type
				0,						// origin id
				$rang,					// Rang
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'invoice_supplier' && $this instanceof FactureFournisseur) {
			$rang = $rang == -1 ? $rang : $rang-1;
			$result = $this->addline(
				$desc,					// Description
				0,						// Unit price
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				$depth,					// Quantity
				0,						// FK product
				0,						// Remise percent
				'',						// Date start
				'',						// Date end
				0,						// Code ventilation
				0,						// info bits
				'',						// Price base type
				self::$PRODUCT_TYPE,	// Type
				$rang,					// Rang
				0,						// no trigger
				[],						// array_options
				null,					// fk_unit
				0,						// origin id
				0,						// pu ht devise
				'',						// ref supplier
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'fichinter' && $this instanceof Fichinter) {
			global $user;
			$result = $this->addline(
				$user,					// user
				$this->id,				// fk_fichinter
				$desc,					// Description
				0,						// dateintervention
				$depth,					// duration
				[],						// arrayoption
				self::$PRODUCT_TYPE,	// Type
				$rang,					// Rang
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		}


		if ($current_module != 'shipping') {
			foreach ($this->lines as $line) {
				'@phan-var-force CommonObjectLine $line';
				/** @var CommonObjectLine $line */
				if ($line->id == $result) {
					$line->extraparams["subtotal"] = $options;
					$line->setExtraParameters();
				}
			}
		}

		if ($result < 0) {
			return $result;
		}

		return $error > 0 ? 0 : $result;
	}

	/**
	 * Deletes a subtotal or a title line from a document.
	 * If the corresponding subtotal line exists and second parameter true, it will also be deleted.
	 *
	 * @param Translate	$langs					Translation.
	 * @param int		$id						ID of the line to delete
	 * @param bool		$correspondingstline	If true, also deletes the corresponding subtotal line
	 * @param User		$user					performing the deletion (used for permissions in some modules)
	 * @return int								ID of deleted line if successful, -1 on error
	 *
	 * @phan-suppress PhanUndeclaredMethod
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function deleteSubtotalLine($langs, $id, $correspondingstline = false, $user = null)
	{
		$current_module = $this->element;
		// Ensure the object is one of the supported types
		if (!in_array($current_module, self::$ALLOWED_TYPES)) {
			$this->errors[] = $langs->trans("UnsupportedModuleError");
			return -1; // Unsupported type
		}

		$result = 0;

		if ($correspondingstline) {
			$oldDesc = "";
			$oldDepth =  0;
			foreach ($this->lines as $line) {
				if ($line->id == $id) {
					$oldDesc = $line->desc;
					$oldDepth = $line->qty;
				}
				if ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty == -$oldDepth && $line->desc == $oldDesc) {
					$this->deleteSubtotalLine($langs, $line->id, false, $user);
					break;
				}
			}
		}

		// Add the line calling the right module
		if ($current_module == 'facture' && $this instanceof Facture) {
			$rowid = $id; // for phan suspicious parameter order...
			$result = $this->deleteLine($rowid);
		} elseif ($current_module == 'propal' && $this instanceof Propal) {
			$rowid = $id; // for phan suspicious parameter order...
			$result = $this->deleteLine($rowid);
		} elseif ($current_module == 'commande' && $this instanceof Commande) {
			$lineid = $id; // for phan suspicious parameter order...
			$result = $this->deleteLine($user, $lineid);
		} elseif ($current_module == 'facturerec') {
			$line = new FactureLigneRec($this->db);
			$line->id = $id;
			$result = $line->delete($user);
		} elseif ($current_module == 'shipping') {
			$line = new ExpeditionLigne($this->db);
			$line->id = $id;
			$result = $line->delete($user);
		} elseif ($current_module == 'supplier_proposal') {
			$line = new SupplierProposalLine($this->db);
			$line->id = $id;
			$result = $line->delete($user);
		} elseif ($current_module == 'order_supplier') {
			$line = new CommandeFournisseurLigne($this->db);
			$line->id = $id;
			$result = $line->delete($user);
		} elseif ($current_module == 'invoice_supplier') {
			$line = new SupplierInvoiceLine($this->db);
			$line->id = $id;
			$result = $line->delete();
		}

		return $result >= 0 ? $result : -1; // Return line ID or false
	}

	/**
	 * Updates a subtotal line of a document.
	 * This function updates a subtotals line based on its id and the given parameters.
	 * Updating a title line updates the corresponding subtotal line except options.
	 *
	 * @param Translate						$langs  	Translation.
	 * @param int							$lineid  	ID of the line to update.
	 * @param string						$desc		Description of the line.
	 * @param int							$depth		Level of the line (>0 for title lines, <0 for subtotal lines)
	 * @param array<string,string>|string	$options	Subtotal options for pdf view
	 * @return int									ID of the added line if successful, 0 on warning, -1 on error
	 *
	 * @phan-suppress PhanUndeclaredMethod
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function updateSubtotalLine($langs, $lineid, $desc, $depth, $options) // @phpstan-ignore-line
	{
		$current_module = $this->element;
		// Ensure the object is one of the supported types
		if (!in_array($current_module, self::$ALLOWED_TYPES)) {
			$this->errors[] = $langs->trans("UnsupportedModuleError");
			return -1; // Unsupported type
		}

		$result = 0;
		$error = 0;

		$max_existing_level = 0;

		if ($depth>0) {
			foreach ($this->lines as $line) {
				if ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty > $max_existing_level && $line->id != $lineid) {
					$max_existing_level = $line->qty;
				}
			}
		}

		if ($max_existing_level+1 < $depth) {
			$depth = $max_existing_level+1;
			$this->errors[] = $langs->trans("TitleEditedLevelTooHigh");
			$error ++;
		}

		if ($depth>0) {
			$oldDesc = "";
			$oldDepth =  0;
			foreach ($this->lines as $line) {
				if ($line->id == $lineid) {
					$oldDesc = $line->desc;
					$oldDepth = $line->qty;
				}
				if ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty == -$oldDepth && $line->desc == $oldDesc) {
					$this->updateSubtotalLine($langs, $line->id, $desc, -$depth, !empty($line->extraparams["subtotal"]) ? $line->extraparams["subtotal"] : array());
					break;
				}
			}
		}

		// Update the line calling the right module
		if ($current_module == 'facture' && $this instanceof Facture) {
			$result = $this->updateline(
				$lineid, 				// ID of line to change
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// Discount percentage
				'',						// Date start
				'',						// Date end
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				'',						// Price base type
				0, 						// Info bits
				self::$PRODUCT_TYPE,	// Type
				0,						// FK parent line
				0,						// Skip update total
				0,						// FK fournprice
				0,						// PA ht
				'',						// Label
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'propal' && $this instanceof Propal) {
			$result = $this->updateline(
				$lineid, 				// ID of line to change
				0,						// Unit price
				$depth,					// Quantity
				0,						// Discount percentage
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				$desc,					// Description
				'',						// Price base type
				0,						// Info bits
				SUBTOTALS_SPECIAL_CODE, // Special code
				0, 						// FK parent line
				0, 						// Skip update total
				0, 						// FK fournprice
				0, 						// PA ht
				'',						// Label
				self::$PRODUCT_TYPE		// Type
			);
		} elseif ($current_module == 'commande' && $this instanceof Commande) {
			$result = $this->updateline(
				$lineid, 				// ID of line to change
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// Discount percentage
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				'',						// Price base type
				0,						// Info bits
				'',						// Date start
				'',						// Date end
				self::$PRODUCT_TYPE,	// Type
				0, 						// FK parent line
				0, 						// Skip update total
				0, 						// FK fournprice
				0, 						// PA ht
				'',						// Label
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'facturerec' && $this instanceof FactureRec) {
			$objectline = new FactureLigneRec($this->db);
			$objectline->fetch($lineid);
			$line_rang = $objectline->rang;
			$result = $this->updateline(
				$lineid,				// ID of line to change
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				0,						// FK parent line
				0,						// Discount percentage
				'',						// Price base type
				0,						// Info bits
				0,						// FK parent line
				0,						// PU ttc
				self::$PRODUCT_TYPE,	// Type
				$line_rang,				// Rang
				SUBTOTALS_SPECIAL_CODE	// Special code
			);
		} elseif ($current_module == 'supplier_proposal' && $this instanceof SupplierProposal) {
			$objectline = new SupplierProposalLine($this->db);
			$objectline->fetch($lineid);
			$line_rang = $objectline->rang;
			$result = $this->updateline(
				$lineid,				// ID of line to change
				0,						// Unit price
				$depth,					// Quantity
				0,						// Discount percentage
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				$desc,					// Description
				'',						// Price base type
				0,						// Info bits
				SUBTOTALS_SPECIAL_CODE,	// Special code
				0,						// FK parent line
				0,						//
				0,						//
				0,						//
				'',						//
				self::$PRODUCT_TYPE		// Type
			);
		} elseif ($current_module == 'order_supplier' && $this instanceof CommandeFournisseur) {
			$objectline = new CommandeFournisseurLigne($this->db);
			$objectline->fetch($lineid);
			$line_rang = $objectline->rang;
			// special code comes from old line
			$result = $this->updateline(
				$lineid,				// ID of line to change
				$desc,					// Description
				0,						// Unit price
				$depth,					// Quantity
				0,						// Discount percentage
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				'',						// Price base type
				0,						// Info bits
				self::$PRODUCT_TYPE,	// Type
				0,						// no trigger
				0,						//
				0,						//
				[],						//
				null					//
			);
		} elseif ($current_module == 'invoice_supplier' && $this instanceof FactureFournisseur) {
			$objectline = new SupplierInvoiceLine($this->db);
			$objectline->fetch($lineid);
			$line_rang = $objectline->rang;
			$result = $this->updateline(
				$lineid,				// ID of line to change
				$desc,					// Description
				0,						// Unit price
				0,						// VAT rate
				0,						// Local tax 1
				0,						// Local tax 2
				$depth,					// Quantity
				0,						// product id
				'',						// Price base type
				0,						// Info bits
				self::$PRODUCT_TYPE,	// Type
				0						// Discount percentage
			);
		}

		foreach ($this->lines as $line) {
			'@phan-var-force CommonObjectLine $line';
			/** @var CommonObjectLine $line */
			if ($line->id == $lineid) {
				$line->extraparams["subtotal"] = $options;
				$line->setExtraParameters();
			}
		}

		if ($result < 0) {
			return $result;
		}

		return $error > 0 ? 0 : $result;
	}

	/**
	 * Updates a block of lines of a document.
	 *
	 * @param Translate	$langs  	Translation.
	 * @param int		$linerang	Rang of the line to start from.
	 * @param string	$mode		Column to change (discount or vat).
	 * @param int		$value		Value of the change.
	 * @return int					Return integer < 0 if KO, 1 if OK
	 *
	 * @phan-suppress PhanUndeclaredMethod
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function updateSubtotalLineBlockLines($langs, $linerang, $mode, $value) // @phpstan-ignore-line
	{
		$current_module = $this->element;
		// Ensure the object is one of the supported types
		if (!in_array($current_module, self::$ALLOWED_TYPES)) {
			$this->errors[] = $langs->trans("UnsupportedModuleError");
			return -1; // Unsupported type
		}

		$result = 0;
		$linerang -= 1;

		$nb_lines = count($this->lines)+1;

		for ($i = $linerang+1; $i < $nb_lines; $i++) {
			if ($this->lines[$i]->special_code == SUBTOTALS_SPECIAL_CODE) {
				if (abs($this->lines[$i]->qty) <= (int) $this->lines[$linerang]->qty) {
					return 1;
				}
			} else {
				if ($current_module == 'facture' && $this instanceof Facture) {
					$result = $this->updateline(
						$this->lines[$i]->id,
						$this->lines[$i]->desc,
						$this->lines[$i]->subprice,
						$this->lines[$i]->qty,
						$mode == 'discount' ? $value : $this->lines[$i]->remise_percent,
						$this->lines[$i]->date_start,
						$this->lines[$i]->date_end,
						$mode == 'tva' ? $value : $this->lines[$i]->tva_tx,
						$this->lines[$i]->localtax1_tx,
						$this->lines[$i]->localtax2_tx,
						'HT',
						$this->lines[$i]->info_bits,
						$this->lines[$i]->product_type,
						$this->lines[$i]->fk_parent_line,
						0,
						$this->lines[$i]->fk_fournprice,
						$this->lines[$i]->pa_ht,
						$this->lines[$i]->label,
						$this->lines[$i]->special_code,
						$this->lines[$i]->array_options,
						$this->lines[$i]->situation_percent,
						$this->lines[$i]->fk_unit,
						$this->lines[$i]->multicurrency_subprice
					);
				} elseif ($current_module == 'commande' && $this instanceof Commande) {
					$result = $this->updateline(
						$this->lines[$i]->id,
						$this->lines[$i]->desc,
						$this->lines[$i]->subprice,
						$this->lines[$i]->qty,
						$mode == 'discount' ? $value : $this->lines[$i]->remise_percent,
						$mode == 'tva' ? $value : $this->lines[$i]->tva_tx,
						$this->lines[$i]->localtax1_rate,
						$this->lines[$i]->localtax2_rate,
						'HT',
						$this->lines[$i]->info_bits,
						$this->lines[$i]->date_start,
						$this->lines[$i]->date_end,
						$this->lines[$i]->product_type,
						$this->lines[$i]->fk_parent_line,
						0,
						$this->lines[$i]->fk_fournprice,
						$this->lines[$i]->pa_ht,
						$this->lines[$i]->label,
						$this->lines[$i]->special_code,
						$this->lines[$i]->array_options,
						$this->lines[$i]->fk_unit,
						$this->lines[$i]->multicurrency_subprice
					);
				} elseif ($current_module == 'propal' && $this instanceof Propal) {
					// Preserve the original entry mode of the line so the total is not drifted by rounding.
					$line_price_base_type = $this->lines[$i]->wasEnteredIncludingTax() ? 'TTC' : 'HT';
					$line_pu = ($line_price_base_type === 'TTC') ? $this->lines[$i]->subprice_ttc : $this->lines[$i]->subprice;
					$result = $this->updateline(
						$this->lines[$i]->id,
						$line_pu,
						$this->lines[$i]->qty,
						$mode == 'discount' ? $value : $this->lines[$i]->remise_percent,
						$mode == 'tva' ? $value : $this->lines[$i]->tva_tx,
						$this->lines[$i]->localtax1_rate,
						$this->lines[$i]->localtax2_rate,
						$this->lines[$i]->desc,
						$line_price_base_type,
						$this->lines[$i]->info_bits,
						$this->lines[$i]->special_code,
						$this->lines[$i]->fk_parent_line,
						0,
						$this->lines[$i]->fk_fournprice,
						$this->lines[$i]->pa_ht,
						$this->lines[$i]->label,
						$this->lines[$i]->product_type,
						$this->lines[$i]->date_start,
						$this->lines[$i]->date_end,
						$this->lines[$i]->array_options,
						$this->lines[$i]->fk_unit,
						$this->lines[$i]->multicurrency_subprice
					);
				}
				if ($result < 0) {
					return $result;
				}
			}
		}
		return 1;
	}

	/**
	 * Return the lines of the block that starts with a title line: the title line itself, all the lines
	 * that follow it and the subtotal line that closes it.
	 * The block ends on the subtotal line of the title, or as soon as a title or a subtotal line of the
	 * same level or of a higher level is found (when the block was not closed), or at the last line.
	 *
	 * @param int	$lineid		ID of the title line that starts the block
	 * @return array<int,CommonObjectLine>	Lines of the block, empty if the line is not a title line
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getSubtotalBlockLines($lineid)
	{
		$titleline = null;
		foreach ($this->lines as $line) {
			if ($line->id == $lineid && $line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty > 0) {
				$titleline = $line;
				break;
			}
		}
		if (is_null($titleline)) {
			return array();
		}

		$level = (int) $titleline->qty;
		$blocklines = array();
		$started = false;

		foreach ($this->lines as $line) {
			if (!$started) {
				if ($line->id == $titleline->id) {
					$started = true;
					$blocklines[] = $line;
				}
				continue;
			}
			if ($line->special_code == SUBTOTALS_SPECIAL_CODE && abs($line->qty) <= $level) {
				if ($line->qty == -$level && $line->desc == $titleline->desc) {
					$blocklines[] = $line;	// This is the subtotal line that closes the block
				}
				break;
			}
			$blocklines[] = $line;
		}

		return $blocklines;
	}

	/**
	 * Duplicate a block of lines (a title line, the lines it contains and its subtotal line).
	 * The copy is inserted just after the original block.
	 * A title line and its subtotal line are linked together by their description, so the description of
	 * each title line of the copy is suffixed with a number to stay unique inside the document.
	 *
	 * @param Translate	$langs		Translation.
	 * @param int		$lineid		ID of the title line that starts the block to duplicate.
	 * @param ?User		$user		User that makes the duplication.
	 * @return int					Number of duplicated lines if OK, -1 if KO
	 *
	 * @phan-suppress PhanUndeclaredMethod
	 * @phan-suppress PhanUndeclaredProperty
	 * @phan-suppress PhanPluginUnknownObjectMethodCall
	 */
	public function duplicateSubtotalBlock($langs, $lineid, $user = null)
	{
		$current_module = $this->element;
		// Ensure the object is one of the supported types
		$allowed_types = array('propal', 'commande', 'facture', 'facturerec');
		if (!in_array($current_module, $allowed_types)) {
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("UnsupportedModuleError");
			}
			return -1; // Unsupported type
		}

		// The lines are inserted without calling addline(), which is the method refusing to work on a
		// document that is not a draft, so we make that check here. A template invoice has no status.
		if ($current_module != 'facturerec' && (int) $this->status != 0) {
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("DuplicateBlockNeedDraft");
			}
			return -1;
		}

		$blocklines = $this->getSubtotalBlockLines($lineid);
		if (empty($blocklines)) {
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("TitleLineNotFound");
			}
			return -1;
		}

		// Descriptions already used by a title or a subtotal line of the document
		$existingdesc = array();
		foreach ($this->lines as $line) {
			if ($line->special_code == SUBTOTALS_SPECIAL_CODE) {
				$existingdesc[$line->desc] = $line->desc;
			}
		}

		// New description of each title and subtotal line of the block, to keep the descriptions unique
		$newdesc = array();
		foreach ($blocklines as $line) {
			if ($line->special_code != SUBTOTALS_SPECIAL_CODE || isset($newdesc[$line->desc])) {
				continue;
			}
			$num = 2;
			while (isset($existingdesc[$line->desc.' ('.$num.')'])) {
				$num++;
			}
			$newdesc[$line->desc] = $line->desc.' ('.$num.')';
			$existingdesc[$newdesc[$line->desc]] = $newdesc[$line->desc];
		}

		$nboflines = count($blocklines);
		$error = 0;

		// The copy is first added at the end of the document, then all the lines are renumbered in the
		// wanted order. We don't move the existing lines by computing their rang, because a document may
		// already contain lines sharing the same rang, and we don't use line_order() either, because it
		// only renumbers the lines having no parent line.
		$rang = 0;
		foreach ($this->lines as $line) {
			$rang = max($rang, (int) $line->rang);
		}

		$this->db->begin();

		$newidoflineid = array();	// Old line ID => new line ID, to keep the links between a parent line and its children

		foreach ($blocklines as $line) {
			if ($error) {
				break;
			}
			$rang++;
			$description = ($line->special_code == SUBTOTALS_SPECIAL_CODE && isset($newdesc[$line->desc])) ? $newdesc[$line->desc] : $line->desc;
			$fk_parent_line = (!empty($line->fk_parent_line) && isset($newidoflineid[$line->fk_parent_line])) ? $newidoflineid[$line->fk_parent_line] : 0;

			if ($current_module == 'facturerec') {
				// The line object of a template invoice has no insert() method, so we add the line with the object method
				$result = $this->addline( // @phpstan-ignore-line
					$description,					// Description @phpstan-ignore-line
					$line->subprice,				// Unit price @phpstan-ignore-line
					$line->qty,						// Quantity @phpstan-ignore-line
					$line->tva_tx,					// VAT rate @phpstan-ignore-line
					$line->localtax1_tx,			// Local tax 1 @phpstan-ignore-line
					$line->localtax2_tx,			// Local tax 2 @phpstan-ignore-line
					$line->fk_product,				// FK product @phpstan-ignore-line
					$line->remise_percent,			// Discount percentage @phpstan-ignore-line
					'HT',							// Price base type @phpstan-ignore-line
					$line->info_bits,				// Info bits @phpstan-ignore-line
					0,								// FK remise except @phpstan-ignore-line
					0,								// PU ttc @phpstan-ignore-line
					$line->product_type,			// Type @phpstan-ignore-line
					$rang,							// Rang @phpstan-ignore-line
					$line->special_code,			// Special code @phpstan-ignore-line
					$line->label,					// Label @phpstan-ignore-line
					$line->fk_unit,					// FK unit @phpstan-ignore-line
					$line->multicurrency_subprice,	// Unit price in currency @phpstan-ignore-line
					$line->date_start_fill,			// Date start fill @phpstan-ignore-line
					$line->date_end_fill,			// Date end fill @phpstan-ignore-line
					$line->fk_fournprice,			// FK fournprice @phpstan-ignore-line
					$line->pa_ht,					// PA ht @phpstan-ignore-line
					$fk_parent_line					// FK parent line @phpstan-ignore-line
				);
				$newline = new FactureLigneRec($this->db);
				if ($result > 0) {
					$newline->fetch($result);
				}
			} else {
				$newline = clone $line;
				$newline->id = 0;
				$newline->rowid = 0;
				$newline->desc = $description;
				$newline->description = $description;
				$newline->rang = $rang;
				$newline->fk_parent_line = $fk_parent_line;
				$newline->fk_remise_except = 0;		// A discount can be consumed only once
				if ($current_module == 'facture') {
					// Only an invoice line has this property, the copy is not the line of a previous situation
					$newline->fk_prev_id = null;
				}
				$newline->array_options = $line->array_options;
				if ($current_module == 'commande') {
					$result = $newline->insert($user); // @phpstan-ignore-line
				} else {
					$result = $newline->insert(); // @phpstan-ignore-line
				}
			}

			if ($result <= 0) {
				$error++;
				if (isset($this->errors)) {
					$this->errors = array_merge($this->errors, (array) $newline->errors);
					if (!empty($newline->error)) {
						$this->errors[] = $newline->error;
					}
				}
				break;
			}

			$newidoflineid[$line->id] = $newline->id;

			// The options of the subtotals lines are stored in the extra parameters, that are not saved by the insert
			if (!empty($line->extraparams)) {
				$newline->extraparams = $line->extraparams;
				$newline->setExtraParameters();
			}
		}

		if (!$error) {
			// Renumber all the lines, the copy being placed just after the block it was made from.
			// Every line gets a new rang, so a document that already had lines sharing the same rang is fixed.
			$lastidofblock = $blocklines[$nboflines - 1]->id;
			$neworder = array();
			foreach ($this->lines as $line) {
				$neworder[] = $line->id;
				if ($line->id == $lastidofblock) {
					$neworder = array_merge($neworder, array_values($newidoflineid));
				}
			}

			$rang = 0;
			foreach ($neworder as $lineidtomove) {
				$rang++;
				if ($this->updateRangOfLine($lineidtomove, $rang) < 0) {
					$error++;
					break;
				}
			}
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		}

		$this->update_price(1);
		$this->db->commit();

		return $nboflines;
	}

	/**
	 * Return the total_ht of lines that are above the current line (excluded) and that are not a subtotal line
	 * until a title line of the same level is found
	 *
	 * @param object	$line	Line that needs the subtotal amount.
	 * @return string	$total_ht
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getSubtotalLineAmount($line)
	{
		$final_amount = 0;
		for ($i = $line->rang-1; $i > 0; $i--) {
			if (is_null($this->lines[$i-1]) || $this->lines[$i-1]->rang >= $line->rang) {
				continue;
			}
			if ($this->lines[$i-1]->special_code == SUBTOTALS_SPECIAL_CODE && $this->lines[$i-1]->qty > 0) {
				if ($this->lines[$i-1]->qty <= abs($line->qty)) {
					return price($final_amount);
				}
			} else {
				$final_amount += $this->lines[$i-1]->total_ht;
			}
		}
		return price($final_amount);
	}

	/**
	 * Return the multicurrency_total_ht of lines that are above the current line (excluded) and that are not a subtotal line
	 * until a title line of the same level is found
	 *
	 * @param object	$line	Line that needs the subtotal amount with multicurrency mod activated.
	 * @return string	$total_ht
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getSubtotalLineMulticurrencyAmount($line)
	{
		$final_amount = 0;
		for ($i = $line->rang-1; $i > 0; $i--) {
			if (is_null($this->lines[$i-1]) || $this->lines[$i-1]->rang >= $line->rang) {
				continue;
			}
			if ($this->lines[$i-1]->special_code == SUBTOTALS_SPECIAL_CODE && $this->lines[$i-1]->qty>0) {
				if ($this->lines[$i-1]->qty <= abs($line->qty)) {
					return price($final_amount);
				}
			} else {
				$final_amount += $this->lines[$i-1]->multicurrency_total_ht;
			}
		}
		return price($final_amount);
	}

	/**
	 * Retrieve the background color associated with a specific subtotal level.
	 * A positive level asks for the color of a title line (header of the block), a negative one
	 * for the color of a subtotal line (footer of the block). When no color is defined for the
	 * footer, the color of the header is used, so the behaviour stays the same as before the
	 * footer color was introduced.
	 * Note: the color of the footer is used on the screen only, the PDF keeps the color of the title
	 * line for both lines of the block.
	 *
	 * @param int|float $level The level of the subtotal for which the color is requested.
	 * @return string The background color in hexadecimal format, empty if not set.
	 */
	public function getSubtotalColors($level)
	{
		$color = '';
		if ($level < 0) {
			$color = getDolGlobalString('SUBTOTAL_FOOT_COLOR_LEVEL_'.abs($level));
		}
		if (empty($color)) {
			$color = getDolGlobalString('SUBTOTAL_BACK_COLOR_LEVEL_'.abs($level));
		}
		return $color;
	}

	/**
	 * Retrieve the text color associated with a specific subtotal level.
	 * A positive level asks for the color of a title line (header of the block), a negative one for the
	 * color of a subtotal line (footer of the block). When no color is defined for the footer, the color
	 * of the header is used. An empty return value means no color was set, so the caller decides one
	 * from the background color, which is the behaviour of the module when no text color is configured.
	 * Note: used on the screen only, the PDF always computes the text color from the background.
	 *
	 * @param int|float $level The level of the subtotal for which the text color is requested.
	 * @return string The text color in hexadecimal format, empty if not set.
	 */
	public function getSubtotalTextColors($level)
	{
		$color = '';
		if ($level < 0) {
			$color = getDolGlobalString('SUBTOTAL_FOOT_TEXT_COLOR_LEVEL_'.abs($level));
		}
		if (empty($color)) {
			$color = getDolGlobalString('SUBTOTAL_TEXT_COLOR_LEVEL_'.abs($level));
		}
		return $color;
	}

	/**
	 * Return the CSS color to use for the content of a subtotal line, ready to be put in a style
	 * attribute. When no text color is configured for the level, a color is chosen from the background
	 * color so the text stays readable.
	 *
	 * @param int|float	$level		Level of the subtotal line (>0 for a title line, <0 for a subtotal line)
	 * @param string	$darkcolor	Color to use on a light background when no text color is configured
	 * @return string				A CSS color
	 */
	public function getSubtotalCssTextColor($level, $darkcolor = 'black')
	{
		$color = $this->getSubtotalTextColors($level);
		if (!empty($color)) {
			return '#'.$color;
		}
		return colorIsLight($this->getSubtotalColors($level)) ? $darkcolor : 'white';
	}

	/**
	 * Retrieve current object possible titles to choose from
	 *
	 * @return array<string,string> The set of titles, empty if no title line set.
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getPossibleTitles()
	{
		$titles = array();
		foreach ($this->lines as $line) {
			if ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty > 0) {
				$titles[$line->desc] = $line->desc;
			}
			if ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty < 0) {
				unset($titles[$line->desc]);
			}
		}
		return $titles;
	}

	/**
	 * Retrieve the current object possible levels (defined in admin page)
	 *
	 * @param Translate $langs 		Translations.
	 * @return array<int,string>	The set of possible levels, empty if not defined correctly.
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getPossibleLevels($langs)
	{
		$depth_array = array();
		$max_depth = getDolGlobalString('SUBTOTAL_'.strtoupper($this->element).'_MAX_DEPTH', 2);
		for ($i = 0; $i < $max_depth; $i++) {
			$depth_array[$i + 1] = $langs->trans("SubtotalLevel", $i + 1);
		}
		return $depth_array;
	}

	/**
	 * Returns an array with the IDs of the line that we don't need to show to avoid empty blocks
	 *
	 * @return array<int>	$total_ht
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getDisabledShippmentSubtotalLines()
	{
		$toDisableLines = array();
		$toDisable = true;
		$oldDesc = "";
		$oldDepth =  0;

		foreach ($this->lines as $titleLine) {
			if ($titleLine->special_code != SUBTOTALS_SPECIAL_CODE || $titleLine->qty <= 0) {
				continue;
			}
			foreach ($this->lines as $line) {
				if ($line->id == $titleLine->id) {
					$oldDesc = $line->desc;
					$oldDepth = $line->qty;
				}
				if ($line->special_code != SUBTOTALS_SPECIAL_CODE && $line->fk_product_type == 0 && !empty($oldDesc) && !empty($oldDepth)) {
					$toDisable = false;
				}
				if ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty == -$oldDepth && $line->desc == $oldDesc) {
					if ($toDisable) {
						$toDisableLines = array_merge($toDisableLines, array($titleLine->id, $line->id));
					}
					$oldDesc = "";
					$oldDepth =  0;
					$toDisable = true;
					break;
				}
			}
		}
		return $toDisableLines;
	}
}
