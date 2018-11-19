<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Ferran Marcet       	<fmarcet@2byte.es>
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
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("suppliers", "orders", "companies", "stocks"));

$id = GETPOST('facid','int')?GETPOST('facid','int'):GETPOST('id','int');
$ref = GETPOST('ref');
$action = GETPOST('action','aZ09');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

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
$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('',$langs->trans("Order"),$help_url);

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
        $object->fetch_thirdparty();

        $author = new User($db);
        $author->fetch($object->user_author_id);

        $head = ordersupplier_prepare_head($object);

        $title=$langs->trans("SupplierOrder");
        dol_fiche_head($head, 'note', $title, -1, 'order');

		// Supplier order card

		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref='<div class="refidno">';
		// Ref supplier
		$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
		// Project
		if (! empty($conf->projet->enabled))
		{
		    $langs->load("projects");
		    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
		    if ($user->rights->fournisseur->commande->creer)
		    {
		        if ($action != 'classify')
		            //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
		            $morehtmlref.=' : ';
		        	if ($action == 'classify') {
		                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
		                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		                $morehtmlref.='<input type="hidden" name="action" value="classin">';
		                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
		                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
		                $morehtmlref.='</form>';
		            } else {
		                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		            }
		    } else {
		        if (! empty($object->fk_project)) {
		            $proj = new Project($db);
		            $proj->fetch($object->fk_project);
		            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
		            $morehtmlref.=$proj->ref;
		            $morehtmlref.='</a>';
		        } else {
		            $morehtmlref.='';
		        }
		    }
		}
		$morehtmlref.='</div>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';


		$cssclass="titlefield";
        include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

        print '</div>';

        dol_fiche_end();
    }
    else
    {
        /* Order not found */
        $langs->load("errors");
        print $langs->trans("ErrorRecordNotFound");
    }
}

// End of page
llxFooter();
$db->close();
