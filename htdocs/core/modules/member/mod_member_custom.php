<?php
/* Copyright (C) 2025	Christophe Battarel	<christophe@altairis.fr>
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
 * \file       htdocs/core/modules/member/mod_member_custom.php
 * \ingroup    member
 * \brief      Fichier contenant la class du modele de numerotation d'ahérent Custom
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/member/modules_member.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';


/**
 *	Class to manage member report numbering rules Custom
 */
class mod_member_custom extends ModeleNumRefMembers
{
	/**
	 * @var string model name
	 */
	public $name = 'Custom';

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var int		Position
	 */
	public $position = 50;

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
		$texte .= '<input type="hidden" name="maskconst" value="MEMBER_CUSTOM_MASK">';
		$texte .= '<input type="hidden" name="page_y" value="">';

		$texte .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Member"), $langs->transnoentities("Member"));
		$tooltip .= $langs->trans("GenericMaskCodes1");
		$tooltip .= '<br>';
		//$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Member"), $langs->transnoentities("Member"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$mask = getDolGlobalString('MEMBER_CUSTOM_MASK');
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskvalue" value="'.$mask.'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans("Save").'"></td>';

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

		$member = new Adherent($db);
		$member->initAsSpecimen();
		$member->fk_user_author = $user->id;

		$numExample = $this->getNextValue(null, $member);

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 *  Return next free value
	 *
	 *  @param  ?Societe	$objsoc		Object third party
	 *  @param  ?Adherent	$object		Object we need next value for
	 *  @return string|int<-1,0>   			Next value if OK, -1 or 0 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString('MEMBER_CUSTOM_MASK');

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = dol_now();

		$fuser = null;
		if ($object->fk_user_author > 0) {
			$fuser = new User($db);
			$fuser->fetch($object->fk_user_author);
		}

		// Get entities
		$entity = getEntity('membernumber', 1, $object);

		$numFinal = get_next_value($db, $mask, 'adherent', 'ref', '', null, $date, 'next', true, $fuser, $entity);

		return $numFinal;
	}
}
