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
 *      \file       scripts/invoices/email_unpaid_invoices_to_representatives.php
 *      \ingroup    facture
 *      \brief      Script to send a mail to dolibarr users linked to companies with unpaid invoices
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

if (! isset($argv[1]) || ! $argv[1] || ! in_array($argv[1],array('test','confirm')))
{
	print "Usage: $script_file [test|confirm] [delay]\n";
	print "\n";
	print "Send an email to users to remind all unpaid invoices of customers they are sale representative for.\n";
	print "If you choose 'test' mode, no emails are sent.\n";
	print "If you add a delay (nb of days), only invoice with due date < today + delay are included.\n";
	exit;
}
$mode=$argv[1];


require($path."../../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php");



/*
 * Main
 */

$now=dol_now('tzserver');
$duration_value=$argv[2];

$error = 0;
print $script_file." launched with mode ".$mode.($duration_value?" delay=".$duration_value:"")."\n";

$sql = "SELECT f.facnumber, f.total_ttc, s.nom as name, u.rowid as uid, u.lastname, u.firstname, u.email, u.lang";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " , ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE f.fk_statut != 0 AND f.paye = 0";
$sql .= " AND f.fk_soc = s.rowid";
if ($duration_value) $sql .= " AND f.date_lim_reglement < '".$db->idate(dol_time_plus_duree($now, $duration_value, "d"))."'";
$sql .= " AND sc.fk_soc = s.rowid";
$sql .= " AND sc.fk_user = u.rowid";
$sql .= " ORDER BY u.email ASC, s.rowid ASC";	// Order by email to allow one message per email

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    $oldemail = 'none'; $olduid = 0; $oldlang='';
    $total = 0;
	print "We found ".$num." couples (unpayed validated invoice/sale representative) qualified\n";
    dol_syslog("We found ".$num." couples (unpayed validated invoice/sale representative) qualified");
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
                   	envoi_mail($mode,$oldemail,$message,$total,$oldlang);
                }
                $oldemail = $obj->email;
                $olduid = $obj->uid;
                $oldlang = $obj->lang;
                $message = '';
                $total = 0;
                if (empty($obj->email)) print "Warning: Sal representative ".dolGetFirstLastname($obj->firstname, $obj->lastname)." has no email. Notice disabled.\n";
            }

            if (dol_strlen($oldemail))
            {
            	$message .= $langs->trans("Invoice")." ".$obj->facnumber." : ".price($obj->total_ttc)." : ".$obj->name."\n";
				print "Invoice ".$obj->facnumber.", price ".price2num($obj->total_ttc).", linked to company ".$obj->name." with sale representative ".dolGetFirstLastname($obj->firstname, $obj->lastname)." qualified.\n";
            	dol_syslog("email_unpaid_invoices_to_representatives.php: ".$obj->email);
            }

            $total += $obj->total_ttc;
            $i++;
        }

        // Si il reste des envois en buffer
        if ($total)
        {
            if (dol_strlen($oldemail) && $oldemail != 'none')	// Break onto email (new email)
            {
       			envoi_mail($mode,$oldemail,$message,$total,$oldlang);
            }
        }
    }
    else
    {
        print "No unpaid invoices to companies linked to a particular commercial dolibarr user\n";
    }
}
else
{
    dol_print_error($db);
    dol_syslog("email_unpaid_invoices_to_representatives.php: Error");
}


/**
 * 	Send email
 *
 * 	@param	string	$mode		Mode (test | confirm)
 *  @param	string	$oldemail	Old email
 * 	@param	string	$message	Message to send
 * 	@param	string	$total		Total amount of unpayed invoices
 *  @param	string	$userlang	Code lang to use for email output.
 * 	@return	int					<0 if KO, >0 if OK
 */
function envoi_mail($mode,$oldemail,$message,$total,$userlang)
{
    global $conf,$langs;

    $newlangs=new Translate('',$conf);
    $newlangs->setDefaultLang($userlang);
    $newlangs->load("main");
    $newlangs->load("bills");

    $subject = "[".(empty($conf->global->MAIN_APPLICATION_TITLE)?'Dolibarr':$conf->global->MAIN_APPLICATION_TITLE)."] ".$newlangs->trans("ListOfYourUnpaidInvoices");
    $sendto = $oldemail;
    $from = $conf->global->MAIN_EMAIL_FROM;
    $errorsto = $conf->global->MAIN_MAIL_ERRORS_TO;
	$msgishtml = 0;

    print "Send email for ".$oldemail.", total: ".$total."\n";
    dol_syslog("email_unpaid_invoices_to_representatives.php: send mail to ".$oldemail);

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
    	$allmessage.= "List of unpaid invoices\n\n";
    	$allmessage.= "This list contains only invoices for third parties you are linked to as a sales representative.\n";
    	$allmessage.= "\n";
    }
    $allmessage.= $message.($usehtml?"<br>\n":"\n");
    $allmessage.= $langs->trans("Total")." = ".price($total).($usehtml?"<br>\n":"\n");
    if (! empty($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_FOOTER))
    {
    	$allmessage.=$conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_FOOTER;
    	if (dol_textishtml($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_FOOTER)) $usehtml+=1;
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
