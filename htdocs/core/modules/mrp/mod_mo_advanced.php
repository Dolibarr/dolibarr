<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2019-2024  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2020       Josep Lluís Amador          <joseplluis@lliuretic.cat>
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
 * \file       htdocs/core/modules/mrp/mod_mo_advanced.php
 * \ingroup    mrp
 * \brief      File containing class for numbering model of MOs advanced
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/mrp/modules_mo.php';


/**
 *	Class to manage MO numbering rules advanced
 */
class mod_mo_advanced extends ModeleNumRefMos
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
	 * @var string name
	 */
	public $name = 'advanced';


	/**
	 *  Returns the description of the numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $conf, $langs, $db;

		$langs->load("bills");

		$form = new Form($db);

		$text = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$text .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$text .= '<input type="hidden" name="token" value="'.newToken().'">';
		$text .= '<input type="hidden" name="action" value="updateMask">';
		$text .= '<input type="hidden" name="maskconstMo" value="MRP_MO_ADVANCED_MASK">';
		$text .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Mo"), $langs->transnoentities("Mo"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Mo"), $langs->transnoentities("Mo"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		//$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$text .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$text .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskMo" value="'.getDolGlobalString('MRP_MO_ADVANCED_MASK').'">', $tooltip, 1, 1).'</td>';

		$text .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button"value="'.$langs->trans("Modify").'"></td>';

		$text .= '</tr>';

		$text .= '</table>';
		$text .= '</form>';

		return $text;
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string|int<0,0>      Example
	 */
	public function getExample()
	{
		global $db, $langs;

		require_once DOL_DOCUMENT_ROOT . '/mrp/class/mo.class.php';
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

		$mo = new Mo($db);
		$mo->initAsSpecimen();
		$product = new Product($db);
		$product->initAsSpecimen();


		$numExample = $this->getNextValue($product, $mo);

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}

		return $numExample;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Product			$objprod    Object product
	 *  @param  Mo				$object		Object we need next value for
	 *  @return string|int<-1,0>			Value if OK, <=0 if KO
	 */
	public function getNextValue($objprod, $object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString('MRP_MO_ADVANCED_MASK');

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = ($object->date_mo ? $object->date_mo : $object->date);

		$numFinal = get_next_value($db, $mask, 'mrp_mo', 'ref', '', null, $date);

		return  $numFinal;
	}
}
