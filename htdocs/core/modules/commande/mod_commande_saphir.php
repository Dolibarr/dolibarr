<?php
<<<<<<< HEAD
/* Copyright (C) 2003-2007 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin               <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
=======
/* Copyright (C) 2003-2007  Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2019       Frédéric France             <frederic.france@netlogic.fr>
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
 * \file       htdocs/core/modules/commande/mod_commande_saphir.php
 * \ingroup    commande
 * \brief      Fichier contenant la classe du modele de numerotation de reference de commande Saphir
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/commande/modules_commande.php';


/**
 *	Class to manage customer order numbering rules Saphir
 */
class mod_commande_saphir extends ModeleNumRefCommandes
{
<<<<<<< HEAD
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Saphir';


    /**
     *  Renvoi la description du modele de numerotation
     *
     *  @return     string      Texte descripif
     */
	function info()
    {
    	global $conf,$langs;

		$langs->load("bills");

		$form = new Form($this->db);
=======
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
	 * @var string name
	 */
	public $name='Saphir';


    /**
     *  Returns the description of the numbering model
     *
     *  @return     string      Texte descripif
     */
    public function info()
    {
    	global $conf, $langs, $db;

		$langs->load("bills");

		$form = new Form($db);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconstorder" value="COMMANDE_SAPHIR_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

<<<<<<< HEAD
		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Order"),$langs->transnoentities("Order"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Order"),$langs->transnoentities("Order"));
=======
		$tooltip=$langs->trans("GenericMaskCodes", $langs->transnoentities("Order"), $langs->transnoentities("Order"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a", $langs->transnoentities("Order"), $langs->transnoentities("Order"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Parametrage du prefix
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
<<<<<<< HEAD
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskorder" value="'.$conf->global->COMMANDE_SAPHIR_MASK.'">',$tooltip,1,1).'</td>';

		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
=======
		$texte.= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskorder" value="'.$conf->global->COMMANDE_SAPHIR_MASK.'">', $tooltip, 1, 1).'</td>';

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
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
    }

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe		$objsoc     Object thirdparty
	 *  @param  Object		$object		Object we need next value for
	 *  @return string      			Value if KO, <0 if KO
	 */
<<<<<<< HEAD
    function getNextValue($objsoc,$object)
=======
    public function getNextValue($objsoc, $object)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask=$conf->global->COMMANDE_SAPHIR_MASK;

		if (! $mask)
		{
			$this->error='NotConfigured';
			return 0;
		}

		$date = ($object->date_commande ? $object->date_commande : $object->date);

<<<<<<< HEAD
		$numFinal=get_next_value($db,$mask,'commande','ref','',$objsoc,$date);
=======
		$numFinal=get_next_value($db, $mask, 'commande', 'ref', '', $objsoc, $date);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		return  $numFinal;
	}


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *  Return next free value
	 *
	 *  @param	Societe		$objsoc     Object third party
<<<<<<< HEAD
	 * 	@param	string		$objforref	Object for number to search
	 *  @return string      			Next free value
     */
    function commande_get_num($objsoc,$objforref)
    {
        return $this->getNextValue($objsoc,$objforref);
    }

}

=======
	 *  @param	string		$objforref	Object for number to search
	 *  @return string      			Next free value
     */
    public function commande_get_num($objsoc, $objforref)
    {
        // phpcs:enable
        return $this->getNextValue($objsoc, $objforref);
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
