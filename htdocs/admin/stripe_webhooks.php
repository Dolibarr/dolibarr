<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@zendsi.com>
 * Copyright (C) 2017		Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 * Copyright (C) 2018		ptibogxiv				<support@ptibogxiv.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file       htdocs/admin/stripe.php
 * \ingroup    stripe
 * \brief      Page to setup stripe module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

$servicename='Stripe';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'paypal', 'paybox', 'stripe'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');

/*
 *	View
 */

llxHeader('',$langs->trans("StripeSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' Stripe',$linkback);

$head=stripeadmin_prepare_head();

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

dol_fiche_head($head, 'stripe_webhooks', '', -1);

print $langs->trans("StripeDesc")."<br>\n";

print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Status").'</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("StripeLiveEnabled").'</td><td>';
if (!empty($conf->global->STRIPE_LIVE))
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setlive&value=0">';
	print img_picto($langs->trans("Activated"),'switch_on');
	print '</a>';
}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setlive&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print '</a>';
}
print '</td><td></td></tr>';

$webhook = \Stripe\WebhookEndpoint::all();

foreach ($webhook->data as $webhook)
	{
  
		$url='https://dashboard.stripe.com/test/webhooks/'.$webhook->id;
		if ($webhook->livemode == true) {
			$url='https://dashboard.stripe.com/webhooks/'.$webhook->id;
		}  
  
	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired"><a href="'.$url.'" target="_stripe">' . img_picto($langs->trans('ShowInStripe'), 'object_globe'). ' ' . $webhook->id . '</a></span></td><td>';
	print $webhook->url.'</td>';
  
	// Status
	print "<td align='right'>";
  if ($webhook->status=='failed')
	{print img_picto($langs->trans("".$txn->status.""),'statut7');}
	elseif ($webhook->status=='enabled')
 	{print img_picto($langs->trans("".$txn->status.""),'statut4');}
	elseif ($webhook->status=='disabled')
	{print img_picto($langs->trans("".$txn->status.""),'statut6');}
	print '</td>';
	print '</tr>';  
  }
  
if (empty($conf->stripeconnect->enabled))
{
	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_LIVE_PUBLISHABLE_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': pk_live_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_SECRET_KEY" value="'.$conf->global->STRIPE_LIVE_SECRET_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': sk_live_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("STRIPE_LIVE_WEBHOOK_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_WEBHOOK_KEY" value="'.$conf->global->STRIPE_LIVE_WEBHOOK_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';
}
else
{
	print '<tr class="oddeven"><td>'.$langs->trans("StripeConnect").'</td>';
	print '<td>'.$langs->trans("StripeConnect_Mode").'</td></tr>';
}

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';

// End of page
llxFooter();
$db->close();
