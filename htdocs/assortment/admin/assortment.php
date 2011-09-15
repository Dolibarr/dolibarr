<?php
/* Copyright (C) 2011 Florian HENRY  <florian.henry.mail@gmail.com>
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
 */

/**
 *  \file       htdocs/assortment/admin/assortment.php
 *  \ingroup    crm
 *  \brief      Administration page for Assortment by customer/supplier
 *  \version    $Id: assortment.php,v 1.0 2011/01/01 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("assortment");

// Security check
if (!$user->admin)
accessforbidden();

//initalize var for display error or warning
$b_msgErr=false;
$b_msgCategoryMustBeOK=false;
$b_msgSetCategUseNo=false;
$s_msgDisplay='';

//this configuration required category module activated
if ($conf->global->ASSORTMENT_BY_CAT == 1 && $conf->global->MAIN_MODULE_CATEGORIE == 0)
{
	$b_msgCategoryMustBeOK = true;
	$b_msgErr=true;
	$s_msgDisplay = $langs->trans("msgCategModRequired");
}

// If category module is not active and use of category 
// for assortment is already set to Yes, then set it to No 
if ($b_msgErr && $b_msgCategoryMustBeOK)
{
	dolibarr_set_const($db, "ASSORTMENT_BY_CAT", 0,'chaine',0,'',$conf->entity);
	$b_msgSetCategUseNo = true;
	$b_msgCategoryMustBeOK = false;
	$s_msgDisplay = $langs->trans("msgSetCategUseNo");
}

if ($_POST["action"] == 'AdminByCateg')
{
	//this configuration required category module activated
	if ($_POST["activate_assortbycat"] == 1 && $conf->global->MAIN_MODULE_CATEGORIE == 0)
	{
		$b_msgCategoryMustBeOK = true;
		$b_msgErr=true;
		$s_msgDisplay = $langs->trans("msgCategModRequired");
	}
	else
	{
		dolibarr_set_const($db, "ASSORTMENT_BY_CAT", $_POST["activate_assortbycat"],'chaine',0,'',$conf->entity);		
		if (isset ($_POST['activate_assortbycatrecursive']))
		{
			dolibarr_set_const($db, "ASSORTMENT_BY_CAT_RECURSIVE", $_POST["activate_assortbycatrecursive"],'chaine',0,'',$conf->entity);		
		}
		if (isset ($_POST['activate_orderassort']))
		{
			dolibarr_set_const($db, "ASSORTMENT_ON_ORDER", $_POST["activate_orderassort"],'chaine',0,'',$conf->entity);		
		}
		if (isset ($_POST['activate_orderfourassort']))
		{
			dolibarr_set_const($db, "ASSORTMENT_ON_ORDER_FOUR", $_POST["activate_orderfourassort"],'chaine',0,'',$conf->entity);		
		}
		
	}
}


/*
 * Affiche page
 */


llxHeader('',$langs->trans("AssortmentSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AssortmentSetup"),$linkback,'setup');

if ($b_msgErr)
{
	if ($b_msgCategoryMustBeOK)
	{
		print '<div class="error">';
		print $s_msgDisplay;
		print '</div>';
	}
	if ($b_msgSetCategUseNo)
	{
		print '<div class="warning">';
		print $s_msgDisplay;
		print '</div>';
	}
}

$html=new Form($db);
$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "  <td>".$langs->trans("Parameters")."</td>\n";
print "  <td align=\"right\" width=\"60\">".$langs->trans("Value")."</td>\n";
print "  <td width=\"80\">&nbsp;</td></tr>\n";

/*
 * Formulaire parametres divers
 */

// Assortment by category activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"assortment.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"AdminByCateg\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("AssortmentCategAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_assortbycat",$conf->global->ASSORTMENT_BY_CAT,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
if ($conf->global->ASSORTMENT_BY_CAT == 1)
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans("AdminByCategRecusiv").'</td>';
	print '<td width="60" align="right">';
	print $html->selectyesno("activate_assortbycatrecursive",$conf->global->ASSORTMENT_BY_CAT_RECURSIVE,1);
	print '</td><td align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</td>";
	print '</tr>';
}

$var=!$var;
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("OrderLimitAssort").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_orderassort",$conf->global->ASSORTMENT_ON_ORDER,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';

$var=!$var;
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("OrderFourLimitAssort").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_orderfourassort",$conf->global->ASSORTMENT_ON_ORDER_FOUR,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';


print '</form>';


print '</tr>';
print '</form>';
print '</table>';

$db->close();

llxFooter('$Date: 2010/12/15 18:15:08 $ - $Revision: 1.5 $');

?>
