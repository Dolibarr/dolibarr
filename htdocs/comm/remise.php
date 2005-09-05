<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/comm/remise.php
        \ingroup    commercial
		\brief      Onglet remise de la societe
		\version    $Revision$
*/
 
require_once("./pre.inc.php");
require_once("../contact.class.php");
//require_once("../cactioncomm.class.php");
//require_once("../actioncomm.class.php");

$user->getrights('propale');
$user->getrights('commande');
$user->getrights('projet');


$langs->load("orders");
$langs->load("companies");


if ($_POST["action"] == 'setremise')
{
    $soc = New Societe($db);
    $soc->fetch($_GET["id"]);
    $soc->set_remise_client($_POST["remise"],$user);
        
    Header("Location: remise.php?id=".$_GET["id"]);
    exit;
}


llxHeader();

$_socid = $_GET["id"];


// Sécurité si un client essaye d'accéder à une autre fiche que la sienne
if ($user->societe_id > 0) 
{
    $_socid = $user->societe_id;
}


/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/  
if ($_socid > 0)
{
  // On recupere les donnees societes par l'objet
  $objsoc = new Societe($db);
  $objsoc->id=$_socid;
  $objsoc->fetch($_socid,$to);
  
  $dac = strftime("%Y-%m-%d %H:%M", time());
  if ($errmesg)
    {
      print '<div class="error">'.$errmesg.'</div><br>';
    }
  
  /*
   * Affichage onglets
   */
  $h = 0;
  
  $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
  $head[$h][1] = $langs->trans("Company");
  $h++;
  
  if ($objsoc->client==1)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = $langs->trans("Customer");
      $h++;
    }
  if ($objsoc->client==2)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$obj->socid;
      $head[$h][1] = $langs->trans("Prospect");
      $h++;
    }
  if ($objsoc->fournisseur)
    {
      $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = $langs->trans("Supplier");
      $h++;
    }
  
  if ($conf->compta->enabled) {
    $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Accountancy");
    $h++;
  }

    $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Note");
    $h++;

    if ($user->societe_id == 0)
    {
        $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$objsoc->id;
        $head[$h][1] = $langs->trans("Documents");
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Notifications");
    $h++;
    
    if ($user->societe_id == 0)
      {
	$head[$h][0] = DOL_URL_ROOT."/comm/index.php?socidp=$objsoc->id&action=add_bookmark";
	$head[$h][1] = img_object($langs->trans("BookmarkThisPage"),'bookmark');
	$head[$h][2] = 'image';
      }

    dolibarr_fiche_head($head, $hselected, $objsoc->nom);

    /*
     *
     *
     */
    print '<form method="POST" action="remise.php?id='.$objsoc->id.'">';
    print '<input type="hidden" name="action" value="setremise">';
    print '<table width="100%" border="0">';
    print '<tr><td valign="top">';
    print '<table class="border" width="100%">';

    print '<tr><td colspan="2" width="25%">';
    print $langs->trans("CustomerRelativeDiscount").'</td><td colspan="2">'.$objsoc->remise_client."%</td></tr>";

    print '<tr><td colspan="2">';
    print $langs->trans("NewValue").'</td><td colspan="2"><input type="text" size="5" name="remise" value="'.$objsoc->remise_client.'">%</td></tr>';
    print '<tr><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

    print "</table>";
    print "</form>";

    print "</td>\n";
    

    print "</td></tr>";
    print "</table></div>\n";    
    print '<br>';        


    /*
     * Liste de l'historique des remises
     */
    $sql  = "SELECT rc.rowid,rc.remise_client,".$db->pdate("rc.datec")." as dc, u.code";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_remise as rc, ".MAIN_DB_PREFIX."user as u";
    $sql .= " WHERE rc.fk_soc =". $objsoc->id;
    $sql .= " AND u.rowid = rc.fk_user_author";
    $sql .= " ORDER BY rc.datec DESC";

    $resql=$db->query($sql);
    if ($resql)
      {
	print '<table class="noborder" width="100%">';
	$tag = !$tag;
	print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Date").'</td>';
    print '<td>'.$langs->trans("Discount").'</td>';
    print '<td>'.$langs->trans("User").'</td>';
	print '</tr>';
	$i = 0 ; 
	$num = $db->num_rows($resql);

	while ($i < $num )
	  {
	    $obj = $db->fetch_object($resql);
	    $tag = !$tag;
	    print '<tr '.$bc[$tag].'>';
	    print '<td>'.dolibarr_print_date($obj->dc,"%d %B %Y %H:%M").'</td>';
	    print '<td>'.$obj->remise_client.' %</td>';
	    print '<td>'.$obj->code.'</td>';
	    print '</tr>';
	    $i++;
	  }
	$db->free($resql);
	print "</table>";
      }
    else
      {
	dolibarr_print_error($db);
      }

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
