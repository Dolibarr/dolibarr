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

require("./pre.inc.php");

llxHeader("","Téléphonie - Contrat");

/* Affichage */

if ($_GET["id"])
{
  $client_comm = new Societe($db);
  $contrat = new TelephonieContrat($db);

  if ($contrat->fetch($_GET["id"]) == 0)
    {
      $result = 1;
    }

  $client_comm->fetch($contrat->client_comm_id, $user);

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
	  $hselected = $h;
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/info.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Infos");
	  $h++;
	  
	  dol_fiche_head($head, $hselected, 'Contrat : '.$contrat->ref);
	  
	  print_fiche_titre('Fiche Contrat', $mesg);

	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	  $client_comm = new Societe($db, $contrat->client_comm_id);
	  $client_comm->fetch($contrat->client_comm_id);
	  
	  print '<tr><td width="20%">Référence</td><td>'.$contrat->ref.'</td>';
	  print '<td colspan="2">Facturé : '.$contrat->facturable.'</td></tr>';
	  
	  print '<tr><td width="20%">Client</td><td>';
	  print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_comm->id.'">';
	  
	  print $client_comm->nom.'</a></td><td colspan="2">'.$client_comm->code_client;
	  print '</td></tr>';
	  
	  $commercial = new User($db, $contrat->commercial_sign_id);
	  $commercial->fetch();
	  $commercial_suiv = new User($db, $contrat->commercial_suiv_id);
	  $commercial_suiv->fetch();
	  
	  print '<tr><td width="20%">Commercial Suivi/Signature</td>';
	  print '<td colspan="3">'.$commercial_suiv->fullname.'/'.$commercial->fullname.'</td></tr>';
	  
	  print "</table><br />";
	  
	  
	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';

	  print '<tr><td width="50%" valign="top" align="center">';

	  $mesg_no_graph = 'Nous avons pas assez de données à ce jour pour générer ce graphique.';

	  $img_root = DOL_DATA_ROOT."/graph/".substr($contrat->id,-1)."/telephonie/contrat/";

	  $file = $img_root.$contrat->id."/graphca.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	      //print '<img src="'.DOL_URL_ROOT.'/viewimage.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td><td width="50%" valign="top" align="center">';

	  $file = $img_root.$contrat->id."/graphgain.png";

	  if (file_exists($file) && $user->rights->telephonie->ligne->gain)
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td></tr>';
	  print '<tr><td width="50%" valign="top" align="center">';

	  $file = $img_root.$contrat->id."/graphappelsdureemoyenne.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="dureemoyenne">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td><td width="50%" valign="top" align="center">';

	  print "&nbsp;";

	  print '</td></tr>';
	  print '<tr><td width="50%" valign="top" align="center">';

	  $file = $img_root.$contrat->id."/nb-comm-mensuel.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td><td width="50%" valign="top" align="center">';

	  $file = $img_root.$contrat->id."/nb-minutes-mensuel.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td></tr></table>';

	}
    }
}
else
{
  print "Erreur";
}

print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
