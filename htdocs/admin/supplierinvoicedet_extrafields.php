<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015 	Claudio Aschieri 		<c.aschieri@19.coop>
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
 *      \file       htdocs/admin/supplierinvoicedet_extrafields.php
 *		\ingroup    fourn
 *		\brief      Page to setup extra fields of supplierinvoice line
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


if (!$user->admin)
	accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("bills");
$langs->load("orders");
$langs->load("suppliers");

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label=ExtraFields::$type2label;
$type2label=array('');
foreach ($tmptype2label as $key => $val) $type2label[$key]=$langs->trans($val);

$action=GETPOST('action', 'alpha');
$attrname=GETPOST('attrname', 'alpha');
$elementtype='facture_fourn_det'; //Must be the $table_element of the class that manage extrafield

if (!$user->admin) accessforbidden();


/*
 * Actions
 */

require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';



/*
 * View
 */

$textobject=$langs->transnoentitiesnoconv("BillsSuppliers");

llxHeader('',$langs->trans("SuppliersSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SuppliersSetup"),$linkback,'title_setup');
print "<br>\n";

$head = supplierorder_admin_prepare_head();

dol_fiche_head($head, 'supplierinvoicedet', $langs->trans("Suppliers"), 0, 'company');

require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';

dol_fiche_end();


// Buttons
if ($action != 'create' && $action != 'edit')
{
    print '<div class="tabsAction">';
    print "<a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?action=create\">".$langs->trans("NewAttribute")."</a>";
    print "</div>";
}


/* ************************************************************************** */
/*                                                                            */
/* Creation d'un champ optionnel											  */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
    print "<br>";
    print load_fiche_titre($langs->trans('NewAttribute'));

    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && ! empty($attrname))
{
    print "<br>";
    print load_fiche_titre($langs->trans("FieldEdition", $attrname));

    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}

llxFooter();

$db->close();
