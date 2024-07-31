<?php
/* Copyright (C) 2003-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013-2018	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2016-2021  Alexandre Spangaro		<aspangaro@open-dsi.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/supplier_invoice/mod_facture_fournisseur_tulip.php
 *	\ingroup    Supplier invoice
 *	\brief      File containing the Tulip Class of numbering models of suppliers invoices references
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';


/**
 * \class      mod_facture_fournisseur_tulip
 * \brief      Tulip Class of numbering models of suppliers invoices references
*/
class mod_facture_fournisseur_tulip extends ModeleNumRefSuppliersInvoices
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Tulip';

	/**
	 * @var string model name
	 */
	public $name = 'Tulip';


	/**
	 *  Returns the description of the model numbering
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $langs, $db;

		// Load translation files required by the page
		$langs->loadLangs(array("bills", "admin"));

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconstinvoice" value="SUPPLIER_INVOICE_TULIP_MASK">';
		$texte .= '<input type="hidden" name="maskconstreplacement" value="SUPPLIER_REPLACEMENT_TULIP_MASK">';
		$texte .= '<input type="hidden" name="maskconstcredit" value="SUPPLIER_CREDIT_TULIP_MASK">';
		$texte .= '<input type="hidden" name="maskconstdeposit" value="SUPPLIER_DEPOSIT_TULIP_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Invoice"), $langs->transnoentities("Invoice"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Invoice"), $langs->transnoentities("Invoice"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Setting the prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceStandard").')';
		$texte .= ':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskinvoice" value="'.getDolGlobalString("SUPPLIER_INVOICE_TULIP_MASK").'">', $tooltip, 1, 1).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button"value="'.$langs->trans("Modify").'"></td>';

		$texte .= '</tr>';

		// Prefix setting of credit note
		$texte .= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceAvoir").'):</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskcredit" value="'.getDolGlobalString("SUPPLIER_CREDIT_TULIP_MASK").'">', $tooltip, 1, 1).'</td>';
		$texte .= '</tr>';

		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
			// Parametrage du prefix des replacement
			$texte .= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceReplacement").'):</td>';
			$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskreplacement" value="'.getDolGlobalString("SUPPLIER_REPLACEMENT_TULIP_MASK").'">', $tooltip, 1, 1).'</td>';
			$texte .= '</tr>';
		}

		// Prefix setting of deposit
		$texte .= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceDeposit").'):</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskdeposit" value="'.getDolGlobalString("SUPPLIER_DEPOSIT_TULIP_MASK").'">', $tooltip, 1, 1).'</td>';
		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Returns a numbering example
	 *
	 *  @return     string     Example
	 */
	public function getExample()
	{
		global $conf, $langs, $mysoc;

		$old_code_client = $mysoc->code_client;
		$mysoc->code_client = 'CCCCCCCCCC';
		$numExample = $this->getNextValue($mysoc, '');
		$mysoc->code_client = $old_code_client;

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 * Return next value
	 *
	 * @param	Societe				$objsoc     Object third party
	 * @param  	FactureFournisseur	$object		Object invoice
	 * @param	string				$mode       'next' for next value or 'last' for last value
	 * @return 	string|0      					Value if OK, 0 if KO
	 */
	public function getNextValue($objsoc, $object, $mode = 'next')
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// Get Mask value
		$mask = '';
		if (is_object($object) && $object->type == 1) {
			$mask = getDolGlobalString("SUPPLIER_REPLACEMENT_TULIP_MASK");
			if (!$mask) {
				$mask = getDolGlobalString("SUPPLIER_INVOICE_TULIP_MASK");
			}
		} elseif (is_object($object) && $object->type == 2) {
			$mask = getDolGlobalString("SUPPLIER_CREDIT_TULIP_MASK");
		} elseif (is_object($object) && $object->type == 3) {
			$mask = getDolGlobalString("SUPPLIER_DEPOSIT_TULIP_MASK");
		} else {
			$mask = getDolGlobalString("SUPPLIER_INVOICE_TULIP_MASK");
		}
		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		// Supplier invoices take invoice date instead of creation date for the mask
		$numFinal = get_next_value($db, $mask, 'facture_fourn', 'ref', '', $objsoc, $object->date);

		return  $numFinal;
	}

	/**
	 * Return next free value
	 *
	 *  @param  Societe     		$objsoc         Object third party
	 *  @param  FactureFournisseur  $objforref      Object for number to search
	 *  @param  string      		$mode           'next' for next value or 'last' for last value
	 *  @return string                      		Next free value
	 *  @deprecated see getNextValue
	 */
	public function getNumRef($objsoc, $objforref, $mode = 'next')
	{
		return $this->getNextValue($objsoc, $objforref, $mode);
	}
}
