<?php
/* Copyright (C) 2003-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008i		Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2019-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) ---Replace with your own copyright and developer email---
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
 * \file       htdocs/core/modules/mymodule/mod_myobject_advanced.php
 * \ingroup    mymodule
 * \brief      File containing class for advanced numbering model of MyObject
 */

dol_include_once('/mymodule/core/modules/mymodule/modules_myobject.php');


/**
 *	Class to manage the Advanced numbering rule for MyObject
 */
class mod_myobject_advanced extends ModeleNumRefMyObject
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z''development'|'experimental'|'dolibarr'
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
	 *	Returns the description of the numbering model
	 *
	 *	@param	Translate	$langs	Translate Object
	 *	@return	string				Text with description
	 */
	public function info($langs)
	{
		global $db;

		$langs->load("bills");

		$form = new Form($db);

		$text = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$text .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$text .= '<input type="hidden" name="token" value="'.newToken().'">';
		$text .= '<input type="hidden" name="action" value="updateMask">';
		$text .= '<input type="hidden" name="maskconst" value="MYMODULE_MYOBJECT_ADVANCED_MASK">';
		$text .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("MyObject"), $langs->transnoentities("MyObject"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("MyObject"), $langs->transnoentities("MyObject"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// prefix configuration
		$text .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$text .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskvalue" value="'.getDolGlobalString('MYMODULE_MYOBJECT_ADVANCED_MASK').'">', $tooltip, 1, 1).'</td>';
		$text .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'" name="Button"></td>';
		$text .= '</tr>';

		$text .= '</table>';
		$text .= '</form>';

		return $text;
	}

	/**
	 *	Return an example of numbering
	 *
	 *	@return	string		Example
	 */
	public function getExample()
	{
		global $db, $langs;

		$object = new MyObject($db);
		$object->initAsSpecimen();

		/*$old_code_client = $mysoc->code_client;
		$old_code_type = $mysoc->typent_code;
		$mysoc->code_client = 'CCCCCCCCCC';
		$mysoc->typent_code = 'TTTTTTTTTT';*/

		$numExample = $this->getNextValue($object);

		/*$mysoc->code_client = $old_code_client;
		$mysoc->typent_code = $old_code_type;*/

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  MyObject		$object		Object we need next value for
	 *  @return string|int<-1,0>			Next value if OK, <=0 if KO
	 */
	public function getNextValue($object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString('MYMODULE_MYOBJECT_ADVANCED_MASK');

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = $object->date_creation;

		$numFinal = get_next_value($db, $mask, 'mymodule_myobject', 'ref', '', '', $date);

		return  $numFinal;
	}
}
