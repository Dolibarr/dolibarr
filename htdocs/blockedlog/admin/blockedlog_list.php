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
 *	\file       htdocs/blockedlog/admin/blockedlog_list.php
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
$contextpage= GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'myobjectlist';   // To manage different context of search
$backtopage = GETPOST('backtopage','alpha');											// Go back to a dedicated page
$optioncss  = GETPOST('optioncss','aZ');												// Option for the css output (always '' except when 'print')

$search=array();

$showonlyerrors = GETPOST('showonlyerrors','int');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortfield)) $sortfield='rowid';
if (empty($sortorder)) $sortorder='DESC';

$search_start = -1;
if(GETPOST('search_startyear')!='') $search_start = dol_mktime(0, 0, 0, GETPOST('search_startmonth'), GETPOST('search_startday'), GETPOST('search_startyear'));

$search_end = -1;
if(GETPOST('search_endyear')!='') $search_end= dol_mktime(23, 59, 59, GETPOST('search_endmonth'), GETPOST('search_endday'), GETPOST('search_endyear'));



$block_static = new BlockedLog($db);


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
	$search_start = -1;
	$search_end = -1;
	$toselect='';
	$search_array_options=array();
}

if ($action === 'downloadblockchain') {

	$auth = new BlockedLogAuthority($db);

	$bc = $auth->getLocalBlockChain();

	header('Content-Type: application/octet-stream');
	header("Content-Transfer-Encoding: Binary");
	header("Content-disposition: attachment; filename=\"" .$auth->signature. ".certif\"");

	echo $bc;

	exit;
}
else if($action === 'downloadcsv') {

	$sql = "SELECT rowid,date_creation,tms,user_fullname,action,amounts,element,fk_object,date_object,ref_object,signature,fk_user,object_data";
	$sql.= " FROM ".MAIN_DB_PREFIX."blockedlog";
	$sql.= " WHERE entity = ".$conf->entity;
	$sql.= " ORDER BY rowid ASC";
	$res = $db->query($sql);

	if($res) {

		$signature = $block_static->getSignature();

		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-disposition: attachment; filename=\"archive-log-" .$signature. ".csv\"");

		print $langs->transnoentities('Id')
			.';'.$langs->transnoentities('Date')
			.';'.$langs->transnoentities('User')
			.';'.$langs->transnoentities('Action')
			.';'.$langs->transnoentities('Element')
			.';'.$langs->transnoentities('Amounts')
			.';'.$langs->transnoentities('ObjectId')
			.';'.$langs->transnoentities('Date')
			.';'.$langs->transnoentities('Ref')
			.';'.$langs->transnoentities('Fingerprint')
			.';'.$langs->transnoentities('FullData')
			."\n";

		while($obj = $db->fetch_object($res)) {

			print $obj->rowid
				.';'.$obj->date_creation
				.';"'.$obj->user_fullname.'"'
				.';'.$obj->action
				.';'.$obj->element
				.';'.$obj->amounts
				.';'.$obj->fk_object
				.';'.$obj->date_object
				.';"'.$obj->ref_object.'"'
				.';'.$obj->signature
				.';"'.str_replace('"','""',$obj->object_data).'"'
				."\n";
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

$form=new Form($db);

$blocks = $block_static->getLog('all', 0, GETPOST('all','alpha') ? 0 : 50, $sortfield, $sortorder, $search_start, $search_end);

llxHeader('',$langs->trans("BlockedLogSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog'),$linkback);

$head=blockedlogadmin_prepare_head();

dol_fiche_head($head, 'fingerprints', '', -1);

print $langs->trans("FingerprintsDesc")."<br>\n";

print '<br>';

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
if ($search_start > 0) $param.='&search_startyear='.urlencode(GETPOST('search_startyear','int')).'&search_startmonth='.urlencode(GETPOST('search_startmonth','int')).'&search_startday='.urlencode(GETPOST('search_startday','int'));
if ($search_end > 0)   $param.='&search_endyear='.urlencode(GETPOST('search_endyear','int')).'&search_endmonth='.urlencode(GETPOST('search_endmonth','int')).'&search_endday='.urlencode(GETPOST('search_endday','int'));
foreach($search as $key => $val)
{
	$param.= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
// Add $param from extra fields
//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


print '<div align="right">';
print ' <a href="?all=1">'.$langs->trans('ShowAllFingerPrintsMightBeTooLong').'</a>';
print ' | <a href="?all=1&showonlyerrors=1">'.$langs->trans('ShowAllFingerPrintsErrorsMightBeTooLong').'</a>';
print ' | <a href="?action=downloadblockchain">'.$langs->trans('DownloadBlockChain').'</a>';
print ' | <a href="?action=downloadcsv">'.$langs->trans('DownloadLogCSV').'</a>';
print ' </div>';


print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">&nbsp;</td>';

print '<td class="liste_titre">';
print $form->select_date($search_start,'search_start');
print $form->select_date($search_end,'search_end');
print '</td>';

print '<td class="liste_titre" colspan="7">&nbsp;</td>';

// Action column
print '<td class="liste_titre" align="middle">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';

print '</tr>';

print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('#'), 0, $_SERVER["PHP_SELF"],'rowid','',$param,'',$sortfield,$sortorder,'minwidth50 ')."\n";
print getTitleFieldOfList($langs->trans('Date'), 0, $_SERVER["PHP_SELF"],'date_creation','',$param,'',$sortfield,$sortorder,'')."\n";
print getTitleFieldOfList($langs->trans('Author'), 0, $_SERVER["PHP_SELF"],'user_fullname','',$param,'',$sortfield,$sortorder,'')."\n";
print getTitleFieldOfList($langs->trans('Action'), 0, $_SERVER["PHP_SELF"],'','',$param,'',$sortfield,$sortorder,'')."\n";
print getTitleFieldOfList($langs->trans('Ref'), 0, $_SERVER["PHP_SELF"],'ref_object','',$param,'',$sortfield,$sortorder,'')."\n";
print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"],'','',$param,'',$sortfield,$sortorder,'')."\n";
print getTitleFieldOfList($langs->trans('Amount'), 0, $_SERVER["PHP_SELF"],'','',$param,'align="right"',$sortfield,$sortorder,'')."\n";
print getTitleFieldOfList($langs->trans('DataOfArchivedEvent'), 0, $_SERVER["PHP_SELF"],'','',$param,'align="center"',$sortfield,$sortorder,'')."\n";
print getTitleFieldOfList($langs->trans('Fingerprint'), 0, $_SERVER["PHP_SELF"],'','',$param,'',$sortfield,$sortorder,'')."\n";
print getTitleFieldOfList('<span id="blockchainstatus"></span>', 0, $_SERVER["PHP_SELF"],'','',$param,'',$sortfield,$sortorder,'')."\n";
print '</tr>';

$loweridinerror=0;
$checkresult=array();
foreach($blocks as &$block) {
	$checksignature = $block->checkSignature();
	$checkresult[$block->id]=$checksignature;	// false if error
	if (! $checksignature)
	{
		if (empty($loweridinerror)) $loweridinerror=$block->id;
		else $loweridinerror = min($loweridinerror, $block->id);
	}

}

foreach($blocks as &$block) {
	$object_link = $block->getObjectLink();

	if (empty($showonlyerrors) || ! $checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror))
	{
	   	print '<tr class="oddeven">';
	   	print '<td>'.$block->id.'</td>';
	   	print '<td>'.dol_print_date($block->tms,'dayhour').'</td>';
	   	print '<td>';
	   	//print $block->getUser()
	   	print $block->user_fullname;
	   	print '</td>';
	   	print '<td>'.$langs->trans('log'.$block->action).'</td>';
	   	print '<td>'.$block->ref_object.'</td>';
	   	print '<td>'.$object_link.'</td>';
	   	print '<td align="right">'.price($block->amounts).'</td>';
	   	print '<td align="center"><a href="#" data-blockid="'.$block->id.'" rel="show-info">'.img_info($langs->trans('ShowDetails')).'</a></td>';

	   	print '<td>';
	   	print $form->textwithpicto(dol_trunc($block->signature, '12'), $block->signature);
	   	print '</td>';

	   	print '<td>';
	   	if (! $checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror))	// If error
	   	{
	   		if ($checkresult[$block->id]) print img_picto($langs->trans('OkCheckFingerprintValidityButChainIsKo'), 'statut1');
	   		else print img_picto($langs->trans('KoCheckFingerprintValidity'), 'statut8');
	   	}
	   	else
	   	{
	   		print img_picto($langs->trans('OkCheckFingerprintValidity'), 'statut4');
	   	}

	   	if(!empty($conf->global->BLOCKEDLOG_USE_REMOTE_AUTHORITY) && !empty($conf->global->BLOCKEDLOG_AUTHORITY_URL)) {
	   		print ' '.($block->certified ? img_picto($langs->trans('AddedByAuthority'), 'info') :  img_picto($langs->trans('NotAddedByAuthorityYet'), 'info_black') );
	   	}
		print '</td>';

		print '</tr>';

	}
}

print '</table>';

print '</form>';

print '</div>';

print '<script type="text/javascript">

jQuery(document).ready(function () {
	jQuery("#dialogforpopup").dialog(
	{ closeOnEscape: true, classes: { "ui-dialog": "highlight" },
	maxHeight: window.innerHeight-60, height: window.innerHeight-60, width: '.($conf->browser->layout == 'phone' ? 400 : 700).',
	modal: true,
	autoOpen: false }).css("z-index: 5000");

	$("a[rel=show-info]").click(function() {

	    console.log("We click on tooltip");

		jQuery("#dialogforpopup").html(\'<div id="pop-info"><table width="100%" height="80%" class="border"><thead><th width="50%">'.$langs->trans('Field').'</th><th>'.$langs->trans('Value').'</th></thead><tbody></tbody></table></div>\');

		var fk_block = $(this).attr("data-blockid");

		$.ajax({
			url:"../ajax/block-info.php?id="+fk_block
			,dataType:"json"
		}).done(function(data) {
			drawData(data,"");
		});

		jQuery("#dialogforpopup").dialog("open");
	});


	function drawData(data, prefix) {
		for(x in data) {
			value = data[x];

			$("#pop-info table tbody").append("<tr><td>"+prefix+x+"</td><td class=\"wordwrap\">"+value+"</td></tr>");
			if( (typeof value === "object") && (value !== null) ) {
				drawData(value, prefix+x+" &gt;&gt; ");
			}
		}
	}

})
</script>'."\n";


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
