<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader("","","Fiche Ligne");

if ($_GET["id"] or $_GET["numero"])
{
  $ligne = new LigneTel($db);
  if ($_GET["id"])
    {
      $result = $ligne->fetch_by_id($_GET["id"]);
    }
  if ($_GET["numero"])
    {
      $result = $ligne->fetch($_GET["numero"]);
    }
  
  if ($result == 1)
    {
      $client_comm = new Societe($db);
      $client_comm->fetch($ligne->client_comm_id, $user);
    }
  
  if (!$client_comm->perm_read)
    {
      print "Lecture non authoris�e";
    }
  
  
  if ($result == 1 && $client_comm->perm_read)
    { 
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  
	  $h=0;
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/fiche.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans("Ligne");
	  $h++;

	  if ($user->rights->telephonie->facture->lire)
	    {	  
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/factures.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Factures');
	      $h++;
	    }

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/infoc.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Infos');
	  $h++;
	      
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/history.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Historique');
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Conso');
	  $h++;
	      
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/stat.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Stats');
	  $hselected = $h;
	  $h++;

	  $ligne->load_previous_next_id();
	  
	  $previous_ref = $ligne->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?id='.$ligne->ref_previous.'">'.img_previous().'</a>':'';
	  $next_ref     = $ligne->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?id='.$ligne->ref_next.'">'.img_next().'</a>':'';

	  dolibarr_fiche_head($head, $hselected, 'Statistiques ligne');

	  print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">Statistiques '.$mesg.'</td>';
	  print '<td class="nobordernopadding"><a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
	  print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table><br />';
	        
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	  print '<tr><td width="25%">Num�ro</td><td>'.dolibarr_print_phone($ligne->numero,0,0,true).'</td>';
	  print '<td>Factur�e : '.$ligne->facturable.'</td><td>&nbsp;</td></tr>';
	      	     
	  $client = new Societe($db, $ligne->client_id);
	  $client->fetch($ligne->client_id);

	  print '<tr><td width="25%">Client</td><td>';

	  print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$ligne->client_id.'">';
	  print $client->nom.'</a></td>';

	  $client_facture = new Societe($db);
	  $client_facture->fetch($ligne->client_facture_id);

	  print '<td width="25%">Client Factur�</td><td>'.$client_facture->nom.'</td></tr>';

	  print '<tr><td width="25%">Statut</td><td colspan="3">';
	  print '<img src="./graph'.$ligne->statut.'.png">&nbsp;';
	  print $ligne->statuts[$ligne->statut];
	  print "</td></tr></table>";

	  /*
	   *
	   *
	   */

	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';

	  print '<tr><td width="50%" valign="top" align="center">';

	  $mesg_no_graph = 'Nous avons pas assez de donn�es � ce jour pour g�n�rer ce graphique.';

	  $img_root = DOL_DATA_ROOT."/graph/".substr($ligne->id,-1)."/telephonie/ligne/";

	  $file = $img_root.$ligne->id."/graphca.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td><td width="50%" valign="top" align="center">';

	  $file = $img_root.$ligne->id."/graphgain.png";
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

	  $file = $img_root.$ligne->id."/nb-comm-mensuel.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td><td width="50%" valign="top" align="center">';

	  $file = $img_root.$ligne->id."/nb-minutes-mensuel.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td></tr>';
	  print '<tr><td width="50%" valign="top" align="center">';

	  $file = $img_root.$ligne->id."/graphappelsdureemoyenne.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td><td width="50%" valign="top" align="center">';
	  print "&nbsp;";
	  print '</td></tr></table>';
	}
    }
}
else
{
  print "Error";
}


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
