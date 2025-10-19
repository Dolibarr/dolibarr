<?php
/* Copyright (C) 2014-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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

if (!defined('SUBTOTALS_SPECIAL_CODE')) {
	define('SUBTOTALS_SPECIAL_CODE', 81);
}

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
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("TitleNeedDesc");
			}
			return -1;
		}
		$current_module = $this->element;
		// Ensure the object is one of the supported types
		$allowed_types = [
			'propal',
			'commande',
			'facture',
			'facturerec',
			'shipping',
		];
		if (!in_array($current_module, $allowed_types)) {
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("UnsupportedModuleError");
			}
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
				if (isset($this->errors)) {
					$this->errors[] = $langs->trans("TitleAddedLevelTooHigh", $depth);
				}

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
		$allowed_types = [
			'propal',
			'commande',
			'facture',
			'facturerec',
			'shipping',
		];
		if (!in_array($current_module, $allowed_types)) {
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("UnsupportedModuleError");
			}
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
		$allowed_types = [
			'propal',
			'commande',
			'facture',
			'facturerec',
			'shipping',
		];
		if (!in_array($current_module, $allowed_types)) {
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("UnsupportedModuleError");
			}
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
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("TitleEditedLevelTooHigh");
			}
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
		$allowed_types = [
			'propal',
			'commande',
			'facture',
			'facturerec',
			'shipping',
		];
		if (!in_array($current_module, $allowed_types)) {
			if (isset($this->errors)) {
				$this->errors[] = $langs->trans("UnsupportedModuleError");
			}
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
						$this->lines[$i]->fk_parent_line, 0,
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
						$this->lines[$i]->fk_parent_line, 0,
						$this->lines[$i]->fk_fournprice,
						$this->lines[$i]->pa_ht,
						$this->lines[$i]->label,
						$this->lines[$i]->special_code,
						$this->lines[$i]->array_options,
						$this->lines[$i]->fk_unit,
						$this->lines[$i]->multicurrency_subprice
					);
				} elseif ($current_module == 'propal' && $this instanceof Propal) {
					$result = $this->updateline(
						$this->lines[$i]->id,
						$this->lines[$i]->subprice,
						$this->lines[$i]->qty,
						$mode == 'discount' ? $value : $this->lines[$i]->remise_percent,
						$mode == 'tva' ? $value : $this->lines[$i]->tva_tx,
						$this->lines[$i]->localtax1_rate,
						$this->lines[$i]->localtax2_rate,
						$this->lines[$i]->desc,
						'HT',
						$this->lines[$i]->info_bits,
						$this->lines[$i]->special_code,
						$this->lines[$i]->fk_parent_line, 0,
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
	 *
	 * @param int|float $level The level of the subtotal for which the color is requested.
	 * @return string|null The background color in hexadecimal format or null if not set.
	 */
	public function getSubtotalColors($level)
	{
		return getDolGlobalString('SUBTOTAL_BACK_COLOR_LEVEL_'.abs($level));
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
