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

require "./pre.inc.php";

$mesg = '';

if ($_POST["action"] == 'addservice' && $user->rights->telephonie->service->affecter)
{
  $contrat = new TelephonieContrat($db);
  $contrat->id= $_GET["id"];

  if ( $contrat->add_service($user, $_POST["service_id"]) == 0)
    {
      Header("Location: services.php?id=".$contrat->id);
    }
}

if ($_GET["action"] == 'rmservice' && $user->rights->telephonie->service->affecter)
{
  $contrat = new TelephonieContrat($db);
  $contrat->id= $_GET["id"];

  if ( $contrat->remove_service($user, $_GET["service_id"]) == 0)
    {
      Header("Location: services.php?id=".$contrat->id);
    }
}

llxHeader("","","Fiche Contrat - Services");


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
	  $hselected = $h;
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/stats.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Stats");
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/info.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Infos");
	  $h++;
	  
	  dol_fiche_head($head, $hselected, 'Contrat : '.$contrat->ref);
	  
	  print_fiche_titre('Fiche Contrat', $mesg);
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';	  	  
	  print '<tr><td width="20%">Référence</td><td>'.$contrat->ref.'</td>';
	  print '<td>Facturé : '.$contrat->facturable.'</td></tr>';
	  
	  print '<tr><td width="20%">Client</td><td>';
	  print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_comm->id.'">';
	  
	  print $client_comm->nom.'</a></td><td>'.$client_comm->code_client;
	  print '</td></tr>';
	  
	  if ($contrat->client_comm_id <> $contrat->client_id)
	    {	      	     
	      $client = new Societe($db, $contrat->client_id);
	      $client->fetch($contrat->client_id);
	      print '<tr><td width="20%">Client (Agence/Filiale)</td><td colspan="2">';
	      print $client->nom.'<br />';
	      print $client->cp . " " .$client->ville;
	      print '</td></tr>';
	    }
	  
	  $commercial = new User($db, $contrat->commercial_sign_id);
	  $commercial->fetch();
	  
	  print '<tr><td width="20%">Commercial Signature</td>';
	  print '<td colspan="2">'.$commercial->fullname.'</td></tr>';
	  
	  $commercial_suiv = new User($db, $contrat->commercial_suiv_id);
	  $commercial_suiv->fetch();
	  
	  print '<tr><td width="20%">Commercial Suivi</td>';
	  print '<td colspan="2">'.$commercial_suiv->fullname.'</td></tr>';

	  /* Contacts */
	  print '<tr><td valign="top" width="20%">Contact facture</td>';
	  print '<td valign="top" colspan="2">';

	  $sql = "SELECT c.rowid, c.name, c.firstname, c.email ";
	  $sql .= "FROM ".MAIN_DB_PREFIX."socpeople as c";
	  $sql .= ",".MAIN_DB_PREFIX."telephonie_contrat_contact_facture as cf";
	  $sql .= " WHERE c.rowid = cf.fk_contact AND cf.fk_contrat = ".$contrat->id." ORDER BY name ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row($i);

		      print $row[1] . " " . $row[2] . " &lt;".$row[3]."&gt;<br />";
		      $i++;
		    }
		}
	      $db->free();     

	    }
	  else
	    {
	      print $sql;
	    }
	  print '</td></tr>';
	  /* Fin Contacts */

	  print "</table><br />";


	  /* Services */
	     
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	      
	  $sql = "SELECT s.libelle, s.statut";
	  $sql .= " , cs.rowid as serid, s.montant, cs.montant as montant_fac";
	  $sql .= " , ".$db->pdate("cs.date_creat") . " as date_creat";
	  $sql .= " , u.name, u.firstname";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_service as cs";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_service as s";
	  $sql .= " , ".MAIN_DB_PREFIX."user as u";

	  $sql .= " WHERE cs.fk_service = s.rowid";
	  $sql .= " AND cs.fk_user_creat = u.rowid";
	  $sql .= " AND cs.fk_contrat = ".$contrat->id;
	      
	  if ( $db->query( $sql) )
	    {
	      $numlignes = $db->num_rows();
	      if ( $numlignes > 0 )
		{
		  $i = 0;		      
		  $ligne = new LigneTel($db);
		      
		  print '<tr class="liste_titre"><td>Service</td>';
		  print '<td align="right">Montant Facturé</td>';
		  print '<td align="right">Montant du service</td>';
		  if ($user->rights->telephonie->service->affecter)		
		    print "<td>&nbsp;</td>\n";
		  print '<td align="center">Ajouté par</td>';
		  print '<td align="center">Ajouté le</td></tr>';
		      
		  while ($i < $numlignes)
		    {
		      $obj = $db->fetch_object($i);	
		      $var=!$var;
			  
		      print "<tr $bc[$var]><td>";
			  
		      print '<img src="../graph'.$obj->statut.'.png">&nbsp;';
			  
			  
		      print '<a href="'.DOL_URL_ROOT.'/telephonie/service/fiche.php?id='.$obj->serid.'">'.$obj->libelle."</a></td>\n";
			  
		      print '<td align="right">'.price($obj->montant_fac)." euros HT</td>\n";
		      print '<td align="right">'.price($obj->montant)." euros HT</td>\n";

		      if ($user->rights->telephonie->service->affecter)
			{
			  print '<td align="center"><a href="services.php?id='.$contrat->id.'&amp;action=rmservice&amp;service_id='.$obj->serid.'">';
			  print img_delete();
			  print "</a></td>";
			}
		      print '<td align="center">'.$obj->firstname.' '.$obj->name.'</td>';
		      print '<td align="center">'.strftime("%d/%m/%y",$obj->date_creat).'</td>';
		      print "</tr>\n";
		      $i++;
		    }
		}
	      $db->free();     
		  
	    }
	  else
	    {
	      print $db->error();
	      print $sql;
	    }
	      
	  print "</table>";
	}	  	 
      /*
       * Service
       *
       *
       */      
      if ($user->rights->telephonie->service->affecter)
	{	  
	  print_fiche_titre('Ajouter un service', $mesg);
	  
	  print '<form action="services.php?id='.$contrat->id.'" method="post">';
	  print '<input type="hidden" name="action" value="addservice">';	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td valign="top" width="20%">Service</td><td valign="top" colspan="2">';
	  
	  $sql = "SELECT rowid, libelle ";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_service ";
	  $sql .= " WHERE statut = 1";
	  
	  if ( $db->query( $sql) )
	    {
	      print '<select name="service_id">';
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row($i);
		      print '<option value="'.$row[0] .'"';
		      print '>'.$row[1];
		      $i++;
		    }
		}
	      $db->free();     
	      print '</select>';
	    }
	  else
	    {
	      print $sql;
	    }
	  
	  print '</td></tr>';
	  
	  print '<tr><td colspan="3" align="center">';
	  if ($num > 0)
	    {
	      print '<input type="submit" value="Ajouter">';
	    }
	  print '</td></tr>';
	  print '</table>';
	  print '</form>';
	} 
      /*
       *
       *
       *
       */
	  
      print '</div>';
	  
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

print "\n<br>\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{  
      
}

print "</div>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
