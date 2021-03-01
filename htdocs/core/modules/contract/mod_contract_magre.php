<?php
/* Copyright (C) 2011       Juanjo Menent	        <jmenent@2byte.es>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string nom
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Magre';

	/**
	 * @var string name
	 */
	public $name = 'Magre';

	/**
	 * @var int Automatic numbering
	 */
	public $code_auto = 1;

	/**
	 *	Return default description of numbering model
	 *
	 *	@return     string      text description
	 */
	public function info()
	{
		global $conf, $langs, $db;

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

		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskcontract" value="'.$conf->global->CONTRACT_MAGRE_MASK.'">', $tooltip, 1, 1).'</td>';
		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
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
	 	global $conf, $langs, $mysoc;

		$old_code_client = $mysoc->code_client;
		$mysoc->code_client = 'CCCCCCCCCC';
	 	$numExample = $this->getNextValue($mysoc, '');
		$mysoc->code_client = $old_code_client;

		if (!$numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc     third party object
	 *	@param	Object		$contract	contract object
	 *	@return string      			Value if OK, 0 if KO
	 */
	public function getNextValue($objsoc, $contract)
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$mask = $conf->global->CONTRACT_MAGRE_MASK;

		if (!$mask)
		{
			$this->error = 'NotConfigured';
			return 0;
		}

		$numFinal = get_next_value($db, $mask, 'contrat', 'ref', '', $objsoc, $contract->date_contrat);

		return  $numFinal;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return next value
	 *
	 *  @param	Societe		$objsoc     third party object
	 *  @param	Object		$objforref	contract object
	 *  @return string      			Value if OK, 0 if KO
	 */
	public function contract_get_num($objsoc, $objforref)
	{
		// phpcs:enable
		return $this->getNextValue($objsoc, $objforref);
	}
}
