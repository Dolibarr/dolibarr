<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */
require("./pre.inc.php");
require("./bank.lib.php");

$user->getrights('banque');

if (!$user->rights->banque->modifier)
  accessforbidden();

llxHeader();

if ($action == 'add')
{
    /*
     * Ajout d'un virement
     */
  $mesg='';
  $dateo = $_POST["$reyear"]."-".$_POST["$remonth"]."-".$_POST["$reday"];
  $label = $_POST["label"];
  $amount= $_POST["amount"];
  
  if ($label && $amount) {

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (datec, datev, dateo, label, amount, fk_user_author,fk_account, fk_type)";
      $sql .= " VALUES (now(), now(), '$dateo', '$label', (0 - $amount),$user->id,$account_from, 'VIR')";
    
      $result = $db->query($sql);
      if (!$result)
        {
          print "Erreur: $sql :".$db->error();
        }
    
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (datec, datev, dateo, label, amount, fk_user_author,fk_account, fk_type)";
      $sql .= " VALUES (now(), now(), '$dateo', '$label', $amount,$user->id, $account_to, 'VIR')";
    
      $result = $db->query($sql);
      if ($result)
        {
            $mesg.="<div class=\"ok\"><b>Votre virement de $amount ".MAIN_MONNAIE." a été crée.</b></div>";
        }
      else {
          print "Erreur: $sql :".$db->error();
        } 
  } else {
      $mesg.="<div class=\"error\"><b>Un libellé de virement et un montant non nul est obligatoire.</b></div>";
  }
}

print_titre("Virement inter-compte");
print '<br>';

if ($mesg) {
    print "$mesg</div><br>";
}

print "En saisissant un virement d'un de vos comptes bancaire vers un autre, Dolibarr crée deux écritures comptables (une de débit dans un compte et l'autre de crédit, du même montant, dans l'autre compte. Le même libellé de transaction, et la même date, sont utilisés pour les 2 écritures)<br><br>";

print "<form method=\"post\" action=\"virement.php\">";

print '<input type="hidden" name="action" value="add">';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="2">';
print '<tr class="liste_titre">';
print '<td>De</td><td>Vers</td><td>Date</td><td>Libelle</td><td>Montant</td>';
print '</tr>';
print "<tr $bc[1]><td>";
print "<select name=\"account_from\">";
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
    $i = 0; $total = 0;
    
    while ($i < $num)
      {
	$objp = $db->fetch_object($i);
	print "<option value=\"$objp->rowid\">$objp->label</option>";
	$i++;
      }
}
print "</select></td><td>\n";

print "<select name=\"account_to\">";
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
    $i = 0; $total = 0;
    
    while ($i < $num)
      {
	$objp = $db->fetch_object($i);
	print "<option value=\"$objp->rowid\">$objp->label</option>";
	$i++;
      }
}
print "</select></td>\n";

print "<td>";
print_date_select();
print "</td>\n";
print '<td><input name="label" type="text" size="40"></td>';
print '<td><input name="amount" type="text" size="8"></td>';

print "<tr $bc[1]>";
print '<td colspan="5" align="center"><input type="submit" value="'.$langs->trans("Add").'"</td></tr>';

print "</table></form>";

$db->close();

llxFooter(" - <em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
