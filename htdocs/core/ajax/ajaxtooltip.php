<?php
/* Copyright (C) 2007-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2023  Frédéric France         <frederic.france@netlogic.fr>
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
 *      \file       htdocs/core/ajax/ajaxtooltip.php
 *      \ingroup    tooltip
 *      \brief      This script returns content of tooltip
 */


if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
include '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

top_httphead();

$id = GETPOST('id', 'int');
$objecttype = GETPOST('objecttype', 'aZ09');

$html = '';
$regs = array();
$params = array();
if (GETPOSTISSET('infologin')) {
	$params['infologin'] = GETPOST('infologin', 'int');
}
if (GETPOSTISSET('option')) {
	$params['option'] = GETPOST('option', 'restricthtml');
}
// If we ask a resource form external module (instead of default path)
if (preg_match('/^([^@]+)@([^@]+)$/i', $objecttype, $regs)) {
	$myobject = $regs[1];
	$module = $regs[2];
} else {
	// Parse $objecttype (ex: project_task)
	$module = $myobject = $objecttype;
	if (preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
		$module = $regs[1];
		$myobject = $regs[2];
	}
}

// Generic case for $classpath
$classpath = $module.'/class';

// Special cases, to work with non standard path
if ($objecttype == 'facture' || $objecttype == 'invoice') {
	$langs->load('bills');
	$classpath = 'compta/facture/class';
	$module = 'facture';
	$myobject = 'facture';
} elseif ($objecttype == 'bank_account') {
	$langs->loadLangs(['banks', 'compta']);
	$classpath = 'compta/bank/class';
	$module = 'banque';
	$myobject = 'account';
} elseif ($objecttype == 'category') {
	$langs->loadLangs(['categories']);
	$classpath = 'categories/class';
	$module = 'categorie';
	$myobject = 'categorie';
} elseif ($objecttype == 'commande' || $objecttype == 'order') {
	$langs->load('orders');
	$classpath = 'commande/class';
	$module = 'commande';
	$myobject = 'commande';
} elseif ($objecttype == 'propal') {
	$langs->load('propal');
	$classpath = 'comm/propal/class';
} elseif ($objecttype == 'action') {
	$langs->load('agenda');
	$classpath = 'comm/action/class';
	$module = 'agenda';
	$myobject = 'actioncomm';
} elseif ($objecttype == 'supplier_proposal') {
	$langs->load('supplier_proposal');
	$classpath = 'supplier_proposal/class';
} elseif ($objecttype == 'shipping') {
	$langs->load('sendings');
	$classpath = 'expedition/class';
	$myobject = 'expedition';
	$module = 'expedition_bon';
} elseif ($objecttype == 'delivery') {
	$langs->load('deliveries');
	$classpath = 'delivery/class';
	$myobject = 'delivery';
	$module = 'delivery_note';
} elseif ($objecttype == 'contract') {
	$langs->load('contracts');
	$classpath = 'contrat/class';
	$module = 'contrat';
	$myobject = 'contrat';
} elseif ($objecttype == 'member') {
	$langs->load('members');
	$classpath = 'adherents/class';
	$module = 'adherent';
	$myobject = 'adherent';
} elseif ($objecttype == 'fichinter') {
	$langs->load('interventions');
	$classpath = 'fichinter/class';
	$module = 'ficheinter';
	$myobject = 'fichinter';
} elseif ($objecttype == 'project') {
	$langs->load('projects');
	$classpath = 'projet/class';
	$module = 'projet';
} elseif ($objecttype == 'task') {
	$langs->load('projects');
	$classpath = 'projet/class';
	$module = 'projet';
	$myobject = 'task';
} elseif ($objecttype == 'stock') {
	$classpath = 'product/stock/class';
	$module = 'stock';
	$myobject = 'stock';
} elseif ($objecttype == 'inventory') {
	$classpath = 'product/inventory/class';
	$module = 'stock';
	$myobject = 'inventory';
} elseif ($objecttype == 'mo') {
	$classpath = 'mrp/class';
	$module = 'mrp';
	$myobject = 'mo';
} elseif ($objecttype == 'productlot') {
	$classpath = 'product/stock/class';
	$module = 'stock';
	$myobject = 'productlot';
} elseif ($objecttype == 'usergroup') {
	$classpath = 'user/class';
	$module = 'user';
	$myobject = 'usergroup';
}

// Generic case for $classfile and $classname
$classfile = strtolower($myobject);
$classname = ucfirst($myobject);

if ($objecttype == 'invoice_supplier') {
	$classfile = 'fournisseur.facture';
	$classname = 'FactureFournisseur';
	$classpath = 'fourn/class';
	$module = 'fournisseur';
} elseif ($objecttype == 'order_supplier') {
	$classfile = 'fournisseur.commande';
	$classname = 'CommandeFournisseur';
	$classpath = 'fourn/class';
	$module = 'fournisseur';
} elseif ($objecttype == 'supplier_proposal') {
	$classfile = 'supplier_proposal';
	$classname = 'SupplierProposal';
	$classpath = 'supplier_proposal/class';
	$module = 'supplier_proposal';
} elseif ($objecttype == 'stock') {
	$classpath = 'product/stock/class';
	$classfile = 'entrepot';
	$classname = 'Entrepot';
} elseif ($objecttype == 'facturerec') {
	$classpath = 'compta/facture/class';
	$classfile = 'facture-rec';
	$classname = 'FactureRec';
	$module = 'facture';
} elseif ($objecttype == 'mailing') {
	$classpath = 'comm/mailing/class';
	$classfile = 'mailing';
	$classname = 'Mailing';
} elseif ($objecttype == 'adherent_type') {
	$langs->load('members');
	$classpath = 'adherents/class';
	$classfile = 'adherent_type';
	$module = 'adherent';
	$myobject = 'adherent_type';
	$classname = 'AdherentType';
} elseif ($objecttype == 'contact') {
	$module = 'societe';
}
// print "objecttype=".$objecttype." module=".$module." subelement=".$subelement." classfile=".$classfile." classname=".$classname." classpath=".$classpath."<br>";

if (isModEnabled($module)) {
	$res = dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
	if ($res) {
		if (class_exists($classname)) {
			$object = new $classname($db);
			$res = $object->fetch($id);
			if ($res > 0) {
				$html = $object->getTooltipContent($params);
			} elseif ($res == 0) {
				$html = $langs->trans('Deleted');
			}
			unset($object);
		} else {
			dol_syslog("Class with classname ".$classname." is unknown even after the include", LOG_ERR);
		}
	}
}

print $html;

$db->close();
