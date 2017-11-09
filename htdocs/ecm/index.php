<?php
/* Copyright (C) 2008-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * You can call this page with param module=medias to get a filemanager for medias.
 */

/**
 *	\file       htdocs/ecm/index.php
 *	\ingroup    ecm
 *	\brief      Main page for ECM section area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

// Load traductions files
$langs->loadLangs(array("ecm","companies","other","users","orders","propal","bills","contracts"));

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ecm', 0);

// Get parameters
$socid=GETPOST('socid','int');
$action=GETPOST('action','aZ09');
$section=GETPOST('section','int')?GETPOST('section','int'):GETPOST('section_id','int');
if (! $section) $section=0;
$section_dir=GETPOST('section_dir','alpha');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="fullname";

$ecmdir = new EcmDirectory($db);
if ($section)
{
	$result=$ecmdir->fetch($section);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmdir->error);
		exit;
	}
}

$form=new Form($db);
$ecmdirstatic = new EcmDirectory($db);
$userstatic = new User($db);

$error=0;


/*
 *	Actions
 */

// Upload file
if (GETPOST("sendit") && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	// Define relativepath and upload_dir
    $relativepath='';
	if ($ecmdir->id) $relativepath=$ecmdir->getRelativePath();
	else $relativepath=$section_dir;
	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;

	if (empty($_FILES['userfile']['tmp_name']))
	{
		$error++;
		if($_FILES['userfile']['error'] == 1 || $_FILES['userfile']['error'] == 2){
			setEventMessages($langs->trans('ErrorFileSizeTooLarge'),null, 'errors');
		}
		else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
		}
	}

	if (! $error)
	{
	    $res = dol_add_file_process($upload_dir, 0, 1, 'userfile', '', '', '', 0);
	    if ($res > 0)
	    {
	       $result=$ecmdir->changeNbOfFiles('+');
	    }
	}
}



// Add directory
if ($action == 'add' && $user->rights->ecm->setup)
{
	$ecmdir->ref                = 'NOTUSEDYET';
	$ecmdir->label              = GETPOST("label");
	$ecmdir->description        = GETPOST("desc");

	$id = $ecmdir->create($user);
	if ($id > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		setEventMessages('Error '.$langs->trans($ecmdir->error), null, 'errors');
		$action = "create";
	}

	clearstatcache();
}

// Remove file
if ($action == 'confirm_deletefile')
{
    if (GETPOST('confirm') == 'yes')
    {
    	// GETPOST('urlfile','alpha') is full relative URL from ecm root dir. Contains path of all sections.
		//var_dump(GETPOST('urlfile'));exit;

    	$upload_dir = $conf->ecm->dir_output.($relativepath?'/'.$relativepath:'');
    	$file = $upload_dir . "/" . GETPOST('urlfile','alpha');	// Do not use urldecode here ($_GET and $_POST are already decoded by PHP).
		//var_dump($file);exit;

    	$ret=dol_delete_file($file);	// This include also the delete from file index in database.
    	if ($ret)
    	{
    		setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile','alpha')), null, 'mesgs');
    		$result=$ecmdir->changeNbOfFiles('-');
    	}
    	else
    	{
    		setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile','alpha')), null, 'errors');
    	}

    	clearstatcache();
    }
   	$action='file_manager';
}

// Remove directory
if ($action == 'confirm_deletesection' && GETPOST('confirm') == 'yes')
{
	$result=$ecmdir->delete($user);
	setEventMessages($langs->trans("ECMSectionWasRemoved", $ecmdir->label), null, 'mesgs');

    clearstatcache();
}

// Refresh directory view
// This refresh list of dirs, not list of files (for preformance reason). List of files is refresh only if dir was not synchronized.
// To refresh content of dir with cache, just open the dir in edit mode.
if ($action == 'refreshmanual')
{
    $ecmdirtmp = new EcmDirectory($db);

	// This part of code is same than into file ecm/ajax/ecmdatabase.php TODO Remove duplicate
	clearstatcache();

    $diroutputslash=str_replace('\\','/',$conf->ecm->dir_output);
    $diroutputslash.='/';

    // Scan directory tree on disk
    $disktree=dol_dir_list($conf->ecm->dir_output,'directories',1,'','^temp$','','',0);

    // Scan directory tree in database
    $sqltree=$ecmdirstatic->get_full_arbo(0);

    $adirwascreated=0;

    // Now we compare both trees to complete missing trees into database
    //var_dump($disktree);
    //var_dump($sqltree);
    foreach($disktree as $dirdesc)    // Loop on tree onto disk
    {
        $dirisindatabase=0;
        foreach($sqltree as $dirsqldesc)
        {
            if ($conf->ecm->dir_output.'/'.$dirsqldesc['fullrelativename'] == $dirdesc['fullname'])
            {
                $dirisindatabase=1;
                break;
            }
        }

        if (! $dirisindatabase)
        {
            $txt="Directory found on disk ".$dirdesc['fullname'].", not found into database so we add it";
            dol_syslog($txt);
            //print $txt."<br>\n";

            // We must first find the fk_parent of directory to create $dirdesc['fullname']
            $fk_parent=-1;
            $relativepathmissing=str_replace($diroutputslash,'',$dirdesc['fullname']);
            $relativepathtosearchparent=$relativepathmissing;
            //dol_syslog("Try to find parent id for directory ".$relativepathtosearchparent);
            if (preg_match('/\//',$relativepathtosearchparent))
            //while (preg_match('/\//',$relativepathtosearchparent))
            {
                $relativepathtosearchparent=preg_replace('/\/[^\/]*$/','',$relativepathtosearchparent);
                $txt="Is relative parent path ".$relativepathtosearchparent." for ".$relativepathmissing." found in sql tree ?";
                dol_syslog($txt);
                //print $txt." -> ";
                $parentdirisindatabase=0;
                foreach($sqltree as $dirsqldesc)
                {
                    if ($dirsqldesc['fullrelativename'] == $relativepathtosearchparent)
                    {
                        $parentdirisindatabase=$dirsqldesc['id'];
                        break;
                    }
                }
                if ($parentdirisindatabase > 0)
                {
                    dol_syslog("Yes with id ".$parentdirisindatabase);
                    //print "Yes with id ".$parentdirisindatabase."<br>\n";
                    $fk_parent=$parentdirisindatabase;
                    //break;  // We found parent, we can stop the while loop
                }
                else
				{
                    dol_syslog("No");
                    //print "No<br>\n";
                }
            }
            else
           {
                dol_syslog("Parent is root");
                $fk_parent=0;   // Parent is root
            }

            if ($fk_parent >= 0)
            {
                $ecmdirtmp->ref                = 'NOTUSEDYET';
                $ecmdirtmp->label              = dol_basename($dirdesc['fullname']);
                $ecmdirtmp->description        = '';
                $ecmdirtmp->fk_parent          = $fk_parent;

                $txt="We create directory ".$ecmdirtmp->label." with parent ".$fk_parent;
                dol_syslog($txt);
                //print $ecmdirtmp->cachenbofdoc."<br>\n";exit;
                $id = $ecmdirtmp->create($user);
                if ($id > 0)
                {
                    $newdirsql=array('id'=>$id,
                                     'id_mere'=>$ecmdirtmp->fk_parent,
                                     'label'=>$ecmdirtmp->label,
                                     'description'=>$ecmdirtmp->description,
                                     'fullrelativename'=>$relativepathmissing);
                    $sqltree[]=$newdirsql; // We complete fulltree for following loops
                    //var_dump($sqltree);
                    $adirwascreated=1;
                }
                else
                {
                    dol_syslog("Failed to create directory ".$ecmdirtmp->label, LOG_ERR);
                }
            }
            else {
                $txt="Parent of ".$dirdesc['fullname']." not found";
                dol_syslog($txt);
                //print $txt."<br>\n";
            }
        }
    }

    // Loop now on each sql tree to check if dir exists
    foreach($sqltree as $dirdesc)    // Loop on each sqltree to check dir is on disk
    {
    	$dirtotest=$conf->ecm->dir_output.'/'.$dirdesc['fullrelativename'];
		if (! dol_is_dir($dirtotest))
		{
			$ecmdirtmp->id=$dirdesc['id'];
			$ecmdirtmp->delete($user,'databaseonly');
			//exit;
		}
    }

    $sql="UPDATE ".MAIN_DB_PREFIX."ecm_directories set cachenbofdoc = -1 WHERE cachenbofdoc < 0";	// If pb into cahce counting, we set to value -1 = "unknown"
    dol_syslog("sql = ".$sql);
    $db->query($sql);

    // If a directory was added, the fulltree array is not correctly completed and sorted, so we clean
    // it to be sure that fulltree array is not used without reloading it.
    if ($adirwascreated) $sqltree=null;
}



/*
 *	View
 */

// Define height of file area (depends on $_SESSION["dol_screenheight"])
//print $_SESSION["dol_screenheight"];
$maxheightwin=(isset($_SESSION["dol_screenheight"]) && $_SESSION["dol_screenheight"] > 466)?($_SESSION["dol_screenheight"]-136):660;	// Also into index_auto.php file

$moreheadcss='';
$moreheadjs='';

//$morejs=array();
$morejs=array('includes/jquery/plugins/blockUI/jquery.blockUI.js','core/js/blockUI.js');	// Used by ecm/tpl/enabledfiletreeajax.tpl.pgp
if (empty($conf->global->MAIN_ECM_DISABLE_JS)) $morejs[]="includes/jquery/plugins/jqueryFileTree/jqueryFileTree.js";

$moreheadjs.='<script type="text/javascript">'."\n";
$moreheadjs.='var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";
$moreheadjs.='</script>'."\n";

llxHeader($moreheadcss.$moreheadjs,$langs->trans("ECMArea"),'','','','',$morejs,'',0,0);

$head = ecm_prepare_dasboard_head('');
dol_fiche_head($head, 'index', $langs->trans("ECMArea").' - '.$langs->trans("ECMFileManager"), -1, '');


// Add filemanager component
$module='ecm';
include DOL_DOCUMENT_ROOT.'/ecm/tpl/filemanager.tpl.php';


/*
// Start container of all panels
?>
<!-- Begin div id="containerlayout" -->
<div id="containerlayout">
<div id="ecm-layout-north" class="toolbar largebutton">
<?php

// Start top panel, toolbar
print '<div class="inline-block toolbarbutton centpercent">';

// Toolbar
if ($user->rights->ecm->setup)
{
    print '<a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create" class="inline-block valignmiddle toolbarbutton" title="'.dol_escape_htmltag($langs->trans('ECMAddSection')).'">';
    print '<img class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/folder-new.png">';
    print '</a>';
}
else
{
    print '<a href="#" class="inline-block valignmiddle toolbarbutton" title="'.$langs->trans("NotAllowed").'">';
    print '<img class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/folder-new.png">';
    print '</a>';
}
$url=((! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))?'#':($_SERVER["PHP_SELF"].'?action=refreshmanual'.($module?'&amp;module='.$module:'').($section?'&amp;section='.$section:'')));
print '<a href="'.$url.'" class="inline-block valignmiddle toolbarbutton" title="'.dol_escape_htmltag($langs->trans('ReSyncListOfDir')).'">';
print '<img id="refreshbutton" class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/view-refresh.png">';
print '</a>';


// Start Add new file area
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

    $formfile=new FormFile($db);
	$formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, ($section?$section:-1), $user->rights->ecm->upload, 48, null, '', 0, '', 0, $nameforformuserfile);
}
else print '&nbsp;';

print '</div>';
// End Add new file area


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


if (empty($action) || $action == 'file_manager' || preg_match('/refresh/i',$action) || $action == 'delete')
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

        if (empty($module)) $module='ecm';

        $_POST['modulepart'] = $module;
        $_POST['openeddir'] = GETPOST('openeddir');
        $_POST['dir'] = empty($_POST['dir'])?'/':$_POST['dir'];

        // Show filemanager tree (will be filled by direct include of ajaxdirtree.php in mode noajax, this will return all dir - all levels - to show)
        print '<div id="filetree" class="ecmfiletree">';

        $mode='noajax';
        $url=DOL_URL_ROOT.'/ecm/index.php';
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
$url=DOL_URL_ROOT.'/ecm/index.php';
include_once DOL_DOCUMENT_ROOT.'/core/ajax/ajaxdirpreview.php';


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
*/

// End of page
dol_fiche_end();

llxFooter();

$db->close();
