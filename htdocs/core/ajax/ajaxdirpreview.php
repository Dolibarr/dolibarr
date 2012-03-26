<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010	   Pierre Morin         <pierre.morin@auguria.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
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

if (! isset($mode) || $mode != 'noajax')
{
    require_once("../../main.inc.php");
    require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
    require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php');
    require_once(DOL_DOCUMENT_ROOT."/ecm/class/ecmdirectory.class.php");

    $action=GETPOST("action");
    $file=urldecode(GETPOST('file'));
    $section=GETPOST("section");
    $module=GETPOST("module");
    $urlsource=GETPOST("urlsource");

    $upload_dir = dirname(str_replace("../","/", $conf->ecm->dir_output.'/'.$file));

    $ecmdir = new EcmDirectory($db);
    $result=$ecmdir->fetch($section);
    if (! $result > 0)
    {
        dol_print_error($db,$ecmdir->error);
        exit;
    }
}
else
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

/*
 if ($action == 'remove_file')   // Remove a file
{
clearstatcache();

dol_syslog(__FILE__." remove $original_file $urlsource", LOG_DEBUG);

// This test should be useless. We keep it to find bug more easily
if (! file_exists($original_file_osencoded))
{
dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$_GET["file"]));
exit;
}

dol_delete_file($original_file);

dol_syslog(__FILE__." back to ".urldecode($urlsource), LOG_DEBUG);

header("Location: ".urldecode($urlsource));

return;
}
*/


/*
 * View
 */

if (! isset($mode) || $mode != 'noajax')
{
    // Ajout directives pour resoudre bug IE
    header('Cache-Control: Public, must-revalidate');
    header('Pragma: public');
}

$type='directory';

// This test if file exists should be useless. We keep it to find bug more easily
if (! dol_is_dir($upload_dir))
{
    $langs->load("install");
    dol_print_error(0,$langs->trans("ErrorDirDoesNotExists",$upload_dir));
    exit;
}

print '<!-- TYPE='.$type.' -->'."\n";
print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";


// Dir
if ($type == 'directory')
{
    $formfile=new FormFile($db);

    $param=($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'');
    $maxlengthname=40;

    // Right area
    if ($module == 'company')  // Auto area for suppliers invoices
    {
        $upload_dir = $conf->societe->dir_output;
        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^payments$','^CVS$','^thumbs$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }
    else if ($module == 'invoice')  // Auto area for suppliers invoices
    {
        $upload_dir = $conf->facture->dir_output;
        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^payments$','^CVS$','^thumbs$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }
    else if ($module == 'invoice_supplier')  // Auto area for suppliers invoices
    {
        $relativepath='facture';
        $upload_dir = $conf->fournisseur->dir_output.'/'.$relativepath;
        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^CVS$','^thumbs$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }
    else if ($module == 'propal')  // Auto area for customers orders
    {
        $upload_dir = $conf->propal->dir_output;
        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^payments$','^CVS$','^thumbs$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }
    else if ($module == 'order')  // Auto area for customers orders
    {
        $upload_dir = $conf->commande->dir_output;
        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^payments$','^CVS$','^thumbs$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }
    else if ($module == 'order_supplier')  // Auto area for suppliers orders
    {
        $relativepath='commande';
        $upload_dir = $conf->fournisseur->dir_output.'/'.$relativepath;
        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^payments$','^CVS$','^thumbs$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }
    else if ($module == 'contract')  // Auto area for suppliers invoices
    {
        $upload_dir = $conf->contrat->dir_output;
        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^CVS$','^thumbs$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }
    else if ($module == 'tax')  // Auto area for suppliers invoices
    {
        $upload_dir = $conf->tax->dir_output;
        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^CVS$','^thumbs$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&module='.$module;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("NoFileFound")));

        $formfile->list_of_autoecmfiles($upload_dir,$filearray,$module,$param,1,'',$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }
    else    // Manual area
    {

        $relativepath=$ecmdir->getRelativePath();
        $upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
        $filearray=dol_dir_list($upload_dir,"files",0,'',array('^\.','\.meta$','^temp$','^CVS$'),$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

        $param.='&section='.$section;
        $textifempty=($section?$langs->trans("NoFileFound"):($showonrightsize=='featurenotyetavailable'?$langs->trans("FeatureNotYetAvailable"):$langs->trans("ECMSelectASection")));

        $formfile->list_of_documents($filearray,'','ecm',$param,1,$relativepath,$user->rights->ecm->upload,1,$textifempty,$maxlengthname);
    }

}

if ((! isset($mode) || $mode != 'noajax') && ! empty($conf->global->MAIN_ECM_TRY_JS))
{
    // Enable jquery handlers on new generated HTML objects
    print "\n".'<script type="text/javascript">'."\n";
    print 'jQuery(".deletefilelink").click(function(e) { jQuery("#dialog-confirm-deletefile").dialog("open"); return false; });'."\n";
    print '</script>'."\n";

    if (is_object($db)) $db->close();
}

?>
