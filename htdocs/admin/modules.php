<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */
require("./pre.inc.php");

llxHeader();

$dir = "../includes/modules/facture/";

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

//
// TODO mettre cette section dans la base de données
//

$modules["MAIN_MODULE_SOCIETE"][0] = "Module societe";
$modules["MAIN_MODULE_SOCIETE"][1] = "MAIN_MODULE_SOCIETE";
$modules["MAIN_MODULE_SOCIETE"][2] = MAIN_MODULE_SOCIETE;
$modules["MAIN_MODULE_SOCIETE"][3] = "Module société";

$modules["MAIN_MODULE_COMMERCIAL"][0] = "Module commercial";
$modules["MAIN_MODULE_COMMERCIAL"][1] = "MAIN_MODULE_COMMERCIAL";
$modules["MAIN_MODULE_COMMERCIAL"][2] = MAIN_MODULE_COMMERCIAL;
$modules["MAIN_MODULE_COMMERCIAL"][3] = "Module commercial";

$modules["MAIN_MODULE_COMPTABILITE"][0] = "Module comptabilité";
$modules["MAIN_MODULE_COMPTABILITE"][1] = "MAIN_MODULE_COMPTABILITE";
$modules["MAIN_MODULE_COMPTABILITE"][2] = MAIN_MODULE_COMPTABILITE;
$modules["MAIN_MODULE_COMPTABILITE"][3] = "Module comptabilité";

$modules["MAIN_MODULE_COMMANDE"][0] = "Module commande";
$modules["MAIN_MODULE_COMMANDE"][1] = "MAIN_MODULE_COMMANDE";
$modules["MAIN_MODULE_COMMANDE"][2] = MAIN_MODULE_COMMANDE;
$modules["MAIN_MODULE_COMMANDE"][3] = "Module de gestion des commandes";

$modules["MAIN_MODULE_FACTURE"][0] = "Module facture";
$modules["MAIN_MODULE_FACTURE"][1] = "MAIN_MODULE_FACTURE";
$modules["MAIN_MODULE_FACTURE"][2] = MAIN_MODULE_FACTURE;
$modules["MAIN_MODULE_FACTURE"][3] = "Module de gestion des factures";
$modules["MAIN_MODULE_FACTURE"][4] = "modFacture";

$modules["MAIN_MODULE_PROPALE"][0] = "Module propale";
$modules["MAIN_MODULE_PROPALE"][1] = "MAIN_MODULE_PROPALE";
$modules["MAIN_MODULE_PROPALE"][2] = MAIN_MODULE_PROPALE;
$modules["MAIN_MODULE_PROPALE"][3] = "Module de gestion des propositions commerciales";
$modules["MAIN_MODULE_PROPALE"][4] = "modPropale";

$modules["MAIN_MODULE_PRODUIT"][0] = "Module produit";
$modules["MAIN_MODULE_PRODUIT"][1] = "MAIN_MODULE_PRODUIT";
$modules["MAIN_MODULE_PRODUIT"][2] = MAIN_MODULE_PRODUIT;
$modules["MAIN_MODULE_PRODUIT"][3] = "Module de gestion des produits";
$modules["MAIN_MODULE_PRODUIT"][4] = "modProduit";

$modules["MAIN_MODULE_PROJET"][0] = "Module projet";
$modules["MAIN_MODULE_PROJET"][1] = "MAIN_MODULE_PROJET";
$modules["MAIN_MODULE_PROJET"][2] = MAIN_MODULE_PROJET;
$modules["MAIN_MODULE_PROJET"][3] = "Module de gestion des projets";
$modules["MAIN_MODULE_PROJET"][4] = "modProjet";

$modules["MAIN_MODULE_FOURNISSEUR"][0] = "Module fournisseur";
$modules["MAIN_MODULE_FOURNISSEUR"][1] = "MAIN_MODULE_FOURNISSEUR";
$modules["MAIN_MODULE_FOURNISSEUR"][2] = MAIN_MODULE_FOURNISSEUR;
$modules["MAIN_MODULE_FOURNISSEUR"][3] = "Module de gestion des fournisseurs";

$modules["MAIN_MODULE_FICHEINTER"][0] = "Module fiche intervention";
$modules["MAIN_MODULE_FICHEINTER"][1] = "MAIN_MODULE_FICHEINTER";
$modules["MAIN_MODULE_FICHEINTER"][2] = MAIN_MODULE_FICHEINTER;
$modules["MAIN_MODULE_FICHEINTER"][3] = "Module de gestion des fiches d'interventions";
$modules["MAIN_MODULE_FICHEINTER"][4] = "modFicheinter";

$modules["MAIN_MODULE_DON"][0] = "Module don";
$modules["MAIN_MODULE_DON"][1] = "MAIN_MODULE_DON";
$modules["MAIN_MODULE_DON"][2] = MAIN_MODULE_DON;
$modules["MAIN_MODULE_DON"][3] = "Module de gestion des dons";

$modules["MAIN_MODULE_ADHERENT"][0] = "Module adherent";
$modules["MAIN_MODULE_ADHERENT"][1] = "MAIN_MODULE_ADHERENT";
$modules["MAIN_MODULE_ADHERENT"][2] = MAIN_MODULE_ADHERENT;
$modules["MAIN_MODULE_ADHERENT"][3] = "Module de gestion des adhérents d'une association";

$modules["MAIN_MODULE_BOUTIQUE"][0] = "Module boutique";
$modules["MAIN_MODULE_BOUTIQUE"][1] = "MAIN_MODULE_BOUTIQUE";
$modules["MAIN_MODULE_BOUTIQUE"][2] = MAIN_MODULE_BOUTIQUE;
$modules["MAIN_MODULE_BOUTIQUE"][3] = "Module de gestion des boutiques";

$modules["MAIN_MODULE_POSTNUKE"][0] = "Module Postnuke";
$modules["MAIN_MODULE_POSTNUKE"][1] = "MAIN_MODULE_POSTNUKE";
$modules["MAIN_MODULE_POSTNUKE"][2] = MAIN_MODULE_POSTNUKE;
$modules["MAIN_MODULE_POSTNUKE"][3] = "Module de gestion de postnuke";

$modules["MAIN_MODULE_WEBCALENDAR"][0] = "Module Webcalendar";
$modules["MAIN_MODULE_WEBCALENDAR"][1] = "MAIN_MODULE_WEBCALENDAR";
$modules["MAIN_MODULE_WEBCALENDAR"][2] = MAIN_MODULE_WEBCALENDAR;
$modules["MAIN_MODULE_WEBCALENDAR"][3] = "Module de gestion du calendrier";

$modules["MAIN_MODULE_EXTERNAL_RSS"][0] = "Module de syndication externe";
$modules["MAIN_MODULE_EXTERNAL_RSS"][1] = "MAIN_MODULE_EXTERNAL_RSS";
$modules["MAIN_MODULE_EXTERNAL_RSS"][2] = MAIN_MODULE_EXTERNAL_RSS;
$modules["MAIN_MODULE_EXTERNAL_RSS"][3] = "Module de gestion de syndication de sites externes";
$modules["MAIN_MODULE_EXTERNAL_RSS"][4] = "modExternalRss";

if ($action == 'set')
{
  $sql = "REPLACE INTO llx_const SET name = '".$value."', value='1', visible = 0";

  if ($db->query($sql))
    {
      $modules[$value][2] = 1;

      $modName = $modules[$value][4];
      if ($modName)
	{
	  $file = $modName . ".class.php";
	  include("../includes/modules/$file");
	  $objMod = new $modName($db);
	  $objMod->init();
	}
    }
}

if ($action == 'reset')
{
  $sql = "REPLACE INTO llx_const SET name = '".$value."', value='0', visible = 0";

  if ($db->query($sql))
    {
      $modules[$value][2] = 0;

      $modName = $modules[$value][4];
      if ($modName)
	{
	  $file = $modName . ".class.php";
	  include("../includes/modules/$file");
	  $objMod = new $modName($db);
	  $objMod->remove();
	}
    }
}

$db->close();

print_titre("Modules");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Description</td><td align="center">Activé</td>';
print '<td align="center">Action</td></tr>';

foreach ($modules as $key => $value)
{
  $titre = $modules[$key][0];
  $const_name = $modules[$key][1];
  $const_value = $modules[$key][2];
  $desc = $modules[$key][3];

  print '<tr><td>';
  echo "$titre";
  print "</td><td>\n";
  echo "$desc";
  print '</td><td align="center">';

  if ($const_value == 1)
    {
      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
    }
  else
    {
      print "&nbsp;";
    }
  
  print '</td><td align="center">';
  
  if ($const_value == 1)
    {
      print '<a href="modules.php?action=reset&value='.$const_name.'">Désactiver</a>';
    }
  else
    {
      print '<a href="modules.php?action=set&value='.$const_name.'">Activer</a>';
    }
  
  print '</td></tr>';
}

print '</table>';

llxFooter();
?>
