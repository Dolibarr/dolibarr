<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani  <acianfa@free.fr>
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
	    \file       htdocs/comm/multiprix.php
        \ingroup    commercial
		\brief      Onglet choix du niveau de prix
		\version    $Revision$
*/
 
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");

$user->getrights('propale');
$user->getrights('commande');
$user->getrights('projet');


$langs->load("orders");
$langs->load("companies");


if ($_POST["action"] == 'setpricelevel')
{
    $soc = New Societe($db);
    $soc->fetch($_GET["id"]);
    $soc->set_price_level($_POST["price_level"],$user);
        
    Header("Location: multiprix.php?id=".$_GET["id"]);
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
	
	if ($errmesg)
	{
		print '<div class="error">'.$errmesg.'</div><br>';
	}
	

	/*
	 * Affichage onglets
	 */

	$head = societe_prepare_head($objsoc);

    dolibarr_fiche_head($head, 'customer', $objsoc->nom);


    print '<form method="POST" action="multiprix.php?id='.$objsoc->id.'">';
    print '<input type="hidden" name="action" value="setpricelevel">';
    print '<table width="100%" border="0">';
    print '<tr><td valign="top">';
    print '<table class="border" width="100%">';

    print '<tr><td colspan="2" width="25%">';
    print $langs->trans("PriceLevel").'</td><td colspan="2">'.$objsoc->price_level."</td></tr>";

    print '<tr><td colspan="2">';
    print $langs->trans("NewValue").'</td><td colspan="2">';
	print '<select name="price_level">';
	for($i=1;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
	{
				print '<option value="'.$i.'"' ;
				if($i == $objsoc->price_level)
					print 'selected';
				print '>'.$i.'</option>';
	}
	print '</select>';
	print '</td></tr>';
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
    $sql  = "SELECT rc.rowid,rc.price_level,".$db->pdate("rc.datec")." as dc, u.login";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_prices as rc, ".MAIN_DB_PREFIX."user as u";
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
    print '<td>'.$langs->trans("PriceLevel").'</td>';
    print '<td>'.$langs->trans("User").'</td>';
	print '</tr>';
	$i = 0 ; 
	$num = $db->num_rows($resql);

	while ($i < $num )
	  {
	    $obj = $db->fetch_object($resql);
	    $tag = !$tag;
	    print '<tr '.$bc[$tag].'>';
	    print '<td>'.dolibarr_print_date($obj->dc,"dayhour").'</td>';
	    print '<td>'.$obj->price_level.' </td>';
	    print '<td>'.$obj->login.'</td>';
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
