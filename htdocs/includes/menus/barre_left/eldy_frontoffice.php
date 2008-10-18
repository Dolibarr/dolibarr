<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file       htdocs/includes/menus/barre_left/eldy_frontoffice.php
		\brief      Gestionnaire du menu du gauche Eldy
		\version    $Id$
		
		\remarks    La construction d'un gestionnaire pour le menu de gauche est simple:
		\remarks    A l'aide d'un objet $newmenu=new Menu() et des méthode add et add_submenu,
		\remarks    définir la liste des entrées menu à faire apparaitre.
		\remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
		\remarks    Ce qui est défini dans un tel gestionnaire sera alors prioritaire sur
		\remarks    les définitions de menu des fichiers pre.inc.php
*/


/**
		\class      MenuLeft
		\brief      Classe permettant la gestion du menu du gauche Eldy
*/

class MenuLeft {

	var $require_top=array("eldy_frontoffice");     // Si doit etre en phase avec un gestionnaire de menu du haut particulier


	/**
	*    \brief      Constructeur
	*    \param      db            Handler d'accès base de donnée
	*    \param      menu_array    Tableau des entrée de menu défini dans les fichier pre.inc.php
	*/
	function MenuLeft($db,&$menu_array)
	{
    	$this->db=$db;
		$this->menu_array=$menu_array;
	}


	/**
	*    \brief      Affiche le menu
	*/
	function showmenu()
	{
		global $user,$conf,$langs,$dolibarr_main_db_name;

		// On récupère mainmenu et leftmenu qui définissent le menu à afficher
		if (isset($_GET["mainmenu"]))
		{
			// On sauve en session le menu principal choisi
			$mainmenu=$_GET["mainmenu"];
			$_SESSION["mainmenu"]=$mainmenu;
			$_SESSION["leftmenuopened"]="";
		}
		else
		{
			// On va le chercher en session si non défini par le lien
			$mainmenu=$_SESSION["mainmenu"];
		}

		if (isset($_GET["leftmenu"]))
		{
			// On sauve en session le menu principal choisi
			$leftmenu=$_GET["leftmenu"];
			$_SESSION["leftmenu"]=$leftmenu;
			if ($_SESSION["leftmenuopened"]==$leftmenu)
			{
				//$leftmenu="";
				$_SESSION["leftmenuopened"]="";
			}
			else
			{
				$_SESSION["leftmenuopened"]=$leftmenu;
			}
		} else {
			// On va le chercher en session si non défini par le lien
			$leftmenu=isset($_SESSION["leftmenu"])?$_SESSION["leftmenu"]:'';
		}


		$newmenu = new Menu();

		/**
		* On definit newmenu en fonction de mainmenu et leftmenu
		* ------------------------------------------------------
		*/
		if ($mainmenu)
		{

			/*
			* Menu HOME
			*/
			if ($mainmenu == 'home')
			{
				$langs->load("users");

				// My Informations
				$newmenu->add(DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'&amp;leftmenu=home', $langs->trans("MyInformations"));

				if ($user->admin)
				{
					$langs->load("admin");

					$newmenu->add(DOL_URL_ROOT."/admin/index.php?leftmenu=setup", $langs->trans("Setup"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/company.php", $langs->trans("MenuCompanySetup"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/menus.php", $langs->trans("Menus"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/delais.php",$langs->trans("Alerts"));

					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/perms.php", $langs->trans("Security"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/mails.php", $langs->trans("Emails"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/limits.php", $langs->trans("Limits"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"));
					if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"));

					$newmenu->add(DOL_URL_ROOT."/admin/system/index.php?leftmenu=system", $langs->trans("SystemInfo"));
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/dolibarr.php", $langs->trans("Dolibarr"),1);
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/constall.php", $langs->trans("AllParameters"),2);
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/modules.php", $langs->trans("Modules"),2);
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/triggers.php", $langs->trans("Triggers"),2);
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/about.php", $langs->trans("About"),2);
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/os.php", $langs->trans("OS"));
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/web.php", $langs->trans("WebServer"));
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/phpinfo.php", $langs->trans("Php"));
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/database.php", $langs->trans("Database"));
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/database-tables.php", $langs->trans("Tables"),2);
					if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/database-tables-contraintes.php", $langs->trans("Constraints"),2);

					$newmenu->add(DOL_URL_ROOT."/admin/tools/index.php?leftmenu=admintools", $langs->trans("SystemTools"));
					if ($leftmenu=="admintools") $newmenu->add_submenu(DOL_URL_ROOT."/admin/tools/dolibarr_export.php", $langs->trans("Backup"),1);
					if ($leftmenu=="admintools") $newmenu->add_submenu(DOL_URL_ROOT."/admin/tools/dolibarr_import.php", $langs->trans("Restore"),1);
					if ($leftmenu=="admintools") $newmenu->add_submenu(DOL_URL_ROOT."/admin/tools/update.php", $langs->trans("Upgrade"),1);
					if ($leftmenu=="admintools") $newmenu->add_submenu(DOL_URL_ROOT."/admin/tools/purge.php", $langs->trans("Purge"),1);
					if ($leftmenu=="admintools") $newmenu->add_submenu(DOL_URL_ROOT."/admin/tools/listevents.php", $langs->trans("Audit"),1);
					if ($leftmenu=="admintools" && function_exists('eaccelerator_info')) $newmenu->add_submenu(DOL_URL_ROOT."/admin/tools/eaccelerator.php", $langs->trans("EAccelerator"),1);
				}

				/*
				$langs->load("users");
				$newmenu->add(DOL_URL_ROOT."/user/home.php?leftmenu=users", $langs->trans("MenuUsersAndGroups"));
				if ($leftmenu=="users") $newmenu->add_submenu(DOL_URL_ROOT."/user/index.php", $langs->trans("Users"), 1, $user->rights->user->user->lire || $user->admin);
				if ($leftmenu=="users") $newmenu->add_submenu(DOL_URL_ROOT."/user/fiche.php?action=create", $langs->trans("NewUser"),2, $user->rights->user->user->creer || $user->admin);
				if ($leftmenu=="users") $newmenu->add_submenu(DOL_URL_ROOT."/user/group/index.php", $langs->trans("Groups"), 1, $user->rights->user->user->lire || $user->admin);
				if ($leftmenu=="users") $newmenu->add_submenu(DOL_URL_ROOT."/user/group/fiche.php?action=create", $langs->trans("NewGroup"), 2, $user->rights->user->user->creer || $user->admin);
				*/
			}

			/*
			* Menu TIERS
			*/
			if ($mainmenu == 'companies')
			{
				// Sociétés
			    if ($conf->societe->enabled)
			    {
			        $langs->load("companies");
			        $newmenu->add(DOL_URL_ROOT."/societe.php", $langs->trans("ThirdParty"), 0, $user->rights->societe->lire);
			
			        if ($user->rights->societe->creer)
			        {
			            $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?action=create", $langs->trans("MenuNewThirdParty"));
			        }
			
			        if(is_dir("societe/groupe"))
			        {
			            $newmenu->add_submenu(DOL_URL_ROOT."/societe/groupe/index.php", $langs->trans("MenuSocGroup"));
			        }
			    }

				// Fournisseurs
				$langs->load("suppliers");

				if ($conf->societe->enabled && $conf->fournisseur->enabled)
				{
					$newmenu->add(DOL_URL_ROOT."/fourn/index.php?leftmenu=suppliers", $langs->trans("Suppliers"), 1, $user->rights->societe->lire && $user->rights->fournisseur->lire);

					// Sécurité accés client
					if ($user->societe_id == 0)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f",$langs->trans("NewSupplier"), 2, $user->rights->societe->creer && $user->rights->fournisseur->lire);
					}
					$newmenu->add_submenu(DOL_URL_ROOT."/fourn/liste.php?leftmenu=suppliers", $langs->trans("List"), 2, $user->rights->societe->lire && $user->rights->fournisseur->lire);
					//$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=suppliers&amp;type=f",$langs->trans("Contacts"), 2, $user->rights->societe->lire && $user->rights->fournisseur->lire && $user->rights->societe->contact->lire);
			    	$newmenu->add_submenu(DOL_URL_ROOT."/fourn/stats.php",$langs->trans("Statistics"), 2, $user->rights->societe->lire && $user->rights->fournisseur->lire);
				}

				// Prospects
			    if ($conf->societe->enabled)
			    {
					$langs->load("commercial");
					$newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?leftmenu=prospects", $langs->trans("Prospects"), 2, $user->rights->societe->lire);

					$newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=prospects&amp;action=create&amp;type=p", $langs->trans("MenuNewProspect"), 2, $user->rights->societe->creer);
					//$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&amp;type=p", $langs->trans("Contacts"), 2, $user->rights->societe->contact->lire);
				}
				
				// Clients
			    if ($conf->societe->enabled)
			    {
					$langs->load("commercial");
					$newmenu->add(DOL_URL_ROOT."/comm/clients.php?leftmenu=customers", $langs->trans("Customers"), 1, $user->rights->societe->lire);

					$newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=customers&amp;action=create&amp;type=c", $langs->trans("MenuNewCustomer"), 2, $user->rights->societe->creer);
					//$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&amp;type=c", $langs->trans("Contacts"), 2, $user->rights->societe->contact->lire);
				}

				// Contacts
				$newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts", $langs->trans("Contacts"), 0, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/fiche.php?leftmenu=contacts&amp;action=create", $langs->trans("NewContact"), 1, $user->rights->societe->contact->creer);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts", $langs->trans("List"), 1, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=p", $langs->trans("Prospects"), 2, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=c", $langs->trans("Customers"), 2, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=f", $langs->trans("Suppliers"), 2, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=o", $langs->trans("Others"), 2, $user->rights->societe->contact->lire);
				//$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?userid=$user->id", $langs->trans("MyContacts"), 1, $user->rights->societe->contact->lire);
			}

			/*
			* Menu COMMERCIAL
			*/
			if ($mainmenu == 'commercial')
			{
				$langs->load("companies");

				// Prospects
				/*
				$newmenu->add(DOL_URL_ROOT."/comm/prospect/index.php?leftmenu=prospects", $langs->trans("Prospects"), 0, $user->rights->societe->lire);

				$newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=prospects&amp;action=create&amp;type=p", $langs->trans("MenuNewProspect"), 1, $user->rights->societe->creer);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=prospects&amp;type=p", $langs->trans("List"), 1, $user->rights->societe->contact->lire);

				if ($leftmenu=="prospects") $newmenu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=-1", $langs->trans("LastProspectDoNotContact"), 2, $user->rights->societe->lire);
				if ($leftmenu=="prospects") $newmenu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=0", $langs->trans("LastProspectNeverContacted"), 2, $user->rights->societe->lire);
				if ($leftmenu=="prospects") $newmenu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=1", $langs->trans("LastProspectToContact"), 2, $user->rights->societe->lire);
				if ($leftmenu=="prospects") $newmenu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=2", $langs->trans("LastProspectContactInProcess"), 2, $user->rights->societe->lire);
				if ($leftmenu=="prospects") $newmenu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=3", $langs->trans("LastProspectContactDone"), 2, $user->rights->societe->lire);

				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=prospects&amp;type=p", $langs->trans("Contacts"), 1, $user->rights->societe->contact->lire);
				*/
				// Clients
				/*
				$newmenu->add(DOL_URL_ROOT."/comm/index.php?leftmenu=customers", $langs->trans("Customers"), 0, $user->rights->societe->lire);

				$newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=customers&amp;action=create&amp;type=c", $langs->trans("MenuNewCustomer"), 1, $user->rights->societe->creer);
				$newmenu->add_submenu(DOL_URL_ROOT."/comm/clients.php?leftmenu=customers", $langs->trans("List"), 1, $user->rights->societe->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&amp;type=c", $langs->trans("Contacts"), 1, $user->rights->societe->contact->lire);
				*/
				// Contacts
				/*
				$newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts", $langs->trans("Contacts"), 0, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/fiche.php?leftmenu=contacts&amp;action=create", $langs->trans("NewContact"), 1, $user->rights->societe->contact->creer);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts", $langs->trans("List"), 1, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=p", $langs->trans("Prospects"), 2, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=c", $langs->trans("Customers"), 2, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=f", $langs->trans("Suppliers"), 2, $user->rights->societe->contact->lire);
				$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=o", $langs->trans("Others"), 2, $user->rights->societe->contact->lire);
				*/
				
				// Propal
				if ($conf->propal->enabled)
				{
					$langs->load("propal");
					$newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals", $langs->trans("Prop"), 0 ,$user->rights->propale->lire);
					if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=propals", $langs->trans("NewPropal"), 1, $user->rights->propale->creer);
					if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals", $langs->trans("List"), 1, $user->rights->propale->lire);
					if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=0", $langs->trans("PropalsDraft"), 2, $user->rights->propale->lire);
					if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=1", $langs->trans("PropalsOpened"), 2, $user->rights->propale->lire);
					if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=2", $langs->trans("PropalStatusSigned"), 2, $user->rights->propale->lire);
					if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=3", $langs->trans("PropalStatusNotSigned"), 2, $user->rights->propale->lire);
					if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=4", $langs->trans("PropalStatusBilled"), 2, $user->rights->propale->lire);
					//if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=2,3,4", $langs->trans("PropalStatusClosedShort"), 2, $user->rights->propale->lire);
					if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal/stats/index.php?leftmenu=propals", $langs->trans("Statistics"), 1, $user->rights->propale->lire);
				}

				// Customers orders
				if ($conf->commande->enabled )
				{
					$langs->load("orders");
					$newmenu->add(DOL_URL_ROOT."/commande/index.php?leftmenu=orders", $langs->trans("CustomersOrders"), 0 ,$user->rights->commande->lire);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=orders", $langs->trans("NewOrder"), 1, $user->rights->commande->creer);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders", $langs->trans("List"), 1, $user->rights->commande->lire);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=0", $langs->trans("StatusOrderDraftShort"), 2, $user->rights->commande->lire);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=1", $langs->trans("StatusOrderValidated"), 2, $user->rights->commande->lire);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=2", $langs->trans("StatusOrderOnProcessShort"), 2, $user->rights->commande->lire);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=3", $langs->trans("StatusOrderToBill"), 2, $user->rights->commande->lire);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=4", $langs->trans("StatusOrderProcessed"), 2, $user->rights->commande->lire);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=-1", $langs->trans("StatusOrderCanceledShort"), 2, $user->rights->commande->lire);
					if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/stats/index.php?leftmenu=orders", $langs->trans("Statistics"), 1 ,$user->rights->commande->lire);
				}

				// Suppliers orders
				if ($conf->fournisseur->enabled)
				{
					$langs->load("orders");
					$newmenu->add(DOL_URL_ROOT."/fourn/commande/index.php?leftmenu=orders_suppliers",$langs->trans("SuppliersOrders"), 0, $user->rights->fournisseur->commande->lire);
					if ($leftmenu=="orders_suppliers") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=orders_suppliers", $langs->trans("NewOrder"), 1, $user->rights->fournisseur->commande->creer);
					if ($leftmenu=="orders_suppliers") $newmenu->add_submenu(DOL_URL_ROOT."/fourn/commande/liste.php?leftmenu=orders_suppliers", $langs->trans("List"), 1, $user->rights->fournisseur->commande->lire);
					if ($leftmenu=="orders_suppliers") $newmenu->add_submenu(DOL_URL_ROOT."/commande/stats/index.php?leftmenu=orders_suppliers&amp;mode=supplier", $langs->trans("Statistics"), 1 ,$user->rights->fournisseur->commande->lire);
				}

				// Contrat
				if ($conf->contrat->enabled)
				{
					$langs->load("contracts");
					$newmenu->add(DOL_URL_ROOT."/contrat/index.php?leftmenu=contracts", $langs->trans("Contracts"), 0 ,$user->rights->contrat->lire);
					if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=contracts", $langs->trans("NewContract"), 1, $user->rights->contrat->creer);
					if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/liste.php?leftmenu=contracts", $langs->trans("List"), 1 ,$user->rights->contrat->lire);
					if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts", $langs->trans("MenuServices"), 1 ,$user->rights->contrat->lire);
					if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&amp;mode=0", $langs->trans("MenuInactiveServices"), 2 ,$user->rights->contrat->lire);
					if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&amp;mode=4", $langs->trans("MenuRunningServices"), 2 ,$user->rights->contrat->lire);
					if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&amp;mode=4&amp;filter=expired", $langs->trans("MenuExpiredServices"), 2 ,$user->rights->contrat->lire);
					if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&amp;mode=5", $langs->trans("MenuClosedServices"), 2 ,$user->rights->contrat->lire);
				}

				// Interventions
				if ($conf->fichinter->enabled )
				{
					$langs->load("interventions");
					$newmenu->add(DOL_URL_ROOT."/fichinter/index.php?leftmenu=ficheinter", $langs->trans("Interventions"), 0, $user->rights->ficheinter->lire);
					if ($leftmenu=="ficheinter") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=ficheinter", $langs->trans("NewIntervention"), 1, $user->rights->ficheinter->creer);
					if ($leftmenu=="ficheinter") $newmenu->add_submenu(DOL_URL_ROOT."/fichinter/index.php?leftmenu=ficheinter", $langs->trans("List"), 1 ,$user->rights->ficheinter->lire);
				}

			}


			/*
			* Menu COMPTA-FINANCIAL
			*/
			if ($mainmenu == 'accountancy')
			{
				$langs->load("companies");

				// Fournisseurs
				if ($conf->societe->enabled && $conf->fournisseur->enabled)
				{
					$langs->load("suppliers");
					$newmenu->add_submenu(DOL_URL_ROOT."/compta/index.php?leftmenu=suppliers", $langs->trans("Suppliers"),0,$user->rights->societe->lire && $user->rights->fournisseur->lire);

					// Sécurité accés client
					if ($user->societe_id == 0)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f",$langs->trans("NewSupplier"),1,$user->rights->societe->creer && $user->rights->fournisseur->lire);
					}
					$newmenu->add_submenu(DOL_URL_ROOT."/fourn/liste.php?leftmenu=suppliers", $langs->trans("List"),1,$user->rights->societe->lire && $user->rights->fournisseur->lire);
					if ($conf->societe->enabled)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=suppliers&amp;type=f",$langs->trans("Contacts"),1,$user->rights->societe->contact->lire && $user->rights->fournisseur->lire);
					}
					if ($conf->facture->enabled)
					{
						$langs->load("bills");
						$newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/index.php?leftmenu=suppliers_bills", $langs->trans("BillsSuppliers"),1,$user->rights->fournisseur->facture->lire);
						if ($user->societe_id == 0)
						{
							if ($leftmenu=="suppliers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"),2,$user->rights->fournisseur->facture->creer);
						}
						if ($leftmenu=="suppliers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/impayees.php", $langs->trans("Unpayed"),2,$user->rights->fournisseur->facture->lire);
						if ($leftmenu=="suppliers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"),2,$user->rights->fournisseur->facture->lire);

						if ($leftmenu=="suppliers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/stats/index.php?leftmenu=suppliers_bills&mode=supplier", $langs->trans("Statistics"),2,$user->rights->fournisseur->facture->lire);
					}
				}

				// Customers
				if ($conf->societe->enabled)
				{
					$newmenu->add(DOL_URL_ROOT."/compta/index.php?leftmenu=customers", $langs->trans("Customers"),0,$user->rights->societe->lire);
					if ($user->societe_id == 0)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=customers&amp;action=create&amp;type=c", $langs->trans("MenuNewCustomer"),1,$user->rights->societe->creer);
					}
					$newmenu->add(DOL_URL_ROOT."/compta/clients.php?leftmenu=customers", $langs->trans("List"),1,$user->rights->societe->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&amp;type=c", $langs->trans("Contacts"),1,$user->rights->societe->contact->lire);
				}

				// Invoices
				if ($conf->facture->enabled)
				{
					$langs->load("bills");
					$newmenu->add(DOL_URL_ROOT."/compta/facture.php?leftmenu=customers_bills",$langs->trans("BillsCustomers"),1,$user->rights->facture->lire);
					if ($user->societe_id == 0)
					{
						if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/clients.php?action=facturer&amp;leftmenu=customers_bills",$langs->trans("NewBill"),2,$user->rights->facture->creer);
					}
					if (! $conf->global->FACTURE_DISABLE_RECUR)
					{
						if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/fiche-rec.php?leftmenu=customers_bills",$langs->trans("Repeatable"),2,$user->rights->facture->lire);
					}
					if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/impayees.php?leftmenu=customers_bills",$langs->trans("Unpayed"),2,$user->rights->facture->lire);

					if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/liste.php?leftmenu=customers_bills_payments",$langs->trans("Payments"),2,$user->rights->facture->lire);

					if (eregi("customers_bills_payments",$leftmenu))  $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/avalider.php?leftmenu=customers_bills_payments",$langs->trans("MenuToValid"),3,$user->rights->facture->lire);
					if (eregi("customers_bills_payments",$leftmenu))  $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/rapport.php?leftmenu=customers_bills_payments",$langs->trans("Reportings"),3,$user->rights->facture->lire);

					if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/stats/index.php?leftmenu=customers_bills", $langs->trans("Statistics"),2,$user->rights->facture->lire);
				}
				
				// Proposals
				if ($conf->propal->enabled)
				{
					$langs->load("propal");
					$newmenu->add(DOL_URL_ROOT."/compta/propal.php",$langs->trans("Prop"),0,$user->rights->propale->lire);
				}

				// Orders
				if ($conf->commande->enabled)
				{
					$langs->load("orders");
					if ($conf->facture->enabled) $newmenu->add(DOL_URL_ROOT."/compta/commande/liste.php?leftmenu=orders&amp;status=3&amp;afacturer=1", $langs->trans("MenuOrdersToBill"), 0, $user->rights->commande->lire);
					//                  if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/", $langs->trans("StatusOrderToBill"), 1 ,$user->rights->commande->lire);
				}

				// Donations
				if ($conf->don->enabled)
				{
					$langs->load("donations");
					$newmenu->add(DOL_URL_ROOT."/compta/dons/index.php?leftmenu=donations&amp;mainmenu=accountancy",$langs->trans("Donations"), 0, $user->rights->don->lire);
					if ($leftmenu=="donations") $newmenu->add_submenu(DOL_URL_ROOT."/compta/dons/fiche.php?action=create",$langs->trans("NewDonation"), 1, $user->rights->don->creer);
					if ($leftmenu=="donations") $newmenu->add_submenu(DOL_URL_ROOT."/compta/dons/liste.php",$langs->trans("List"), 1, $user->rights->don->lire);
					if ($leftmenu=="donations") $newmenu->add_submenu(DOL_URL_ROOT."/compta/dons/stats.php",$langs->trans("Statistics"), 1, $user->rights->don->lire);
				}

				// Déplacements
				if ($conf->deplacement->enabled)
				{
					$langs->load("trips");
					$newmenu->add(DOL_URL_ROOT."/compta/deplacement/index.php?leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("TripsAndExpenses"), 0, $user->rights->deplacement->lire);
					if ($leftmenu=="tripsandexpenses") $newmenu->add(DOL_URL_ROOT."/compta/deplacement/fiche.php?action=create&amp;leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("New"), 1, $user->rights->deplacement->creer);
					if ($leftmenu=="tripsandexpenses") $newmenu->add(DOL_URL_ROOT."/compta/deplacement/index.php?leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("List"), 1, $user->rights->deplacement->lire);
				}

				// Taxes and social contributions
				if ($conf->tax->enabled)
				{
					$newmenu->add(DOL_URL_ROOT."/compta/charges/index.php?leftmenu=tax&amp;mainmenu=accountancy",$langs->trans("MenuTaxAndDividends"), 0, $user->rights->tax->charges->lire);
					if (eregi('^tax',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/charges/index.php?leftmenu=tax_social&amp;mainmenu=accountancy&amp;mode=sconly",$langs->trans("MenuSocialContributions"),1,$user->rights->tax->charges->lire);
					if (eregi('^tax',$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/sociales/charges.php?leftmenu=tax_social&action=create",$langs->trans("MenuNewSocialContribution"), 2, $user->rights->tax->charges->creer);
					if (eregi('^tax',$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/sociales/index.php?leftmenu=tax_social",$langs->trans("List"), 2, $user->rights->tax->charges->lire);
					// VAT
					if ($conf->compta->tva)
					{
						if (eregi('^tax',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/tva/index.php?leftmenu=tax_vat&amp;mainmenu=accountancy",$langs->trans("VAT"),1,$user->rights->tax->charges->lire);
						if (eregi('^tax',$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/fiche.php?leftmenu=tax_vat&action=create",$langs->trans("NewPayment"),2,$user->rights->tax->charges->creer);
						if (eregi('^tax',$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/reglement.php?leftmenu=tax_vat",$langs->trans("List"),2,$user->rights->tax->charges->lire);
						if (eregi('^tax',$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/clients.php?leftmenu=tax_vat", $langs->trans("ReportByCustomers"), 2, $user->rights->tax->charges->lire);
						if (eregi('^tax',$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/quadri_detail.php?leftmenu=tax_vat", $langs->trans("ReportByQuarter"), 2, $user->rights->tax->charges->lire);
					}
				}

				// Compta simple
				/*
				if ($conf->compta->enabled)
				{
					$newmenu->add(DOL_URL_ROOT."/compta/ventilation/index.php?leftmenu=ventil",$langs->trans("Ventilation"),0,$user->rights->compta->ventilation->lire);
					if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/ventilation/liste.php",$langs->trans("A ventiler"),1,$user->rights->compta->ventilation->lire);
					if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/ventilation/lignes.php",$langs->trans("Ventilées"),1,$user->rights->compta->ventilation->lire);
					if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/param/",$langs->trans("Setup"),1,$user->rights->compta->ventilation->parametrer);
					if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/param/comptes/fiche.php?action=create",$langs->trans("New"),2,$user->rights->compta->ventilation->parametrer);
					if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/param/comptes/liste.php",$langs->trans("List"),2,$user->rights->compta->ventilation->parametrer);
					if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/export/",$langs->trans("Export"),1,$user->rights->compta->ventilation->lire);
					if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/export/index.php?action=export",$langs->trans("New"),2,$user->rights->compta->ventilation->lire);
					if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/export/liste.php",$langs->trans("List"),2,$user->rights->compta->ventilation->lire);
				}

				// Compta expert
				if ($conf->comptaexpert->enabled)
				{

				}

				*/
				// Prélèvements
				if ($conf->prelevement->enabled)
				{
					$langs->load("withdrawals");
					$langs->load("banks");

					$newmenu->add(DOL_URL_ROOT."/compta/prelevement/index.php?leftmenu=withdraw",$langs->trans("StandingOrders"),0,$user->rights->prelevement->bons->lire);

					//if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandes.php",$langs->trans("StandingOrder"),1,$user->rights->prelevement->bons->lire);
					if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandes.php?status=0",$langs->trans("StandingOrderToProcess"),1,$user->rights->prelevement->bons->lire);
					//if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandes.php?status=1",$langs->trans("StandingOrderProcessed"),2,$user->rights->prelevement->bons->lire);

					if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/create.php",$langs->trans("NewStandingOrder"),1,$user->rights->prelevement->bons->creer);

					if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/bons.php",$langs->trans("WithdrawalsReceipts"),1,$user->rights->prelevement->bons->lire);
					if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/liste.php",$langs->trans("WithdrawalsLines"),1,$user->rights->prelevement->bons->lire);
					if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/liste_factures.php",$langs->trans("WithdrawedBills"),1,$user->rights->prelevement->bons->lire);
					if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/rejets.php",$langs->trans("Rejects"),1,$user->rights->prelevement->bons->lire);
					if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/stats.php",$langs->trans("Statistics"),1,$user->rights->prelevement->bons->lire);

					if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/config.php",$langs->trans("Setup"),1,$user->rights->prelevement->bons->configurer);
				}

				// Gestion cheques
/*
				if ($conf->facture->enabled && $conf->banque->enabled)
				{
					$newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/cheque/index.php?leftmenu=checks",$langs->trans("MenuChequeDeposits"),0,$user->rights->banque->cheque);
					if (eregi("checks",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/cheque/fiche.php?leftmenu=checks&amp;action=new",$langs->trans("NewChequeDeposit"),1,$user->rights->banque->cheque);
					if (eregi("checks",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/cheque/liste.php?leftmenu=checks",$langs->trans("MenuChequesReceipts"),1,$user->rights->banque->cheque);
				}
*/

				// Bank-Caisse
				/*
				if ($conf->banque->enabled)
				{
					$langs->load("banks");
					$newmenu->add(DOL_URL_ROOT."/compta/bank/index.php?leftmenu=bank&amp;mainmenu=bank",$langs->trans("MenuBankCash"),0,$user->rights->banque->lire);
				}
				*/
				// Rapports
				/*
				if ($conf->compta->enabled || $conf->comptaexpert->enabled)
				{
					// Bilan, résultats
					$newmenu->add(DOL_URL_ROOT."/compta/resultat/index.php?leftmenu=ca&amp;mainmenu=accountancy",$langs->trans("Reportings"),0,$user->rights->compta->resultat->lire||$user->rights->comptaexpert->comptarapport->lire);

					if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/resultat/index.php?leftmenu=ca","Résultat / Exercice",1,$user->rights->compta->resultat->lire||$user->rights->comptaexpert->comptarapport->lire);
					if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/resultat/clientfourn.php?leftmenu=ca",$langs->trans("ByCompanies"),2,$user->rights->compta->resultat->lire||$user->rights->comptaexpert->comptarapport->lire);

					
					if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/index.php?leftmenu=ca","Chiffre d'affaire",1,$user->rights->compta->resultat->lire||$user->rights->comptaexpert->comptarapport->lire);


					if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/casoc.php?leftmenu=ca",$langs->trans("ByCompanies"),2,$user->rights->compta->resultat->lire||$user->rights->comptaexpert->comptarapport->lire);
					if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/cabyuser.php?leftmenu=ca",$langs->trans("ByUsers"),2,$user->rights->compta->resultat->lire||$user->rights->comptaexpert->comptarapport->lire);
				}
				*/

			}


			/*
			* Menu PRODUITS-SERVICES
			*/
			if ($mainmenu == 'products')
			{
				// Products
				if ($conf->produit->enabled)
				{
					$newmenu->add(DOL_URL_ROOT."/product/index.php?leftmenu=product&amp;type=0", $langs->trans("Products"), 0, $user->rights->produit->lire);
					if ($user->societe_id == 0)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/product/fiche.php?leftmenu=product&amp;action=create&amp;type=0", $langs->trans("NewProduct"), 1, $user->rights->produit->creer);
						$newmenu->add_submenu(DOL_URL_ROOT."/product/liste.php?leftmenu=product&amp;type=0", $langs->trans("List"), 1, $user->rights->produit->lire);
						if (! empty($conf->global->PRODUIT_SPECIAL_LIVRE) && ! empty($conf->global->PRODUCT_CANVAS_ABILITY))
						{
							$newmenu->add_submenu(DOL_URL_ROOT."/product/fiche.php?leftmenu=product&amp;action=create&amp;type=0&canvas=livre", $langs->trans("NewBook"), 1, $user->rights->produit->creer);
							$newmenu->add_submenu(DOL_URL_ROOT."/product/liste.php?leftmenu=product&amp;type=0&amp;canvas=livre", $langs->trans("BookList"), 1, $user->rights->produit->creer);
						}
					}
					if ($conf->stock->enabled)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/product/reassort.php?type=0", $langs->trans("Stocks"), 1, $user->rights->stock->lire);
					}
				}

				// Services
				if ($conf->service->enabled)
				{
					$newmenu->add(DOL_URL_ROOT."/product/index.php?leftmenu=service&amp;type=1", $langs->trans("Services"), 0, $user->rights->produit->lire);
					if ($user->societe_id == 0)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/product/fiche.php?leftmenu=service&amp;action=create&amp;type=1", $langs->trans("NewService"), 1, $user->rights->produit->creer);
					}
					$newmenu->add_submenu(DOL_URL_ROOT."/product/liste.php?leftmenu=service&amp;type=1", $langs->trans("List"), 1, $user->rights->produit->lire);
				}

				// Categories
				if ($conf->categorie->enabled)
				{
					$langs->load("categories");
					$newmenu->add(DOL_URL_ROOT."/categories/index.php?leftmenu=cat&amp;type=0", $langs->trans("Categories"), 0, $user->rights->categorie->lire);
					if ($user->societe_id == 0)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=0", $langs->trans("NewCat"), 1, $user->rights->categorie->creer);
					}
					//if ($leftmenu=="cat") $newmenu->add_submenu(DOL_URL_ROOT."/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
				}

				// Statistics
				$newmenu->add(DOL_URL_ROOT."/product/stats/index.php?leftmenu=stats", $langs->trans("Statistics"), 0, $user->rights->produit->lire);
				if ($conf->propal->enabled)
				{
					$newmenu->add_submenu(DOL_URL_ROOT."/product/popuprop.php?leftmenu=stats", $langs->trans("Popularity"), 1, $user->rights->propale->lire);
				}

				// Stocks
				if ($conf->stock->enabled)
				{
					$langs->load("stocks");
					$newmenu->add(DOL_URL_ROOT."/product/stock/index.php?leftmenu=stock", $langs->trans("Stock"), 0, $user->rights->stock->lire);
					if ($leftmenu=="stock") $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/fiche.php?action=create", $langs->trans("MenuNewWarehouse"), 1, $user->rights->stock->creer);
					if ($leftmenu=="stock") $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/liste.php", $langs->trans("List"), 1, $user->rights->stock->lire);
					if ($leftmenu=="stock") $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/valo.php", $langs->trans("EnhancedValue"), 1, $user->rights->stock->lire);
					if ($leftmenu=="stock") $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/mouvement.php", $langs->trans("Movements"), 1, $user->rights->stock->mouvement->lire);
				}

				// Expeditions
				if ($conf->expedition->enabled) 
				{
					$langs->load("sendings");
					$newmenu->add(DOL_URL_ROOT."/expedition/index.php?leftmenu=sendings", $langs->trans("Sendings"), 0, $user->rights->expedition->lire);
					if ($leftmenu=="sendings") $newmenu->add_submenu(DOL_URL_ROOT."/expedition/liste.php?leftmenu=sendings", $langs->trans("List"), 1 ,$user->rights->expedition->lire);
					if ($leftmenu=="sendings") $newmenu->add_submenu(DOL_URL_ROOT."/expedition/stats/index.php?leftmenu=sendings", $langs->trans("Statistics"), 1 ,$user->rights->expedition->lire);
				}

			}


			/*
			* Menu FOURNISSEURS
			*/
			if ($mainmenu == 'suppliers')
			{
				$langs->load("suppliers");

				if ($conf->societe->enabled && $conf->fournisseur->enabled)
				{
					$newmenu->add(DOL_URL_ROOT."/fourn/index.php?leftmenu=suppliers", $langs->trans("Suppliers"), 0, $user->rights->societe->lire && $user->rights->fournisseur->lire);

					// Sécurité accés client
					if ($user->societe_id == 0)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f",$langs->trans("NewSupplier"), 1, $user->rights->societe->creer && $user->rights->fournisseur->lire);
					}
				    $newmenu->add_submenu(DOL_URL_ROOT."/fourn/liste.php",$langs->trans("List"), 1, $user->rights->societe->lire && $user->rights->fournisseur->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=suppliers&amp;type=f",$langs->trans("Contacts"), 1, $user->rights->societe->contact->lire && $user->rights->fournisseur->lire);
			    	$newmenu->add_submenu(DOL_URL_ROOT."/fourn/stats.php",$langs->trans("Statistics"), 1, $user->rights->societe->lire && $user->rights->fournisseur->lire);
				}

				if ($conf->facture->enabled)
				{
					$langs->load("bills");
					$newmenu->add(DOL_URL_ROOT."/fourn/facture/index.php", $langs->trans("Bills"), 0, $user->rights->fournisseur->facture->lire);

					if ($user->societe_id == 0)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"), 1, $user->rights->fournisseur->facture->creer);
					}

					$newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"), 1, $user->rights->fournisseur->facture->lire);
				}

				if ($conf->fournisseur->enabled)
				{
					$langs->load("orders");
					$newmenu->add(DOL_URL_ROOT."/fourn/commande/index.php?leftmenu=suppliers",$langs->trans("Orders"), 0, $user->rights->fournisseur->commande->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=supplier", $langs->trans("NewOrder"), 1, $user->rights->fournisseur->commande->creer);
					$newmenu->add_submenu(DOL_URL_ROOT."/fourn/commande/liste.php?leftmenu=suppliers", $langs->trans("List"), 1, $user->rights->fournisseur->commande->lire);
				}

			}

			/*
			* Menu AGENDA
			*/
			if ($mainmenu == 'agenda')
			{
				// Actions
				if ($conf->agenda->enabled)
				{
					$langs->load("agenda");
					
					// Actions
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda", $langs->trans("Actions"), 0, $user->rights->agenda->myactions->read);
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/fiche.php?mainmenu=agenda&amp;leftmenu=agenda&amp;action=create", $langs->trans("NewAction"), 1, $user->rights->agenda->myactions->read);
					// Calendar
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda", $langs->trans("Calendar"), 1, $user->rights->agenda->myactions->read);
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine", $langs->trans("MenuToDoMyActions"),2, $user->rights->agenda->myactions->read);
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine", $langs->trans("MenuDoneMyActions"),2, $user->rights->agenda->myactions->read);
					if ($user->rights->agenda->allactions->read)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo", $langs->trans("MenuToDoActions"),2, $user->rights->agenda->allactions->read);
						$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done", $langs->trans("MenuDoneActions"),2, $user->rights->agenda->allactions->read);
					}
					// List
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda", $langs->trans("List"), 1, $user->rights->agenda->myactions->read);
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine", $langs->trans("MenuToDoMyActions"),2, $user->rights->agenda->myactions->read);
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine", $langs->trans("MenuDoneMyActions"),2, $user->rights->agenda->myactions->read);
					if ($user->rights->agenda->allactions->read)
					{
						$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo", $langs->trans("MenuToDoActions"),2, $user->rights->agenda->allactions->read);
						$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done", $langs->trans("MenuDoneActions"),2, $user->rights->agenda->allactions->read);
					}
					// Reports
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/action/rapport/index.php?mainmenu=agenda&amp;leftmenu=agenda", $langs->trans("Reportings"), 1, $user->rights->agenda->myactions->read);
				}
			}	

			/*
			* Menu PROJETS
			*/
			if ($mainmenu == 'project')
			{
				if ($conf->projet->enabled)
				{
					$langs->load("projects");
					$newmenu->add(DOL_URL_ROOT."/projet/index.php?leftmenu=projects", $langs->trans("Projects"), 0, $user->rights->projet->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/projet/fiche.php?leftmenu=projects&action=create", $langs->trans("NewProject"), 1, $user->rights->projet->creer);
					$newmenu->add_submenu(DOL_URL_ROOT."/projet/liste.php?leftmenu=projects", $langs->trans("List"), 1, $user->rights->projet->lire);

					$newmenu->add(DOL_URL_ROOT."/projet/tasks/index.php", $langs->trans("Tasks"), 0, $user->rights->projet->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/projet/tasks/fiche.php?action=create", $langs->trans("NewTask"), 1, $user->rights->projet->creer);
					$newmenu->add_submenu(DOL_URL_ROOT."/projet/tasks/index.php?mode=mine", $langs->trans("Mytasks"), 1, $user->rights->projet->lire);
					
					$newmenu->add(DOL_URL_ROOT."/projet/activity/index.php", $langs->trans("TimeSpent"), 0, $user->rights->projet->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/projet/activity/list.php", $langs->trans("NewTimeSpent"), 1, $user->rights->projet->creer);
					$newmenu->add_submenu(DOL_URL_ROOT."/projet/activity/index.php?mode=mine", $langs->trans("MyTimeSpent"), 1, $user->rights->projet->lire);
				}
			}


			/*
			* Menu OUTILS
			*/
			if ($mainmenu == 'tools')
			{

				if ($conf->mailing->enabled)
				{
					$langs->load("mails");
					/*
					$newmenu->add(DOL_URL_ROOT."/comm/mailing/index.php?leftmenu=mailing", $langs->trans("EMailings"), 0, $user->rights->mailing->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/mailing/fiche.php?leftmenu=mailing&amp;action=create", $langs->trans("NewMailing"), 1, $user->rights->mailing->creer);
					$newmenu->add_submenu(DOL_URL_ROOT."/comm/mailing/liste.php?leftmenu=mailing", $langs->trans("List"), 1, $user->rights->mailing->lire);
					*/
				}

				if ($conf->bookmark->enabled)
				{
					$langs->load("other");
					$newmenu->add_submenu(DOL_URL_ROOT."/bookmarks/liste.php?leftmenu=bookmarks", $langs->trans("Bookmarks"), 0, $user->rights->bookmark->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/bookmarks/fiche.php?action=create", $langs->trans("NewBookmark"), 1, $user->rights->bookmark->creer);
					$newmenu->add_submenu(DOL_URL_ROOT."/bookmarks/liste.php", $langs->trans("List"), 1, $user->rights->bookmark->lire);
				}

				if ($conf->export->enabled)
				{
					$langs->load("exports");
					$newmenu->add_submenu(DOL_URL_ROOT."/exports/index.php?leftmenu=export",$langs->trans("FormatedExport"),0, $user->rights->export->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/exports/export.php?leftmenu=export",$langs->trans("NewExport"),1, $user->rights->export->creer);
				}

				if ($conf->global->MAIN_MODULE_DOMAIN)
				{
					$langs->load("domains");
					$newmenu->add_submenu(DOL_URL_ROOT."/domain/index.php?leftmenu=export",$langs->trans("DomainNames"),0, $user->rights->domain->read);
					$newmenu->add_submenu(DOL_URL_ROOT."/domain/fiche.php?action=create&leftmenu=export",$langs->trans("NewDomain"),1, $user->rights->domain->create);
					$newmenu->add_submenu(DOL_URL_ROOT."/domain/index.php?leftmenu=export",$langs->trans("List"),1, $user->rights->domain->read);
				}
			}

			/*
			* Menu MEMBERS
			*/
			if ($mainmenu == 'members')
			{
				if ($conf->adherent->enabled)
				{
					$langs->load("members");
					$langs->load("compta");

					$newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=members&amp;mainmenu=members",$langs->trans("Members"),0,$user->rights->adherent->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/fiche.php?leftmenu=members&amp;action=create",$langs->trans("NewMember"),1,$user->rights->adherent->creer);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members",$langs->trans("List"),1,$user->rights->adherent->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=-1",$langs->trans("MenuMembersToValidate"),1,$user->rights->adherent->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=1",$langs->trans("MenuMembersValidated"),1,$user->rights->adherent->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=uptodate",$langs->trans("MenuMembersUpToDate"),1,$user->rights->adherent->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=outofdate",$langs->trans("MenuMembersNotUpToDate"),1,$user->rights->adherent->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=0",$langs->trans("MenuMembersResiliated"),1,$user->rights->adherent->lire);

					$newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=members&amp;mainmenu=members",$langs->trans("Subscriptions"),0,$user->rights->adherent->cotisation->lire);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=-1,1&amp;mainmenu=members",$langs->trans("NewSubscription"),1,$user->rights->adherent->cotisation->creer);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/cotisations.php?leftmenu=members",$langs->trans("List"),1,$user->rights->adherent->cotisation->lire);

					if ($conf->banque->enabled)
					{
						$langs->load("bills");
						$newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/cheque/index.php?leftmenu=members_checks",$langs->trans("MenuChequeDeposits"),0,$user->rights->adherent->cotisation->lire);
						if (eregi("members_checks",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/cheque/fiche.php?leftmenu=members_checks&amp;action=new",$langs->trans("NewChequeDeposit"),1,$user->rights->adherent->cotisation->creer);
						if (eregi("members_checks",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/cheque/liste.php?leftmenu=members_checks",$langs->trans("MenuChequesReceipts"),1,$user->rights->adherent->cotisation->lire);
					}
					
					if ($conf->banque->enabled)
					{
						$langs->load("banks");
						$newmenu->add_submenu(DOL_URL_ROOT."/compta/bank/index.php?leftmenu=members",$langs->trans("Banks"),0,$user->rights->adherent->lire);
					}
					
					$newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=export&amp;mainmenu=members",$langs->trans("Exports"),0,$user->rights->adherent->export);
					if ($conf->export->enabled && $leftmenu=="export") $newmenu->add_submenu(DOL_URL_ROOT."/exports/index.php?leftmenu=export",$langs->trans("Datas"),1,$user->rights->adherent->export);
					if ($leftmenu=="export") $newmenu->add_submenu(DOL_URL_ROOT."/adherents/htpasswd.php?leftmenu=export",$langs->trans("Filehtpasswd"),1,$user->rights->adherent->export);
					if ($leftmenu=="export") $newmenu->add_submenu(DOL_URL_ROOT."/adherents/cartes/carte.php?leftmenu=export",$langs->trans("MembersCards"),1,$user->rights->adherent->export,'_blank');
					if ($leftmenu=="export") $newmenu->add_submenu(DOL_URL_ROOT."/adherents/cartes/etiquette.php?leftmenu=export",$langs->trans("MembersTickets"),1,$user->rights->adherent->export,'_blank');

					$newmenu->add(DOL_URL_ROOT."/public/adherents/index.php?leftmenu=member_public",$langs->trans("MemberPublicLinks"));
					/*
					if ($leftmenu=="member_public") $newmenu->add(DOL_URL_ROOT."/public/adherents/","Non adherent");
					if ($leftmenu=="member_public") $newmenu->add_submenu("new.php","Inscription");
					if ($leftmenu=="member_public") $newmenu->add(DOL_URL_ROOT."/public/adherents/","Adherents");
					if ($leftmenu=="member_public") $newmenu->add_submenu("priv_edit.php",$langs->trans("EditCard"));
					if ($leftmenu=="member_public") $newmenu->add_submenu("priv_liste.php",$langs->trans("List"));
					*/

					$newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=setup&amp;mainmenu=members",$langs->trans("Setup"),0,$user->rights->adherent->configurer);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/type.php?leftmenu=setup&amp;",$langs->trans("MembersTypes"),1,$user->rights->adherent->configurer);
					$newmenu->add_submenu(DOL_URL_ROOT."/adherents/options.php?leftmenu=setup&amp;",$langs->trans("MembersAttributes"),1,$user->rights->adherent->configurer);
				}

			}

			// Affichage des menus personnalises
    		require_once(DOL_DOCUMENT_ROOT."/core/menubase.class.php");
			
    		$menuArbo = new Menubase($this->db,'eldy','left');
			$this->overwritemenufor = $menuArbo->listeMainmenu();
			// Add other mainmenu to the list of menu to overwrite pre.inc.php
			$overwritemenumore=array('home','companies','members','products','suppliers','commercial','accountancy','agenda','project','tools','ecm');
			$this->overwritemenufor=array_merge($overwritemenumore, $this->overwritemenufor);
    		$newmenu = $menuArbo->menuLeftCharger($newmenu,$mainmenu,$leftmenu,1,'eldy');
			
			/*
			* Menu AUTRES (Pour les menus du haut qui ne serait pas gérés)
			*/
			if ($mainmenu && ! in_array($mainmenu,$this->overwritemenufor)) { $mainmenu=""; }

		}



		/**
		*  Si on est sur un cas géré de surcharge du menu, on ecrase celui par defaut
		*/
		if ($mainmenu) {
			$this->menu_array=$newmenu->liste;
		}


		// Affichage du menu
		$alt=0;
		if (! sizeof($this->menu_array))
		{
			print '<div class="blockvmenuimpair">'."\n";
			print $langs->trans("NoMenu");
			print '</div>';
		}
		else
		{
			$contenu = 0;
			for ($i = 0 ; $i < sizeof($this->menu_array) ; $i++)
			{
				$alt++;
				if ($this->menu_array[$i]['level']==0)
				{
					if (($alt%2==0))
					{
						print '<div class="blockvmenuimpair">'."\n";
					}
					else
					{
						print '<div class="blockvmenupair">'."\n";
					}
				}

				// Place tabulation
				$tabstring='';
				$tabul=($this->menu_array[$i]['level'] - 1);
				if ($tabul > 0)
				{
					for ($j=0; $j < $tabul; $j++)
					{
						$tabstring.='&nbsp; &nbsp; ';
					}
				}

				// Menu niveau 0
				if ($this->menu_array[$i]['level'] == 0)
				{
					if ($contenu == 1) print '<div class="menu_fin"></div>'."\n";
					if ($this->menu_array[$i]['enabled'])
					{
						print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.$this->menu_array[$i]['url'].'"'.($this->menu_array[$i]['target']?' target="'.$this->menu_array[$i]['target'].'"':'').'>'.$this->menu_array[$i]['titre'].'</a></div>';
					}
					else
					{
						print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$this->menu_array[$i]['titre'].'</font></div>';
					}
				}
				// Menu niveau > 0
				if ($this->menu_array[$i]['level'] > 0)
				{
					if ($this->menu_array[$i]['level']==1) $contenu = 1;
					if ($this->menu_array[$i]['enabled'])
					{
						print '<div class="menu_contenu">';
						print $tabstring.'<a class="vsmenu" href="'.$this->menu_array[$i]['url'].'"'.($this->menu_array[$i]['target']?' target="'.$this->menu_array[$i]['target'].'"':'').'>';
						print $this->menu_array[$i]['titre'];
						print '</a>';
						// If title is not pure text and contains a table, no carriage return added
						if (! strstr($this->menu_array[$i]['titre'],'<table')) print '<br>';
						print '</div>';
					}
					else
					{
						print '<div class="menu_contenu">';
						print $tabstring.'<font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
						print '</div>';
					}
				}


				if ($i == (sizeof($this->menu_array)-1) || $this->menu_array[$i+1]['level']==0)
				{
					print "</div>\n";
				}
			}
            if ($contenu == 1) print '<div class="menu_fin"></div>'."\n";
			
		}

		$conf->global->MAIN_SEARCHFORM_SOCIETE=0;
		$conf->global->MAIN_SEARCHFORM_CONTACT=0;
		$conf->global->MAIN_SEARCHFORM_PRODUITSERVICE=0;

	}

}

?>
