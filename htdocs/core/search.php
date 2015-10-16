<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/search.php
 *       \brief      Wrapper that receive any search. Depending on input field, make a redirect to correct URL.
 */

if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');		// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN')) define('NOLOGIN',1);					// Not disabled cause need to load personalized language
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU',1);
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML',1);

require_once '../main.inc.php';

/*
 * Actions
 */

if (GETPOST('search_proposal') != '')
{
	header("Location: ".DOL_URL_ROOT.'/comm/propal/list.php?sall='.urlencode(GETPOST('search_proposal')));
	exit;
}
if (GETPOST('search_customer_order') != '')
{
	header("Location: ".DOL_URL_ROOT.'/commande/list.php?sall='.urlencode(GETPOST('search_customer_order')));
	exit;
}
if (GETPOST('search_supplier_order') != '')
{
	header("Location: ".DOL_URL_ROOT.'/fourn/commande/list.php?search_all='.urlencode(GETPOST('search_supplier_order')));
	exit;
}
if (GETPOST('search_intervention') != '')
{
	header("Location: ".DOL_URL_ROOT.'/fichinter/list.php?sall='.urlencode(GETPOST('search_intervention')));
	exit;
}
if (GETPOST('search_contract') != '')
{
	header("Location: ".DOL_URL_ROOT.'/contrat/list.php?sall='.urlencode(GETPOST('search_contract')));
	exit;
}

// If we are here, search was called with no supported criteria
if (! empty($_SERVER['HTTP_REFERER']))
{
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
}
else
{
	print 'The wrapper search.php was called without any search criteria';
}

