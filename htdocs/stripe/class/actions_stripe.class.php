<?php
/* Copyright (C) 2009-2016  Regis Houssin  			<regis.houssin@inodbox.com>
 * Copyright (C) 2011       Herve Prot     			<herve.prot@symeos.com>
 * Copyright (C) 2014       Philippe Grand 			<philippe.grand@atoo-net.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

// TODO File of hooks not used yet. To remove ?

/**
 *	\file       htdocs/stripe/class/actions_stripe.class.php
 *	\ingroup    stripe
 *	\brief      File Class actionsstripeconnect
 */

require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';


/**
 *	Class Actions Stripe Connect
 */
class ActionsStripeconnect extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	private $config = array(); // @phpstan-ignore-line


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * formObjectOptions
	 *
	 * @param	array			$parameters		Parameters
	 * @param	CommonObject	$object			Object
	 * @param	string			$action			Action
	 * @return int
	 */
	public function formObjectOptions($parameters, &$object, &$action)
	{
		global $conf, $langs;

		if (isModEnabled('stripe') && (!getDolGlobalString('STRIPE_LIVE') || GETPOST('forcesandbox', 'alpha'))) {
			$service = 'StripeTest';
			dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), [], 'warning');
		} else {
			$service = 'StripeLive';
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach ($parameters as $key => $value) {
				$key = $value;
			}
		}

		if (is_object($object) && $object->element == 'societe') {
			$this->resprints .= '<tr><td>';
			$this->resprints .= '<table width="100%" class="nobordernopadding"><tr><td>';
			$this->resprints .= $langs->trans('StripeCustomer');
			$this->resprints .= '<td><td class="right">';
			//				$this->resprints.= '<a class="editfielda" href="'.$dolibarr_main_url_root.dol_buildpath('/dolipress/card.php?socid='.$object->id, 1).'">'.img_edit().'</a>';
			$this->resprints .= '</td></tr></table>';
			$this->resprints .= '</td>';
			$this->resprints .= '<td colspan="3">';
			$stripe = new Stripe($this->db);
			if ($stripe->getStripeAccount($service) && $object->client != 0) {
				$customer = $stripe->customerStripe($object, $stripe->getStripeAccount($service));
				$this->resprints .= $customer->id;
			} else {
				$this->resprints .= $langs->trans("NoStripe");
			}
			$this->resprints .= '</td></tr>';
		} elseif ($object instanceof CommonObject && $object->element == 'member') {
			$this->resprints .= '<tr><td>';
			$this->resprints .= '<table width="100%" class="nobordernopadding"><tr><td>';
			$this->resprints .= $langs->trans('StripeCustomer');
			$this->resprints .= '<td><td class="right">';
			$this->resprints .= '</td></tr></table>';
			$this->resprints .= '</td>';
			$this->resprints .= '<td colspan="3">';
			$stripe = new Stripe($this->db);
			if ($stripe->getStripeAccount($service) && $object->fk_soc > 0) {
				$object->fetch_thirdparty();
				$customer = $stripe->customerStripe($object->thirdparty, $stripe->getStripeAccount($service));
				$this->resprints .= $customer->id;
			} else {
				$this->resprints .= $langs->trans("NoStripe");
			}
			$this->resprints .= '</td></tr>';

			$this->resprints .= '<tr><td>';
			$this->resprints .= '<table width="100%" class="nobordernopadding"><tr><td>';
			$this->resprints .= $langs->trans('SubscriptionStripe');
			$this->resprints .= '<td><td class="right">';
			$this->resprints .= '</td></tr></table>';
			$this->resprints .= '</td>';
			$this->resprints .= '<td colspan="3">';
			$stripe = new Stripe($this->db);
			if (7 == 4) {
				$object->fetch_thirdparty();
				$customer = $stripe->customerStripe($object, $stripe->getStripeAccount($service));
				$this->resprints .= $customer->id;
			} else {
				$this->resprints .= $langs->trans("NoStripe");
			}
			$this->resprints .= '</td></tr>';
		} elseif ($object instanceof CommonObject && $object->element == 'adherent_type') {
			$this->resprints .= '<tr><td>';
			$this->resprints .= '<table width="100%" class="nobordernopadding"><tr><td>';
			$this->resprints .= $langs->trans('PlanStripe');
			$this->resprints .= '<td><td class="right">';
			//				$this->resprints.= '<a class="editfielda" href="'.$dolibarr_main_url_root.dol_buildpath('/dolipress/card.php?socid='.$object->id, 1).'">'.img_edit().'</a>';
			$this->resprints .= '</td></tr></table>';
			$this->resprints .= '</td>';
			$this->resprints .= '<td colspan="3">';
			$stripe = new Stripe($this->db);
			if (7 == 4) {
				$object->fetch_thirdparty();
				$customer = $stripe->customerStripe($object, $stripe->getStripeAccount($service));
				$this->resprints .= $customer->id;
			} else {
				$this->resprints .= $langs->trans("NoStripe");
			}
			$this->resprints .= '</td></tr>';
		}
		return 0;
	}

	/**
	 * addMoreActionsButtons
	 *
	 * @param array	 	$parameters	Parameters
	 * @param Object	$object		Object
	 * @param string	$action		action
	 * @return int					0
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action)
	{
		global $conf, $langs;

		if (is_object($object) && $object->element == 'facture') {
			// Verify if the invoice has payments
			$sql = 'SELECT pf.amount';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
			$sql .= ' WHERE pf.fk_facture = '.((int) $object->id);

			$totalpaid = 0;

			$result = $this->db->query($sql);
			if ($result) {
				$i = 0;
				$num = $this->db->num_rows($result);

				while ($i < $num) {
					$objp = $this->db->fetch_object($result);
					$totalpaid += $objp->amount;
					$i++;
				}
			} else {
				dol_print_error($this->db, '');
			}

			$resteapayer = $object->total_ttc - $totalpaid;
			// Request a direct debit order
			if ($object->statut > Facture::STATUS_DRAFT && $object->statut < Facture::STATUS_ABANDONED && $object->paye == 0) {
				$stripe = new Stripe($this->db);
				if ($resteapayer > 0) {
					if ($stripe->getStripeAccount($conf->entity)) {  // a modifier avec droit stripe
						$langs->load("withdrawals");
						print '<a class="butActionDelete" href="'.dol_buildpath('/stripeconnect/payment.php?facid='.$object->id.'&action=create', 1).'" title="'.dol_escape_htmltag($langs->trans("StripeConnectPay")).'">'.$langs->trans("StripeConnectPay").'</a>';
					} else {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("StripeConnectPay").'</a>';
					}
				} elseif ($resteapayer == 0) {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("StripeConnectPay").'</a>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("StripeConnectPay").'</a>';
			}
		} elseif (is_object($object) && $object->element == 'invoice_supplier') {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("StripeConnectPay")).'">'.$langs->trans("StripeConnectPay").'</a>';
		} elseif (is_object($object) && $object->element == 'member') {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("StripeAutoSubscription")).'">'.$langs->trans("StripeAutoSubscription").'</a>';
		}
		return 0;
	}
}
