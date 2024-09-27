<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2019-2024  Frédéric France             <frederic.france@free.fr>
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
 * \file       htdocs/knowledgemanagement/core/modules/knowledgemanagement/mod_knowledgerecord_advanced.php
 * \ingroup    knowledgemanagement
 * \brief      File containing class for advanced numbering model of KnowledgeRecord
 */

dol_include_once('/knowledgemanagement/core/modules/knowledgemanagement/modules_knowledgerecord.php');


/**
 *	Class to manage customer Bom numbering rules advanced
 */
class mod_knowledgerecord_advanced extends ModeleNumRefKnowledgeRecord
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
	 *	Returns the default description of the numbering template
	 *
	 *  @param	Translate	$langs Object langs
	 *	@return	string	Descriptive text
	 */
	public function info($langs)
	{
		global $db;

		$langs->load("bills");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconst" value="KNOWLEDGEMANAGEMENT_KNOWLEDGERECORD_ADVANCED_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("KnowledgeRecord"), $langs->transnoentities("KnowledgeRecord"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("KnowledgeRecord"), $langs->transnoentities("KnowledgeRecord"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskKnowledgeRecord" value="'.getDolGlobalString('KNOWLEDGEMANAGEMENT_KNOWLEDGERECORD_ADVANCED_MASK').'">', $tooltip, 1, 1).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit" name="Button"value="'.$langs->trans("Modify").'"></td>';

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
		global $db, $langs;

		$object = new KnowledgeRecord($db);
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
	 *  @param  KnowledgeRecord	$object		Object we need next value for
	 *  @return string|int<-1,0>			Next value if OK, <=0 if KO
	 */
	public function getNextValue($object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString('KNOWLEDGEMANAGEMENT_KNOWLEDGERECORD_ADVANCED_MASK');

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = $object->date_creation;

		$numFinal = get_next_value($db, $mask, 'knowledgemanagement_knowledgerecord', 'ref', '', '', $date);

		return  $numFinal;
	}
}
