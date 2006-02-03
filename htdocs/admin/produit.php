<?php
/* Copyright (C) 2004-2005 Laurent Destailleur       <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani          <acianfa@free.fr>
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
	    \file       htdocs/admin/produit.php
		\ingroup    produit
		\brief      Page d'administration/configuration du module Produit
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("propal");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'nbprod')
{
    dolibarr_set_const($db, "PRODUIT_LIMIT_SIZE", $_POST["value"]);
    Header("Location: produit.php");
    exit;
}
else if ($_POST["action"] == 'multiprix_num')
{
    dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", $_POST["value"]);
    Header("Location: produit.php");
    exit;
}
if ($_GET["action"] == 'activate_multiprix')
{
	$res=$db -> desc_table(MAIN_DB_PREFIX."product_price","price_level");
	if(! $db -> fetch_row())
	{
		// on ajoute le champ price_level dans la table product_price
		$field_desc = array('type'=>'TINYINT','value'=>'4','default'=>'1');
		if(! $db -> add_field(MAIN_DB_PREFIX."product_price","price_level",$field_desc,"after date_price"))
		{
			dolibarr_print_error($db);
			print "<script language='JavaScript'>setTimeout(\"document.location='./produit.php'\",5000);</script>";
		}
		else
		{
			// on ajoute le champ price_level dans la table societe
			if(! $db -> add_field(MAIN_DB_PREFIX."societe","price_level",$field_desc))
			{
				dolibarr_print_error($db);
				print "<script language='JavaScript'>setTimeout(\"document.location='./produit.php'\",5000);</script>";
			}
			// on crée la table societe_prices
			else
			{
				$table = MAIN_DB_PREFIX."societe_prices";
				$fields['rowid'] = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
				$fields['fk_soc'] = array('type'=>'int','value'=>'11','null'=>'not null','default'=> '0');
				$fields['tms'] = array('type'=>'timestamp','value'=>'14','null'=>'not null');
				$fields['datec'] = array('type'=>'datetime','default'=> 'null');
				$fields['fk_user_author'] = array('type'=>'int','value'=>'11','default'=> 'null');
				$fields['price_level'] = array('type'=>'tinyint','value'=>'4','default'=> '1');
				if(! $db -> create_table($table,$fields,"rowid","MyISAM"))
				{
					dolibarr_print_error($db);
					print "<script language='JavaScript'>setTimeout(\"document.location='./produit.php'\",5000);</script>";
				}
				else
				{
					dolibarr_set_const($db, "PRODUIT_MULTIPRICES", "1");
					dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", "6");
					Header("Location: produit.php");
				}
			}
		}
	}
	else
	{
			dolibarr_set_const($db, "PRODUIT_MULTIPRICES", "1");
			dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", "6");
			Header("Location: produit.php");
	}
    exit;
}
else if ($_GET["action"] == 'disable_multiprix')
{
    //"ALTER TABLE ".MAIN_DB_PREFIX."product_price drop price_level"
	dolibarr_del_const($db, "PRODUIT_MULTIPRICES");
	dolibarr_del_const($db, "PRODUIT_MULTIPRICES_LIMIT");
    Header("Location: produit.php");
    exit;
}

/*
 * Affiche page
 */

llxHeader('',$langs->trans("ProductSetup"));


print_titre($langs->trans("ProductSetup"));

/*
 * Formulaire parametres divers
 */

print '<br>';
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"nbprod\">";
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td align=\"left\">".$langs->trans("Value")."</td>\n";
print "  <td>&nbsp;</td>\n";
print "</tr><tr ".$bc[false].">";
print '<td>'.$langs->trans("NumberOfProductShowInSelect").'</td>';
print "<td align=\"left\"><input size=\"3\" type=\"text\" class=\"flat\" name=\"value\" value=\"".$conf->global->PRODUIT_LIMIT_SIZE."\"></td>";
print '<td><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</table>';
print '</form>';
print '<br>';


// multiprix activation/desactivation
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td align="center">&nbsp;</td>';
print '<td align="center">'.$langs->trans("Active").'</td>';
print "</tr>\n";
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"multiprix\">";
print "<tr ".$bc[false].">";
print '<td width="80%">'.$langs->trans("MultiPricesAbility").'</td>';
print '<td align="center">';
if($conf->global->PRODUIT_MULTIPRICES == 1)
	print img_tick();
print '</td>';
print "<td align=\"center\">";
if($conf->global->PRODUIT_MULTIPRICES == 0)
print '<a href="produit.php?action=activate_multiprix">'.$langs->trans("Activate").'</a>';
else if($conf->global->PRODUIT_MULTIPRICES == 1)
	print '<a href="produit.php?action=disable_multiprix">'.$langs->trans("Disable").'</a>';
print "</td>";
print '</tr>';
print '</table>';
print '</form>';


// multiprix nombre de prix a proposer
if($conf->global->PRODUIT_MULTIPRICES == 1)
{
	print '<br>';
	print "<form method=\"post\" action=\"produit.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"multiprix_num\">";
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print '  <td width="80%">'.$langs->trans("Name")."</td>\n";
	print "  <td align=\"left\">".$langs->trans("Value")."</td>\n";
	print "  <td>&nbsp;</td>\n";
	print "</tr><tr ".$bc[false].">";
	print '<td>'.$langs->trans("MultiPricesNumPrices").'</td>';
	print "<td align=\"left\"><input size=\"3\" type=\"text\" class=\"flat\" name=\"value\" value=\"".$conf->global->PRODUIT_MULTIPRICES_LIMIT."\"></td>";
	print '<td><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</tr>';
	print '</table>';
	print '</form>';
}
	


$db->close();

llxFooter();
?>
