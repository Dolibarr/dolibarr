<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur         <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2007 Regis Houssin               <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2007 Regis Houssin               <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 * \file       htdocs/core/modules/propale/mod_propale_saphir.php
 * \ingroup    propale
 * \brief      File that contains the numbering module rules Saphir
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/propale/modules_propale.php';


/**
 * Class of file that contains the numbering module rules Saphir
 */
class mod_propale_saphir extends ModeleNumRefPropales
{
<<<<<<< HEAD
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Saphir';
=======
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'

	/**
     * @var string Error code (or message)
     */
    public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see name
	 */
	public $nom='Saphir';

	/**
	 * @var string model name
	 */
	public $name='Saphir';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


    /**
     *  Return description of module
     *
     *  @return     string      Texte descripif
     */
<<<<<<< HEAD
	function info()
    {
    	global $conf,$langs;
=======
	public function info()
    {
    	global $conf, $langs;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		$langs->load("bills");

		$form = new Form($this->db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconstpropal" value="PROPALE_SAPHIR_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

<<<<<<< HEAD
		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Proposal"),$langs->transnoentities("Proposal"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Proposal"),$langs->transnoentities("Proposal"));
=======
		$tooltip=$langs->trans("GenericMaskCodes", $langs->transnoentities("Proposal"), $langs->transnoentities("Proposal"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a", $langs->transnoentities("Proposal"), $langs->transnoentities("Proposal"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Parametrage du prefix
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
<<<<<<< HEAD
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskpropal" value="'.$conf->global->PROPALE_SAPHIR_MASK.'">',$tooltip,1,1).'</td>';

		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
=======
		$texte.= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskpropal" value="'.$conf->global->PROPALE_SAPHIR_MASK.'">', $tooltip, 1, 1).'</td>';

		$texte.= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
    function getExample()
=======
    public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
     	global $conf,$langs,$mysoc;

    	$old_code_client=$mysoc->code_client;
    	$old_code_type=$mysoc->typent_code;
    	$mysoc->code_client='CCCCCCCCCC';
    	$mysoc->typent_code='TTTTTTTTTT';
<<<<<<< HEAD
     	$numExample = $this->getNextValue($mysoc,'');
=======
     	$numExample = $this->getNextValue($mysoc, '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$mysoc->code_client=$old_code_client;
		$mysoc->typent_code=$old_code_type;

		if (! $numExample)
		{
			$numExample = 'NotConfigured';
		}
		return $numExample;
    }

	/**
	 *  Return next value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 * 	@param	Propal		$propal		Object commercial proposal
	 *  @return string      			Value if OK, 0 if KO
	 */
<<<<<<< HEAD
	function getNextValue($objsoc,$propal)
=======
	public function getNextValue($objsoc, $propal)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

<<<<<<< HEAD
		$constant = 'PROPALE_SAPHIR_MASK_'.$propal->entity;

		// On defini critere recherche compteur
		if (! empty($conf->global->$constant)) {
			$mask = $conf->global->$constant; // for multicompany proposal sharing
		} else {
			$mask = $conf->global->PROPALE_SAPHIR_MASK;
		}
=======
		// On defini critere recherche compteur
		$mask = $conf->global->PROPALE_SAPHIR_MASK;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		if (! $mask)
		{
			$this->error='NotConfigured';
			return 0;
		}

<<<<<<< HEAD
		// Use object entity ID
		$entity = ((isset($propal->entity) && is_numeric($propal->entity)) ? $propal->entity : $conf->entity);

		$date = $propal->date;

		$numFinal=get_next_value($db,$mask,'propal','ref','',$objsoc,$date,'next',false,null,$entity);

		return  $numFinal;
	}

=======
		// Get entities
		$entity = getEntity('proposalnumber', 1, $propal);

		$date = $propal->date;

		$numFinal=get_next_value($db, $mask, 'propal', 'ref', '', $objsoc, $date, 'next', false, null, $entity);

		return  $numFinal;
	}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
