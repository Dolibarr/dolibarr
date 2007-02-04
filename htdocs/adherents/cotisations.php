<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo <jlb@j1b.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");
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


        $dateop=time();
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


/*
 * Affichage liste
 */

llxHeader();


// Liste des cotisations
$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe,";
$sql.= " c.cotisation, ".$db->pdate("c.dateadh")." as dateadh, c.fk_bank as bank, c.rowid as crowid,";
$sql.= " b.fk_account";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank=b.rowid";
$sql.= " WHERE d.rowid = c.fk_adherent";
if (isset($date_select) && $date_select != '')
{
  $sql.= " AND dateadh LIKE '$date_select%'";
}
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result) 
{
    $num = $db->num_rows($result);
    $i = 0;


    $param.="&amp;statut=$statut&amp;date_select=$date_select";
	print_barre_liste($langs->trans("ListOfSubscriptions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num);


    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Ref"),"cotisations.php","c.rowid",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Date"),"cotisations.php","c.dateadh",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Name"),"cotisations.php","d.nom",$param,"","",$sortfield);
    if ($conf->global->ADHERENT_BANK_USE)
    {
        print_liste_field_titre($langs->trans("Bank"),"cotisations.php","b.fk_account",$pram,"","",$sortfield);
    }
    print_liste_field_titre($langs->trans("Amount"),"cotisations.php","c.cotisation",$param,"","align=\"right\"",$sortfield);
    print "</tr>\n";

    $var=true;
    $total=0;
    while ($i < $num && $i < $conf->liste_limit)
    {
        $objp = $db->fetch_object($result);
        $total+=price($objp->cotisation);

        $cotisation=new Cotisation($db);
        $cotisation->ref=$objp->crowid;
        $cotisation->id=$objp->crowid;

        $adherent=new Adherent($db);
        $adherent->ref=trim($objp->prenom.' '.$objp->nom);
        $adherent->id=$objp->rowid;

        $var=!$var;
        print "<tr $bc[$var]>";
        print '<td>'.$cotisation->getNomUrl(1).'</td>';
        print '<td>'.dolibarr_print_date($objp->dateadh)."</td>\n";
        print '<td>'.$adherent->getNomUrl(1).'</td>';
        if ($conf->global->ADHERENT_BANK_USE)
        {
            if ($objp->fk_account)
            {
                $accountstatic=new Account($db);
                $accountstatic->id=$objp->fk_account;
                $accountstatic->fetch($objp->fk_account);
                //$accountstatic->label=$objp->label;
                print '<td>'.$accountstatic->getNomUrl(1).'</td>';
            }
            else
            {
                print "<td>";
                print "<form method=\"post\" action=\"cotisations.php\">";
                print '<input type="hidden" name="action" value="2bank">';
                print '<input type="hidden" name="rowid" value="'.$objp->crowid.'">';
                $html = new Form($db);
                $html->select_types_paiements();
                print '<input name="num_chq" type="text" class="flat" size="6">&nbsp;-&nbsp;';
                print "<input name=\"label\" type=\"text\" class=\"flat\" size=\"30\" value=\"".$langs->trans("Subscriptions").' '.stripslashes($objp->prenom)." ".stripslashes($objp->nom)." ".strftime("%Y",$objp->dateadh)."\" >\n";
                //	print "<td><input name=\"debit\" type=\"text\" size=8></td>";
                //	print "<td><input name=\"credit\" type=\"text\" size=8></td>";
                print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
                print "</form>\n";
                print "</td>\n";
            }
        }
        print '<td align="right">'.price($objp->cotisation).'</td>';
        print "</tr>";
        $i++;
    }

    $var=!$var;
    print '<tr class="liste_total">';
    print "<td>".$langs->trans("Total")."</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    if ($conf->global->ADHERENT_BANK_USE)
    {
    	print '<td>&nbsp;</td>';
    }
    print "<td align=\"right\">".price($total)."</td>\n";
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
