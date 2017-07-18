<?php
/* Copyright (C) 2017 ATM Consulting <contact@atm-consulting.fr>
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
 *	\file       htdocs/blockedlog/admin/fingerprints.php
 *  \ingroup    blockedlog
 *  \brief      Page setup for blockedlog module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/authority.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load("other");
$langs->load("blockedlog");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$showonlyerrors = GETPOST('showonlyerrors','int');

$block_static = new BlockedLog($db);

if($action === 'downloadblockchain') {

	$auth = new BlockedLogAuthority($db);

	$bc = $auth->getLocalBlockChain();

	header('Content-Type: application/octet-stream');
	header("Content-Transfer-Encoding: Binary");
	header("Content-disposition: attachment; filename=\"" .$auth->signature. ".certif\"");

	echo $bc;

	exit;
}
else if($action === 'downloadcsv') {

	$res = $db->query("SELECT rowid,tms,action,amounts,element,fk_object,date_object,ref_object,signature,fk_user
					FROM ".MAIN_DB_PREFIX."blockedlog ORDER BY rowid ASC");

	if($res) {

		$signature = $block_static->getSignature();

		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-disposition: attachment; filename=\"" .$signature. ".csv\"");

		print $langs->transnoentities('Id')
			.';'.$langs->transnoentities('Timestamp')
			.';'.$langs->transnoentities('Action')
			.';'.$langs->transnoentities('Amounts')
			.';'.$langs->transnoentities('Element')
			.';'.$langs->transnoentities('ObjectId')
			.';'.$langs->transnoentities('Date')
			.';'.$langs->transnoentities('Ref')
			.';'.$langs->transnoentities('Fingerprint')
			.';'.$langs->transnoentities('User')."\n";

		while($obj = $db->fetch_object($res)) {

			print $obj->rowid
				.';'.$obj->tms
				.';'.$obj->action
				.';'.$obj->amounts
				.';'.$obj->element
				.';'.$obj->fk_object
				.';'.$obj->date_object
				.';'.$obj->ref_object
				.';'.$obj->signature
				.';'.$obj->fk_user."\n";

		}

		exit;
	}
	else{
		setEventMessage($db->lasterror, 'errors');
	}

}

/*
 *	View
 */

$blocks = $block_static->getLog('all', 0, GETPOST('all') ? 0 : 50);

$form=new Form($db);

llxHeader('',$langs->trans("BlockedLogSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog'),$linkback);

$head=blockedlogadmin_prepare_head();

dol_fiche_head($head, 'fingerprints', '', -1);

print $langs->trans("FingerprintsDesc")."<br>\n";

print '<br>';

print '<div align="right">';
print ' <a href="?all=1">'.$langs->trans('ShowAllFingerPrintsMightBeTooLong').'</a>';
print ' | <a href="?all=1&showonlyerrors=1">'.$langs->trans('ShowAllFingerPrintsErrorsMightBeTooLong').'</a>';
print ' | <a href="?action=downloadblockchain">'.$langs->trans('DownloadBlockChain').'</a>';
print ' | <a href="?action=downloadcsv">'.$langs->trans('DownloadLogCSV').'</a>';
print ' </div>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';

print '<td class="minwidth50">'.$langs->trans('#').'</td>';
print '<td class="center">'.$langs->trans('Date').'</td>';
print '<td>'.$langs->trans('Author').'</td>';
print '<td>'.$langs->trans('Action').'</td>';
print '<td>'.$langs->trans('Ref').'</td>';
print '<td>'.$langs->trans('Element').'</td>';
print '<td>'.$langs->trans('Amount').'</td>';
print '<td class="center">'.$langs->trans('DataOfArchivedEvent').'</td>';
print '<td>'.$langs->trans('Fingerprint').'</td>';
print '<td><span id="blockchainstatus"></span></td>';

print '</tr>';

foreach($blocks as &$block) {

	$checksignature = $block->checkSignature();
	$object_link = $block->getObjectLink();

	if(!$showonlyerrors || $block->error>0) {

	   	print '<tr class="oddeven">';
	   	print '<td>'.$block->id.'</td>';
	   	print '<td>'.dol_print_date($block->tms,'dayhour').'</td>';
	   	print '<td>'.$block->getUser().'</td>';
	   	print '<td>'.$langs->trans('log'.$block->action).'</td>';
	   	print '<td>'.$block->ref_object.'</td>';
	   	print '<td>'.$object_link.'</td>';
	   	print '<td align="right">'.price($block->amounts).'</td>';
	   	print '<td align="center"><a href="#" blockid="'.$block->id.'" rel="show-info">'.img_info($langs->trans('ShowDetails')).'</a></td>';

	   	print '<td>';
	   	print $form->textwithpicto(dol_trunc($block->signature, '12'), $block->signature);
	   	print '</td>';

	   	print '<td>';
	   	print $block->error == 0 ? img_picto($langs->trans('OkCheckFingerprintValidity'), 'tick') : img_picto($langs->trans('KoCheckFingerprintValidity'), 'statut8');

	   	if(!empty($conf->global->BLOCKEDLOG_USE_REMOTE_AUTHORITY) && !empty($conf->global->BLOCKEDLOG_AUTHORITY_URL)) {
	   		print ' '.($block->certified ? img_picto($langs->trans('AddedByAuthority'), 'info') :  img_picto($langs->trans('NotAddedByAuthorityYet'), 'info_black') );
	   	}
		print '</td>';

		print '</tr>';

	}
}

print '</table>';



?>
<script type="text/javascript">
$('a[rel=show-info]').click(function() {

	$pop = $('<div id="pop-info"><table width="100%" class="border"><thead><th width="25%"><?php echo $langs->trans('Field') ?></th><th><?php echo $langs->trans('Value') ?></th></thead><tbody></tbody></table></div>');

	$pop.dialog({
		title:"<?php echo $langs->transnoentities('BlockedlogInfoDialog'); ?>"
		,modal:true
		,width:'80%'
	});

	var fk_block = $(this).attr("blockid");

	$.ajax({
		url:"../ajax/block-info.php?id="+fk_block
		,dataType:'json'
	}).done(function(data) {

		drawData(data,'');

	});

});

function drawData(data, prefix) {

	for(x in data) {

		value = data[x];

		$('#pop-info table tbody').append('<tr><td>'+prefix+x+'</td><td>'+value+'</td></tr>');

		if( (typeof value === "object") && (value !== null) ) {
			drawData(value, prefix+x+' &gt;&gt; ');
		}

	}

}

</script>

<?php


if(!empty($conf->global->BLOCKEDLOG_USE_REMOTE_AUTHORITY) && !empty($conf->global->BLOCKEDLOG_AUTHORITY_URL)) {

?>
				<script type="text/javascript">

					$.ajax({
						url : "<?php echo dol_buildpath('/blockedlog/ajax/check_signature.php',1) ?>"
						,dataType:"html"
					}).done(function(data) {

						if(data == 'hashisok') {
							$('#blockchainstatus').html('<?php echo $langs->trans('AuthorityReconizeFingerprintConformity'). ' '. img_picto($langs->trans('SignatureOK'), 'on') ?>');
						}
						else{
							$('#blockchainstatus').html('<?php echo $langs->trans('AuthorityDidntReconizeFingerprintConformity'). ' '.img_picto($langs->trans('SignatureKO'), 'off') ?>');
						}

					});

				</script>

<?php

}

dol_fiche_end();

print '<br><br>';

llxFooter();
$db->close();
