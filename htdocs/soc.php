<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

require("pre.inc.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($_POST["action"] == 'add' or $_POST["action"] == 'update')
{
  $soc = new Societe($db);
  $soc->nom                  = $_POST["nom"];
  $soc->adresse              = $_POST["adresse"];
  $soc->cp                   = $_POST["cp"];
  $soc->ville                = $_POST["ville"];
  $soc->pays_id              = $_POST["pays_id"];
  $soc->departement_id       = $_POST["departement_id"];
  $soc->tel                  = $_POST["tel"];
  $soc->fax                  = $_POST["fax"];
  $soc->url                  = ereg_replace( "http://", "", $_POST["url"] );
  $soc->siren                = $_POST["siren"];
  $soc->siret                = $_POST["siret"];
  $soc->ape                  = $_POST["ape"];
  $soc->capital              = $_POST["capital"];
  $soc->tva_intra            = $_POST["tva_intra_code"] . $_POST["tva_intra_num"];
  $soc->forme_juridique_code = $_POST["forme_juridique_code"];
  $soc->effectif_id          = $_POST["effectif_id"];
  $soc->client               = $_POST["client"];
  $soc->fournisseur          = $_POST["fournisseur"];

  if ($_POST["action"] == 'update')
    {
      $soc->update($socid);
    }
  if ($_POST["action"] == 'add')
    {
      $socid = $soc->create();
    }
}

/*
 *
 *
 */
llxHeader();
$form = new Form($db);

if ($_GET["action"] == 'create') 
{
  /*
   * Fiche societe en mode création
   */
  $soc = new Societe($db);
  if ($_GET["type"]=='f') { $soc->fournisseur=1; }
  if ($_GET["type"]=='c') { $soc->client=1; }
  if ($_GET["type"]=='p') { $soc->client=2; }
  
  print '<div class="titre">Nouvelle société (prospect, client, fournisseur)</div><br>';
  print '<form action="soc.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="fournisseur" value="0">';

  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr><td>Nom</td><td colspan="3"><input type="text" name="nom"></td></tr>';
  print '<tr><td>'.$langs->trans('Adresse').'</td><td colspan="3"><textarea name="adresse" cols="30" rows="3" wrap="soft"></textarea></td></tr>';
  print '<tr><td>CP</td><td><input size="6" type="text" name="cp">&nbsp;';
  print 'Ville&nbsp;<input type="text" name="ville"></td>';

  print '<td>Département/Province/Canton</td><td>';
  print $form->select_departement($soc->departement_id);
  print '</td></tr>';

  print '<tr><td>Pays</td><td colspan="3">';
  print $form->select_pays($soc->pays_id);
  print '</td></tr>';

  print '<tr><td>'.$langs->trans('Téléphone').'</td><td><input type="text" name="tel"></td>';
  print '<td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax"></td></tr>';

  print '<tr><td>Web</td><td colspan="3">http://<input size="40" type="text" name="url"></td></tr>';

  print '<tr><td>Siren</td><td><input type="text" name="siren"></td>';

  print '<td>Siret</td><td><input type="text" name="siret" size="15" maxlength="14" value="'.$soc->siret.'"></td></tr>';

  print '<tr><td>Ape</td><td><input type="text" name="ape" size="5" maxlength="4" value="'.$soc->ape.'"></td>';
  print '<td>Capital</td><td><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.MAIN_MONNAIE.'</td></tr>';
  
  print '<tr><td>Forme juridique</td><td colspan="3">';
  print $form->select_forme_juridique($soc->forme_juridique_code);
  print '</td></tr>';
  
  print '<tr><td>Effectif</td><td colspan="3">';
  print $form->select_array("effectif_id",$soc->effectif_array(), $soc->effectif_id);
  print '</td></tr>';

  print '<tr><td colspan="2">Numéro de TVA Intracommunautaire</td><td colspan="2">';
  
  print '<input type="text" name="tva_intra_code" size="3" maxlength="2" value="'.$soc->tva_intra_code.'">';
  print '<input type="text" name="tva_intra_num" size="18" maxlength="18" value="'.$soc->tva_intra_num.'">';
  print '<br>Vous pouvez vérifier ce numéro sur le <a href="http://europa.eu.int/comm/taxation_customs/vies/fr/vieshome.htm" target="_blank">site</a> de la commission européenne';
  print '</td></tr>';
  
  print '<tr><td>Prospect / Client</td><td><select name="client">';
  print '<option value="2"'.($soc->client==2?' selected':'').'>Prospect</option>';
  print '<option value="1"'.($soc->client==1?' selected':'').'>Client</option>';
  print '<option value="0"'.($soc->client==0?' selected':'').'>Ni client, ni prospect</option>';
  print '</select></td>';

  print '<td>Fournisseur</td><td><select name="fournisseur">';
  print_oui_non($soc->fournisseur);
  print '</select></td></tr>';

  print '<tr><td colspan="4" align="center"><input type="submit" value="Ajouter"></td></tr>';
  print '</table>';
  print '</form>';
}
elseif ($_GET["action"] == 'edit')
{
  /*
   * Fiche societe en mode edition
   */
   
  print_titre("Edition de la société");

  if ($_GET["socid"])
    {
      $soc = new Societe($db);
      $soc->id = $_GET["socid"];
      $soc->fetch($_GET["socid"]);

      print '<form action="soc.php?socid='.$soc->id.'" method="post">';
      print '<input type="hidden" name="action" value="update">';

      print '<table class="border" width="100%" cellpadding="3" cellspacing="0">';
      print '<tr><td>Nom</td><td colspan="3"><input type="text" size="40" name="nom" value="'.$soc->nom.'"></td></tr>';
      print '<tr><td valign="top">'.$langs->trans('Adresse').'</td><td colspan="3"><textarea name="adresse" cols="30" rows="3" wrap="soft">';
      print $soc->adresse;
      print '</textarea></td></tr>';
      
      print '<tr><td>CP</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'">&nbsp;';
      print 'Ville&nbsp;<input type="text" name="ville" value="'.$soc->ville.'"></td>';
      print '<td>Département/Province/Canton</td><td>';
      print $form->select_departement($soc->departement_id);
      print '</td></tr>';      

      print '<tr><td>Pays</td><td colspan="3">';
      print $form->select_pays($soc->pays_id);
      print '</td></tr>';

      print '<tr><td>'.$langs->trans('Téléphone').'</td><td><input type="text" name="tel" value="'.$soc->tel.'"></td>';
      print '<td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$soc->fax.'"></td></tr>';
      print '<tr><td>Web</td><td colspan="3">http://<input type="text" name="url" size="40" value="'.$soc->url.'"></td></tr>';
      
      print '<tr><td>Siren</td><td><input type="text" name="siren" size="10" maxlength="9" value="'.$soc->siren.'"></td>';
      print '<td>Siret</td><td><input type="text" name="siret" size="15" maxlength="14" value="'.$soc->siret.'"></td></tr>';

      print '<tr><td>Ape</td><td><input type="text" name="ape" size="5" maxlength="4" value="'.$soc->ape.'"></td>';
      print '<td>Capital</td><td><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.MAIN_MONNAIE.'</td></tr>';


      print '<tr><td>Forme juridique</td><td colspan="3">';
      print $form->select_forme_juridique($soc->forme_juridique_code);
      print '</td></tr>';

      print '<tr><td>Effectif</td><td colspan="3">';
      print $form->select_array("effectif_id",$soc->effectif_array(), $soc->effectif_id);
      print '</td></tr>';

      print '<tr><td colspan="2">Numéro de TVA Intracommunautaire</td><td colspan="2">';

      print '<input type="text" name="tva_intra_code" size="3" maxlength="2" value="'.$soc->tva_intra_code.'">';
      print '<input type="text" name="tva_intra_num" size="18" maxlength="18" value="'.$soc->tva_intra_num.'">';

      print '</td></tr>';

      print '<tr><td>Prospect / Client</td><td><select name="client">';
	  print '<option value="2"'.($soc->client==2?' selected':'').'>Prospect</option>';
	  print '<option value="1"'.($soc->client==1?' selected':'').'>Client</option>';
	  print '<option value="0"'.($soc->client==0?' selected':'').'>Ni client, ni prospect</option>';
      print '</select></td>';

      print '<td>Fournisseur</td><td><select name="fournisseur">';
      print_oui_non($soc->fournisseur);
      print '</select>';
      
      print '</td></tr>';
      
      print '<tr><td align="center" colspan="4"><input type="submit" value="Mettre à jour"></td></tr>';
      print '</table>';
      print '</form>';

      print 'Astuce : Vous pouvez vérifier le numéro de TVA intra communautaire sur le <a href="http://europa.eu.int/comm/taxation_customs/vies/fr/vieshome.htm" target="_blank">site</a> de la commission européenne';

    }
}
else
{
  $soc = new Societe($db);
  $soc->id = $_GET["socid"];
  $soc->fetch($_GET["socid"]);

  $head[0][0] = 'soc.php?socid='.$soc->id;
  $head[0][1] = "Fiche société";
  $h = 1;

  if ($soc->client==1)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id;
      $head[$h][1] = 'Fiche client';
      $h++;
    }
  if ($soc->client==2)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$soc->id;
      $head[$h][1] = 'Fiche prospect';
      $h++;
    }
  if ($soc->fournisseur)
    {
      $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id;
      $head[$h][1] = 'Fiche fournisseur';
      $h++;
    }

  if ($conf->compta->enabled) {
      $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id;
      $head[$h][1] = 'Fiche compta';
      $h++;
  }

  $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$soc->id;
  $head[$h][1] = 'Note';
  $h++;
  
  if ($user->societe_id == 0)
    {
      $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$soc->id;
      $head[$h][1] = 'Documents';
      $h++;
    }

  $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$soc->id;
  $head[$h][1] = 'Notifications';

  dolibarr_fiche_head($head, 0);

  /*
   * Fiche société en mode visu
   */
  
  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr><td width="20%">Nom</td><td width="80%" colspan="3">'.$soc->nom.'</td></tr>';

  print "<tr><td valign=\"top\">".$langs->trans('Adresse')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";

  print '<tr><td>'.$langs->trans('Téléphone').'</td><td>'.dolibarr_print_phone($soc->tel).'</td>';
  print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax).'</td></tr>';
  print '<tr><td>Web</td><td colspan="3">';
  if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
  print '</td></tr>';
  
  print '<tr><td>Siren</td><td><a target="_blank" href="http://www.societe.com/cgi-bin/recherche?rncs='.$soc->siren.'">'.$soc->siren.'</a>&nbsp;</td>';

  print '<td>Siret</td><td>'.$soc->siret.'</td></tr>';

  print '<tr><td>Ape</td><td>'.$soc->ape.'</td>';
  print '<td>Capital</td><td>'.$soc->capital.' '.MAIN_MONNAIE.'</td></tr>';

  print '<tr><td>Forme juridique</td><td colspan="3">'.$soc->forme_juridique.'</td></tr>';
  print '<tr><td>Effectif</td><td colspan="3">'.$soc->effectif.'</td></tr>';

  print '<tr><td colspan="2">Numéro de TVA Intracommunautaire</td><td colspan="2">';
  print $soc->tva_intra;
  print '</td></tr>';

  print '</table>';
  print "<br></div>\n";
  /*
   *
   */  
  print '<div class="tabsAction">';

  print '<a class="tabAction" href="'.DOL_URL_ROOT.'/soc.php?socid='.$socid.'&action=edit">Editer</a>';

  print '<a class="tabAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$socid.'&amp;action=create">Ajouter un contact</a>';
  
  print '</div>';
/*
 *
 */
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
