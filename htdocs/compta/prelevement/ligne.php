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

require_once DOL_DOCUMENT_ROOT."/compta/prelevement/rejet-prelevement.class.php";
require_once DOL_DOCUMENT_ROOT."/paiement.class.php";
require_once DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php";

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) accessforbidden();

if ($_POST["action"] == 'confirm_rejet')
{
  if ( $_POST["confirm"] == 'yes')
    {
      if ($_POST["motif"] > 0)
	{
	  $rej = new RejetPrelevement($db, $user);
	  
	  $rej->create($user, $_GET["id"], $_GET["socid"], $_GET["previd"], $_POST["motif"]);
	  
	  Header("Location: lignes.php?id=".$_GET["id"]);
	}
      else
	{
	  Header("Location: lignes.php?id=".$_GET["id"]."&action=rejet&socid=".$_GET["socid"]."&previd=".$_GET["previd"]);
	}
    }
  else
    {
      Header("Location: lignes.php?id=".$_GET["id"]);
    }
}


llxHeader('','Prélèvement');

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Fiche");
$hselected = $h;
$h++;      

if ($_GET["id"])
{
  dolibarr_fiche_head($head, $hselected, 'Prélèvement : '. $bon->ref);


  print '<table class="border" width="100%">';
  
  print '<tr><td width="20%">Référence</td><td>'.$bon->ref.'</td></tr>';
  
  print '</table><br />';

}

if ($_GET["action"] == 'rejet')
{
  $html = new Form($db);

  $soc = new Societe($db);
  $soc->fetch($_GET["socid"]);

  //$html->form_confirm("factures.php"."?id=".$_GET["id"]."&amp;socid=".$_GET["socid"]."&amp;previd=".$_GET["previd"],"Rejet de prélèvement","Etes-vous sûr de vouloir saisir un rejet de prélèvement pour la société ".$soc->nom." ?","confirm_rejet");

  $rej = new RejetPrelevement($db, $user);

  print '<form method="post" action="lignes.php?id='.$_GET["id"]."&amp;socid=".$_GET["socid"]."&amp;previd=".$_GET["previd"].'">';
  print '<input type="hidden" name="action" value="confirm_rejet">';
  print '<table class="border" width="100%">';
  print '<tr><td colspan="3">Rejet de prélèvement</td></tr>';
  print '<tr><td class="valid">Etes-vous sûr de vouloir saisir un rejet de prélèvement pour la société '.$soc->nom.' ?</td>';
  print '<td colspan="2" class="valid">';
  print '<select name="confirm">';
  print '<option value="yes">oui</option>';
  print '<option value="no" selected>non</option>';
  print '</select>';
  print '</td></tr>';

  print '<tr><td class="valid">Motif du rejet</td>';
  print '<td class="valid">';
  print '<select name="motif">';
  print '<option value="0">(Motif du Rejet)</option>';

  foreach($rej->motifs as $key => $value)
    {
      print '<option value="'.$key.'">'.$value.'</option>';
    }
  print '</select>';
  print '</td>';
  print '<td class="valid" align="center">';
  print '<input type="submit" value="Confirmer"></td></tr>';

  print '</table></form>';




  print '<table class="border" width="100%">';
  
  print '<tr><td width="20%">Référence</td><td>'.$bon->ref.'</td></tr>';

  print '</table><br />';
}

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="pl.fk_soc";
}

/*
 * Liste des factures
 *
 *
 */
$sql = "SELECT pf.rowid";
$sql .= " ,f.rowid as facid, f.facnumber as ref, f.total_ttc";
$sql .= " , s.idp, s.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE pf.fk_prelevement_lignes = pl.rowid";
$sql .= " AND pl.fk_prelevement_bons = p.rowid";
$sql .= " AND f.fk_soc = s.idp";
$sql .= " AND pf.fk_facture = f.rowid";
$sql .= " AND pl.rowid=".$_GET["id"];

if ($_GET["socid"])
{
  $sql .= " AND s.idp = ".$_GET["socid"];
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);

if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  $urladd = "&amp;id=".$_GET["id"];

  print_barre_liste("Factures", $page, "factures.php", $urladd, $sortfield, $sortorder, '', $num);
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Facture","factures.php","p.ref",'',$urladd);
  print_liste_field_titre("Société","factures.php","s.nom",'',$urladd);
  print_liste_field_titre("Montant","factures.php","f.total_ttc","",$urladd,'align="center"');
  print '<td colspan="2">&nbsp;</td></tr>';

  $var=True;

  $total = 0;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	

      print "<tr $bc[$var]><td>";

      print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">';
      print img_object($langs->trans("ShowBill"),"bill");
      print '</a>&nbsp;';

      print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">'.$obj->ref."</a></td>\n";

      print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->idp.'">';
      print img_object($langs->trans("ShowCompany"),"company"). ' '.stripslashes($obj->nom)."</a></td>\n";

      print '<td align="center">'.price($obj->total_ttc)."</td>\n";

      print '<td>';

      if ($obj->statut == 0)
	{
	  print '-';
	}
      elseif ($obj->statut == 1)
	{
	  print 'Crédité';
	}
      elseif ($obj->statut == 2)
	{
	  print '<b>Rejeté</b>';
	}

      print "</td></tr>\n";

      $i++;
    }

  print "</table>";

  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
