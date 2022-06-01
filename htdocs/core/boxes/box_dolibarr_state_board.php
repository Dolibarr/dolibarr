<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2021 Frederic France      <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/boxes/box_dolibarr_state_board.php
 *	\ingroup
 *	\brief      Module Dolibarr state base
 */

include_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';


/**
 * Class to manage the box to show last thirdparties
 */
class box_dolibarr_state_board extends ModeleBoxes
{
	public $boxcode = "dolibarrstatebox";
	public $boximg = "generic";
	public $boxlabel = "BoxDolibarrStateBoard";
	public $depends = array("user");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $enabled = 1;

	public $info_box_head = array();
	public $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $conf, $user;

		$this->db = $db;
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;
		$langs->load("boxes");

		$this->max = $max;
		$this->info_box_head = array('text' => $langs->trans("DolibarrStateBoard"));

		if (empty($user->socid) && empty($conf->global->MAIN_DISABLE_GLOBAL_BOXSTATS)) {
			$hookmanager = new HookManager($this->db);
			$hookmanager->initHooks(array('index'));
			$object = new stdClass;
			$action = '';
			$hookmanager->executeHooks('addStatisticLine', array(), $object, $action);
			$boxstatItems = array();
			$boxstatFromHook = '';
			$boxstatFromHook = $hookmanager->resPrint;
			$boxstat = '';

			$keys = array(
				'users',
				'members',
				'expensereports',
				'holidays',
				'customers',
				'prospects',
				'suppliers',
				'contacts',
				'products',
				'services',
				'projects',
				'proposals',
				'orders',
				'invoices',
				'donations',
				'supplier_proposals',
				'supplier_orders',
				'supplier_invoices',
				'contracts',
				'interventions',
				'ticket'
			);
			$conditions = array(
				'users' => $user->rights->user->user->lire,
				'members' => !empty($conf->adherent->enabled) && $user->rights->adherent->lire,
				'customers' => !empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS),
				'prospects' => !empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS),
				'suppliers' => ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) && $user->rights->fournisseur->lire)
								 || (!empty($conf->supplier_order->enabled) && $user->rights->supplier_order->lire)
								 || (!empty($conf->supplier_invoice->enabled) && $user->rights->supplier_invoice->lire)
								 )
								 && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS),
				'contacts' => !empty($conf->societe->enabled) && $user->rights->societe->contact->lire,
				'products' => !empty($conf->product->enabled) && $user->rights->produit->lire,
				'services' => !empty($conf->service->enabled) && $user->rights->service->lire,
				'proposals' => !empty($conf->propal->enabled) && $user->rights->propale->lire,
				'orders' => !empty($conf->commande->enabled) && $user->rights->commande->lire,
				'invoices' => !empty($conf->facture->enabled) && $user->rights->facture->lire,
				'donations' => !empty($conf->don->enabled) && $user->rights->don->lire,
				'contracts' => !empty($conf->contrat->enabled) && $user->rights->contrat->lire,
				'interventions' => !empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire,
				'supplier_orders' => !empty($conf->supplier_order->enabled) && $user->rights->fournisseur->commande->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_ORDERS_STATS),
				'supplier_invoices' => !empty($conf->supplier_invoice->enabled) && $user->rights->fournisseur->facture->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_INVOICES_STATS),
				'supplier_proposals' => !empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_PROPOSAL_STATS),
				'projects' => !empty($conf->projet->enabled) && $user->rights->projet->lire,
				'expensereports' => !empty($conf->expensereport->enabled) && $user->rights->expensereport->lire,
				'holidays' => !empty($conf->holiday->enabled) && $user->rights->holiday->read,
				'ticket' => !empty($conf->ticket->enabled) && $user->rights->ticket->read
			);
			$classes = array(
				'users' => 'User',
				'members' => 'Adherent',
				'customers' => 'Client',
				'prospects' => 'Client',
				'suppliers' => 'Fournisseur',
				'contacts' => 'Contact',
				'products' => 'Product',
				'services' => 'ProductService',
				'proposals' => 'Propal',
				'orders' => 'Commande',
				'invoices' => 'Facture',
				'donations' => 'Don',
				'contracts' => 'Contrat',
				'interventions' => 'Fichinter',
				'supplier_orders' => 'CommandeFournisseur',
				'supplier_invoices' => 'FactureFournisseur',
				'supplier_proposals' => 'SupplierProposal',
				'projects' => 'Project',
				'expensereports' => 'ExpenseReport',
				'holidays' => 'Holiday',
				'ticket' => 'Ticket',
			);
			$includes = array(
				'users' => DOL_DOCUMENT_ROOT . "/user/class/user.class.php",
				'members' => DOL_DOCUMENT_ROOT . "/adherents/class/adherent.class.php",
				'customers' => DOL_DOCUMENT_ROOT . "/societe/class/client.class.php",
				'prospects' => DOL_DOCUMENT_ROOT . "/societe/class/client.class.php",
				'suppliers' => DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.class.php",
				'contacts' => DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php",
				'products' => DOL_DOCUMENT_ROOT . "/product/class/product.class.php",
				'services' => DOL_DOCUMENT_ROOT . "/product/class/product.class.php",
				'proposals' => DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php",
				'orders' => DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php",
				'invoices' => DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php",
				'donations' => DOL_DOCUMENT_ROOT . "/don/class/don.class.php",
				'contracts' => DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php",
				'interventions' => DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php",
				'supplier_orders' => DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.commande.class.php",
				'supplier_invoices' => DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.facture.class.php",
				'supplier_proposals' => DOL_DOCUMENT_ROOT . "/supplier_proposal/class/supplier_proposal.class.php",
				'projects' => DOL_DOCUMENT_ROOT . "/projet/class/project.class.php",
				'expensereports' => DOL_DOCUMENT_ROOT . "/expensereport/class/expensereport.class.php",
				'holidays' => DOL_DOCUMENT_ROOT . "/holiday/class/holiday.class.php",
				'ticket' => DOL_DOCUMENT_ROOT . "/ticket/class/ticket.class.php"
			);
			$links = array(
				'users' => DOL_URL_ROOT . '/user/list.php',
				'members' => DOL_URL_ROOT . '/adherents/list.php?statut=1&mainmenu=members',
				'customers' => DOL_URL_ROOT . '/societe/list.php?type=c&mainmenu=companies',
				'prospects' => DOL_URL_ROOT . '/societe/list.php?type=p&mainmenu=companies',
				'suppliers' => DOL_URL_ROOT . '/societe/list.php?type=f&mainmenu=companies',
				'contacts' => DOL_URL_ROOT . '/contact/list.php?mainmenu=companies',
				'products' => DOL_URL_ROOT . '/product/list.php?type=0&mainmenu=products',
				'services' => DOL_URL_ROOT . '/product/list.php?type=1&mainmenu=products',
				'proposals' => DOL_URL_ROOT . '/comm/propal/list.php?mainmenu=commercial&leftmenu=propals',
				'orders' => DOL_URL_ROOT . '/commande/list.php?mainmenu=commercial&leftmenu=orders',
				'invoices' => DOL_URL_ROOT . '/compta/facture/list.php?mainmenu=billing&leftmenu=customers_bills',
				'donations' => DOL_URL_ROOT . '/don/list.php?leftmenu=donations',
				'contracts' => DOL_URL_ROOT . '/contrat/list.php?mainmenu=commercial&leftmenu=contracts',
				'interventions' => DOL_URL_ROOT . '/fichinter/list.php?mainmenu=commercial&leftmenu=ficheinter',
				'supplier_orders' => DOL_URL_ROOT . '/fourn/commande/list.php?mainmenu=commercial&leftmenu=orders_suppliers',
				'supplier_invoices' => DOL_URL_ROOT . '/fourn/facture/list.php?mainmenu=billing&leftmenu=suppliers_bills',
				'supplier_proposals' => DOL_URL_ROOT . '/supplier_proposal/list.php?mainmenu=commercial&leftmenu=',
				'projects' => DOL_URL_ROOT . '/projet/list.php?mainmenu=project',
				'expensereports' => DOL_URL_ROOT . '/expensereport/list.php?mainmenu=hrm&leftmenu=expensereport',
				'holidays' => DOL_URL_ROOT . '/holiday/list.php?mainmenu=hrm&leftmenu=holiday',
				'ticket' => DOL_URL_ROOT . '/ticket/list.php?leftmenu=ticket'
			);
			$titres = array(
				'users' => "Users",
				'members' => "Members",
				'customers' => "ThirdPartyCustomersStats",
				'prospects' => "ThirdPartyProspectsStats",
				'suppliers' => "Suppliers",
				'contacts' => "Contacts",
				'products' => "Products",
				'services' => "Services",
				'proposals' => "CommercialProposalsShort",
				'orders' => "CustomersOrders",
				'invoices' => "BillsCustomers",
				'donations' => "Donations",
				'contracts' => "Contracts",
				'interventions' => "Interventions",
				'supplier_orders' => "SuppliersOrders",
				'supplier_invoices' => "SuppliersInvoices",
				'supplier_proposals' => "SupplierProposalShort",
				'projects' => "Projects",
				'expensereports' => "ExpenseReports",
				'holidays' => "Holidays",
				'ticket' => "Ticket",
			);
			$langfile = array(
				'customers' => "companies",
				'contacts' => "companies",
				'services' => "products",
				'proposals' => "propal",
				'invoices' => "bills",
				'supplier_orders' => "orders",
				'supplier_invoices' => "bills",
				'supplier_proposals' => 'supplier_proposal',
				'expensereports' => "trips",
				'holidays' => "holiday",
			);
			$boardloaded = array();

			foreach ($keys as $val) {
				if ($conditions[$val]) {
					$boxstatItem = '';
					$class = $classes[$val];
					// Search in cache if load_state_board is already realized
					$classkeyforcache = $class;
					if ($classkeyforcache == 'ProductService') {
						$classkeyforcache = 'Product'; // ProductService use same load_state_board than Product
					}

					if (!isset($boardloaded[$classkeyforcache]) || !is_object($boardloaded[$classkeyforcache])) {
						include_once $includes[$val]; // Loading a class cost around 1Mb

						$board = new $class($this->db);
						$board->load_state_board();
						$boardloaded[$class] = $board;
					} else {
						$board = $boardloaded[$classkeyforcache];
					}

					$langs->load(empty($langfile[$val]) ? $val : $langfile[$val]);

					$text = $langs->trans($titres[$val]);
					$boxstatItem .= '<a href="' . $links[$val] . '" class="boxstatsindicator thumbstat nobold nounderline">';
					$boxstatItem .= '<div class="boxstats">';
					$boxstatItem .= '<span class="boxstatstext" title="' . dol_escape_htmltag($text) . '">' . $text . '</span><br>';
					$boxstatItem .= '<span class="boxstatsindicator">' . img_object("", $board->picto, 'class="inline-block"') . ' ' . (!empty($board->nb[$val]) ? $board->nb[$val] : 0) . '</span>';
					$boxstatItem .= '</div>';
					$boxstatItem .= '</a>';

					$boxstatItems[$val] = $boxstatItem;
				}
			}

			if (!empty($boxstatFromHook) || !empty($boxstatItems)) {
				$boxstat .= $boxstatFromHook;

				if (is_array($boxstatItems) && count($boxstatItems) > 0) {
					$boxstat .= implode('', $boxstatItems);
				}

				$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
				$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
				$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
				$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
				$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
				$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
				$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
				$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';

				$this->info_box_contents[0][0] = array(
					'tr' => 'class="nohover"',
					'td' => '',
					'textnoformat' => $boxstat
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => '',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}


	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
