#!/usr/bin/php
<?php
/*
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

if (! isset($argv[1]) || ! $argv[1]) {
	print "Usage: $script_file now\n";
	exit;
}


require($path."../../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php");


$error = 0;

$sql = "SELECT f.facnumber, f.total_ttc, s.nom, u.name, u.firstname, u.email";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " , ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE f.paye = 0";
$sql .= " AND f.fk_soc = s.rowid";
$sql .= " AND sc.fk_soc = s.rowid";
$sql .= " AND sc.fk_user = u.rowid";
$sql .= " ORDER BY u.email ASC, s.rowid ASC";

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    $oldemail = '';
    $message = '';
    $total = '';
    dol_syslog("email_unpaid_invoices_to_representatives.php");

    if ($num)
    {
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);

            if ($obj->email <> $oldemail)
            {
                if (dol_strlen($oldemail))
                {
                    envoi_mail($oldemail,$message,$total);
                }
                $oldemail = $obj->email;
                $message = '';
                $total = 0;
            }

            $message .= "Facture ".$obj->facnumber." : ".price($obj->total_ttc)." : ".$obj->nom."\n";
            $total += $obj->total_ttc;

            dol_syslog("email_unpaid_invoices_to_representatives.php: ".$obj->email);
            $i++;
        }

        // Si il reste des envois en buffer
        if ($total)
        {
            envoi_mail($oldemail,$message,$total);
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
 * 	@param	string	$oldemail	Old email
 * 	@param	string	$message	Message to send
 * 	@param	string	$total		Total amount of unpayed invoices
 * 	@return	int					<0 if KO, >0 if OK
 */
function envoi_mail($oldemail,$message,$total)
{
    global $conf,$langs;

    $subject = "[Dolibarr] List of unpaid invoices";
    $sendto = $oldemail;
    $from = $conf->global->MAIN_EMAIL_FROM;
	$msgishtml = 0;

    print "Envoi mail pour $oldemail, total: $total\n";
    dol_syslog("email_unpaid_invoices_to_representatives.php: send mail to $oldemail");

    $allmessage = "List of unpaid invoices\n";
    $allmessage .= "This list contains only invoices for third parties you are linked to as a sales representative.\n";
    $allmessage .= "\n";
    $allmessage .= $message;
    $allmessage .= "\n";
    $allmessage .= $langs->trans("Total")." = ".price($total)."\n";

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

    $result=$mail->sendfile();
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
