<?php
/* Copyright (C) 2011       Juanjo Menent	        <jmenent@2byte.es>
 * Copyright (C) 2019-2024	Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/core/modules/contract/mod_contract_magre.php
 *  \ingroup    contract
 *  \brief      File of class to manage contract numbering rules Magre
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';

/**
 *	Class to manage contract numbering rules Magre
 */
class mod_contract_magre extends ModelNumRefContracts
{

	// variables inherited from ModelNumRefContracts class
	public $name = 'Magre';
	public $version = 'dolibarr';
	public $error = '';

	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$this->code_auto = 1;
	}

	/**
	 *	Return default description of numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $langs, $db;

		$langs->load("bills");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconstcontract" value="CONTRACT_MAGRE_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Contract"), $langs->transnoentities("Contract"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Contract"), $langs->transnoentities("Contract"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskcontract" value="'.getDolGlobalString("CONTRACT_MAGRE_MASK").'">', $tooltip, 1, 1).'</td>';
		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button"value="'.$langs->trans("Modify").'"></td>';
		$texte .= '</tr>';
		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *	Return numbering example
	 *
	 *	@return     string      Example
	 */
	public function getExample()
	{
		global $langs, $mysoc;

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
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc     third party object
	 *	@param	Contrat		$contract	contract object
	 *	@return string|0      			Next value if OK, 0 if KO
	 */
	public function getNextValue($objsoc, $contract)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$mask = getDolGlobalString("CONTRACT_MAGRE_MASK");

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$numFinal = get_next_value($db, $mask, 'contrat', 'ref', '', $objsoc, $contract->date_contrat);

		return  $numFinal;
	}
}
