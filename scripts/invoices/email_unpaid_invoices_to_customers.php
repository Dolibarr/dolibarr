#!/usr/bin/php
<?php
/*
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       scripts/invoices/email_unpaid_invoices_to_customers.php
 *      \ingroup    facture
 *      \brief      Script to send a mail to customers with unpaid invoices
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

if (! isset($argv[1]) || ! $argv[1] || ! in_array($argv[1],array('test','confirm')))
{
	print "Usage: $script_file [test|confirm] [delay]\n";
	print "\n";
	print "Send an email to customers to remind all unpaid customer invoices.\n";
	print "If you choose 'test' mode, no emails are sent.\n";
	print "If you add a delay (nb of days), only invoice with due date < today + delay are included.\n";
	exit;
}
$mode=$argv[1];


require($path."../../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php");

$langs->load('main');


/*
 * Main
 */

$now=dol_now('tzserver');
$duration_value=isset($argv[2])?$argv[2]:-1;

$error = 0;
print $script_file." launched with mode ".$mode.($duration_value>=0?" delay=".$duration_value:"")."\n";

$sql = "SELECT f.facnumber, f.total_ttc, f.date_lim_reglement as due_date, s.nom as name, s.email, s.default_lang";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql.= " , ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE f.fk_statut = 1 AND f.paye = 0";
$sql.= " AND f.fk_soc = s.rowid";
if ($duration_value>=0) $sql.= " AND f.date_lim_reglement < '".$db->idate(dol_time_plus_duree($now, $duration_value, "d"))."'";
$sql.= " ORDER BY s.email ASC, s.rowid ASC";	// Order by email to allow one message per email

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    $oldemail = 'none'; $olduid = 0; $oldlang='';
    $total = 0; $foundtoprocess = 0;
	print "We found ".$num." couples (unpayed validated invoice - customer) qualified\n";
    dol_syslog("We found ".$num." couples (unpayed validated invoice - customer) qualified");
	$message='';

    if ($num)
    {
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);

            if (($obj->email <> $oldemail || $obj->uid <> $olduid) || $oldemail == 'none')
            {
                // Break onto sales representative (new email or uid)
                if (dol_strlen($oldemail) && $oldemail != 'none')
                {
                   	envoi_mail($mode,$oldemail,$message,$total,$oldlang,$oldcustomer);
                }
                else
				{
                	if ($oldemail != 'none') print "- No email sent for ".$oldcustomer.", total: ".$total."\n";
                }
                $oldemail = $obj->email;
                $olduid = $obj->uid;
                $oldlang = $obj->lang;
                $oldcustomer=$obj->name;
                $message = '';
                $total = 0;
                $foundtoprocess = 0;
                $customer=$obj->name;
                if (empty($obj->email)) print "Warning: Customer ".$customer." has no email. Notice disabled.\n";
            }

            if (dol_strlen($oldemail))
            {
            	$message .= $langs->trans("Invoice")." ".$obj->facnumber." : ".price($obj->total_ttc)." : ".$obj->name."\n";
            	dol_syslog("email_unpaid_invoices_to_customers.php: ".$obj->email);
            	$foundtoprocess++;
            }
            print "Unpaid invoice ".$obj->facnumber.", price ".price2num($obj->total_ttc).", due date ".dol_print_date($db->jdate($obj->due_date),'day')." customer ".$obj->name.", email ".$obj->email.": ";
            if (dol_strlen($obj->email)) print "qualified.";
            else print "disqualified (no email).";
            print "\n";

            $total += $obj->total_ttc;

            $i++;
        }

        // Si il reste des envois en buffer
        if ($foundtoprocess)
        {
            if (dol_strlen($oldemail) && $oldemail != 'none')	// Break onto email (new email)
            {
       			envoi_mail($mode,$oldemail,$message,$total,$oldlang,$oldcustomer);
            }
            else
			{
            	if ($oldemail != 'none') print "- No email sent for ".$oldcustomer.", total: ".$total."\n";
            }
        }
    }
    else
    {
        print "No unpaid invoices found\n";
    }
}
else
{
    dol_print_error($db);
    dol_syslog("email_unpaid_invoices_to_customers.php: Error");
}


/**
 * 	Send email
 *
 * 	@param	string	$mode			Mode (test | confirm)
 *  @param	string	$oldemail		Old email
 * 	@param	string	$message		Message to send
 * 	@param	string	$total			Total amount of unpayed invoices
 *  @param	string	$userlang		Code lang to use for email output.
 *  @param	string	$oldcustomer	Old customer
 * 	@return	int						<0 if KO, >0 if OK
 */
function envoi_mail($mode,$oldemail,$message,$total,$userlang,$oldcustomer)
{
    global $conf,$langs;

    $newlangs=new Translate('',$conf);
    $newlangs->setDefaultLang($userlang);
    $newlangs->load("main");
    $newlangs->load("bills");

    $subject = "[".(empty($conf->global->MAIN_APPLICATION_TITLE)?'Dolibarr':$conf->global->MAIN_APPLICATION_TITLE)."] ".$newlangs->trans("ListOfYourUnpaidInvoices");
    $sendto = $oldemail;
    $from = $conf->global->MAIN_MAIL_EMAIL_FROM;
    $errorsto = $conf->global->MAIN_MAIL_ERRORS_TO;
	$msgishtml = -1;

    print "- Send email for ".$oldcustomer."(".$oldemail."), total: ".$total."\n";
    dol_syslog("email_unpaid_invoices_to_customers.php: send mail to ".$oldemail);

    $usehtml=0;
    if (dol_textishtml($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_FOOTER)) $usehtml+=1;
    if (dol_textishtml($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_HEADER)) $usehtml+=1;

    $allmessage='';
    if (! empty($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_HEADER))
    {
    	$allmessage.=$conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_HEADER;
    }
    else
    {
    	$allmessage.= "Dear customer".($usehtml?"<br>\n":"\n").($usehtml?"<br>\n":"\n");
    	$allmessage.= "Please, find a summary of the bills with pending payments from you, with an attachment of invoices.".($usehtml?"<br>\n":"\n").($usehtml?"<br>\n":"\n");
    	$allmessage.= "Note: This list contains only unpaid invoices.".($usehtml?"<br>\n":"\n");
    }
    $allmessage.= $message.($usehtml?"<br>\n":"\n");
    $allmessage.= $langs->trans("Total")." = ".price($total).($usehtml?"<br>\n":"\n");
    if (! empty($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_CUSTOMERS_FOOTER))
    {
    	$allmessage.=$conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_CUSTOMERS_FOOTER;
    	if (dol_textishtml($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_CUSTOMERS_FOOTER)) $usehtml+=1;
    }

    $mail = new CMailFile(
        $subject,
        $sendto,
        $from,
        $allmessage,
        array(),
        array(),
        array(),
        '',
        '',
        0,
        $msgishtml
    );

    $mail->errors_to = $errorsto;

    // Send or not email
    if ($mode == 'confirm')
    {
    	$result=$mail->sendfile();
    }
    else
    {
    	print "No email sent (test mode)\n";
    	dol_syslog("No email sent (test mode)");
    	$mail->dump_mail();
    	$result=1;
    }

    if ($result)
    {
        return 1;
    }
    else
    {
        return -1;
    }
}


?>
