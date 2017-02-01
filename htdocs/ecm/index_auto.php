<?php
/* Copyright (C) 2008-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016      Alexandre Spangaro   <aspangaro@zendsi.com>
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
 *	\file       htdocs/ecm/index_auto.php
 *	\ingroup    ecm
 *	\brief      Main page for ECM section area
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
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
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
		//TODO: Translate
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
	    	$relativepath=$ecmdir->getRelativePath();
    	}
    	else $relativepath='';
    	$upload_dir = $conf->ecm->dir_output.($relativepath?'/'.$relativepath:'');
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
$maxheightwin=(isset($_SESSION["dol_screenheight"]) && $_SESSION["dol_screenheight"] > 466)?($_SESSION["dol_screenheight"]-136):660;	// Also into index.php file

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
        ,   resizable: false
            });
    });
</script>";

llxHeader($moreheadcss.$moreheadjs,$langs->trans("ECMArea"),'','','','',$morejs,'',0,0);


// Add sections to manage
$rowspan=0;
$sectionauto=array();
if (! empty($conf->global->ECM_AUTO_TREE_ENABLED))
{
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))	{ $langs->load("products"); $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'product', 'test'=>(! empty($conf->product->enabled) || ! empty($conf->service->enabled)), 'label'=>$langs->trans("ProductsAndServices"), 'desc'=>$langs->trans("ECMDocsByProducts")); }
	if (! empty($conf->societe->enabled))		{ $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'company', 'test'=>$conf->societe->enabled, 'label'=>$langs->trans("ThirdParties"), 'desc'=>$langs->trans("ECMDocsByThirdParties")); }
	if (! empty($conf->propal->enabled))		{ $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'propal',  'test'=>$conf->propal->enabled, 'label'=>$langs->trans("Prop"), 'desc'=>$langs->trans("ECMDocsByProposals")); }
	if (! empty($conf->contrat->enabled))		{ $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'contract','test'=>$conf->contrat->enabled, 'label'=>$langs->trans("Contracts"), 'desc'=>$langs->trans("ECMDocsByContracts")); }
	if (! empty($conf->commande->enabled))		{ $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'order',   'test'=>$conf->commande->enabled, 'label'=>$langs->trans("CustomersOrders"), 'desc'=>$langs->trans("ECMDocsByOrders")); }
	if (! empty($conf->facture->enabled))		{ $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'invoice', 'test'=>$conf->facture->enabled, 'label'=>$langs->trans("CustomersInvoices"), 'desc'=>$langs->trans("ECMDocsByInvoices")); }
	if (! empty($conf->fournisseur->enabled))	{ $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'order_supplier', 'test'=>$conf->fournisseur->enabled, 'label'=>$langs->trans("SuppliersOrders"), 'desc'=>$langs->trans("ECMDocsByOrders")); }
	if (! empty($conf->fournisseur->enabled))	{ $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'invoice_supplier', 'test'=>$conf->fournisseur->enabled, 'label'=>$langs->trans("SuppliersInvoices"), 'desc'=>$langs->trans("ECMDocsByInvoices")); }
	if (! empty($conf->tax->enabled))			{ $langs->load("compta"); $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'tax', 'test'=>$conf->tax->enabled, 'label'=>$langs->trans("SocialContributions"), 'desc'=>$langs->trans("ECMDocsBySocialContributions")); }
	if (! empty($conf->projet->enabled))		{ $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'project', 'test'=>$conf->projet->enabled, 'label'=>$langs->trans("Projects"), 'desc'=>$langs->trans("ECMDocsByProjects")); }
	if (! empty($conf->ficheinter->enabled))	{ $langs->load("interventions"); $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'fichinter', 'test'=>$conf->ficheinter->enabled, 'label'=>$langs->trans("Interventions"), 'desc'=>$langs->trans("ECMDocsByInterventions")); }
	if (! empty($conf->expensereport->enabled))	{ $langs->load("trips"); $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'expensereport', 'test'=>$conf->expensereport->enabled, 'label'=>$langs->trans("ExpenseReports"), 'desc'=>$langs->trans("ECMDocsByExpenseReports")); }
	$rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'user', 'test'=>1, 'label'=>$langs->trans("Users"), 'desc'=>$langs->trans("ECMDocsByUsers"));
}

//print load_fiche_titre($langs->trans("ECMArea").' - '.$langs->trans("ECMFileManager"));

$helptext1=''; $helptext2='';
$helptext1.=$langs->trans("ECMAreaDesc");
$helptext1.=$langs->trans("ECMAreaDesc2");
$helptext2.=$langs->trans("ECMAreaDesc");
$helptext2.=$langs->trans("ECMAreaDesc2");

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
dol_fiche_head($head, 'index_auto', $langs->trans("ECMArea").' - '.$langs->trans("ECMFileManager"), 1, '');


// Start container of all panels
?>
<div id="containerlayout"> <!-- begin div id="containerlayout" -->
<div id="ecm-layout-north" class="toolbar largebutton">
<?php

// Start top panel, toolbar
print '<div class="toolbarbutton">';

// Toolbar
$url=((! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))?'#':($_SERVER["PHP_SELF"].'?action=refreshmanual'.($module?'&amp;module='.$module:'').($section?'&amp;section='.$section:'')));
print '<a href="'.$url.'" class="toolbarbutton" title="'.dol_escape_htmltag($langs->trans('Refresh')).'">';
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

	print '<!-- Title for auto directories -->'."\n";
	print '<tr class="liste_titre">'."\n";
    print '<th class="liste_titre" align="left" colspan="6">';
    print '&nbsp;'.$langs->trans("ECMSections");
	print '</th></tr>';

    $showonrightsize='';
    // Auto section
	if (count($sectionauto))
	{
		$htmltooltip=$langs->trans("ECMAreaDesc2");

		// Root title line (Automatic section)
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding"><tr class="nobordernopadding">';
		print '<td align="left" width="24">';
		print img_picto_common('','treemenu/base.gif');
		print '</td><td align="left">';
		$txt=$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionsAuto").')';
		print $form->textwithpicto($txt, $htmltooltip, 1, 0);
		print '</td>';
		print '</tr></table>';
		print '</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="center">';
		print '</td>';
		print '</tr>';

		$sectionauto=dol_sort_array($sectionauto,'label','ASC',true,false);

		print '<tr>';
    	print '<td colspan="6" style="padding-left: 20px">';
	    print '<div id="filetreeauto" class="ecmfiletree"><ul class="ecmjqft">';

		$nbofentries=0;
		$oldvallevel=0;
		foreach ($sectionauto as $key => $val)
		{
			if (empty($val['test'])) continue;   // If condition to show is ok

			$var=false;

		    print '<li class="directory collapsed">';
			if (! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))
			{
			    print '<a class="fmdirlia jqft ecmjqft" href="'.$_SERVER["PHP_SELF"].'?module='.$val['module'].'">';
			    print $val['label'];
   			    print '</a>';
			}
			else
			{
			    print '<a class="fmdirlia jqft ecmjqft" href="'.$_SERVER["PHP_SELF"].'?module='.$val['module'].'">';
			    print $val['label'];
			    print '</a>';
			}

		    print '<div class="ecmjqft">';
		    // Info
		    $htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
		    $htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionAuto").'<br>';
		    $htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$langs->trans("ECMTypeAuto").'<br>';
		    $htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['desc'];
		    print $form->textwithpicto('', $htmltooltip, 1, 'info');
		    print '</div>';
		    print '</li>';

		    $nbofentries++;
		}

	    print '</ul></div></td></tr>';
	}

	print "</table>";
}


// End left banner
?>
</div>
<div id="ecm-layout-center" class="<?php echo $classviewhide; ?>">
<div class="pane-in ecm-in-layout-center">
<div id="ecmfileview" class="ecmfileview">
<?php
// Start right panel


$mode='noajax';
$url=DOL_URL_ROOT.'/ecm/index_auto.php';
include_once DOL_DOCUMENT_ROOT.'/core/ajax/ajaxdirpreview.php';


// End right panel
?>
</div>
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
