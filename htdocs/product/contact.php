<?php
/* Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2024      Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2025		   MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		   Charlene Benke		<charlene@patas-monkey.com>

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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/fichinter/contact.php
 *       \ingroup    fichinter
 *       \brief      Onglet de gestion des contacts de produits
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('products'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

$object = new Product($db);
$result = $object->fetch($id, $ref);
if (!$result) {
	print 'Record not found';
	exit;
}

if ($object->id > 0) {
	if ($object->type == $object::TYPE_PRODUCT) {
		restrictedArea($user, 'product', $id, 'product&product', '', '');
	}
	if ($object->type == $object::TYPE_SERVICE) {
		restrictedArea($user, 'service', $id, 'product&product', '', '');
	}
} else {
	restrictedArea($user, 'product|service', $fieldvalue, 'product&product', '', '', $fieldtype);
}


$usercancreate = ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'));

/*
 * Adding a new contact
 */

if ($action == 'addcontact' && $usercancreate) {
	if ($result > 0 && $id > 0) {
		$contactid = (GETPOSTINT('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
		$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
		$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
	}

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			$mesg = $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
		} else {
			$mesg = $object->error;
		}

		setEventMessages($mesg, null, 'errors');
	}
} elseif ($action == 'swapstatut' && $usercancreate) {
	// Toggle the status of a contact
	$result = $object->swapContactStatus(GETPOSTINT('ligne'));
} elseif ($action == 'deletecontact' && $usercancreate) {
	// Deletes a contact
	$result = $object->delete_contact(GETPOSTINT('lineid'));

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

$title = $langs->trans('ProductServiceCard');
$help_url = "";
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Notes');
	$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos|DE:Modul_Produkte';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Notes');
	$help_url = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios|DE:Modul_Leistungen';
}

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-product page-card_note');

// Mode vue et edition

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = product_prepare_head($object);
	$titre = $langs->trans("CardProduct".$object->type);
	$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

	print dol_get_fiche_head($head, 'contact', $titre, -1, $picto);


	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';
	$object->next_prev_filter = "(te.fk_product_type:=:".((int) $object->type).")";

	$shownav = 1;
	if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
		$shownav = 0;
	}

	dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

	print dol_get_fiche_end();

	print '<br>';

	if (getDolGlobalString('PRODUCT_HIDE_ADD_CONTACT_USER')) {
		$hideaddcontactforuser = 1;
	}
	if (getDolGlobalString('PRODUCT_HIDE_ADD_CONTACT_THIPARTY')) {
		$hideaddcontactforthirdparty = 1;
	}

	// Contacts lines (modules that overwrite templates must declare this into descriptor)
	$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
	foreach ($dirtpls as $reldir) {
		$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
		if ($res) {
			break;
		}
	}
}


llxFooter();
$db->close();
