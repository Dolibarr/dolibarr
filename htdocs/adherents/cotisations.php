<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/adherents/cotisations.php
 *      \ingroup    member
 *		\brief      Page de consultation et insertion d'une cotisation
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/cotisation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("members");

$filter=$_GET["filter"];
$statut=isset($_GET["statut"])?$_GET["statut"]:1;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="c.dateadh"; }

$msg='';
$date_select=isset($_GET["date_select"])?$_GET["date_select"]:$_POST["date_select"];

// Security check
$result=restrictedArea($user,'adherent','','','cotisation');


/*
 *	Actions
 */


/*
 * View
 */

llxHeader('',$langs->trans("ListOfSubscriptions"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

if ($msg)	print $msg.'<br>';

// Liste des cotisations
$sql = "SELECT d.rowid, d.login, d.firstname, d.lastname, d.societe,";
$sql.= " c.rowid as crowid, c.cotisation,";
$sql.= " c.dateadh,";
$sql.= " c.datef,";
$sql.= " c.fk_bank as bank, c.note,";
$sql.= " b.fk_account";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank=b.rowid";
$sql.= " WHERE d.rowid = c.fk_adherent";
if (isset($date_select) && $date_select != '')
{
    $sql.= " AND dateadh LIKE '$date_select%'";
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    $title=$langs->trans("ListOfSubscriptions");
    if (! empty($date_select)) $title.=' ('.$langs->trans("Year").' '.$date_select.')';

    $param="";
    $param.="&amp;statut=$statut&amp;date_select=$date_select";
    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num);


    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Ref"),"cotisations.php","c.rowid",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Name"),"cotisations.php","d.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Login"),"cotisations.php","d.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Label"),"cotisations.php","c.note",$param,"",'align="left"',$sortfield,$sortorder);
    if (! empty($conf->banque->enabled))
    {
        print_liste_field_titre($langs->trans("Account"),"cotisations.php","b.fk_account",$pram,"","",$sortfield,$sortorder);
    }
    print_liste_field_titre($langs->trans("Date"),"cotisations.php","c.dateadh",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateEnd"),"cotisations.php","c.datef",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Amount"),"cotisations.php","c.cotisation",$param,"",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

    // Static objects
    $cotisation=new Cotisation($db);
    $adherent=new Adherent($db);
    $accountstatic=new Account($db);

    $var=true;
    $total=0;
    while ($i < $num && $i < $conf->liste_limit)
    {
        $objp = $db->fetch_object($result);
        $total+=$objp->cotisation;

        $cotisation->ref=$objp->crowid;
        $cotisation->id=$objp->crowid;

        $adherent->lastname=$objp->lastname;
        $adherent->firstname=$objp->firstname;
        $adherent->ref=$adherent->getFullName($langs);
        $adherent->id=$objp->rowid;
        $adherent->login=$objp->login;

        $var=!$var;

        if ($allowinsertbankafter && ! $objp->fk_account && ! empty($conf->banque->enabled) && $objp->cotisation)
        {
            print "<form method=\"post\" action=\"cotisations.php\">";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        }
        print "<tr $bc[$var]>";

        // Ref
        print '<td>'.$cotisation->getNomUrl(1).'</td>';

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
                if ($allowinsertbankafter && $objp->cotisation)
                {
                    print '<input type="hidden" name="action" value="2bank">';
                    print '<input type="hidden" name="rowid" value="'.$objp->crowid.'">';
                    $form = new Form($db);
                    $form->select_comptes('','accountid',0,'',1);
                    print '<br>';
                    $form->select_types_paiements('','paymenttypeid');
                    print '<input name="num_chq" type="text" class="flat" size="5">';
                }
                else
                {
                    print '&nbsp;';
                }
                print "</td>\n";
            }
        }

        // Date start
        print '<td align="center">'.dol_print_date($db->jdate($objp->dateadh),'day')."</td>\n";

        // Date end
        print '<td align="center">'.dol_print_date($db->jdate($objp->datef),'day')."</td>\n";

        // Price
        print '<td align="right">'.price($objp->cotisation).'</td>';

        print "</tr>";
        if ($allowinsertbankafter && ! $objp->fk_account && ! empty($conf->banque->enabled) && $objp->cotisation)
        {
            print "</form>\n";
        }
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
   	print "<td align=\"right\">".price($total)."</td>\n";
    print "</tr>\n";

    print "</table>";
    print "<br>\n";


}
else
{
    dol_print_error($db);
}


$db->close();

llxFooter();
?>
