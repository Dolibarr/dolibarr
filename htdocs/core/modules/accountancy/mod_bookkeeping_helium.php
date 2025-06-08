<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2019-2024  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025	    Jean-Rémi Taponier   		<jean-remi@netlogic.fr>
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
 * \file       htdocs/core/modules/accountancy/mod_bookkeeping_helium.php
 * \ingroup    accountancy
 *  \brief     File of class to manage Bookkeeping numbering rules Helium
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/accountancy/modules_accountancy.php';


/**
 *	Class to manage Bookkeeping numbering rules Helium, configurable numbering model
 */
class mod_bookkeeping_helium extends ModeleNumRefBookkeeping
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
	public $name = 'Helium';

	/**
	 * @var int	position
	 */
	public $position = 60;


	/**
	 *  Returns the description of the numbering model
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
		$texte .= '<input type="hidden" name="maskconstbookkeeping" value="BOOKKEEPING_HELIUM_MASK">';
		$texte .= '<input type="hidden" name="page_y" value="">';

		$texte .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Bookkeeping"), $langs->transnoentities("Bookkeeping"));
		$tooltip .= $langs->trans("GenericMaskCodes1");
		//$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Bookkeeping"), $langs->transnoentities("Bookkeeping"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		//$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskbookkeeping" value="'.getDolGlobalString("BOOKKEEPING_HELIUM_MASK").'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans("Save").'"></td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string|int<0,0>      Example
	 */
	public function getExample()
	{
		global $db, $langs;

		require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';

		$bookkeeping = new BookKeeping($db);
		$bookkeeping->initAsSpecimen();

		$numExample = $this->getNextValue($bookkeeping);

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}

		return $numExample;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  BookKeeping		$object		Object we need next value for
	 *  @return string|int<-1,0>		Value if OK, -1 if KO
	 */
	public function getNextValue(BookKeeping $object)
	{
		global $conf, $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString("BOOKKEEPING_HELIUM_MASK");

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		// Get entities
		//$entity = getEntity('accountingbookkeeping', 1, $object);
		$entity = $conf->entity;	// In accountancy, we can't share entities

		$numFinal = get_next_value($db, $mask, 'accounting_bookkeeping', 'ref', '', '', $object->doc_date, 'next', false, null, (string) $entity, $object);

		return $numFinal;
	}
}
