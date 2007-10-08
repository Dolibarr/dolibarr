<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$dt = time();

$h = strftime("%H",$dt);
$m = strftime("%M",$dt);
$s = strftime("%S",$dt);

if ($_POST["action"] == 'add')
{
  $ligne = new LigneAdsl($db);

  $ligne->numero         = $_POST["numero"];
  $ligne->client         = $_POST["client"];
  $ligne->client_install = $_POST["client_install"];
  $ligne->client_facture = $_POST["client_facture"];
  $ligne->contrat        = $_POST["contrat"];
  $ligne->fournisseur    = $_POST["fournisseur"];
  $ligne->commercial     = $_POST["commercial"];
  $ligne->type           = $_POST["type"];
  $ligne->note           = $_POST["note"];


  if ( $ligne->create($user) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
  else
    {
      $_GET["action"] = 'create_line';
      $_GET["client"] = $_POST["client"];
    }
  
}

if ($_GET["action"] == 'delete' && $user->rights->telephonie->adsl->creer)
{
  $ligne = new LigneAdsl($db);

  if ( $ligne->delete($_GET["id"]) == 0)
    {
      Header("Location: liste.php");
    }

}

if ($_GET["action"] == 'ordertech')
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $ligne->set_statut($user, 1) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}

if ($_GET["action"] == 'cancelordertech')
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $ligne->set_statut($user, -1) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}

if ($_GET["action"] == 'refuse')
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  if ( $ligne->set_statut($user, 7, $datea, $_POST["commentaire"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}

if ($_GET["action"] == 'resilier')
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 4)
    {
      if ( $ligne->set_statut($user, 5, $datea, $_POST["commentaire"]) == 0)
	{
	  Header("Location: fiche.php?id=".$ligne->id);
	}
    }
  else
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'annuleresilier')
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 5)
    {
      if ( $ligne->set_statut($user, 4) == 0)
	{
	  Header("Location: fiche.php?id=".$ligne->id);
	}
    }
  else
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'resilierfourn')
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 5)
    {
      if ( $ligne->set_statut($user, 6, $datea, $_POST["commentaire"]) == 0)
	{
	  Header("Location: fiche.php?id=".$ligne->id);
	}
    }
  else
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'acquitresilierfourn')
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 6)
    {
      if ( $ligne->set_statut($user, 7, $datea, $_POST["commentaire"]) == 0)
	{
	  Header("Location: fiche.php?id=".$ligne->id);
	}
    }
  else
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'commandefourn' && $user->rights->telephonie->adsl->commander)
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  if ( $ligne->set_statut($user, 2, $datea, $_POST["commentaire"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'activefourn' && $user->rights->telephonie->adsl->gerer)
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  $ligne->update_info($_POST["ip"], $_POST["login"], $_POST["password"]);


  if ( $ligne->set_statut($user, 3, $datea, $_POST["commentaire"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'livraison' && $user->rights->telephonie->adsl->gerer)
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  if ( $ligne->set_statut($user, 4, $datea, $_POST["commentaire"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'backbone' && $user->rights->telephonie->adsl->gerer)
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  if ( $ligne->set_statut($user, 9, $datea, $_POST["commentaire"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'acommander')
{
  $ligne = new LigneAdsl($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $ligne->set_statut($user, 1, '', $_POST["commentaire"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}


if ($_POST["action"] == 'update' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  $ligne = new LigneAdsl($db);
  $ligne->id = $_GET["id"];

  $ligne->numero         = $_POST["numero"];
  $ligne->client_comm    = $_POST["client_comm"];
  $ligne->client         = $_POST["client"];
  $ligne->client_facture = $_POST["client_facture"];
  $ligne->fournisseur    = $_POST["fournisseur"];
  $ligne->commercial     = $_POST["commercial"];
  $ligne->concurrent     = $_POST["concurrent"];
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

llxHeader("","","Fiche Liaison");

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
  print_titre("Nouvelle liaison ADSL");

  if (is_object($ligne))
    {
      // La création a échouée
      print $ligne->error_message;
    }
  else
    {
      $ligne = new LigneAdsl($db);
    }

  print '<form action="fiche.php" method="GET">';
  print '<input type="hidden" name="action" value="create_line">';
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="20%">Client</td><td >';
  $ff = array();
  $sql = "SELECT rowid, nom, ville FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ORDER BY nom ";
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
	      $ff[$row[0]] = stripslashes($row[1]) . " (".$row[2].")";
	      $i++;
	    }
	}
      $db->free($resql);
    }
  $form->select_array("client",$ff,$ligne->client_comm);
  print '</td></tr>';

  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>'."\n";
  print '</table>'."\n";
  print '</form>';
}
elseif ($_GET["action"] == 'create_line' && $_GET["client"] > 0)
{
  $form = new Form($db);
  print_titre("Nouvelle liaison ADSL");

  if (is_object($ligne))
    {
      // La création a échouée
      print '<div class="errormessage">'.$ligne->error_message.'</div>';
    }
  else
    {
      $ligne = new LigneAdsl($db);
      $ligne->client_install_id = $_GET["client"];
      $ligne->client_facture_id = $_GET["client"];
    }
      
  $socc = new Societe($db);
  if ( $socc->fetch($_GET["client"]) == 1)
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
	  print 'Impossible de créer une ligne pour cette société, vous devez au préalablement lui affecter un code client.';
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
	  print '<input type="hidden" name="client" value="'.$socc->id.'">'."\n";
	  if ($_GET['contratid'] > 0)
	    {
	      print '<input type="hidden" name="contrat" value="'.$_GET['contratid'].'">'."\n";
	    }

	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td width="20%">Client</td><td >';  
	  print "$socc->nom ($socc->code_client)";
	  print '</td></tr>';
	  
	  print '<tr><td width="20%">Contrat</td><td>';

	  $contrats = array();
	  $contrats_id = array();
	  $sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."telephonie_contrat WHERE fk_soc = ".$socc->id.";";
	  if ( $resql = $db->query( $sql) )
	    {
	      $i = 0;
	      while ($row = $db->fetch_row($resql))
		{
		  $contrats[$i] = $row;
		  $contrats_id[$row[0]] = $row[1];
		  $i++;
		}
	      $db->free($resql);      
	    }

	  if ($i == 0)
	    {
	      print "Pas de contrat en cours";
	      print '</td></tr>';
	    }
	  elseif($i == 1)
	    {
	      print $contrats[0][1];
	      print '</td></tr>';
	      print '<input type="hidden" name="contrat" value="'.$contrats[0][1].'">'."\n";
	    }
	  else
	    {
	      $form->select_array("contrat",$contrats_id);
	      print '</td></tr>';
	    }

	  // On continue si il existe des contrats
	  if (sizeof($contrats) > 0)
	    {
	      print '<tr><td width="20%">Numéro de la ligne téléphonique</td><td><input name="numero" size="12" value="'.$ligne->numero.'"></td></tr>';
	  
	  print '<tr><td width="20%">Client (Agence/Filiale)</td><td >';
	  $ff = array();
	  $sql = "SELECT rowid, nom, ville FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ORDER BY nom ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row();
		      $ff[$row[0]] = stripslashes($row[1]) . " (".$row[2].")";
		      $i++;
		    }
		}
	      $db->free();      
	    }
	  $form->select_array("client_install",$ff,$ligne->client_install_id);
	  print " (Correspond à l'adresse d'installation)</td></tr>";
	  
	  print '<tr><td width="20%">Client à facturer</td><td >';
	  $ff = array();
	  $sql = "SELECT rowid, nom, ville FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ORDER BY nom ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row();
		      $ff[$row[0]] = stripslashes($row[1]) . " (".$row[2].")";
		      $i++;
		    }
		}
	      $db->free();     
	    }
	  $form->select_array("client_facture",$ff,$ligne->client_facture_id);
	  print '</td></tr>';
	  
	  /*
	   * Type
	   */

	  print '<tr><td width="20%">Débit de la ligne</td><td >';
	  $ff = array();
	  $sql = "SELECT rowid, intitule FROM ".MAIN_DB_PREFIX."telephonie_adsl_type WHERE commande_active = 1 ORDER BY intitule ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row();
		      $ff[$row[0]] = $row[1];
		      $i++;
		    }
		}
	      $db->free();
	      
	    }
	  $form->select_array("type",$ff,$ligne->type);
	  print '</td></tr>';
	  /*
	   * Fournisseur
	   */
	  print '<tr><td width="20%">Fournisseur</td><td >';
	  $ff = array();
	  $sql = "SELECT f.rowid, f.nom FROM ".MAIN_DB_PREFIX."societe as f";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_adsl_fournisseur as fa";
	  $sql .= " WHERE fa.commande_active = 1 AND fa.fk_soc = f.rowid ORDER BY f.nom ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row();
		      $ff[$row[0]] = stripslashes($row[1]);
		      $i++;
		    }
		}
	      $db->free();
	    }
	  else
	    {
	      print $db->error();
	    }
	  $form->select_array("fournisseur",$ff,$ligne->fournisseur);
	  print '</td></tr>';
	  
	  /*
	   * Commercial
	   */
	  print '<tr><td width="20%">Commercial</td><td >';
	  $ff = array();
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
		      $ff[$row[0]] = stripslashes($row[1]) . " " . stripslashes($row[2]);
		      $i++;
		    }
		}
	      $db->free();
	      
	    }
      
	  $form->select_array("commercial",$ff,$ligne->commercial);
      
	  print '</td></tr>';
      
	  print '<tr><td width="20%" valign="top">Note</td><td>'."\n";
	  print '<textarea name="note" rows="4" cols="50">'."\n";
	  print stripslashes($ligne->note);
	  print '</textarea></td></tr>'."\n";
	  
	  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>'."\n";
	  print '</table>'."\n";
	  print '</form>';
	    }
	  else
	    {
	  print '</table>'."\n";
	  print '</form>';
	      print '<a href="../contrat/fiche.php?action=create_line&amp;client_comm='.$_GET["client"].'">Nouveau contrat</a>';
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
  if ($_GET["id"] or $_GET["numero"])
    {
      if ($_GET["action"] <> 're-edit')
	{
	  $ligne = new LigneAdsl($db);
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
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/adsl/fiche.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans("Liaison ADSL");
	      $hselected = $h;
	      $h++;
	      
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/adsl/history.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Historique');
	      $h++;

	      dolibarr_fiche_head($head, $hselected, 'Liaison ADSL : '.$ligne->numero);

	      print_fiche_titre('Fiche Liaison ADSL', $mesg);
      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	      $client = new Societe($db, $ligne->client_id);
	      $client->fetch($ligne->client_id);

	      print '<tr><td width="20%">Client</td><td>';
	      print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$client->id.'">';

	      print $client->nom.'</a></td><td>'.$client->code_client;
	      print '</td></tr>';

	      print '<tr><td width="20%">Numéro</td><td>'.dolibarr_print_phone($ligne->numero).'</td>';
	      print '<td>&nbsp;</td></tr>';

	      print '<tr><td width="20%">Débit de la liaison</td><td>'.$ligne->type.'</td>';
	      print '<td>Prix de vente : '.price($ligne->prix).' euros HT</td></tr>';
	      	     
	      $client_install = new Societe($db, $ligne->client_install_id);
	      $client_install->fetch($ligne->client_install_id);

	      print '<tr><td width="20%">Client (Agence/Filiale)</td><td colspan="2">';
	      print $client_install->nom.'<br />';

	      print $client_install->cp . " " .$client_install->ville;
	      print '</td></tr>';

	      $client_facture = new Societe($db);
	      $client_facture->fetch($ligne->client_facture_id);

	      print '<tr><td width="20%">Client Facturé</td><td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid=';
	      print $client_facture->id.'">';
	      print $client_facture->nom.'</a><br />';
	      print $client_facture->cp . " " .$client_facture->ville;

	      print '</td><td>';

	      if ($ligne->mode_paiement == 'pre')
		{
		  print 'RIB : '.$client_facture->display_rib();
		}
	      else
		{
		  print 'Paiement par virement';
		}

	      print '</td></tr>';
	      print '<tr><td width="20%">Contrat</td>';
	      print '<td colspan="2"><a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$ligne->contrat_id.'">';
	      print substr("00000000".$ligne->contrat_id,-8).'</a></td></tr>';

	      $commercial = new User($db, $ligne->commercial_id);
	      $commercial->fetch();

	      print '<tr><td width="20%">Commercial</td>';
	      print '<td colspan="2">'.$commercial->fullname.'</td></tr>';


	      if ( $user->rights->telephonie->adsl->gerer)
		{
		  print '<tr><td width="20%">IP</td><td colspan="2">'.$ligne->ip.'</td></tr>';
		  print '<tr><td width="20%">Login</td><td colspan="2">'.$ligne->login.'</td></tr>';
		  print '<tr><td width="20%">Password</td><td colspan="2">'.$ligne->password.'</td></tr>';
		}

	      print '<tr><td width="20%">Statut</td><td colspan="2">';	  
	      print '<img src="./statut'.$ligne->statut.'.png">&nbsp;';
	      print $ligne->statuts[$ligne->statut];
	      print '</td></tr>';



	      print "</table>";
	    }
	
	  print '</div>';

	}
    }
  else
    {
      print "Error";
    }
}

$form = new Form($db);

if ( $user->rights->telephonie->adsl->commander && $ligne->statut == 1)
{
  /**
   * 
   */

  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';

  print '<form name="commandefourn" action="fiche.php?id='.$ligne->id.'&amp;action=commandefourn" method="POST">';
  print '<table class="noborder" cellpadding="2" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Commande chez le fournisseur</td><td>';
  print '<tr><td>Date de la commande</td><td>';
  print $form->select_date('','','','','',"commandefourn");
  print '</td>';
  print '<td colspan="2"><input type="submit" name="Commander"></td></tr>';
  print '<tr><td colspan="3">Commentaire <input size="30" type="text" name="commentaire"></td></tr>';
  print '</table>';

  print '</form></td><td>';

  print '&nbsp;</td></tr></table>';
}

if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 2)
{
  /**
   * 
   */

  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';

  print '<form name="activefourn" action="fiche.php?id='.$ligne->id.'&amp;action=activefourn" method="POST">';
  print '<table class="noborder" cellpadding="2" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Activée chez le fournisseur</td><td>';
  print "<tr><td>Date de l'activation</td><td>";
  print $form->select_date('','','','','',"activefourn");
  print '</td></tr>';

  print '<tr><td>Commentaire</td><td><input size="30" type="text" name="commentaire"></td></tr>';

  print '<tr><td>Adresse IP affectée</td><td><input size="30" type="text" name="ip"></td></tr>';
  print '<tr><td>Login</td><td><input size="30" type="text" name="login"></td></tr>';
  print '<tr><td>Password</td><td><input size="30" type="text" name="password"></td></tr>';

  print '<tr><td colspan="2"><input type="submit" name="Commander"></td></tr>';

  print '</table>';

  print '</form></td><td>';

  print '&nbsp;</td></tr></table>';
}

if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 3)
{
  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';

  print '<form name="backbone" action="fiche.php?id='.$ligne->id.'&amp;action=backbone" method="POST">';
  print '<table class="noborder" cellpadding="2" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Programmé sur le backbone</td><td>';
  print "<tr><td>Date de la programmation</td><td>";
  print $form->select_date('','','','','',"backbone");
  print '</td>';
  print '<td colspan="2"><input type="submit" name="Programmer"></td></tr>';
  print '<tr><td colspan="3">Commentaire <input size="30" type="text" name="commentaire"></td></tr>';
  print '</table>';
  print '</form></td><td>';
  print '&nbsp;</td></tr></table>';
}

if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 9)
{
  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';

  print '<form name="livraison" action="fiche.php?id='.$ligne->id.'&amp;action=livraison" method="POST">';
  print '<table class="noborder" cellpadding="2" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Livrée au client</td><td>';
  print "<tr><td>Date de la livraison client</td><td>";
  print $form->select_date('','','','','',"livraison");
  print '</td>';
  print '<td colspan="2"><input type="submit" name="Commander"></td></tr>';
  print '<tr><td colspan="3">Commentaire <input size="30" type="text" name="commentaire"></td></tr>';
  print '</table>';
  print '</form></td><td>';
  print '&nbsp;</td></tr></table>';
}

if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 4)
{
  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';
  print '<form name="resilier" action="fiche.php?id='.$ligne->id.'&amp;action=resilier" method="POST">';
  print '<table class="noborder" cellpadding="4" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">A résilier</td><td>';
  print '<tr class="pair"><td>Date de résiliation demandée</td><td>';
  print $form->select_date('','','','','',"resilier");
  print '</td></tr>';
  print '<tr class="pair"><td>Commentaire</td><td><input size="30" type="text" name="commentaire"></td></tr>';
  print '<tr class="pair"><td colspan="2" align="center"><input type="submit" name="Commander"></td></tr>';
  print '</table></form></td><td>';
  print '&nbsp;</td></tr></table>';
}

if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 5)
{
  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';
  print '<form name="resilierfourn" action="fiche.php?id='.$ligne->id.'&amp;action=resilierfourn" method="POST">';
  print '<table class="noborder" cellpadding="4" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Demande de résiliatin fournisseur</td><td>';
  print '<tr class="pair"><td>Date de la demande de résiliation</td><td>';
  print $form->select_date('','','','','',"resilierfourn");
  print '</td></tr>';
  print '<tr class="pair"><td>Commentaire</td><td><input size="30" type="text" name="commentaire"></td></tr>';
  print '<tr class="pair"><td colspan="2" align="center"><input type="submit" name="Commander"></td></tr>';
  print '</table></form></td><td>';
  print '&nbsp;</td></tr></table>';
}

if ( $user->rights->telephonie->adsl->gerer && $ligne->statut == 6)
{
  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';
  print '<form name="acquitresilierfourn" action="fiche.php?id='.$ligne->id.'&amp;action=acquitresilierfourn" method="POST">';
  print '<table class="noborder" cellpadding="4" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Confirmation de résiliatin fournisseur</td><td>';
  print '<tr class="pair"><td>Date de la confirmation de résiliation</td><td>';
  print $form->select_date('','','','','',"acquitresilierfourn");
  print '</td></tr>';
  print '<tr class="pair"><td>Commentaire</td><td><input size="30" type="text" name="commentaire"></td></tr>';
  print '<tr class="pair"><td colspan="2" align="center"><input type="submit" name="Commander"></td></tr>';
  print '</table></form></td><td>';
  print '&nbsp;</td></tr></table>';
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "\n<br>\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{

  if ( $user->rights->telephonie->adsl->requete && $ligne->statut == -1)
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=ordertech&amp;id=$ligne->id\">".$langs->trans("Commander")."</a>";
    }

  if ( $user->rights->telephonie->adsl->requete && $ligne->statut == 1)
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=cancelordertech&amp;id=$ligne->id\">".$langs->trans("Annuler la commande")."</a>";

    }

  if ( $user->rights->telephonie->adsl->requete && $ligne->statut == 5)
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=annuleresilier&amp;id=$ligne->id\">".$langs->trans("Annuler la demande de résiliation")."</a>";

    }

  if ( $user->rights->telephonie->adsl->creer && $ligne->statut == -1)
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=delete&amp;id=$ligne->id\">".$langs->trans("Delete")."</a>";

    }
}

print "</div>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
