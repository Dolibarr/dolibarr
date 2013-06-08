<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/core/class/commondocgenerator.class.php
 *		\ingroup    core
 *		\brief      File of parent class for documents generators
 */


/**
 *	\class      CommonDocGenerator
 *	\brief      Parent class for documents generators
 */
abstract class CommonDocGenerator
{
	var $error='';


    /**
     * Define array with couple subtitution key => subtitution value
     *
     * @param   User		$user           User
     * @param   Translate	$outputlangs    Language object for output
     * @return	array						Array of substitution key->code
     */
    function get_substitutionarray_user($user,$outputlangs)
    {
        global $conf;

        return array(
            'myuser_lastname'=>$user->lastname,
            'myuser_firstname'=>$user->firstname,
            'myuser_fullname'=>$user->getFullName($outputlangs,1),
            'myuser_login'=>$user->login,
            'myuser_phone'=>$user->office_phone,
       		'myuser_address'=>$user->address,
       		'myuser_zip'=>$user->zip,
       		'myuser_town'=>$user->town,
       		'myuser_country'=>$user->country,
        	'myuser_country_code'=>$user->country_code,
       		'myuser_state'=>$user->state,
        	'myuser_state_code'=>$user->state_code,
        	'myuser_fax'=>$user->office_fax,
            'myuser_mobile'=>$user->user_mobile,
            'myuser_email'=>$user->email,
        	'myuser_logo'=>$logotouse,
            'myuser_web'=>''	// url not exist in $user object
        );
    }


    /**
     * Define array with couple subtitution key => subtitution value
     *
     * @param   Societe		$mysoc			Object thirdparty
     * @param   Translate	$outputlangs    Language object for output
     * @return	array						Array of substitution key->code
     */
    function get_substitutionarray_mysoc($mysoc,$outputlangs)
    {
        global $conf;

        if (empty($mysoc->forme_juridique) && ! empty($mysoc->forme_juridique_code))
        {
            $mysoc->forme_juridique=getFormeJuridiqueLabel($mysoc->forme_juridique_code);
        }
        if (empty($mysoc->country) && ! empty($mysoc->country_code))
        {
        	$mysoc->country=$outputlangs->transnoentitiesnoconv("Country".$mysoc->country_code);
        }
        if (empty($mysoc->state) && ! empty($mysoc->state_code))
        {
        	$mysoc->state=getState($mysoc->state_code,0);
        }

        $logotouse=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;

        return array(
            'mycompany_logo'=>$logotouse,
            'mycompany_name'=>$mysoc->name,
            'mycompany_email'=>$mysoc->email,
            'mycompany_phone'=>$mysoc->phone,
            'mycompany_fax'=>$mysoc->fax,
            'mycompany_address'=>$mysoc->address,
            'mycompany_zip'=>$mysoc->zip,
            'mycompany_town'=>$mysoc->town,
            'mycompany_country'=>$mysoc->country,
            'mycompany_country_code'=>$mysoc->country_code,
            'mycompany_state'=>$mysoc->state,
            'mycompany_state_code'=>$mysoc->state_code,
        	'mycompany_web'=>$mysoc->url,
            'mycompany_juridicalstatus'=>$mysoc->forme_juridique,
            'mycompany_capital'=>$mysoc->capital,
            'mycompany_barcode'=>$mysoc->barcode,
            'mycompany_idprof1'=>$mysoc->idprof1,
            'mycompany_idprof2'=>$mysoc->idprof2,
            'mycompany_idprof3'=>$mysoc->idprof3,
            'mycompany_idprof4'=>$mysoc->idprof4,
            'mycompany_idprof5'=>$mysoc->idprof5,
            'mycompany_idprof6'=>$mysoc->idprof6,
        	'mycompany_vatnumber'=>$mysoc->tva_intra,
            'mycompany_note'=>$mysoc->note
        );
    }


    /**
     * Define array with couple subtitution key => subtitution value
     *
     * @param	Object		$object			Object
     * @param   Translate	$outputlangs    Language object for output
     * @return	array						Array of substitution key->code
     */
    function get_substitutionarray_thirdparty($object,$outputlangs)
    {
        global $conf;

        if (empty($object->country) && ! empty($object->country_code))
        {
        	$object->country=$outputlangs->transnoentitiesnoconv("Country".$object->country_code);
        }
        if (empty($mysoc->state) && ! empty($mysoc->state_code))
        {
        	$object->state=getState($object->state_code,0);
        }

        $array_thirdparty = array(
            'company_name'=>$object->name,
            'company_email'=>$object->email,
            'company_phone'=>$object->phone,
            'company_fax'=>$object->fax,
            'company_address'=>$object->address,
            'company_zip'=>$object->zip,
            'company_town'=>$object->town,
            'company_country'=>$object->country,
        	'company_country_code'=>$object->country_code,
            'company_state'=>$object->state,
        	'company_state_code'=>$object->state_code,
        	'company_web'=>$object->url,
            'company_barcode'=>$object->barcode,
            'company_vatnumber'=>$object->tva_intra,
            'company_customercode'=>$object->code_client,
            'company_suppliercode'=>$object->code_fournisseur,
            'company_customeraccountancycode'=>$object->code_compta,
            'company_supplieraccountancycode'=>$object->code_compta_fournisseur,
            'company_juridicalstatus'=>$object->forme_juridique,
            'company_capital'=>$object->capital,
            'company_idprof1'=>$object->idprof1,
            'company_idprof2'=>$object->idprof2,
            'company_idprof3'=>$object->idprof3,
            'company_idprof4'=>$object->idprof4,
            'company_idprof5'=>$object->idprof5,
            'company_idprof6'=>$object->idprof6,
            'company_note'=>$object->note
        );

        // Retrieve extrafields
        if(is_array($object->array_options) && count($object->array_options))
        {
        	if(!class_exists('Extrafields'))
        		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        	$extrafields = new ExtraFields($this->db);
        	$extralabels = $extrafields->fetch_name_optionals_label('societe',true);
        	$object->fetch_optionals($object->id,$extralabels);

        	foreach($extrafields->attribute_label as $key=>$label)
        	{
        		if($extrafields->attribute_type[$key] == 'price')
        		{
        			$object->array_options['options_'.$key] = price($object->array_options['options_'.$key]).' '.$outputlangs->getCurrencySymbol($conf->currency);
        		}
        		else if($extrafields->attribute_type[$key] == 'select')
        		{
        			$object->array_options['options_'.$key] = $extrafields->attribute_param[$key]['options'][$object->array_options['options_'.$key]];
        		}
        		$array_thirdparty=array_merge($array_thirdparty,array('company_options_'.$key => $object->array_options['options_'.$key]));
        	}
        }
        return $array_thirdparty;
    }


    /**
     * Define array with couple subtitution key => subtitution value
     *
     * @param   Translate	$outputlangs    Language object for output
     * @return	array						Array of substitution key->code
     */
    function get_substitutionarray_other($outputlangs)
    {
    	global $conf;

    	$now=dol_now('gmt');	// gmt
    	$array_other = array(
   			'current_date'=>dol_print_date($now,'day','tzuser'),
   			'current_datehour'=>dol_print_date($now,'dayhour','tzuser'),
   			'current_server_date'=>dol_print_date($now,'day','tzserver'),
   			'current_server_datehour'=>dol_print_date($now,'dayhour','tzserver'),
    	);

    	return $array_other;
    }


    /**
     * Define array with couple substitution key => substitution value
     *
     * @param   Object			$object             Main object to use as data source
     * @param   Translate		$outputlangs        Lang object to use for output
     * @param   array_key		$array_key	        Name of the key for return array
     * @return	array								Array of substitution
     */
    function get_substitutionarray_propal($object,$outputlangs,$array_key='object')
    {
    	global $conf;

    	$array_propal=array(
	    	$array_key.'_id'=>$object->id,
	    	$array_key.'_ref'=>$object->ref,
	    	$array_key.'_ref_ext'=>$object->ref_ext,
	    	$array_key.'_ref_customer'=>$object->ref_client,
	    	$array_key.'_hour'=>dol_print_date($object->date,'hour'),
    		$array_key.'_date'=>dol_print_date($object->date,'day'),
	    	$array_key.'_date_end'=>dol_print_date($object->fin_validite,'day'),
	    	$array_key.'_date_creation'=>dol_print_date($object->date_creation,'day'),
	    	$array_key.'_date_modification'=>dol_print_date($object->date_modification,'day'),
	    	$array_key.'_date_validation'=>dol_print_date($object->date_validation,'dayhour'),
	    	$array_key.'_payment_mode_code'=>$object->mode_reglement_code,
	    	$array_key.'_payment_mode'=>($outputlangs->transnoentitiesnoconv('PaymentType'.$object->mode_reglement_code)!='PaymentType'.$object->mode_reglement_code?$outputlangs->transnoentitiesnoconv('PaymentType'.$object->mode_reglement_code):$object->mode_reglement),
	    	$array_key.'_payment_term_code'=>$object->cond_reglement_code,
	    	$array_key.'_payment_term'=>($outputlangs->transnoentitiesnoconv('PaymentCondition'.$object->cond_reglement_code)!='PaymentCondition'.$object->cond_reglement_code?$outputlangs->transnoentitiesnoconv('PaymentCondition'.$object->cond_reglement_code):$object->cond_reglement),
	    	$array_key.'_total_ht'=>price($object->total_ht2),
	    	$array_key.'_total_vat'=>price($object->total_tva),
	    	$array_key.'_total_ttc'=>price($object->total_ttc),
	    	$array_key.'_total_discount_ht' => price($object->getTotalDiscount()),
	    	$array_key.'_vatrate'=>vatrate($object->tva),
	    	$array_key.'_note_private'=>$object->note,
	    	$array_key.'_note'=>$object->note_public,
    	);

    	// Add vat by rates
    	foreach ($object->lines as $line)
    	{
    		if (empty($array_propal[$array_key.'_total_vat_'.$line->tva_tx])) $array_propal[$array_key.'_total_vat_'.$line->tva_tx]=0;
    		$array_propal[$array_key.'_total_vat_'.$line->tva_tx]+=$line->total_tva;
    	}

    	// Retrieve extrafields
    	if(is_array($object->array_options) && count($object->array_options))
    	{
    		if(!class_exists('Extrafields'))
    			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    		$extrafields = new ExtraFields($this->db);
    		$extralabels = $extrafields->fetch_name_optionals_label('propal',true);
    		$object->fetch_optionals($object->id,$extralabels);

    		$array_propal = $this->fill_substitutionarray_with_extrafields($object,$array_propal,$extrafields,$array_key,$outputlangs);
    	}
    	return $array_propal;
    }


    /**
     *	Define array with couple substitution key => substitution value
     *
     *	@param  array			$line				Array of lines
     *	@param  Translate		$outputlangs        Lang object to use for output
     *	@return	array								Substitution array
     */
    function get_substitutionarray_propal_lines($line,$outputlangs)
    {
    	global $conf;

    	return array(
    	'line_fulldesc'=>doc_getlinedesc($line,$outputlangs),
    	'line_product_ref'=>$line->product_ref,
    	'line_product_label'=>$line->product_label,
    	'line_desc'=>$line->desc,
    	'line_vatrate'=>vatrate($line->tva_tx,true,$line->info_bits),
    	'line_up'=>price($line->subprice),
    	'line_qty'=>$line->qty,
    	'line_discount_percent'=>($line->remise_percent?$line->remise_percent.'%':''),
    	'line_price_ht'=>price($line->total_ht),
    	'line_price_ttc'=>price($line->total_ttc),
    	'line_price_vat'=>price($line->total_tva),
    	'line_date_start'=>$line->date_start,
    	'line_date_end'=>$line->date_end
    	);
    }

    /**
     *	Fill array with couple extrafield key => extrafield value
     *
     *	@param  Object			$object				Object with extrafields (must have $object->array_options filled)
     *	@param  array			$array_to_fill      Substitution array
     *  @param  Extrafields		$extrafields        Extrafields object
     *  @param   array_key		$array_key	        Name of the key for return array
     *  @param  Translate		$outputlangs        Lang object to use for output
     *	@return	array								Substitution array
     */
	function fill_substitutionarray_with_extrafields($object,$array_to_fill,$extrafields,$array_key,$outputlangs)
	{
		global $conf;
		foreach($extrafields->attribute_label as $key=>$label)
		{
			if($extrafields->attribute_type[$key] == 'price')
			{
				$object->array_options['options_'.$key] = price2num($object->array_options['options_'.$key],2);
				$object->array_options['options_'.$key.'_currency'] = $object->array_options['options_'.$key].' '.$outputlangs->getCurrencySymbol($conf->currency);
				//Add value to store price with currency
				$array_to_fill=array_merge($array_to_fill,array($array_key.'_options_'.$key.'_currency' => $object->array_options['options_'.$key.'_currency']));
			}
			else if($extrafields->attribute_type[$key] == 'select')
			{
				$object->array_options['options_'.$key] = $extrafields->attribute_param[$key]['options'][$object->array_options['options_'.$key]];
			}
			else if($extrafields->attribute_type[$key] == 'date')
			{
				$object->array_options['options_'.$key] = (strlen($object->array_options['options_'.$key])>0?dol_print_date($object->array_options['options_'.$key],'day'):'');
			}
			else if($extrafields->attribute_type[$key] == 'datetime')
			{
				$object->array_options['options_'.$key] = ($object->array_options['options_'.$key]!="0000-00-00 00:00:00"?dol_print_date($object->array_options['options_'.$key],'dayhour'):'');
			}
			$array_to_fill=array_merge($array_to_fill,array($array_key.'_options_'.$key => $object->array_options['options_'.$key]));
		}

		return $array_to_fill;

	}


	/**
	 * Rect pdf
	 *
	 * @param	PDF		$pdf			Object PDF
	 * @param	float	$x				Abscissa of first point
	 * @param	float	$y		        Ordinate of first point
	 * @param	float	$l				??
	 * @param	float	$h				??
	 * @param	int		$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 * @param	int		$hidebottom		Hide bottom
	 * @return	void
	 */
    function printRect($pdf, $x, $y, $l, $h, $hidetop=0, $hidebottom=0)
    {
	    if (empty($hidetop) || $hidetop==-1) $pdf->line($x, $y, $x+$l, $y);
	    $pdf->line($x+$l, $y, $x+$l, $y+$h);
	    if (empty($hidebottom)) $pdf->line($x+$l, $y+$h, $x, $y+$h);
	    $pdf->line($x, $y+$h, $x, $y);
    }
}

?>
