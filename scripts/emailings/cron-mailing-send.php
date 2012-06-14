#!/usr/bin/php
<?php
/*
 * Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis@dolibarr.fr>
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
 *      \file       scripts/emailings/cron-mailing-send.php
 *      \ingroup    mailing
 *      \brief      Script d'envoi d'un mailing prepare et valide
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

require_once ($path."../../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php");


$error = 0;


// We read data of email
$sql = "SELECT m.rowid, m.titre, m.sujet, m.body,";
$sql.= " m.email_from, m.email_replyto, m.email_errorsto";
$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql.= " WHERE m.statut IN (1,2)";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	if ($num)
	{
		for ($i=0;$i<$num;$i++)
		{
			$obj = $db->fetch_object($resql);
			
			dol_syslog("mailing ".$obj->rowid);
			
			$id       = $obj->rowid;
			$subject  = $obj->sujet;
			$message  = $obj->body;
			$from     = $obj->email_from;
			$replyto  = $obj->email_replyto;
			$errorsto = $obj->email_errorsto;
			// Le message est-il en html
			$msgishtml=-1;  // Unknown by default
			if (preg_match('/[\s\t]*<html>/i',$message)) $msgishtml=1;
			
			// Set statut 9 (in progress) to avoid duplication
			$sql="UPDATE ".MAIN_DB_PREFIX."mailing";
			$sql.=" SET statut=9 WHERE rowid=".$id;
			$resql3=$db->query($sql);
			if (! $resql3)
			{
				dol_print_error($db);
			}
			
			$nbok=0; $nbko=0;
			
			// On choisit les mails non deja envoyes pour ce mailing (statut=0)
			// ou envoyes en erreur (statut=-1)
			$sql = "SELECT mc.rowid, mc.nom as lastname, mc.prenom as firstname, mc.email, mc.other, mc.source_url, mc.source_id, mc.source_type, mc.tag";
			$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
			$sql .= " WHERE mc.statut < 1 AND mc.fk_mailing = ".$id;
			
			$resql=$db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
			
				if ($num)
				{
					dol_syslog("nb of targets = ".$num, LOG_DEBUG);
			
					$now=dol_now();
			
					// Positionne date debut envoi
					$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET date_envoi='".$db->idate($now)."' WHERE rowid=".$id;
			
					$resql2=$db->query($sql);
					if (! $resql2)
					{
						dol_print_error($db);
					}
			
					// Look on each email and sent message
					$i = 0;
					while ($i < $num)
					{
						$res=1;
						$now=dol_now();
			
						$obj2 = $db->fetch_object($resql);
			
						// sendto en RFC2822
						$sendto = str_replace(',',' ',$obj2->firstname." ".$obj2->lastname) ." <".$obj2->email.">";
			
						// Make subtsitutions on topic and body
						$other=explode(';',$obj2->other);
						$other1=$other[0];
						$other2=$other[1];
						$other3=$other[2];
						$other4=$other[3];
						$other5=$other[4];
						$substitutionarray=array(
								'__ID__' => $obj2->source_id,
								'__EMAIL__' => $obj2->email,
								'__CHECK_READ__' => '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$obj2->tag.'" width="1" height="1" style="width:1px;height:1px" border="0"/>',
								'__UNSUSCRIBE__' => '<a href="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-unsubscribe.php?tag='.$obj2->tag.'&unsuscrib=1" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>',
								'__MAILTOEMAIL__' => '<a href="mailto:'.$obj2->email.'">'.$obj2->email.'</a>',
								'__LASTNAME__' => $obj2->lastname,
								'__FIRSTNAME__' => $obj2->firstname,
								'__OTHER1__' => $other1,
								'__OTHER2__' => $other2,
								'__OTHER3__' => $other3,
								'__OTHER4__' => $other4,
								'__OTHER5__' => $other5
						);
			
						complete_substitutions_array($substitutionarray,$langs);
						$newsubject=make_substitutions($subject,$substitutionarray);
						$newmessage=make_substitutions($message,$substitutionarray);
			
						$substitutionisok=true;
			
						// Fabrication du mail
						$mail = new CMailFile(
								$newsubject,
								$sendto,
								$from,
								$newmessage,
								array(),
								array(),
								array(),
								'',
								'',
								0,
								$msgishtml,
								$errorsto
						);
			
						if ($mail->error)
						{
							$res=0;
						}
						if (! $substitutionisok)
						{
							$mail->error='Some substitution failed';
							$res=0;
						}
			
						// Send Email
						if ($res)
						{
							$res=$mail->sendfile();
						}
			
						if ($res)
						{
							// Mail successful
							$nbok++;
			
							dol_syslog("ok for #".$i.($mail->error?' - '.$mail->error:''), LOG_DEBUG);
			
							$sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
							$sql.=" SET statut=1, date_envoi=".$db->idate($now)." WHERE rowid=".$obj2->rowid;
							$resql2=$db->query($sql);
							if (! $resql2)
							{
								dol_print_error($db);
							}
							else
							{
								//if cheack read is use then update prospect contact status
								if (strpos($message, '__CHECK_READ__') !== false && ! empty($obj2->source_id))
								{
									//Update status communication of thirdparty prospect
									$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=2 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid=".$obj2->rowid.")";
									dol_syslog("fiche.php: set prospect thirdparty status sql=".$sql, LOG_DEBUG);
									$resql2=$db->query($sql);
									if (! $resql2)
									{
										dol_print_error($db);
									}
			
									//Update status communication of contact prospect
									$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=2 WHERE rowid IN (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."socpeople AS sc INNER JOIN ".MAIN_DB_PREFIX."mailing_cibles AS mc ON mc.rowid=".$obj2->rowid." AND mc.source_type = 'contact' AND mc.source_id = sc.rowid)";
									dol_syslog("fiche.php: set prospect contact status sql=".$sql, LOG_DEBUG);
			
									$resql2=$db->query($sql);
									if (! $resql2)
									{
										dol_print_error($db);
									}
								}
							}
						}
						else
						{
							// Mail failed
							$nbko++;
			
							dol_syslog("error for #".$i.($mail->error?' - '.$mail->error:''), LOG_DEBUG);
			
							$sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
							$sql.=" SET statut=-1, date_envoi=".$db->idate($now)." WHERE rowid=".$obj2->rowid;
							$resql2=$db->query($sql);
							if (! $resql2)
							{
								dol_print_error($db);
							}
						}
			
						$i++;
					}
				}
			
				// Loop finished, set global statut of mail
				$statut=2;
				if (! $nbko) $statut=3;
			
				$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$statut." WHERE rowid=".$id;
				dol_syslog("update global status sql=".$sql, LOG_DEBUG);
				$resql2=$db->query($sql);
				if (! $resql2)
				{
					dol_print_error($db);
				}
			}
			else
			{
				dol_print_error($db);
			}
		}
	}
}

?>
