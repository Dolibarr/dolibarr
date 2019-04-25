<?php
/* Copyright (C) 2017 Maxime Kohlhaas <support@atm-consulting.fr>
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
 * \file       htdocs/core/modules/expensereport/mod_expensereport_sand.php
 * \ingroup    expensereport
 * \brief      Fichier contenant la classe du modele de numerotation de reference de note de frais Sand
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/expensereport/modules_expensereport.php';


/**
 *	Class to manage expense report numbering rules Sand
 */
class mod_expensereport_sand extends ModeleNumRefExpenseReport
{
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom='Sand';

	/**
	 * @var string model name
	 */
	public $name='Sand';


    /**
     *  Renvoi la description du modele de numerotation
     *
     *  @return     string      Texte descripif
     */
    public function info()
    {
    	global $db, $conf, $langs;

		$langs->load("bills");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconst" value="EXPENSEREPORT_SAND_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		$tooltip=$langs->trans("GenericMaskCodes", $langs->transnoentities("ExpenseReport"), $langs->transnoentities("ExpenseReport"));
		//$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a", $langs->transnoentities("ExpenseReport"), $langs->transnoentities("ExpenseReport"));
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Parametrage du prefix
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskvalue" value="'.$conf->global->EXPENSEREPORT_SAND_MASK.'">', $tooltip, 1, 1).'</td>';

		$texte.= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
    }

    /**
     *  Renvoi un exemple de numerotation
     *
     *  @return     string      Example
     */
    public function getExample()
    {
     	global $db, $conf,$langs,$user;

     	$exp=new ExpenseReport($db);
     	$exp->initAsSpecimen();
     	$exp->fk_user_author = $user->id;

     	$numExample = $this->getNextValue($exp);

		if (! $numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
    }

    /**
     *  Return next free value
     *
     *  @param  Object      $object     Object we need next value for
     *  @return string                  Value if KO, <0 if KO
     */
    public function getNextValue($object)
    {
        global $db,$conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask=$conf->global->EXPENSEREPORT_SAND_MASK;

		if (! $mask)
		{
			$this->error='NotConfigured';
			return 0;
		}

		$date=$object->date_valid;		// $object->date does not exists
		if (empty($date))
		{
			$this->error = 'Date valid not defined';
			return 0;
		}

		$fuser = null;
		if ($object->fk_user_author > 0)
		{
			$fuser=new User($db);
			$fuser->fetch($object->fk_user_author);
		}

		$numFinal=get_next_value($db, $mask, 'expensereport', 'ref', '', null, $date, 'next', true, $fuser);

		return $numFinal;
    }
}
