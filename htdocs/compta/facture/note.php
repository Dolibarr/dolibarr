<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *      \file       htdocs/compta/facture/note.php
 *      \ingroup    facture
 *      \brief      Fiche de notes sur une facture
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills'));

$id=(GETPOST('id', 'int')?GETPOST('id', 'int'):GETPOST('facid', 'int'));  // For backward compatibility
$ref=GETPOST('ref', 'alpha');
$socid=GETPOST('socid', 'int');
$action=GETPOST('action', 'alpha');

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'facture', $id, '');

$object = new Facture($db);
$object->fetch($id);

$permissionnote=$user->rights->facture->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once



/*
 * View
 */

$title = $langs->trans('InvoiceCustomer') . " - " . $langs->trans('Notes');
$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
llxHeader('', $title, $helpurl);

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$object = new Facture($db);
	$object->fetch($id, $ref);

	$object->fetch_thirdparty();

    $head = facture_prepare_head($object);

    $totalpaye = $object->getSommePaiement();

    dol_fiche_head($head, 'note', $langs->trans("InvoiceCustomer"), -1, 'bill');

    // Invoice content

    $linkback = '<a href="' . DOL_URL_ROOT . '/compta/facture/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref='<div class="refidno">';
    // Ref customer
    $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'customer');
    // Project
    if (! empty($conf->projet->enabled))
    {
    	$langs->load("projects");
    	$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
    	if ($user->rights->facture->creer)
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

    $object->totalpaye = $totalpaye;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';


	$cssclass="titlefield";
    include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	dol_fiche_end();
}

// End of page
llxFooter();
$db->close();
