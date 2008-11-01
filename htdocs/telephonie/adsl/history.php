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

if (!$user->rights->telephonie->adsl->lire) accessforbidden();

$mesg = '';

llxHeader("","","Historique Liaison ADSL");

/*
 * Affichage
 *
 */

if ($_GET["id"])
{
  if ($_GET["action"] <> 're-edit')
    {
      $ligne = new LigneADSL($db);
      
      $result = $ligne->fetch_by_id($_GET["id"]);            
    }
  
  if ( $result )
    { 
      $h=0;
      $head[$h][0] = DOL_URL_ROOT."/telephonie/adsl/fiche.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans("Liaison ADSL");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT."/telephonie/adsl/history.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Historique');
      $hselected = $h;
      $h++;
	  
      dolibarr_fiche_head($head, $hselected, 'Liaison ADSL : '.$ligne->numero);

      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

      print '<tr><td width="20%">Num�ro de support</td><td colspan="2">'.dolibarr_print_phone($ligne->numero,0,0,true).'</td></tr>';

	      	     
      $client = new Societe($db, $ligne->client_id);
      $client->fetch($ligne->client_id);

      print '<tr><td width="20%">Client</td><td colspan="2">'.$client->nom.'</td></tr>';

      print '<tr><td width="20%">Statut</td><td colspan="2">';
      print '<img src="./statut'.$ligne->statut.'.png">&nbsp;';
      print $ligne->statuts[$ligne->statut];
      print '</td></tr>';

      print '<tr class="liste_titre"><td colspan="3">Historique</td></tr>';

      /* historique */
	     
      $sql = "SELECT ".$db->pdate("l.tms").", l.statut, l.fk_user, u.name, u.firstname, l.comment";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_adsl_ligne_statut as l";
      $sql .= ",".MAIN_DB_PREFIX."user as u";
      $sql .= " WHERE u.rowid = l.fk_user AND l.fk_ligne = ".$ligne->id;
      $sql .= " ORDER BY l.tms DESC ";

      $resql = $db->query($sql);

      if ( $resql )
	{
	  $num = $db->num_rows($resql);
	  if ( $num > 0 )
	    {
	      $i = 0;
	      while ($i < $num)
		{
		  $row = $db->fetch_row($resql);

		  print '<tr><td valign="top" width="20%">'.strftime("%a %d %B %Y %H:%M:%S",$row[0]).'</td>';

		  print '<td><img src="./statut'.$row[1].'.png">&nbsp;';
		  print $ligne->statuts[$row[1]];
		  if ($row[5])
		    {
		      print '<br />'.$row[5];
		    }

		  print '</td><td>'.$row[4] . " " . $row[3] . "</td></tr>";
		  $i++;
		}
	    }
	  $db->free($resql);
	}
      else
	{
	  print $sql;
	}
	  
      /* Fin Contacts */

      print "</table>";
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
