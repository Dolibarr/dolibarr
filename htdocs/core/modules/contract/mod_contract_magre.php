<?php
<<<<<<< HEAD
/* Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
=======
/* Copyright (C) 2011       Juanjo Menent	        <jmenent@2byte.es>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *  \file       htdocs/core/modules/contract/mod_contract_magre.php
 *  \ingroup    contract
 *  \brief      File of class to manage contract numbering rules Magre
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/contract/modules_contract.php';

/**
 *	Class to manage contract numbering rules Magre
 */
class mod_contract_magre extends ModelNumRefContracts
{
<<<<<<< HEAD
	var $version='dolibarr';
	var $error = '';
	var $nom = 'Magre';
	var $code_auto=1;
=======
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string nom
	 * @deprecated
	 * @see $name
	 */
	public $nom='Magre';

	/**
	 * @var string name
	 */
	public $name='Magre';

	public $code_auto=1;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *	Return default description of numbering model
	 *
	 *	@return     string      text description
	 */
<<<<<<< HEAD
	function info()
    {
    	global $conf,$langs;

		$langs->load("bills");

		$form = new Form($this->db);
=======
    public function info()
    {
    	global $conf,$langs, $db;

		$langs->load("bills");

		$form = new Form($db);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconstcontract" value="CONTRACT_MAGRE_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

<<<<<<< HEAD
		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Contract"),$langs->transnoentities("Contract"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Contract"),$langs->transnoentities("Contract"));
		$tooltip.=$langs->trans("GenericMaskCodes5");

		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskcontract" value="'.$conf->global->CONTRACT_MAGRE_MASK.'">',$tooltip,1,1).'</td>';
		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
=======
		$tooltip=$langs->trans("GenericMaskCodes", $langs->transnoentities("Contract"), $langs->transnoentities("Contract"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a", $langs->transnoentities("Contract"), $langs->transnoentities("Contract"));
		$tooltip.=$langs->trans("GenericMaskCodes5");

		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskcontract" value="'.$conf->global->CONTRACT_MAGRE_MASK.'">', $tooltip, 1, 1).'</td>';
		$texte.= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
    function getExample()
=======
    public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
     	global $conf,$langs,$mysoc;

    	$old_code_client=$mysoc->code_client;
    	$mysoc->code_client='CCCCCCCCCC';
<<<<<<< HEAD
     	$numExample = $this->getNextValue($mysoc,'');
=======
     	$numExample = $this->getNextValue($mysoc, '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$mysoc->code_client=$old_code_client;

		if (! $numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
    }

	/**
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc     third party object
	 *	@param	Object		$contract	contract object
	 *	@return string      			Value if OK, 0 if KO
	 */
<<<<<<< HEAD
    function getNextValue($objsoc,$contract)
=======
    public function getNextValue($objsoc, $contract)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

		$mask=$conf->global->CONTRACT_MAGRE_MASK;

		if (! $mask)
		{
			$this->error='NotConfigured';
			return 0;
		}

<<<<<<< HEAD
		$numFinal=get_next_value($db,$mask,'contrat','ref','',$objsoc,$contract->date_contrat);
=======
		$numFinal=get_next_value($db, $mask, 'contrat', 'ref', '', $objsoc, $contract->date_contrat);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		return  $numFinal;
	}

<<<<<<< HEAD
	/**
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc     third party object
	 *	@param	Object		$objforref	contract object
	 *	@return string      			Value if OK, 0 if KO
	 */
    function contract_get_num($objsoc,$objforref)
    {
        return $this->getNextValue($objsoc,$objforref);
    }

}

=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return next value
	 *
	 *  @param	Societe		$objsoc     third party object
	 *  @param	Object		$objforref	contract object
	 *  @return string      			Value if OK, 0 if KO
     */
    public function contract_get_num($objsoc, $objforref)
    {
        // phpcs:enable
        return $this->getNextValue($objsoc, $objforref);
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
