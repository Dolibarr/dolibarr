<?php
/* Copyright (C) 2008-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	\file       htdocs/ecm/index.php
 *	\ingroup    ecm
 *	\brief      Main page for ECM section area
 *	\author		Laurent Destailleur
 */

if (! defined('REQUIRE_JQUERY_LAYOUT'))  define('REQUIRE_JQUERY_LAYOUT','1');
if (! defined('REQUIRE_JQUERY_BLOCKUI')) define('REQUIRE_JQUERY_BLOCKUI', 1);

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");
$langs->load("users");
$langs->load("orders");
$langs->load("propal");
$langs->load("bills");
$langs->load("contracts");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ecm', 0);

// Get parameters
$socid=GETPOST('socid','int');
$action=GETPOST("action");
$section=GETPOST("section")?GETPOST("section","int"):GETPOST("section_id","int");
$module=GETPOST("module");
if (! $section) $section=0;
$section_dir=GETPOST('section_dir');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="fullname";
if ($module == 'invoice_supplier' && $sortfield == "fullname") $sortfield="level1name";

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
		if (dol_mkdir($upload_dir) >= 0)
		{
			$resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . dol_unescapefile($_FILES['userfile']['name']),0, 0, $_FILES['userfile']['error']);
			if (is_numeric($resupload) && $resupload > 0)
			{
				$result=$ecmdir->changeNbOfFiles('+');
			}
			else
			{
				$langs->load("errors");
				if ($resupload < 0)	// Unknown error
				{
					setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
				}
				else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
				{
					setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
				}
				else	// Known error
				{
					setEventMessages($langs->trans($resupload), null, 'errors');
				}
			}
		}
		else
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFailToCreateDir",$upload_dir), null, 'errors');
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
    	$langs->load("other");
    	if ($section)
    	{
	    	$result=$ecmdir->fetch($section);
	    	if (! ($result > 0))
	    	{
	    		dol_print_error($db,$ecmdir->error);
	    		exit;
	    	}
    	}
    	else $relativepath='';
    	$upload_dir = $conf->ecm->dir_output;
    	$file = $upload_dir . "/" . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_POST are already decoded by PHP).

    	$ret=dol_delete_file($file);
    	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
    	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');

    	$result=$ecmdir->changeNbOfFiles('-');

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

$morejs=array();
if (empty($conf->global->MAIN_ECM_DISABLE_JS)) $morejs=array("/includes/jquery/plugins/jqueryFileTree/jqueryFileTree.js");
$moreheadcss="
<!-- dol_screenheight=".$_SESSION["dol_screenheight"]." -->
<style type=\"text/css\">
    #containerlayout {
        height:     ".$maxheightwin."px;
        margin:     0 auto;
        width:      100%;
        min-width:  700px;
        _width:     700px; /* min-width for IE6 */
    }
</style>";
$moreheadjs=empty($conf->use_javascript_ajax)?"":"
<script type=\"text/javascript\">
    jQuery(document).ready(function () {
        jQuery('#containerlayout').layout({
        	name: \"ecmlayout\"
        ,   paneClass:    \"ecm-layout-pane\"
        ,   resizerClass: \"ecm-layout-resizer\"
        ,   togglerClass: \"ecm-layout-toggler\"
        ,   center__paneSelector:   \"#ecm-layout-center\"
        ,   north__paneSelector:    \"#ecm-layout-north\"
        ,   west__paneSelector:     \"#ecm-layout-west\"
        ,   resizable: true
        ,   north__size:        36
        ,   north__resizable:   false
        ,   north__closable:    false
        ,   west__size:         340
        ,   west__minSize:      280
        ,   west__slidable:     true
        ,   west__resizable:    true
        ,   west__togglerLength_closed: '100%'
        ,   useStateCookie:     true
            });

        jQuery('#ecm-layout-center').layout({
            center__paneSelector:   \".ecm-in-layout-center\"
        ,   south__paneSelector:    \".ecm-in-layout-south\"
        ,   resizable: false
        ,   south__minSize:      32
        ,   south__resizable:   false
        ,   south__closable:    false
            });
    });
</script>";

llxHeader($moreheadcss.$moreheadjs,$langs->trans("ECMArea"),'','','','',$morejs,'',0,0);


// Add sections to manage
$rowspan=0;
$sectionauto=array();
if (! empty($conf->global->ECM_AUTO_TREE_ENABLED))
{
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))     { $langs->load("products"); $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'product', 'test'=>(! empty($conf->product->enabled) || ! empty($conf->service->enabled)), 'label'=>$langs->trans("ProductsAndServices"),     'desc'=>$langs->trans("ECMDocsByProducts")); }
	if (! empty($conf->societe->enabled))     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'company', 'test'=>$conf->societe->enabled, 'label'=>$langs->trans("ThirdParties"), 'desc'=>$langs->trans("ECMDocsByThirdParties")); }
	if (! empty($conf->propal->enabled))      { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'propal',  'test'=>$conf->propal->enabled,  'label'=>$langs->trans("Prop"),    'desc'=>$langs->trans("ECMDocsByProposals")); }
	if (! empty($conf->contrat->enabled))     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'contract','test'=>$conf->contrat->enabled, 'label'=>$langs->trans("Contracts"),    'desc'=>$langs->trans("ECMDocsByContracts")); }
	if (! empty($conf->commande->enabled))    { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'order',   'test'=>$conf->commande->enabled,'label'=>$langs->trans("CustomersOrders"),       'desc'=>$langs->trans("ECMDocsByOrders")); }
	if (! empty($conf->facture->enabled))     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'invoice', 'test'=>$conf->facture->enabled, 'label'=>$langs->trans("CustomersInvoices"),     'desc'=>$langs->trans("ECMDocsByInvoices")); }
	if (! empty($conf->fournisseur->enabled)) { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'order_supplier',   'test'=>$conf->fournisseur->enabled, 'label'=>$langs->trans("SuppliersOrders"),     'desc'=>$langs->trans("ECMDocsByOrders")); }
	if (! empty($conf->fournisseur->enabled)) { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'invoice_supplier', 'test'=>$conf->fournisseur->enabled, 'label'=>$langs->trans("SuppliersInvoices"),   'desc'=>$langs->trans("ECMDocsByInvoices")); }
	if (! empty($conf->tax->enabled))         { $langs->load("compta"); $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'tax', 'test'=>$conf->tax->enabled, 'label'=>$langs->trans("SocialContributions"),     'desc'=>$langs->trans("ECMDocsBySocialContributions")); }
	if (! empty($conf->projet->enabled))      { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'project', 'test'=>$conf->projet->enabled, 'label'=>$langs->trans("Projects"),     'desc'=>$langs->trans("ECMDocsByProjects")); }
}

//print load_fiche_titre($langs->trans("ECMArea").' - '.$langs->trans("ECMFileManager"));

/*
print '<div class="hideonsmartphone">';
print $langs->trans("ECMAreaDesc")."<br>";
print $langs->trans("ECMAreaDesc2")."<br>";
print "<br>\n";
print '</div>';
*/

// Confirm remove file (for non javascript users)
if ($action == 'delete' && empty($conf->use_javascript_ajax))
{
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section.'&urlfile='.urlencode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile','','',1);

}

if (! empty($conf->use_javascript_ajax)) $classviewhide='hidden';
else $classviewhide='visible';


$head = ecm_prepare_dasboard_head('');
dol_fiche_head($head, 'index', $langs->trans("ECMArea").' - '.$langs->trans("ECMFileManager"), 1, '');

// Start container of all panels
?>
<div id="containerlayout"> <!-- begin div id="containerlayout" -->
<div id="ecm-layout-north" class="toolbar largebutton">
<?php

// Start top panel, toolbar
print '<div class="toolbarbutton">';

// Toolbar
if ($user->rights->ecm->setup)
{
    print '<a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create" class="toolbarbutton" title="'.dol_escape_htmltag($langs->trans('ECMAddSection')).'">';
    print '<img class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/folder-new.png">';
    print '</a>';
}
else
{
    print '<a href="#" class="toolbarbutton" title="'.$langs->trans("NotAllowed").'">';
    print '<img class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/folder-new.png">';
    print '</a>';
}
$relativeurl=((! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))?'#':($_SERVER["PHP_SELF"].'?action=refreshmanual'.($module?'&amp;module='.$module:'').($section?'&amp;section='.$section:'')));
print '<a href="'.$relativeurl.'" class="toolbarbutton" title="'.dol_escape_htmltag($langs->trans('Refresh')).'">';
print '<img id="refreshbutton" class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/view-refresh.png">';
print '</a>';

print '</div>';
// End top panel, toolbar

?>
</div>
<div id="ecm-layout-west" class="<?php echo $classviewhide; ?>">
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


	// Root of manual section
	print '<tr><td>';
	print '<table class="nobordernopadding"><tr class="nobordernopadding">';
	print '<td align="left" width="24px">';
	print img_picto_common('','treemenu/base.gif');
	print '</td><td align="left">';
	$txt=$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionsManual").')';
	print $form->textwithpicto($txt, $htmltooltip, 1, 'info');
	print '</td>';
	print '</tr></table></td>';
	print '<td align="right">';
	print '</td>';
	print '<td align="right">&nbsp;</td>';
	//print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create">'.img_edit_add().'</a></td>';
	print '<td align="right">&nbsp;</td>';
	print '<td align="right">&nbsp;</td>';
	print '<td align="center">';
	//print $form->textwithpicto('',$htmltooltip,1,"info");
	print '</td>';
	print '</tr>';

    if (! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))
    {
        print '<tr><td colspan="6" style="padding-left: 20px">';

    	// Show filemanager tree
	    print '<div id="filetree" class="ecmfiletree"></div>';

	    if ($action == 'deletefile') print $form->formconfirm('eeeee', $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', '', 'deletefile');

	    print '</td></tr>';
    }
    else
    {
        print '<tr><td colspan="6" style="padding-left: 20px">';
        print '<div id="filetree" class="ecmfiletree">';
        print '<ul class="ecmjqft">';

    	// Load full tree
    	if (empty($sqltree)) $sqltree=$ecmdirstatic->get_full_arbo(0);    // Slow

    	// ----- This section will show a tree from a fulltree array -----
    	// $section must also be defined
    	// ----------------------------------------------------------------

    	// Define fullpathselected ( _x_y_z ) of $section parameter
    	$fullpathselected='';
    	foreach($sqltree as $key => $val)
    	{
    		//print $val['id']."-".$section."<br>";
    		if ($val['id'] == $section)
    		{
    			$fullpathselected=$val['fullpath'];
    			break;
    		}
    	}
    	//print "fullpathselected=".$fullpathselected."<br>";

    	// Update expandedsectionarray in session
    	$expandedsectionarray=array();
    	if (isset($_SESSION['dol_ecmexpandedsectionarray'])) $expandedsectionarray=explode(',',$_SESSION['dol_ecmexpandedsectionarray']);

    	if ($section && GETPOST('sectionexpand') == 'true')
    	{
    		// We add all sections that are parent of opened section
    		$pathtosection=explode('_',$fullpathselected);
    		foreach($pathtosection as $idcursor)
    		{
    			if ($idcursor && ! in_array($idcursor,$expandedsectionarray))	// Not already in array
    			{
    				$expandedsectionarray[]=$idcursor;
    			}
    		}
    		$_SESSION['dol_ecmexpandedsectionarray']=join(',',$expandedsectionarray);
    	}
    	if ($section && GETPOST('sectionexpand') == 'false')
    	{
    		// We removed all expanded sections that are child of the closed section
    		$oldexpandedsectionarray=$expandedsectionarray;
    		$expandedsectionarray=array();	// Reset
    		foreach($oldexpandedsectionarray as $sectioncursor)
    		{
    			// is_in_subtree(fulltree,sectionparent,sectionchild)
    			if ($sectioncursor && ! is_in_subtree($sqltree,$section,$sectioncursor)) $expandedsectionarray[]=$sectioncursor;
    		}
    		$_SESSION['dol_ecmexpandedsectionarray']=join(',',$expandedsectionarray);
    	}
    	//print $_SESSION['dol_ecmexpandedsectionarray'].'<br>';

    	$nbofentries=0;
    	$oldvallevel=0;
    	$var=true;
    	foreach($sqltree as $key => $val)
    	{
    		$var=false;

    		$ecmdirstatic->id=$val['id'];
    		$ecmdirstatic->ref=$val['label'];

    		// Refresh cache
    		if (preg_match('/refresh/i',$action))
    		{
    			$result=$ecmdirstatic->fetch($val['id']);
    			$ecmdirstatic->ref=$ecmdirstatic->label;

    			$result=$ecmdirstatic->refreshcachenboffile(0);
    			$val['cachenbofdoc']=$result;
    		}

    		//$fullpathparent=preg_replace('/(_[^_]+)$/i','',$val['fullpath']);

    		// Define showline
    		$showline=0;

    		// If directory is son of expanded directory, we show line
    		if (in_array($val['id_mere'],$expandedsectionarray)) $showline=4;
    		// If directory is brother of selected directory, we show line
    		elseif ($val['id'] != $section && $val['id_mere'] == $ecmdirstatic->motherof[$section]) $showline=3;
    		// If directory is parent of selected directory or is selected directory, we show line
    		elseif (preg_match('/'.$val['fullpath'].'_/i',$fullpathselected.'_')) $showline=2;
    		// If we are level one we show line
    		elseif ($val['level'] < 2) $showline=1;

    		if ($showline)
    		{
    			if (in_array($val['id'],$expandedsectionarray)) $option='indexexpanded';
    			else $option='indexnotexpanded';
    			//print $option;

    			print '<li class="directory collapsed">';

    			// Show tree graph pictos
                $cpt=1;
    			while ($cpt < $sqltree[$key]['level'])
    			{
    			    print ' &nbsp; &nbsp;';
    			    $cpt++;
    			}
    			$resarray=tree_showpad($sqltree,$key,1);
    			$a=$resarray[0];
    			$nbofsubdir=$resarray[1];
    			$nboffilesinsubdir=$resarray[2];

    			// Show link
    			print $ecmdirstatic->getNomUrl(0,$option,32,'class="fmdirlia jqft ecmjqft"');

    			print '<div class="ecmjqft">';

    			// Nb of docs
    			print '<table class="nobordernopadding"><tr><td>';
    			print $val['cachenbofdoc'];
    			print '</td>';
    			print '<td align="left">';
    			if ($nbofsubdir && $nboffilesinsubdir) print '<font color="#AAAAAA">+'.$nboffilesinsubdir.'</font> ';
    			print '</td>';

    			// Info
    			print '<td align="center">';
    			$userstatic->id=$val['fk_user_c'];
    			$userstatic->lastname=$val['login_c'];
    			$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
    			$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionManual").'<br>';
    			$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$userstatic->getNomUrl(1, '', false, 1).'<br>';
    			$htmltooltip.='<b>'.$langs->trans("ECMCreationDate").'</b>: '.dol_print_date($val['date_c'],"dayhour").'<br>';
    			$htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['description'].'<br>';
    			$htmltooltip.='<b>'.$langs->trans("ECMNbOfFilesInDir").'</b>: '.$val['cachenbofdoc'].'<br>';
    			if ($nbofsubdir) $htmltooltip.='<b>'.$langs->trans("ECMNbOfFilesInSubDir").'</b>: '.$nboffilesinsubdir;
    			else $htmltooltip.='<b>'.$langs->trans("ECMNbOfSubDir").'</b>: '.$nbofsubdir.'<br>';
    			print $form->textwithpicto('', $htmltooltip, 1, 'info');
    			print "</td>";

    			print '</tr></table>';

    			print '</div>';

    			print "</li>\n";
    		}

    		$oldvallevel=$val['level'];
    		$nbofentries++;
    	}

    	// If nothing to show
    	if ($nbofentries == 0)
    	{
    		print '<li class="directory collapsed">';
    		print '<div class="ecmjqft">';
    		print $langs->trans("ECMNoDirectoryYet");
    		print '</div>';
   			print "</li>\n";
    	}

    	print '</ul>';
    	print '</div>';
    	print '</td></tr>';
    }


	print "</table>";
}


// End left panel
?>
</div>
<div id="ecm-layout-center" class="<?php echo $classviewhide; ?>">
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
<div class="pane-in ecm-in-layout-south layout-padding valignmiddle">
<?php
// Start Add new file area


// To attach new file
if ((! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) || ! empty($section))
{
	if (empty($section) || $section == -1)
	{
		?>
		<script type="text/javascript">
    	jQuery(document).ready(function() {
			jQuery('#formuserfile').hide();
    	});
    	</script>
		<?php
	}

    $formfile=new FormFile($db);
	$formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, ($section?$section:-1), $user->rights->ecm->upload, 48, null, '', 0, '', 0, 'formuserfile');
}
else print '&nbsp;';



// End Add new file area
?>
</div>
</div>
</div> <!-- end div id="containerlayout" -->
<?php
// End of page


dol_fiche_end();


if (! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) {
	include DOL_DOCUMENT_ROOT.'/ecm/tpl/enablefiletreeajax.tpl.php';
}



llxFooter();

$db->close();
