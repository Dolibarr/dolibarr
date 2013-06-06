<?php
/* Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
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
 *	\file       htdocs/compta/paiement/cheque/fiche.php
 *	\ingroup    bank, invoice
 *	\brief      Page for cheque deposits
 */

require('../../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load('bills');
$langs->load('companies');
$langs->load('compta');

$id =GETPOST('id','int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');

// Security check
$fieldname = (! empty($ref)?'number':'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'cheque', $id, 'bordereau_cheque','','',$fieldname);

$mesg='';

$sortfield=GETPOST('sortfield', 'alpha');
$sortorder=GETPOST('sortorder', 'alpha');
$page=GETPOST('page', 'int');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="b.dateo,b.rowid";
if ($page < 0) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$dir=$conf->banque->dir_output.'/bordereau/';
$filterdate=dol_mktime(0, 0, 0, GETPOST('fdmonth'), GETPOST('fdday'), GETPOST('fdyear'));
$filteraccountid=GETPOST('accountid');
//var_dump($_POST);

$object = new RemiseCheque($db);


/*
 * Actions
 */

if ($action == 'setdate' && $user->rights->banque->cheque)
{
    $result = $object->fetch(GETPOST('id','int'));
    if ($result > 0)
    {
        //print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
        $date=dol_mktime(0, 0, 0, $_POST['datecreate_month'], $_POST['datecreate_day'], $_POST['datecreate_year']);

        $result=$object->set_date($user,$date);
        if ($result < 0)
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
    else
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

if ($action == 'create' && $_POST["accountid"] > 0 && $user->rights->banque->cheque)
{
	if (is_array($_POST['toRemise']))
	{
		$result = $object->create($user, $_POST["accountid"], 0, $_POST['toRemise']);
		if ($result > 0)
		{
	        if ($object->statut == 1)     // If statut is validated, we build doc
	        {
	            $object->fetch($object->id);    // To force to reload all properties in correct property name
	    	    // Define output language
	    	    $outputlangs = $langs;
	            $newlang='';
	            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	            //if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	            if (! empty($newlang))
	            {
	                $outputlangs = new Translate("",$conf);
	                $outputlangs->setDefaultLang($newlang);
	            }
	            $result = $object->generatePdf($_POST["model"], $outputlangs);
	        }

       		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
        	exit;
		}
		else
		{
			$mesg='<div class="error">'.$object->error.'</div>';
		}
	}
	else
	{
        $mesg=$langs->trans("ErrorSelectAtLeastOne");
	    $action='new';
	}
}

if ($action == 'remove' && $id > 0 && $_GET["lineid"] > 0 && $user->rights->banque->cheque)
{
	$object->id = $id;
	$result = $object->removeCheck($_GET["lineid"]);
	if ($result === 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->banque->cheque)
{
	$object->id = $id;
	$result = $object->delete();
	if ($result == 0)
	{
		header("Location: index.php");
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}

if ($action == 'confirm_valide' && $confirm == 'yes' && $user->rights->banque->cheque)
{
	$result = $object->fetch($id);
	$result = $object->validate($user);
	if ($result >= 0)
	{
        // Define output language
        $outputlangs = $langs;
        $newlang='';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
        //if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
        if (! empty($newlang))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($newlang);
        }
        $result = $object->generatePdf(GETPOST('model'), $outputlangs);

        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}

if ($action == 'builddoc' && $user->rights->banque->cheque)
{
	$result = $object->fetch($id);

	/*if ($_REQUEST['model'])
	{
		$object->setDocModel($user, $_REQUEST['model']);
	}*/

    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
    //if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
	$result = $object->generatePdf($_POST["model"], $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}
	else
	{
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
		exit;
	}
}

// Remove file in doc form
else if ($action == 'remove_file' && $user->rights->banque->cheque)
{
	if ($object->fetch($id) > 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$langs->load("other");

		$file=$dir.get_exdir($object->number,2,1) . GETPOST('file');
		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('file')));
		else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), 'errors');
	}
}


/*
 * View
 */

if (GETPOST('removefilter'))
{
    $filterdate='';
    $filteraccountid=0;
}

llxHeader();

$form = new Form($db);
$formfile = new FormFile($db);


if ($action == 'new')
{
	$h=0;
	$head[$h][0] = $_SERVER["PHP_SELF"].'?action=new';
	$head[$h][1] = $langs->trans("MenuChequeDeposits");
	$hselected = $h;
	$h++;

	print_fiche_titre($langs->trans("Cheques"));
}
else
{
	$result = $object->fetch($id, $ref);
	if ($result < 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}

	$h=0;
	$head[$h][0] = $_SERVER["PHP_SELF"].'?id='.$object->id;
	$head[$h][1] = $langs->trans("CheckReceipt");
	$hselected = $h;
	$h++;
	//  $head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/info.php?id='.$object->id;
	//  $head[$h][1] = $langs->trans("Info");
	//  $h++;

	dol_fiche_head($head, $hselected, $langs->trans("Cheques"),0,'payment');

	/*
	 * Confirmation de la suppression du bordereau
	 */
	if ($action == 'delete')
	{
		$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("DeleteCheckReceipt"), $langs->trans("ConfirmDeleteCheckReceipt"), 'confirm_delete','','',1);
		if ($ret == 'html') print '<br>';
	}

	/*
	 * Confirmation de la validation du bordereau
	 */
	if ($action == 'valide')
	{
		$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("ValidateCheckReceipt"), $langs->trans("ConfirmValidateCheckReceipt"), 'confirm_valide','','',1);
		if ($ret == 'html') print '<br>';
	}
}


dol_htmloutput_errors($mesg);

$accounts = array();

if ($action == 'new')
{
	$lines = array();

	$now=dol_now();

	print $langs->trans("SelectChequeTransactionAndGenerate").'<br><br>'."\n";

	print '<form class="nocellnopadd" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="action" value="new">';
    //print '<fieldset><legend>aaa</legend>';
	print '<table class="border" width="100%">';
	//print '<tr><td width="30%">'.$langs->trans('Date').'</td><td width="70%">'.dol_print_date($now,'day').'</td></tr>';
	// Filter
	print '<tr><td width="200">'.$langs->trans("DateChequeReceived").'</td><td>';
	print $form->select_date($filterdate,'fd',0,0,1,'',1,1);
	print '</td></tr>';
    print '<tr><td>'.$langs->trans("BankAccount").'</td><td>';
    print $form->select_comptes($filteraccountid,'accountid',0,'courant <> 2',1);
    print '</td></tr>';
	print '</table>';
    print '<center>';
	print '<input type="submit" class="button" name="filter" value="'.dol_escape_htmltag($langs->trans("ToFilter")).'">';
    if ($filterdate || $filteraccountid > 0)
    {
    	print ' &nbsp; ';
    	print '<input type="submit" class="button" name="removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    }
	print '</center>';
    //print '</fieldset>';
	print '</form>';
	print '<br>';

	$sql = "SELECT ba.rowid as bid, b.datec as datec, b.dateo as date, b.rowid as chqid, ";
	$sql.= " b.amount, ba.label, b.emetteur, b.num_chq, b.banque";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b,";
	$sql.= " ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= " WHERE b.fk_type = 'CHQ'";
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
	$sql.= " AND b.fk_bordereau = 0";
	$sql.= " AND b.amount > 0";
	if ($filterdate)      $sql.=" AND b.dateo = '".$db->idate($filterdate)."'";
    if ($filteraccountid) $sql.=" AND ba.rowid= '".$filteraccountid."'";
	$sql.= $db->order("b.dateo,b.rowid","ASC");

	$resql = $db->query($sql);
	if ($resql)
	{
		$i = 0;
		while ( $obj = $db->fetch_object($resql) )
		{
			$accounts[$obj->bid] = $obj->label;
			$lines[$obj->bid][$i]["date"] = $db->jdate($obj->date);
			$lines[$obj->bid][$i]["amount"] = $obj->amount;
			$lines[$obj->bid][$i]["emetteur"] = $obj->emetteur;
			$lines[$obj->bid][$i]["numero"] = $obj->num_chq;
			$lines[$obj->bid][$i]["banque"] = $obj->banque;
			$lines[$obj->bid][$i]["id"] = $obj->chqid;
			$i++;
		}

		if ($i == 0)
		{
			print '<b>'.$langs->trans("NoWaitingChecks").'</b><br>';
		}
	}

	foreach ($accounts as $bid => $account_label)
	{

        print '
        <script language="javascript" type="text/javascript">
        jQuery(document).ready(function()
        {
            jQuery("#checkall_'.$bid.'").click(function()
            {
                jQuery(".checkforremise_'.$bid.'").attr(\'checked\', true);
            });
            jQuery("#checknone_'.$bid.'").click(function()
            {
                jQuery(".checkforremise_'.$bid.'").attr(\'checked\', false);
            });
        });
        </script>
        ';

		$num = $db->num_rows($resql);
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="hidden" name="accountid" value="'.$bid.'">';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td style="min-width: 120px">'.$langs->trans("DateChequeReceived").' ';
		print "</td>\n";
		print '<td style="min-width: 120px">'.$langs->trans("ChequeNumber")."</td>\n";
		print '<td style="min-width: 200px">'.$langs->trans("CheckTransmitter")."</td>\n";
		print '<td style="min-width: 200px">'.$langs->trans("Bank")."</td>\n";
		print '<td align="right" width="100px">'.$langs->trans("Amount")."</td>\n";
		print '<td align="center" width="100px">'.$langs->trans("Select")."<br>";
		if ($conf->use_javascript_ajax) print '<a href="#" id="checkall_'.$bid.'">'.$langs->trans("All").'</a> / <a href="#" id="checknone_'.$bid.'">'.$langs->trans("None").'</a>';
		print '</td>';

		print "</tr>\n";

		$var=true;

		foreach ($lines[$bid] as $lid => $value)
		{
			$var=!$var;

			$account_id = $bid;
			if (! isset($accounts[$bid]))
				$accounts[$bid]=0;
			$accounts[$bid] += 1;

			print "<tr ".$bc[$var].">";
			print '<td>'.dol_print_date($value["date"],'day').'</td>';
			print '<td>'.$value["numero"]."</td>\n";
			print '<td>'.$value["emetteur"]."</td>\n";
			print '<td>'.$value["banque"]."</td>\n";
			print '<td align="right">'.price($value["amount"]).'</td>';
			print '<td align="center">';
			print '<input id="'.$value["id"].'" class="flat checkforremise_'.$bid.'" checked="checked" type="checkbox" name="toRemise[]" value="'.$value["id"].'">';
			print '</td>' ;
			print '</tr>';

			$i++;
		}
		print "</table>";

		print '<div class="tabsAction">';
		if ($user->rights->banque->cheque)
		{
			print '<input type="submit" class="button" value="'.$langs->trans('NewCheckDepositOn',$account_label).'">';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('NewCheckDepositOn',$account_label).'</a>';
		}
		print '</div><br>';
		print '</form>';
	}

}
else
{
	$linkback='<a href="'.$_SERVER["PHP_SELF"].'?leftmenu=customers_bills_checks&action=new">'.$langs->trans("BackToList").'</a>';
	$paymentstatic=new Paiement($db);
	$accountlinestatic=new AccountLine($db);
	$accountstatic=new Account($db);

	$accountstatic->id=$object->account_id;
	$accountstatic->label=$object->account_label;

	print '<table class="border" width="100%">';
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td><td colspan="2" >';

	print $form->showrefnav($object,'ref',$linkback, 1, 'number');

	print "</td>";
	print "</tr>\n";

	print '<tr><td>';

    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('Date');
    print '</td>';
    if ($action != 'editdate') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
    print '</tr></table>';
    print '</td><td colspan="2">';
    if ($action == 'editdate')
    {
        print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="setdate">';
        $form->select_date($object->date_bordereau,'datecreate_','','','',"setdate");
        print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
        print '</form>';
    }
    else
    {
        print $object->date_bordereau ? dol_print_date($object->date_bordereau,'day') : '&nbsp;';
    }

	print '</td>';
	print '</tr>';

	print '<tr><td>'.$langs->trans('Account').'</td><td colspan="2">';
	print $accountstatic->getNomUrl(1);
	print '</td></tr>';

	// Nb of cheques
	print '<tr><td>'.$langs->trans('NbOfCheques').'</td><td colspan="2">';
	print $object->nbcheque;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Total').'</td><td colspan="2">';
	print price($object->amount);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Status').'</td><td colspan="2">';
	print $object->getLibStatut(4);
	print '</td></tr>';

	print '</table><br>';


	// Liste des cheques
	$sql = "SELECT b.rowid, b.amount, b.num_chq, b.emetteur,";
	$sql.= " b.dateo as date, b.datec as datec, b.banque,";
	$sql.= " p.rowid as pid, ba.rowid as bid";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."bank as b";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement as p ON p.fk_bank = b.rowid";
	$sql.= " WHERE ba.rowid = b.fk_account";
	$sql.= " AND ba.entity = ".$conf->entity;
	$sql.= " AND b.fk_type= 'CHQ'";
	$sql.= " AND b.fk_bordereau = ".$object->id;
	$sql.= " ORDER BY $sortfield $sortorder";

	dol_syslog("sql=".$sql);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';

		$param="&amp;id=".$object->id;
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Cheque"),'','','','','width="30"');
		print_liste_field_titre($langs->trans("DateChequeReceived"),$_SERVER["PHP_SELF"],"b.dateo,b.rowid", "",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Numero"),$_SERVER["PHP_SELF"],"b.num_chq", "",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("CheckTransmitter"),$_SERVER["PHP_SELF"],"b.emetteur", "",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Bank"),$_SERVER["PHP_SELF"],"b.banque", "",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"b.amount", "",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("LineRecord"),$_SERVER["PHP_SELF"],"b.rowid", "",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre('','','');
		print "</tr>\n";
		$i=1;
		$var=false;
		while ($objp = $db->fetch_object($resql))
		{
			$account_id = $objp->bid;
			if (! isset($accounts[$objp->bid]))
				$accounts[$objp->bid]=0;
			$accounts[$objp->bid] += 1;

			print "<tr $bc[$var]>";
			print '<td align="center">'.$i.'</td>';
			print '<td align="center">'.dol_print_date($db->jdate($objp->date),'day').'</td>';	// Date operation
			print '<td align="center">'.($objp->num_chq?$objp->num_chq:'&nbsp;').'</td>';
			print '<td>'.dol_trunc($objp->emetteur,24).'</td>';
			print '<td>'.dol_trunc($objp->banque,24).'</td>';
			print '<td align="right">'.price($objp->amount).'</td>';
			print '<td align="center">';
			$accountlinestatic->rowid=$objp->rowid;
			if ($accountlinestatic->rowid)
			{
				print $accountlinestatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
			if ($object->statut == 0)
			{
				print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=remove&amp;lineid='.$objp->rowid.'">'.img_delete().'</a></td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
			print '</tr>';
			$var=!$var;
			$i++;
		}
		print "</table>";
	}
	else
	{
		dol_print_error($db);
	}

    dol_fiche_end();
}




/*
 * Boutons Actions
 */

print '<div class="tabsAction">';

if ($user->societe_id == 0 && count($accounts) == 1 && $action == 'new' && $user->rights->banque->cheque)
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=create&amp;accountid='.$account_id.'">'.$langs->trans('NewCheckReceipt').'</a>';
}

if ($user->societe_id == 0 && ! empty($object->id) && $object->statut == 0 && $user->rights->banque->cheque)
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valide&amp;sortfield='.$sortfield.'&amp;sortorder='.$sortorder.'">'.$langs->trans('Valid').'</a>';
}

if ($user->societe_id == 0 && ! empty($object->id) && $user->rights->banque->cheque)
{
	print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete&amp;sortfield='.$sortfield.'&amp;sortorder='.$sortorder.'">'.$langs->trans('Delete').'</a>';

}
print '</div>';



if ($action != 'new')
{
	if ($object->statut == 1)
	{
		$filename=dol_sanitizeFileName($object->ref);
		$filedir=$dir.get_exdir($object->number,2,1) . dol_sanitizeFileName($object->ref);
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;

		$formfile->show_documents('remisecheque', $filename, $filedir, $urlsource, 1, 1);

		print '<br>';
	}
}


$db->close();

llxFooter();
?>
