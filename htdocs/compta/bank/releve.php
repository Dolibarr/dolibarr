<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2017      Patrick Delcroix	<pmpdelcroix@gmail.com>
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
 *	    \file       htdocs/compta/bank/releve.php
 *      \ingroup    banque
 *		\brief      Page to show a bank statement report
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
//show files
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("companies");
$langs->load("bills");

$action=GETPOST('action', 'alpha');
$id=GETPOST('account');
$ref=GETPOST('ref');
$dvid=GETPOST('dvid');
$num=GETPOST('num');

// Security check
$fieldid = (! empty($ref)?$ref:$id);
$fieldname = isset($ref)?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$fieldid,'bank_account','','',$fieldname);

if ($user->rights->banque->consolidate && $action == 'dvnext' && ! empty($dvid))
{
	$al = new AccountLine($db);
	$al->datev_next($dvid);
}

if ($user->rights->banque->consolidate && $action == 'dvprev' && ! empty($dvid))
{
	$al = new AccountLine($db);
	$al->datev_previous($dvid);
}


$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$pageplusone = GETPOST("pageplusone",'int');
if ($pageplusone) $page = $pageplusone - 1;
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$object = new Account($db);
if ($id > 0 || ! empty($ref))
{
    $result=$object->fetch($id, $ref);
    $account = $object->id;     // Force the search field on id of account
}


// Initialize technical object to manage context to save list fields
$contextpage='banktransactionlist'.(empty($object->ref)?'':'-'.$object->id);
/*
*ZIP creation
*/
if($action=="dl" && $num>0 && isset($_SESSION["releve"][$num])){
        unset($zip);
    $log='';
        $filearray=$_SESSION["releve"][$num];
        $zipname = $num.'.zip';
        $zip = new ZipArchive;
        $zip->open($zipname, ZipArchive::OVERWRITE);
        foreach ($filearray as $key=> $files) {
            if(is_array($files)){
                foreach ($files as $file) {
                    $zip->addFile($file["fullname"],$file["name"]);//
                    $log.=$key.','.$file["name"]."\n";
                }
            }else{
                $log.=$key.','.$langs->trans("Nofile")."\n";
            }
        }
        $zip->addFromString('log.csv', $log);
        $zip->close();

        ///Then download the zipped file.
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.$zipname);
        header('Content-Length: ' . filesize($zipname));
        readfile($zipname);
}
unset($_SESSION["releve"][$num]);

/*
 * View
 */

$title = $langs->trans("FinancialAccount").' - '.$langs->trans("AccountStatements");
$helpurl = "";
llxHeader('',$title,$helpurl);

$form = new Form($db);
$societestatic=new Societe($db);
$chargestatic=new ChargeSociales($db);
$memberstatic=new Adherent($db);
$paymentstatic=new Paiement($db);
$paymentsupplierstatic=new PaiementFourn($db);
$paymentvatstatic=new TVA($db);
$bankstatic=new Account($db);
$banklinestatic=new AccountLine($db);
$remisestatic = new RemiseCheque($db);

// Must be before button action
$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($id > 0) $param.='&id='.urlencode($id);


if (empty($num))
{
	/*
	 *	Vue liste tous releves confondus
	 */
	$sql = "SELECT DISTINCT(b.num_releve) as numr";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.fk_account = ".$object->id;
	$sql.= " ORDER BY numr DESC";

	$sql.= $db->plimit($conf->liste_limit+1,$offset);

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$numrows = $db->num_rows($result);
		$i = 0;

		// Onglets
		$head=bank_prepare_head($object);
		dol_fiche_head($head,'statement',$langs->trans("FinancialAccount"),0,'account');

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php">'.$langs->trans("BackToList").'</a>';
		
		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);
		
		dol_fiche_end();

		
		print '<div class="tabsAction">';

		if ($object->canBeConciliated() > 0) {
			// If not cash account and can be reconciliate
			if ($user->rights->banque->consolidate) {
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/bank/bankentries.php?action=reconcile'.$param.'">'.$langs->trans("Conciliate").'</a>';
			} else {
				print '<a class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("Conciliate").'</a>';
			}
		}

		print '</div>';
		print '<br><br>';
		

		print_barre_liste('', $page, $_SERVER["PHP_SELF"], "&account=".$object->id, $sortfield, $sortorder,'',$numrows);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("AccountStatement").'</td>';
		print '<td align="right">'.$langs->trans("InitialBankBalance").'</td>';
		print '<td align="right">'.$langs->trans("EndBankBalance").'</td>';
		print '</tr>';

		$balancestart=array();
		$content=array();

		while ($i < min($numrows,$conf->liste_limit))
		{
			$objp = $db->fetch_object($result);
			$var=!$var;
			if (! isset($objp->numr))
			{
				//
			}
			else
			{
				print '<tr '.$bc[$var].'><td><a href="releve.php?num='.$objp->numr.'&amp;account='.$object->id.'">'.$objp->numr.'</a></td>';

				// Calculate start amount
				$sql = "SELECT sum(b.amount) as amount";
				$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
				$sql.= " WHERE b.num_releve < '".$db->escape($objp->numr)."'";
				$sql.= " AND b.fk_account = ".$object->id;
				$resql=$db->query($sql);
				if ($resql)
				{
					$obj=$db->fetch_object($resql);
					$balancestart[$objp->numr] = $obj->amount;
					$db->free($resql);
				}
				print '<td align="right">'.price($balancestart[$objp->numr],'',$langs,1,-1,-1,$conf->currency).'</td>';

				// Calculate end amount
				$sql = "SELECT sum(b.amount) as amount";
				$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
				$sql.= " WHERE b.num_releve = '".$db->escape($objp->numr)."'";
				$sql.= " AND b.fk_account = ".$object->id;
				$resql=$db->query($sql);
				if ($resql)
				{
					$obj=$db->fetch_object($resql);
					$content[$objp->numr] = $obj->amount;
					$db->free($resql);
				}
				print '<td align="right">'.price(($balancestart[$objp->numr]+$content[$objp->numr]),'',$langs,1,-1,-1,$conf->currency).'</td>';

				print '</tr>'."\n";
			}
			$i++;
		}
		print "</table>\n";

		print "\n</div>\n";
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	/**
	 *   Show list of bank statements
	 */
	$ve=$_GET["ve"];

	// Define number of receipt to show (current, previous or next one ?)
	$found=false;
	if ($_GET["rel"] == 'prev')
	{
		// Recherche valeur pour num = numero releve precedent
		$sql = "SELECT DISTINCT(b.num_releve) as num";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= " WHERE b.num_releve < '".$db->escape($num)."'";
		$sql.= " AND b.fk_account = ".$object->id;
		$sql.= " ORDER BY b.num_releve DESC";

		dol_syslog("htdocs/compta/bank/releve.php", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$numrows = $db->num_rows($resql);
			if ($numrows > 0)
			{
				$obj = $db->fetch_object($resql);
				$num = $obj->num;
				$found=true;
			}
		}
	}
	elseif ($_GET["rel"] == 'next')
	{
		// Recherche valeur pour num = numero releve precedent
		$sql = "SELECT DISTINCT(b.num_releve) as num";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= " WHERE b.num_releve > '".$db->escape($num)."'";
		$sql.= " AND b.fk_account = ".$object->id;
		$sql.= " ORDER BY b.num_releve ASC";

		dol_syslog("htdocs/compta/bank/releve.php", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$numrows = $db->num_rows($resql);
			if ($numrows > 0)
			{
				$obj = $db->fetch_object($resql);
				$num = $obj->num;
				$found=true;
			}
		}
	}
	else {
		// On veut le releve num
		$found=true;
	}

    $mesprevnext='';
	$mesprevnext.='<div class="pagination"><ul>';
	$mesprevnext.='<li class="pagination"><a data-role="button" data-icon="arrow-l" data-iconpos="left" href="'.$_SERVER["PHP_SELF"].'?rel=prev&amp;num='.$num.'&amp;ve='.$ve.'&amp;account='.$object->id.'"><</a></li>';
	//$mesprevnext.=' &nbsp; ';
	$mesprevnext.='<li class="pagination"><span class="active">'.$langs->trans("AccountStatement")." ".$num.'</span></li>';
	//$mesprevnext.=' &nbsp; ';
    $mesprevnext.='<li class="pagination"><a data-role="button" data-icon="arrow-r" data-iconpos="right" href="'.$_SERVER["PHP_SELF"].'?rel=next&amp;num='.$num.'&amp;ve='.$ve.'&amp;account='.$object->id.'">></a></li>';
    $mesprevnext.='</ul></div>';
	print load_fiche_titre($langs->trans("AccountStatement").' '.$num.', '.$langs->trans("BankAccount").' : '.$object->getNomUrl(0, 'receipts'), $mesprevnext, 'title_bank.png');
	print '<br>';

	print "<form method=\"post\" action=\"releve.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"add\">";

    print '<div class="div-table-responsive">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td align="center">'.$langs->trans("DateOperationShort").'</td>';
	print '<td align="center">'.$langs->trans("DateValueShort").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td align="right" width="60">'.$langs->trans("Debit").'</td>';
	print '<td align="right" width="60">'.$langs->trans("Credit").'</td>';
	print '<td align="right">'.$langs->trans("Balance").'</td>';
	print '<td>&nbsp;</td>';
	print "</tr>\n";

	// Calcul du solde de depart du releve
	$sql = "SELECT sum(b.amount) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.num_releve < '".$db->escape($num)."'";
	$sql.= " AND b.fk_account = ".$object->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$total = $obj->amount;
		$db->free($resql);
	}

	// Recherche les ecritures pour le releve
	$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
	$sql.= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type,";
	$sql.= " b.fk_bordereau,";
    $sql.= " bc.ref,";
	$sql.= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."bank as b";
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bordereau_cheque as bc ON bc.rowid=b.fk_bordereau';
	$sql.= " WHERE b.num_releve='".$db->escape($num)."'";
	if (!isset($num))	$sql.= " OR b.num_releve is null";
	$sql.= " AND b.fk_account = ".$object->id;
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= $db->order("b.datev, b.datec", "ASC");  // We add date of creation to have correct order when everything is done the same day

	$result = $db->query($sql);
	if ($result)
	{
		$var=False;
		$numrows = $db->num_rows($result);
		$i = 0;

		// Ligne Solde debut releve
		print "<tr ".$bc[$var]."><td colspan=\"3\"></td>";
		print "<td colspan=\"3\"><b>".$langs->trans("InitialBankBalance")." :</b></td>";
		print '<td align="right"><b>'.price($total).'</b></td><td>&nbsp;</td>';
		print "</tr>\n";

		while ($i < $numrows)
		{
			$objp = $db->fetch_object($result);
			$total = $total + $objp->amount;

			$var=!$var;
			print "<tr ".$bc[$var].">";

			// Date operation
			print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->do),"day").'</td>';

			// Date de valeur
			print '<td align="center" valign="center" class="nowrap">';
			print dol_print_date($db->jdate($objp->dv),"day") .' ';
			print '<a href="releve.php?action=dvprev&amp;num='.$num.'&amp;account='.$object->id.'&amp;dvid='.$objp->rowid.'">';
			print img_edit_remove() . "</a> ";
			print '<a href="releve.php?action=dvnext&amp;num='.$num.'&amp;account='.$object->id.'&amp;dvid='.$objp->rowid.'">';
			print img_edit_add() ."</a>";
			print "</td>\n";
			print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=dvnext&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
				
			// Type and num
            if ($objp->fk_type == 'SOLD') {
                $type_label='&nbsp;';
            } else {
                $type_label=($langs->trans("PaymentTypeShort".$objp->fk_type)!="PaymentTypeShort".$objp->fk_type)?$langs->trans("PaymentTypeShort".$objp->fk_type):$objp->fk_type;
            }
            $link='';
            if ($objp->fk_bordereau>0) {
                $remisestatic->id = $objp->fk_bordereau;
                $remisestatic->ref = $objp->ref;
                $link = ' '.$remisestatic->getNomUrl(1);
            }
			print '<td class="nowrap">'.$type_label.' '.($objp->num_chq?$objp->num_chq:'').$link.'</td>';

			// Description
			print '<td valign="center"><a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$object->id.'">';
			$reg=array();
			preg_match('/\((.+)\)/i',$objp->label,$reg);	// Si texte entoure de parenthese on tente recherche de traduction
			if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) print $langs->trans($reg[1]);
			else print $objp->label;
			print '</a>';

			/*
			 * Ajout les liens (societe, company...)
			 */
			$newline=1;
			$links = $object->get_url($objp->rowid);
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
					$paymentsupplierstatic->ref=$langs->trans("Payment");
					print ' '.$paymentsupplierstatic->getNomUrl(1);
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_sc')
				{
					print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/card.php?id='.$links[$key]['url_id'].'">';
					print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
					print $langs->trans("SocialContributionPayment");
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_vat')
				{
					$paymentvatstatic->id=$links[$key]['url_id'];
					$paymentvatstatic->ref=$langs->trans("Payment");
					print ' '.$paymentvatstatic->getNomUrl(1);
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
                    $societestatic->id = $links[$key]['url_id'];
                    $societestatic->name = $links[$key]['label'];
                    print $societestatic->getNomUrl(1, 'company', 24);
					$newline=0;
				}
				elseif ($links[$key]['type']=='member') {
					print '<a href="'.DOL_URL_ROOT.'/adherents/card.php?rowid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowMember'),'user').' ';
					print $links[$key]['label'];
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='sc') {
					print '<a href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$links[$key]['url_id'].'">';
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

			// Categories
			if ($ve)
			{
				$sql = "SELECT label";
				$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ as ct";
				$sql.= ", ".MAIN_DB_PREFIX."bank_class as cl";
				$sql.= " WHERE ct.rowid = cl.fk_categ";
				$sql.= " AND ct.entity = ".$conf->entity;
				$sql.= " AND cl.lineid = ".$objp->rowid;

				$resc = $db->query($sql);
				if ($resc)
				{
					$numc = $db->num_rows($resc);
					$ii = 0;
					if ($numc && ! $newline) print '<br>';
					while ($ii < $numc)
					{
						$objc = $db->fetch_object($resc);
						print "<br>-&nbsp;<i>$objc->label</i>";
						$ii++;
					}
				}
				else
				{
					dol_print_error($db);
				}
			}
                        print Get_attach_files($db,$objp->rowid,$num,$objp->label);
			print "</td>";

			if ($objp->amount < 0)
			{
				$totald = $totald + abs($objp->amount);
				print '<td align="right" class="nowrap">'.price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
			}
			else
			{
				$totalc = $totalc + abs($objp->amount);
				print '<td>&nbsp;</td><td align="right" class="nowrap">'.price($objp->amount)."</td>\n";
			}

			print '<td align="right" class="nowrap">'.price($total)."</td>\n";

			if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
			{
				print "<td align=\"center\"><a href=\"ligne.php?rowid=$objp->rowid&amp;account=".$object->id."\">";
				print img_edit();
				print "</a></td>";
			}
			else
			{
				print "<td align=\"center\">&nbsp;</td>";
			}
			print "</tr>";
			$i++;
		}
		$db->free($result);
	}

	// Line Total
	print "\n".'<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("Total")." :</td><td align=\"right\">".price($totald)."</td><td align=\"right\">".price($totalc)."</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

	// Line Balance
	print "\n<tr><td align=\"right\" colspan=\"3\">&nbsp;</td><td colspan=\"3\"><b>".$langs->trans("EndBankBalance")." :</b></td>";
	print "<td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td>";
	print "</tr>\n";
	print "</table>";
	print "</div>";
	
	print "</form>\n";
	 // download button
         echo  '<a href="releve.php?num='.$num.'&account='.$id.'&action=dl" class="butAction" name="Send" >'.$langs->trans('DownloadFile')." </a>\n";

}


llxFooter();

$db->close();

/*Function to generate the HTML code used to show the file name & download link attached to the Item covered by the bank line
 * @param $db       Object          database object   
 * @param $bankId   int             bank line id
 * @param $num      int 	    bank statement      
 * @param $label    string	    label used to optimise the sql querry
 * 
 */
function Get_attach_files($db, $bankId,$num,$label=''){
    $out='';
    global$conf;
     $sql='SELECT u.url_id, u.type,ff.rowid as id , ff.`ref` AS reff, f.facnumber AS `ref`,';
     $sql.=' e.`ref` AS refe, sp.rowid AS ids, d.rowid AS idd';
     $sql.=' FROM '.MAIN_DB_PREFIX.'bank_url AS u';
    if ( !empty($label) || $label=='(CustomerInvoicePayment)'){
     $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'paiement AS p ON p.fk_bank = u.fk_bank';
     $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture AS pf ON pf.fk_paiement = p.rowid';
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'facture AS f ON f.rowid = pf.fk_facture';
    }
    if( !empty($label) || $label=='(SupplierInvoicePayment)'){
    //invoice suplier (SupplierInvoicePayment)
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn AS fp ON fp.fk_bank = u.fk_bank';
     $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn AS fpf ON fpf.fk_paiementfourn = fp.rowid';
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn AS ff ON ff.rowid = fpf.fk_facturefourn';
    }
    if( !empty($label) || $label=='(ExpenseReportPayment)'){
    //EXPENSEs (ExpenseReportPayment)
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'payment_expensereport AS ep ON ep.fk_bank = u.fk_bank';
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'expensereport AS e ON e.rowid = ep.fk_expensereport';
    }
    if( !empty($label) || $label=='(DonationPayment)'){
    //donation
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'payment_donation AS dp ON dp.fk_bank = u.fk_bank';
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'don AS d ON d.rowid = dp.fk_donation';
    }
    if( !empty($label) || $label=='(SalaryPayment)'){
    //loan
//    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'payment_loan AS lp ON lp.fk_bank = u.fk_bank';
    //salary
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'payment_salary AS sp ON sp.fk_bank = u.fk_bank';
    }
    //END SQL
    $sql.=" WHERE u.fk_bank in('".$bankId."')AND  u.type in ('payment','payment_supplier','payment_expensereport','payment_salary','payment_donation' )";
    $resd = $db->query($sql);
    $files=array(); 
    $link='';
    if ($resd)
     {
         $numd = $db->num_rows($resd);
        $upload_dir ='';
         $i=0;
         if($numd>0)
         {
            
            
            $objd = $db->fetch_object($resd);
          
            switch($objd->type){
            case "payment":    
                $subdir=dol_sanitizeFileName($objd->ref);
                $upload_dir = $conf->facture->dir_output.'/'.$subdir;
                $link="../../document.php?modulepart=facture&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
               
            case "payment_supplier":      
                $subdir=get_exdir($objd->id,2,0,0,$objd,'invoice_supplier').$objd->reff;
                $upload_dir = $conf->fournisseur->facture->dir_output.'/'.$subdir;
                $link="../../document.php?modulepart=facture_fournisseur&file=".str_replace('/','%2F',$subdir).'%2F';
                break;  
            case "payment_expensereport":
                $subdir=dol_sanitizeFileName($objd->refe);
                $upload_dir = $conf->expensereport->dir_output.'/'.$subdir;
                $link="../../document.php?modulepart=expensereport&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            case "payment_salary":
                $subdir=dol_sanitizeFileName($objd->ids);
                $upload_dir = $conf->salaries->dir_output.'/'.$subdir;
                $link="../../document.php?modulepart=salaries&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            case "payment_donation":
                $subdir=get_exdir(null,2,0,1,$objd,'donation'). '/'. dol_sanitizeFileName($objd->idd);
                $upload_dir = $conf->don->dir_output . '/' . $subdir;
                $link="../../document.php?modulepart=don&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            default:
                break;
            }
            
            if(!empty($upload_dir)){
                $files=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$','',SORT_ASC,1);
                foreach ($files as $key => $file){
                    $out.= '<br><a href="'.$link.$file['name'].'">'.$file['name'].'</a>';
                }
                $_SESSION["releve"][$num][]=$files;
            }
         }
     } 
     $db->free($resd);
       return $out;
}
