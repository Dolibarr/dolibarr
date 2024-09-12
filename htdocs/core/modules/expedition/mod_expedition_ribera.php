<?php
/* Copyright (C) 2011       Juanjo Menent	        <jmenent@2byte.es>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/core/modules/expedition/mod_expedition_ribera.php
 *  \ingroup    expedition
 *  \brief      File of class to manage expedition numbering rules Ribera
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';

/**
 *	Class to manage expedition numbering rules Ribera
 */
class mod_expedition_ribera extends ModelNumRefExpedition
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Ribera';

	/**
	 * @var string model name
	 */
	public $name = 'Ribera';

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
		$texte .= '<input type="hidden" name="maskconstexpedition" value="EXPEDITION_RIBERA_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Shipment"), $langs->transnoentities("Shipment"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Shipment"), $langs->transnoentities("Shipment"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskexpedition" value="'.getDolGlobalString('EXPEDITION_RIBERA_MASK').'">', $tooltip, 1, 1).'</td>';
		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans("Modify").'"></td>';
		$texte .= '</tr>';
		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *	Return numbering example
	 *
	 *	@return     string|int<0,0>      Example
	 */
	public function getExample()
	{
		global $db, $langs;

		require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		$shipment = new Expedition($db);
		$shipment->initAsSpecimen();
		$thirdparty = new Societe($db);
		$thirdparty->initAsSpecimen();

		$numExample = $this->getNextValue($thirdparty, $shipment);

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}

		return $numExample;
	}

	/**
	 *	Return next value
	 *
	 *	@param	Societe			$objsoc     Third party object
	 *	@param	Expedition		$shipment	Shipment object
	 *	@return string|int<-1,0> 			Value if OK, 0 or -1 if KO
	 */
	public function getNextValue($objsoc, $shipment)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$mask = getDolGlobalString('EXPEDITION_RIBERA_MASK');

		if (empty($mask)) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = $shipment->date_shipping;

		$numFinal = get_next_value($db, $mask, 'expedition', 'ref', '', $objsoc, $date);

		return  $numFinal;
	}
}
