<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
//show files
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("banks","categories","companies","bills","trips"));

$action=GETPOST('action', 'alpha');
$id=GETPOST('account','int');
$ref=GETPOST('ref','alpha');
$dvid=GETPOST('dvid','alpha');
$numref=GETPOST('num','alpha');
$ve=GETPOST("ve",'alpha');
$brref=GETPOST('brref','alpha');
$oldbankreceipt=GETPOST('oldbankreceipt','alpha');
$newbankreceipt=GETPOST('newbankreceipt','alpha');

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


$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$pageplusone = GETPOST("pageplusone",'int');
if ($pageplusone) $page = $pageplusone - 1;
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
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


// Define number of receipt to show (current, previous or next one ?)
$found=false;
if ($_GET["rel"] == 'prev')
{
	// Recherche valeur pour num = numero releve precedent
	$sql = "SELECT DISTINCT(b.num_releve) as num";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.num_releve < '".$db->escape($numref)."'";
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
			$numref = $obj->num;
			$found=true;
		}
	}
}
elseif ($_GET["rel"] == 'next')
{
	// Recherche valeur pour num = numero releve precedent
	$sql = "SELECT DISTINCT(b.num_releve) as num";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.num_releve > '".$db->escape($numref)."'";
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
			$numref = $obj->num;
			$found=true;
		}
	}
}
else {
	// On veut le releve num
	$found=true;
}


$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
$sql.= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type,";
$sql.= " b.fk_bordereau,";
$sql.= " bc.ref,";
$sql.= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
$sql.= ", ".MAIN_DB_PREFIX."bank as b";
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bordereau_cheque as bc ON bc.rowid=b.fk_bordereau';
$sql.= " WHERE b.num_releve='".$db->escape($numref)."'";
if (empty($numref))	$sql.= " OR b.num_releve is null";
$sql.= " AND b.fk_account = ".$object->id;
$sql.= " AND b.fk_account = ba.rowid";
$sql.= $db->order("b.datev, b.datec", "ASC");  // We add date of creation to have correct order when everything is done the same day

$sqlrequestforbankline = $sql;



/*
 * Actions
 */

if ($action == 'confirm_editbankreceipt' && ! empty($oldbankreceipt) && ! empty($newbankreceipt))
{
	// TODO Add a test to check newbankreceipt does not exists yet
	$sqlupdate = 'UPDATE '.MAIN_DB_PREFIX.'bank SET num_releve = "'.$db->escape($newbankreceipt).'" WHERE num_releve = "'.$db->escape($oldbankreceipt).'"';
	$result = $db->query($sqlupdate);
	if ($result < 0) dol_print_error($db);

	$action='view';
}

// ZIP creation
if ($action=="dl" && $numref > 0)
{
	// TODO Replace this with a standard builddoc action that use a document generation module to build the ZIP
    $log = '';

    $outdir = $conf->bank->dir_temp.'/'.$numref.'-'.$object->label;
    $outdirinvoices = $outdir.'/'.$langs->trans("BillsCustomers");
    $outdirsupplierinvoices = $outdir.'/'.$langs->trans("BillsSuppliers");

    dol_mkdir($outdir);
    dol_mkdir($outdirinvoices);
    dol_mkdir($outdirsupplierinvoices);

    //$zipname = $object->label.'-'.$numref . '.zip';
    //$zip = new ZipArchive();
    //$zip->open($zipname, ZipArchive::OVERWRITE);

    $sql = $sqlrequestforbankline;

    $facturestatic=new Facture($db);

    $resd = $db->query($sql);
    if ($resd) {
        $numd = $db->num_rows($resd);
        $i = 0;
        if ($numd > 0)
        {
            $objd = $db->fetch_object($resd);

            $log.='Transaction '.$objd->rowid;
            $links = $object->get_url($objd->rowid);

            foreach($links as $key=>$val)
            {
                $link = ''; $upload_dir = '';

                switch ($val['type']) {
                    case "payment":
                        $payment = new Paiement($db);
                        $payment->fetch($val['url_id']);
                        $arraybill = $payment->getBillsArray();
                        if (is_array($arraybill) && count($arraybill) > 0)
                        {
                            foreach ($arraybill as $billid)
                            {
                                $facturestatic->fetch($billid);
                                $subdir = get_exdir($facturestatic->id, 2, 0, 0, $facturestatic, 'invoice');

                                $arrayofinclusion=array();              // TODO Find a way to get doc ODT or other
                                // TODO Use get_exdir
                                $arrayofinclusion[]=preg_quote($facturestatic->ref.'.pdf','/');
                                $listoffiles = dol_dir_list($conf->facture->dir_output.$subdir,'all',1,implode('|',$arrayofinclusion),'\.meta$|\.png','date',SORT_DESC,0,true);
                                // build list of files with full path
                                $files = array();
                                foreach($listoffiles as $filefound)
                                {
                                    if (strstr($filefound["name"],$facturestatic->ref))
                                    {
                                        $files[] = $uploaddir.'/'.$facturestatic->ref.'/'.$filefound["name"];
                                        break;
                                    }
                                }
                                /*var_dump($files);*/
                                //var_dump($listoffiles);
                                foreach($listoffiles as $key => $srcfileobj)
                                {
                                    $srcfile = $srcfileobj['fullname'];
                                    $destfile = $outdirinvoices.'/'.$srcfileobj['name'];
                                    //var_dump($srcfile.' - '.$destfile);
                                    dol_copy($srcfile, $destfile);
                                }
                            }
                        }
                        break;
                    case "payment_supplier":
                        $payment = new PaiementFourn($db);
                        $payment->fetch($val['url_id']);
                        $arraybill = $payment->getBillsArray();
                        if (is_array($arraybill) && count($arraybill) > 0)
                        {
                            foreach ($arraybill as $billid)
                            {
                                $facturestatic->fetch($billid);
                                $subdir = get_exdir($facturestatic->id, 2, 0, 0, $facturestatic, 'invoice_supplier');

                                $arrayofinclusion=array();              // TODO Find a way to get doc ODT or other
                                // TODO Use get_exdir
                                $arrayofinclusion[]=preg_quote($facturestatic->ref.'.pdf','/');
                                $listoffiles = dol_dir_list($conf->fournisseur->facture->dir_output.$subdir,'all',1,implode('|',$arrayofinclusion),'\.meta$|\.png','date',SORT_DESC,0,true);
                                // build list of files with full path
                                $files = array();
                                foreach($listoffiles as $filefound)
                                {
                                    if (strstr($filefound["name"],$facturestatic->ref))
                                    {
                                        $files[] = $uploaddir.'/'.$facturestatic->ref.'/'.$filefound["name"];
                                        break;
                                    }
                                }
                                /*var_dump($files);*/
                                //var_dump($listoffiles);
                                foreach($listoffiles as $key => $srcfileobj)
                                {
                                    $srcfile = $srcfileobj['fullname'];
                                    $destfile = $outdirinvoices.'/'.$srcfileobj['name'];
                                    //var_dump($srcfile.' - '.$destfile);
                                    dol_copy($srcfile, $destfile);
                                }
                            }
                        }
                        break;
                    case "payment_expensereport":
                        /*$subdir = dol_sanitizeFileName($objd->refe);
                        $upload_dir = $conf->expensereport->dir_output . '/' . $subdir;*/
                        break;
                    case "payment_salary":
                        /*$subdir = dol_sanitizeFileName($objd->ids);
                        $upload_dir = $conf->salaries->dir_output . '/' . $subdir;*/
                        break;
                    case "payment_donation":
                        /*$subdir = get_exdir(null, 2, 0, 1, $objd, 'donation') . '/' . dol_sanitizeFileName($objd->idd);
                        $upload_dir = $conf->don->dir_output . '/' . $subdir;*/
                        break;
                    default:
                        break;
                }
            }
            $log.="\n";

            /*if (! empty($upload_dir))
            {
                $files = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', '', SORT_ASC, 1);

                if (is_array($files)) {
                    foreach ($files as $file) {
                        $zip->addFile($file["fullname"], $file["name"]); //
                        $log .= $key . ',' . $file["name"] . "\n";
                    }
                } else {
                    $log .= $key . ',' . $langs->trans("Nofile") . "\n";
                }

            }*/
        }
    }

    $db->free($resd);


    //$zip->addFromString('log '.$numref.'.csv', $log);
    //$zip->close();

    // /Then download the zipped file.
    /*header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zipname);
    header('Content-Length: ' . filesize($zipname));

    readfile($zipname);

    exit;*/
}


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


if (empty($numref))
{
	$sortfield='numr';
	$sortorder='DESC';

	// List of all standing receipts
	$sql = "SELECT DISTINCT(b.num_releve) as numr";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.fk_account = ".$object->id;
	$sql.=$db->order($sortfield,$sortorder);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
	}

	$sql.= $db->plimit($conf->liste_limit+1,$offset);

	$result = $db->query($sql);
	if ($result)
	{
		$numrows = $db->num_rows($result);
		$i = 0;

		// Onglets
		$head=bank_prepare_head($object);
		dol_fiche_head($head,'statement',$langs->trans("FinancialAccount"),0,'account');

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

		dol_fiche_end();


		print '<div class="tabsAction">';

		if ($object->canBeConciliated() > 0) {
			// If not cash account and can be reconciliate
			if ($user->rights->banque->consolidate) {
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?action=reconcile&search_conciliated=0'.$param.'">'.$langs->trans("Conciliate").'</a>';
			} else {
				print '<a class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("Conciliate").'</a>';
			}
		}

		print '</div>';


		print_barre_liste('', $page, $_SERVER["PHP_SELF"], "&account=".$object->id, $sortfield, $sortorder,'',$numrows, $totalnboflines, '');

		print '<form name="aaa" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="confirm_editbankreceipt">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="account" value="'.$object->id.'">';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Ref").'</td>';
		print '<td align="right">'.$langs->trans("InitialBankBalance").'</td>';
		print '<td align="right">'.$langs->trans("EndBankBalance").'</td>';
		print '<td></td>';
		print '</tr>';

		$balancestart=array();
		$content=array();

		while ($i < min($numrows,$conf->liste_limit))
		{
			$objp = $db->fetch_object($result);

			if (! isset($objp->numr))
			{
				//
			}
			else
			{
				print '<tr class="oddeven">';
				print '<td>';
				if ($action != 'editbankreceipt' || $objp->numr != $brref)
				{
					print '<a href="releve.php?num='.$objp->numr.'&account='.$object->id.'">'.$objp->numr.'</a>';
				}
				else
				{
					print '<input type="hidden" name="oldbankreceipt" value="'.$objp->numr.'">';
					print '<input type="text" name="newbankreceipt" value="'.$objp->numr.'">';
					print '<input type="submit" class="button" name="actionnewbankreceipt" value="'.$langs->trans("Rename").'">';
					print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
				}
				print '</td>';

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

				print '<td align="center">';
				if ($user->rights->banque->consolidate && $action != 'editbankreceipt') {
					print '<a href="'.$_SERVER["PHP_SELF"].'?account='.$object->id.'&action=editbankreceipt&brref='.$objp->numr.'">'.img_edit().'</a>';
				}
				print '</td>';

				print '</tr>'."\n";
			}
			$i++;
		}
		print "</table>\n";
		print '</form>';

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

    $mesprevnext='';
	$mesprevnext.='<div class="pagination"><ul>';
	$mesprevnext.='<li class="pagination"><a class="paginationnext" href="'.$_SERVER["PHP_SELF"].'?rel=prev&amp;num='.$numref.'&amp;ve='.$ve.'&amp;account='.$object->id.'"><i class="fa fa-chevron-left" title="'.dol_escape_htmltag($langs->trans("Previous")).'"></i></a></li>';
	//$mesprevnext.=' &nbsp; ';
	$mesprevnext.='<li class="pagination"><span class="active">'.$langs->trans("AccountStatement")." ".$numref.'</span></li>';
	//$mesprevnext.=' &nbsp; ';
    $mesprevnext.='<li class="pagination"><a class="paginationnext" href="'.$_SERVER["PHP_SELF"].'?rel=next&amp;num='.$numref.'&amp;ve='.$ve.'&amp;account='.$object->id.'"><i class="fa fa-chevron-right" title="'.dol_escape_htmltag($langs->trans("Next")).'"></i></a></li>';
    $mesprevnext.='</ul></div>';

    $title=$langs->trans("AccountStatement").' '.$numref.' - '.$langs->trans("BankAccount").' '.$object->getNomUrl(1, 'receipts');
	print load_fiche_titre($title, $mesprevnext, 'title_bank.png');
	//print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, 0, $nbtotalofrecords, 'title_bank.png', 0, '', '', 0, 1);

	print "<form method=\"post\" action=\"releve.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

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
	$sql.= " WHERE b.num_releve < '".$db->escape($numref)."'";
	$sql.= " AND b.fk_account = ".$object->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$total = $obj->amount;
		$db->free($resql);
	}

	// Recherche les ecritures pour le releve
    $sql = $sqlrequestforbankline;

	$result = $db->query($sql);
	if ($result)
	{
		$numrows = $db->num_rows($result);
		$i = 0;

		// Ligne Solde debut releve
		print '<tr class="oddeven"><td colspan="3"></td>';
		print '<td colspan="3"><b>'.$langs->trans("InitialBankBalance")." :</b></td>";
		print '<td class="right"><b>'.price($total).'</b></td><td>&nbsp;</td>';
		print "</tr>\n";

		while ($i < $numrows)
		{
			$objp = $db->fetch_object($result);
			$total = $total + $objp->amount;

			print '<tr class="oddeven">';

			// Date operation
			print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->do),"day").'</td>';

			// Date de valeur
			print '<td align="center" valign="center" class="nowrap">';
			print dol_print_date($db->jdate($objp->dv),"day") .' ';
			print '<a href="releve.php?action=dvprev&amp;num='.$numref.'&amp;account='.$object->id.'&amp;dvid='.$objp->rowid.'">';
			print img_edit_remove() . "</a> ";
			print '<a href="releve.php?action=dvnext&amp;num='.$numref.'&amp;account='.$object->id.'&amp;dvid='.$objp->rowid.'">';
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
				elseif ($links[$key]['type']=='payment_salary')
				{
					print '<a href="'.DOL_URL_ROOT.'/compta/salaries/card.php?id='.$links[$key]['url_id'].'">';
					print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
					print $langs->trans("Payment");
					print '</a>';
					$newline=0;
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
				elseif ($links[$key]['type']=='user') {
					print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowUser'),'user').' ';
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
						print "<br>-&nbsp;<i>".$objc->label."</i>";
						$ii++;
					}
				}
				else
				{
					dol_print_error($db);
				}
			}

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

			print '<td align="right" class="nowrap">'.price(price2num($total, 'MT'))."</td>\n";

			if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
			{
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&account='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?account='.$object->id.'&num='.$numref).'">';
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
	print "\n<tr>";
	print "<td align=\"right\" colspan=\"3\">&nbsp;</td><td colspan=\"3\"><b>".$langs->trans("EndBankBalance")." :</b></td>";
	print '<td class="right"><b>'.price(price2num($total, 'MT'))."</b></td><td>&nbsp;</td>";
	print "</tr>\n";
	print "</table>";
	print "</div>";

	print "</form>\n";

	// Add a download button
	if ($conf->global->MAIN_FEATURES_LEVEL >= 2)   // Started a rewrite to make this feature more Dolibarr compliant. Still need dev to be completed.
	{
	    // TODO Replace this with standard box to generate document.
	   print '<a href="'.DOL_URL_ROOT.'/compta/bank/releve.php?num='.$numref.'&account='.$id.'&action=dl" class="butAction" name="Send" >'.$langs->trans('DownloadPackageWithAllDocuments')." </a>\n";
	}
}

// End of page
llxFooter();
$db->close();
