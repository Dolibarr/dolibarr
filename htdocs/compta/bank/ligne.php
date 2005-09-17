<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier DUTOIT        <doli@sydesy.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
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
        \file       htdocs/compta/bank/ligne.php
        \ingroup    compta
		\brief      Page édition d'une écriture bancaire
		\version    $Revision$
*/

require("./pre.inc.php");

if (!$user->rights->banque->modifier)
  accessforbidden();

$langs->load("banks");


$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];

$html = new Form($db);


/*
 * Actions
 */

if ($_GET["action"] == 'dvnext')
{
  $ac = new Account($db);
  $ac->datev_next($_GET["rowid"]);
}

if ($_GET["action"] == 'dvprev')
{
  $ac = new Account($db);
  $ac->datev_previous($_GET["rowid"]);
}

if ($_POST["action"] == 'confirm_delete_categ' && $_POST["confirm"] == "yes")
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = $rowid AND fk_categ = ".$_GET["cat1"];
  if (! $db->query($sql))
    {
      dolibarr_print_error($db);
    }
}

if ($_POST["action"] == 'class')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = $rowid AND fk_categ = ".$_POST["cat1"];
  if (! $db->query($sql))
    {
      dolibarr_print_error($db);
    }

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES (".$_GET["rowid"].", ".$_POST["cat1"].")";
  if (! $db->query($sql))
    {
      dolibarr_print_error($db);
    }
}

if ($_POST["action"] == "update")
{
	// Avant de modifier la date ou le montant, on controle si ce n'est pas encore rapproche
	$sql = "SELECT b.rappro FROM ".MAIN_DB_PREFIX."bank as b WHERE rowid=".$rowid;
	$result = $db->query($sql);
	if ($result)
	{
		$objp = $db->fetch_object($result);
		if ($objp->rappro)
			die ("Vous ne pouvez pas modifier une écriture déjà rapprochée");
	}
	if (!empty($_POST['amount']))
	{
		$amount = str_replace(' ','',$_POST['amount']);
		$dateop = $_POST["reyear"].'-'.$_POST["remonth"].'-'.$_POST["reday"]; 
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank set label='".$_POST["label"]."', dateo = '".$dateop."', amount='$amount' WHERE rowid = $rowid;";
	}
	else
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank set label='".$_POST["label"]."', dateo = '".$dateop."' WHERE rowid = $rowid;";

	$result = $db->query($sql);
	if (! $result)
	{
		dolibarr_print_error($db);
	}
}

if ($_POST["action"] == 'type')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."bank set fk_type='".$_POST["value"]."', num_chq='".$_POST["num_chq"]."' WHERE rowid = $rowid;";
  $result = $db->query($sql);
}

if ($_POST["action"] == 'num_releve')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."bank set num_releve=".$_POST["num_rel"]." WHERE rowid = $rowid;";
  $result = $db->query($sql);
}


/*
 * Affichage fiche ligne ecriture en mode edition
 */
 
llxHeader();

// On initialise la liste des categories
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ;";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows($result);
  $i = 0;
  $options = "<option value=\"0\" selected></option>";
  while ($i < $num)
    {
      $obj = $db->fetch_object($result);
      $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n";
      $i++;
    }
  $db->free($result);
}

$var=False;
$h=0;


$head[$h][0] = DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$_GET["rowid"];
$head[$h][1] = $langs->trans('Card');
$hselected=$h;
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans('LineRecord').' : '.$_GET["rowid"]);


$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do,".$db->pdate("b.datev")." as dv, b.amount, b.label, b.rappro,";
$sql.= " b.num_releve, b.fk_user_author, b.num_chq, b.fk_type, b.fk_account";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql.= " WHERE rowid=".$rowid;
$sql.= " ORDER BY dateo ASC";
$result = $db->query($sql);
if ($result)
{
    $i = 0; $total = 0;
    if ($db->num_rows($result))
    {

        // Confirmations
        if ($_GET["action"] == 'delete_categ')
        {
            $html->form_confirm("ligne.php?rowid=".$_GET["rowid"]."&amp;cat1=".$_GET["fk_categ"],"Supprimer dans la catégorie","Etes-vous sûr de vouloir supprimer le classement dans la catégorie ?","confirm_delete_categ");
            print '<br>';
        }

        print '<table class="border" width="100%">';

        $objp = $db->fetch_object($result);
        $total = $total + $objp->amount;

        $acct=new Account($db,$objp->fk_account);
        $acct->fetch($objp->fk_account);
        $account = $acct->id;

        // Account
        print "<tr><td>".$langs->trans("Account")."</td><td colspan=\"3\"><a href=\"account.php?account=$account\">".$acct->label."</a></td></tr>";

        print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
        print "<input type=\"hidden\" name=\"action\" value=\"update\">";

        // Date
        if (! $objp->rappro)
        {
            print "<tr><td>".$langs->trans("Date")."</td><td colspan=\"3\">";
            $html->select_date($objp->do);
            print '&nbsp; <input type="submit" class="button" value="'.$langs->trans("Update").'"></td>';
            print "</tr>";
        }

        // Value date
        print "<tr>";
        print '<td colspan="2">'.$langs->trans("DateValue").'</td><td colspan="2">'.strftime("%d %b %Y",$objp->dv)." &nbsp; ";
        print '<a href="ligne.php?action=dvprev&amp;account='.$_GET["account"].'&amp;rowid='.$objp->rowid.'">';
        print img_edit_remove() . "</a> ";
        print '<a href="ligne.php?action=dvnext&amp;account='.$_GET["account"].'&amp;rowid='.$objp->rowid.'">';
        print img_edit_add() ."</a></td>";
        print '</tr>';

        // Description
        print "<tr><td>".$langs->trans("Label")."</td><td colspan=\"3\">";
        print '<input name="label" class="flat" value="'.$objp->label.'" size="50">';
        print '&nbsp; <input type="submit" class="button" value="'.$langs->trans("Update").'"></td>';
        print "</tr>";

        // Amount
        if (! $objp->rappro)
        {
            print "<tr><td>".$langs->trans("Amount")."</td><td colspan=\"3\">";
            print '<input name="amount" class="flat" value="'.price($objp->amount).'">';
            print '&nbsp; <input type="submit" class="button" value="'.$langs->trans("Update").'"></td>';
            print "</tr>";
        }

        print "</form>";

        // Type paiement
        print "<tr><td>".$langs->trans("Type")."</td><td colspan=\"3\">";
        print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
        print '<input type="hidden" name="action" value="type">';
        print $html->select_types_paiements($objp->fk_type,"value",'',2);
        print '<input type="text" class="flat" name="num_chq" value="'.(empty($objp->num_chq) ? '' : $objp->num_chq).'">';
        print '&nbsp; <input type="submit" class="button" value="'.$langs->trans("Update").'">';
        print "</form>";
        print "</td></tr>";

        // Author
        print "<tr><td>".$langs->trans("Author")."</td>";
        if ($objp->fk_user_author) {
            $author=new User($db,$objp->fk_user_author);
            $author->fetch();
            print "<td colspan=\"3\">".$author->fullname."</td>";
            } else {
                print "<td colspan=\"3\">&nbsp;</td>";
            }
            print "</tr>";

            $i++;
        }

        // Releve rappro
        if ($acct->rappro)
        {
            print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
            print '<input type="hidden" name="action" value="num_releve">';
            print "<tr><td>".$langs->trans("Conciliation")."</td><td colspan=\"3\">";
            print $langs->trans("AccountStatement").' <input name="num_rel" class="flat" value="'.$objp->num_releve.'">';
            print '&nbsp; <input type="submit" class="button" value="'.$langs->trans("Update").'"></td>';
            print "</tr>";
            print "</form>";
        }

        print "</table>";

        $db->free($result);
    }
print '</div>';

print '<br>';


// Liste les categories

print '<table class="noborder" width="100%">';

print "<form method=\"post\" action=\"ligne.php?rowid=$rowid&amp;account=$account\">";
print "<input type=\"hidden\" name=\"action\" value=\"class\">";
print "<tr class=\"liste_titre\"><td>".$langs->trans("Categories")."</td><td colspan=\"2\">";
print "<select class=\"flat\" name=\"cat1\">$options";
print "</select>&nbsp;";
print '<input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
print "</tr>";
print "</form>";

$sql = "SELECT c.label, c.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."bank_class as a, ".MAIN_DB_PREFIX."bank_categ as c WHERE a.lineid=$rowid AND a.fk_categ = c.rowid ";
$sql .= " ORDER BY c.label";
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
      print "<tr $bc[$var]>";
      
      print "<td>$objp->label</td>";
      print "<td align=\"center\"><a href=\"budget.php?bid=$objp->rowid\">".$langs->trans("List")."</a></td>";
      print "<td align=\"center\"><a href=\"ligne.php?action=delete_categ&amp;rowid=$rowid&amp;fk_categ=$objp->rowid\">".img_delete($langs->trans("Remove"))."</a></td>";
      print "</tr>";

      $i++;
    }
  $db->free($result);
}
print "</table>";


$db->close();

llxFooter('$Date$ - $Revision$');
?>
