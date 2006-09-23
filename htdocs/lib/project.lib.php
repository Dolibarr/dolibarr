<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/lib/project.lib.php
		\brief      Ensemble de fonctions de base pour le module projet
        \ingroup    societe
        \version    $Revision$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function project_prepare_head($objsoc)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$objsoc->id;
	$head[$h][1] = $langs->trans("Project");
    $head[$h][2] = 'project';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/fiche.php?id='.$objsoc->id;
	$head[$h][1] = $langs->trans("Tasks");
    $head[$h][2] = 'tasks';
	$h++;

	if ($conf->propal->enabled)
	{
		$langs->load("propal");
		$head[$h][0] = DOL_URL_ROOT.'/projet/propal.php?id='.$objsoc->id;
		$head[$h][1] = $langs->trans("Proposals");
	    $head[$h][2] = 'propal';
		$h++;
	}

	if ($conf->commande->enabled)
	{
		$langs->load("orders");
		$head[$h][0] = DOL_URL_ROOT.'/projet/commandes.php?id='.$objsoc->id;
		$head[$h][1] = $langs->trans("Orders");
	    $head[$h][2] = 'order';
		$h++;
	}

	if ($conf->facture->enabled)
	{
		$langs->load("bills");
		$head[$h][0] = DOL_URL_ROOT.'/projet/facture.php?id='.$objsoc->id;
		$head[$h][1] = $langs->trans("Invoices");
	    $head[$h][2] = 'invoice';
		$h++;
	}

	return $head;
}

?>