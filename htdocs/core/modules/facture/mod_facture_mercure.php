<?php
/* Copyright (C) 2003-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2013		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2022		Anthony Berton				<anthony.berton@bb2a.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/facture/mod_facture_mercure.php
 *	\ingroup    invoice
 *	\brief      File containing class for numbering module Mercure
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';


/**
 *	Class of numbering module Mercure for invoices
 */
class mod_facture_mercure extends ModeleNumRefFactures
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error message
	 */
	public $error = '';


	/**
	 *  Returns the description of the numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $db, $langs;

		$langs->load("bills");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconstinvoice" value="FACTURE_MERCURE_MASK_INVOICE">';
		$texte .= '<input type="hidden" name="maskconstreplacement" value="FACTURE_MERCURE_MASK_REPLACEMENT">';
		$texte .= '<input type="hidden" name="maskconstcredit" value="FACTURE_MERCURE_MASK_CREDIT">';
		$texte .= '<input type="hidden" name="maskconstdeposit" value="FACTURE_MERCURE_MASK_DEPOSIT">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Invoice"), $langs->transnoentities("Invoice"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Invoice"), $langs->transnoentities("Invoice"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Setting the prefix
		$texte .= '<tr><td><span class="opacitymedium">'.$langs->trans("Mask").' ('.$langs->trans("InvoiceStandard").'):</span></td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskinvoice" value="'.getDolGlobalString("FACTURE_MERCURE_MASK_INVOICE").'">', $tooltip, 1, 1, '', 0, 3, 'tooltipstandardmercure').'</td>';

		$texte .= '<td class="left" rowspan="3">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button"value="'.$langs->trans("Modify").'"></td>';

		$texte .= '</tr>';

		// Prefix setting of credit note
		$texte .= '<tr><td><span class="opacitymedium">'.$langs->trans("Mask").' ('.$langs->trans("InvoiceAvoir").'):</span></td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskcredit" value="'.getDolGlobalString("FACTURE_MERCURE_MASK_CREDIT").'">', $tooltip, 1, 1, '', 0, 3, 'tooltipcreditnotemercure').'</td>';
		$texte .= '</tr>';

		// Prefix setting of replacement invoices
		if (!getDolGlobalString('INVOICE_DISABLE_REPLACEMENT')) {
			$texte .= '<tr><td><span class="opacitymedium">'.$langs->trans("Mask").' ('.$langs->trans("InvoiceReplacement").'):</span></td>';
			$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskreplacement" value="'.getDolGlobalString("FACTURE_MERCURE_MASK_REPLACEMENT").'">', $tooltip, 1, 1, '', 0, 3, 'tooltipreplacementmercure').'</td>';
			$texte .= '</tr>';
		}

		// Prefix setting of deposit
		$texte .= '<tr><td><span class="opacitymedium">'.$langs->trans("Mask").' ('.$langs->trans("InvoiceDeposit").'):</span></td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskdeposit" value="'.getDolGlobalString("FACTURE_MERCURE_MASK_DEPOSIT").'">', $tooltip, 1, 1, '', 0, 3, 'tooltipdownpaymentmercure').'</td>';
		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Return an example of number value
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $mysoc;

		$old_code_client = $mysoc->code_client;
		$old_code_type = $mysoc->typent_code;
		$mysoc->code_client = 'CCCCCCCCCC';
		$mysoc->typent_code = 'TTTTTTTTTT';
		$numExample = $this->getNextValue($mysoc, null);
		$mysoc->code_client = $old_code_client;
		$mysoc->typent_code = $old_code_type;

		if (!$numExample) {
			$numExample = 'NotConfigured';
		}
		return $numExample;
	}

	/**
	 * Return next value not used or last value used
	 *
	 * @param	Societe		$objsoc		Object third party
	 * @param   ?Facture	$invoice	Object invoice
	 * @param   string		$mode		'next' for next value or 'last' for last value
	 * @return  string|int<-1,0>		Value if OK, <=0 if KO
	 */
	public function getNextValue($objsoc, $invoice, $mode = 'next')
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// Get Mask value
		$mask = '';
		if (is_object($invoice) && $invoice->type == 1) {
			$mask = getDolGlobalString('FACTURE_MERCURE_MASK_REPLACEMENT', getDolGlobalString('FACTURE_MERCURE_MASK_INVOICE'));
		} elseif (is_object($invoice) && $invoice->type == 2) {
			$mask = getDolGlobalString('FACTURE_MERCURE_MASK_CREDIT');
		} elseif (is_object($invoice) && $invoice->type == 3) {
			$mask = getDolGlobalString('FACTURE_MERCURE_MASK_DEPOSIT');
		} else {
			$mask = getDolGlobalString('FACTURE_MERCURE_MASK_INVOICE');
		}
		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$where = '';
		//if ($facture->type == 2) $where.= " AND type = 2";
		//else $where.=" AND type != 2";

		// Get entities
		$entity = getEntity('invoicenumber', 1, $invoice);
		$numFinal = get_next_value($db, $mask, 'facture', 'ref', $where, $objsoc, (empty($invoice) ? dol_now() : $invoice->date), $mode, false, null, $entity);
		if (!preg_match('/([0-9])+/', $numFinal)) {
			$this->error = $numFinal;
		}

		return $numFinal;
	}


	/**
	 * Return next free value
	 *
	 * @param	Societe			$objsoc     	Object third party
	 * @param	Facture			$objforref		Object for number to search
	 * @param   string			$mode       	'next' for next value or 'last' for last value
	 * @return  string|int      				Next free value, 0 if KO
	 * @deprecated see getNextValue
	 */
	public function getNumRef($objsoc, $objforref, $mode = 'next')
	{
		return $this->getNextValue($objsoc, $objforref, $mode);
	}
}
