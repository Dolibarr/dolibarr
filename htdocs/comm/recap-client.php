<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/comm/recap-client.php
 *		\ingroup    societe
 *		\brief      Page de fiche recap client
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Load translation files required by the page
$langs->load("companies");
if (!empty($conf->facture->enabled)) $langs->load("bills");

// Security check
$socid = $_GET["socid"];
if ($user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}



/*
 *	View
 */

llxHeader();

if ($socid > 0)
{
	$societe = new Societe($db);
	$societe->fetch($socid);

	/*
     * Affichage onglets
     */
	$head = societe_prepare_head($societe);

	print dol_get_fiche_head($head, 'customer', $langs->trans("ThirdParty"), 0, 'company');


	print "<table width=\"100%\">\n";
	print '<tr><td valign="top" width="50%">';

	print '<table class="border centpercent">';

	// Name
	print '<tr><td width="20%">'.$langs->trans("ThirdParty").'</td><td width="80%" colspan="3">'.$societe->getNomUrl(1).'</td></tr>';

	// Prefix
	if (!empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	{
		print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
		print ($societe->prefix_comm ? $societe->prefix_comm : '&nbsp;');
		print '</td></tr>';
	}

	print "</table>";

	print "</td></tr></table>\n";

	print '</div>';


	print $langs->trans("FeatureNotYetAvailable");
} else {
  	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
