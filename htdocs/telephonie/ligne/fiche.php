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

require "./pre.inc.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php";
require_once DOL_DOCUMENT_ROOT.'/telephonie/telephonie.contrat.class.php';

$mesg = '';

$dt = time();

$h = strftime("%H",$dt);
$m = strftime("%M",$dt);
$s = strftime("%S",$dt);

if ($_POST["action"] == 'add' && $user->rights->telephonie->ligne->creer)
{
  $result = 0;
  
  $cbegin = trim($_POST["numero"]);
  $cend = strlen(trim($_POST["numero_end"]))>0?trim($_POST["numero_end"]):trim($_POST["numero"]);

  $cli = $cbegin;
  
  while ($cli <= $cend)
    {     
      $ligne = new LigneTel($db);
      $ligne->contrat         = $_POST["contrat"];
      $ligne->numero          = "0".$cli;
      $ligne->client_comm     = $_POST["client_comm"];
      $ligne->client          = $_POST["client"];
      $ligne->client_facture  = $_POST["client_facture"];
      $ligne->fournisseur     = $_POST["fournisseur"];
      $ligne->commercial_sign = $_POST["commercial_sign"];
      $ligne->commercial_suiv = $_POST["commercial_suiv"];
      $ligne->concurrent      = $_POST["concurrent"];
      $ligne->remise          = $_POST["remise"];
      $ligne->note            = $_POST["note"];
      $ligne->techno          = $_POST["techno"];
      
      $result += $ligne->create($user);
      
      $cli++;
    }
    
  if ( $result == 0)
    {
      Header("Location: ../contrat/fiche.php?id=".$ligne->contrat);
    }
  else
    {
      $_GET["action"] = 'create';
      $_GET["contratid"] = $_POST["contrat"];

      $ligne->numero = $_POST["numero"];
      $ligne->numero_end = $_POST["numero_end"];
    }  
}

if ($_GET["action"] == 'transfer' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $ligne->transfer($user,$_POST["fournisseur"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $ligne->delete($user) == 0)
    {
      Header("Location: index.php");
    }
}

if ($_POST["action"] == 'updateremise' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->SetRemise($user, $_POST["remise"], $_POST["comment"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
  else
    {
      $_GET["action"] = 'editremise';
    }  
}

if ($_POST["action"] == 'changecontrat' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->ChangeContrat($user, $_POST["contrat"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    } 
}

if ($_POST["action"] == 'addcontact' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->add_contact($_POST["contact_id"]) )
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}


if ($_GET["action"] == 'delcontact' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  if ( $ligne->del_contact($_GET["contact_id"]) )
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}

if ($_GET["action"] == 'active' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  if ( $ligne->set_statut($user, 3, $datea) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }

}

if ($_GET["action"] == 'refuse' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
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

if ($_GET["action"] == 'resilier' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $ligne->set_statut($user, 4) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'annuleresilier' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $ligne->set_statut($user, 3) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'confirmresilier' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->fetch_by_id($_GET["id"]);

  $datea = $db->idate(mktime($h, $m , $s,
			    $_POST["remonth"], 
			    $_POST["reday"],
			    $_POST["reyear"]));

  $comm = new User($db,$ligne->commercial_id);
  $comm->fetch();

  $soc = new Societe($db);
  $soc->fetch($ligne->socid);

  if ( $ligne->set_statut($user, 6, $datea, $_POST["commentaire"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}

if ($_GET["action"] == 'acommander' && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->fetch_by_id($_GET["id"]);

  if ( $ligne->set_statut($user, 1, '', $_POST["commentaire"]) == 0)
    {
      Header("Location: fiche.php?id=".$ligne->id);
    }
}


if ($_POST["action"] == 'update' && $_POST["cancel"] <> $langs->trans("Cancel") && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  $ligne->numero         = $_POST["numero"];
  $ligne->fournisseur    = $_POST["fournisseur"];
  $ligne->concurrent     = $_POST["concurrent"];
  $ligne->note           = $_POST["note"];

  if ( $ligne->update($user) )

    {
      $action = '';
      $mesg = 'Fiche mise � jour';
    }
  else
    {
      $action = 're-edit';
      $mesg = 'Fiche non mise � jour !' . "<br>" . $entrepot->mesg_error;
    }
}


llxHeader("","","Fiche Ligne");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}

/*
 * Cr�ation en 2 �tape
 *
 */
if ($_GET["action"] == 'create1')
{
  $form = new Form($db);
  print_titre("Nouvelle ligne");

  if (is_object($ligne))
    {
      // La cr�ation a �chou�e
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
  $sql = "SELECT rowid, nom, ville FROM ".MAIN_DB_PREFIX."societe WHERE client=1 ORDER BY nom ";
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

  print '<tr><td>&nbsp;</td><td><input type="submit" value="Cr�er"></td></tr>'."\n";
  print '</table>'."\n";
  print '</form>';
}
elseif ($_GET["action"] == 'create' && $_GET["contratid"] > 0)
{

  $contrat = new TelephonieContrat($db);
  $contrat->fetch($_GET["contratid"]);


  $form = new Form($db);
  print_titre("Nouvelle(s) ligne(s) sur le contrat : ".$contrat->ref);

  if (is_object($ligne))
    {
      // La cr�ation a �chou�e
      print $ligne->error_message;
    }
  else
    {
      $ligne = new LigneTel($db);
    }

      
  $socc = new Societe($db);
  //if ( $socc->fetch($_GET["client_comm"]) == 1)
  if ( $socc->fetch($contrat->client_comm_id) == 1)
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
	  print 'Impossible de cr�er une ligne pour cette soci�t�, vous devez au pr�alablement lui affecter un code client.';
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
	  print 'Le code client de cette soci�t� est incorrect, vous devez lui affecter un code client correct.';
	}
      else
	{
	  print '<form action="fiche.php" method="post">';
	  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="contrat" value="'.$contrat->id.'">'."\n";
	  print '<input type="hidden" name="client_comm" value="'.$socc->id.'">'."\n";
	  print '<input type="hidden" name="commercial_suiv" value="'.$contrat->commercial_suiv_id.'">'."\n";
	  print '<input type="hidden" name="commercial_sign" value="'.$contrat->commercial_sign_id.'">'."\n";
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	  print '<tr><td width="20%">Contrat</td><td colspan="2">'.$contrat->ref_url.'</td></tr>';

	  print '<tr><td width="20%">Client</td><td colspan="2">'.$socc->nom.'</td></tr>';
	  
	  print '<tr><td width="20%">Code client</td><td colspan="2">'.$socc->code_client.'</td></tr>';
	  	 
	  print '<tr><td width="20%">Num�ro</td><td>0<input name="numero" size="10" maxlength="9" value="'.$ligne->numero.'"></td>';
	  print '<td>0<input name="numero_end" size="10" maxlength="9" value="'.$ligne->numero_end.'"> derniere SDA</td></tr>';

	  $client = new Societe($db, $contrat->client_id);
	  $client->fetch($contrat->client_id);
	  
	  print '<tr><td width="20%">Client (Agence/Filiale)</td><td colspan="2">';
	  print $client->nom.'<br />';
	  
	  print $client->cp . " " .$client->ville;
	  print '</td></tr>';
	  print '<input type="hidden" name="client" value="'.$contrat->client_id.'">';

	  $client_facture = new Societe($db);
	  $client_facture->fetch($contrat->client_facture_id);
	  
	  print '<tr><td width="20%">Client Factur�</td><td>';
	  print $client_facture->nom.'<br />';
	  print $client_facture->cp . " " .$client_facture->ville;
	  
	  print '</td><td>';
	  print '<input type="hidden" name="client_facture" value="'.$contrat->client_facture_id.'">';

	  print '<tr><td width="20%">Fournisseur</td><td>';
	  $ff = array();
	  $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur WHERE commande_active = 1 ORDER BY nom ";
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
	  $form->select_array("fournisseur",$ff,$ligne->fournisseur);

	  $tech = array();
	  $tech["presel"] = "Pr�s�lection";
	  $tech["voip"] = "VoIP";
	  print "</td><td>Technologie&nbsp;:&nbsp;";
	  $form->select_array("techno",$tech,"presel");

	  print '</td></tr>';
	  
	  /*
	   * Concurrents
	   */
	  
	  print '<tr><td width="20%">Fournisseur pr�c�dent</td><td colspan="2">';
	  $ff = array();
	  $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_concurrents ORDER BY rowid ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row($i);
		      $ff[$row[0]] = $row[1];
		      $i++;
		    }
		}
	      $db->free();
	      
	    }
	  $form->select_array("concurrent",$ff,$ligne->concurrent);
	  print '</td></tr>';
	  
	  print '<tr><td width="20%">Remise LMN</td><td><input name="remise" size="3" maxlength="2" value="'.$ligne->remise.'">&nbsp;%</td></tr>'."\n";
	  
	  print '<tr><td width="20%" valign="top">Note</td><td colspan="2">'."\n";
	  print '<textarea name="note" rows="4" cols="50">'."\n";
	  print stripslashes($ligne->note);
	  print '</textarea></td></tr>'."\n";
	  
	  print '<tr><td>&nbsp;</td><td colspan="2"><input type="submit" value="Cr�er"></td></tr>'."\n";
	  print '</table>'."\n";
	  print '</form>';	  
	}      
    }
  else
    {
      print "Erreur";
    }

}
else
{
  /*
   * Mode Visualisation
   *
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
	      $hselected = $h;
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
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Conso');
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/stat.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Stats');
	      $h++;
	      
	      $ligne->load_previous_next_id();

	      $previous_ref = $ligne->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?id='.$ligne->ref_previous.'">'.img_previous().'</a>':'';
	      $next_ref     = $ligne->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?id='.$ligne->ref_next.'">'.img_next().'</a>':'';

	      dol_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);

	      print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">Fiche Ligne '.$mesg.'</td>';
	      print '<td class="nobordernopadding"><a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
	      print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table><br />';

	      /*
	       *
	       */
	      if ($_GET["action"] == 'delete' && $ligne->statut == -1)
		{		  
		  $html = new Form($db);

		  $html->form_confirm("fiche.php"."?id=".$_GET["id"],"Suppression de ligne","Etes-vous s�r de vouloir supprimer la ligne : ".dol_print_phone($ligne->numero,0,0,true)." ?","confirm_delete");
		  print '<br />';
		}

	      /*
	       *
	       */
	             
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

	      print '<tr><td width="20%">Num�ro</td><td>'.dol_print_phone($ligne->numero).'</td>';
	      //print " ".$ligne->support.

	      print '<td>Factur�e : '.$ligne->facturable.'</td></tr>';
	      	     
	      $client = new Societe($db, $ligne->client_id);
	      $client->fetch($ligne->client_id);

	      print '<tr><td width="20%">Client (Agence/Filiale)</td><td colspan="2">';
	      print $client->nom.'<br />';

	      print $client->cp . " " .$client->ville;
	      print '</td></tr>';

	      $client_facture = new Societe($db);
	      $client_facture->fetch($ligne->client_facture_id);

	      print '<tr><td width="20%">Client Factur�</td><td>';
	      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_facture->id.'">';
	      print $client_facture->nom.'</a><br />';
	      print $client_facture->cp . " " .$client_facture->ville;

	      print '</td><td valign="top" rowspan="8">';

	      /* Historique */

	      print '<table width="100%" class="border">';
	      print '<tr class="liste_titre">';
	      print '<td>Date</td>';
	      print '<td>Statut</td>';
	      if ($user->rights->telephonie->fournisseur->lire)
		print '<td>Fournisseur</td>';
	      print '<td>Rapporteur</td>';
	      print '</tr>';
	      $ff = array();     
	      $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur";
	      $sql .= " ORDER BY nom ";

	      $resql = $db->query($sql);
	      if ($resql)
		{
		  $num = $db->num_rows($resql);
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($resql);
			  $ff[$row[0]] = $row[1];
			  $i++;
			}
		    }
		  $db->free($resql);
		}

	      $sql = "SELECT ".$db->pdate("l.tms").", l.statut, l.fk_user";
	      $sql .= ", u.login, l.comment, l.fk_fournisseur";
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

			  print '<tr><td valign="top" width="20%">'.strftime("%d/%m/%y",$row[0]).'</td>';
			  print '<td><img src="./graph'.$row[1].'.png">&nbsp;';
			  print $ligne->statuts[$row[1]];
			  if (strlen($row[4]) > 0)
			    {
			      print '<br />'.$row[4];
			    }

			  print '</td>';

			  if ($user->rights->telephonie->fournisseur->lire)
			    print '<td>'.$ff[$row[5]].'</td>';

			  print '<td>'. $row[3] . "</td></tr>";
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


	      /* Fin historique */



	      print '</td></tr>';

	      print '<tr><td width="20%">Fournisseur</td><td>';
	      if ($user->rights->telephonie->fournisseur->lire)
		{
		  $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur";
		  $sql .= " WHERE rowid = ".$ligne->fournisseur_id;
		  
		  if ( $db->query( $sql) )
		    {
		      $num = $db->num_rows();
		      if ( $num > 0 )
			{			  
			  $row = $db->fetch_row();
			  print $row[1];
			}
		      $db->free();	      
		    }

		  print " - ".$ligne->techno;

		}
	      else
		{
		  print '-';
		}
	      print '</td></tr>';




	      print '<tr><td>PDF d�tail</td><td>'.$ligne->pdfdetail.'</td></tr>';



	      print '<tr><td width="20%">Remise LMN</td><td>'.$ligne->remise.'&nbsp;%&nbsp;';
	      print '(<a href="remises.php?id='.$ligne->id.'">historique</a>)</td></tr>';

	      $commercial_suiv = new User($db, $ligne->commercial_suiv_id);
	      $commercial_suiv->fetch();

	      print '<tr><td width="20%">Commercial</td>';
	      print '<td>'.$commercial_suiv->fullname.'</td></tr>';
	      print '<tr><td>Sign� par</td><td>';


	      if ($ligne->commercial_suiv_id <> $ligne->commercial_sign_id)
		{
		  $commercial_sign = new User($db, $ligne->commercial_sign_id);
		  $commercial_sign->fetch();
		  
		  print $commercial_sign->fullname.'</td></tr>';
		}
	      else
		{
		  print $commercial_suiv->fullname.'</td></tr>';
		}


	      print '<tr><td width="20%">Concurrent pr�c�dent</td>';
	      print '<td>'.$ligne->print_concurrent_nom().'</td></tr>';

	      print '<tr><td width="20%">Factures</td><td>';	  
	      print '<a href="'.DOL_URL_ROOT.'/telephonie/facture/liste.php?search_ligne='.$ligne->numero.'">liste</a>';
	      print '</td></tr>';



	      /* Contacts */
	     
	      $sql = "SELECT c.rowid, c.name, c.firstname, c.email ";
	      $sql .= "FROM ".MAIN_DB_PREFIX."socpeople as c";
	      $sql .= ",".MAIN_DB_PREFIX."telephonie_contact_facture as cf";
	      $sql .= " WHERE c.rowid = cf.fk_contact AND cf.fk_ligne = ".$ligne->id." ORDER BY name ";
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
			  print '<td valign="top" colspan="2">'.$row[1] . " " . $row[2] . " &lt;".$row[3]."&gt;</td></tr>";
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
	      if ($ligne->note)
		{
		  print '<tr><td width="20%" valign="top">Note</td><td colspan="2">'."\n";
		  print nl2br($ligne->note);
		  print '</td></tr>'."\n";
		}

	      print "</table><br />";

	      if ($_GET["action"] == "editremise" &&  $ligne->statut <> 6 && $user->rights->telephonie->ligne->creer)
		{
		  /**
		   * Edition de la remise
		   */

		  print '<form action="fiche.php?id='.$ligne->id.'" method="POST">';
		  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		  print '<input type="hidden" name="action" value="updateremise">';
		  print '<table class="border" width="100%" cellpadding="4" cellspacing="0">';
		  print '<tr class="liste_titre"><td colspan="2">Modification de la remise Local/Mobile/National</td></tr>';
		  print '<tr><td>Nouvelle remise LMN</td><td>';
		  print '<input size="4" type="text" name="remise" value="'.$ligne->remise.'" maxlength="3">&nbsp;%';
		  print '</td></tr>';

		  print '<tr><td width="20%">Commentaire</td><td><input size="40" type="text" name="comment"></td></tr>';

		  print '<tr><td align="center" colspan="2"><input type="submit" name="Activer"></td></tr>';

		  print '</table><br />';
		  
		  print '</form>';
		}

	      if ($_GET["action"] == "chgcontrat" && $user->rights->telephonie->ligne->creer)
		{
		  /**
		   * Edition de la remise
		   */

		  print '<form action="fiche.php?id='.$ligne->id.'" method="POST">';
		  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		  print '<input type="hidden" name="action" value="changecontrat">';
		  print '<table class="border" width="100%" cellpadding="4" cellspacing="0">';
		  print '<tr class="liste_titre"><td colspan="2">Migrer vers un autre contrat</td></tr>';
		  print '<tr><td width="20%">Nouveau contrat</td><td>';


		  $sql = "SELECT c.rowid, c.ref ";
		  $sql .= "FROM ".MAIN_DB_PREFIX."telephonie_contrat as c";
		  $sql .= " WHERE c.rowid <> ".$ligne->contrat;
		  $sql .= " AND c.fk_client_comm = ".$ligne->client_comm_id;
		  $sql .= " AND c.statut <> 6"; // contrat non r�sili�

		  $resql =  $db->query($sql);
		  if ($resql)
		    {
		      $num = $db->num_rows($resql);

		      if ($num > 0)
			{

			  print '<select name="contrat">';

			  $i = 0;
			  while ($i < $num)
			    {
			      $row = $db->fetch_row($resql);
			      
			      print '<option value="'.$row[0].'">'.$row[1];
			      $i++;
			    }
			  print '</select>';
			}		      
		      else
			{
			  print "Aucun contrat �ligible";
			}

		      $db->free();
		    }
		  else
		    {
		      print $sql;
		    }
		  
		  print '</td></tr>';
		  if ($num > 0)
		    {
		      print '<tr><td align="center" colspan="2"><input type="submit" value="Migrer"></td></tr>';
		    }

		  print '</table><br />';		  
		  print '</form>';
		}
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
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/fiche.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans("Ligne");
	      $hselected = $h;
	      $h++;
	      
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/infoc.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Infos');
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/history.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Historique');
	      $h++;

	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Conso');
	      $h++;
	      
	      dol_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);

	      print_fiche_titre('Edition de la ligne', $mesg);
	      
	      print "<form action=\"fiche.php?id=$ligne->id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	      $client_comm = new Societe($db, $ligne->client_comm_id);
	      $client_comm->fetch($ligne->client_comm_id);

	      print '<tr><td width="20%">Client</td><td>';
	      print $client_comm->nom;
	      print '</td></tr>';
	      print '<input type="hidden" name="client_comm" value="'.$client_comm->id.'">'."\n";
	      
	      print '<tr><td width="20%">Num�ro</td><td>';
	      if ($ligne->statut == -1)
		{
		  print '<input name="numero" size="12" value="'.$ligne->numero.'">';
		}
	      else
		{
		  print '<input type="hidden" name="numero" value="'.$ligne->numero.'">';
		  print dol_print_phone($ligne->numero);
		}
	      print '</td></tr>';

	      $client = new Societe($db, $ligne->client_id);
	      $client->fetch($ligne->client_id);
	  
	      print '<tr><td width="20%">Client (Agence/Filiale)</td><td>';
	      print $client->nom.'<br />';
	      
	      print $client->cp . " " .$client->ville;
	      print '</td></tr>';
	      print '<input type="hidden" name="client" value="'.$ligne->client_id.'">';

	      $client_facture = new Societe($db);
	      $client_facture->fetch($ligne->client_facture_id);
	      
	      print '<tr><td width="20%">Client Factur�</td><td>';
	      print $client_facture->nom.'<br />';
	      print $client_facture->cp . " " .$client_facture->ville;
	      
	      print '</td></tr>';
	      print '<input type="hidden" name="client_facture" value="'.$ligne->client_facture_id.'">';

	      print '<tr><td width="20%">Fournisseur</td><td>';

	      if ($ligne->statut == -1)
		{
		  print '<select name="fournisseur">';
	      
		  $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur WHERE commande_active = 1 ORDER BY nom ";
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
			      if ($row[0] == $ligne->fournisseur_id)
				{
				  print " SELECTED";
				}
			      print '>'.$row[1];
			      $i++;
			    }
			}
		      $db->free();	      
		    }
		  
		  print '</select>';
		}
	      else
		{
		  print '<input type="hidden" name="fournisseur" value="'.$ligne->fournisseur_id.'">';

		  $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur";
		  $sql .= " WHERE commande_active = 1 AND rowid = ".$ligne->fournisseur_id;

		  if ( $db->query( $sql) )
		    {
		      $num = $db->num_rows();
		      if ( $num > 0 )
			{			  
			  $row = $db->fetch_row();
			  print $row[1];
			}
		      $db->free();	      
		    }
		}

	      print '</td></tr>';
	  
	      /*
	       * Commercial
	       */
	  
	      print '<tr><td width="20%">Fournisseur pr�c�dent</td><td>';
	      print '<select name="concurrent">';
	  
	      $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_concurrents ORDER BY nom ";
	      if ( $db->query( $sql) )
		{
		  $num = $db->num_rows();
		  if ( $num > 0 )
		    {
		      $i = 0;
		      while ($i < $num)
			{
			  $row = $db->fetch_row($i);
			  print '<option value="'.$row[0].'"';
			  if ($row[0] == $ligne->concurrent_id)
			    {
			      print " SELECTED";
			    }
			  print '>'.$row[1];
			  $i++;
			}
		    }
		  $db->free();
      
		}
	      print '</select></td></tr>'."\n";
	      /*
	       *
	       *
	       */
	      print '<tr><td width="20%">Remise LMN</td><td>'.$ligne->remise.'&nbsp;%</td></tr>';

	      print '<tr><td width="20%" valign="top">Note</td><td>';
	      print '<textarea name="note" rows="4" cols="50">';
	      print nl2br($ligne->note);
	      print "</textarea></td></tr>";
	  
	      print '<tr><td align="center" colspan="2"><input type="submit" value="Mettre � jour">';
	      print '<a href="fiche.php?id='.$ligne->id.'">Annuler</a></td></tr>';
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

	      print "<form action=\"fiche.php?id=$ligne->id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="addcontact">';

	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';


	      $sql = "SELECT c.rowid, c.name, c.firstname, c.email ";
	      $sql .= "FROM ".MAIN_DB_PREFIX."socpeople as c";
	      $sql .= ",".MAIN_DB_PREFIX."telephonie_contact_facture as cf";
	      $sql .= " WHERE c.rowid = cf.fk_contact AND cf.fk_ligne = ".$ligne->id." ORDER BY name ";
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
			  print '<a href="fiche.php?id='.$ligne->id.'&amp;action=delcontact&amp;contact_id='.$row[0].'">';
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
	  	 
	      $sql = "SELECT rowid, name, firstname, email FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".$ligne->client_facture_id." ORDER BY name ";
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
	  
	      print '<p>Contact auquel est envoy� la facture par email</p></td></tr>';
	  	  
	      print '<tr><td>&nbsp;</td><td>';
	      if ($num > 0)
		{
		  print '<input type="submit" value="Ajouter">';
		}
	      print '<a href="fiche.php?id='.$ligne->id.'">Annuler</a></td></tr>';
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

if ( $user->rights->telephonie->ligne_commander && $ligne->statut == 3 )
{
  $ff = array();
  $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur WHERE commande_active = 1 ORDER BY nom ";
  if ( $db->query( $sql) )
    {
      $num = $db->num_rows();
      if ( $num > 0 )
	{
	  $i = 0;
	  while ($i < $num)
	    {
	      $row = $db->fetch_row();
	      if ($row[0] <> $ligne->fournisseur_id)
		{
		  $ff[$row[0]] = $row[1];
		}
	      $i++;
	    }
	}
      $db->free();      
    }

  if (sizeof($ff) > 0 && $ligne->techno == 'presel')
    {
      /**
       * Transferer chez un autre fournisseur
       */
      $form = new Form($db);      
      print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';      
      print '<form action="fiche.php?id='.$ligne->id.'&amp;action=transfer" method="post">';
      print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
      print '<table class="noborder" cellpadding="2" cellspacing="0">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">Commander la ligne chez un autre fournisseur</td></tr>';
      print '<tr><td width="20%">Fournisseur</td><td >';
      $form->select_array("fournisseur",$ff,$ligne->fournisseur);
      print '</td></tr>';
      
      print '<tr><td colspan="2"align="center"><input type="submit" name="Activer"></td></tr>';
      print '</table>';
      print '</form></td><td>';
      print '&nbsp;</td></tr></table>';
    }
}



if ( $user->rights->telephonie->ligne_activer && $ligne->statut == 2 && $ligne->techno == 'presel')
{
  $form = new Form($db);

  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td valign="top">';

  print '<form name="active" action="fiche.php?id='.$ligne->id.'&amp;action=active" method="post">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print '<table class="noborder" cellpadding="2" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Activer</td><td>';
  print '<tr><td>Date</td><td>';
  print $form->select_date('','','','','',"active");
  print '</td>';
  print '<td colspan="2"><input type="submit" name="Activer"></td></tr>';
  print '</table>';

  print '</form></td><td>';


  print '<form name="refuse" action="fiche.php?id='.$ligne->id.'&amp;action=refuse" method="post">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print '<table class="noborder" cellpadding="2" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Refuser</td><td>';
  print '<tr><td>Date</td><td>';
  print $form->select_date('','','','','',"refuse");
  print '</td>';
  print '<td colspan="2"><input type="submit" name="Activer"></td></tr>';
  print '<tr><td colspan="3">Commentaire <input size="30" type="text" name="commentaire"></td></tr>';
  print '</table>';

  print '</form></td></tr></table>';
}

if ( $user->rights->telephonie->ligne_activer && ( $ligne->statut == 5 || $ligne->statut == 3) && $ligne->techno == 'presel')
{
  /**
   * Resiliation demandee
   */
  $form = new Form($db);

  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';

  print '<form name="confirmresilier" action="fiche.php?id='.$ligne->id.'&amp;action=confirmresilier" method="post">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print '<table class="noborder" cellpadding="2" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Confirmation de la r�siliation</td><td>';
  print '<tr><td>Date</td><td>';
  print $form->select_date('','','','','',"confirmresilier");
  print '</td>';
  print '<td colspan="2"><input type="submit" name="Activer"></td></tr>';
  print '<tr><td colspan="3">Commentaire <input size="30" type="text" name="commentaire"></td></tr>';
  print '</table>';

  print '</form></td><td>';

  print '&nbsp;</td></tr></table>';
}


if ( $user->rights->telephonie->ligne->creer && $ligne->statut == 6 && $ligne->techno == 'presel')
{
  /**
   * A commander
   */
  $form = new Form($db);

  print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';

  print '<form action="fiche.php?id='.$ligne->id.'&amp;action=acommander" method="post">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print '<table class="noborder" cellpadding="2" cellspacing="0">';
  print '<tr class="liste_titre"><td colspan="2">Recommander la ligne</td><td>';
  print '<tr><td>Date</td><td>';
  print strftime("%e %B %Y", time());
  print '</td>';
  print '<td colspan="2"><input type="submit" value="Commander"></td></tr>';
  print '<tr><td colspan="3">Commentaire <input size="30" type="text" name="commentaire"></td></tr>';
  print '</table>';

  print '</form></td><td>';

  print '&nbsp;</td></tr></table>';
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "\n<br>\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '' && $result == 1 && $client_comm->perm_read)
{

  if ( $user->rights->telephonie->ligne->resilier && $ligne->statut == 3 && $ligne->techno == 'presel')
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=resilier&amp;id=$ligne->id\">".$langs->trans("Demander la r�siliation")."</a>";
    }

  if ( $user->rights->telephonie->ligne->resilier && $ligne->statut == 4 && $ligne->techno == 'presel')
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=annuleresilier&amp;id=$ligne->id\">".$langs->trans("Annuler la demande de r�siliation")."</a>";
    }
  
  if ( $user->rights->telephonie->ligne_activer && $ligne->statut <> 6)
    {
  print "<a class=\"butAction\" href=\"fiche.php?action=contact&amp;id=$ligne->id\">".$langs->trans("Contact")."</a>";
    }

  if ( $user->rights->telephonie->ligne->creer && $ligne->statut < 4)
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=chgcontrat&amp;id=$ligne->id\">".$langs->trans("Changer de contrat")."</a>";
    }

  if ( $user->rights->telephonie->ligne->creer && $ligne->statut < 4)
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=editremise&amp;id=$ligne->id\">".$langs->trans("Changer la remise")."</a>";
    }

  if ( $user->rights->telephonie->ligne_activer && $ligne->statut == -1)
    {
      print "<a class=\"butAction\" href=\"fiche.php?action=edit&amp;id=$ligne->id\">".$langs->trans("Modify")."</a>";
    }
     
  if ( $user->rights->telephonie->ligne->creer && $ligne->statut == -1)
    {
      print "<a class=\"butActionDelete\" href=\"fiche.php?action=delete&amp;id=$ligne->id\">".$langs->trans("Delete")."</a>";
    }
 
}

print "</div>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
