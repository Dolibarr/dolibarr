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

// Instantiate hooks of thirdparty module
$hookmanager->initHooks(array('searchform'));

// Define $searchform
$searchform = '';

// TODO Mutualize code here with function left_menu into main.inc.php page
if ($conf->use_javascript_ajax && 1 == 2)
{
    if (! is_object($form)) $form=new Form($db);
    $selected=-1;
    $searchform.=$form->selectArrayAjax('searchselectcombo', DOL_URL_ROOT.'/core/ajax/selectsearchbox.php', $selected, 'data-role="none"', '', 0, 1, 'vmenusearchselectcombo', 1, $langs->trans("Search"), 0);
}
else
{
    // Define $searchform
    if ((( ! empty($conf->societe->enabled) && (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) || empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))) || ! empty($conf->fournisseur->enabled)) && $user->rights->societe->lire)
    {
        $langs->load("companies");
        $searchform.=printSearchForm(DOL_URL_ROOT.'/societe/list.php', DOL_URL_ROOT.'/societe/list.php', $langs->trans("ThirdParties"), 'soc', 'sall', 'T', 'searchleftt', img_object('','company'));
    }

    if (! empty($conf->societe->enabled) && $user->rights->societe->lire)
    {
        $langs->load("companies");
        $searchform.=printSearchForm(DOL_URL_ROOT.'/contact/list.php', DOL_URL_ROOT.'/contact/list.php', $langs->trans("Contacts"), 'contact', 'sall', 'A', 'searchleftc', img_object('','contact'));
    }

    if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
    )
    {
        $langs->load("products");
        $searchform.=printSearchForm(DOL_URL_ROOT.'/product/list.php', DOL_URL_ROOT.'/product/list.php', $langs->trans("Products")."/".$langs->trans("Services"), 'products', 'sall', 'P', 'searchleftp', img_object('','product'));
    }

    if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
    {
        $langs->load("projects");
        $searchform.=printSearchForm(DOL_URL_ROOT.'/projet/list.php', DOL_URL_ROOT.'/projet/list.php', $langs->trans("Projects"), 'project', 'search_all', 'Q', 'searchleftproj', img_object('','projectpub'));
    }

    if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire)
    {
        $langs->load("members");
        $searchform.=printSearchForm(DOL_URL_ROOT.'/adherents/list.php', DOL_URL_ROOT.'/adherents/list.php', $langs->trans("Members"), 'member', 'sall', 'M', 'searchleftm', img_object('','user'));
    }

	if (! empty($conf->user->enabled) && $user->rights->user->user->lire)
    {
        $langs->load("users");
        $searchform.=printSearchForm(DOL_URL_ROOT.'/user/list.php', DOL_URL_ROOT.'/user/list.php', $langs->trans("Users"), 'user', 'sall', 'M', 'searchleftuser', img_object('','user'));
    }
}

// Execute hook printSearchForm
$parameters=array('searchform'=>$searchform);
$reshook=$hookmanager->executeHooks('printSearchForm',$parameters);    // Note that $action and $object may have been modified by some hooks
if (empty($reshook))
{
	$searchform.=$hookmanager->resPrint;
}
else $searchform=$hookmanager->resPrint;


print "\n";
print "<!-- Begin SearchForm -->\n";
print '<div class="center" data-role="page" align="center"><div align="center" style="padding: 6px;">';
print '<style>.menu_titre { padding-top: 7px; }</style>';
//print '<div id="distance"></div><div id="container" class="center">';
print '<div id="blockvmenusearch">'."\n";
print $searchform;
print '</div>'."\n";
//print '</div></div>';
print '</div></div>';
print "\n<!-- End SearchForm -->\n";

print '</div>';
print '</body></html>'."\n";

$db->close();
