<?php
/* Copyright (C) 2018		Quentin Vial-Gouteyron	<quentin.vial-gouteyron@atm-consulting.fr>
 * Copyright (C) 2019		Frédéric France			<frederic.france@netlogic.fr>
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
 *  \file       htdocs/core/modules/reception/mod_reception_moonstone.php
 *  \ingroup    reception
 *  \brief      File of class to manage reception numbering rules Moonstone
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/reception/modules_reception.php';

/**
 *	Class to manage reception numbering rules Moonstone
 */
class mod_reception_moonstone extends ModelNumRefReception
{
	public $version = 'dolibarr';
	public $error = '';
	public $nom = 'Moonstone';

	/**
	 *  Return default description of numbering model
	 *
	 *  @return     string      text description
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
		$texte .= '<input type="hidden" name="maskconstreception" value="RECEPTION_MOONSTONE_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Reception"), $langs->transnoentities("Reception"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Reception"), $langs->transnoentities("Reception"));
		$tooltip .= $langs->trans("GenericMaskCodes5");

		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskreception" value="'.$conf->global->RECEPTION_MOONSTONE_MASK.'">', $tooltip, 1, 1).'</td>';
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
		$old_code_type = $mysoc->typent_code;
		$mysoc->code_client = 'CCCCCCCCCC';
		$mysoc->typent_code = 'TTTTTTTTTT';
	 	$numExample = $this->getNextValue($mysoc, '');
		$mysoc->code_client = $old_code_client;
		$mysoc->typent_code = $old_code_type;

		if (!$numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc     Third party object
	 *	@param	Object		$reception	Reception object
	 *	@return string      			Value if OK, 0 if KO
	 */
	public function getNextValue($objsoc, $reception)
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$mask = $conf->global->RECEPTION_MOONSTONE_MASK;

		if (!$mask)
		{
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = $reception->date_reception;

		$numFinal = get_next_value($db, $mask, 'reception', 'ref', '', $objsoc, $date);

		return  $numFinal;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return next free value
	 *
	 *	@param	Societe		$objsoc     Third party object
	 *	@param	Object		$objforref	Reception object
	 *	@return string      			Next free value
	 */
	public function reception_get_num($objsoc, $objforref)
	{
		// phpcs:enable
		return $this->getNextValue($objsoc, $objforref);
	}
}
