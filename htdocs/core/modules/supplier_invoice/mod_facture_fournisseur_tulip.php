<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Philippe Grand       <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/core/modules/supplier_invoice/mod_facture_fournisseur_tulip.php
 *	\ingroup    commande
 *	\brief      File containing the Tulip Class of numbering models of suppliers invoices references
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/supplier_invoice/modules_facturefournisseur.php';


/**
	\class      mod_facture_fournisseur_tulip
	\brief      Tulip Class of numbering models of suppliers invoices references
*/
class mod_facture_fournisseur_tulip extends ModeleNumRefSuppliersInvoices
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Tulip';


    /**
     *  Returns the description of the model numbering
     *
     * 	@return     string      Description Text
     */
	function info()
    {
    	global $conf,$langs;

		$langs->load("bills");
		$langs->load("admin");

		$form = new Form($this->db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconstinvoice" value="SUPPLIER_INVOICE_TULIP_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Invoice"),$langs->transnoentities("Invoice"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Invoice"),$langs->transnoentities("Invoice"));
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Parametrage du prefix
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskinvoice" value="'.$conf->global->SUPPLIER_INVOICE_TULIP_MASK.'">',$tooltip,1,1).'</td>';

		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
    }

    /**
     *  Returns a numbering example
     *
     *  @return     string     Example
     */
    function getExample()
    {
    	global $conf,$langs,$mysoc;

    	$old_code_client=$mysoc->code_client;
    	$mysoc->code_client='CCCCCCCCCC';
    	$numExample = $this->getNextValue($mysoc,'');
		$mysoc->code_client=$old_code_client;

		if (! $numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
    }

	/**
	 * Return next value
	 *
	 * @param	Societe		$objsoc     Object third party
	 * @param  	Object	    $object		Object
     * @param	string		$mode       'next' for next value or 'last' for last value
     * @return 	string      			Value if OK, 0 if KO
	 */
    function getNextValue($objsoc,$object,$mode='next')
    {
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

		// On defini critere recherche compteur
		$mask=$conf->global->SUPPLIER_INVOICE_TULIP_MASK;

		if (! $mask)
		{
			$this->error='NotConfigured';
			return 0;
		}

		$numFinal=get_next_value($db,$mask,'facture_fournisseur','ref','',$objsoc->code_fournisseur,$object->datec);

		return  $numFinal;
	}

    /**
	 * Return next free value
	 *
     * @param	Societe		$objsoc     	Object third party
     * @param	string		$objforref		Object for number to search
     * @param   string		$mode       	'next' for next value or 'last' for last value
     * @return  string      				Next free value
     */
	function getNumRef($objsoc,$objforref,$mode='next')
	{
		return $this->getNextValue($objsoc,$objforref,$mode);
	}
}

?>