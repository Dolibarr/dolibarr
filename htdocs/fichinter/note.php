<?php
/* Copyright (C) 2005-2012	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2011-2012	Juanjo Menent	<jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/fichinter/note.php
 *	\ingroup    fichinter
 *	\brief      Fiche d'information sur une fiche d'intervention
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");

$langs->load('companies');
$langs->load("interventions");

$id = GETPOST('id','int');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

$object = new Fichinter($db);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'setnote_public' && $user->rights->ficheinter->creer)
{
	$object->fetch($id);
	$result=$object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
	if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'setnote' && $user->rights->ficheinter->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note'), ENT_QUOTES));
	if ($result < 0) dol_print_error($db,$object->error);
}


/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader();

$form = new Form($db);

if ($id > 0)
{
	if ($mesg) print $mesg;

	if ($object->fetch($id))
	{
		$societe = new Societe($db);
		if ($societe->fetch($object->socid))
		{
			$head = fichinter_prepare_head($object);
			dol_fiche_head($head, 'note', $langs->trans('InterventionCard'), 0, 'intervention');

			print '<table class="border" width="100%">';

			print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">'.$object->ref.'</td></tr>';

			// Company
			print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$societe->getNomUrl(1).'</td></tr>';

			print "</table>";

			print '<br>';

			include(DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php');

			print '</div>';
		}
	}
}

llxFooter();
$db->close();
?>
