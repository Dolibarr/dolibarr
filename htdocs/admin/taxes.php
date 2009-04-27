<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
	    \file       htdocs/admin/taxes.php
        \ingroup    tax
        \brief      Page de configuration du module tax
		\version    $Id$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load('admin');
$langs->load('compta');
$langs->load('taxes');

if (!$user->admin)
  accessforbidden();

/*
 * View
 */

llxHeader();


// 0=normal, 1=option vat for services is on debit
$tax_mode = defined('TAX_MODE')?TAX_MODE:0;

if ($_POST['action'] == 'settaxmode')
{
  $tax_mode = $_POST['tax_mode'];
  if (! dolibarr_set_const($db, 'TAX_MODE', $tax_mode,'chaine',0,'',$conf->entity)) { print $db->error(); }
}

if ($_POST['action'] == 'update' || $_POST['action'] == 'add')
{
	if (! dolibarr_set_const($db, $_POST['constname'], $_POST['constvalue'], $typeconst[$_POST['consttype']], 0, isset($_POST['constnote']) ? $_POST['constnote'] : '',$conf->entity));
	{
	  	print $db->error();
	}
}

if ($_GET['action'] == 'delete')
{
	if (! dolibarr_del_const($db, $_GET['constname'],$conf->entity));
	{
	  	print $db->error();
	}
}


/*
 * Affichage page
 */

$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans('TaxSetup'),$linkback,'setup');


print '<br>';

print '<table class="noborder" width="100%">';

// Cas du parametre TAX_MODE
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="settaxmode">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('OptionVatMode').'</td><td>'.$langs->trans('Description').'</td>';
print '<td align="right"><input class="button" type="submit" value="'.$langs->trans('Modify').'"></td>';
print "</tr>\n";
print '<tr '.$bc[false].'><td width="200"><input type="radio" name="tax_mode" value="0"'.($tax_mode != 1 ? ' checked' : '').'> '.$langs->trans('OptionVATDefault').'</td>';
print '<td colspan="2">'.nl2br($langs->trans('OptionVatDefaultDesc'));
print "</td></tr>\n";
print '<tr '.$bc[true].'><td width="200"><input type="radio" name="tax_mode" value="1"'.($tax_mode == 1 ? ' checked' : '').'> '.$langs->trans('OptionVATDebitOption').'</td>';
print '<td colspan="2">'.nl2br($langs->trans('OptionVatDebitOptionDesc'))."</td></tr>\n";
print '</form>';

print "</table>\n";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
