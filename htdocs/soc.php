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

$langs->load("soc");
 


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $_POST["action"] = '';
  $_GET["action"] = '';
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

  dolibarr_fiche_head($head, 0, $soc->nom);

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

  print '<a class="tabAction" href="'.DOL_URL_ROOT.'/soc.php?socid='.$soc->id.'&action=edit">Editer</a>';

  print '<a class="tabAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$soc->id.'&amp;action=create">Ajouter un contact</a>';
  
  print '</div>';
/*
 *
 */
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
