<?php
/* Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file 		htdocs/barcode/codeinit.php
 *	\ingroup    member
 *	\brief      Page to make mass init of barcode
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("admin");
$langs->load("members");
$langs->load("errors");

// Choix de l'annee d'impression ou annee courante.
$now = dol_now();
$year=dol_print_date($now,'%Y');
$month=dol_print_date($now,'%m');
$day=dol_print_date($now,'%d');
$forbarcode=GETPOST('forbarcode');
$fk_barcode_type=GETPOST('fk_barcode_type');
$mode=GETPOST('mode');
$modellabel=GETPOST("modellabel");	// Doc template to use
$numberofsticker=GETPOST('numberofsticker','int');

$mesg='';

$action=GETPOST('action');

$producttmp=new Product($db);
$thirdpartytmp=new Societe($db);


/*
 * Actions
 */

if ($action == 'init')
{
	$action='';


}



/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("MassBarcodeInit"));

print_fiche_titre($langs->trans("MassBarcodeInit"));
print '<br>';

print $langs->trans("MassBarcodeInitDesc").'<br>';
print '<br>';

dol_htmloutput_errors($mesg);

//print img_picto('','puce').' '.$langs->trans("PrintsheetForOneBarCode").'<br>';
//print '<br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="mode" value="label">';
print '<input type="hidden" name="action" value="builddoc">';

print '<br><input class="button" type="submit" id="submitformbarcodegen" '.((GETPOST("selectorforbarcode") && GETPOST("selectorforbarcode"))?'':'disabled="checked" ').'value="'.$langs->trans("InitEmptyBarCode").'">';

print '</form>';
print '<br>';

llxFooter();

$db->close();
?>
