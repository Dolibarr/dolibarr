<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) accessforbidden();

if ($_POST["action"] == 'confirm_credite' && $_POST["confirm"] == yes)
{
  $bon = new BonPrelevement($db,"");
  $bon->id = $_GET["id"];
  $bon->set_credite();

  Header("Location: fiche.php?id=".$_GET["id"]);
}

if ($_POST["action"] == 'infotrans')
{
  $bon = new BonPrelevement($db,"");
  $bon->id = $_GET["id"];
  $dt = mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

  $bon->set_infotrans($user, $dt, $_POST["methode"]);

  Header("Location: fiche.php?id=".$_GET["id"]);
}

if ($_POST["action"] == 'infocredit')
{
  $bon = new BonPrelevement($db,"");
  $bon->id = $_GET["id"];
  $dt = mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

  $bon->set_infocredit($user, $dt);

  Header("Location: fiche.php?id=".$_GET["id"]);
}

llxHeader('','Bon de prélèvement');

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Fiche");
$hselected = $h;
$h++;      

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Factures");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistiques");
$h++;  

$prev_id = $_GET["id"];

$html = new Form($db);

if ($_GET["id"])
{
  $bon = new BonPrelevement($db,"");

  if ($bon->fetch($_GET["id"]) == 0)
    {
      dolibarr_fiche_head($head, $hselected, 'Prélèvement : '. $bon->ref);

      if ($_GET["action"] == 'credite')
	{
	  $html->form_confirm("fiche.php?id=".$bon->id,"Classer comme crédité","Etes-vous sûr de vouloir classer ce bon de prélèvement comme crédité sur votre compte bancaire ?","confirm_credite");
	  print '<br />';
	}

      print '<table class="border" width="100%">';

      print '<tr><td width="20%">Référence</td><td>'.$bon->ref.'</td></tr>';
      print '<tr><td width="20%">Date</td><td>'.strftime("%d %b %Y",$bon->datec).'</td></tr>';
      print '<tr><td width="20%">Montant</td><td>'.price($bon->amount).'</td></tr>';
      print '<tr><td width="20%">Fichier</td><td>';

      $encfile = urlencode(DOL_DATA_ROOT.'/prelevement/bon/'.$bon->ref);

      print '<a href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;file='.$encfile.'">'.$bon->ref.'</a>';

      print '</td></tr>';

      if($bon->date_trans <> 0)
	{
	  $muser = new User($db, $bon->user_trans);
	  $muser->fetch();

	  print '<tr><td width="20%">Date Transmission / Par</td><td>';
	  print strftime("%d %b %Y",$bon->date_trans);
	  print ' par '.$muser->fullname.'</td></tr>';
	  print '<tr><td width="20%">Méthode Transmission</td><td>';
	  print $bon->methodes_trans[$bon->method_trans];
	  print '</td></tr>';
	}
      if($bon->date_credit <> 0)
	{
	  print '<tr><td width="20%">Crédité le</td><td>';
	  print strftime("%d %b %Y",$bon->date_credit);
	  print '</td></tr>';
	}

      print '</table><br />';

      if($bon->date_trans == 0)
	{
	  print '<form method="post" action="fiche.php?id='.$bon->id.'">';
	  print '<input type="hidden" name="action" value="infotrans">';
	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">Date Transmission</td><td>';
	  print $html->select_date();
	  print '</td></tr>';
	  print '<tr><td width="20%">Méthode Transmission</td><td>';
	  print $html->select_array("methode",$bon->methodes_trans);
	  print '</td></tr>';
	  print '<tr><td colspan="2" align="center">';
	  print '<input type="submit">';
	  print '</td></tr>';
	  print '</table></form>';
	}

      if($bon->date_trans <> 0 && $bon->date_credit == 0)
	{
	  print '<form method="post" action="fiche.php?id='.$bon->id.'">';
	  print '<input type="hidden" name="action" value="infocredit">';
	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">Crédité le</td><td>';
	  print $html->select_date();
	  print '</td></tr>';
	  print '<tr><td colspan="2" align="center">';
	  print '<input type="submit">';
	  print '</td></tr>';
	  print '</table></form>';
	}

    }
  else
    {
      print "Erreur";
    }
}

/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n</div>\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{  
  
  if ($bon->credite == 0)
    {      
      print "<a class=\"tabAction\" href=\"fiche.php?action=credite&amp;id=$bon->id\">".$langs->trans("Classer crédité")."</a>";
    }


      
}

print "</div>";


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
