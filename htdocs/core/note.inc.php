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
 *  \file       htdocs/core/note.inc.php
 *  \brief      Shared controller for the "Notes" tab of an object card.
 *              Included by each module's own note.php after it has fetched
 *              its object, run its security check, and set the note*
 *              variables documented below. Never called directly by a URL.
 */

/**
 * @var CommonObject $object
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 * @var string $element
 *
 * @var int $id
 * @var ?string $ref
 * @var ?string $action
 * @var int<0,1> $permissionnote
 *
 * @var array<int,string> $notehookcontext
 * @var callable $notepreparehead
 * @var string $notetabid
 * @var string $notetabtitle
 * @var string $notepicto
 * @var string $notepagetitle
 * @var ?string $notehelpurl
 * @var ?string $notebodyclass
 * @var string $noteparamid
 * @var ?string $notelinkback
 * @var ?int $noteshownav
 * @var ?string $notefieldid
 * @var ?string $notefieldref
 * @var ?callable $notemorehtmlref
 * @var ?string $notemoreparam
 * @var ?int $notenodbprefix
 * @var ?string $notemorehtmlleft
 * @var ?string $notemorehtmlstatus
 * @var ?bool $noteviewguard
 * @var ?callable $noteextraaction
 * @var ?callable $noteextracontent  Called as function(CommonObject $object, Form $form): void
 * @var ?callable $notenotfoundcontent
 */
'
@phan-var-force CommonObject $object
@phan-var-force string $element
@phan-var-force int $id
@phan-var-force ?string $ref
@phan-var-force ?string $action
@phan-var-force int<0,1> $permissionnote
@phan-var-force array<int,string> $notehookcontext
@phan-var-force callable $notepreparehead
@phan-var-force string $notetabid
@phan-var-force string $notetabtitle
@phan-var-force string $notepicto
@phan-var-force string $notepagetitle
@phan-var-force ?string $notehelpurl
@phan-var-force ?string $notebodyclass
@phan-var-force string $noteparamid
@phan-var-force ?string $notelinkback
@phan-var-force ?int $noteshownav
@phan-var-force ?string $notefieldid
@phan-var-force ?string $notefieldref
@phan-var-force ?callable $notemorehtmlref
@phan-var-force ?string $notemoreparam
@phan-var-force ?int $notenodbprefix
@phan-var-force ?string $notemorehtmlleft
@phan-var-force ?string $notemorehtmlstatus
@phan-var-force ?bool $noteviewguard
@phan-var-force ?callable $noteextraaction
@phan-var-force ?callable $noteextracontent  Called as function(CommonObject $object, Form $form): void
@phan-var-force ?callable $notenotfoundcontent
';
// Protection to avoid direct call of this shared controller as a URL
if (empty($object) || !is_object($object)) {
	print "Error, this page can't be called directly";
	exit(1);
}

$hookmanager->initHooks($notehookcontext);


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'
}
if (isset($noteextraaction)) {
	$noteextraaction();
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $notepagetitle, (empty($notehelpurl) ? '' : $notehelpurl), '', 0, 0, '', '', '', (empty($notebodyclass) ? '' : $notebodyclass));

if ($object->id > 0 && (!isset($noteviewguard) || $noteviewguard)) {
	$head = $notepreparehead($object);
	print dol_get_fiche_head($head, $notetabid, $notetabtitle, -1, $notepicto);

	$morehtmlref = (isset($notemorehtmlref) ? $notemorehtmlref($object, $form, $action) : '');

	// $element must always be threaded into every self-referencing URL this page (and notes.tpl.php below,
	// which reuses this same $moreparam) builds via $_SERVER['PHP_SELF'] — otherwise the note edit link, the
	// note save POST, and the prev/next banner nav all point at /core/note.php with no way to resolve which
	// module's loader to run, and hit accessforbidden().
	$moreparam = (isset($notemoreparam) ? $notemoreparam : '').'&element='.urlencode($element);

	dol_banner_tab(
		$object,
		$noteparamid,
		(isset($notelinkback) ? $notelinkback : ''),
		(isset($noteshownav) ? $noteshownav : 1),
		(isset($notefieldid) ? $notefieldid : 'rowid'),
		(isset($notefieldref) ? $notefieldref : 'ref'),
		$morehtmlref,
		$moreparam,
		(isset($notenodbprefix) ? $notenodbprefix : 0),
		(isset($notemorehtmlleft) ? $notemorehtmlleft : ''),
		(isset($notemorehtmlstatus) ? $notemorehtmlstatus : '')
	);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$cssclass = 'titlefield';
	if (isset($noteextracontent)) {
		$noteextracontent($object, $form);
	}

	$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
	foreach ($dirtpls as $reldir) {
		$res = @include dol_buildpath($reldir.'/notes.tpl.php');
		if ($res) {
			break;
		}
	}

	print '</div>';

	print dol_get_fiche_end();
} elseif (isset($notenotfoundcontent)) {
	$notenotfoundcontent();
}

// End of page
llxFooter();
$db->close();
