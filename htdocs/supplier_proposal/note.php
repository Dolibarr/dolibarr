<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php';

$langs->load('supplier_proposal');
$langs->load('compta');
$langs->load('bills');

$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'supplier_proposal', $id, 'supplier_proposal');

$object = new SupplierProposal($db);



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

$permissionnote=$user->rights->supplier_proposal->creer;	// Used by the include of actions_setnotes.inc.php

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once



/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader('',$langs->trans('CommRequest'),'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur');

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
			$head = supplier_proposal_prepare_head($object);
			dol_fiche_head($head, 'note', $langs->trans('CommRequest'), 0, 'supplier_proposal');

			print '<table class="border" width="100%">';

			$linkback = '<a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans('BackToList').'</a>';

			// Ref
			print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
			print $form->showrefnav($object,'ref',$linkback,1,'ref','ref','');
			print '</td></tr>';
			
			// Customer
			if ( is_null($object->client) )
				$object->fetch_thirdparty();
			print "<tr><td>".$langs->trans("Supplier")."</td>";
			print '<td colspan="3">'.$object->client->getNomUrl(1).'</td></tr>';
			
			print '<tr><td>'.$langs->trans('SupplierProposalDate').'</td><td colspan="3">';
			print dol_print_date($object->date_livraison,'daytext');
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
