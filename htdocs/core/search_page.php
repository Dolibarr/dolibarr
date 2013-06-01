<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This file is a modified version of datepicker.php from phpBSM to fix some
 * bugs, to add new features and to dramatically increase speed.
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
 *       \file       htdocs/core/search_page.php
 *       \brief      File to return search box
 */

//if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');		// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
//if (! defined('NOLOGIN')) define('NOLOGIN',1);					// Not disabled cause need to load personalized language
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU',1);
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML',1);

require_once '../main.inc.php';

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));	// If language was forced on URL by the main.inc.php
$langs->load("main");
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');


/*
 * View
 */

$title=$langs->trans("Search");

// URL http://mydolibarr/core/search_page?dol_use_jmobile=1 can be used for tests
$head='<!-- Quick access -->'."\n";
$arrayofjs=array();
$arrayofcss=array();
top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);



print '<body>'."\n";
print '<div>';
//print '<br>';

$nbofsearch=0;

// Define $searchform
if (! empty($conf->societe->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_SOCIETE) && $user->rights->societe->lire)
{
	$langs->load("companies");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/societe/societe.php', DOL_URL_ROOT.'/societe/societe.php', img_object('','company').' '.$langs->trans("ThirdParties"), 'soc', 'socname');
	$nbofsearch++;
}

if (! empty($conf->societe->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_CONTACT) && $user->rights->societe->lire)
{
	$langs->load("companies");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/contact/list.php', DOL_URL_ROOT.'/contact/list.php', img_object('','contact').' '.$langs->trans("Contacts"), 'contact', 'contactname');
	$nbofsearch++;
}

if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
	&& ! empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE))
{
	$langs->load("products");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/product/liste.php', DOL_URL_ROOT.'/product/liste.php', img_object('','product').' '.$langs->trans("Products")."/".$langs->trans("Services"), 'products', 'sall');
	$nbofsearch++;
}

if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
	&& ! empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE))
{
	$langs->load("products");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/fourn/product/liste.php', DOL_URL_ROOT.'/fourn/product/liste.php', img_object('','product').' '.$langs->trans("SupplierRef"), 'products', 'srefsupplier');
	$nbofsearch++;
}

if (! empty($conf->adherent->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_ADHERENT) && $user->rights->adherent->lire)
{
	$langs->load("members");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/adherents/liste.php', DOL_URL_ROOT.'/adherents/liste.php', img_object('','user').' '.$langs->trans("Members"), 'member', 'sall');
	$nbofsearch++;
}

// Execute hook printSearchForm
$parameters=array();
$reshook=$hookmanager->executeHooks('printSearchForm',$parameters);
if (empty($reshook)) $searchform.=$hookmanager->resPrint;
else $searchform=$hookmanager->resPrint;


print "\n";
print "<!-- Begin SearchForm -->\n";
print '<div class="center" data-role="page" style="padding-left: 2px;">';
print '<style>.menu_titre { padding-top: 6px; }</style>';
//print '<div id="distance"></div><div id="container" class="center">';
print '<div id="blockvmenusearch">'."\n";
print $searchform;
print '</div>'."\n";
//print '</div></div>';
print '</div>';
print "<!-- End SearchForm -->\n";

print '</div>';
print '</body></html>'."\n";

$db->close();
?>
