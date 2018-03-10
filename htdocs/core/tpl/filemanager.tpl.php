<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * Output code for the filemanager
 * $module must be defined ('ecm', 'medias', ...)
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page filemanager.tpl.php can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE core/tpl/filemanager.tpl.php -->
<!-- Doc of fileTree plugin at http://www.abeautifulsite.net/blog/2008/03/jquery-file-tree/ -->

<?php

require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

if (empty($module)) $module='ecm';

$permtoadd = 0;
$permtoupload = 0;
if ($module == 'ecm')
{
	$permtoadd = $user->rights->ecm->setup;
	$permtoupload = $user->rights->ecm->upload;
}
if ($module == 'medias')
{
	$permtoadd = ($user->rights->mailing->creer || $user->rights->website->write);
	$permtoupload = ($user->rights->mailing->creer || $user->rights->website->write);
}



// Confirm remove file (for non javascript users)
if (($action == 'delete' || $action == 'file_manager_delete') && empty($conf->use_javascript_ajax))
{
	// TODO Add website, pageid, filemanager if defined
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section.'&urlfile='.urlencode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile','','',1);
}

// Start container of all panels
?>
<!-- Begin div id="containerlayout" -->
<div id="containerlayout">
<div id="ecm-layout-north" class="toolbar largebutton">
<?php

// Start top panel, toolbar
print '<div class="inline-block toolbarbutton centpercent">';

// Toolbar
//if (preg_match('/\/ecm/', $_SERVER['PHP_SELF'])) {
//if ($module == 'ecm') {

	if ($permtoadd)
	{
	    print '<a href="'.DOL_URL_ROOT.'/ecm/dir_add_card.php?action=create&module='.urlencode($module).($website?'&website='.$website:'').($pageid?'&pageid='.$pageid:'').'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?file_manager=1&website='.$website.'&pageid='.$pageid).'" class="inline-block valignmiddle toolbarbutton" title="'.dol_escape_htmltag($langs->trans('ECMAddSection')).'">';
	    print '<img class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/folder-new.png">';
	    print '</a>';
	}
	else
	{
	    print '<a href="#" class="inline-block valignmiddle toolbarbutton" title="'.$langs->trans("NotAllowed").'">';
	    print '<img class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/folder-new.png">';
	    print '</a>';
	}
	if ($module == 'ecm')
	{
		$tmpurl=((! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))?'#':($_SERVER["PHP_SELF"].'?action=refreshmanual'.($module?'&amp;module='.$module:'').($section?'&amp;section='.$section:'')));
		print '<a href="'.$tmpurl.'" class="inline-block valignmiddle toolbarbutton" title="'.dol_escape_htmltag($langs->trans('ReSyncListOfDir')).'">';
		print '<img id="refreshbutton" class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/view-refresh.png">';
		print '</a>';
	}
//}

// Start "Add new file" area
$nameforformuserfile = 'formuserfileecm';

print '<div class="inline-block valignmiddle floatright">';

// To attach new file
if ((! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) || ! empty($section))
{
	if ((empty($section) || $section == -1) && ($module != 'medias'))
	{
		?>
		<script type="text/javascript">
    	jQuery(document).ready(function() {
			jQuery('#<?php echo $nameforformuserfile ?>').hide();
    	});
    	</script>
		<?php
	}

	$sectiondir=GETPOST('file','alpha');
	print '<!-- Start form to attach new file in filemanager.tpl.php sectionid='.$section.' sectiondir='.$sectiondir.' -->'."\n";
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
    $formfile=new FormFile($db);
    $formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, ($section?$section:-1), $permtoupload, 48, null, '', 0, '', 0, $nameforformuserfile, '', $sectiondir);
}
else print '&nbsp;';

print '</div>';
// End "Add new file" area


print '</div>';
// End top panel, toolbar

?>
</div>
<div id="ecm-layout-west" class="inline-block">
<?php
// Start left area


// Confirmation de la suppression d'une ligne categorie
if ($action == 'delete_section')
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section, $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection','','',1);
}
// End confirm


if (empty($action) || $action == 'editfile' || $action == 'file_manager' || preg_match('/refresh/i',$action) || $action == 'delete')
{
	print '<table width="100%" class="liste noborderbottom">'."\n";

	print '<!-- Title for manual directories -->'."\n";
	print '<tr class="liste_titre">'."\n";
    print '<th class="liste_titre" align="left" colspan="6">';
    print '&nbsp;'.$langs->trans("ECMSections");
	print '</th></tr>';

    $showonrightsize='';

	// Manual section
	$htmltooltip=$langs->trans("ECMAreaDesc2");

    if (! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))
    {
        print '<tr><td colspan="6">';

    	// Show filemanager tree (will be filled by call of ajax enablefiletreeajax.tpl.php that execute ajaxdirtree.php)
	    print '<div id="filetree" class="ecmfiletree"></div>';

	    if ($action == 'deletefile') print $form->formconfirm('eeeee', $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', '', 'deletefile');

	    print '</td></tr>';
    }
    else
    {
        print '<tr><td colspan="6" style="padding-left: 20px">';

        $_POST['modulepart'] = $module;
        $_POST['openeddir'] = GETPOST('openeddir');
        $_POST['dir'] = empty($_POST['dir'])?'/':$_POST['dir'];

        // Show filemanager tree (will be filled by direct include of ajaxdirtree.php in mode noajax, this will return all dir - all levels - to show)
        print '<div id="filetree" class="ecmfiletree">';

        $mode='noajax';
        if (empty($url)) $url=DOL_URL_ROOT.'/ecm/index.php';
        include DOL_DOCUMENT_ROOT.'/core/ajax/ajaxdirtree.php';

    	print '</div>';
    	print '</td></tr>';
    }


	print "</table>";
}


// End left panel
?>
</div>
<div id="ecm-layout-center" class="inline-block">
<div class="pane-in ecm-in-layout-center">
<div id="ecmfileview" class="ecmfileview">
<?php
// Start right panel


$mode='noajax';
if (empty($url)) $url=DOL_URL_ROOT.'/ecm/index.php';
include DOL_DOCUMENT_ROOT.'/core/ajax/ajaxdirpreview.php';


// End right panel
?>
</div>
</div>

</div>
</div> <!-- End div id="containerlayout" -->
<?php


if (! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) {
	include DOL_DOCUMENT_ROOT.'/ecm/tpl/enablefiletreeajax.tpl.php';
}

?>
<!-- END PHP TEMPLATE core/tpl/filemanager.tpl.php -->
