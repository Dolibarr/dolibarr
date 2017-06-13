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
 *	\file       htdocs/blockedlog/admin/blockedlog.php
 *  \ingroup    system
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

if($action === 'downloadblockchain') {
	
	$auth = new BlockedLogAuthority($db);
	
	$bc = $auth->getLocalBlockChain();
	
	header('Content-Type: application/octet-stream');
	header("Content-Transfer-Encoding: Binary");
	header("Content-disposition: attachment; filename=\"" .$auth->signature. ".certif\""); 
	
	echo $bc;
	
	exit;
}


/*
 *	View
 */

$block_static = new BlockedLog($db);

$blocks = $block_static->getLog('all', 0, GETPOST('all') ? 0 : 50);

$form=new Form($db);

llxHeader('',$langs->trans("BlockedLogSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' BlockedLog',$linkback);

$head=blockedlogadmin_prepare_head();

dol_fiche_head($head, 'fingerprints', '', -1);

print $langs->trans("FingerprintsDesc")."<br>\n";

print '<br>';

echo '<div align="right"><a href="?all=1">'.$langs->trans('ShowAllFingerPrintsMightBeTooLong').'</a> | <a href="?action=downloadblockchain">'.$langs->trans('DownloadBlockChain').'</a></div>';
		

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';

print '<td>'.$langs->trans('Date').'</td>';
print '<td>'.$langs->trans('Ref').'</td>';
print '<td>'.$langs->trans('Action').'</td>';
print '<td>'.$langs->trans('Element').'</td>';
print '<td>'.$langs->trans('Amount').'</td>';
print '<td>'.$langs->trans('Author').'</td>';
print '<td>'.$langs->trans('Fingerprint').'</td>';
print '<td><span id="blockchainstatus"></span></td>';
				
print '</tr>';

foreach($blocks as &$block) {
	
   	print '<tr class="oddeven">';
   	print '<td>'.dol_print_date($block->tms,'dayhour').'</td>';
   	print '<td>'.$block->ref_object.'</td>';
   	print '<td>'.$langs->trans('log'.$block->action).'</td>';
   	print '<td>'.$block->getObject().'<a href="#" blockid="'.$block->id.'" rel="show-info">'.img_info($langs->trans('ShowDetails')).'</a></td>';
   	print '<td align="right">'.price($block->amounts).'</td>';
   	print '<td>'.$block->getUser().'</td>';
   	print '<td>'.$block->signature.'</td>';
   	print '<td>';
   	
   	print $block->checkSignature() ? img_picto($langs->trans('OkCheckFingerprintValidity'), 'on') : img_picto($langs->trans('KoCheckFingerprintValidity'), 'off');
   	if(!empty($conf->global->BLOCKEDLOG_USE_REMOTE_AUTHORITY) && !empty($conf->global->BLOCKEDLOG_AUTHORITY_URL)) {
   		print ' '.($block->certified ? img_picto($langs->trans('AddedByAuthority'), 'info') :  img_picto($langs->trans('NotAddedByAuthorityYet'), 'info_black') );
   	}
	print '</td>';
	print '</tr>';
					
}

print '</table>';

?>
<script type="text/javascript">
$('a[rel=show-info]').click(function() {

	$pop = $('<div id="pop-info"><table width="100%" class="border"><thead><th width="25%"><?php echo $langs->trans('Field') ?></th><th><?php echo $langs->trans('Value') ?></th></thead><tbody></tbody></table></div>');
	
	$pop.dialog({
		title:"<?php echo $langs->trans('BlockedlogInfoDialog'); ?>"
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
