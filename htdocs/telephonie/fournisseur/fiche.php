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
require_once(DOL_DOCUMENT_ROOT.'/telephonie/telephonie.tarif.grille.class.php');

if (!$user->rights->telephonie->fournisseur->lire)
  accessforbidden();

$mesg = '';

if ($_POST["action"] == 'add')
{
  $fourn = new FournisseurTelephonie($db);

  $fourn->nom              = $_POST["nom"];
  $fourn->email_commande   = $_POST["email_commande"];
  $fourn->methode_commande = $_POST["methode"];
  $fourn->cdrformat        = $_POST["cdrformat"];
  $fourn->grille           = $_POST["grille"];

  if ( $fourn->create($user) == 0)
    {
      Header("Location: index.php");
    }
}

if ($_POST["action"] == 'update' && $_GET["id"])
{
  $fourn = new FournisseurTelephonie($db);

  if ($fourn->fetch($_GET["id"]) == 0)
    {

      $fourn->num_client       = $_POST["num_client"];
      $fourn->email_commande   = $_POST["email_commande"];
      $fourn->methode_commande = $_POST["methode"];
      $fourn->cdrformat        = $_POST["cdrformat"];
      $fourn->commande_bloque  = $_POST["commande_bloque"];
      $fourn->grille           = $_POST["grille"];      

      if ( $fourn->update($user) == 0)
	{
	  Header("Location: fiche.php?id=".$fourn->id);
	}
    }
}

if ($_GET["action"] == 'active')
{
  $fourn = new FournisseurTelephonie($db);
  $fourn->id = $_GET["id"];

  if ( $fourn->active($user) == 0)
    {
      Header("Location: index.php");
    }
}

if ($_GET["action"] == 'desactive')
{
  $fourn = new FournisseurTelephonie($db);
  $fourn->id = $_GET["id"];

  if ( $fourn->desactive($user) == 0)
    {
      Header("Location: index.php");
    }
}

llxHeader("","Telephonie - Fournisseur");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}

$ta = new TelephonieTarifGrille($db);
$ta->GetListe($user,'achat');

/*
 * Création
 *
 */

if ($_GET["action"] == 'create')
{
  $fourn = new FournisseurTelephonie($db);
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print_titre("Nouveau  fournisseur");
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="20%">Nom</td><td><input name="nom" size="30" value=""></td></tr>';
  print '<tr><td width="20%">Email de commande</td><td><input name="email_commande" size="40" value=""></td><td>adresse email à laquelle sont envoyées les commandes de lignes</td></tr>';

  $html = new Form($db);
  
  $arr = $fourn->array_methode();
  $cdr = $fourn->array_cdrformat();  

  print '<tr><td width="20%">Méthode de commande</td>';
  print '<td>';
  print $html->select_array("methode",$arr,$fourn->class_commande);
  print '</td>';
  print '<td>méthode utilisée pour les commandes de lignes</td></tr>';

  print '<tr><td width="20%">Format de CDR</td>';
  print '<td>';
  print $html->select_array("cdrformat",$cdr,$fourn->cdrformat);
  print '</td>';
  print '<td>Format des fichiers CDR</td></tr>';

  $ta->liste_name[0] = ' Creer une nouvelle grille';
  asort($ta->liste_name);
  print '<tr><td width="20%">Grille de tarif</td>';
  print '<td>';
  print $html->select_array("grille",$ta->liste_name);
  print '</td>';
  print '<td>Grille de tarif</td></tr>';

  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
}

/*
 * Visualisation & Edition
 *
 */
if ($_GET["id"] > 0)
{
  $art[0] = "non";
  $art[1] = "oui";

  $fourn = new FournisseurTelephonie($db);
  if ($fourn->fetch($_GET["id"]) == 0)
  {

    if ($_GET["action"] == "edit" && $user->rights->telephonie->fournisseur->config)
      {
	/*
	 * Edition
	 *
	 */
	print_titre("Modification fournisseur");

	print '<form action="fiche.php?id='.$fourn->id.'" method="post">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	
	print '<tr><td width="20%">Nom</td><td colspan="2">'.$fourn->nom.'</td></tr>';

	print '<tr><td width="20%">Numéro Client</td>';
	print '<td><input name="num_client" size="30" value="'.$fourn->num_client.'"></td>';
	print '<td>Notre numéro de client</td></tr>';

	print '<tr><td width="20%">Email de commande</td>';
	print '<td><input name="email_commande" size="30" value="'.$fourn->email_commande.'"></td>';
	print '<td>adresse email à laquelle sont envoyées les commandes de lignes</td></tr>';

	$html = new Form($db);

	$arr = $fourn->array_methode();
	$cdr = $fourn->array_cdrformat();

	print '<tr><td width="20%">Méthode de commande</td>';
	print '<td>';
	print $html->select_array("methode",$arr,$fourn->class_commande);
	print '</td>';
	print '<td>méthode utilisée pour les commandes de lignes</td></tr>';
    
	print '<tr><td width="20%">Format de CDR</td>';
	print '<td>';
	print $html->select_array("cdrformat",$cdr,$fourn->cdrformat);
	print '</td>';
	print '<td>Format des fichiers CDR</td></tr>';

	print '<tr><td width="20%">Blocage des commandes</td>';
	print '<td>';

	print $html->select_array("commande_bloque",$art,$fourn->commande_bloque);
	print '</td>';
	print '<td>Les commandes vers ce fournisseur sont bloquées</td></tr>';


	
	print '<tr><td width="20%">Grille de tarif</td>';
	print '<td>';
	print $html->select_array("grille",$ta->liste_name, $fourn->grille);
	print '</td>';
	print '<td>Grille de tarif</td></tr>';

	print '<tr><td colspan="3" align="center"><input type="submit" value="Update"></td></tr>';
	print '</table></form><br />';
      }
    else
      {
	/*
	 * Visualisation
	 *
	 */
	$h = 0;
	$head[$h][0] = DOL_URL_ROOT."/telephonie/fournisseur/fiche.php?id=".$fourn->id;
	$head[$h][1] = $langs->trans("Fiche");
	$hselected = $h;
	$h++;
	/*	
	$head[$h][0] = DOL_URL_ROOT."/telephonie/fournisseur/cdr.php?id=".$fourn->id;
	$head[$h][1] = $langs->trans("CDR");
	$h++;
	*/
	dolibarr_fiche_head($head, $hselected, 'Fournisseur : '.$fourn->nom);
	
	print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	
	print '<tr><td width="20%">Nom</td><td colspan="2">'.$fourn->nom.'</td></tr>';

	print '<tr><td width="20%">Numéro Client</td>';
	print '<td>'.$fourn->num_client.'</td>';
	print '<td>Notre numéro de client</td></tr>';

	print '<tr><td width="20%">Email de commande</td>';
	print '<td>'.$fourn->email_commande.'</td>';
	print '<td>adresse email à laquelle sont envoyées les commandes de lignes</td></tr>';
	
	print '<tr><td width="20%">Méthode de commande</td>';
	print '<td>'.$fourn->class_commande.'</td>';
	print '<td>méthode utilisée pour les commandes de lignes</td></tr>';
	       
	print '<tr><td width="20%">Format de CDR</td>';
	print '<td>'.$fourn->cdrformat.'</td>';
	print '<td>Format des fichiers CDR</td></tr>';

	print '<tr><td width="20%">Blocage des commandes</td>';
	print '<td>'.$art[$fourn->commande_bloque].'</td>';
	print '<td>Les commandes sont bloquées</td></tr>';

	print '<tr><td width="20%">Grille de tarif</td>';
	print '<td>'.$ta->liste_name[$fourn->grille].'</td>';
	print '<td>Grille de tarif</td></tr>';
	print '</table><br />';
	print 'Format de CDR<br/>';

	if (strlen($fourn->cdrformat))
	  {
	    if (@require_once(DOL_DOCUMENT_ROOT."/telephonie/fournisseur/cdrformat/cdrformat.".$fourn->cdrformat.".class.php"))
	      {
		$format = "CdrFormat".ucfirst($fourn->cdrformat);
		$cdrformat = new $format();
		print '<pre>'.$cdrformat->ShowSample().'</pre>';
	      }
	  }
	print '</div>';

      }
  }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{  
  if($user->rights->telephonie->fournisseur->config)

    {
      print '<a class="butAction" href="'.DOL_URL_ROOT.'/telephonie/fournisseur/fiche.php?action=edit&amp;id='.$fourn->id.'">'.$langs->trans("Modify").'</a>';
    }
}

print "</div>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
