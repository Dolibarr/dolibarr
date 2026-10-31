<?php
/* Copyright (C) 2026  Frédéric France  <frederic.france@free.fr>
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
 *  \file       htdocs/core/note.php
 *  \brief      Single entry point for the "Notes" tab of any object card.
 *              Resolves the 'element' request parameter against a closed
 *              whitelist (core/lib/note.lib.php), calls that module's
 *              loader function, then includes the shared view controller.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/note.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$element = GETPOST('element', 'aZ09');

$noteregistry = getNoteRegistry();
if (!isset($noteregistry[$element])) {
	accessforbidden('Bad value for parameter element');
}

$notefunction = $noteregistry[$element];
$noteinfo = $notefunction();

$object = $noteinfo['object'];
$id = $noteinfo['id'];
$ref = $noteinfo['ref'];
$action = $noteinfo['action'];
$permissionnote = $noteinfo['permissionnote'];
$notehookcontext = $noteinfo['notehookcontext'];
$notepreparehead = $noteinfo['notepreparehead'];
$notetabid = $noteinfo['notetabid'];
$notetabtitle = $noteinfo['notetabtitle'];
$notepicto = $noteinfo['notepicto'];
$notepagetitle = $noteinfo['notepagetitle'];
$noteparamid = $noteinfo['noteparamid'];
if (isset($noteinfo['notehelpurl'])) {
	$notehelpurl = $noteinfo['notehelpurl'];
}
if (isset($noteinfo['notebodyclass'])) {
	$notebodyclass = $noteinfo['notebodyclass'];
}
if (isset($noteinfo['notelinkback'])) {
	$notelinkback = $noteinfo['notelinkback'];
}
if (isset($noteinfo['noteshownav'])) {
	$noteshownav = $noteinfo['noteshownav'];
}
if (isset($noteinfo['notefieldid'])) {
	$notefieldid = $noteinfo['notefieldid'];
}
if (isset($noteinfo['notefieldref'])) {
	$notefieldref = $noteinfo['notefieldref'];
}
if (isset($noteinfo['notemorehtmlref'])) {
	$notemorehtmlref = $noteinfo['notemorehtmlref'];
}
if (isset($noteinfo['notemoreparam'])) {
	$notemoreparam = $noteinfo['notemoreparam'];
}
if (isset($noteinfo['notenodbprefix'])) {
	$notenodbprefix = $noteinfo['notenodbprefix'];
}
if (isset($noteinfo['notemorehtmlleft'])) {
	$notemorehtmlleft = $noteinfo['notemorehtmlleft'];
}
if (isset($noteinfo['notemorehtmlstatus'])) {
	$notemorehtmlstatus = $noteinfo['notemorehtmlstatus'];
}
if (isset($noteinfo['noteviewguard'])) {
	$noteviewguard = $noteinfo['noteviewguard'];
}
if (isset($noteinfo['noteextraaction'])) {
	$noteextraaction = $noteinfo['noteextraaction'];
}
if (isset($noteinfo['noteextracontent'])) {
	$noteextracontent = $noteinfo['noteextracontent'];
}
if (isset($noteinfo['notenotfoundcontent'])) {
	$notenotfoundcontent = $noteinfo['notenotfoundcontent'];
}

include DOL_DOCUMENT_ROOT.'/core/note.inc.php';
