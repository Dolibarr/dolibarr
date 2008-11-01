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

llxHeader("","","Historique Remises");

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
      
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/remises.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Discounts');
      $hselected=$h;
      $h++;
      
      dolibarr_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);
      
      print_fiche_titre('Fiche Ligne', $mesg);
      
      print '<table class="border" width="100%">';

      print '<tr><td width="20%">Num�ro</td><td colspan="2">'.dolibarr_print_phone($ligne->numero,0,0,true).'</td></tr>';
	      	     
      $client = new Societe($db, $ligne->client_id);
      $client->fetch($ligne->client_id);

      print '<tr><td width="20%">Client</td><td colspan="2">';
      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_comm->id.'">';
      print $client_comm->nom.'</a></td></tr>';

      print '<tr><td width="20%">Statut actuel</td><td colspan="2">';
      print '<img src="./graph'.$ligne->statut.'.png">&nbsp;';
      print $ligne->statuts[$ligne->statut];
      print '</td></tr>';

      if ($ligne->user_creat)
	{
	  print '<tr><td width="20%">Cr�� par</td><td colspan="2">';

	  $cuser = new User($db, $ligne->user_creat);
	  $cuser->fetch();

	  print $cuser->fullname;
	  print '</td></tr>';
	}
      if ($ligne->user_commande)
	{
	  print '<tr><td width="20%">Command� par</td><td colspan="2">';

	  $couser = new User($db, $ligne->user_commande);
	  $couser->fetch();
		  
	  print $couser->fullname;
	  print '</td></tr>';
	}

      print '</table><br />';
      print '<table class="border" width="100%">';

      print '<tr class="liste_titre">';
      print '<td>Date</td>';
      print '<td align="center">'.$langs->trans("Discount").'</td>';
      print '<td>'.$langs->trans("Comment").'</td>';
      print '<td>'.$langs->trans("User").'</td>';
      print '</tr>';

      /* historique */
	     
      $sql = "SELECT ".$db->pdate("r.tms").", r.remise, r.fk_user, r.comment, u.name, u.firstname";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_remise as r";
      $sql .= ",".MAIN_DB_PREFIX."user as u";
      $sql .= " WHERE u.rowid = r.fk_user AND r.fk_ligne = ".$ligne->id;
      $sql .= " ORDER BY r.tms DESC ";
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

		  print '<td align="center">'.$row[1].'&nbsp;%</td>';
		  print '<td>'.stripslashes($row[3]).'&nbsp;</td>';

		  print '<td>'.$row[5] . " " . $row[4] . "</td></tr>";
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

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
