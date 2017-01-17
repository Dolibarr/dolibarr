<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/adherents/subscription/list.php
 *      \ingroup    member
 *      \brief      list of subscription
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("members");

$filter=$_GET["filter"];
$statut=isset($_GET["statut"])?$_GET["statut"]:1;
$search_ref=GETPOST('search_ref','alpha');
$search_lastname=GETPOST('search_lastname','alpha');
$search_login=GETPOST('search_login','alpha');
$search_note=GETPOST('search_note','alpha');
$search_account=GETPOST('search_account','int');
$search_amount=GETPOST('search_amount','alpha');
$optioncss = GETPOST('optioncss','alpha');

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="c.dateadh"; }

$date_select=isset($_GET["date_select"])?$_GET["date_select"]:$_POST["date_select"];

// Security check
$result=restrictedArea($user,'adherent','','','cotisation');


/*
 *	Actions
 */

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search="";
	$search_ref="";
    $search_lastname="";
	$search_firstname="";
	$search_login="";
    $search_note="";
	$search_amount="";
	$search_account="";
}


/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("ListOfSubscriptions"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');


// List of subscriptions
$sql = "SELECT d.rowid, d.login, d.firstname, d.lastname, d.societe,";
$sql.= " c.rowid as crowid, c.subscription,";
$sql.= " c.dateadh,";
$sql.= " c.datef,";
$sql.= " c.fk_bank as bank, c.note,";
$sql.= " b.fk_account";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."subscription as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank=b.rowid";
$sql.= " WHERE d.rowid = c.fk_adherent";
$sql.= " AND d.entity IN (".getEntity('adherent', 1).")";
if (isset($date_select) && $date_select != '')
{
    $sql.= " AND c.dateadh LIKE '".$date_select."%'";
}
if ($search_ref)
{
	if (is_numeric($search_ref)) $sql.= " AND (c.rowid = ".$db->escape($search_ref).")";
	else $sql.=" AND 1 = 2";    // Always wrong
}
if ($search_lastname) $sql.= natural_search(array('d.firstname','d.lastname','d.societe'), $search_lastname);
if ($search_login) $sql.= natural_search('c.subscription', $search_login);
if ($search_note)  $sql.= natural_search('c.note', $search_note);
if ($search_account > 0) $sql.= " AND b.fk_account = ".$search_account;
if ($search_amount) $sql.= natural_search('c.subscription', $search_amount, 1);
$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    $title=$langs->trans("ListOfSubscriptions");
    if (! empty($date_select)) $title.=' ('.$langs->trans("Year").' '.$date_select.')';

    $param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
    if ($statut != '')    $param.="&statut=".$statut;
    if ($date_select)     $param.="&date_select=".$date_select;
    if ($search_lastname) $param.="&search_lastname=".$search_lastname;
	if ($search_login)    $param.="&search_login=".$search_login;
	if ($search_acount)   $param.="&search_account=".$search_account;
	if ($search_amount)   $param.="&search_amount=".$search_amount;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
    
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

	if ($sall)
	{
		print $langs->trans("Filter")." (".$langs->trans("Ref").", ".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
	}

    $moreforfilter = '';
    
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"c.rowid",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Name"),$_SERVER["PHP_SELF"],"d.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Login"),$_SERVER["PHP_SELF"],"d.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"c.note",$param,"",'align="left"',$sortfield,$sortorder);
    if (! empty($conf->banque->enabled))
    {
        print_liste_field_titre($langs->trans("Account"),$_SERVER["PHP_SELF"],"b.fk_account",$pram,"","",$sortfield,$sortorder);
    }
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"c.dateadh",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],"c.datef",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"c.subscription",$param,"",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('');
    print "</tr>\n";


	// Line for filters fields
	print '<tr class="liste_titre">';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_ref" value="'.$search_ref.'" size="4"></td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_lastname" value="'.$search_lastname.'" size="12"></td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_login" value="'.$search_login.'" size="7"></td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_note" value="'.$search_note.'" size="7"></td>';

    if (! empty($conf->banque->enabled))
    {
		print '<td class="liste_titre">';
		print $form->select_comptes($search_account, 'search_account', 0, '', 1);
		print '</td>';
    }

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td align="right" class="liste_titre">';
	print '<input class="flat" type="text" name="search_amount" value="'.$search_amount.'" size="4">';
	print '</td>';
	
    // Action column
    print '<td class="liste_titre" align="right">';
    $searchpitco=$form->showFilterAndCheckAddButtons(0);
    print $searchpitco;
    print '</td>';  

	print "</tr>\n";


    // Static objects
    $subscription=new Subscription($db);
    $adherent=new Adherent($db);
    $accountstatic=new Account($db);

    $var=true;
    $total=0;
    while ($i < min($num, $limit))
    {
        $objp = $db->fetch_object($result);
        $total+=$objp->subscription;

        $subscription->ref=$objp->crowid;
        $subscription->id=$objp->crowid;

        $adherent->lastname=$objp->lastname;
        $adherent->firstname=$objp->firstname;
        $adherent->ref=$adherent->getFullName($langs);
        $adherent->id=$objp->rowid;
        $adherent->login=$objp->login;

        $var=!$var;

        print "<tr ".$bc[$var].">";

        // Ref
        print '<td>'.$subscription->getNomUrl(1).'</td>';

        // Lastname
        print '<td>'.$adherent->getNomUrl(1).'</td>';

        // Login
        print '<td>'.$adherent->login.'</td>';

        // Libelle
        print '<td>';
        print dol_trunc($objp->note,32);
        print '</td>';

        // Banque
        if (! empty($conf->banque->enabled))
        {
            if ($objp->fk_account)
            {
                $accountstatic->id=$objp->fk_account;
                $accountstatic->fetch($objp->fk_account);
                //$accountstatic->label=$objp->label;
                print '<td>'.$accountstatic->getNomUrl(1).'</td>';
            }
            else
            {
                print "<td>";
                print "</td>\n";
            }
        }

        // Date start
        print '<td align="center">'.dol_print_date($db->jdate($objp->dateadh),'day')."</td>\n";

        // Date end
        print '<td align="center">'.dol_print_date($db->jdate($objp->datef),'day')."</td>\n";

        // Price
        print '<td align="right">'.price($objp->subscription).'</td>';
        
        print '<td></td>';

        print "</tr>";

        $i++;
    }

    // Total
    $var=!$var;
    print '<tr class="liste_total">';
    print "<td>".$langs->trans("Total")."</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    if (! empty($conf->banque->enabled))
    {
        print '<td>&nbsp;</td>';
    }
   	print '<td>&nbsp;</td>';
   	print '<td>&nbsp;</td>';
   	print '<td align="right">'.price($total)."</td>\n";
   	print '<td></td>';
    print "</tr>\n";

    print "</table>";
    print '</div>';
	print '</form>';
}
else
{
    dol_print_error($db);
}


llxFooter();
$db->close();
