<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
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
 *       \file       htdocs/fourn/commande/note.php
 *       \ingroup    commande
 *       \brief      Fiche note commande
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

$id = GETPOST('facid','int')?GETPOST('facid','int'):GETPOST('id','int');
$ref = GETPOST('ref');
$action = GETPOST('action');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');

$object = new CommandeFournisseur($db);
$object->fetch($id, $ref);

$permissionnote=$user->rights->fournisseur->commande->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once


/*
 * View
 */

llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");

$form = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

$now=dol_now();

if ($id > 0 || ! empty($ref))
{
    if ($result >= 0)
    {
        $soc = new Societe($db);
        $soc->fetch($object->socid);

        $author = new User($db);
        $author->fetch($object->user_author_id);

        $head = ordersupplier_prepare_head($object);

        $title=$langs->trans("SupplierOrder");
        dol_fiche_head($head, 'note', $title, 0, 'order');


        /*
         *   Commande
         */
        print '<table class="border" width="100%">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

        // Ref
        print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
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

        // Author
        print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
        print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
        print '</tr>';

        print "</table>";

        print '<br>';

        $colwidth=20;
        include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

        dol_fiche_end();
    }
    else
    {
        /* Order not found */
        $langs->load("errors");
        print $langs->trans("ErrorRecordNotFound");
    }
}


llxFooter();

$db->close();
