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

require "./pre.inc.php";
require_once DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php";

$mesg = '';

if ($_POST["action"] == 'add')
{
  $contrat = new TelephonieContrat($db);

  $contrat->client_comm     = $_POST["client_comm"];
  $contrat->client          = $_POST["client"];
  $contrat->client_facture  = $_POST["client_facture"];
  $contrat->commercial_sign = $_POST["commercial_sign"];
  $contrat->note            = $_POST["note"];

  if ( $contrat->create($user) == 0)
    {
      Header("Location: fiche.php?id=".$contrat->id);
    }
  else
    {
      $_GET["action"] = 'create';
    }
  
}

if ($_POST["action"] == 'update' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  $contrat = new TelephonieContrat($db);
  $contrat->id = $_GET["id"];

  $contrat->client         = $_POST["client"];
  $contrat->client_facture = $_POST["client_facture"];
  $contrat->fournisseur    = $_POST["fournisseur"];
  $contrat->commercial     = $_POST["commercial"];
  $contrat->concurrent     = $_POST["concurrent"];
  $contrat->note           = $_POST["note"];
  $contrat->mode_paiement  = $_POST["mode_paiement"];

  $contrat->commercial_suiv_id  = $_POST["commercial_suiv"];

  if ( $contrat->update($user) == 0)
    {
      $action = '';
      $mesg = 'Fiche mise à jour';
      Header("Location: fiche.php?id=".$contrat->id);
    }
  else
    {
      $action = 're-edit';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $entrepot->mesg_error;
    }
}

if ($_POST["action"] == 'addcontact')
{
  $contrat = new TelephonieContrat($db);
  $contrat->id = $_GET["id"];

  if ( $contrat->add_contact_facture($_POST["contact_id"]) )
    {
      Header("Location: fiche.php?id=".$contrat->id);
    }
}

if ($_POST["action"] == 'addpo' && $user->rights->telephonie->ligne->creer)
{
  $contrat = new TelephonieContrat($db);
  $contrat->fetch($_GET["id"]);

  $contrat->addpo($_POST["montant"], $user);
  Header("Location: fiche.php?id=".$contrat->id);
}


if ($_GET["action"] == 'delcontact')
{
  $contrat = new TelephonieContrat($db);
  $contrat->id = $_GET["id"];

  if ( $contrat->del_contact_facture($_GET["contact_id"]) )
    {
      Header("Location: fiche.php?id=".$contrat->id);
    }
}

if ($_GET["action"] == 'delete' && $user->rights->telephonie->ligne->creer)
{
  $contrat = new TelephonieContrat($db);
  $contrat->id = $_GET["id"];

  $contrat->delete() ;    
  Header("Location: index.php");
}



llxHeader("","","Fiche Contrat");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}

/*
 * Création en 2 étape
 *
 */
if ($_GET["action"] == 'create')
{
  $form = new Form($db);
  print_titre("Nouveau contrat");

  if (is_object($ligne))
    {
      // La création a échouée
      print $ligne->error_message;
    }
  else
    {
      $ligne = new LigneTel($db);
    }

  print '<form action="fiche.php" method="GET">';
  print '<input type="hidden" name="action" value="create_line">';
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="20%">Client</td><td >';
  $ff = array();
  $sql = "SELECT idp, nom, ville FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ORDER BY nom ";
  if ( $db->query( $sql) )
    {
      $num = $db->num_rows();
      if ( $num > 0 )
	{
	  $i = 0;
	  while ($i < $num)
	    {
	      $row = $db->fetch_row($i);
	      $ff[$row[0]] = $row[1] . " (".$row[2].")";

	      $i++;
	    }
	}
      $db->free();      
    }
  $form->select_array("client_comm",$ff,$ligne->client_comm);
  print '</td></tr>';

  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>'."\n";
  print '</table>'."\n";
  print '</form>';
}
elseif ($_GET["action"] == 'create_line' && $_GET["client_comm"] > 0)
{
  $form = new Form($db);
  print_titre("Nouveau contrat");

  if (is_object($ligne))
    {
      // La création a échouée
      print $ligne->error_message;
    }
  else
    {
      $ligne = new LigneTel($db);
    }
      
  $socc = new Societe($db);

  if ( $socc->fetch($_GET["client_comm"]) == 1)
    {

      if (strlen($socc->code_client) == 0)
	{
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td width="20%">Client</td><td >';  
	  print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$socc->id.'">'.$socc->nom.'</a>';
	  print '</td></tr>';
	  
	  print '<tr><td width="20%">Code client</td><td >';  
	  print $socc->code_client;
	  print '</td></tr>';
	  print '</table><br /><br />';
	  print 'Impossible de créer un contrat pour cette société, vous devez au préalablement lui affecter un code client.';
	}
      elseif (strlen($socc->code_client) > 0 && $socc->check_codeclient() <> 0)
	{
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td width="20%">Client</td><td >';  
	  print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$socc->id.'">'.$socc->nom.'</a>';
	  print '</td></tr>';
	  
	  print '<tr><td width="20%">Code client</td><td >';  
	  print $socc->code_client;
	  print '</td></tr>';
	  print '</table><br /><br />';
	  print 'Le code client de cette société est incorrect, vous devez lui affecter un code client correct.';
	}
      else
	{
	  print "<form action=\"fiche.php\" method=\"post\">\n";
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="client_comm" value="'.$socc->id.'">'."\n";
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td width="20%">Client</td><td >';  
	  print $socc->nom;
	  print '</td></tr>';
	  
	  print '<tr><td width="20%">Code client</td><td >';  
	  print $socc->code_client;
	  print '</td></tr>';	  	 
	  
	  print '<tr><td width="20%">Client (Agence/Filiale)</td><td >';
	  $ff = array();
	  $sql = "SELECT idp, nom, ville FROM ".MAIN_DB_PREFIX."societe";
	  $sql .= " WHERE client=1";
	  $sql .= " AND (idp = $socc->id OR parent = $socc->id)";
	  $sql .= " ORDER BY nom ";

	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row();
		      $ff[$row[0]] = $row[1] . " (".$row[2].")";
		      $i++;
		    }
		}
	      $db->free();      
	    }
	  else
	    {
	      print $sql;
	    }
	  $form->select_array("client",$ff,$ligne->client);
	  print '</td></tr>';
	  
	  print '<tr><td width="20%">Client à facturer</td><td >';
	  $ff = array();
	  $sql = "SELECT idp, nom, ville FROM ".MAIN_DB_PREFIX."societe WHERE client=1";
	  $sql .= " AND (idp = $socc->id OR parent = $socc->id)";
	  $sql .= " ORDER BY nom ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row();
		      $ff[$row[0]] = $row[1] . " (".$row[2].")";
		      $i++;
		    }
		}
	      $db->free();     
	    }
	  $form->select_array("client_facture",$ff,$ligne->client_facture);
	  print '</td></tr>';
	  
	  /*
	   * Commercial
	   */
	  
	  print '<tr><td width="20%">Commercial Signature</td><td >';
	  $ff = array();
	  $sql = "SELECT u.rowid, u.firstname, u.name";
	  $sql .= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."usergroup_user as ug";
	  $sql .= " WHERE u.rowid = ug.fk_user";
	  $sql .= " AND ug.fk_usergroup = '".TELEPHONIE_GROUPE_COMMERCIAUX_ID."'";
	  $sql .= " ORDER BY name ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row($i);
		      $ff[$row[0]] = $row[1] . " " . $row[2];
		      $i++;
		    }
		}
	      $db->free();
	      
	    }
	  
	  $form->select_array("commercial_sign",$ff,$ligne->commercial);
	  
	  print '</td></tr>';
	  
	  print '<tr><td width="20%" valign="top">Note</td><td>'."\n";
	  print '<textarea name="note" rows="4" cols="50">'."\n";
	  print stripslashes($ligne->note);
	  print '</textarea></td></tr>'."\n";
	  
	  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>'."\n";
	  print '</table>'."\n";
	  print '</form>';
	  
	  /*
	   * Contrats existants
	   */
	  $sql = "SELECT c.rowid, c.ref, s.idp as socidp, s.nom ";
	  $sql .= ", sf.idp as sfidp, sf.nom as sfnom";
	  $sql .= ", sa.idp as saidp, sa.nom as sanom";
	  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	  $sql .= " , ".MAIN_DB_PREFIX."societe as sf";
	  $sql .= " , ".MAIN_DB_PREFIX."societe as sa";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";	  	  
	  $sql .= " WHERE c.fk_client_comm = s.idp";
	  $sql .= " AND c.fk_soc = sa.idp";
	  $sql .= " AND c.fk_soc_facture = sf.idp";	  	 
	  $sql .= " AND s.idp = ".$_GET["client_comm"];
	  
	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0;
	      if ($num > 0)
		{
		  print"<br />\n<!-- debut table -->\n";
		  print_titre("Contrats existants");
		  print '<br /><table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		  print '<tr class="liste_titre"><td>Réf</td>';	     
		  print '<td>Client</td><td>Client (Agence/Filiale)</td>';	      
		  print '<td>Client facturé</td>';
		  print "</tr>\n";
		  
		  $var=True;
		  
		  while ($i < $num)
		    {
		      $obj = $db->fetch_object();
		      $var=!$var;
		      
		      print "<tr $bc[$var]><td>";
		      print '<a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$obj->rowid.'">';
		      print img_file();      
		      print '</a>&nbsp;';
		      print '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";
		      
		      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socidp.'">'.stripslashes($obj->nom).'</a></td>';
		      
		      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socidp.'">'.stripslashes($obj->sanom).'</a></td>';
		      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->sfidp.'">'.stripslashes($obj->sfnom).'</a></td>';
		      
		      print "</tr>\n";
		      $i++;
		    }
		  print "</table>";
		}
	      
	      $db->free();
	    }
	  else 
	    {
	      print $db->error() . ' ' . $sql;
	    }	  
	}
    }
  else
    {
      print "Erreur";
    }
}
else
{
  if ($_GET["id"])
    {
      if ($_GET["action"] <> 're-edit')
	{
	  $contrat = new TelephonieContrat($db);

	  if ($contrat->fetch($_GET["id"]) == 0)
	    {
	      $result = 1;
	    }
	}

      if ( $result )
	{ 
	  if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	    {

	      $h=0;
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/fiche.php?id=".$contrat->id;
	      $head[$h][1] = $langs->trans("Contrat");
	      $hselected = $h;
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
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/info.php?id=".$contrat->id;
	      $head[$h][1] = $langs->trans("Infos");
	      $h++;

	      dolibarr_fiche_head($head, $hselected, 'Contrat : '.$contrat->ref);

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
	      	     
	      $client = new Societe($db, $contrat->client_id);
	      $client->fetch($contrat->client_id);

	      print '<tr><td width="20%">Client (Agence/Filiale)</td><td>';
	      print $client->nom.'<br />';

	      print $client->cp . " " .$client->ville;
	      print '</td><td colspan="2" valign="top">'.$client->code_client;

	      print '</td></tr>';

	      $client_facture = new Societe($db);
	      $client_facture->fetch($contrat->client_facture_id);

	      print '<tr><td width="20%">Client Facturé</td><td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid=';
	      print $client_facture->id.'">';
	      print $client_facture->nom.'</a><br />';
	      print $client_facture->cp . " " .$client_facture->ville;

	      print '</td><td valign="top">'.$client_facture->code_client;
	      print '</td><td>';



	      if ($contrat->mode_paiement == 'pre')
		{
		  print 'RIB : '.$client_facture->display_rib();
		}
	      else
		{
		  print 'Paiement par virement';
		}

	      print '</td></tr>';

	      $commercial = new User($db, $contrat->commercial_sign_id);
	      $commercial->fetch();

	      print '<tr><td width="20%">Commercial Signature</td>';
	      print '<td>'.$commercial->fullname.'</td>';

	      print '<td>Grille tarif</td><td>'.$contrat->grille_tarif_nom.'</td></tr>';

	      $commercial_suiv = new User($db, $contrat->commercial_suiv_id);
	      $commercial_suiv->fetch();

	      print '<tr><td width="20%">Commercial Suivi</td>';
	      print '<td colspan="3">'.$commercial_suiv->fullname.'</td></tr>';


	      /* Prise d'ordre */
	      print '<tr><td width="20%">Prise d\'ordre</td>';

	      $po = $contrat->priseordre_totale();

	      print '<td colspan="3">'.$po.' euros HT</td></tr>';

	      /*
	      print '<tr><td width="20%">Statut</td><td colspan="2">';	  
	      print '<img src="./graph'.$contrat->statut.'.png">&nbsp;';
	      print $contrat->statuts[$contrat->statut];
	      print '</td></tr>';
	      */


	      /* Contacts */
	      print '<tr><td valign="top" width="20%">Contact facture</td>';
	      print '<td valign="top" colspan="3">';

	      $sql = "SELECT c.idp, c.name, c.firstname, c.email ";
	      $sql .= "FROM ".MAIN_DB_PREFIX."socpeople as c";
	      $sql .= ",".MAIN_DB_PREFIX."telephonie_contrat_contact_facture as cf";
	      $sql .= " WHERE c.idp = cf.fk_contact AND cf.fk_contrat = ".$contrat->id." ORDER BY name ";
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

	      /* Lignes */
	     
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	      
	      $sql = "SELECT l.ligne, l.statut, l.rowid, l.remise, f.nom as fournisseur";
	      $sql .= ", ss.code_client, ss.nom as agence";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	      $sql .= " , ".MAIN_DB_PREFIX."societe as ss";
	      $sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
	      $sql .= " WHERE l.fk_fournisseur = f.rowid";
	      $sql .= " AND l.fk_soc = ss.idp ";
	      $sql .= " AND l.fk_contrat = ".$contrat->id;
	      
	      if ( $db->query( $sql) )
		{
		  $numlignes = $db->num_rows();
		  if ( $numlignes > 0 )
		    {
		      $i = 0;
		      
		      $ligne = new LigneTel($db);
		      
		      print '<tr class="liste_titre"><td width="15%" valign="center">Ligne';
		      print '</td><td>Agence/Filiale</td><td align="center">Statut</td><td align="center">Remise LMN';
		      print '</td><td>Fournisseur</td>';
		      
		      print "</tr>\n";
		      
		      while ($i < $numlignes)
			{
			  $obj = $db->fetch_object($i);	
			  $var=!$var;
			  
			  print "<tr $bc[$var]><td>";
			  
			  print '<img src="../graph'.$obj->statut.'.png">&nbsp;';
			  
			  print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">';
			  print img_file();
			  
			  print '</a>&nbsp;';
			  
			  print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">'.dolibarr_print_phone($obj->ligne)."</a></td>\n";
			  
			  print '<td>'.$obj->code_client."&nbsp;".$obj->agence."</td>\n";
			  print '<td align="center">'.$ligne->statuts[$obj->statut]."</td>\n";
			  
			  print '<td align="center">'.$obj->remise." %</td>\n";
			  print "<td>".$obj->fournisseur."</td>\n";
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
	   * Edition
	   *
	   *
	   *
	   */
	  
	  if ($_GET["action"] == 'edit' || $action == 're-edit')
	    {

	      $h=0;
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/fiche.php?id=".$contrat->id;
	      $head[$h][1] = $langs->trans("Contrat");
	      $hselected = $h;
	      $h++;
	      
	      dolibarr_fiche_head($head, $hselected, 'Contrat : '.$contrat->ref);

	      print_fiche_titre('Edition du contrat', $mesg);
	      
	      print '<form action="fiche.php?id='.$contrat->id.'" method="post">';
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	      print '<tr><td width="20%">Référence</td><td>'.$contrat->ref.'</td>';
	      print '<td>Facturé : '.$contrat->facturable.'</td></tr>';

	      $client_comm = new Societe($db, $contrat->client_comm_id);
	      $client_comm->fetch($contrat->client_comm_id);

	      print '<tr><td width="20%">Client</td><td>';
	      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_comm->id.'">';

	      print $client_comm->nom.'</a></td><td>'.$client_comm->code_client;
	      print '</td></tr>';
	      

	      print '<tr><td width="20%">Client (Agence/Filiale)</td><td colspan="2">';
	      print '<select name="client">';
	      
	      $sql = "SELECT idp, nom, ville FROM ".MAIN_DB_PREFIX."societe WHERE client=1";
	      $sql .= " AND (idp = $client_comm->id OR parent = $client_comm->id)";
	      $sql .= "  ORDER BY nom ";
	      if ( $db->query( $sql) )
		{
		  $num = $db->num_rows();
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($i);
			  print '<option value="'.$row[0] .'"';
			  if ($row[0] == $contrat->client_id)
			    {
			      print " SELECTED";
			    }
			  print '>'.stripslashes($row[1]). " (".stripslashes($row[2]).")";
		      $i++;
			}
		    }
		  $db->free();      
		}
	      
	      print '</select></td></tr>';
	      
	      print '<tr><td width="20%">Client à facturer</td><td colspan="2">'."\n";
	      print '<select name="client_facture">'."\n";
	      
	      
	      $sql = "SELECT idp, nom,ville FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ";
	      $sql .= " AND (idp = $client_comm->id OR parent = $client_comm->id)";
	      $sql .= "  ORDER BY nom ";
	      if ( $db->query( $sql) )
		{
		  $num = $db->num_rows();
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($i);
			  print '<option value="'.$row[0] .'"';
			  if ($row[0] == $contrat->client_facture_id)
			    {
			      print " SELECTED";
			    }
			  print '>'.stripslashes($row[1]). " (".stripslashes($row[2]).")";
			  
			  $i++;
			}
		    }
		  $db->free();     
		}
	      
	      print '</select></td></tr>';

	      /*
	       *
	       */
	      print '<tr><td width="20%">Mode de réglement</td>';
	      print '<td colspan="2">';

	      if ($user->rights->telephonie->contrat->paiement)
		{
		  print '<select name="mode_paiement">'."\n";
	      	     
		  if ($contrat->mode_paiement == 'pre')
		    {
		      print '<option value="pre" SELECTED>Prélèvement</option>';
		      print '<option value="vir">Virement</option>';
		    }
		  else
		    {
		      print '<option value="pre">Prélèvement</option>';
		      print '<option value="vir" SELECTED>Virement</option>';
		    }
		  print '</select>';
		}
	      else
		{		  
		  print '<input type="hidden" name="mode_paiement" value="'.$contrat->mode_paiement.'">';

		  if ($contrat->mode_paiement == 'pre')
		    {
		      print 'Prélèvement';
		    }
		  else
		    {
		      print 'Virement';
		    }
		}


	      print '</td></tr>';

	      
	      /*
	       * Commercial
	       */
	  
	      $commercial = new User($db, $contrat->commercial_sign_id);
	      $commercial->fetch();

	      print '<tr><td width="20%">Commercial Signature</td>';
	      print '<td colspan="2">'.$commercial->fullname.'</td></tr>';

	      print "\n".'<tr><td width="20%">Commercial Suivi</td><td colspan="2">';
	      print '<select name="commercial_suiv">';
	  
	      $sql = "SELECT u.rowid, u.name, u.firstname";
	      $sql .= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."usergroup_user as ug";
	      $sql .= " WHERE u.rowid = ug.fk_user";
	      $sql .= " AND ug.fk_usergroup = '".TELEPHONIE_GROUPE_COMMERCIAUX_ID."'";
	      $sql .= " ORDER BY name ";

	      if ( $db->query( $sql) )
		{
		  $num = $db->num_rows();
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($i);
			  print '<option value="'.$row[0] .'"';
			  if ($row[0] == $contrat->commercial_suiv_id)
			    {
			      print " SELECTED";
			    }
			  print '>'.$row[2]." ".$row[1];


			  $i++;
			}
		    }
		  $db->free();	      
		}
	  
	      print '</select></td></tr>';
	
	      /*
	       *
	       *
	       */
	      print '<tr><td width="20%" valign="top">Note</td><td colspan="2">';
	      print '<textarea name="note" rows="4" cols="50">';
	      print "</textarea></td></tr>";
	  
	      print '<tr><td align="center" colspan="3"><input type="submit" value="Mettre à jour">';
	      print '<a class="tabAction" href="fiche.php?id='.$contrat->id.'">Annuler</a></td></tr>';
	      print '</table>'."\n";
	      print '</form>'."\n";
	  
	    }

	  /*
	   * Contact
	   *
	   *
	   */
	  if ($_GET["action"] == 'contact')
	    {
	      print_fiche_titre('Ajouter un contact', $mesg);

	      print '<form action="fiche.php?id='.$contrat->id.'" method="post">';
	      print '<input type="hidden" name="action" value="addcontact">';

	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	      $sql = "SELECT c.idp, c.name, c.firstname, c.email ";
	      $sql .= "FROM ".MAIN_DB_PREFIX."socpeople as c";
	      $sql .= ",".MAIN_DB_PREFIX."telephonie_contrat_contact_facture as cf";
	      $sql .= " WHERE c.idp = cf.fk_contact ";
	      $sql .= " AND cf.fk_contrat = ".$contrat->id." ORDER BY name ";

	      if ( $db->query( $sql) )
		{
		  $num = $db->num_rows();
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($i);

			  print '<tr><td valign="top" width="20%">Contact facture '.$i.'</td>';
			  print '<td valign="top">'.$row[1] . " " . $row[2] . " &lt;".$row[3]."&gt;";
			  print '</td><td>';
			  print '<a href="fiche.php?id='.$contrat->id.'&amp;action=delcontact&amp;contact_id='.$row[0].'">';
			  print img_delete();
			  print "</a></td></tr>";
			  $i++;
			}
		    }
		  $db->free();     

		}
	      else
		{
		  print $sql;
		}


	      print '<tr><td valign="top" width="20%">Contact</td><td valign="top" colspan="2">';
	  	 
	      $sql = "SELECT idp, name, firstname, email ";
	      $sql .= " FROM ".MAIN_DB_PREFIX."socpeople ";
	      $sql .= " WHERE fk_soc in (".$contrat->client_facture_id.",".$contrat->client_id.")";
	      $sql .= " ORDER BY name ";

	      if ( $db->query( $sql) )
		{
		  print '<select name="contact_id">';
		  $num = $db->num_rows();
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($i);
			  print '<option value="'.$row[0] .'"';
			  print '>'.$row[1] . " " . $row[2] . " &lt;".$row[3]."&gt;";
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
	  
	      print '<p>Contact auquel est envoyé la facture par email</p></td></tr>';
	  	  
	      print '<tr><td colspan="3" align="center">';
	      if ($num > 0)
		{
		  print '<input type="submit" value="Ajouter">';
		}
	      print '<a href="fiche.php?id='.$contrat->id.'">Annuler</a></td></tr>';
	      print '</table>';
	      print '</form>';
	  
	    }

	  /*
	   * Contact
	   *
	   *
	   */
	  if ($_GET["action"] == 'po')
	    {
	      print_fiche_titre('Ajouter une prise d\'ordre');

	      print '<form action="fiche.php?id='.$contrat->id.'" method="post">';
	      print '<input type="hidden" name="action" value="addpo">';
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	      print '<tr><td valign="top" width="20%">Montant</td><td valign="top" colspan="2">';
	      print '<input name="montant" size="8"> euros HT</td></tr>';	  
	      print '</td></tr>';	  	  
	      print '<tr><td colspan="3" align="center">';
	      print '<input type="submit" value="Ajouter">';

	      print '<a href="fiche.php?id='.$contrat->id.'">Annuler</a></td></tr>';
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
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "\n<br>\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{  

  if ($user->rights->telephonie->ligne->creer)
    {
      print '<a class="tabAction" href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?action=create&amp;contratid='.$contrat->id.'">Nouvelle ligne</a>';
    }
  
  print "<a class=\"tabAction\" href=\"fiche.php?action=contact&amp;id=$contrat->id\">".$langs->trans("Contact")."</a>";

  if ($user->rights->telephonie->ligne->creer)
    {
      print "<a class=\"tabAction\" href=\"fiche.php?action=po&amp;id=$contrat->id\">Ajouter une prise d'ordre</a>";
    }

  print "<a class=\"tabAction\" href=\"fiche.php?action=edit&amp;id=$contrat->id\">".$langs->trans("Edit")."</a>";

  if ($user->rights->telephonie->ligne->creer && $numlignes == 0)
    {
  print "<a class=\"butDelete\" href=\"fiche.php?action=delete&amp;id=$contrat->id\">".$langs->trans("Delete")."</a>";
    }

      
}

print "</div>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
