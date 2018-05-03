<?php
/**
 * Copyright (C) 2013	Marcos García	<marcosgdf@gmail.com>
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
 * Returns an array with the tabs for the "Payment" section
 * It loads tabs from modules looking for the entity payment
 *
 * @param Paiement $object Current payment object
 * @return array Tabs for the payment section
 */
function payment_prepare_head(Paiement $object) {

	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'payment';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'payment');

	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'payment', 'remove');

	return $head;
}

/**
 * Returns an array with the tabs for the "Supplier payment" section
 * It loads tabs from modules looking for the entity payment_supplier
 *
 * @param Paiement $object Current payment object
 * @return array Tabs for the payment section
 */
function payment_supplier_prepare_head(Paiement $object) {

	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'payment';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'payment_supplier');

	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'payment_supplier', 'remove');

	return $head;
}


/**
 * Return string with full Url
 *
 * @param   string	$type		Type of URL ('free', 'order', 'invoice', 'contractline', 'membersubscription' ...)
 * @param	string	$ref		Ref of object
 * @return	string				Url string
 */
function showOnlinePaymentUrl($type,$ref)
{
	global $conf, $langs;

	$langs->load("payment");
	$langs->load("paybox");
	$servicename='Online';

	$out = img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePayment",$servicename).'<br>';
	$url = getOnlinePaymentUrl(0,$type,$ref);
	$out.= '<input type="text" id="onlinepaymenturl" class="quatrevingtpercent" value="'.$url.'">';
	$out.= ajax_autoselect("onlinepaymenturl", 0);
	return $out;
}

/**
 * Return string with full Url
 *
 * @param   int		$mode		0=True url, 1=Url formated with colors
 * @param   string	$type		Type of URL ('free', 'order', 'invoice', 'contractline', 'membersubscription' ...)
 * @param	string	$ref		Ref of object
 * @param	int		$amount		Amount (required for $type='free' only)
 * @param	string	$freetag	Free tag
 * @return	string				Url string
 */
function getOnlinePaymentUrl($mode, $type, $ref='', $amount='9.99', $freetag='your_free_tag')
{
	global $conf;

	$ref=str_replace(' ','',$ref);
	$out='';

	if ($type == 'free')
	{
		$out=DOL_MAIN_URL_ROOT.'/public/payment/newpayment.php?amount='.($mode?'<font color="#666666">':'').$amount.($mode?'</font>':'').'&tag='.($mode?'<font color="#666666">':'').$freetag.($mode?'</font>':'');
		if (! empty($conf->global->PAYMENT_SECURITY_TOKEN))
		{
			if (empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) $out.='&securekey='.$conf->global->PAYMENT_SECURITY_TOKEN;
			else $out.='&securekey='.dol_hash($conf->global->PAYMENT_SECURITY_TOKEN, 2);
		}
	}
	if ($type == 'order')
	{
		$out=DOL_MAIN_URL_ROOT.'/public/payment/newpayment.php?source=order&ref='.($mode?'<font color="#666666">':'');
		if ($mode == 1) $out.='order_ref';
		if ($mode == 0) $out.=urlencode($ref);
		$out.=($mode?'</font>':'');
		if (! empty($conf->global->PAYMENT_SECURITY_TOKEN))
		{
			if (empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) $out.='&securekey='.$conf->global->PAYMENT_SECURITY_TOKEN;
			else
			{
				$out.='&securekey='.($mode?'<font color="#666666">':'');
				if ($mode == 1) $out.="hash('".$conf->global->PAYMENT_SECURITY_TOKEN."' + '".$type."' + order_ref)";
				if ($mode == 0) $out.= dol_hash($conf->global->PAYMENT_SECURITY_TOKEN . $type . $ref, 2);
				$out.=($mode?'</font>':'');
			}
		}
	}
	if ($type == 'invoice')
	{
		$out=DOL_MAIN_URL_ROOT.'/public/payment/newpayment.php?source=invoice&ref='.($mode?'<font color="#666666">':'');
		if ($mode == 1) $out.='invoice_ref';
		if ($mode == 0) $out.=urlencode($ref);
		$out.=($mode?'</font>':'');
		if (! empty($conf->global->PAYMENT_SECURITY_TOKEN))
		{
			if (empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) $out.='&securekey='.$conf->global->PAYMENT_SECURITY_TOKEN;
			else
			{
				$out.='&securekey='.($mode?'<font color="#666666">':'');
				if ($mode == 1) $out.="hash('".$conf->global->PAYMENT_SECURITY_TOKEN."' + '".$type."' + invoice_ref)";
				if ($mode == 0) $out.= dol_hash($conf->global->PAYMENT_SECURITY_TOKEN . $type . $ref, 2);
				$out.=($mode?'</font>':'');
			}
		}
	}
	if ($type == 'contractline')
	{
		$out=DOL_MAIN_URL_ROOT.'/public/payment/newpayment.php?source=contractline&ref='.($mode?'<font color="#666666">':'');
		if ($mode == 1) $out.='contractline_ref';
		if ($mode == 0) $out.=urlencode($ref);
		$out.=($mode?'</font>':'');
		if (! empty($conf->global->PAYMENT_SECURITY_TOKEN))
		{
			if (empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) $out.='&securekey='.$conf->global->PAYMENT_SECURITY_TOKEN;
			else
			{
				$out.='&securekey='.($mode?'<font color="#666666">':'');
				if ($mode == 1) $out.="hash('".$conf->global->PAYMENT_SECURITY_TOKEN."' + '".$type."' + contractline_ref)";
				if ($mode == 0) $out.= dol_hash($conf->global->PAYMENT_SECURITY_TOKEN . $type . $ref, 2);
				$out.=($mode?'</font>':'');
			}
		}
	}
	if ($type == 'member' || $type == 'membersubscription')
	{
		$out=DOL_MAIN_URL_ROOT.'/public/payment/newpayment.php?source=membersubscription&ref='.($mode?'<font color="#666666">':'');
		if ($mode == 1) $out.='member_ref';
		if ($mode == 0) $out.=urlencode($ref);
		$out.=($mode?'</font>':'');
		if (! empty($conf->global->PAYMENT_SECURITY_TOKEN))
		{
			if (empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) $out.='&securekey='.$conf->global->PAYMENT_SECURITY_TOKEN;
			else
			{
				$out.='&securekey='.($mode?'<font color="#666666">':'');
				if ($mode == 1) $out.="hash('".$conf->global->PAYMENT_SECURITY_TOKEN."' + '".$type."' + member_ref)";
				if ($mode == 0) $out.= dol_hash($conf->global->PAYMENT_SECURITY_TOKEN . $type . $ref, 2);
				$out.=($mode?'</font>':'');
			}
		}
	}

	// For multicompany
	if (! empty($out) && ! empty($conf->multicompany->enabled)) $out.="&entity=".$conf->entity; // Check the entity because we may have the same reference in several entities

	return $out;
}



/**
 * Show footer of company in HTML pages
 *
 * @param   Societe		$fromcompany	Third party
 * @param   Translate	$langs			Output language
 * @param	int			$addformmessage	Add the payment form message
 * @param	string		$suffix			Suffix to use on constants
 * @param	Object		$object			Object related to payment
 * @return	void
 */
function htmlPrintOnlinePaymentFooter($fromcompany, $langs, $addformmessage=0, $suffix='', $object=null)
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

    print '<br>';

    print '<div class="center">'."\n";
    if ($addformmessage)
    {
    	print '<!-- object = '.$object->element.' -->';
    	print '<br>';

    	$parammessageform='ONLINE_PAYMENT_MESSAGE_FORM_'.$suffix;
    	if (! empty($conf->global->$parammessageform)) print $langs->transnoentities($conf->global->$parammessageform);
    	else if (! empty($conf->global->ONLINE_PAYMENT_MESSAGE_FORM)) print $langs->transnoentities($conf->global->ONLINE_PAYMENT_MESSAGE_FORM);

    	// Add other message if VAT exists
    	if ($object->total_vat != 0 || $object->total_tva != 0)
    	{
    		$parammessageform='ONLINE_PAYMENT_MESSAGE_FORMIFVAT_'.$suffix;
    		if (! empty($conf->global->$parammessageform)) print $langs->transnoentities($conf->global->$parammessageform);
    		else if (! empty($conf->global->ONLINE_PAYMENT_MESSAGE_FORMIFVAT)) print $langs->transnoentities($conf->global->ONLINE_PAYMENT_MESSAGE_FORMIFVAT);
    	}
    }

    print '<font style="font-size: 10px;"><br><hr>'."\n";
    print $fromcompany->name.'<br>';
    print $line1;
    if (strlen($line1+$line2) > 50) print '<br>';
    else print ' - ';
    print $line2;
    print '</font></div>'."\n";
}
