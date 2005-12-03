<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/lib/invoice.lib.php
		\brief      Ensemble de fonctions de base pour le module factures
		\version    $Revision$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function facture_prepare_head($fac)
{
	global $langs, $conf;
	$h = 0;
	$head = array();
	
	$head[$h][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;
	$head[$h][1] = $langs->trans('CardBill');
	$hselected = $h;
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/contact.php?facid='.$fac->id;
	$head[$h][1] = $langs->trans('BillContacts');
	$h++;

	if ($conf->use_preview_tabs)
	{
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/apercu.php?facid='.$fac->id;
		$head[$h][1] = $langs->trans('Preview');
		$h++;
	}

	if ($fac->mode_reglement_code == 'PRE')
	{
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$fac->id;
		$head[$h][1] = $langs->trans('StandingOrders');
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$fac->id;
	$head[$h][1] = $langs->trans('Note');
	$h++;
	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$fac->id;
	$head[$h][1] = $langs->trans('Info');
	$h++;

	return $head;
}

?>