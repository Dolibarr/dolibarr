#!/usr/bin/php
<?php
/*
 * Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       scripts/bank/export-bank-receipts.php
 *      \ingroup    bank
 *      \brief      Script file to export bank receipts into Excel files
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

if (! isset($argv[2]) || ! $argv[2]) {
	print "Usage: $script_file bank_receipt_number (csv|tsv|excel|excel2007)\n";
	exit;
}
$num=$argv[1];
$model=$argv[2];

// Recupere env dolibarr
$version='1.10';

require_once($path."../../htdocs/master.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';



$societestatic=new Societe($db);
$chargestatic=new ChargeSociales($db);
$memberstatic=new Adherent($db);
$paymentstatic=new Paiement($db);
$paymentsupplierstatic=new PaiementFourn($db);
$paymentvatstatic=new TVA($db);
$bankstatic=new Account($db);
$banklinestatic=new AccountLine($db);


$dirname = $conf->banque->dir_temp;
$filename = 'export-bank-receipts-'.$num.'.xls';


// Creation de la classe d'export du model ExportXXX
$dir = DOL_DOCUMENT_ROOT . "/core/modules/export/";
$file = "export_".$model.".modules.php";
$classname = "Export".$model;
if (! dol_is_file($dir.$file))
{
	print "No driver to export with format ".$model."\n";
	exit;
}
require_once $dir.$file;
$objmodel = new $classname($db);


// Open file
print 'Create file '.$filename.' into directory '.$dirname."\n";
dol_mkdir($dirname);
$result=$objmodel->open_file($dirname."/".$filename, $langs);

if ($result >= 0)
{
	// Genere en-tete
	$objmodel->write_header($outputlangs);

	// Genere ligne de titre
	$objmodel->write_title($this->array_export_fields[$indice],$array_selected,$outputlangs);


	// Recherche les ecritures pour le releve
	$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
	$sql.= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type,";
	$sql.= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.num_releve='".$db->escape($num)."'";
	if (!isset($num))	$sql.= " OR b.num_releve is null";
	$sql.= " AND b.fk_account = ".$acct->id;
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= $db->order("b.datev, b.datec", "ASC");  // We add date of creation to have correct order when everything is done the same day

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print "Lines ".$num."\n";

		while ($i < $numrows)
		{
			$objp = $db->fetch_object($result);
			$total = $total + $objp->amount;

			$var=!$var;
			print "<tr $bc[$var]>";

			// Date operation
			print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->do),"day").'</td>';

			// Date de valeur
			print '<td align="center" valign="center" class="nowrap">';
			print '<a href="releve.php?action=dvprev&amp;num='.$num.'&amp;account='.$acct->id.'&amp;dvid='.$objp->rowid.'">';
			print img_previous().'</a> ';
			print dol_print_date($db->jdate($objp->dv),"day") .' ';
			print '<a href="releve.php?action=dvnext&amp;num='.$num.'&amp;account='.$acct->id.'&amp;dvid='.$objp->rowid.'">';
			print img_next().'</a>';
			print "</td>\n";

			// Num cheque
			print '<td class="nowrap">'.$objp->fk_type.' '.($objp->num_chq?$objp->num_chq:'').'</td>';

			// Libelle
			print '<td valign="center"><a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
			$reg=array();
			preg_match('/\((.+)\)/i',$objp->label,$reg);	// Si texte entoure de parenthese on tente recherche de traduction
			if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) print $langs->trans($reg[1]);
			else print $objp->label;
			print '</a>';

			/*
			 * Ajout les liens (societe, company...)
			*/
			$newline=1;
			$links = $acct->get_url($objp->rowid);
			foreach($links as $key=>$val)
			{
				if (! $newline) print ' - ';
				else print '<br>';
				if ($links[$key]['type']=='payment')
				{
					$paymentstatic->id=$links[$key]['url_id'];
					$paymentstatic->ref=$langs->trans("Payment");
					print ' '.$paymentstatic->getNomUrl(1);
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_supplier')
				{
					$paymentsupplierstatic->id=$links[$key]['url_id'];
					$paymentsupplierstatic->ref=$langs->trans("Payment");;
					print ' '.$paymentsupplierstatic->getNomUrl(1);
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_sc')
				{
					print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id='.$links[$key]['url_id'].'">';
					print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
					print $langs->trans("SocialContributionPayment");
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_vat')
				{
					$paymentvatstatic->id=$links[$key]['url_id'];
					$paymentvatstatic->ref=$langs->trans("Payment");
					print ' '.$paymentvatstatic->getNomUrl(2);
				}
				elseif ($links[$key]['type']=='banktransfert') {
					// Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
					if ($objp->amount > 0)
					{
						$banklinestatic->fetch($links[$key]['url_id']);
						$bankstatic->id=$banklinestatic->fk_account;
						$bankstatic->label=$banklinestatic->bank_account_label;
						print ' ('.$langs->trans("from").' ';
						print $bankstatic->getNomUrl(1,'transactions');
						print ' '.$langs->trans("toward").' ';
						$bankstatic->id=$objp->bankid;
						$bankstatic->label=$objp->bankref;
						print $bankstatic->getNomUrl(1,'');
						print ')';
					}
					else
					{
						$bankstatic->id=$objp->bankid;
						$bankstatic->label=$objp->bankref;
						print ' ('.$langs->trans("from").' ';
						print $bankstatic->getNomUrl(1,'');
						print ' '.$langs->trans("toward").' ';
						$banklinestatic->fetch($links[$key]['url_id']);
						$bankstatic->id=$banklinestatic->fk_account;
						$bankstatic->label=$banklinestatic->bank_account_label;
						print $bankstatic->getNomUrl(1,'transactions');
						print ')';
					}
				}
				elseif ($links[$key]['type']=='company') {
					print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowCustomer'),'company').' ';
					print dol_trunc($links[$key]['label'],24);
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='member') {
					print '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowMember'),'user').' ';
					print $links[$key]['label'];
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='sc') {
					print '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowBill'),'bill').' ';
					print $langs->trans("SocialContribution");
					print '</a>';
					$newline=0;
				}
				else {
					print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
					print $links[$key]['label'];
					print '</a>';
					$newline=0;
				}
			}


			if ($objp->amount < 0)
			{
				$totald = $totald + abs($objp->amount);
				print '<td align="right" nowrap=\"nowrap\">'.price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
			}
			else
			{
				$totalc = $totalc + abs($objp->amount);
				print "<td>&nbsp;</td><td align=\"right\" nowrap=\"nowrap\">".price($objp->amount)."</td>\n";
			}

			print "<td align=\"right\" nowrap=\"nowrap\">".price($total)."</td>\n";

			if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
			{
				print "<td align=\"center\"><a href=\"ligne.php?rowid=$objp->rowid&amp;account=".$acct->id."\">";
				print img_edit();
				print "</a></td>";
			}
			else
			{
			print "<td align=\"center\">&nbsp;</td>";
			}
			print "</tr>";
			$i++;

			// end of special operation processing
			$objmodel->write_record($array_selected,$objp,$outputlangs,$this->array_export_TypeFields[$indice]);
		}

	}


	// Genere en-tete
	$objmodel->write_footer($outputlangs);

	// Close file
	$objmodel->close_file();

	print 'File '.$filename.' was generated into dir '.$dirname.'.'."\n";

	$ret=0;
}
else
{
	print 'Failed to create file '.$filename.' into dir '.$dirname.'.'."\n";

	$ret=-1;
}



$db->close();

return $ret;
?>