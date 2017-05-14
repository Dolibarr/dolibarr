<?php
/* Copyright (C) 2017      Alexandre Spangaro   <aspangaro@zendsi.com>
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
 *	\file			htdocs/stripe/lib/stripe.lib.php
 *	\ingroup		stripe
 *  \brief			Library for common stripe functions
 */

/**
 *  Define head array for tabs of stripe tools setup pages
 *
 *  @return			Array of head
 */
function stripeadmin_prepare_head()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/stripe/admin/stripe.php";
	$head[$h][1] = $langs->trans("Stripe");
	$head[$h][2] = 'stripeaccount';
	$h++;

	$object=new stdClass();

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'stripeadmin');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'stripeadmin','remove');

    return $head;
}



/**
 * Return string with full Url
 *
 * @param   string	$type		Type of URL ('free', 'order', 'invoice', 'contractline', 'membersubscription' ...)
 * @param	string	$ref		Ref of object
 * @return	string				Url string
 */
function showStripePaymentUrl($type,$ref)
{
	global $conf, $langs;

	$langs->load("paypal");
    $langs->load("paybox");
	$langs->load("stripe");

    $servicename='Stripe';
    $out='<br><br>';
    $out.=img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePayment",$servicename).'<br>';
    $url=getStripePaymentUrl(0,$type,$ref);
    $out.='<input type="text" id="stripeurl" class="quatrevingtpercent" value="'.$url.'"><br>';
    $out.=ajax_autoselect("stripeurl", 0);
    return $out;
}

/**
 * Return string with full Url
 *
 * @param   int		$mode		0=True url, 1=Url formated with colors
 * @param   string	$type		Type of URL ('free', 'order', 'invoice', 'contractline', 'membersubscription' ...)
 * @param	string	$ref		Ref of object
 * @param	int		$amount		Amount
 * @param	string	$freetag	Free tag
 * @return	string				Url string
 */
function getStripePaymentUrl($mode,$type,$ref='',$amount='9.99',$freetag='your_free_tag')
{
	global $conf;

	$ref=str_replace(' ','',$ref);
	
    if ($type == 'free')
    {
	    $out=DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?amount='.($mode?'<font color="#666666">':'').$amount.($mode?'</font>':'').'&tag='.($mode?'<font color="#666666">':'').$freetag.($mode?'</font>':'');
    }
    if ($type == 'order')
    {
        $out=DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?source=order&ref='.($mode?'<font color="#666666">':'');
        if ($mode == 1) $out.='order_ref';
        if ($mode == 0) $out.=urlencode($ref);
	    $out.=($mode?'</font>':'');
    }
    if ($type == 'invoice')
    {
        $out=DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?source=invoice&ref='.($mode?'<font color="#666666">':'');
        if ($mode == 1) $out.='invoice_ref';
        if ($mode == 0) $out.=urlencode($ref);
	    $out.=($mode?'</font>':'');
    }
    if ($type == 'contractline')
    {
        $out=DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?source=contractline&ref='.($mode?'<font color="#666666">':'');
        if ($mode == 1) $out.='contractline_ref';
        if ($mode == 0) $out.=urlencode($ref);
	    $out.=($mode?'</font>':'');
    }
    if ($type == 'membersubscription')
    {
        $out=DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?source=membersubscription&ref='.($mode?'<font color="#666666">':'');
        if ($mode == 1) $out.='member_ref';
        if ($mode == 0) $out.=urlencode($ref);
	    $out.=($mode?'</font>':'');
    }

    // For multicompany
    $out.="&entity=".$conf->entity; // Check the entity because He may be the same reference in several entities

    return $out;
}


/**
 * Show footer of company in HTML pages
 *
 * @param   Societe		$fromcompany	Third party
 * @param   Translate	$langs			Output language
 * @return	void
 */
function html_print_stripe_footer($fromcompany,$langs)
{
	global $conf;

	// Juridical status
	$line1="";
	if ($fromcompany->forme_juridique_code)
	{
		$line1.=($line1?" - ":"").getFormeJuridiqueLabel($fromcompany->forme_juridique_code);
	}
	// Capital
	if ($fromcompany->capital)
	{
		$line1.=($line1?" - ":"").$langs->transnoentities("CapitalOf",$fromcompany->capital)." ".$langs->transnoentities("Currency".$conf->currency);
	}
	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || ! $fromcompany->idprof2))
	{
		$field=$langs->transcountrynoentities("ProfId1",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line1.=($line1?" - ":"").$field.": ".$fromcompany->idprof1;
	}
	// Prof Id 2
	if ($fromcompany->idprof2)
	{
		$field=$langs->transcountrynoentities("ProfId2",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line1.=($line1?" - ":"").$field.": ".$fromcompany->idprof2;
	}

	// Second line of company infos
	$line2="";
	// Prof Id 3
	if ($fromcompany->idprof3)
	{
		$field=$langs->transcountrynoentities("ProfId3",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line2.=($line2?" - ":"").$field.": ".$fromcompany->idprof3;
	}
	// Prof Id 4
	if ($fromcompany->idprof4)
	{
		$field=$langs->transcountrynoentities("ProfId4",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line2.=($line2?" - ":"").$field.": ".$fromcompany->idprof4;
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '')
	{
		$line2.=($line2?" - ":"").$langs->transnoentities("VATIntraShort").": ".$fromcompany->tva_intra;
	}

	print '<br><br><hr>'."\n";
	print '<div class="center"><font style="font-size: 10px;">'."\n";
	print $fromcompany->name.'<br>';
	print $line1.'<br>';
	print $line2;
	print '</font></div>'."\n";
}

