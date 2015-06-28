<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/comm/propal/note.php
 *	\ingroup    propal
 *	\brief      Fiche d'information sur une proposition commerciale
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';

$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load("companies");

$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'propale', $id, 'propal');

$object = new Propal($db);



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

$permissionnote=$user->rights->propale->creer;	// Used by the include of actions_setnotes.inc.php

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once



/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader('',$langs->trans('Proposal'),'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	if ($mesg) print $mesg;

	$now=dol_now();

	if ($object->fetch($id, $ref))
	{
		$societe = new Societe($db);
		if ( $societe->fetch($object->socid) )
		{
			$head = propal_prepare_head($object);
			dol_fiche_head($head, 'note', $langs->trans('Proposal'), 0, 'propal');

			print '<table class="border" width="100%">';

			$linkback = '<a href="'.DOL_URL_ROOT.'/comm/propal/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans('BackToList').'</a>';

			// Ref
			print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
			print $form->showrefnav($object,'ref',$linkback,1,'ref','ref','');
			print '</td></tr>';

			// Ref client
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
			print $langs->trans('RefCustomer').'</td><td align="left">';
			print '</td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			print $object->ref_client;
			print '</td>';
			print '</tr>';

			// Customer
			if ( is_null($object->client) )
				$object->fetch_thirdparty();
			print "<tr><td>".$langs->trans("Company")."</td>";
			print '<td colspan="3">'.$object->client->getNomUrl(1).'</td></tr>';

			// Discounts
			print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="3">';
			if ($societe->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$societe->remise_percent);
			else print $langs->trans("CompanyHasNoRelativeDiscount");
			$absolute_discount=$societe->getAvailableDiscounts();
			print '. ';
			if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
			else print $langs->trans("CompanyHasNoAbsoluteDiscount");
			print '.';
			print '</td></tr>';

			// Date
			print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
			print dol_print_date($object->date,'daytext');
			print '</td>';
			print '</tr>';

			// Date fin propal
			print '<tr>';
			print '<td>'.$langs->trans('DateEndPropal').'</td><td colspan="3">';
			if ($object->fin_validite)
			{
				print dol_print_date($object->fin_validite,'daytext');
				if ($object->statut == Propal::STATUS_VALIDATED && $object->fin_validite < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
			}
			else
			{
				print $langs->trans("Unknown");
			}
			print '</td>';
			print '</tr>';

			print "</table>";

			print '<br>';

			include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

			dol_fiche_end();
		}
	}
}


llxFooter();
$db->close();
