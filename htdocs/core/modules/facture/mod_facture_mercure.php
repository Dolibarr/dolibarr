<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/core/modules/facture/mod_facture_mercure.php
 *	\ingroup    facture
 *	\brief      File containing class for numbering module Mercure
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/facture/modules_facture.php';


/**
 *	Class of numbering module Mercure for invoices
 */
class mod_facture_mercure extends ModeleNumRefFactures
{
<<<<<<< HEAD
    var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
    var $error = '';


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
     *  Returns the description of the numbering model
     *
     *  @return     string      Texte descripif
     */
    public function info()
    {
        global $db, $conf, $langs;

        $langs->load("bills");

        $form = new Form($db);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
        $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        $texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        $texte.= '<input type="hidden" name="action" value="updateMask">';
        $texte.= '<input type="hidden" name="maskconstinvoice" value="FACTURE_MERCURE_MASK_INVOICE">';
        $texte.= '<input type="hidden" name="maskconstreplacement" value="FACTURE_MERCURE_MASK_REPLACEMENT">';
        $texte.= '<input type="hidden" name="maskconstcredit" value="FACTURE_MERCURE_MASK_CREDIT">';
		$texte.= '<input type="hidden" name="maskconstdeposit" value="FACTURE_MERCURE_MASK_DEPOSIT">';
        $texte.= '<table class="nobordernopadding" width="100%">';

<<<<<<< HEAD
        $tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Invoice"),$langs->transnoentities("Invoice"));
        $tooltip.=$langs->trans("GenericMaskCodes2");
        $tooltip.=$langs->trans("GenericMaskCodes3");
        $tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Invoice"),$langs->transnoentities("Invoice"));
=======
        $tooltip=$langs->trans("GenericMaskCodes", $langs->transnoentities("Invoice"), $langs->transnoentities("Invoice"));
        $tooltip.=$langs->trans("GenericMaskCodes2");
        $tooltip.=$langs->trans("GenericMaskCodes3");
        $tooltip.=$langs->trans("GenericMaskCodes4a", $langs->transnoentities("Invoice"), $langs->transnoentities("Invoice"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $tooltip.=$langs->trans("GenericMaskCodes5");

        // Parametrage du prefix
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceStandard").'):</td>';
<<<<<<< HEAD
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskinvoice" value="'.$conf->global->FACTURE_MERCURE_MASK_INVOICE.'">',$tooltip,1,1).'</td>';

        $texte.= '<td align="left" rowspan="3">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
=======
        $texte.= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskinvoice" value="'.$conf->global->FACTURE_MERCURE_MASK_INVOICE.'">', $tooltip, 1, 1).'</td>';

        $texte.= '<td class="left" rowspan="3">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $texte.= '</tr>';

        // Parametrage du prefix des replacement
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceReplacement").'):</td>';
<<<<<<< HEAD
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskreplacement" value="'.$conf->global->FACTURE_MERCURE_MASK_REPLACEMENT.'">',$tooltip,1,1).'</td>';
        $texte.= '</tr>';
        
        // Parametrage du prefix des avoirs
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceAvoir").'):</td>';
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskcredit" value="'.$conf->global->FACTURE_MERCURE_MASK_CREDIT.'">',$tooltip,1,1).'</td>';
=======
        $texte.= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskreplacement" value="'.$conf->global->FACTURE_MERCURE_MASK_REPLACEMENT.'">', $tooltip, 1, 1).'</td>';
        $texte.= '</tr>';

        // Parametrage du prefix des avoirs
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceAvoir").'):</td>';
        $texte.= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskcredit" value="'.$conf->global->FACTURE_MERCURE_MASK_CREDIT.'">', $tooltip, 1, 1).'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $texte.= '</tr>';

        // Parametrage du prefix des acomptes
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceDeposit").'):</td>';
<<<<<<< HEAD
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskdeposit" value="'.$conf->global->FACTURE_MERCURE_MASK_DEPOSIT.'">',$tooltip,1,1).'</td>';
=======
        $texte.= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskdeposit" value="'.$conf->global->FACTURE_MERCURE_MASK_DEPOSIT.'">', $tooltip, 1, 1).'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $texte.= '</tr>';

        $texte.= '</table>';
        $texte.= '</form>';

        return $texte;
    }

    /**
     *  Return an example of number value
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
     * Return next value
     *
     * @param	Societe		$objsoc     Object third party
<<<<<<< HEAD
     * @param   Facture		$facture	Object invoice
     * @param   string		$mode       'next' for next value or 'last' for last value
     * @return  string      			Value if OK, 0 if KO
     */
    function getNextValue($objsoc,$facture,$mode='next')
    {
        global $db,$conf;

        require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

        // Get Mask value
        $mask = '';
        if (is_object($facture) && $facture->type == 1) 
=======
     * @param   Facture		$invoice	Object invoice
     * @param   string		$mode       'next' for next value or 'last' for last value
     * @return  string      			Value if OK, 0 if KO
     */
    public function getNextValue($objsoc, $invoice, $mode = 'next')
    {
    	global $db,$conf;

    	require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

        // Get Mask value
        $mask = '';
        if (is_object($invoice) && $invoice->type == 1)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        {
        	$mask=$conf->global->FACTURE_MERCURE_MASK_REPLACEMENT;
        	if (! $mask)
        	{
        		$mask=$conf->global->FACTURE_MERCURE_MASK_INVOICE;
        	}
        }
<<<<<<< HEAD
        else if (is_object($facture) && $facture->type == 2) $mask=$conf->global->FACTURE_MERCURE_MASK_CREDIT;
		else if (is_object($facture) && $facture->type == 3) $mask=$conf->global->FACTURE_MERCURE_MASK_DEPOSIT;
=======
        elseif (is_object($invoice) && $invoice->type == 2) $mask=$conf->global->FACTURE_MERCURE_MASK_CREDIT;
        elseif (is_object($invoice) && $invoice->type == 3) $mask=$conf->global->FACTURE_MERCURE_MASK_DEPOSIT;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        else $mask=$conf->global->FACTURE_MERCURE_MASK_INVOICE;
        if (! $mask)
        {
            $this->error='NotConfigured';
            return 0;
        }

<<<<<<< HEAD
        $where='';
        //if ($facture->type == 2) $where.= " AND type = 2";
        //else $where.=" AND type != 2";

        $numFinal=get_next_value($db,$mask,'facture','facnumber',$where,$objsoc,$facture->date,$mode);
        if (! preg_match('/([0-9])+/',$numFinal)) $this->error = $numFinal;

        return  $numFinal;
=======
    	$where='';
    	//if ($facture->type == 2) $where.= " AND type = 2";
    	//else $where.=" AND type != 2";

    	// Get entities
    	$entity = getEntity('invoicenumber', 1, $invoice);

    	$numFinal=get_next_value($db, $mask, 'facture', 'ref', $where, $objsoc, $invoice->date, $mode, false, null, $entity);
    	if (! preg_match('/([0-9])+/', $numFinal)) $this->error = $numFinal;

    	return  $numFinal;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


    /**
     * Return next free value
     *
     * @param	Societe		$objsoc     	Object third party
     * @param	string		$objforref		Object for number to search
     * @param   string		$mode       	'next' for next value or 'last' for last value
     * @return  string      				Next free value
     */
<<<<<<< HEAD
    function getNumRef($objsoc,$objforref,$mode='next')
    {
        return $this->getNextValue($objsoc,$objforref,$mode);
    }

=======
    public function getNumRef($objsoc, $objforref, $mode = 'next')
    {
        return $this->getNextValue($objsoc, $objforref, $mode);
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
