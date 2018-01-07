<?php
/* Copyright (C) 2017      ATM Consulting      <contact@atm-consulting.fr>
 * Copyright (C) 2017-2018 Laurent Destailleur <eldy@destailleur.fr>
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

$langs->loadLangs(array("admin", "other", "blockedlog", "bills"));

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$contextpage= GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'blockedloglist';   // To manage different context of search
$backtopage = GETPOST('backtopage','alpha');											// Go back to a dedicated page
$optioncss  = GETPOST('optioncss','aZ');												// Option for the css output (always '' except when 'print')

$showonlyerrors = GETPOST('showonlyerrors','int');

$search_fk_user=GETPOST('search_fk_user','intcomma');
$search_start = -1;
if(GETPOST('search_startyear')!='') $search_start = dol_mktime(0, 0, 0, GETPOST('search_startmonth'), GETPOST('search_startday'), GETPOST('search_startyear'));
$search_end = -1;
if(GETPOST('search_endyear')!='') $search_end= dol_mktime(23, 59, 59, GETPOST('search_endmonth'), GETPOST('search_endday'), GETPOST('search_endyear'));
$search_code = GETPOST('search_code', 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');


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

$block_static = new BlockedLog($db);


$result = restrictedArea($user, 'blockedlog', 0, '');


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
	$search_fk_user = '';
	$search_start = -1;
	$search_end = -1;
	$search_code = '';
	$search_ref = '';
	$search_amount = '';
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
	$sql.= " ORDER BY rowid ASC";					// Required so later we can use the parameter $previoushash of checkSignature()

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
			.';'.$langs->transnoentities('Status')
			.';'.$langs->transnoentities('FullData')
			."\n";

		$previoushash = '';
		$loweridinerror = 0;

		while ($obj = $db->fetch_object($res))
		{
			// We set here all data used into signature calculation (see checkSignature method) and more
			// IMPORTANT: We must have here, the same rule for transformation of data than into the fetch method (db->jdate for date, ...)
			$block_static->id = $obj->rowid;
			$block_static->date_creation = $db->jdate($obj->date_creation);
			$block_static->date_modification = $db->jdate($obj->tms);
			$block_static->action = $obj->action;
			$block_static->fk_object = $obj->fk_object;
			$block_static->element = $obj->element;
			$block_static->amounts = (double) $obj->amounts;
			$block_static->ref_object = $obj->ref_object;
			$block_static->date_object = $db->jdate($obj->date_object);
			$block_static->user_fullname = $obj->user_fullname;
			$block_static->fk_user = $obj->fk_user;
			$block_static->signature = $obj->signature;
			$block_static->object_data = unserialize($obj->object_data);

			$checksignature = $block_static->checkSignature($previoushash);	// If $previoushash is not defined, checkSignature will search it

			if ($checksignature)
			{
				if ($loweridinerror > 0) $statusofrecord = 'ValidButFoundAPreviousKO';
				else $statusofrecord = 'Valid';
			}
			else
			{
				$statusofrecord = 'KO';
				$loweridinerror = $obj->rowid;
			}

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
				.';'.$statusofrecord
				.';"'.str_replace('"','""',$obj->object_data).'"'
				."\n";

			// Set new previous hash for next fetch
			$previoushash = $obj->signature;
		}

		exit;
	}
	else
	{
		setEventMessage($db->lasterror, 'errors');
	}
}


/*
 *	View
 */

$form=new Form($db);

if (GETPOST('withtab','alpha'))
{
	$title=$langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog');
}
else
{
	$title=$langs->trans("BrowseBlockedLog");
}

llxHeader('',$langs->trans("BrowseBlockedLog"));

$blocks = $block_static->getLog('all', 0, GETPOST('all','alpha') ? 0 : 50, $sortfield, $sortorder, $search_fk_user, $search_start, $search_end, $search_ref, $search_amount, $search_code);
if (! is_array($blocks))
{
	dol_print_error($block_static->db);
	exit;
}

$linkback='';
if (GETPOST('withtab','alpha'))
{
	$linkback='<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php').'">'.$langs->trans("BackToModuleList").'</a>';
}

print load_fiche_titre($title, $linkback);

if (GETPOST('withtab','alpha'))
{
	$head=blockedlogadmin_prepare_head();
	dol_fiche_head($head, 'fingerprints', '', -1);
}

print '<span class="opacitymedium">'.$langs->trans("FingerprintsDesc")."</span><br>\n";

print '<br>';

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
if ($search_fk_user > 0) $param.='&search_fk_user='.urlencode($search_fk_user);
if ($search_start > 0)   $param.='&search_startyear='.urlencode(GETPOST('search_startyear','int')).'&search_startmonth='.urlencode(GETPOST('search_startmonth','int')).'&search_startday='.urlencode(GETPOST('search_startday','int'));
if ($search_end > 0)     $param.='&search_endyear='.urlencode(GETPOST('search_endyear','int')).'&search_endmonth='.urlencode(GETPOST('search_endmonth','int')).'&search_endday='.urlencode(GETPOST('search_endday','int'));
if ($optioncss != '')    $param.='&optioncss='.urlencode($optioncss);
if (GETPOST('withtab','alpha')) $param.='&withtab='.urlencode(GETPOST('withtab','alpha'));

// Add $param from extra fields
//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


print '<div align="right">';
print ' <a href="?all=1'.(GETPOST('withtab','alpha')?'&withtab='.GETPOST('withtab','alpha'):'').'">'.$langs->trans('ShowAllFingerPrintsMightBeTooLong').'</a>';
print ' | <a href="?all=1&showonlyerrors=1'.(GETPOST('withtab','alpha')?'&withtab='.GETPOST('withtab','alpha'):'').'">'.$langs->trans('ShowAllFingerPrintsErrorsMightBeTooLong').'</a>';
print ' | <a href="?action=downloadcsv'.(GETPOST('withtab','alpha')?'&withtab='.GETPOST('withtab','alpha'):'').'">'.$langs->trans('DownloadLogCSV').'</a>';
if (!empty($conf->global->BLOCKEDLOG_USE_REMOTE_AUTHORITY)) print ' | <a href="?action=downloadblockchain'.(GETPOST('withtab','alpha')?'&withtab='.GETPOST('withtab','alpha'):'').'">'.$langs->trans('DownloadBlockChain').'</a>';
print ' </div>';


print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="withtab" value="'.GETPOST('withtab','alpha').'">';

print '<table class="noborder" width="100%">';

// Line of filters
print '<tr class="liste_titre_filter">';

print '<td class="liste_titre">&nbsp;</td>';

print '<td class="liste_titre">';
//print $langs->trans("from").': ';
$form->select_date($search_start,'search_start');
//print '<br>';
//print $langs->trans("to").': ';
$form->select_date($search_end,'search_end');
print '</td>';

// User
print '<td class="liste_titre">';
print $form->select_dolusers($search_fk_user, 'search_fk_user', 1);
print '</td>';

// Actions code
$langs->load("blockedlog");
print '<td class="liste_titre">';
print $form->selectarray('search_code', $block_static->trackedevents, $search_code, 1, 0, 0, '', 1, 0, 0, 'ASC', 'maxwidth200', 1);
print '</td>';

// Ref
print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';

print '<td class="liste_titre"></td>';

// Amount
print '<td class="liste_titre right"><input type="text" class="maxwidth50" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';

print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';

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
print getTitleFieldOfList('<span id="blockchainstatus"></span>', 0, $_SERVER["PHP_SELF"],'','',$param,'align="center"',$sortfield,$sortorder,'')."\n";
print '</tr>';

if (! empty($conf->global->BLOCKEDLOG_SCAN_ALL_FOR_LOWERIDINERROR))
{
	// This is version that is faster memory but require more memory and report errors that are outside the filter range

	// TODO Make a full scan of table in reverse order of id of $block, so we can use the parameter $previoushash into checkSignature to save requests
	// to find the $loweridinerror.

}
else
{
	// This is version that optimize memory (but will not report error that are outside the filter range)
	$loweridinerror=0;
	$checkresult=array();
	foreach($blocks as &$block) {
		$checksignature = $block->checkSignature();	// Note: this make a sql request at each call, we can't avoid this as the sorting order is various
		$checkresult[$block->id]=$checksignature;	// false if error
		if (! $checksignature)
		{
			if (empty($loweridinerror)) $loweridinerror=$block->id;
			else $loweridinerror = min($loweridinerror, $block->id);
		}
	}
}

foreach($blocks as &$block) {
	$object_link = $block->getObjectLink();

	if (empty($showonlyerrors) || ! $checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror))
	{
	   	print '<tr class="oddeven">';
	   	// ID
	   	print '<td>'.$block->id.'</td>';
	   	// Date
	   	print '<td>'.dol_print_date($block->tms,'dayhour').'</td>';
		// User
	   	print '<td>';
	   	//print $block->getUser()
	   	print $block->user_fullname;
	   	print '</td>';
	   	// Action
	   	print '<td>'.$langs->trans('log'.$block->action).'</td>';
	   	// Ref
	   	print '<td>'.$block->ref_object.'</td>';
	   	// Link to source object
	   	print '<td>'.$object_link.'</td>';
	   	print '<td align="right">'.price($block->amounts).'</td>';
	   	print '<td align="center"><a href="#" data-blockid="'.$block->id.'" rel="show-info">'.img_info($langs->trans('ShowDetails')).'</a></td>';

	   	print '<td>';
	   	print $form->textwithpicto(dol_trunc($block->signature, '12'), $block->signature);
	   	print '</td>';

	   	print '<td class="center">';
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

			if (typeof value != "object") {
				$("#pop-info table tbody").append("<tr><td>"+prefix+x+"</td><td class=\"wordwrap\">"+value+"</td></tr>");
			}
			if ((typeof value === "object") && (value !== null)) {
				drawData(value, prefix+x+" &gt;&gt; ");
			}
		}
	}

})
</script>'."\n";


if(!empty($conf->global->BLOCKEDLOG_USE_REMOTE_AUTHORITY) && !empty($conf->global->BLOCKEDLOG_AUTHORITY_URL))
{
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

if (GETPOST('withtab','alpha'))
{
	dol_fiche_end();
}

print '<br><br>';

llxFooter();
$db->close();
