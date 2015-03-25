<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014       Alexandre Spangaro		<alexandre.spangaro@gmail.com> 
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
 */

/**
 *	\file       htdocs/core/modules/dons/html_cerfafr.modules.php
 *	\ingroup    don
 *	\brief      Form of donation
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';


/**
 *	Class to generate document for subscriptions
 */
class html_cerfafr extends ModeleDon
{
    /**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        global $conf,$langs;

        $this->db = $db;
        $this->name = "cerfafr";
        $this->description = $langs->trans('DonationsReceiptModel').' - fr_FR - Cerfa 11580*03';

        // Dimension page for size A4
        $this->type = 'html';
    }


	/**
	 * 	Return if a module can be used or not
	 *
	 *  @return	boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}


    /**
     *  Write the object to document file to disk
     *
     *	@param	Don			$don	        Donation object
     *  @param  Translate	$outputlangs    Lang object for output language
     *	@return	int             			>0 if OK, <0 if KO
     */
    function write_file($don,$outputlangs)
    {
		global $user,$conf,$langs,$mysoc;

		$now=dol_now();
		$id = (! is_object($don)?$don:'');

		if (! is_object($outputlangs)) $outputlangs=$langs;

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("donations");

        if (! empty($conf->don->dir_output))
        {
			// Definition of the object don (for upward compatibility)
        	if (! is_object($don))
        	{
	            $don = new Don($this->db);
	            $ret=$don->fetch($id);
	            $id=$don->id;
			}

			// Definition of $dir and $file
			if (! empty($don->specimen))
			{
				$dir = $conf->don->dir_output;
				$file = $dir . "/SPECIMEN.html";
			}
			else
			{
				$donref = dol_sanitizeFileName($don->ref);
				$dir = $conf->don->dir_output . "/" . get_exdir($donref,2);
				$file = $dir . "/" . $donref . ".html";
			}

	        if (! file_exists($dir))
	        {
	            if (dol_mkdir($dir) < 0)
	            {
	                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
	                return -1;
	            }
	        }

            if (file_exists($dir))
            {
            	$formclass = new Form($this->db);

            	// This is not the proper way to do it but $formclass->form_modes_reglement
            	// prints the translation instead of returning it
            	if ($don->modepaiementid)
            	{
            		$formclass->load_cache_types_paiements();
            		$paymentmode = $formclass->cache_types_paiements[$don->modepaiementid]['label'];
            	}
            	else $paymentmode = '';

		        // Define contents
		        $donmodel=DOL_DOCUMENT_ROOT ."/core/modules/dons/html_cerfafr.html";
		        $form = implode('', file($donmodel));
		        $form = str_replace('__REF__',$don->id,$form);
		        $form = str_replace('__DATE__',dol_print_date($don->date,'day',false,$outputlangs),$form);
		        //$form = str_replace('__IP__',$user->ip,$form); // TODO $user->ip not exist
		        $form = str_replace('__AMOUNT__',$don->amount,$form);
		        $form = str_replace('__CURRENCY__',$outputlangs->transnoentitiesnoconv("Currency".$conf->currency),$form);
		        $form = str_replace('__CURRENCYCODE__',$conf->currency,$form);
		        $form = str_replace('__MAIN_INFO_SOCIETE_NOM__',$mysoc->name,$form);
		        $form = str_replace('__MAIN_INFO_SOCIETE_ADDRESS__',$mysoc->address,$form);
		        $form = str_replace('__MAIN_INFO_SOCIETE_ZIP__',$mysoc->zip,$form);
		        $form = str_replace('__MAIN_INFO_SOCIETE_TOWN__',$mysoc->town,$form);
				$form = str_replace('__DONATOR_FIRSTNAME__',$don->firstname,$form);
            	$form = str_replace('__DONATOR_LASTNAME__',$don->lastname,$form);
		        $form = str_replace('__DONATOR_ADDRESS__',$don->address,$form);
		        $form = str_replace('__DONATOR_ZIP__',$don->zip,$form);
		        $form = str_replace('__DONATOR_TOWN__',$don->town,$form);
		        $form = str_replace('__PAYMENTMODE_LIB__ ', $paymentmode,$form);
		        $form = str_replace('__NOW__',dol_print_date($now,'day',false,$outputlangs),$form);
		        $form = str_replace('__DonationRef__',$outputlangs->trans("DonationRef"),$form);
				$form = str_replace('__DonationTitle__',$outputlangs->trans("DonationTitle"),$form);
		        $form = str_replace('__DonationReceipt__',$outputlangs->trans("DonationReceipt"),$form);
		        $form = str_replace('__DonationRecipient__',$outputlangs->trans("DonationRecipient"),$form);
		        $form = str_replace('__DonationDatePayment__',$outputlangs->trans("DonationDatePayment"),$form);
				$form = str_replace('__PaymentMode__',$outputlangs->trans("PaymentMode"),$form);
		        $form = str_replace('__Name__',$outputlangs->trans("Name"),$form);
		        $form = str_replace('__Address__',$outputlangs->trans("Address"),$form);
		        $form = str_replace('__Zip__',$outputlangs->trans("Zip"),$form);
		        $form = str_replace('__Town__',$outputlangs->trans("Town"),$form);
				$form = str_replace('__Object__',$outputlangs->trans("Object"),$form);
		        $form = str_replace('__Donor__',$outputlangs->trans("Donor"),$form);
		        $form = str_replace('__Date__',$outputlangs->trans("Date"),$form);
		        $form = str_replace('__Signature__',$outputlangs->trans("Signature"),$form);
		        $form = str_replace('__Message__',$outputlangs->trans("Message"),$form);
		        $form = str_replace('__IConfirmDonationReception__',$outputlangs->trans("IConfirmDonationReception"),$form);
		        $form = str_replace('__DonationMessage__',$conf->global->DONATION_MESSAGE,$form);
				
				$frencharticle='';
		        if (preg_match('/fr/i',$outputlangs->defaultlang)) $frencharticle='<font size="+1">Article 200, 238 bis et 885-0 V bis A du code général des impôts (CGI)</font>';
				$form = str_replace('__FrenchArticle__',$frencharticle,$form);
				
				$frencheligibility='';
				if (preg_match('/fr/i',$outputlangs->defaultlang)) $frencheligibility='Le bénéficiaire certifie sur l\'honneur que les dons et versements qu\'il reçoit ouvrent droit à la réduction d\'impôt prévue à l\'article :';
				$form = str_replace('__FrenchEligibility__',$frencheligibility,$form);
				
				$art200='';
				if (preg_match('/fr/i',$outputlangs->defaultlang)) {
					if ($conf->global->DONATION_ART200 >= 1) 
					{
						$art200='<input type="checkbox" checked="checked">200 du CGI';
					}
					else
					{
						$art200='<input type="checkbox">200 du CGI';
					}
				}
				$form = str_replace('__ARTICLE200__',$art200,$form);

				$art238='';
				if (preg_match('/fr/i',$outputlangs->defaultlang)) {
					if ($conf->global->DONATION_ART238 >= 1) 
					{
						$art238='<input type="checkbox" checked="checked">238 bis du CGI';
					}
					else
					{
						$art238='<input type="checkbox">238 bis du CGI';
					}
				}
				$form = str_replace('__ARTICLE238__',$art238,$form);
				
				$art885='';
				if (preg_match('/fr/i',$outputlangs->defaultlang)) {
					if ($conf->global->DONATION_ART885 >= 1) 
					{
						$art885='<input type="checkbox" checked="checked">885-0 V bis du CGI';
					}
					else
					{
						$art885='<input type="checkbox">885-0 V bis du CGI';
					}
				}
				$form = str_replace('__ARTICLE885__',$art885,$form);
				
				// Save file on disk
		        dol_syslog("html_cerfafr::write_file $file");
		        $handle=fopen($file,"w");
		        fwrite($handle,$form);
		        fclose($handle);
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

		        return 1;
            }
            else
            {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                return 0;
            }
	    }
        else
        {
            $this->error=$langs->trans("ErrorConstantNotDefined","DON_OUTPUTDIR");
            return 0;
		}
        $this->error=$langs->trans("ErrorUnknown");
        return 0;   // Error by default
    }
}

