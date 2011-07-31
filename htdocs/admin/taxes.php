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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     \file       htdocs/admin/taxes.php
 *     \ingroup    tax
 *     \brief      Page de configuration du module tax
 *     \version    $Id: taxes.php,v 1.10 2011/07/31 22:23:24 eldy Exp $
 */

require("../main.inc.php");
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
$tax_mode = empty($conf->global->TAX_MODE)?0:$conf->global->TAX_MODE;

// TAX_MODE=0 (most cases):
//              Buy                     Sell
// Product      On delivery             On delivery
// Service      On payment              On payment

// TAX_MODE=1 (option):
//              Buy                     Sell
// Product      On delivery             On delivery
// Service      On invoice              On invoice


if ($_POST['action'] == 'settaxmode')
{
  $tax_mode = $_POST['tax_mode'];
  if (! dolibarr_set_const($db, 'TAX_MODE', $tax_mode,'chaine',0,'',$conf->entity)) { print $db->error(); }

  if ($tax_mode == 0)
  {
      if (! dolibarr_set_const($db, 'TAX_MODE_SELL_PRODUCT', 'invoice','chaine',0,'',$conf->entity)) { print $db->error(); }
      if (! dolibarr_set_const($db, 'TAX_MODE_BUY_PRODUCT', 'invoice','chaine',0,'',$conf->entity)) { print $db->error(); }
      if (! dolibarr_set_const($db, 'TAX_MODE_SELL_SERVICE', 'payment','chaine',0,'',$conf->entity)) { print $db->error(); }
      if (! dolibarr_set_const($db, 'TAX_MODE_BUY_SERVICE', 'payment','chaine',0,'',$conf->entity)) { print $db->error(); }
  }
  if ($tax_mode == 1)
  {
      if (! dolibarr_set_const($db, 'TAX_MODE_SELL_PRODUCT', 'invoice','chaine',0,'',$conf->entity)) { print $db->error(); }
      if (! dolibarr_set_const($db, 'TAX_MODE_BUY_PRODUCT', 'invoice','chaine',0,'',$conf->entity)) { print $db->error(); }
      if (! dolibarr_set_const($db, 'TAX_MODE_SELL_SERVICE', 'invoice','chaine',0,'',$conf->entity)) { print $db->error(); }
      if (! dolibarr_set_const($db, 'TAX_MODE_BUY_SERVICE', 'invoice','chaine',0,'',$conf->entity)) { print $db->error(); }
  }

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
if (empty($mysoc->tva_assuj))
{
	print $langs->trans("YourCompanyDoesNotUseVAT").'<br>';
}
else
{
	print '<table class="noborder" width="100%">';

	// Cas des parametres TAX_MODE_SELL/BUY_SERVICE/PRODUCT
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
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

	print '<br><br>';
	print_fiche_titre($langs->trans("SummaryOfVatExigibilityUsedByDefault"),'','');
	//print ' ('.$langs->trans("CanBeChangedWhenMakingInvoice").')';

	print '<table class="border" width="100%">';
	print '<tr><td>&nbsp;</td><td>'.$langs->trans("Buy").'</td><td>'.$langs->trans("Sell").'</td></tr>';

	// Products
	print '<tr><td>'.$langs->trans("Product").'</td>';
	print '<td>';
	print $langs->trans("OnDelivery");
	print ' ('.$langs->trans("SupposedToBeInvoiceDate").')';
	print '</td>';
	print '<td>';
	print $langs->trans("OnDelivery");
	print ' ('.$langs->trans("SupposedToBeInvoiceDate").')';
	print '</td></tr>';

	// Services
	print '<tr><td>'.$langs->trans("Services").'</td>';
	print '<td>';
    if ($tax_mode == 0)
    {
        print $langs->trans("OnPayment");
        print ' ('.$langs->trans("SupposedToBePaymentDate").')';
    }
    if ($tax_mode == 1)
    {
        print $langs->trans("OnInvoice");
        print ' ('.$langs->trans("InvoiceDateUsed").')';
    }
	print '</td>';
	print '<td>';
	if ($tax_mode == 0)
	{
		print $langs->trans("OnPayment");
		print ' ('.$langs->trans("SupposedToBePaymentDate").')';
	}
	if ($tax_mode == 1)
	{
		print $langs->trans("OnInvoice");
		print ' ('.$langs->trans("InvoiceDateUsed").')';
	}
	print '</td></tr>';

	print '</table>';
}

$db->close();

llxFooter('$Date: 2011/07/31 22:23:24 $ - $Revision: 1.10 $');
?>
