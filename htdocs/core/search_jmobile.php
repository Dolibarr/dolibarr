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
 *       \file       htdocs/core/search_jmobile.php
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

//var_dump($langs->defaultlang);
//var_dump($conf->format_date_short_java);
//var_dump($langs->trans("FormatDateShortJava"));



/*
 * View
 */

// URL http://mydolibarr/core/getmenu_jmobime?mainmenu=mainmenu&leftmenu=leftmenu can be used for tests
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);



print '<body style="margin: 40px; text-align: center">'."\n";
print '<center>';

// Define $searchform
if (! empty($conf->societe->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_SOCIETE) && $user->rights->societe->lire)
{
	$langs->load("companies");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/societe/societe.php', DOL_URL_ROOT.'/societe/societe.php', img_object('','company').' '.$langs->trans("ThirdParties"), 'soc', 'socname');
}

if (! empty($conf->societe->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_CONTACT) && $user->rights->societe->lire)
{
	$langs->load("companies");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/contact/list.php', DOL_URL_ROOT.'/contact/list.php', img_object('','contact').' '.$langs->trans("Contacts"), 'contact', 'contactname');
}

if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
	&& ! empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE))
{
	$langs->load("products");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/product/liste.php', DOL_URL_ROOT.'/product/liste.php', img_object('','product').' '.$langs->trans("Products")."/".$langs->trans("Services"), 'products', 'sall');
}

if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
	&& ! empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE))
{
	$langs->load("products");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/fourn/product/liste.php', DOL_URL_ROOT.'/fourn/product/liste.php', img_object('','product').' '.$langs->trans("SupplierRef"), 'products', 'srefsupplier');
}

if (! empty($conf->adherent->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_ADHERENT) && $user->rights->adherent->lire)
{
	$langs->load("members");
	$searchform.=printSearchForm(DOL_URL_ROOT.'/adherents/liste.php', DOL_URL_ROOT.'/adherents/liste.php', img_object('','user').' '.$langs->trans("Members"), 'member', 'sall');
}

// Execute hook printSearchForm
$parameters=array();
$searchform.=$hookmanager->executeHooks('printSearchForm',$parameters);    // Note that $action and $object may have been modified by some hooks


print "\n";
print "<!-- Begin SearchForm -->\n";
print '<div id="blockvmenusearch" class="blockvmenusearch">'."\n";
print $searchform;
print '</div>'."\n";
print "<!-- End SearchForm -->\n";

print '</center>';
print '</body></html>'."\n";

$db->close();
?>
