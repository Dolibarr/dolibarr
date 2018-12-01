<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010	   Pierre Morin         <pierre.morin@auguria.net>
 * Copyright (C) 2013      Marcos García        <marcosgdf@gmail.com>
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
 *	\file       htdocs/core/ajax/ajaxdirpreview.php
 *  \brief      Service to return a HTML preview of a directory
 *  			Call of this service is made with URL:
 * 				ajaxdirpreview.php?mode=nojs&action=preview&module=ecm&section=0&file=xxx
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');

if (! isset($mode) || $mode != 'noajax')    // For ajax call
{
    require_once '../../main.inc.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
    require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

    $action=GETPOST('action','aZ09');
    $file=urldecode(GETPOST('file','alpha'));
    $section=GETPOST("section",'alpha');
    $module=GETPOST("module",'alpha');
    $urlsource=GETPOST("urlsource",'alpha');
    $search_doc_ref=GETPOST('search_doc_ref','alpha');

    $sortfield = GETPOST("sortfield",'alpha');
    $sortorder = GETPOST("sortorder",'alpha');
    $page = GETPOST("page",'int');
    if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
    $offset = $conf->liste_limit * $page;
    $pageprev = $page - 1;
    $pagenext = $page + 1;
    if (! $sortorder) $sortorder="ASC";
    if (! $sortfield) $sortfield="name";

	$rootdirfordoc = $conf->ecm->dir_output;

	$upload_dir = dirname(str_replace("../", "/", $rootdirfordoc.'/'.$file));

    $ecmdir = new EcmDirectory($db);
    $result=$ecmdir->fetch($section);
    if (! $result > 0)
    {
        //dol_print_error($db,$ecmdir->error);
        //exit;
    }
}
else    // For no ajax call
{
	$rootdirfordoc = $conf->ecm->dir_output;

	$ecmdir = new EcmDirectory($db);
    $relativepath='';
    if ($section > 0)
    {
        $result=$ecmdir->fetch($section);
        if (! $result > 0)
        {
            dol_print_error($db,$ecmdir->error);
            exit;
        }
    }
    $relativepath=$ecmdir->getRelativePath();
    $upload_dir = $rootdirfordoc.'/'.$relativepath;
}

if (empty($url))
{
	if (GETPOSTISSET('website')) $url=DOL_URL_ROOT.'/website/index.php';
	else $url=DOL_URL_ROOT.'/ecm/index.php';
}

// Load traductions files
$langs->loadLangs(array("ecm","companies","other"));

// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;

//print 'xxx'.$upload_dir;

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans les noms de fichiers.
if (preg_match('/\.\./',$upload_dir) || preg_match('/[<>|]/',$upload_dir))
{
    dol_syslog("Refused to deliver file ".$upload_dir);
    // Do no show plain path in shown error message
    dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$upload_dir));
    exit;
}

// Check permissions
if ($modulepart == 'ecm')
{
	if (! $user->rights->ecm->read) accessforbidden();
}
if ($modulepart == 'medias')
{
	// Always allowed
}


/*
 * Action
 */

// None



/*
 * View
 */

if (! isset($mode) || $mode != 'noajax')
{
	// Ajout directives pour resoudre bug IE
    header('Cache-Control: Public, must-revalidate');
    header('Pragma: public');

    top_httphead();
}

$type='directory';

// This test if file exists should be useless. We keep it to find bug more easily
if (! dol_is_dir($upload_dir))
{
//	dol_mkdir($upload_dir);
/*    $langs->load("install");
    dol_print_error(0,$langs->trans("ErrorDirDoesNotExists",$upload_dir));
    exit;*/
}

print '<!-- ajaxdirpreview type='.$type.' -->'."\n";
//print '<!-- Page called with mode='.dol_escape_htmltag(isset($mode)?$mode:'').' type='.dol_escape_htmltag($type).' module='.dol_escape_htmltag($module).' url='.dol_escape_htmltag($url).' '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

$param=($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'');
if (! empty($website)) $param.='&website='.$website;
if (! empty($pageid))  $param.='&pageid='.$pageid;


// Dir scan
if ($type == 'directory')
{
    $formfile=new FormFile($db);

    $maxlengthname=40;
    $excludefiles = array('^SPECIMEN\.pdf$','^\.','(\.meta|_preview.*\.png)$','^temp$','^payments$','^CVS$','^thumbs$');
    $sorting = (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC);

    // Right area. If module is defined here, we are in automatic ecm.
    $automodules = array('company', 'invoice', 'invoice_supplier', 'propal', 'supplier_proposal', 'order', 'order_supplier', 'contract', 'product', 'tax', 'project', 'fichinter', 'user', 'expensereport', 'holiday');

    // TODO change for multicompany sharing
    // Auto area for suppliers invoices
    if ($module == 'company') $upload_dir = $conf->societe->dir_output;
    // Auto area for suppliers invoices
    else if ($module == 'invoice') $upload_dir = $conf->facture->dir_output;
    // Auto area for suppliers invoices
    else if ($module == 'invoice_supplier') $upload_dir = $conf->fournisseur->facture->dir_output;
    // Auto area for customers proposal
    else if ($module == 'propal') $upload_dir = $conf->propal->dir_output;
    // Auto area for suppliers proposal
    else if ($module == 'supplier_proposal') $upload_dir = $conf->supplier_proposal->dir_output;
    // Auto area for customers orders
    else if ($module == 'order') $upload_dir = $conf->commande->dir_output;
    // Auto area for suppliers orders
    else if ($module == 'order_supplier') $upload_dir = $conf->fournisseur->commande->dir_output;
    // Auto area for suppliers invoices
    else if ($module == 'contract') $upload_dir = $conf->contrat->dir_output;
    // Auto area for products
    else if ($module == 'product') $upload_dir = $conf->product->dir_output;
    // Auto area for suppliers invoices
    else if ($module == 'tax') $upload_dir = $conf->tax->dir_output;
    // Auto area for projects
    else if ($module == 'project') $upload_dir = $conf->projet->dir_output;
    // Auto area for interventions
    else if ($module == 'fichinter') $upload_dir = $conf->ficheinter->dir_output;
    // Auto area for users
    else if ($module == 'user') $upload_dir = $conf->user->dir_output;
    // Auto area for expense report
    else if ($module == 'expensereport') $upload_dir = $conf->expensereport->dir_output;
	// Auto area for holiday
    else if ($module == 'holiday') $upload_dir = $conf->holiday->dir_output;

    // Automatic list
    if (in_array($module, $automodules))
    {
        $param.='&module='.$module;
        if (isset($search_doc_ref) && $search_doc_ref != '') $param.='&search_doc_ref='.$search_doc_ref;

        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        if ($module == 'company') $excludefiles[]='^contact$';   // The subdir 'contact' contains files of contacts with no id of thirdparty.

        $filter=preg_quote($search_doc_ref, '/');
        $filearray=dol_dir_list($upload_dir, "files", 1, $filter, $excludefiles, $sortfield, $sorting,1);

        $perm=$user->rights->ecm->upload;

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$perm,1,$textifempty,$maxlengthname,$url,1);
    }
    // Manual list
    else
    {
    	if ($module == 'medias')
    	{
    		$relativepath=GETPOST('file','alpha');
    		if ($relativepath && $relativepath!= '/') $relativepath.='/';
    		$upload_dir = $dolibarr_main_data_root.'/'.$module.'/'.$relativepath;
    		if (GETPOSTISSET('website') || GETPOSTISSET('file_manager'))
	    	{
	    		$param.='&file_manager=1';
	    		if (!preg_match('/website=/',$param)) $param.='&website='.urlencode(GETPOST('website','alpha'));
	    		if (!preg_match('/pageid=/',$param)) $param.='&pageid='.urlencode(GETPOST('pageid','int'));
	    		//if (!preg_match('/backtopage=/',$param)) $param.='&backtopage='.urlencode($_SERVER["PHP_SELF"].'?file_manager=1&website='.$website.'&pageid='.$pageid);
	    	}
    	}
    	else
    	{
        	$relativepath=$ecmdir->getRelativePath();
        	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
    	}

        // If $section defined with value 0
		if (($section === '0' || empty($section)) && ($module != 'medias'))
        {
            $filearray=array();
        }
        else
        {
        	$filearray=dol_dir_list($upload_dir,"files",0,'',array('^\.','(\.meta|_preview.*\.png)$','^temp$','^CVS$'),$sortfield, $sorting,1);
        }

        if ($section)
        {
            $param.='&section='.$section;
        	if (isset($search_doc_ref) && $search_doc_ref != '') $param.='&search_doc_ref='.$search_doc_ref;

            $textifempty = $langs->trans('NoFileFound');
        }
        else if ($section === '0')
        {
        	if ($module == 'ecm') $textifempty='<br><div align="center"><font class="warning">'.$langs->trans("DirNotSynchronizedSyncFirst").'</font></div><br>';
        	else $textifempty = $langs->trans('NoFileFound');
        }
        else $textifempty=($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("ECMSelectASection"));

    	if ($module == 'medias')
    	{
    		$useinecm = 2;
    		$modulepart='medias';
        	$perm=($user->rights->website->write || $user->rights->emailing->creer);
        	$title='none';
    	}
    	else
    	{
    		$useinecm = 1;
    		$modulepart='ecm';
        	$perm=$user->rights->ecm->upload;
        	$title='';	// Use default
    	}

    	// When we show list of files for ECM files, $filearray contains file list, and directory is defined with modulepart + section into $param
    	// When we show list of files for a directory, $filearray ciontains file list, and directory is defined with modulepart + $relativepath
    	//var_dump("title=".$title." modulepart=".$modulepart." useinecm=".$useinecm." perm=".$perm." relativepath=".$relativepath." param=".$param." url=".$url);
		$formfile->list_of_documents($filearray, '', $modulepart, $param, 1, $relativepath, $perm, $useinecm, $textifempty, $maxlengthname, $title, $url, 0, $perm);
    }
}



// Bottom of page
$useajax=1;
if (! empty($conf->dol_use_jmobile)) $useajax=0;
if (empty($conf->use_javascript_ajax)) $useajax=0;
if (! empty($conf->global->MAIN_ECM_DISABLE_JS)) $useajax=0;

//$param.=($param?'?':'').(preg_replace('/^&/','',$param));

if ($useajax || $action == 'delete')
{
	$urlfile='';
	if ($action == 'delete') $urlfile=GETPOST('urlfile','alpha');

	if (empty($section_dir)) $section_dir=GETPOST("file","alpha");
	$section_id=$section;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	$useglobalvars=1;
	$form = new Form($db);
	$formquestion['urlfile']=array('type'=>'hidden','value'=>$urlfile,'name'=>'urlfile');				// We must always put field, even if empty because it is fille by javascript later
	$formquestion['section']=array('type'=>'hidden','value'=>$section,'name'=>'section');				// We must always put field, even if empty because it is fille by javascript later
	$formquestion['section_id']=array('type'=>'hidden','value'=>$section_id,'name'=>'section_id');		// We must always put field, even if empty because it is fille by javascript later
	$formquestion['section_dir']=array('type'=>'hidden','value'=>$section_dir,'name'=>'section_dir');	// We must always put field, even if empty because it is fille by javascript later
	if (! empty($action) && $action == 'file_manager')	$formquestion['file_manager']=array('type'=>'hidden','value'=>1,'name'=>'file_manager');
	if (! empty($website))								$formquestion['website']=array('type'=>'hidden','value'=>$website,'name'=>'website');
	if (! empty($pageid) && $pageid > 0)				$formquestion['pageid']=array('type'=>'hidden','value'=>$pageid,'name'=>'pageid');

	print $form->formconfirm($url,$langs->trans("DeleteFile"),$langs->trans("ConfirmDeleteFile"),'confirm_deletefile',$formquestion,"no",($useajax?'deletefile':0));
}

if ($useajax)
{
	print '<script type="text/javascript">';

	// Enable jquery handlers on new generated HTML objects (same code than into lib_footer.js.php)
	// Because the content is reloaded by ajax call, we must also reenable some jquery hooks
	// Wrapper to manage document_preview
	if ($conf->browser->layout != 'phone')
	{
		print "\n/* JS CODE TO ENABLE document_preview */\n";
		print '
                jQuery(document).ready(function () {
			        jQuery(".documentpreview").click(function () {
            		    console.log("We click on preview for element with href="+$(this).attr(\'href\')+" mime="+$(this).attr(\'mime\'));
            		    document_preview($(this).attr(\'href\'), $(this).attr(\'mime\'), \''.dol_escape_js($langs->transnoentities("Preview")).'\');
                		return false;
        			});
        		});
           ' . "\n";
	}

	// Enable jquery handlers button to delete files
	print 'jQuery(document).ready(function() {'."\n";
	print '  jQuery(".deletefilelink").click(function(e) { '."\n";
	print '    console.log("We click on button with class deletefilelink, param='.$param.', we set urlfile to "+jQuery(this).attr("rel"));'."\n";
	print '    jQuery("#urlfile").val(jQuery(this).attr("rel"));'."\n";
	//print '    jQuery("#section_dir").val(\'aaa\');'."\n";
	print '    jQuery("#dialog-confirm-deletefile").dialog("open");'."\n";
	print '    return false;'."\n";
	print '  });'."\n";
	print '});'."\n";
	print '</script>'."\n";
}

// Close db if mode is not noajax
if ((! isset($mode) || $mode != 'noajax') && is_object($db)) $db->close();
