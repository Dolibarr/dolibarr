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

llxHeader("","","Historique Ligne");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 * Affichage
 *
 */

  if ($_GET["id"] or $_GET["numero"])
    {
      if ($_GET["action"] <> 're-edit')
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
	}

      if ( $result )
	{ 
	  if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	    {

	      $h=0;
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/fiche.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans("Ligne");
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/factures.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Factures');
	      $h++;
	      
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/infoc.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Infos');
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/history.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Historique');
	      $hselected = $h;
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Conso');
	      $h++;
	      
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/stat.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Stats');
	      $h++;

	      dolibarr_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);

	      print_fiche_titre('Fiche Ligne', $mesg);
      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	      print '<tr><td width="20%">Numéro</td><td colspan="2">'.dolibarr_print_phone($ligne->numero).'</td></tr>';
	      	     
	      $client = new Societe($db, $ligne->client_id);
	      $client->fetch($ligne->client_id);

	      $client_comm = new Societe($db, $ligne->client_comm_id);
	      $client_comm->fetch($ligne->client_comm_id);

	      print '<tr><td width="20%">Client</td><td colspan="2">';
	      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_comm->id.'">';
	      print $client_comm->nom.'</a></td></tr>';

	      print '<tr><td width="20%">Statut actuel</td><td colspan="2">';
	      print '<img src="./graph'.$ligne->statut.'.png">&nbsp;';
	      print $ligne->statuts[$ligne->statut];
	      print '</td></tr>';

	      print '<tr class="liste_titre">';
	      print '<td>Date</td>';
	      print '<td>Statut</td>';
	      print '<td>Rapporteur</td>';
	      print '</tr>';

	      /* historique */
	     
	      $sql = "SELECT ".$db->pdate("l.tms").", l.statut, l.fk_user, u.name, u.firstname, l.comment";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut as l";
	      $sql .= ",".MAIN_DB_PREFIX."user as u";
	      $sql .= " WHERE u.rowid = l.fk_user AND l.fk_ligne = ".$ligne->id;
	      $sql .= " ORDER BY l.tms DESC ";
	      if ( $db->query( $sql) )
		{
		  $num = $db->num_rows();
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($i);

			  print '<tr><td valign="top" width="20%">'.strftime("%a %d %B %Y %H:%M:%S",$row[0]).'</td>';

			  print '<td><img src="./graph'.$row[1].'.png">&nbsp;';
			  print $ligne->statuts[$row[1]];
			  if ($row[5])
			    {
			      print '<br />'.$row[5];
			    }

			  print '</td><td>'.$row[4] . " " . $row[3] . "</td></tr>";
			  $i++;
			}
		    }
		  $db->free();
		}
	      else
		{
		  print $sql;
		}
	  
	      print "</table>";
	    }
	}
      /*
       *
       */
      print '<br />';
      print_titre("Retours Fournisseurs");
      $sql = "SELECT ";
      $sql .= " cli,mode,situation,date_mise_service,date_resiliation,motif_resiliation,commentaire,fichier, traite ";
      
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commande_retour";
      $sql .= " WHERE cli = ".$ligne->numero;
      $sql .= " ORDER BY rowid DESC " . $db->plimit($conf->liste_limit+1, $offset);
      
      if ($db->query($sql))
	{
	  $num = $db->num_rows();
	  $i = 0;
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr class="liste_titre"><td>Mode</td><td>Resultat</td>';
	  print '<td align="center">Date</td><td>Commentaire</td><td>Fichier</td>';
	  print "</tr>\n";
	  $var=True;
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object();
	      $var=!$var;
	      
	      print "<tr $bc[$var]>";
	      print '<td>'.$obj->mode."</td>\n";
	      print '<td>'.$obj->situation."</td>\n";
	      print '<td align="center">'.$obj->date_mise_service."</td>\n";
	      print '<td>'.$obj->commentaire."</td>\n";
	      print '<td>'.$obj->fichier."</td>\n";
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

      /*
       *
       *
       *
       */
    }
  else
    {
      print "Error";
    }


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
