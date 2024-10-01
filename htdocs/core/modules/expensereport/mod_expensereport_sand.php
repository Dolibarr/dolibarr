<?php
/* Copyright (C) 2017 Maxime Kohlhaas <support@atm-consulting.fr>
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
 * \file       htdocs/core/modules/expensereport/mod_expensereport_sand.php
 * \ingroup    expensereport
 * \brief      Fichier contenant la class du modele de numerotation de reference de note de frais Sand
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';


/**
 *	Class to manage expense report numbering rules Sand
 */
class mod_expensereport_sand extends ModeleNumRefExpenseReport
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
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Sand';

	/**
	 * @var string model name
	 */
	public $name = 'Sand';


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
		$texte .= '<input type="hidden" name="maskconst" value="EXPENSEREPORT_SAND_MASK">';
		$texte .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("ExpenseReport"), $langs->transnoentities("ExpenseReport"));
		//$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("ExpenseReport"), $langs->transnoentities("ExpenseReport"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$mask = getDolGlobalString('EXPENSEREPORT_SAND_MASK');
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskvalue" value="'.$mask.'">', $tooltip, 1, 1).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans("Modify").'"></td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $db, $langs, $user;

		$exp = new ExpenseReport($db);
		$exp->initAsSpecimen();
		$exp->fk_user_author = $user->id;

		$numExample = $this->getNextValue($exp);

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 *  Return next free value
	 *
	 *  @param  ExpenseReport	$object     Object we need next value for
	 *  @return string|int<-1,0>   			Next value if OK, -1 or 0 if KO
	 */
	public function getNextValue($object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString('EXPENSEREPORT_SAND_MASK');

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = $object->date_valid; // $object->date does not exists
		if (empty($date)) {
			$this->error = 'Date valid not defined';
			return 0;
		}

		$fuser = null;
		if ($object->fk_user_author > 0) {
			$fuser = new User($db);
			$fuser->fetch($object->fk_user_author);
		}

		$numFinal = get_next_value($db, $mask, 'expensereport', 'ref', '', null, $date, 'next', true, $fuser);

		return $numFinal;
	}
}
