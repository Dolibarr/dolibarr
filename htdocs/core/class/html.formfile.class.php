<?php
/* Copyright (c) 2008-2011 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Regis Houssin		<regis@dolibarr.fr>
 * Copyright (c) 2010      Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/core/class/html.formfile.class.php
 *  \ingroup    core
 *	\brief      File of class to offer components to list and upload files
 */


/**
 *	Class to offer components to list and upload files
 */
class FormFile
{
    var $db;
    var $error;

    var $numoffiles;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function FormFile($db)
    {
        $this->db = $db;
        $this->numoffiles=0;
        return 1;
    }


    /**
     *  Show form to upload a new file
	 *
     *  @param  string	$url			Url
     *  @param  string	$title			Title zone (Title or '' or 'none')
     *  @param  int		$addcancel		1=Add 'Cancel' button
     *	@param	int		$sectionid		If upload must be done inside a particular ECM section
     * 	@param	int		$perm			Value of permission to allow upload
     *  @param  int		$size           Length of input file area
     *  @param	Object	$object			Object to use (when attachment is done on an element)
     *  @param	string	$options		Options
     * 	@return	int						<0 if KO, >0 if OK
     */
    function form_attach_new_file($url, $title='', $addcancel=0, $sectionid=0, $perm=1, $size=50, $object='', $options='')
    {
        global $conf,$langs;

        if ($conf->global->MAIN_USE_JQUERY_FILEUPLOAD)
        {
            return $this->_formAjaxFileUpload($object);
        }
        else
        {
            $maxlength=$size;

            print "\n\n<!-- Start form attach new file -->\n";

            if (empty($title)) $title=$langs->trans("AttachANewFile");
            if ($title != 'none') print_titre($title);

            print '<form name="formuserfile" action="'.$url.'" enctype="multipart/form-data" method="POST">';
            print '<input type="hidden" id="formuserfile_section_dir" name="section_dir" value="">';
            print '<input type="hidden" id="formuserfile_section_id"  name="section_id" value="'.$sectionid.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

            print '<table width="100%" class="nobordernopadding">';
            print '<tr>';

            if (! empty($options)) print '<td>'.$options.'</td>';

            print '<td valign="middle" nowrap="nowrap">';

            $max=$conf->global->MAIN_UPLOAD_DOC;		// En Kb
            $maxphp=@ini_get('upload_max_filesize');	// En inconnu
            if (preg_match('/m$/i',$maxphp)) $maxphp=$maxphp*1024;
            if (preg_match('/k$/i',$maxphp)) $maxphp=$maxphp;
            // Now $max and $maxphp are in Kb
            if ($maxphp > 0) $max=min($max,$maxphp);

            if ($max > 0)
            {
                print '<input type="hidden" name="max_file_size" value="'.($max*1024).'">';
            }
            print '<input class="flat" type="file" name="userfile" size="'.$maxlength.'"';
            print (empty($conf->global->MAIN_UPLOAD_DOC) || empty($perm)?' disabled="disabled"':'');
            print '>';
            print ' &nbsp; ';
            print '<input type="submit" class="button" name="sendit" value="'.$langs->trans("Upload").'"';
            print (empty($conf->global->MAIN_UPLOAD_DOC) || empty($perm)?' disabled="disabled"':'');
            print '>';

            if ($addcancel)
            {
                print ' &nbsp; ';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
            }

            if (! empty($conf->global->MAIN_UPLOAD_DOC))
            {
                if ($perm)
                {
                    print ' ('.$langs->trans("MaxSize").': '.$max.' '.$langs->trans("Kb");
                    print ' '.info_admin($langs->trans("ThisLimitIsDefinedInSetup",$max,$maxphp),1);
                    print ')';
                }
            }
            else
            {
                print ' ('.$langs->trans("UploadDisabled").')';
            }
            print "</td></tr>";
            print "</table>";

            print '</form>';
            if (empty($sectionid)) print '<br>';

            print "\n<!-- End form attach new file -->\n\n";

            return 1;
        }
    }

    /**
     *      Show the box with list of available documents for object
     *
     *      @param      string				$modulepart         propal, facture, facture_fourn, ...
     *      @param      string				$filename           Sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if $filedir is already complete)
     *      @param      string				$filedir            Directory to scan
     *      @param      string				$urlsource          Url of origin page (for return)
     *      @param      int					$genallowed         Generation is allowed (1/0 or array of formats)
     *      @param      int					$delallowed         Remove is allowed (1/0)
     *      @param      string				$modelselected      Model to preselect by default
     *      @param      string				$allowgenifempty	Show warning if no model activated
     *      @param      string				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
     *      @param      int					$iconPDF            Show only PDF icon with link (1/0)
     * 		@param		int					$maxfilenamelength	Max length for filename shown
     * 		@param		string				$noform				Do not output html form tags
     * 		@param		string				$param				More param on http links
     * 		@param		string				$title				Title to show on top of form
     * 		@param		string				$buttonlabel		Label on submit button
     * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
     * 		@param		HookManager			$hookmanager		Object hookmanager with instance of external modules hook classes
     * 		@return		int										<0 if KO, number of shown files if OK
     */
    function show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$allowgenifempty=1,$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$noform=0,$param='',$title='',$buttonlabel='',$codelang='',$hookmanager=false)
    {
        $this->numoffiles=0;
        print $this->showdocuments($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed,$modelselected,$allowgenifempty,$forcenomultilang,$iconPDF,$maxfilenamelength,$noform,$param,$title,$buttonlabel,$codelang,$hookmanager);
        return $this->numoffiles;
    }

    /**
     *      Return a string to show the box with list of available documents for object.
     *      This also set the property $this->numoffiles
     *
     *      @param      string				$modulepart         propal, facture, facture_fourn, ...
     *      @param      string				$filename           Sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if $filedir is already complete)
     *      @param      string				$filedir            Directory to scan
     *      @param      string				$urlsource          Url of origin page (for return)
     *      @param      int					$genallowed         Generation is allowed (1/0 or array list of templates)
     *      @param      int					$delallowed         Remove is allowed (1/0)
     *      @param      string				$modelselected      Model to preselect by default
     *      @param      string				$allowgenifempty	Allow generation even if list of template ($genallowed) is empty (show however a warning)
     *      @param      string				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
     *      @param      int					$iconPDF            Show only PDF icon with link (1/0)
     * 		@param		int					$maxfilenamelength	Max length for filename shown
     * 		@param		string				$noform				Do not output html form tags
     * 		@param		string				$param				More param on http links
     * 		@param		string				$title				Title to show on top of form
     * 		@param		string				$buttonlabel		Label on submit button
     * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
     * 		@param		HookManager			$hookmanager		Object hookmanager with instance of external modules hook classes
     * 		@return		string              					Output string with HTML array of documents (might be empty string)
     */
    function showdocuments($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$allowgenifempty=1,$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$noform=0,$param='',$title='',$buttonlabel='',$codelang='',$hookmanager=false)
    {
        // filedir = conf->...dir_ouput."/".get_exdir(id)
        include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');

        global $langs,$bc,$conf;

        $forname='builddoc';
        $out='';
        $var=true;

        // Clean paramaters
        if ($iconPDF == 1)
        {
            $genallowed = '';
            $delallowed = 0;
            $modelselected = '';
            $forcenomultilang=0;
        }
        //$filename = dol_sanitizeFileName($filename);    //Must be sanitized before calling show_documents
        $headershown=0;
        $showempty=0;
        $i=0;

        $titletoshow=$langs->trans("Documents");
        if (! empty($title)) $titletoshow=$title;

        $out.= "\n".'<!-- Start show_document -->'."\n";
        //print 'filedir='.$filedir;

        // Affiche en-tete tableau
        if ($genallowed)
        {
            $modellist=array();

            if ($modulepart == 'company')
            {
                $showempty=1;
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php');
                    $modellist=ModeleThirdPartyDoc::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'propal')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php');
                    $modellist=ModelePDFPropales::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'commande')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php');
                    $modellist=ModelePDFCommandes::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'expedition')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php');
                    $modellist=ModelePDFExpedition::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'livraison')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/livraison/modules_livraison.php');
                    $modellist=ModelePDFDeliveryOrder::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'ficheinter')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php');
                    $modellist=ModelePDFFicheinter::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'facture')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
                    $modellist=ModelePDFFactures::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'project')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php');
                    $modellist=ModelePDFProjects::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'export')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php');
                    $modellist=ModeleExports::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'commande_fournisseur')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php');
                    $modellist=ModelePDFSuppliersOrders::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'facture_fournisseur')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php');
                    $modellist=ModelePDFSuppliersInvoices::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'remisecheque')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/cheque/pdf/modules_chequereceipts.php');
                    $modellist=ModeleChequeReceipts::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'donation')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php');
                    $modellist=ModeleDon::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'unpaid')
            {
                $modellist='';
            }
            else
            {
                // Generic feature, for external modules
                $file=dol_buildpath('/core/modules/'.$modulepart.'/modules_'.$modulepart.'.php',0);
                if (file_exists($file))
                {
                    $res=include_once($file);
                }
                $class='Modele'.ucfirst($modulepart);
                if (class_exists($class))
                {
                    $modellist=call_user_func($class.'::liste_modeles',$this->db);
                }
                else
                {
                    dol_print_error($this->db,'Bad value for modulepart');
                    return -1;
                }
            }

            $headershown=1;

            $form = new Form($this->db);
            $buttonlabeltoshow=$buttonlabel;
            if (empty($buttonlabel)) $buttonlabel=$langs->trans('Generate');

            if (empty($noform)) $out.= '<form action="'.$urlsource.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc').'" name="'.$forname.'" id="'.$forname.'_form" method="post">';
            $out.= '<input type="hidden" name="action" value="builddoc">';
            $out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

            $out.= '<div class="titre">'.$titletoshow.'</div>';
            $out.= '<table class="liste formdoc" summary="listofdocumentstable" width="100%">';

            $out.= '<tr class="liste_titre">';

            // Model
            if (! empty($modellist))
            {
                $out.= '<th align="center" class="formdoc liste_titre">';
                $out.= $langs->trans('Model').' ';
                if (is_array($modellist) && count($modellist) == 1)    // If there is only one element
                {
                    $arraykeys=array_keys($modellist);
                    $modelselected=$arraykeys[0];
                }
                $out.= $form->selectarray('model',$modellist,$modelselected,$showempty,0,0);
                $out.= '</th>';
            }
            else
            {
                $out.= '<th align="left" class="formdoc liste_titre">';
                $out.= $langs->trans("Files");
                $out.= '</th>';
            }

            // Language code (if multilang)
            $out.= '<th align="center" class="formdoc liste_titre">';
            if (($allowgenifempty || (is_array($modellist) && count($modellist) > 0)) && $conf->global->MAIN_MULTILANGS && ! $forcenomultilang)
            {
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');
                $formadmin=new FormAdmin($this->db);
                $defaultlang=$codelang?$codelang:$langs->getDefaultLang();
                $out.= $formadmin->select_language($defaultlang);
            }
            else
            {
                $out.= '&nbsp;';
            }
            $out.= '</th>';

            // Button
            $out.= '<th align="center" colspan="'.($delallowed?'2':'1').'" class="formdocbutton liste_titre">';
            $out.= '<input class="button" id="'.$forname.'_generatebutton"';
            $out.= ' type="submit" value="'.$buttonlabel.'"';
            if (! $allowgenifempty && ! is_array($modellist) && empty($modellist)) $out.= ' disabled="disabled"';
            $out.= '>';
            if ($allowgenifempty && ! is_array($modellist) && empty($modellist) && $modulepart != 'unpaid')
            {
                $langs->load("errors");
                $out.= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
            }
            $out.= '</th>';

            $out.= '</tr>';

            // Execute hooks
            $parameters=array('socid'=>$GLOBALS['socid'],'id'=>$GLOBALS['id'],'modulepart'=>$modulepart);
            if (is_object($hookmanager)) $out.= $hookmanager->executeHooks('formBuilddocOptions',$parameters,$GLOBALS['object']);
        }

        // Get list of files
        if ($filedir)
        {
            $png = '';
            $filter = '';
            if ($iconPDF==1)
            {
                $png = '\.png$';
                $filter = $filename.'.pdf';
            }
            $file_list=dol_dir_list($filedir,'files',0,$filter,'\.meta$'.($png?'|'.$png:''),'date',SORT_DESC);

            // Affiche en-tete tableau si non deja affiche
            if (! empty($file_list) && ! $headershown && ! $iconPDF)
            {
                $headershown=1;
                $out.= '<div class="titre">'.$titletoshow.'</div>';
                $out.= '<table class="border" summary="listofdocumentstable" width="100%">';
            }
            else if (empty($file_list) && ! empty($iconPDF))
            {
            	// For ajax treatment
            	$out.= '<div id="gen_pdf_'.$filename.'" class="linkobject hideobject">'.img_picto('', 'refresh').'</div>'."\n";
            }

            // Loop on each file found
            foreach($file_list as $file)
            {
                $var=!$var;

                // Define relative path for download link (depends on module)
                $relativepath=$file["name"];								// Cas general
                if ($filename) $relativepath=$filename."/".$file["name"];	// Cas propal, facture...
                // Autre cas
                if ($modulepart == 'donation')            { $relativepath = get_exdir($filename,2).$file["name"]; }
                if ($modulepart == 'export')              { $relativepath = $file["name"]; }

                if (! $iconPDF) $out.= "<tr ".$bc[$var].">";

                // Show file name with link to download
                if (! $iconPDF) $out.= '<td nowrap="nowrap">';
                $out.= '<a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'"';
                $mime=dol_mimetype($relativepath,'',0);
                if (preg_match('/text/',$mime)) $out.= ' target="_blank"';
                $out.= '>';
                if (! $iconPDF)
                {
                    $out.= img_mime($file["name"],$langs->trans("File").': '.$file["name"]).' '.dol_trunc($file["name"],$maxfilenamelength);
                }
                else
                {
                    $out.= img_pdf($file["name"],2);
                }
                $out.= '</a>'."\n";
                if (! $iconPDF)
                {
                	$out.= '</td>';
                	// Show file size
                	$out.= '<td align="right" nowrap="nowrap">'.dol_print_size(dol_filesize($filedir."/".$file["name"])).'</td>';
                	// Show file date
                	$out.= '<td align="right" nowrap="nowrap">'.dol_print_date(dol_filemtime($filedir."/".$file["name"]),'dayhour').'</td>';
                }

                if ($delallowed)
                {
                    $out.= '<td align="right">';
                    //$out.= '<a href="'.DOL_URL_ROOT.'/document.php?action=remove_file&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath);
                    $out.= '<a href="'.$urlsource.'&action=remove_file&modulepart='.$modulepart.'&file='.urlencode($relativepath);
                    $out.= ($param?'&'.$param:'');
                    $out.= '&urlsource='.urlencode($urlsource);
                    $out.= '">'.img_delete().'</a></td>';
                }

                if (! $iconPDF) $out.= '</tr>';

                $this->numoffiles++;
            }
        }

        if ($headershown)
        {
            // Affiche pied du tableau
            $out.= "</table>\n";
            if ($genallowed)
            {
                if (empty($noform)) $out.= '</form>'."\n";
            }
        }
        $out.= '<!-- End show_document -->'."\n";
        //return ($i?$i:$headershown);
        return $out;
    }


    /**
     *      Show list of documents in a directory
     *
     *      @param	array	$filearray          Array of files loaded by dol_dir_list('files') function before calling this
     * 		@param	Object	$object				Object on which document is linked to
     * 		@param	string	$modulepart			Value for modulepart used by download or viewimage wrapper
     * 		@param	string	$param				Parameters on sort links
     * 		@param	int		$forcedownload		Force to open dialog box "Save As" when clicking on file
     * 		@param	string	$relativepath		Relative path of docs (autodefined if not provided)
     * 		@param	int		$permtodelete		Permission to delete
     * 		@param	int		$useinecm			Change output for use in ecm module
     * 		@param	string	$textifempty		Text to show if filearray is empty ('NoFileFound' if not defined)
     *      @param  int		$maxlength          Maximum length of file name shown
     *      @param	string	$title				Title before list
     * 		@return	int							<0 if KO, nb of files shown if OK
     */
    function list_of_documents($filearray,$object,$modulepart,$param,$forcedownload=0,$relativepath='',$permtodelete=1,$useinecm=0,$textifempty='',$maxlength=0,$title='')
    {
        global $user, $conf, $langs;
        global $bc;
        global $sortfield, $sortorder, $maxheightmini;

        // Show list of existing files
        if (empty($useinecm)) print_titre($title?$title:$langs->trans("AttachedFiles"));
        //else { $bc[true]=''; $bc[false]=''; };
        $url=$_SERVER["PHP_SELF"];
        print '<table width="100%" class="'.($useinecm?'nobordernopadding':'liste').'">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans("Documents2"),$_SERVER["PHP_SELF"],"name","",$param,'align="left"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Size"),$_SERVER["PHP_SELF"],"size","",$param,'align="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"date","",$param,'align="center"',$sortfield,$sortorder);
        if (empty($useinecm)) print_liste_field_titre('',$_SERVER["PHP_SELF"],"","",$param,'align="center"');
        print_liste_field_titre('','','');
        print '</tr>';

        $nboffiles=count($filearray);

        if ($nboffiles > 0) include_once(DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php');

        $var=true;
        foreach($filearray as $key => $file)      // filearray must be only files here
        {
            if ($file['name'] != '.'
            && $file['name'] != '..'
            && $file['name'] != 'CVS'
            && ! preg_match('/\.meta$/i',$file['name']))
            {
                // Define relative path used to store the file
                if (! $relativepath) $relativepath=dol_sanitizeFileName($object->ref).'/';

                $var=!$var;
                print '<tr '.$bc[$var].'>';
                print '<td>';
                //print "XX".$file['name'];	//$file['name'] must be utf8
                print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
                if ($forcedownload) print '&attachment=1';
                if (! empty($object->entity)) print '&entity='.$object->entity;
                print '&file='.urlencode($relativepath.$file['name']).'">';
                print img_mime($file['name'],$file['name'].' ('.dol_print_size($file['size'],0,0).')').' ';
                print dol_trunc($file['name'],$maxlength,'middle');
                print '</a>';
                print "</td>\n";
                print '<td align="right">'.dol_print_size($file['size'],1,1).'</td>';
                print '<td align="center">'.dol_print_date($file['date'],"dayhour").'</td>';
                // Preview
                if (empty($useinecm))
                {
                    print '<td align="center">';
                    $tmp=explode('.',$file['name']);
                    $minifile=$tmp[0].'_mini.'.$tmp[1];
                    if (image_format_supported($file['name']) > 0) print '<img border="0" height="'.$maxheightmini.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($relativepath.'thumbs/'.$minifile).'" title="">';
                    else print '&nbsp;';
                    print '</td>';
                }
                // Delete or view link
                print '<td align="right">';
                if ($useinecm)     print '<a href="'.DOL_URL_ROOT.'/ecm/docfile.php?urlfile='.urlencode($file['name']).$param.'" class="editfilelink" rel="'.urlencode($file['name']).'">'.img_view().'</a> &nbsp; ';
                if ($permtodelete) print '<a href="'.(($useinecm && !empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))?'#':$url.'?id='.$object->id.'&action=delete&urlfile='.urlencode($file['name']).$param).'" class="deletefilelink" rel="'.urlencode($file['name']).'">'.img_delete().'</a>';
                else print '&nbsp;';
                print "</td>";
                print "</tr>\n";
            }
        }
        if ($nboffiles == 0)
        {
            print '<tr '.$bc[$var].'><td colspan="'.(empty($useinecm)?'5':'4').'">';
            if (empty($textifempty)) print $langs->trans("NoFileFound");
            else print $textifempty;
            print '</td></tr>';
        }
        print "</table>";

        return $nboffiles;
    }


    /**
     *      Show list of documents in a directory
     *
     *      @param	string	$upload_dir         Directory that was scanned
     *      @param  array	$filearray          Array of files loaded by dol_dir_list function before calling this function
     *      @param  string	$modulepart         Value for modulepart used by download wrapper
     *      @param  string	$param              Parameters on sort links
     *      @param  int		$forcedownload      Force to open dialog box "Save As" when clicking on file
     *      @param  string	$relativepath       Relative path of docs (autodefined if not provided)
     *      @param  int		$permtodelete       Permission to delete
     *      @param  int		$useinecm           Change output for use in ecm module
     *      @param  int		$textifempty        Text to show if filearray is empty
     *      @param  int		$maxlength          Maximum length of file name shown
     *      @return int                 		<0 if KO, nb of files shown if OK
     */
    function list_of_autoecmfiles($upload_dir,$filearray,$modulepart,$param,$forcedownload=0,$relativepath='',$permtodelete=1,$useinecm=0,$textifempty='',$maxlength=0)
    {
        global $user, $conf, $langs;
        global $bc;
        global $sortfield, $sortorder;

        dol_syslog(get_class($this).'::list_of_autoecmfiles upload_dir='.$upload_dir.' modulepart='.$modulepart);

        // Affiche liste des documents existant
        if (empty($useinecm)) print_titre($langs->trans("AttachedFiles"));
        //else { $bc[true]=''; $bc[false]=''; };
        $url=$_SERVER["PHP_SELF"];
        print '<table width="100%" class="nobordernopadding">';
        print '<tr class="liste_titre">';
        $sortref="fullname";
        if ($modulepart == 'invoice_supplier') $sortref='';    // No sort for supplier invoices as path name is not
        print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],$sortref,"",$param,'align="left"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Documents2"),$_SERVER["PHP_SELF"],"name","",$param,'align="left"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Size"),$_SERVER["PHP_SELF"],"size","",$param,'align="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"date","",$param,'align="center"',$sortfield,$sortorder);
        print_liste_field_titre('','','');
        print '</tr>';

        // To show ref or specific information according to view to show (defined by $module)
        if ($modulepart == 'company')
        {
            include_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
            $object_instance=new Societe($this->db);
        }
        else if ($modulepart == 'invoice')
        {
            include_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
            $object_instance=new Facture($this->db);
        }
        else if ($modulepart == 'invoice_supplier')
        {
            include_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
            $object_instance=new FactureFournisseur($this->db);
        }
        else if ($modulepart == 'propal')
        {
            include_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
            $object_instance=new Propal($this->db);
        }
        else if ($modulepart == 'order')
        {
            include_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
            $object_instance=new Commande($this->db);
        }
        else if ($modulepart == 'order_supplier')
        {
            include_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');
            $object_instance=new CommandeFournisseur($this->db);
        }
        else if ($modulepart == 'contract')
        {
            include_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
            $object_instance=new Contrat($this->db);
        }
        else if ($modulepart == 'tax')
        {
            include_once(DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php');
            $object_instance=new ChargeSociales($this->db);
        }

        $var=true;
        foreach($filearray as $key => $file)
        {
            if (!is_dir($file['name'])
            && $file['name'] != '.'
            && $file['name'] != '..'
            && $file['name'] != 'CVS'
            && ! preg_match('/\.meta$/i',$file['name']))
            {
                // Define relative path used to store the file
                $relativefile=preg_replace('/'.preg_quote($upload_dir.'/','/').'/','',$file['fullname']);

                //var_dump($file);
                $id=0; $ref=''; $label='';

                // To show ref or specific information according to view to show (defined by $module)
                if ($modulepart == 'company')          { preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=$reg[1]; }
                if ($modulepart == 'invoice')          { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1]; }
                if ($modulepart == 'invoice_supplier') { preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=$reg[1]; }
                if ($modulepart == 'propal')           { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1]; }
                if ($modulepart == 'order')            { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1]; }
                if ($modulepart == 'order_supplier')   { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1]; }
                if ($modulepart == 'contract')         { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1]; }
                if ($modulepart == 'tax')              { preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=$reg[1]; }

                if (! $id && ! $ref) continue;

                $found=0;
                if (! empty($this->cache_objects[$modulepart.'_'.$id.'_'.$ref]))
                {
                    $found=1;
                }
                else
                {
                    //print 'Fetch '.$idorref.'<br>';
                    $result=$object_instance->fetch($id,$ref);
                    if ($result > 0)  { $found=1; $this->cache_objects[$modulepart.'_'.$id.'_'.$ref]=dol_clone($object_instance); }    // Save object into a cache
                    if ($result == 0) { $found=1; $this->cache_objects[$modulepart.'_'.$id.'_'.$ref]='notfound'; }
                }

                if (! $found > 0 || ! is_object($this->cache_objects[$modulepart.'_'.$id.'_'.$ref])) continue;    // We do not show orphelins files

                $var=!$var;
                print '<tr '.$bc[$var].'>';
                print '<td>';
                if ($found > 0 && is_object($this->cache_objects[$modulepart.'_'.$id.'_'.$ref])) print $this->cache_objects[$modulepart.'_'.$id.'_'.$ref]->getNomUrl(1,'document');
                else print $langs->trans("ObjectDeleted",($id?$id:$ref));
                print '</td>';
                print '<td>';
                //print "XX".$file['name']; //$file['name'] must be utf8
                print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
                if ($forcedownload) print '&attachment=1';
                print '&file='.urlencode($relativefile).'">';
                print img_mime($file['name'],$file['name'].' ('.dol_print_size($file['size'],0,0).')').' ';
                print dol_trunc($file['name'],$maxlength,'middle');
                print '</a>';
                print "</td>\n";
                print '<td align="right">'.dol_print_size($file['size'],1,1).'</td>';
                print '<td align="center">'.dol_print_date($file['date'],"dayhour").'</td>';
                print '<td align="right">';
                if (! empty($useinecm))  print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
                if ($forcedownload) print '&attachment=1';
                print '&file='.urlencode($relativefile).'">';
                print img_view().'</a> &nbsp; ';
                //if ($permtodelete) print '<a href="'.$url.'?id='.$object->id.'&section='.$_REQUEST["section"].'&action=delete&urlfile='.urlencode($file['name']).'">'.img_delete().'</a>';
                //else print '&nbsp;';
                print "</td></tr>\n";
            }
        }
        if (count($filearray) == 0)
        {
            print '<tr '.$bc[$var].'><td colspan="4">';
            if (empty($textifempty)) print $langs->trans("NoFileFound");
            else print $textifempty;
            print '</td></tr>';
        }
        print "</table>";
        // Fin de zone
    }

    /**
     *    Show form to upload a new file with jquery fileupload.
     *    This form use the fileupload.php file.
     *
     *    @param	Object	$object		Object to use
     *    @return	void
     */
    private function _formAjaxFileUpload($object)
    {
        global $langs;

        // PHP post_max_size
        $post_max_size				= ini_get('post_max_size');
        $mul_post_max_size			= substr($post_max_size, -1);
        $mul_post_max_size			= ($mul_post_max_size == 'M' ? 1048576 : ($mul_post_max_size == 'K' ? 1024 : ($mul_post_max_size == 'G' ? 1073741824 : 1)));
        $post_max_size				= $mul_post_max_size * (int) $post_max_size;
        // PHP upload_max_filesize
        $upload_max_filesize		= ini_get('upload_max_filesize');
        $mul_upload_max_filesize	= substr($upload_max_filesize, -1);
        $mul_upload_max_filesize	= ($mul_upload_max_filesize == 'M' ? 1048576 : ($mul_upload_max_filesize == 'K' ? 1024 : ($mul_upload_max_filesize == 'G' ? 1073741824 : 1)));
        $upload_max_filesize		= $mul_upload_max_filesize * (int) $upload_max_filesize;
        // Max file size
        $max_file_size 				= (($post_max_size < $upload_max_filesize) ? $post_max_size : $upload_max_filesize);

        // Include main
        include(DOL_DOCUMENT_ROOT.'/core/tpl/ajax/fileupload_main.tpl.php');

        // Include template
        include(DOL_DOCUMENT_ROOT.'/core/tpl/ajax/fileupload_view.tpl.php');

    }

}

?>
