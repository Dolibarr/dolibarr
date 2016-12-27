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

	$action=GETPOST("action");
    $file=urldecode(GETPOST('file'));
    $section=GETPOST("section");
    $module=GETPOST("module");
    $urlsource=GETPOST("urlsource");

    $sortfield = GETPOST("sortfield",'alpha');
    $sortorder = GETPOST("sortorder",'alpha');
    $page = GETPOST("page",'int');
    if ($page == -1) { $page = 0; }
    $offset = $conf->liste_limit * $page;
    $pageprev = $page - 1;
    $pagenext = $page + 1;
    if (! $sortorder) $sortorder="ASC";
    if (! $sortfield) $sortfield="name";

    $upload_dir = dirname(str_replace("../","/", $conf->ecm->dir_output.'/'.$file));

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
    $upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
}
if (empty($url)) $url=DOL_URL_ROOT.'/ecm/index.php';

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");

// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;

//print 'xxx'.$upload_dir;

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (preg_match('/\.\./',$upload_dir) || preg_match('/[<>|]/',$upload_dir))
{
    dol_syslog("Refused to deliver file ".$upload_dir);
    // Do no show plain path in shown error message
    dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$upload_dir));
    exit;
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
print '<!-- Page called with mode='.(isset($mode)?$mode:'').' type='.$type.' module='.$module.' url='.$url.' '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

$param=($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'');


// Dir scan
if ($type == 'directory')
{
    $formfile=new FormFile($db);

    $maxlengthname=40;
    $excludefiles = array('^SPECIMEN\.pdf$','^\.','(\.meta|_preview\.png)$','^temp$','^payments$','^CVS$','^thumbs$');
    $sorting = (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC);

    // Right area. If module is defined, we are in automatic ecm.
    $automodules = array('company', 'invoice', 'invoice_supplier', 'propal', 'order', 'order_supplier', 'contract', 'product', 'tax', 'project', 'fichinter', 'user', 'expensereport');

    // TODO change for multicompany sharing
    // Auto area for suppliers invoices
    if ($module == 'company') $upload_dir = $conf->societe->dir_output;
    // Auto area for suppliers invoices
    else if ($module == 'invoice') $upload_dir = $conf->facture->dir_output;
    // Auto area for suppliers invoices
    else if ($module == 'invoice_supplier')
    {
        $relativepath='facture';
        $upload_dir = $conf->fournisseur->dir_output.'/'.$relativepath;
    }
    // Auto area for customers orders
    else if ($module == 'propal') $upload_dir = $conf->propal->dir_output;
    // Auto area for customers orders
    else if ($module == 'order') $upload_dir = $conf->commande->dir_output;
    // Auto area for suppliers orders
    else if ($module == 'order_supplier')
    {
        $relativepath='commande';
        $upload_dir = $conf->fournisseur->dir_output.'/'.$relativepath;
    }
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

    if (in_array($module, $automodules))
    {
        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $filearray=dol_dir_list($upload_dir,"files",1,'', $excludefiles, $sortfield, $sorting,1);
        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname,$url);
    }
    //Manual area
    else
    {
        $relativepath=$ecmdir->getRelativePath();
        $upload_dir = $conf->ecm->dir_output.'/'.$relativepath;

        // If $section defined with value 0
        if ($section === '0')
        {
            $filearray=array();
        }
        else $filearray=dol_dir_list($upload_dir,"files",0,'',array('^\.','(\.meta|_preview\.png)$','^temp$','^CVS$'),$sortfield, $sorting,1);

        if ($section)
        {
            $param.='&section='.$section;
            $textifempty = $langs->trans('NoFileFound');
        }
        else if ($section === '0') $textifempty='<br><div align="center"><font class="warning">'.$langs->trans("DirNotSynchronizedSyncFirst").'</font></div><br>';
        else $textifempty=($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("ECMSelectASection"));

        $formfile->list_of_documents($filearray,'','ecm',$param,1,$relativepath,$user->rights->ecm->upload,1,$textifempty,$maxlengthname,'',$url);
    }
}


if ($section)
{
	$useajax=1;
	if (! empty($conf->dol_use_jmobile)) $useajax=0;
	if (empty($conf->use_javascript_ajax)) $useajax=0;
	if (! empty($conf->global->MAIN_ECM_DISABLE_JS)) $useajax=0;

	$param.=($param?'?':'').(preg_replace('/^&/','',$param));

	if ($useajax || $action == 'delete')
	{
		$urlfile='';
		if ($action == 'delete') $urlfile=GETPOST('urlfile');

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		$useglobalvars=1;
		$form = new Form($db);
		$formquestion=array(
			'urlfile'=>array('type'=>'hidden','value'=>$urlfile,'name'=>'urlfile'),
			'section'=>array('type'=>'hidden','value'=>$section,'name'=>'section')
		);
		print $form->formconfirm($url,$langs->trans("DeleteFile"),$langs->trans("ConfirmDeleteFile"),'confirm_deletefile',$formquestion,"no",($useajax?'deletefile':0));
	}

	if ($useajax)
	{
		// Enable jquery handlers on new generated HTML objects
		print '<script type="text/javascript">'."\n";
		print 'jQuery(document).ready(function() {'."\n";
		print 'jQuery(".deletefilelink").click(function(e) { jQuery("#urlfile").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-deletefile").dialog("open"); return false; });'."\n";
		print '});'."\n";
		print '</script>'."\n";
	}
}

// Close db if mode is not noajax
if ((! isset($mode) || $mode != 'noajax') && is_object($db)) $db->close();
