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


if ($_POST["action"] == 'add')
{
  $ligne = new LigneTel($db);

  $ligne->numero         = $_POST["numero"];
  $ligne->client         = $_POST["client"];
  $ligne->client_facture = $_POST["client_facture"];
  $ligne->fournisseur    = $_POST["fournisseur"];
  $ligne->commercial     = $_POST["commercial"];
  $ligne->remise         = $_POST["remise"];
  $ligne->note           = $_POST["note"];


  if ( $ligne->create($user) )
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}

if ($_POST["action"] == 'addcontact')
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->add_contact($_POST["contact_id"]) )
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}


if ($_GET["action"] == 'delcontact')
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->del_contact($_GET["contact_id"]) )
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}

if ($_GET["action"] == 'active')
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->set_statut($user, 3) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}

if ($_GET["action"] == 'resilier')
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->set_statut($user, 4) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'annuleresilier')
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->set_statut($user, 3) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}




if ($_POST["action"] == 'update' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  $ligne->numero         = $_POST["numero"];
  $ligne->client         = $_POST["client"];
  $ligne->client_facture = $_POST["client_facture"];
  $ligne->fournisseur    = $_POST["fournisseur"];
  $ligne->commercial     = $_POST["commercial"];
  $ligne->remise         = $_POST["remise"];
  $ligne->note           = $_POST["note"];

  if ( $ligne->update($user) )

    {
      $action = '';
      $mesg = 'Fiche mise à jour';
    }
  else
    {
      $action = 're-edit';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $entrepot->mesg_error;
    }
}


llxHeader("","","Fiche Ligne");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 * Affichage
 *
 */
/*
 * Création
 *
 */

if ($_GET["action"] == 'create')
{
  $ligne = new LigneTel($db);
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="type" value="'.$type.'">'."\n";
  print_titre("Nouvelle ligne");
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="20%">Numéro</td><td><input name="numero" size="12" value=""></td></tr>';

  print '<tr><td width="20%">Client</td><td colspan="2">';
  print '<select name="client">';

  $sql = "SELECT idp, nom FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ORDER BY nom ";
  if ( $db->query( $sql) )
    {
      $num = $db->num_rows();
      if ( $num > 0 )
	{
	  $i = 0;
	  while ($i < $num)
	    {
	      $row = $db->fetch_row($i);
	      print '<option value="'.$row[0].'">'.$row[1];
	      $i++;
	    }
	}
      $db->free();      
    }

  print '</select></td></tr>';

  print '<tr><td width="20%">Client à facturer</td><td colspan="2">';
  print '<select name="client_facture">';


  $sql = "SELECT idp, nom FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ORDER BY nom ";
  if ( $db->query( $sql) )
    {
      $num = $db->num_rows();
      if ( $num > 0 )
	{
	  $i = 0;
	  while ($i < $num)
	    {
	      $row = $db->fetch_row($i);
	      print '<option value="'.$row[0].'">'.$row[1];
	      $i++;
	    }
	}
      $db->free();     
    }

  print '</select></td></tr>';

  print '<tr><td width="20%">Fournisseur</td><td colspan="2">';
  print '<select name="fournisseur">';

  $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur ORDER BY nom ";
  if ( $db->query( $sql) )
    {
      $num = $db->num_rows();
      if ( $num > 0 )
	{
	  $i = 0;
	  while ($i < $num)
	    {
	      $row = $db->fetch_row($i);
	      print '<option value="'.$row[0].'">'.$row[1];
	      $i++;
	    }
	}
      $db->free();
      
    }
  print '</select></td></tr>';

  print '<tr><td width="20%">Commercial</td><td colspan="2">';
  print '<select name="commercial">';

  $sql = "SELECT rowid, name, firstname FROM ".MAIN_DB_PREFIX."user ORDER BY name ";
  if ( $db->query( $sql) )
    {
      $num = $db->num_rows();
      if ( $num > 0 )
	{
	  $i = 0;
	  while ($i < $num)
	    {
	      $row = $db->fetch_row($i);
	      print '<option value="'.$row[0].'">'.$row[1] . " " . $row[2];
	      $i++;
	    }
	}
      $db->free();
      
    }
  print '</select></td></tr>';


  print '<tr><td width="20%">Remise</td><td><input name="remise" size="3" maxlength="2" value="">&nbsp;%</td></tr>';
  
  print '<tr><td width="20%" valign="top">Note</td><td>';
  print '<textarea name="note" rows="4" cols="50">';
  print "</textarea></td></tr>";

  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
}
else
{
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
	      $hselected = $h;
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Conso');
	      $h++;
	      
	      dolibarr_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);

	      print_fiche_titre('Fiche Ligne', $mesg);
      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	      print '<tr><td width="20%">Numéro</td><td>'.dolibarr_print_phone($ligne->numero).'</td>';
	      print '<td>Facturée : '.$ligne->facturable.'</td><td>&nbsp;</td></tr>';
	      	     
	      $client = new Societe($db, $ligne->client_id);
	      $client->fetch($ligne->client_id);

	      print '<tr><td width="20%">Client</td><td>'.$client->nom.'</td>';

	      $client_facture = new Societe($db);
	      $client_facture->fetch($ligne->client_facture_id);

	      print '<td width="20%">Client Facturé</td><td>'.$client_facture->nom.'</td></tr>';


	      print '<tr><td width="20%">Statut</td><td colspan="3">';
	      print '<img src="./graph'.$ligne->statut.'.png">&nbsp;';
	      print $ligne->statuts[$ligne->statut];
	      print '</td></tr>';

	      /* Contacts */
	     
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

			  print '</td><td colspan="2">'.$row[4] . " " . $row[3] . "</td></tr>";
			  $i++;
			}
		    }
		  $db->free();
		}
	      else
		{
		  print $sql;
		}
	  
	      /* Fin Contacts */

	      if ($_GET["action"] <> 'edit' && 0)
		{
		  print '<tr><td width="20%">Point de rentabilité</td><td colspan="2">';	  


		  print '<img src="./graphrent.php?remise='.$ligne->remise.'">';
		  
		  print '</td></tr>';
		}

	      print "</table>";
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
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
