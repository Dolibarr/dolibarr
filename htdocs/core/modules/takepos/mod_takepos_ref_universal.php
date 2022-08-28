<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin                <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2013      Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2020      Open-DSI	                    <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/takepos/mod_takepos_ref_universal.php
 *	\ingroup    takepos
 *	\brief      File with Universal ref numbering module for takepos
 */
dol_include_once('/core/modules/takepos/modules_takepos.php');

/**
 *	Class to manage ref numbering of takepos cards with rule universal.
 */
class mod_takepos_ref_universal extends ModeleNumRefTakepos
{
	/**
	 * Dolibarr version of the loaded document 'development', 'experimental', 'dolibarr'
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * Name
	 * @var string
	 */
	public $nom = 'Universal';

	/**
	 *  Renvoi la description du modele de numerotation
	 *
	 * @return     string      Texte descripif
	 */
	public function info()
	{
		global $db, $langs;

		$langs->load('cashdesk@cashdesk');

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconst" value="TAKEPOS_REF_UNIVERSAL_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans('GenericMaskCodes', $langs->transnoentities('CashDesk'), $langs->transnoentities('CashDesk'));
		$tooltip .= $langs->trans('GenericMaskCodes2');
		$tooltip .= $langs->trans('GenericMaskCodes3');
		$tooltip .= $langs->trans('GenericMaskCodes4a', $langs->transnoentities('CashDesk'), $langs->transnoentities('CashDesk'));
		$tooltip .= $langs->trans('GenericMaskCodes5');
		$tooltip .= $langs->trans('CashDeskGenericMaskCodes6');

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskvalue" value="'.getDolGlobalString('TAKEPOS_REF_UNIVERSAL_MASK').'">', $tooltip, 1, 1).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit" name="Button"value="'.$langs->trans("Modify").'"></td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 * Renvoi un exemple de numerotation
	 *
	 * @return     string      Example
	 */
	public function getExample()
	{
		global $conf, $langs, $mysoc;

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
	 * Return next free value
	 *
	 * @param   Societe     $objsoc     Object thirdparty
	 * @param   Facture		$invoice	Object invoice
	 * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return  string      Value if KO, <0 if KO
	 */
	public function getNextValue($objsoc = null, $invoice = null, $mode = 'next')
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// On defini critere recherche compteur
		$mask = getDolGlobalString('TAKEPOS_REF_UNIVERSAL_MASK');

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		// Get entities
		$entity = getEntity('invoicenumber', 1, $invoice);

		$date = (empty($invoice->date) ? dol_now() : $invoice->date);
		$pos_source = is_object($invoice) && $invoice->pos_source > 0 ? $invoice->pos_source : 0;
		$mask = str_replace('{TN}', $pos_source, $mask);
		$numFinal = get_next_value($db, $mask, 'facture', 'ref', '', $objsoc, $date, $mode, false, null, $entity);

		return $numFinal;
	}


	/**
	 * Return next free value
	 *
	 * @param   Societe     $objsoc         Object third party
	 * @param   Object      $objforref      Object for number to search
	 * @return  string      Next free value
	 */
	public function getNumRef($objsoc, $objforref)
	{
		return $this->getNextValue($objsoc, $objforref);
	}
}
