<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_lineupdown.inc.php
 *  \brief			Code for actions on moving lines up or down onto object page
 */


// $action must be defined
// $permissiontoedit must be defined to permission to edit object
// $object must be defined
// $langs must be defined
// $hidedetails, $hidedesc, $hideref must de defined

if ($action == 'up' && $permissiontoedit)
{
	$object->line_up(GETPOST('rowid'));

	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}

	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
		$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '#' . GETPOST('rowid'));
	exit();
}

if ($action == 'down' && $permissiontoedit)
{
	$object->line_down(GETPOST('rowid'));

	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
		$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '#' . GETPOST('rowid'));
	exit();
}

