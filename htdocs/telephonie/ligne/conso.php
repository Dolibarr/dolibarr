<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/infoc.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Infos');
	      $h++;
	      
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/history.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Historique');
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

	      print '<tr><td width="25%">Numéro</td><td>'.dolibarr_print_phone($ligne->numero).'</td>';
	      print '<td>Facturée : '.$ligne->facturable.'</td><td>&nbsp;</td></tr>';
	      	     
	      $client = new Societe($db, $ligne->client_id);
	      $client->fetch($ligne->client_id);

	      print '<tr><td width="25%">Client</td><td>';

	      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$ligne->client_id.'">';
	      print $client->nom.'</a></td>';

	      $client_facture = new Societe($db);
	      $client_facture->fetch($ligne->client_facture_id);

	      print '<td width="25%">Client Facturé</td><td>'.$client_facture->nom.'</td></tr>';


	      print '<tr><td width="25%">Statut</td><td colspan="3">';
	      print '<img src="./graph'.$ligne->statut.'.png">&nbsp;';
	      print $ligne->statuts[$ligne->statut];
	      print '</td></tr>';

	      /* 
	     
	      $sql = "SELECT f.cout_vente";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
	      $sql .= " WHERE f.fk_ligne = ".$ligne->id;
	      $sql .= " ORDER BY f.date ASC ";
	      if ( $db->query( $sql) )
		{
		  $num = $db->num_rows();
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($i);

			  print '<tr><td valign="top" width="25%">'.strftime("%A %d %B %Y",$row[0]).'</td>';

			  print '<td><img src="./graph'.$row[1].'.png">&nbsp;';
			  print $ligne->statuts[$row[1]]."</td>";
			  print '<td colspan="2">'.$row[4] . " " . $row[3] . "</td></tr>";
			  $i++;
			}
		    }
		  $db->free();
		}
	      else
		{
		  print $sql;
		}
	      */
	      print '<tr><td colspan="2" align="center">';
	      print '<img src="./graphconso.php?id='.$ligne->id.'"><br />';
	      print '<a href="'.DOL_URL_ROOT.'/telephonie/facture/liste.php?search_ligne='.$ligne->numero.'">liste</a><br />';
	      print '</td><td colspan="2" align="center">';
	      print '<img src="./graphconsominutes.php?ligne='.$ligne->numero.'">';
	      print "</td></tr></table>";


	    }
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


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
