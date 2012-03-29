<?php
/* Copyright (C) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2010 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/ecm.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/treeview.lib.php");
require_once(DOL_DOCUMENT_ROOT."/ecm/class/ecmdirectory.class.php");

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

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
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


/*
 *	Actions
 */

// Upload file
if (GETPOST("sendit") && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	// Define relativepath and upload_dir
    $relativepath='';
	if ($ecmdir->id) $relativepath=$ecmdir->getRelativePath();
	else $relativepath=GETPOST('section_dir');
	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;

	if (dol_mkdir($upload_dir) >= 0)
	{
		$resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0, 0, $_FILES['userfile']['error']);
		if (is_numeric($resupload) && $resupload > 0)
		{
			//$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
			//print_r($_FILES);
			$result=$ecmdir->changeNbOfFiles('+');
		}
		else
		{
			$langs->load("errors");
			if ($resupload < 0)	// Unknown error
			{
				$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
			}
			else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
			{
				$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
			}
			else	// Known error
			{
				$mesg = '<div class="error">'.$langs->trans($resupload).'</div>';
			}
		}
	}
	else
	{
		$langs->load("errors");
		$mesg = '<div class="error">'.$langs->trans("ErrorFailToCreateDir",$upload_dir).'</div>';
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
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		$mesg='<div class="error">Error '.$langs->trans($ecmdir->error).'</div>';
		$action = "create";
	}

	clearstatcache();
}

// Remove file
if ($action == 'confirm_deletefile' && GETPOST('confirm') == 'yes')
{
	$result=$ecmdir->fetch($section);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmdir->error);
		exit;
	}
	$relativepath=$ecmdir->getRelativePath();
	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
	$file = $upload_dir . "/" . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

	$result=dol_delete_file($file);

	$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';

	$result=$ecmdir->changeNbOfFiles('-');
	$action='file_manager';

	clearstatcache();
}

// Remove directory
if ($action == 'confirm_deletesection' && GETPOST('confirm') == 'yes')
{
	$result=$ecmdir->delete($user);
	$mesg = '<div class="ok">'.$langs->trans("ECMSectionWasRemoved", $ecmdir->label).'</div>';

    clearstatcache();
}

// Refresh directory view
if ($action == 'refreshmanual')
{
    clearstatcache();

    $diroutputslash=str_replace('\\','/',$conf->ecm->dir_output);
    $diroutputslash.='/';

    // Scan directory tree on disk
    $disktree=dol_dir_list($conf->ecm->dir_output,'directories',1,'','','','',0);

    // Scan directory tree in database
    $sqltree=$ecmdirstatic->get_full_arbo(0);

    $adirwascreated=0;

    // Now we compare both trees to complete missing trees into database
    //var_dump($disktree);
    //var_dump($sqltree);
    foreach($disktree as $dirdesc)
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
                $fk_parent=0;   // Parent is root
            }

            if ($fk_parent >= 0)
            {
                $ecmdirtmp=new EcmDirectory($db);
                $ecmdirtmp->ref                = 'NOTUSEDYET';
                $ecmdirtmp->label              = basename($dirdesc['fullname']);
                $ecmdirtmp->description        = '';
                $ecmdirtmp->fk_parent          = $fk_parent;

                $txt="We create directory ".$ecmdirtmp->label." with parent ".$fk_parent;
                dol_syslog($txt);
                //print $txt."<br>\n";
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
            }
            else {
                $txt="Parent of ".$dirdesc['fullname']." not found";
                dol_syslog($txt);
                //print $txt."<br>\n";
            }
        }
    }

    // If a directory was added, the fulltree array is not correctly completed and sorted, so we clean
    // it to be sure that fulltree array is not used without reloading it.
    if ($adirwascreated) $sqltree=null;
}



/*
 *	View
 */

//print "xx".$_SESSION["dol_screenheight"];
$maxheightwin=(isset($_SESSION["dol_screenheight"]) && $_SESSION["dol_screenheight"] > 500)?($_SESSION["dol_screenheight"]-166):660;
$morejs=array();
if (! empty($conf->global->MAIN_ECM_TRY_JS)) $morejs=array("/filemanager/includes/jqueryFileTree/jqueryFileTree.js");    // TODO Move lib into includes
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
$moreheadjs="
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
        ,   north__size:        32
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

llxHeader($moreheadcss.$moreheadjs,$langs->trans("ECM"),'','','','',$morejs,'',0,0);


// Add sections to manage
$rowspan=0;
$sectionauto=array();
if ($conf->product->enabled || $conf->service->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'product', 'test'=>$conf->product->enabled, 'label'=>$langs->trans("ProductsAndServices"),     'desc'=>$langs->trans("ECMDocsByProducts")); }
if ($conf->societe->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'company', 'test'=>$conf->societe->enabled, 'label'=>$langs->trans("ThirdParties"), 'desc'=>$langs->trans("ECMDocsByThirdParties")); }
if ($conf->propal->enabled)      { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'propal',  'test'=>$conf->propal->enabled,  'label'=>$langs->trans("Prop"),    'desc'=>$langs->trans("ECMDocsByProposals")); }
if ($conf->contrat->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'contract','test'=>$conf->contrat->enabled, 'label'=>$langs->trans("Contracts"),    'desc'=>$langs->trans("ECMDocsByContracts")); }
if ($conf->commande->enabled)    { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'order',   'test'=>$conf->commande->enabled,'label'=>$langs->trans("CustomersOrders"),       'desc'=>$langs->trans("ECMDocsByOrders")); }
if ($conf->facture->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'invoice', 'test'=>$conf->facture->enabled, 'label'=>$langs->trans("CustomersInvoices"),     'desc'=>$langs->trans("ECMDocsByInvoices")); }
if ($conf->fournisseur->enabled) { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'order_supplier',   'test'=>$conf->fournisseur->enabled, 'label'=>$langs->trans("SuppliersOrders"),     'desc'=>$langs->trans("ECMDocsByOrders")); }
if ($conf->fournisseur->enabled) { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'invoice_supplier', 'test'=>$conf->fournisseur->enabled, 'label'=>$langs->trans("SuppliersInvoices"),   'desc'=>$langs->trans("ECMDocsByInvoices")); }
if ($conf->tax->enabled)         { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'tax', 'test'=>$conf->tax->enabled, 'label'=>$langs->trans("SocialContributions"),     'desc'=>$langs->trans("ECMDocsBySocialContributions")); }

print_fiche_titre($langs->trans("ECMArea").' - '.$langs->trans("ECMFileManager"));

print $langs->trans("ECMAreaDesc")."<br>";
print $langs->trans("ECMAreaDesc2")."<br>";
print "<br>\n";

// Confirm remove file
if ($action == 'delete')
{
	$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?section='.$section.'&urlfile='.urlencode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile','','',1);
	if ($ret == 'html') print '<br>';
}

dol_htmloutput_mesg($mesg);


// Start container of all panels
if ($conf->use_javascript_ajax)
{
?>
	<div id="containerlayout"> <!-- begin div id="containerlayout" -->
	<div id="ecm-layout-north" class="toolbar">
<?php
}
else
{
    print '<table class="border" width="100%">';
    print '<tr><td colspan="2" style="background: #FFFFFF" style="height: 34px !important">';
}
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
print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual'.($module?'&amp;module='.$module:'').($section?'&amp;section='.$section:'').'" class="toolbarbutton" title="'.dol_escape_htmltag($langs->trans('Refresh')).'">';
print '<img class="toolbarbutton" border="0" src="'.DOL_URL_ROOT.'/theme/common/view-refresh.png">';
print '</a>';

print '</div>';
// End top panel, toolbar
if ($conf->use_javascript_ajax)
{
?>
	</div>
    <div id="ecm-layout-west" class="hidden">
<?php
}
else
{
    print '</td></tr>';
    print '<tr>';
    print '<td width="40%" valign="top" style="background: #FFFFFF" rowspan="2">';
}
// Start left area



// Confirmation de la suppression d'une ligne categorie
if ($action == 'delete_section')
{
    $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?section='.$section, $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection','','',1);
    if ($ret == 'html') print '<br>';
}
// End confirm



if (empty($action) || $action == 'file_manager' || preg_match('/refresh/i',$action) || $action == 'delete')
{
	print '<table width="100%" class="nobordernopadding">';

	print '<tr class="liste_titre">';
    print '<td class="liste_titre" align="left" colspan="6">';
    print '&nbsp;'.$langs->trans("ECMSections");
	print '</td></tr>';

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
		print $form->textwithpicto($txt,$htmltooltip,1,0);
		print '</td>';
		print '</tr></table>';
		print '</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="center">';
		//print $form->textwithpicto('',$htmltooltip,1,0);
		print '</td>';
		//print '<td align="right">'.$langs->trans("ECMNbOfDocsSmall").' <a href="'.$_SERVER["PHP_SELF"].'?action=refreshauto">'.img_picto($langs->trans("Refresh"),'refresh').'</a></td>';
		print '</tr>';

		$sectionauto=dol_sort_array($sectionauto,'label','ASC',true,false);

		$nbofentries=0;
		$oldvallevel=0;
		foreach ($sectionauto as $key => $val)
		{
			if ($val['test'])    // If condition to show is ok
			{
				$var=false;

				print '<tr>';

				// Section
				print '<td align="left">';
				print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>';
				tree_showpad($sectionauto,$key);
				print '</td>';

				print '<td valign="top">';
				if ($val['module'] == GETPOST("module"))
				{
					$n=3;
					$ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop'.$n.'.gif','',1);
				}
				else
				{
					$n=3;
					$ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/plustop'.$n.'.gif','',1);
				}
				print '<a href="'.DOL_URL_ROOT.'/ecm/index.php?module='.$val['module'].'">';
				print $ref;
				print '</a>';
				print img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/folder.gif','',1);
				print '</td>';

				print '<td valign="middle">';
				print '<a href="'.DOL_URL_ROOT.'/ecm/index.php?module='.$val['module'].'">';
				print $val['label'];
				print '</a></td></tr></table>';
				print "</td>\n";

				// Nb of doc in dir
				print '<td align="right">&nbsp;</td>';

				// Nb of doc in subdir
				print '<td align="right">&nbsp;</td>';

				// Edit link
				print '<td align="right">&nbsp;</td>';

				// Add link
				print '<td align="right">&nbsp;</td>';

				// Info
				print '<td align="center">';
				$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
				$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionAuto").'<br>';
				$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$langs->trans("ECMTypeAuto").'<br>';
				$htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['desc'];
				print $form->textwithpicto('',$htmltooltip,1,"info");
				print '</td>';

				print "</tr>\n";

				if ($val['module'] == GETPOST('module'))    // We are on selected module
				{
					if (in_array($val['module'],array('product')))
					{
						$showonrightsize='featurenotyetavailable';
					}
				}

				$oldvallevel=$val['level'];
				$nbofentries++;
			}
		}
	}


	// Manual section
	$htmltooltip=$langs->trans("ECMAreaDesc2");


	// Root of manual section
	print '<tr><td>';
	print '<table class="nobordernopadding"><tr class="nobordernopadding">';
	print '<td align="left" width="24px">';
	print img_picto_common('','treemenu/base.gif');
	print '</td><td align="left">';
	$txt=$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionsManual").')';
	print $form->textwithpicto($txt,$htmltooltip,1,"info");
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

    if (! empty($conf->global->MAIN_ECM_TRY_JS))
    {
        print '<tr><td colspan="6" style="padding-left: 20px">';

    	// Show filemanager tree
	    print '<div id="filetree" class="ecmfiletree">';
	    print '</div>';

	    print '</td></tr>';

	    $openeddir='/';
        ?>

	   	<script type="text/javascript">

	    function loadandshowpreview(filedirname,section)
	    {
	        //alert('filename='+filename);
	        jQuery('#ecmfileview').empty();

	        url='<?php echo dol_buildpath('/core/ajax/ajaxdirpreview.php',1); ?>?action=preview&module=ecm&section='+section+'&file='+urlencode(filedirname);

	        jQuery.get(url, function(data) {
	            //alert('Load of url '+url+' was performed : '+data);
	            pos=data.indexOf("TYPE=directory",0);
	            //alert(pos);
	            if ((pos > 0) && (pos < 20))
	            {
	                filediractive=filedirname;    // Save current dirname
	                filetypeactive='directory';
	            }
	            else
	            {
	                filediractive=filedirname;    // Save current dirname
	                filetypeactive='file';
	            }
	            jQuery('#ecmfileview').append(data);
	        });
	    }

		jQuery(document).ready( function() {
    	    jQuery('#filetree').fileTree({ root: '<?php print dol_escape_js($openeddir); ?>',
            	script: '<?php echo DOL_URL_ROOT.'/core/ajax/ajaxdirtree.php?modulepart=ecm&openeddir='.urlencode($openeddir); ?>',
                folderEvent: 'click',
                multiFolder: false  },
                function(file) {
                	jQuery("#mesg").hide();
                    loadandshowpreview(file,0);
             	}
            );

		});

	    </script>
	    <?php

	    print $form->formconfirm('eeeee', $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', '', 'deletefile');
    }
    else
    {
    	// Load full tree
    	if (empty($sqltree)) $sqltree=$ecmdirstatic->get_full_arbo(0);

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
    		if (preg_match('/refresh/i',$_GET['action']))
    		{
    			$result=$ecmdirstatic->fetch($val['id']);
    			$ecmdirstatic->ref=$ecmdirstatic->label;

    			$result=$ecmdirstatic->refreshcachenboffile();
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

    			print '<tr>';

    			// Show tree graph pictos
    			print '<td align="left">';
    			print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>';
    			$resarray=tree_showpad($sqltree,$key);
    			$a=$resarray[0];
    			$nbofsubdir=$resarray[1];
    			$nboffilesinsubdir=$resarray[2];
    			print '</td>';

    			// Show picto
    			print '<td valign="top">';
    			//print $val['fullpath']."(".$showline.")";
    			$n='2';
    			if ($b == 0 || ! in_array($val['id'],$expandedsectionarray)) $n='3';
    			if (! in_array($val['id'],$expandedsectionarray)) $ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/plustop'.$n.'.gif','',1);
    			else $ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop'.$n.'.gif','',1);
    			if ($option == 'indexexpanded') $lien = '<a href="'.$_SERVER["PHP_SELF"].'?section='.$val['id'].'&amp;sectionexpand=false">';
    	    	if ($option == 'indexnotexpanded') $lien = '<a href="'.$_SERVER["PHP_SELF"].'?section='.$val['id'].'&amp;sectionexpand=true">';
    	    	//$newref=str_replace('_',' ',$ref);
    	    	$newref=$ref;
    	    	$lienfin='</a>';
    	    	print $lien.$newref.$lienfin;
    			if (! in_array($val['id'],$expandedsectionarray)) print img_picto($ecmdirstatic->ref,DOL_URL_ROOT.'/theme/common/treemenu/folder.gif','',1);
    			else print img_picto($ecmdirstatic->ref,DOL_URL_ROOT.'/theme/common/treemenu/folder-expanded.gif','',1);
    			print '</td>';

    			// Show link
    			print '<td valign="middle">';
    			if ($section == $val['id']) print ' <u>';
    			print $ecmdirstatic->getNomUrl(0,'index',32);
    			if ($section == $val['id']) print '</u>';
    			print '</td>';
    			print '<td>&nbsp;</td>';
    			print '</tr></table>';
    			print "</td>\n";

    			// Nb of docs
    			print '<td align="right">';
    			print $val['cachenbofdoc'];
    			print '</td>';
    			print '<td align="left">';
    			if ($nbofsubdir && $nboffilesinsubdir) print '<font color="#AAAAAA">+'.$nboffilesinsubdir.'</font> ';
    			print '</td>';

    			// Edit link
    			print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docmine.php?section='.$val['id'].'">'.img_view($langs->trans("Edit").' - '.$langs->trans("Show")).'</a></td>';

    			// Add link
    			//print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create&amp;catParent='.$val['id'].'">'.img_edit_add().'</a></td>';
    			print '<td align="right">&nbsp;</td>';

    			// Info
    			print '<td align="center">';
    			$userstatic->id=$val['fk_user_c'];
    			$userstatic->lastname=$val['login_c'];
    			$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
    			$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionManual").'<br>';
    			$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$userstatic->getNomUrl(1).'<br>';
    			$htmltooltip.='<b>'.$langs->trans("ECMCreationDate").'</b>: '.dol_print_date($val['date_c'],"dayhour").'<br>';
    			$htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['description'].'<br>';
    			$htmltooltip.='<b>'.$langs->trans("ECMNbOfFilesInDir").'</b>: '.$val['cachenbofdoc'].'<br>';
    			if ($nbofsubdir) $htmltooltip.='<b>'.$langs->trans("ECMNbOfFilesInSubDir").'</b>: '.$nboffilesinsubdir;
    			else $htmltooltip.='<b>'.$langs->trans("ECMNbOfSubDir").'</b>: '.$nbofsubdir.'<br>';
    			print $form->textwithpicto('',$htmltooltip,1,"info");
    			print "</td>";

    			print "</tr>\n";
    		}

    		$oldvallevel=$val['level'];
    		$nbofentries++;
    	}

    	// If nothing to show
    	if ($nbofentries == 0)
    	{
    		print '<tr>';
    		print '<td class="left"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
    		print '<td>'.img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop3.gif','',1).'</td>';
    		print '<td valign="middle">';
    		print $langs->trans("ECMNoDirecotyYet");
    		print '</td>';
    		print '<td>&nbsp;</td>';
    		print '</table></td>';
    		print '<td colspan="5">&nbsp;</td>';
    		print '</tr>';
    	}
    }


	print "</table>";
}


// End left banner
if ($conf->use_javascript_ajax)
{
?>
    </div>
    <div id="ecm-layout-center" class="hidden">
    <div class="pane-in ecm-in-layout-center">
    <div id="ecmfileview" class="ecmfileview">

<?php
}
else
{
    print '</td><td valign="top" style="background: #FFFFFF">';
}
// Start right panel


//if (empty($conf->global->MAIN_ECM_TRY_JS))
//{
    $mode='noajax';
    include_once(DOL_DOCUMENT_ROOT.'/core/ajax/ajaxdirpreview.php');
//}


// End right panel
if ($conf->use_javascript_ajax)
{
?>
	</div>
    </div>
    <div class="pane-in ecm-in-layout-south layout-padding valignmiddle">
<?php
}
else
{
    print '</td></tr>';
    print '<tr height="22">';
    print '<td>';
}
// Start Add new file area


// To attach new file
if (! empty($conf->global->MAIN_ECM_TRY_JS) || ! empty($section))
{
    $formfile=new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/ecm/index.php', 'none', 0, ($section?$section:-1), $user->rights->ecm->upload, 48);
}
else print '&nbsp;';



// End Add new file area
if ($conf->use_javascript_ajax)
{
?>
    </div>
    </div>
	</div> <!-- end div id="containerlayout" -->
<?php
}
else
{
    print '</td></tr>';
    print '</table>';
}
// End of page



llxFooter();

$db->close();
?>
