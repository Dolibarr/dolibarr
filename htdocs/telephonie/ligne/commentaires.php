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
require_once DOL_DOCUMENT_ROOT.'/telephonie/telephonie.contrat.class.php';
$mesg = '';

llxHeader("","","Fiche client");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 * Affichage
 *
 */

if ($_GET["id"])
{

  $ligne = new LigneTel($db);
  $result = $ligne->fetch_by_id($_GET["id"]);

  if ($result == 1)
    {
      $client_comm = new Societe($db);
      $client_comm->fetch($ligne->client_comm_id, $user);
    }

  $soc = new Societe($db);
  $result = $soc->fetch($ligne->client_comm_id, $user);

  if ($_GET["action"] == 'add')
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_ligne_comments";
      $sql .= " (fk_ligne, fk_user, commentaire,datec)";
      $sql .= " VALUES ('".$ligne->id."','".$user->id."','".addslashes($_POST["comment"])."',now());";
      $db->query($sql);
    }

  if ($_GET["action"] == 'del')
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_comments";
      $sql .= " WHERE rowid = '".$_GET["commid"]."'";
      $sql .= " AND fk_user = '".$user->id."';";
      $db->query($sql);
    }

  if (!$soc->perm_read)
    {
      print "Lecture non authoris�e";
    }

  if ( $result == 1 && $soc->perm_read)
    { 

      $h=0;

      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/fiche.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans("Ligne");
      $h++;
      
      if ($ligne->statut == -1)
	{
	  if ($user->rights->telephonie->ligne->creer)
	    {
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/commande.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Commande');
	      $h++;
	    }
	}
      else
	{
	  if ($user->rights->telephonie->facture->lire)
	    {
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/factures.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Factures');
	      $h++;
	    }
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
      $hselected = $h;
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Conso');
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/stat.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Stats');
      $h++;
      
      dol_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);
      

      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
      
      if ($ligne->contrat)
	{
	  $contrat = new TelephonieContrat($db);
	  $contrat->fetch($ligne->contrat);
	  
	  print '<tr><td width="20%">Contrat</td><td>'.$contrat->ref_url.'</a></td><td>';
	  print '<img src="./graph'.$ligne->statut.'.png">&nbsp;';
	  print $ligne->statuts[$ligne->statut];
	  print '</td></tr>';
	}
      
      print '<tr><td width="20%">Client</td><td>';
      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_comm->id.'">';
      
      print $client_comm->nom.'</a></td><td>'.$client_comm->code_client;
      print '</td></tr>';
      
      print '<tr><td width="20%">Num�ro</td><td>'.dol_print_phone($ligne->numero,0,0,true).'</td>';
      print '<td>Factur�e : '.$ligne->facturable.'</td></tr>';
      
      $client = new Societe($db, $ligne->client_id);
      $client->fetch($ligne->client_id);
      
      print '<tr><td width="20%">Client (Agence/Filiale)</td><td colspan="2">';
      print $client->nom.'<br />';
      
      print $client->cp . " " .$client->ville;
      print '</td></tr></table>';
            
      print '<br />';
      
      print '<form method="POST" action="commentaires.php?id='.$ligne->id.'&action=add">';
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';	  
      print '<tr><td width="15%" valign="center">Nouveau<br>commentaire';
      print '</td><td><textarea cols="60" rows="3" name="comment"></textarea></td>';
      print '<td><input type="submit" value="Ajouter"></td></tr>';
      print "</table></form><br />";
      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
      
      /* Commentaires */
      
      $sql = "SELECT c.commentaire, u.firstname, u.name, u.login, c.rowid, c.fk_user";
      $sql .= " , ".$db->pdate("c.datec") ." as datec";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_comments as c";
      $sql .= " , ".MAIN_DB_PREFIX."user as u";
      $sql .= " WHERE fk_ligne = ".$ligne->id;
      $sql .= " AND c.fk_user = u.rowid";
      $sql .= " ORDER BY c.datec DESC";
      
      $resql = $db->query($sql);
      
      if ($resql)
	{
	  print '<tr class="liste_titre"><td width="15%" valign="center">Date';
	  print '</td><td>Commentaire</td><td align="center">Auteur</td><td>&nbsp;</td>';
	  print "</tr>\n";
	  
	  while ($obj = $db->fetch_object($resql))
	    {
	      print "<tr $bc[$var]><td>".strftime("%d/%m/%y %H:%M",$obj->datec);
	      print "</td>\n";
	      print '<td>'.nl2br(stripslashes($obj->commentaire))."</td>\n";
	      print '<td align="center">'.$obj->login."</td>\n";
	      print '<td align="center">&nbsp;';
	      if ($obj->fk_user == $user->id)
		{
		  print '<a href="commentaires.php?id='.$ligne->id.'&amp;commid='.$obj->rowid.'&amp;action=del">';
		  print img_delete().'</a>';
		}
	      print "</td></tr>\n";
	      $var=!$var;		  
	    }
	  $db->free($resql);	  
	}
      else
	{
	  print $sql;
	}      
      print "</table>";
    }
}
else
{
  print "Error";
}


print '</div>';

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */
/*
print "\n<br>\n<div class=\"tabsAction\">\n";
print "<a class=\"butAction\" href=\"commentaires.php?action=add&amp;id=$soc->id\">".$langs->trans("Ajouter un commentaire")."</a>";
print "</div>";
*/

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
