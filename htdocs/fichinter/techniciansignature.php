<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
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
 *	\file       htdocs/fichinter/techniciansignature.php
 *	\brief      Sign an intervention as its assigned technician
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('interventions', 'errors', 'other'));
$id = GETPOSTINT('id');
$object = new Fichinter($db);
if (!isModEnabled('ficheinter') || !$user->hasRight('ficheinter', 'lire') || !$user->hasRight('ficheinter', 'creer')
	|| $id <= 0 || $object->fetch($id) <= 0 || (int) $object->entity !== (int) $conf->entity) {
	accessforbidden();
}
restrictedArea($user, 'ficheinter', $object->id, 'fichinter');
if (!$object->isAssignedTechnicianForSignature($user) || $object->model_pdf !== 'soleil') {
	accessforbidden();
}

$cardUrl = DOL_URL_ROOT.'/fichinter/card.php?id='.$object->id;


/*
 * Actions
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && GETPOST('action', 'aZ09', 2) === 'save') {
	$result = $object->signAsTechnician($user, $langs, GETPOST('signature', 'none', 2), GETPOST('digest', 'aZ09', 2));
	if ($result > 0) {
		header('Location: '.$cardUrl);
		exit;
	}
	setEventMessages($langs->trans('ErrorCantSignDocument'), null, 'errors');
	header('Location: '.DOL_URL_ROOT.'/fichinter/techniciansignature.php?id='.$object->id);
	exit;
}


/*
 * View
 */

$source = $object->getPdfForTechnicianSignature();
$digest = $source !== '' ? hash_file('sha256', $source) : false;
$title = $langs->trans('SignInterventionAsTechnician');
llxHeader('', $title, '', '', 0, 0, array('/public/includes/jquery/plugins/jSignature/jSignature.js'));
print load_fiche_titre($title.' - '.dolPrintLabel($object->ref), '', 'intervention');

if ($digest === false || $object->hasTechnicianSignedPdf()) {
	print '<div class="warning">'.$langs->trans('ErrorCantSignDocument').'</div>';
} else {
	$file = dol_sanitizeFileName($object->ref).'/'.dol_basename($source);
	$pdfUrl = DOL_URL_ROOT.'/document.php?modulepart=ficheinter&file='.urlencode($file);
	print '<p>'.$langs->trans('ReviewInterventionPdfBeforeSigning').' <a href="'.dolPrintHTMLForAttributeUrl($pdfUrl).'" target="_blank" rel="noopener">'.$langs->trans('DownloadDocument').'</a></p>';
	print '<form method="POST" id="technician-signature-form" action="'.DOL_URL_ROOT.'/fichinter/techniciansignature.php">';
	print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="save">';
	print '<input type="hidden" name="digest" value="'.$digest.'">';
	print '<input type="hidden" name="signature" value="">';
	print '<div id="technician-signature" class="border centpercent maxwidth750" role="group" aria-label="'.dolPrintHTMLForAttribute($title).'"></div>';
	print '<div class="center">';
	print '<button type="button" class="button" id="clear-technician-signature">'.$langs->trans('Clear').'</button>';
	print '<button type="submit" class="button button-save" disabled>'.$langs->trans('SignIntervention').'</button>';
	print '</div></form>';
	print '<script nonce="'.getNonce().'">
		jQuery(function ($) {
			var form = $("#technician-signature-form");
			var pad = $("#technician-signature").jSignature({height: 160, width: "100%", lineWidth: 2});
			var submit = form.find("button[type=submit]");
			pad.on("change", function () {
				submit.prop("disabled", !pad.jSignature("getData", "native").length);
			});
			$("#clear-technician-signature").on("click", function () {
				pad.jSignature("reset");
			});
			form.on("submit", function (event) {
				if (!pad.jSignature("getData", "native").length) {
					event.preventDefault();
					return;
				}
				form.find("input[name=signature]").val(pad.jSignature("getData", "image")[1]);
				submit.prop("disabled", true);
			});
		});
	</script>';
}
print '<div class="center"><a class="button" href="'.dolPrintHTMLForAttributeUrl($cardUrl).'">'.$langs->trans('Cancel').'</a></div>';

llxFooter();
$db->close();
