<?php
/* Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modFacture_CouffignalFactureSituation.class.php
 * \ingroup facture
 * \brief   Couffignal facture situation.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/FactureTools.php';


/**
 *  Class of triggers for MyModule module
 */
class InterfaceCouffignalFactureSituation extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "facture";
		$this->description = "Couffignal facture situation.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'couffignal_facture_situation';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string $action Event action code
	 * @param CommonObject $object Object
	 * @param User $user Object user
	 * @param Translate $langs Object langs
	 * @param Conf $conf Object conf
	 * @return int                    Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('facture') || !isModEnabled('project')) {
			return 0;
		}

		if (($object instanceof Facture) && ((int) $object->type === Facture::TYPE_SITUATION)) {
			if ($action === 'BILL_VALIDATE') {
				$difference = abs(round(FactureTools::calculateDifference($this->db, $object), 2));

				if ($difference <= 0.01) {
					return 1;
				}

				if ($difference < 5) {
					setEventMessage($langs->trans('InvoiceSituationMustHaveOrdersImportedForBeingValidated', number_format($difference, 2)), 'warnings');
					return 1;
				}

				setEventMessage($langs->trans('InvoiceSituationMustHaveOrdersImportedForBeingValidated', number_format($difference, 2)), 'errors');
				return -1;
			}
		}

		// SpecialCode devis client
		if (($object instanceof PropaleLigne)) {
			if ($action === 'LINEPROPAL_INSERT') {
				$productSpecialCode = $this->getSpecialCodeFromProductId($object->fk_product);
				if ($productSpecialCode === 0) {
					return 0;
				}
				$object->special_code = $productSpecialCode;
				$object->update(true);
				return 1;
			}
		}

		// SpecialCode devis fournisseur
		if (($object instanceof SupplierProposalLine)) {
			if ($action === 'LINESUPPLIER_PROPOSAL_INSERT') {
				$productSpecialCode = $this->getSpecialCodeFromProductId($object->fk_product);
				if ($productSpecialCode === 0) {
					return 0;
				}
				$object->special_code = $productSpecialCode;
				$object->update(true);
				return 1;
			}
		}

		// SpecialCode commande client
		if (($object instanceof OrderLine)) {
			if ($action === 'LINEORDER_INSERT') {
				$productSpecialCode = $this->getSpecialCodeFromProductId($object->fk_product);
				if ($productSpecialCode === 0) {
					return 0;
				}
				$object->special_code = $productSpecialCode;
				$object->update($user, true);
				return 1;
			}
		}

		// SpecialCode commande fournisseur
		if (($object instanceof CommandeFournisseurLigne)) {
			if ($action === 'LINEORDER_SUPPLIER_CREATE') {
				$productSpecialCode = $this->getSpecialCodeFromProductId($object->fk_product);
				if ($productSpecialCode === 0) {
					return 0;
				}
				$object->special_code = $productSpecialCode;
				$object->update(true);
				return 1;
			}
		}

		// SpecialCode facture client
		if (($object instanceof FactureLigne)) {
			if ($action === 'LINEBILL_INSERT') {
				$productSpecialCode = $this->getSpecialCodeFromProductId($object->fk_product);
				if ($productSpecialCode === 0) {
					return 0;
				}

				$object->special_code = $productSpecialCode;
				$object->update($user, true);
				return 1;
			}
		}

		// SpecialCode facture fournisseur
		if (($object instanceof SupplierInvoiceLine)) {
			if ($action === 'LINEBILL_SUPPLIER_CREATE') {
				$productSpecialCode = $this->getSpecialCodeFromProductId($object->fk_product);
				if ($productSpecialCode === 0) {
					return 0;
				}
				$object->special_code = $productSpecialCode;

				/**
				 * SupplierInvoiceLine::update() does not update the special_code :-/
				 */
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn_det t';
				$sql .= ' SET t.special_code = '.$this->db->escape($object->special_code);
				$sql .= ' WHERE t.rowid = '.(int) $object->id;
				$this->db->query($sql);
				if ($this->db->error()) {
					setEventMessage($this->db->lasterror(), 'errors');
					return -1;
				}

				return 1;
			}
		}

		return 0;
	}

	/**
	 * Get special code from product id.
	 *
	 * @param int $productId ID of the product
	 * @return int
	 */
	private function getSpecialCodeFromProductId($productId): int
	{
		$product = new Product($this->db);
		$product->fetch($productId);
		if (empty($product->array_options) || !array_key_exists('options_specialcodeauto', $product->array_options)) {
			return 0;
		}
		return (int) $product->array_options['options_specialcodeauto'];
	}
}
