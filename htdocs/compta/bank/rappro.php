<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/compta/bank/rappro.php
        \ingroup    banque
        \brief      Page de rapprochement bancaire
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("banks");

$user->getrights('compta');

if (! $user->rights->banque->modifier) accessforbidden();


llxHeader();


/*
 * Action rapprochement
 */
if ($_POST["action"] == 'rappro')
{
    if ($_POST["num_releve"] > 0)
    {
        $db->begin();
        
        $valrappro=1;
        $sql = "UPDATE ".MAIN_DB_PREFIX."bank";
        $sql.= " set rappro=".$valrappro.", num_releve=".$_POST["num_releve"].",";
        $sql.= " fk_user_rappro=".$user->id;
        $sql.= " WHERE rowid=".$_POST["rowid"];

        $result = $db->query($sql);
        if ($result)
        {
            if ($cat1 && $_POST["action"])
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES ($rowid, $cat1)";
                $result = $db->query($sql);
        
                if ($result)
                {
                    $db->commit();
                }
                else
                {
                    $db->rollback();
                    dolibarr_print_error($db);
                }
            }
            else
            {
                $db->commit();
            }
        }
        else
        {
            $db->rollback();
            dolibarr_print_error($db);
        }
    }
    else {
        $msg="Erreur: Saisissez le relevé qui référence la transaction pour la rapprocher.";
    }
}

/*
* Action suppression ecriture
*/
if ($_GET["action"] == 'del') {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank WHERE rowid=".$_GET["rowid"];
    $result = $db->query($sql);
    if (! $result) {
        dolibarr_print_error($db);
    }
}

$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ ORDER BY label";
$result = $db->query($sql);
$options="";
if ($result) {
    $var=True;
    $num = $db->num_rows($result);
    $i = 0;
    while ($i < $num) {
        if ($options == "") { $options = "<option value=\"0\" selected>&nbsp;</option>"; }
        $obj = $db->fetch_object($result);
        $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n"; $i++;
    }
    $db->free($result);
}


/*
* Affichage liste des transactions à rapprocher
*/
$acct = new Account($db);
$acct->fetch($_GET["account"]);

$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, ".$db->pdate("b.datev")." as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type as type";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b WHERE rappro=0 AND fk_account=".$_GET["account"];
$sql .= " ORDER BY dateo";
$sql .= " ASC LIMIT ".$conf->liste_limit;

$result = $db->query($sql);
if ($result)
{
    $var=True;
    $num = $db->num_rows($result);

    if ($num == 0)
    {
        header("Location: /compta/bank/account.php?account=".$_GET["account"]);
        exit;
    }
    else
    {

        print_titre('Rapprochement compte bancaire : <a href="account.php?account='.$_GET["account"].'">'.$acct->label.'</a>');
        print '<br>';

        if ($msg) {
            print "$msg<br><br>";
        }

        // Affiche nom des derniers relevés
        $nbmax=5;
        $liste="";

        $sql = "SELECT distinct num_releve FROM ".MAIN_DB_PREFIX."bank";
        $sql.= " WHERE fk_account=".$_GET["account"];
        $sql.= " ORDER BY num_releve DESC";
        $sql.= " LIMIT ".($nbmax+1);
        print $langs->trans("LastAccountStatements").' : ';
        $resultr=$db->query($sql);
        if ($resultr)
        {
            $numr=$db->num_rows($resultr);
            $i=0;
            while (($i < $numr) && ($i < $nbmax))
            {
                $objr = $db->fetch_object($resultr);
                $last_releve = $objr->num_releve;
                $i++;
                $liste='<a href="releve.php?account='.$_GET["account"].'&amp;num='.$objr->num_releve.'">'.$objr->num_releve.'</a> &nbsp; '.$liste;
            }
            if ($num >= $nbmax) $liste="... &nbsp; ".$liste;
            print "$liste";
            if ($num > 0) print '<br><br>';
            else print $langs->trans("None").'<br><br>';
        }
        else
        {
            dolibarr_print_error($db);
        }

        print '<table class="noborder" width="100%">';
        print "<tr class=\"liste_titre\">";
        print '<td>'.$langs->trans("Date").'</td>';
        print '<td>'.$langs->trans("DateValue").'</td>';
        print '<td>'.$langs->trans("Type").'</td>';
        print '<td>'.$langs->trans("Description").'</td>';
        print '<td align="right">'.$langs->trans("Debit").'</td>';
        print '<td align="right">'.$langs->trans("Credit").'</td>';
        print '<td align="center" width="60">'.$langs->trans("Action").'</td>';
        print '<td align="center" width="100" colspan="2">'.$langs->trans("AccountStatement").' (Ex: YYYYMM)</td>';
        print "</tr>\n";
    }

    $i = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);

        $var=!$var;
        print "<tr $bc[$var]>";
        print '<form method="post" action="rappro.php?account='.$_GET["account"].'">';
        print "<input type=\"hidden\" name=\"action\" value=\"rappro\">";
        print "<input type=\"hidden\" name=\"account\" value=\"".$_GET["account"]."\">";
        print "<input type=\"hidden\" name=\"rowid\" value=\"".$objp->rowid."\">";

        print '<td nowrap>'.dolibarr_print_date($objp->do).'</td>';
        print '<td nowrap>'.dolibarr_print_date($objp->dv).'</td>';
        print '<td nowrap>'.$objp->type.($objp->num_chq?' '.$objp->num_chq:'').'</td>';
        print '<td>'.$objp->label.'</td>';

        if ($objp->amount < 0)
        {
            print "<td align=\"right\" nowrap>".price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
        }
        else
        {
            print "<td>&nbsp;</td><td align=\"right\" nowrap>".price($objp->amount)."</td>\n";
        }

        if ($objp->rappro)
        {
            // Si ligne déjà rapprochée, on affiche relevé.
            print "<td align=\"center\"><a href=\"releve.php?num=$objp->num_releve&amp;account=$acct->id\">$objp->num_releve</a></td>";
        }
        else
        {
            // Si pas encore rapproché
            if ($user->rights->banque->modifier)
            {
                print '<td align="center" width="30">';

                print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
                print img_edit();
                print '</a>&nbsp; &nbsp;';

                if ($objp->do <= mktime() ) {
                    print '<a href="'.DOL_URL_ROOT.'/compta/bank/rappro.php?action=del&amp;rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
                    print img_delete();
                    print "</a>";
                }
                else {
                    print "&nbsp;";	// On n'empeche la suppression car le raprochement ne pourra se faire qu'après la date passée et que l'écriture apparaissent bien sur le compte.
                }
                print "</td>";
            }
            else
            {
                print "<td align=\"center\">&nbsp;</td>";
            }
        }


        // Affiche bouton "Rapprocher"
        if ($objp->do <= mktime() ) {
            print "<td align=\"center\">";
            print "<input class=\"flat\" name=\"num_releve\" type=\"text\" value=\"\" size=\"8\">";
            if ($options) {
                print "<br><select name=\"cat1\">$options";
                print "</select>";
            }
            print "</td>";
            print "<td align=\"center\"><input class=\"button\" type=\"submit\" value=\"".$langs->trans("Rapprocher")."\">";
            print "</td>";
        }
        else {
            print "<td align=\"left\" colspan=\"2\">";
            print "Ecriture future. Ne peut pas encore être rapprochée.";
            print "</td>";
        }

        print "</tr>";

        print "</form>";
        $i++;
    }
    $db->free($result);

    if ($num != 0) {
        print "</table>";
    }

} else {
    dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
