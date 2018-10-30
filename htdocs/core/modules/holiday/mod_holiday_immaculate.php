<?php
/* Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2018      Charlene Benke		<charlie@patas-monkey.com>
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
 *  \file       htdocs/core/modules/holiday/mod_holiday_immaculate.php
 *  \ingroup    contract
 *  \brief      File of class to manage contract numbering rules Magre
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/holiday/modules_holiday.php';

/**
 *	Class to manage contract numbering rules Magre
 */
class mod_holiday_immaculate extends ModelNumRefHolidays
{
	/**
     * Dolibarr version of the loaded document
     * @public string
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
	public $nom='Immaculate';

	/**
	 * @var string model name
	 */
	public $name='Immaculate';

	public $code_auto=1;

	/**
	 *	Return default description of numbering model
	 *
	 *	@return     string      text description
	 */
	function info()
    {
    	global $conf, $langs;

		$langs->load("bills");

		$form = new Form($this->db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconstcontract" value="HOLIDAY_IMMACULATE_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Holiday"),$langs->transnoentities("Holiday"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Holiday"),$langs->transnoentities("Holiday"));
		$tooltip.=$langs->trans("GenericMaskCodes5");

		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskholiday" value="'.$conf->global->HOLIDAY_IMMACULATE_MASK.'">',$tooltip,1,1).'</td>';
		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
		$texte.= '</tr>';
		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
    }

	/**
	 *	Return numbering example
	 *
	 *	@return     string      Example
	 */
    function getExample()
    {
     	global $conf,$langs,$user;

    	$old_login=$user->login;
    	$user->login='UUUUUUU';
     	$numExample = $this->getNextValue($user, '');
		$user->login=$old_login;

		if (! $numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
    }

	/**
	 *	Return next value
	 *
	 *	@param	Societe		$user     	user object
	 *	@param	Object		$holiday	holiday object
	 *	@return string      			Value if OK, 0 if KO
	 */
    function getNextValue($user, $holiday)
    {
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

		$mask=$conf->global->HOLIDAY_IMMACULATE_MASK;

		if (! $mask)
		{
			$this->error='NotConfigured';
			return 0;
		}

		$numFinal=get_next_value($db,$mask,'holiday','ref','', $user, $holiday->date_create);

		return  $numFinal;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return next value
	 *
	 *  @param  User		$fuser     	User object
	 *  @param  Object		$objforref	Holiday object
	 *  @return string      			Value if OK, 0 if KO
	 */
    function holiday_get_num($fuser, $objforref)
    {
        // phpcs:enable
        return $this->getNextValue($fuser, $objforref);
    }
}
