<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/fourn/commande/info.php
 *       \ingroup    commande
 *       \brief      Fiche commande
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');

// Security check
$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');


/*
 * View
 */

$form =	new	Form($db);

$now=dol_now();

if ($id > 0 || ! empty($ref))
{
	$soc = new Societe($db);
	$object = new CommandeFournisseur($db);

	$result=$object->fetch($id,$ref);
	if ($result >= 0)
	{
        $object->info($object->id);
        
	    $soc->fetch($object->socid);

		$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
		llxHeader('',$langs->trans("Order"),$help_url);

		$head = ordersupplier_prepare_head($object);

		$title=$langs->trans("SupplierOrder");
		dol_fiche_head($head, 'info', $title, 0, 'order');


		/*
		*   Commande
		*/

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
		print '<td colspan="2">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print '</td>';
		print '</tr>';

		// Fournisseur
		print '<tr><td>'.$langs->trans("Supplier")."</td>";
		print '<td colspan="2">'.$soc->getNomUrl(1,'supplier').'</td>';
		print '</tr>';

		// Statut
		print '<tr>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td colspan="2">';
		print $object->getLibStatut(4);
		print "</td></tr>";

		// Date
		if ($object->methode_commande_id > 0)
		{
			print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
			if ($object->date_commande)
			{
				print dol_print_date($object->date_commande,"dayhourtext")."\n";
			}
			print "</td></tr>";

			if ($object->methode_commande)
			{
                print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$object->getInputMethod().'</td></tr>';
			}
		}

		print "</table>\n";
		print "<br>";

		print '<table width="100%"><tr><td>';
		dol_print_object_info($object, 1);
		print '</td></tr></table>';
		
		print '</div>';
	}
	else
	{
		/* Order not found */
		print "OrderNotFound";
	}
}


llxFooter();
$db->close();
