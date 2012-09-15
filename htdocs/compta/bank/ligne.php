<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier DUTOIT        <doli@sydesy.com>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/compta/bank/ligne.php
 *	\ingroup    compta
 *	\brief      Page to edit a bank transaction record
 */

require 'pre.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("compta");
$langs->load("bills");
$langs->load("categories");
if (! empty($conf->adherent->enabled)) $langs->load("members");


$id = (GETPOST('id','int') ? GETPOST('id','int') : GETPOST('account','int'));
$ref = GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$rowid=GETPOST("rowid",'int');
$orig_account=GETPOST("orig_account");

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref :''));
$fieldtype = (! empty($ref) ? 'ref' :'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$fieldvalue,'bank_account','','',$fieldtype);
if (! $user->rights->banque->lire && ! $user->rights->banque->consolidate) accessforbidden();


/*
 * Actions
 */

if ($user->rights->banque->consolidate && $action == 'dvnext')
{
    $al = new AccountLine($db);
    $al->datev_next($_GET["rowid"]);
}

if ($user->rights->banque->consolidate && $action == 'dvprev')
{
    $al = new AccountLine($db);
    $al->datev_previous($_GET["rowid"]);
}

if ($action == 'confirm_delete_categ' && $confirm == "yes" && $user->rights->banque->modifier)
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = ".$rowid." AND fk_categ = ".GETPOST("cat1");
    if (! $db->query($sql))
    {
        dol_print_error($db);
    }
}

if ($user->rights->banque->modifier && $action == 'class')
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = ".$rowid." AND fk_categ = ".$_POST["cat1"];
    if (! $db->query($sql))
    {
        dol_print_error($db);
    }

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES (".$rowid.", ".$_POST["cat1"].")";
    if (! $db->query($sql))
    {
        dol_print_error($db);
    }
}

if ($user->rights->banque->modifier && $action == "update")
{
	$error=0;

	$ac = new Account($db);
	$ac->fetch($id);

	if ($ac->courant == 2 && $_POST['value'] != 'LIQ')
	{
		$mesg = '<div class="error">'.$langs->trans("ErrorCashAccountAcceptsOnlyCashMoney").'</div>';
		$error++;
	}

	if (! $error)
	{
		// Avant de modifier la date ou le montant, on controle si ce n'est pas encore rapproche
		$conciliated=0;
		$sql = "SELECT b.rappro FROM ".MAIN_DB_PREFIX."bank as b WHERE rowid=".$rowid;
		$result = $db->query($sql);
		if ($result)
		{
			$objp = $db->fetch_object($result);
			$conciliated=$objp->rappro;
		}

		$db->begin();

		$amount = price2num($_POST['amount']);
		$dateop = dol_mktime(12,0,0,$_POST["dateomonth"],$_POST["dateoday"],$_POST["dateoyear"]);
		$dateval= dol_mktime(12,0,0,$_POST["datevmonth"],$_POST["datevday"],$_POST["datevyear"]);
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
		$sql.= " SET ";
		// Always opened
		if (isset($_POST['value']))      $sql.=" fk_type='".$db->escape($_POST['value'])."',";
		if (isset($_POST['num_chq']))    $sql.=" num_chq='".$db->escape($_POST["num_chq"])."',";
		if (isset($_POST['banque']))     $sql.=" banque='".$db->escape($_POST["banque"])."',";
		if (isset($_POST['emetteur']))   $sql.=" emetteur='".$db->escape($_POST["emetteur"])."',";
		// Blocked when conciliated
		if (! $conciliated)
		{
			if (isset($_POST['label']))      $sql.=" label='".$db->escape($_POST["label"])."',";
			if (isset($_POST['amount']))     $sql.=" amount='".$amount."',";
			if (isset($_POST['dateomonth'])) $sql.=" dateo = '".$db->idate($dateop)."',";
			if (isset($_POST['datevmonth'])) $sql.=" datev = '".$db->idate($dateval)."',";
		}
		$sql.= " fk_account = ".$id;
		$sql.= " WHERE rowid = ".$rowid;

		$result = $db->query($sql);
		if ($result)
		{
			$mesg=$langs->trans("RecordSaved");
			$db->commit();
		}
		else
		{
			$db->rollback();
			dol_print_error($db);
		}
	}
}

// Reconcile
if ($user->rights->banque->consolidate && ($action == 'num_releve' || $action == 'setreconcile'))
{
    $num_rel=trim($_POST["num_rel"]);
    $rappro=$_POST['reconciled']?1:0;

    // Check parameters
    if ($rappro && empty($num_rel))
    {
        $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("AccountStatement"));
        $error++;
    }

    if (! $error)
    {
        $db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank";
        $sql.= " SET num_releve=".($num_rel?"'".$num_rel."'":"null");
        if (empty($num_rel)) $sql.= ", rappro = 0";
        else $sql.=", rappro = ".$rappro;
        $sql.= " WHERE rowid = ".$rowid;

        dol_syslog("ligne.php sql=".$sql, LOG_DEBUG);
        $result = $db->query($sql);
        if ($result)
        {
            $mesg=$langs->trans("RecordSaved");
            $db->commit();
        }
        else
        {
            $db->rollback();
            dol_print_error($db);
        }
    }
}



/*
 * View
 */

$form = new Form($db);

llxHeader();

// On initialise la liste des categories
$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ";
$sql.= " ORDER BY label";
$result = $db->query($sql);
if ($result)
{
    $var=True;
    $num = $db->num_rows($result);
    $i = 0;
    $options = "<option value=\"0\" selected=\"true\">&nbsp;</option>";
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n";
        $i++;
    }
    $db->free($result);
}

$var=false;

$tabs = array(
    array(
        DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$rowid,
        $langs->trans('Card')
    ),
    array(
        DOL_URL_ROOT.'/compta/bank/info.php?rowid='.$rowid,
        $langs->trans('Info')
    )
);

dol_fiche_head($tabs, 0, $langs->trans('LineRecord'), 0, 'account');

dol_htmloutput_mesg($mesg);

$sql = "SELECT b.rowid,b.dateo as do,b.datev as dv, b.amount, b.label, b.rappro,";
$sql.= " b.num_releve, b.fk_user_author, b.num_chq, b.fk_type, b.fk_account, b.fk_bordereau as receiptid,";
$sql.= " b.emetteur,b.banque";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql.= " WHERE rowid=".$rowid;
$sql.= " ORDER BY dateo ASC";
$result = $db->query($sql);
if ($result)
{
    $i = 0; $total = 0;
    if ($db->num_rows($result))
    {
        $objp = $db->fetch_object($result);

        $total = $total + $objp->amount;

        $acct=new Account($db);
        $acct->fetch($objp->fk_account);
        $account = $acct->id;

        $bankline = new AccountLine($db);
        $bankline->fetch($rowid,$ref);

        $links=$acct->get_url($rowid);
        $bankline->load_previous_next_ref('','rowid');

        // Confirmations
        if ($action == 'delete_categ')
        {
            $ret=$form->form_confirm($_SERVER['PHP_SELF']."?rowid=".$rowid."&cat1=".GETPOST("fk_categ")."&orig_account=".$orig_account, $langs->trans("RemoveFromRubrique"), $langs->trans("RemoveFromRubriqueConfirm"), "confirm_delete_categ", '', 'yes', 1);
            if ($ret == 'html') print '<br>';
        }

        print '<form name="update" method="POST" action="'.$_SERVER['PHP_SELF'].'?rowid='.$rowid.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="orig_account" value="'.$orig_account.'">';
        print '<input type="hidden" name="id" value="'.$acct->id.'">';

        print '<table class="border" width="100%">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/search.php">'.$langs->trans("BackToList").'</a>';

        // Ref
        print '<tr><td width="20%">'.$langs->trans("Ref")."</td>";
        print '<td colspan="4">';
        print $form->showrefnav($bankline, 'rowid', $linkback, 1, 'rowid', 'rowid');
        print '</td>';
        print '</tr>';

        $i++;


        // Bank account
        print "<tr><td>".$langs->trans("Account")."</td>";
        print '<td colspan="4">';
        print $acct->getNomUrl(1,'transactions');
        print '</td>';
        print '</tr>';

        // Show links of bank transactions
        if (count($links))
        {
            print "<tr><td>".$langs->trans("Links")."</td>";
            print '<td colspan="4">';
            foreach($links as $key=>$val)
            {
                if ($key) print '<br>';
                if ($links[$key]['type']=='payment') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowPayment'),'payment').' ';
                    print $langs->trans("Payment");
                    print '</a>';
                }
                else if ($links[$key]['type']=='payment_supplier') {
                    print '<a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowPayment'),'payment').' ';
                    print $langs->trans("Payment");
                    print '</a>';
                }
                else if ($links[$key]['type']=='company') {
                    print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowCompany'),'company').' ';
                    print $links[$key]['label'];
                    print '</a>';
                }
                else if ($links[$key]['type']=='sc') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowSocialContribution'),'bill').' ';
                    print $langs->trans("SocialContribution").($links[$key]['label']?' - '.$links[$key]['label']:'');
                    print '</a>';
                }
                else if ($links[$key]['type']=='payment_sc') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowPayment'),'payment').' ';
                    print $langs->trans("SocialContributionPayment");
                    print '</a>';
                }
                else if ($links[$key]['type']=='payment_vat') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/tva/fiche.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowVAT'),'payment').' ';
                    print $langs->trans("VATPayment");
                    print '</a>';
                }
                else if ($links[$key]['type']=='member') {
                    print '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowMember'),'user').' ';
                    print $links[$key]['label'];
                    print '</a>';
                }
                else if ($links[$key]['type']=='banktransfert') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowTransaction'),'payment').' ';
                    print $langs->trans("TransactionOnTheOtherAccount");
                    print '</a>';
                }
                else {
                    print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
                    print img_object('','generic').' ';
                    print $links[$key]['label'];
                    print '</a>';
                }
            }
            print '</td></tr>';
        }

        $rowspan=0;

        //$user->rights->banque->modifier=false;
        //$user->rights->banque->consolidate=true;

        // Type of payment / Number
        print "<tr><td>".$langs->trans("Type")." / ".$langs->trans("Numero");
        print "</td>";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print $form->select_types_paiements($objp->fk_type,"value",'',2);
            print '<input type="text" class="flat" name="num_chq" value="'.(empty($objp->num_chq) ? '' : $objp->num_chq).'">';
            if ($objp->receiptid)
            {
                include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
                $receipt=new RemiseCheque($db);
                $receipt->fetch($objp->receiptid);
                print ' &nbsp; &nbsp; '.$langs->trans("CheckReceipt").': '.$receipt->getNomUrl(2);

            }
            print '</td>';
            $rowspan=7;
            print '<td align="center" rowspan="'.$rowspan.'" width="20%"><input type="submit" class="button" value="'.$langs->trans("Update").'">';
            print '</td>';
        }
        else
        {
            print '<td colspan="4">'.$objp->fk_type.' '.$objp->num_chq.'</td>';
        }
        print "</tr>";

        // Bank of cheque
        print "<tr><td>".$langs->trans("Bank")."</td>";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print '<input type="text" class="flat" size="40" name="banque" value="'.(empty($objp->banque) ? '' : $objp->banque).'">';
            print '</td>';
        }
        else
        {
            print '<td colspan="'.($rowspan?'3':'4').'">'.$objp->banque.'</td>';
        }
        print "</tr>";

        // Transmitter
        print "<tr><td>".$langs->trans("CheckTransmitter")."</td>";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print '<input type="text" class="flat" size="40" name="emetteur" value="'.(empty($objp->emetteur) ? '' : stripslashes($objp->emetteur)).'">';
            print '</td>';
        }
        else
        {
            print '<td colspan="'.($rowspan?'3':'4').'">'.$objp->emetteur.'</td>';
        }
        print "</tr>";

        // Date ope
        print '<tr><td>'.$langs->trans("DateOperation").'</td>';
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print $form->select_date($db->jdate($objp->do),'dateo','','','','update',1,0,1,$objp->rappro);
            print '</td>';
        }
        else
        {
            print '<td colspan="'.($rowspan?'3':'4').'">';
            print dol_print_date($db->jdate($objp->do),"day");
            print '</td>';
        }
        print '</tr>';

        // Value date
        print "<tr><td>".$langs->trans("DateValue")."</td>";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print $form->select_date($db->jdate($objp->dv),'datev','','','','update',1,0,1,$objp->rappro);
            if (! $objp->rappro)
            {
                print ' &nbsp; ';
                print '<a href="'.$_SERVER['PHP_SELF'].'?action=dvprev&amp;id='.$id.'&amp;rowid='.$objp->rowid.'">';
                print img_edit_remove() . "</a> ";
                print '<a href="'.$_SERVER['PHP_SELF'].'?action=dvnext&amp;id='.$id.'&amp;rowid='.$objp->rowid.'">';
                print img_edit_add() ."</a>";
            }
            print '</td>';
        }
        else
        {
            print '<td colspan="'.($rowspan?'3':'4').'">';
            print dol_print_date($db->jdate($objp->dv),"day");
            print '</td>';
        }
        print "</tr>";

        // Description
        print "<tr><td>".$langs->trans("Label")."</td>";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print '<input name="label" class="flat" '.($objp->rappro?' disabled="disabled"':'').' value="';
            if (preg_match('/^\((.*)\)$/i',$objp->label,$reg))
            {
                // Label generique car entre parentheses. On l'affiche en le traduisant
                print $langs->trans($reg[1]);
            }
            else
            {
                print $objp->label;
            }
            print '" size="50">';
            print '</td>';
        }
        else
        {
            print '<td colspan="'.($rowspan?'3':'4').'">';
            if (preg_match('/^\((.*)\)$/i',$objp->label,$reg))
            {
                // Label generique car entre parentheses. On l'affiche en le traduisant
                print $langs->trans($reg[1]);
            }
            else
            {
                print $objp->label;
            }
            print '</td>';
        }
        print '</tr>';

        // Amount
        print "<tr><td>".$langs->trans("Amount")."</td>";
        if ($user->rights->banque->modifier)
        {
            print '<td colspan="3">';
            print '<input name="amount" class="flat" size="10" '.($objp->rappro?' disabled="disabled"':'').' value="'.price($objp->amount).'"> '.$langs->trans("Currency".$conf->currency);
            print '</td>';
        }
        else
        {
            print '<td colspan="'.($rowspan?'3':'4').'">';
            print price($objp->amount);
            print '</td>';
        }
        print "</tr>";

        print "</table>";
        print "</form>";

        // Releve rappro
        if ($acct->canBeConciliated() > 0)  // Si compte rapprochable
        {
            print '<br>'."\n";
            print_fiche_titre($langs->trans("Reconciliation"),'','');
            print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?rowid='.$objp->rowid.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="setreconcile">';
            print '<input type="hidden" name="orig_account" value="'.$orig_account.'">';

            print '<table class="border" width="100%">';

            print '<tr><td width="20%">'.$langs->trans("Conciliation")."</td>";
            if ($user->rights->banque->consolidate)
            {
                print '<td colspan="3">';
                if ($objp->rappro)
                {
                    print $langs->trans("AccountStatement").' <input name="num_rel_bis" class="flat" value="'.$objp->num_releve.'"'.($objp->rappro?' disabled="disabled"':'').'>';
                    print '<input name="num_rel" type="hidden" value="'.$objp->num_releve.'">';
                }
                else
                {
                    print $langs->trans("AccountStatement").' <input name="num_rel" class="flat" value="'.$objp->num_releve.'"'.($objp->rappro?' disabled="disabled"':'').'>';
                }
                if ($objp->num_releve) print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/compta/bank/releve.php?num='.$objp->num_releve.'&account='.$acct->id.'">'.$langs->trans("AccountStatement").' '.$objp->num_releve.')</a>';
                print '</td><td align="center" rowspan="2" width="20%"><input type="submit" class="button" value="'.$langs->trans("Update").'"></td>';
            }
            else
            {
                print '<td colspan="4">'.$objp->num_releve.'&nbsp;</td>';
            }
            print '</tr>';

            print "<tr><td>".$langs->trans("BankLineConciliated")."</td>";
            if ($user->rights->banque->consolidate)
            {
                print '<td colspan="3">';
                print '<input type="checkbox" name="reconciled" class="flat" '.(isset($_POST["reconciled"])?($_POST["reconciled"]?' checked="checked"':''):($objp->rappro?' checked="checked"':'')).'">';
                print '</td>';
            }
            else
            {
                print '<td colspan="4">'.yn($objp->rappro).'</td>';
            }
            print '</tr>';

            print "</table>";
            print '</form>';
        }

    }

    $db->free($result);
}
else dol_print_error($db);
print '</div>';



// List of bank categories

print '<br>';
print '<table class="noborder" width="100%">';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?rowid='.$rowid.'&amp;id='.$id.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="class">';
print '<input type="hidden" name="orig_account" value="'.$orig_account.'">';
print '<tr class="liste_titre"><td>'.$langs->trans("Rubriques").'</td><td colspan="2">';
if ($user->rights->banque->modifier)
{
    print '<select class="flat" name="cat1">'.$options.'</select>&nbsp;';
    print '<input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
}
print "</tr>";
print "</form>";

$sql = "SELECT c.label, c.rowid";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_class as a, ".MAIN_DB_PREFIX."bank_categ as c";
$sql.= " WHERE a.lineid=".$rowid." AND a.fk_categ = c.rowid";
$sql.= " ORDER BY c.label";
$result = $db->query($sql);
if ($result)
{
    $var=True;
    $num = $db->num_rows($result);
    $i = 0; $total = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);

        $var=!$var;
        print "<tr ".$bc[$var].">";

        print "<td>".$objp->label."</td>";
        print "<td align=\"center\"><a href=\"budget.php?bid=".$objp->rowid."\">".$langs->trans("ListBankTransactions")."</a></td>";
        if ($user->rights->banque->modifier)
        {
            print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=delete_categ&amp;rowid='.$rowid.'&amp;fk_categ='.$objp->rowid.'">'.img_delete($langs->trans("Remove")).'</a></td>';
        }
        print "</tr>";

        $i++;
    }
    $db->free($result);
}
print "</table>";

llxFooter();

$db->close();
?>
