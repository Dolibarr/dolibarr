<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$mesg = '';

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

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/commentaires.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Commentaires');
	  $numc = $ligne->num_comments();
	  if ($numc > 0)
	    {
	      $head[$h][1] = $langs->trans("Commentaires ($numc)");
	    }
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Conso');
	  $hselected = $h;
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/stat.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Stats');
	  $h++;
	      
	  dolibarr_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);

	  print_fiche_titre('Fiche Ligne', $mesg);
      
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
	  print '<img alt="statut" src="./graph'.$ligne->statut.'.png">&nbsp;';
	  print $ligne->statuts[$ligne->statut];
	  print '</td></tr>';

	  $sql = "SELECT max(".$db->pdate("date").")";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
	  $sql .= " WHERE fk_ligne = ".$ligne->id;

	  $resql = $db->query( $sql);

	  if ( $resql )
	    {
	      $num = $db->num_rows($resql);
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row($resql);
		      
		      print '<tr><td colspan="2">Date de la derni�re communication</td>';
		      print '<td colspan="2">'.strftime("%A %d %B %Y",$row[0]).'</td>';
		      print '</tr>';
		      $i++;
		    }
		}
	      $db->free();
	    }
	  else
	    {
	      //print $sql;
	    }
	  
	  print '<tr><td colspan="2" align="center">';

	  print '<a href="'.DOL_URL_ROOT.'/telephonie/facture/liste.php?search_ligne='.$ligne->numero.'">liste</a><br />';
	  print '</td><td colspan="2" align="center">';

	  print "</td></tr></table>";


	  $img_root = DOL_DATA_ROOT."/graph/".substr($ligne->id,-1)."/telephonie/ligne/";

	  $file = $img_root.$ligne->id."/conso.png";

	  if (file_exists($file)) 
	    {
	      print '<br><img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="Conso">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  /*
	   * Mode Liste
	   *
	   */
	  
	  $sql = "SELECT date,numero, cout_vente, duree,fichier_cdr";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
	  $sql .= " WHERE fk_ligne=".$ligne->id;

	  if (isset($_GET["search_num"]))
	    $selnum = urldecode($_GET["search_num"]);
	  
	  if (isset($_POST["search_num"]))
	    $selnum = urldecode($_POST["search_num"]);


	  if ($selnum)
	    {
	      $selnum = ereg_replace("\.","",$selnum);
	      $selnum = ereg_replace(" ","",$selnum);
	      $sql .= " AND numero LIKE '%".$selnum."%'";
	    }
	  
	  $page = $_GET["page"];
	  if ($page == -1) { $page = 0 ; }

	  $sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:'DESC';
	  $sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:'date';
  
	  $offset = $conf->liste_limit * $page ;
	  $pageprev = $page - 1;
	  $pagenext = $page + 1;
	      

	  $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
	  $resql = $db->query($sql);
	  
	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      
	      $urladd= "&amp;id=".$ligne->id."&amp;search_ligne=".$sel."&amp;search_num=".$selnum;
	  
	      print_barre_liste("CDR", $page, "conso.php", $urladd, $sortfield, $sortorder, '', $num);

	      print '<form action="conso.php?'.$urladd.'" method="POST">'."\n";
	      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">'."\n";
	      print '<tr class="liste_titre">';
	      print '<td>Numero</td><td>Date</td><td align="right">Duree</td>';
	      print '<td align="right">Montant</td><td align="right">Fichier</td>';
	      print "</tr>\n";
	      
	      print '<tr class="liste_titre">';

	      print '<td><input type="text" name="search_num" value="'. $selnum.'" size="10"></td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
	      
	      $var=True;
	      
	      while ($obj = $db->fetch_object($resql))
		{
		  $var=!$var;
		  
		  print "<tr $bc[$var]>";
		  print '<td>'.$obj->numero."</td>\n";
		  print '<td>'.$obj->date." ".$obj->heure."</td>\n";
		  print '<td align="right">'.$obj->duree."</td>\n";
		  print '<td align="right">'.$obj->cout_vente."</td>\n";
		  print '<td align="right">'.$obj->fichier_cdr."</td>\n";
		  
		}
	      print "</table></form>";
	    }
	  else
	    {
	      print $db->error();
	    }


	}
    }

  print '</div>';

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
