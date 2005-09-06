<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/adherents/cotisations.php
        \ingroup    adherent
		\brief      Page de consultation et insertion d'une cotisation
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];
$filter=$_GET["filter"];
$statut=isset($_GET["statut"])?$_GET["statut"]:1;

if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="c.dateadh"; }
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$date_select=isset($_GET["date_select"])?$_GET["date_select"]:$_POST["date_select"];



// Insertion de la cotisation dans le compte banquaire
if ($_POST["action"] == '2bank' && $_POST["rowid"] !='')
{
    if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0)
    {

        // \todo    Créer une facture et enregistrer son paiement


        $dateop=strftime("%Y%m%d",time());
        $sql="SELECT cotisation FROM ".MAIN_DB_PREFIX."cotisation WHERE rowid=".$_POST["rowid"]." ";
        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            if ($num>0)
            {
                $objp = $db->fetch_object($result);
                $amount=$objp->cotisation;
                $acct=new Account($db,ADHERENT_BANK_ACCOUNT);
                $insertid=$acct->addline($dateop, $_POST["operation"], $_POST["label"], $amount, $_POST["num_chq"],ADHERENT_BANK_CATEGORIE,$user);
                if ($insertid == '')
                {
                    dolibarr_print_error($db);
                }
                else
                {
                    // met a jour la table cotisation
                    $sql="UPDATE ".MAIN_DB_PREFIX."cotisation SET fk_bank=$insertid WHERE rowid=".$_POST["rowid"]." ";
                    $result = $db->query($sql);
                    if ($result)
                    {
                        //Header("Location: cotisations.php");
                    }
                    else
                    {
                        dolibarr_print_error($db);
                    }
                }
            }
            else
            {
                dolibarr_print_error($db);
            }
        }
        else
        {
            dolibarr_print_error($db);
        }

    }
}




llxHeader();


$params="&amp;select_date=".$select_date;
print_barre_liste($langs->trans("ListOfSubscriptions"), $page, "cotisations.php", $params, $sortfield, $sortorder,'',$num);


// Liste des cotisations
$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, b.fk_account,";
$sql.= " c.cotisation, ".$db->pdate("c.dateadh")." as dateadh, c.fk_bank as bank, c.rowid as crowid,";
$sql.= " b.fk_account";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank=b.rowid";
$sql.= " WHERE d.rowid = c.fk_adherent";
if(isset($date_select) && $date_select != '')
{
  $sql .= " AND dateadh LIKE '$date_select%'";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
    $num = $db->num_rows($result);
    $i = 0;

    print '<table class="noborder" width="100%">';

    $param="&page=$page&statut=$statut&amp;date_select=$date_select";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Date"),"cotisations.php","c.dateadh",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Name"),"cotisations.php","d.nom",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Amount"),"cotisations.php","c.cotisation",$param,"","align=\"right\"",$sortfield);
    if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0)
    {
        print_liste_field_titre($langs->trans("Bank"),"cotisations.php","b.fk_account",$pram,"","",$sortfield);
    }
    print "</tr>\n";

    $var=true;
    $total=0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);
        $total+=price($objp->cotisation);

        $var=!$var;
        print "<tr $bc[$var]>";
        print "<td>".dolibarr_print_date($objp->dateadh)."</td>\n";
        print "<td><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".img_object($langs->trans("ShowMember"),"user").' '.stripslashes($objp->prenom)." ".stripslashes($objp->nom)."</a></td>\n";
        print '<td align="right">'.price($objp->cotisation).'</td>';
        if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0)
        {
            if ($objp->fk_account)
            {
                $acc=new Account($db);
                $acc->fetch($objp->fk_account);
                print '<td><a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->fk_account.'">'.$acc->label.'</a></td>';
            }
            else
            {
                print "<td>";
                print "<form method=\"post\" action=\"cotisations.php\">";
                print '<input type="hidden" name="action" value="2bank">';
                print '<input type="hidden" name="rowid" value="'.$objp->crowid.'">';
                $html = new Form($db);
                $html->select_types_paiements();
                print '<input name="num_chq" type="text" size="6">&nbsp;-&nbsp;';
                print "<input name=\"label\" type=\"text\" size=20 value=\"".$langs->trans("Subscriptions").' '.stripslashes($objp->prenom)." ".stripslashes($objp->nom)." ".strftime("%Y",$objp->dateadh)."\" >\n";
                //	print "<td><input name=\"debit\" type=\"text\" size=8></td>";
                //	print "<td><input name=\"credit\" type=\"text\" size=8></td>";
                print '<input type="submit" value="'.$langs->trans("Save").'">';
                print "</form>\n";
                print "</td>\n";
            }
        }
        print "</tr>";
        $i++;
    }

    $var=!$var;
    print '<tr class="liste_total">';
    print "<td>".$langs->trans("Total")."</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">".price($total)."</td>\n";
    print '<td>&nbsp;</td>';
    print "</tr>\n";
    print "</table>";
    print "<br>\n";


}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
