<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require "./pre.inc.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php";

$mesg = '';

llxHeader("","","Fiche Contrat");

if ($_GET["id"])
{
  $client_comm = new Societe($db);
  $contrat = new TelephonieContrat($db);
      
  if ($contrat->fetch($_GET["id"]) == 0)
    {
      $result = 1;
      $client_comm->fetch($contrat->client_comm_id, $user);
    }
  else
    {
      print "Erreur";
    }
  
  if (!$client_comm->perm_read)
    {
      print "Lecture non authorisée";
    }
  
  if ( $result && $client_comm->perm_read)
    { 
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  
	  $h=0;
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/fiche.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Contrat");
	  $h++;
	  
	  $nser = $contrat->count_associated_services();
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/services.php?id=".$contrat->id;
	  if ($nser > 0)
	    {
	      $head[$h][1] = $langs->trans("Services")." (".$nser.")";
	    }
	  else
	    {
	      $head[$h][1] = $langs->trans("Services");
	    }
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/stats.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Stats");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/info.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Infos");
	  $hselected = $h;
	  $h++;

	  dol_fiche_head($head, $hselected, 'Contrat : '.$contrat->ref);
	  
	  print_fiche_titre('Fiche Contrat', $mesg);
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  
	  $client_comm = new Societe($db, $contrat->client_comm_id);
	  $client_comm->fetch($contrat->client_comm_id);
	  
	  print '<tr><td width="20%">Référence</td><td>'.$contrat->ref.'</td>';
	  print '<td>Facturé : '.$contrat->facturable.'</td></tr>';
	  
	  print '<tr><td width="20%">Client</td><td>';
	  print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_comm->id.'">';
	  
	  print $client_comm->nom.'</a></td><td>'.$client_comm->code_client;
	  print '</td></tr>';
	  
	  $commercial = new User($db, $contrat->commercial_sign_id);
	  $commercial->fetch();
	  
	  print '<tr><td width="20%">Commercial Signature</td>';
	  print '<td colspan="2">'.$commercial->fullname.'</td></tr>';
	  
	  $commercial_suiv = new User($db, $contrat->commercial_suiv_id);
	  $commercial_suiv->fetch();
	  
	  print '<tr><td width="20%">Commercial Suivi</td>';
	  print '<td colspan="2">'.$commercial_suiv->fullname.'</td></tr>';
	  
	  $cuser_suiv = new User($db, $contrat->user_creat);
	  $cuser_suiv->fetch();
	  
	  print '<tr><td width="20%">Créé par</td>';
	  print '<td colspan="2">'.$cuser_suiv->fullname;
	  print '</td></tr>';

	  print '<tr><td width="20%">Créé le</td>';
	  print '<td colspan="2">'.strftime("%e %B %Y",$contrat->date_creat);
	  print '</td></tr>';

	  print "</table><br />";


  $sql = "SELECT s.rowid as socid, s.nom, p.fk_contrat, p.montant, p.avance_duree, p.avance_pourcent";
  $sql .= ", p.rem_pour_prev, p.rem_pour_autr, p.mode_paiement";
  $sql .= ", u.name, u.firstname, u.login";
  $sql .= " , ".$db->pdate("p.datepo") . " as datepo";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
  $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
  $sql .= " , ".MAIN_DB_PREFIX."societe as s";
  $sql .= " , ".MAIN_DB_PREFIX."user as u";
  
  $sql .= " WHERE p.fk_commercial =u.rowid";
  $sql .= " AND p.fk_user =u.rowid";
  $sql .= " AND c.fk_soc = s.rowid";
  $sql .= " AND p.fk_contrat = c.rowid";
  $sql .= " AND c.rowid =".$_GET["id"];

  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      print_barre_liste("Prises d'ordre", $page, "commercialpo.php","&amp;commid=".$_GET["commid"], $sortfield, $sortorder, '', $num);
      
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td>Affecté à</td>';
      print '<td align="center">Date</td>';
      print '<td align="right">Montant</td>';
      print '<td align="center">MdP</td>';
      print '<td align="center">Saisi par</td>';
      print "</tr>\n";
      
      $var=True;
      
      while ($i < min($num,$conf->liste_limit))
	{
	  $obj = $db->fetch_object($resql);
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  
	  print '<td>'.$obj->firstname.' '.$obj->name."</td>\n";

	  print '<td align="center">'.strftime("%e %b %Y",$obj->datepo)."</td>\n";

	  print '<td align="right">'.sprintf("%01.2f",$obj->montant)."</td>\n";
	  
	  if ($obj->mode_paiement == 'pre')
	    {
	      print '<td align="center">Prelev</td>';
	    }
	  else
	    {
	      print '<td align="center">Autre</td>';
	    }
	  print '<td align="center">'.$obj->login.'</td>';

	  print "</tr>\n";
	  $i++;
	}
      print "</table>";
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }


	}
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
