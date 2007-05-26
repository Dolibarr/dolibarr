<?php
/* Copyright (C) 2004-2007 Laurent Destailleur       <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin             <regis.houssin@cap-networks.com>
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
	    \file       htdocs/admin/fckeditor.php
		\ingroup    fckeditor
		\brief      Page d'activation du module FCKeditor dans les autres modules
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("fckeditor");

if (!$user->admin)
  accessforbidden();

// Constante et traduction de la description du module
$modules = array(
'SOCIETE' => 'FCKeditorForCompany',
'PRODUCTDESC' => 'FCKeditorForProduct',
'DETAILS' => 'FCKeditorForProductDetails',
'MAILING' => 'FCKeditorForMailing',
'MEMBER' => 'FCKeditorForMembers'
);
// Conditions pour que l'option soit proposée
$conditions = array(
'SOCIETE' => $conf->societe->enabled,
'PRODUCTDESC' => ($conf->produit->enabled||$conf->service->enabled),
'DETAILS' => ($conf->facture->enabled||$conf->propal->enabled||$conf->commande->enabled),
'MAILING' => $conf->mailing->enabled,
'MEMBER' => $conf->adherent->enabled
);
// Picto
$picto = array(
'SOCIETE' => 'company',
'PRODUCTDESC' => 'product',
'DETAILS' => 'generic',
'MAILING' => 'email',
'MEMBER' => 'user'
);


foreach($modules as $const => $desc)
{
	if ($_GET["action"] == 'activate_'.strtolower($const))
	{
	    dolibarr_set_const($db, "FCKEDITOR_ENABLE_".$const, "1");
	    //si fckeditor est activé dans la description produit/service, on l'active dans les documents
	    if ($const == 'PRODUCTDESC')
	    {
	    	dolibarr_set_const($db, "FCKEDITOR_ENABLE_DETAILS", "1");
	    }
	    Header("Location: fckeditor.php");
	    exit;
	}
	if ($_GET["action"] == 'disable_'.strtolower($const))
	{
		dolibarr_del_const($db, "FCKEDITOR_ENABLE_".$const);
		//si fckeditor est desactivé dans les documents, on le désactive dans la description produit/service
	    if ($const == 'DETAILS')
	    {
	    	dolibarr_del_const($db, "FCKEDITOR_ENABLE_PRODUCTDESC");
	    }
		Header("Location: fckeditor.php");
		exit;
	}
}


/*
 * Affiche page
 */

llxHeader("","");

$html=new Form($db);

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/fckeditor.php";
$head[$h][1] = $langs->trans("Activation");
$hselected=$h;
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 * Activation/désactivation de FCKeditor
 */

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("ActivateFCKeditor").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Action").'</td>';
print "</tr>\n";

// Modules
foreach($modules as $const => $desc)
{
	// Si condition non remplie, on ne propose pas l'option
	if (! $conditions[$const]) continue;
	
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="16">'.img_object("",$picto[$const]).'</td>';
	print '<td>'.$langs->trans($desc).'</td>';
	print '<td align="center" width="20">';

	$constante = FCKEDITOR_ENABLE_.$const;
	$value = $conf->global->$constante;

	print $value == 1 ? img_tick() : '&nbsp;';

	print '</td>';
	print '<td align="center" width="100">';

	if($value == 0)
	{
		print '<a href="fckeditor.php?action=activate_'.strtolower($const).'">'.$langs->trans("Activate").'</a>';
	}
	else if($value == 1)
	{
		print '<a href="fckeditor.php?action=disable_'.strtolower($const).'">'.$langs->trans("Disable").'</a>';
	}

	print "</td>";
	print '</tr>';
}

print '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
