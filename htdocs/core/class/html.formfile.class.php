<?php
/* Copyright (C) 2008-2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2016	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Charles-Fr BENKE	<charles.fr@benke.fr>
 * Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes		<bafbes@gmail.com>
 * Copyright (C) 2016		Ferran Marcet		<fmarcet@2byte.es>

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
	var $infofiles;			// Used to return informations by function getDocumentsLink


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
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
     *  @param  int		$size           Length of input file area. Deprecated.
     *  @param	Object	$object			Object to use (when attachment is done on an element)
     *  @param	string	$options		Add an option column
     *  @param	integer	$useajax		Use fileupload ajax (0=never, 1=if enabled, 2=always whatever is option). 2 should never be used.
     *  @param	string	$savingdocmask	Mask to use to define output filename. For example 'XXXXX-__YYYYMMDD__-__file__'
     *  @param	integer	$linkfiles		1=Also add form to link files, 0=Do not show form to link files
     *  @param	string	$htmlname		Name and id of HTML form
     * 	@return	int						<0 if KO, >0 if OK
     */
    function form_attach_new_file($url, $title='', $addcancel=0, $sectionid=0, $perm=1, $size=50, $object='', $options='', $useajax=1, $savingdocmask='', $linkfiles=1, $htmlname='formuserfile')
    {
        global $conf,$langs, $hookmanager;
        $hookmanager->initHooks(array('formfile'));


        if (! empty($conf->browser->layout) && $conf->browser->layout != 'classic') $useajax=0;

		if ((! empty($conf->global->MAIN_USE_JQUERY_FILEUPLOAD) && $useajax) || ($useajax==2))
        {
        	// TODO: Check this works with 2 forms on same page
        	// TODO: Check this works with GED module, otherwise, force useajax to 0
        	// TODO: This does not support option savingdocmask
        	// TODO: This break feature to upload links too
        	return $this->_formAjaxFileUpload($object);
        }
        else
       	{
	        //If there is no permission and the option to hide unauthorized actions is enabled, then nothing is printed
	        if (!$perm && !empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)) {
		        return 1;
	        }

            $maxlength=$size;

            $out = "\n\n<!-- Start form attach new file -->\n";

            if (empty($title)) $title=$langs->trans("AttachANewFile");
            if ($title != 'none') $out.=load_fiche_titre($title, null, null);

            $out .= '<form name="'.$htmlname.'" id="'.$htmlname.'" action="'.$url.'" enctype="multipart/form-data" method="POST">';
            $out .= '<input type="hidden" id="'.$htmlname.'_section_dir" name="section_dir" value="">';
            $out .= '<input type="hidden" id="'.$htmlname.'_section_id"  name="section_id" value="'.$sectionid.'">';
            $out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

            $out .= '<table width="100%" class="nobordernopadding">';
            $out .= '<tr>';

            if (! empty($options)) $out .= '<td>'.$options.'</td>';

            $out .= '<td valign="middle">';

            $max=$conf->global->MAIN_UPLOAD_DOC;		// En Kb
            $maxphp=@ini_get('upload_max_filesize');	// En inconnu
            if (preg_match('/k$/i',$maxphp)) $maxphp=$maxphp*1;
            if (preg_match('/m$/i',$maxphp)) $maxphp=$maxphp*1024;
            if (preg_match('/g$/i',$maxphp)) $maxphp=$maxphp*1024*1024;
            if (preg_match('/t$/i',$maxphp)) $maxphp=$maxphp*1024*1024*1024;
            // Now $max and $maxphp are in Kb
            if ($maxphp > 0) $max=min($max,$maxphp);

            if ($max > 0)
            {
                $out .= '<input type="hidden" name="max_file_size" value="'.($max*1024).'">';
            }
            $out .= '<input class="flat minwidth400" type="file" name="userfile"';
            $out .= (empty($conf->global->MAIN_UPLOAD_DOC) || empty($perm)?' disabled':'');
            $out .= '>';
            $out .= ' ';
            $out .= '<input type="submit" class="button" name="sendit" value="'.$langs->trans("Upload").'"';
            $out .= (empty($conf->global->MAIN_UPLOAD_DOC) || empty($perm)?' disabled':'');
            $out .= '>';

            if ($addcancel)
            {
                $out .= ' &nbsp; ';
                $out .= '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
            }

            if (! empty($conf->global->MAIN_UPLOAD_DOC))
            {
                if ($perm)
                {
                	$langs->load('other');
                    $out .= ' ';
                    $out .= info_admin($langs->trans("ThisLimitIsDefinedInSetup",$max,$maxphp),1);
                }
            }
            else
            {
                $out .= ' ('.$langs->trans("UploadDisabled").')';
            }
            $out .= "</td></tr>";

            if ($savingdocmask)
            {
            	$out .= '<tr>';
   	            if (! empty($options)) $out .= '<td>'.$options.'</td>';
	            $out .= '<td valign="middle" class="nowrap">';
				$out .= '<input type="checkbox" checked class="savingdocmask" name="savingdocmask" value="'.dol_escape_js($savingdocmask).'"> '.$langs->trans("SaveUploadedFileWithMask", preg_replace('/__file__/',$langs->transnoentitiesnoconv("OriginFileName"),$savingdocmask), $langs->transnoentitiesnoconv("OriginFileName"));
            	$out .= '</td>';
            	$out .= '</tr>';
            }

            $out .= "</table>";

            $out .= '</form>';
            if (empty($sectionid)) $out .= '<br>';

            $out .= "\n<!-- End form attach new file -->\n";

            if ($linkfiles)
            {
	            $out .= "\n<!-- Start form attach new link -->\n";
	            $langs->load('link');
	            $title = $langs->trans("LinkANewFile");
	            $out .= load_fiche_titre($title, null, null);
	            $out .= '<form name="'.$htmlname.'_link" id="'.$htmlname.'_link" action="'.$url.'" method="POST">';
	            $out .= '<input type="hidden" id="'.$htmlname.'_link_section_dir" name="link_section_dir" value="">';
	            $out .= '<input type="hidden" id="'.$htmlname.'_link_section_id"  name="link_section_id" value="'.$sectionid.'">';
	            $out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	            $out .= '<div class="valignmiddle" >';
	            $out .= '<div class="inline-block" style="padding-right: 10px;">';
	            if (! empty($conf->global->OPTIMIZEFORTEXTBROWSER)) $out .= '<label for="link">'.$langs->trans("URLToLink") . ':</label> ';
	            $out .= '<input type="text" name="link" class="flat minwidth400imp" id="link" placeholder="'.dol_escape_htmltag($langs->trans("URLToLink")).'">';
	            $out .= '</div>';
	            $out .= '<div class="inline-block" style="padding-right: 10px;">';
	            if (! empty($conf->global->OPTIMIZEFORTEXTBROWSER)) $out .= '<label for="label">'.$langs->trans("Label") . ':</label> ';
	            $out .= '<input type="text" class="flat" name="label" id="label" placeholder="'.dol_escape_htmltag($langs->trans("Label")).'">';
	            $out .= '<input type="hidden" name="objecttype" value="' . $object->element . '">';
	            $out .= '<input type="hidden" name="objectid" value="' . $object->id . '">';
	            $out .= '</div>';
	            $out .= '<div class="inline-block" style="padding-right: 10px;">';
	            $out .= '<input type="submit" class="button" name="linkit" value="'.$langs->trans("ToLink").'"';
	            $out .= (empty($conf->global->MAIN_UPLOAD_DOC) || empty($perm)?' disabled':'');
	            $out .= '>';
	            $out .= '</div>';
                $out .= '</div>';
                $out .= '<div class="clearboth"></div>';
	            $out .= '</form><br>';
	            $parameters = array('socid'=>(isset($GLOBALS['socid'])?$GLOBALS['socid']:''),'id'=>(isset($GLOBALS['id'])?$GLOBALS['id']:''), 'url'=>$url, 'perm'=>$perm);
	            $res = $hookmanager->executeHooks('formattachOptions',$parameters,$object);

	            $out .= "\n<!-- End form attach new file -->\n";
            }

            if (empty($res))
            {
        		print '<div class="attacharea">';
            	print $out;
            	print '</div>';
            }

            print $hookmanager->resPrint;

            return 1;
        }
    }

    /**
     *      Show the box with list of available documents for object
     *
     *      @param      string				$modulepart         propal, facture, facture_fourn, ...
     *      @param      string				$modulesubdir       Sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
     *      @param      string				$filedir            Directory to scan
     *      @param      string				$urlsource          Url of origin page (for return)
     *      @param      int					$genallowed         Generation is allowed (1/0 or array of formats)
     *      @param      int					$delallowed         Remove is allowed (1/0)
     *      @param      string				$modelselected      Model to preselect by default
     *      @param      integer				$allowgenifempty	Show warning if no model activated
     *      @param      integer				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
     *      @param      int					$iconPDF            Show only PDF icon with link (1/0)
     * 		@param		int					$maxfilenamelength	Max length for filename shown
     * 		@param		integer				$noform				Do not output html form tags
     * 		@param		string				$param				More param on http links
     * 		@param		string				$title				Title to show on top of form
     * 		@param		string				$buttonlabel		Label on submit button
     * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
     * 		@return		int										<0 if KO, number of shown files if OK
     *      @deprecated                                         Use print xxx->showdocuments() instead.
     */
    function show_documents($modulepart,$modulesubdir,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$allowgenifempty=1,$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$noform=0,$param='',$title='',$buttonlabel='',$codelang='')
    {
        $this->numoffiles=0;
        print $this->showdocuments($modulepart,$modulesubdir,$filedir,$urlsource,$genallowed,$delallowed,$modelselected,$allowgenifempty,$forcenomultilang,$iconPDF,$maxfilenamelength,$noform,$param,$title,$buttonlabel,$codelang);
        return $this->numoffiles;
    }

    /**
     *      Return a string to show the box with list of available documents for object.
     *      This also set the property $this->numoffiles
     *
     *      @param      string				$modulepart         Module the files are related to ('propal', 'facture', 'facture_fourn', 'mymodule', 'mymodule_temp', ...)
     *      @param      string				$modulesubdir       Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
     *      @param      string				$filedir            Directory to scan
     *      @param      string				$urlsource          Url of origin page (for return)
     *      @param      int					$genallowed         Generation is allowed (1/0 or array list of templates)
     *      @param      int					$delallowed         Remove is allowed (1/0)
     *      @param      string				$modelselected      Model to preselect by default
     *      @param      integer				$allowgenifempty	Allow generation even if list of template ($genallowed) is empty (show however a warning)
     *      @param      integer				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
     *      @param      int					$iconPDF            Deprecated, see getDocumentsLink
     * 		@param		int					$maxfilenamelength	Max length for filename shown
     * 		@param		integer				$noform				Do not output html form tags
     * 		@param		string				$param				More param on http links
     * 		@param		string				$title				Title to show on top of form
     * 		@param		string				$buttonlabel		Label on submit button
     * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
     * 		@param		string				$morepicto			Add more HTML content into cell with picto
     *      @param      Object              $object             Object when method is called from an object card.
     * 		@return		string              					Output string with HTML array of documents (might be empty string)
     */
    function showdocuments($modulepart,$modulesubdir,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$allowgenifempty=1,$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$noform=0,$param='',$title='',$buttonlabel='',$codelang='',$morepicto='',$object=null)
    {
		// Deprecation warning
		if (0 !== $iconPDF) {
			dol_syslog(__METHOD__ . ": passing iconPDF parameter is deprecated", LOG_WARNING);
		}

        global $langs, $conf, $user, $hookmanager;
        global $form, $bc;

        if (! is_object($form)) $form=new Form($this->db);

        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        // For backward compatibility
        if (! empty($iconPDF)) {
        	return $this->getDocumentsLink($modulepart, $modulesubdir, $filedir);
        }

        $printer=0;
        if (in_array($modulepart,array('facture','supplier_proposal','propal','proposal','order','commande','expedition', 'commande_fournisseur', 'expensereport')))	// The direct print feature is implemented only for such elements
        {
            $printer = (!empty($user->rights->printing->read) && !empty($conf->printing->enabled))?true:false;
        }

        $hookmanager->initHooks(array('formfile'));
        $forname='builddoc';
        $out='';
        $var=true;

        $headershown=0;
        $showempty=0;
        $i=0;

        $out.= "\n".'<!-- Start show_document -->'."\n";
        //print 'filedir='.$filedir;

        if (preg_match('/massfilesarea_/', $modulepart))
        {
	        $out.='<br><a name="show_files"></a>';
			$title=$langs->trans("MassFilesArea").' <a href="" id="togglemassfilesarea" ref="shown">('.$langs->trans("Hide").')</a>';
			$title.='<script type="text/javascript" language="javascript">
				jQuery(document).ready(function() {
					jQuery(\'#togglemassfilesarea\').click(function() {
						if (jQuery(\'#togglemassfilesarea\').attr(\'ref\') == "shown")
						{
							jQuery(\'#'.$modulepart.'_table\').hide();
							jQuery(\'#togglemassfilesarea\').attr("ref", "hidden");
							jQuery(\'#togglemassfilesarea\').text("('.dol_escape_js($langs->trans("Show")).')");
						}
						else
						{
							jQuery(\'#'.$modulepart.'_table\').show();
							jQuery(\'#togglemassfilesarea\').attr("ref","shown");
							jQuery(\'#togglemassfilesarea\').text("('.dol_escape_js($langs->trans("Hide")).')");
						}
						return false;
					});		
				});
				</script>';
        }        
        
        $titletoshow=$langs->trans("Documents");
        if (! empty($title)) $titletoshow=$title;

        // Show table
        if ($genallowed)
        {
            $modellist=array();

            if ($modulepart == 'company')
            {
                $showempty=1;
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';
                    $modellist=ModeleThirdPartyDoc::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'propal')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
                    $modellist=ModelePDFPropales::liste_modeles($this->db);
                }
            }
			else if ($modulepart == 'supplier_proposal')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_proposal/modules_supplier_proposal.php';
                    $modellist=ModelePDFSupplierProposal::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'commande')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
                    $modellist=ModelePDFCommandes::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'expedition')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
                    $modellist=ModelePDFExpedition::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'livraison')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/livraison/modules_livraison.php';
                    $modellist=ModelePDFDeliveryOrder::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'ficheinter')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
                    $modellist=ModelePDFFicheinter::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'facture')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
                    $modellist=ModelePDFFactures::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'contract')
            {
            	if (is_array($genallowed)) $modellist=$genallowed;
            	else
            	{
            		include_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
            		$modellist=ModelePDFContract::liste_modeles($this->db);
            	}
            }
            elseif ($modulepart == 'project')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
                    $modellist=ModelePDFProjects::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'project_task')
            {
            	if (is_array($genallowed)) $modellist=$genallowed;
            	else
            	{
            		include_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';
            		$modellist=ModelePDFTask::liste_modeles($this->db);
            	}
            }
            elseif ($modulepart == 'export')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
                    $modellist=ModeleExports::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'commande_fournisseur')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
                    $modellist=ModelePDFSuppliersOrders::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'facture_fournisseur')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
                    $modellist=ModelePDFSuppliersInvoices::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'remisecheque')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/modules_chequereceipts.php';
                    $modellist=ModeleChequeReceipts::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'donation')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
                    $modellist=ModeleDon::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'member')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/member/modules_cards.php';
                    $modellist=ModelePDFCards::liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'agenda')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/action/modules_action.php';
                    $modellist=ModeleAction::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'expensereport')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
                    $modellist=ModeleExpenseReport::liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'unpaid')
            {
                $modellist='';
            }
            else //if ($modulepart != 'agenda')
            {
            	// For normalized standard modules
            	$file=dol_buildpath('/core/modules/'.$modulepart.'/modules_'.$modulepart.'.php',0);
                if (file_exists($file))
                {
                	$res=include_once $file;
                }
                // For normalized external modules
                else
				{
                	$file=dol_buildpath('/'.$modulepart.'/core/modules/'.$modulepart.'/modules_'.$modulepart.'.php',0);
                	$res=include_once $file;
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

            // Set headershown to avoit to have table opened a second time later
            $headershown=1;

            $buttonlabeltoshow=$buttonlabel;
            if (empty($buttonlabel)) $buttonlabel=$langs->trans('Generate');

            if ($conf->browser->layout == 'phone') $urlsource.='#'.$forname.'_form';   // So we switch to form after a generation
            if (empty($noform)) $out.= '<form action="'.$urlsource.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc').'" name="'.$forname.'" id="'.$forname.'_form" method="post">';
            $out.= '<input type="hidden" name="action" value="builddoc">';
            $out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            
            $out.= load_fiche_titre($titletoshow, '', '');
            $out.= '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';

            $out.= '<tr class="liste_titre">';

            $addcolumforpicto=($delallowed || $printer || $morepicto);
            $out.= '<th align="center" colspan="'.(3+($addcolumforpicto?'2':'1')).'" class="formdoc liste_titre maxwidthonsmartphone">';

            // Model
            if (! empty($modellist))
            {
                $out.= '<span class="hideonsmartphone">'.$langs->trans('Model').' </span>';
                if (is_array($modellist) && count($modellist) == 1)    // If there is only one element
                {
                    $arraykeys=array_keys($modellist);
                    $modelselected=$arraykeys[0];
                }
                $out.= $form->selectarray('model', $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', 'minwidth100');
                $out.= ajax_combobox('model');
            }
            else
            {
                $out.= '<div class="float">'.$langs->trans("Files").'</div>';
            }

            // Language code (if multilang)
            if (($allowgenifempty || (is_array($modellist) && count($modellist) > 0)) && $conf->global->MAIN_MULTILANGS && ! $forcenomultilang && (! empty($modellist) || $showempty))
            {
                include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
                $formadmin=new FormAdmin($this->db);
                $defaultlang=$codelang?$codelang:$langs->getDefaultLang();
                $morecss='maxwidth150';
                if (! empty($conf->browser->phone)) $morecss='maxwidth100';
                $out.= $formadmin->select_language($defaultlang, 'lang_id', 0, 0, 0, 0, 0, $morecss);
            }
            else
            {
                $out.= '&nbsp;';
            }

            // Button
            $genbutton = '<input class="button buttongen" id="'.$forname.'_generatebutton" name="'.$forname.'_generatebutton"';
            $genbutton.= ' type="submit" value="'.$buttonlabel.'"';
            if (! $allowgenifempty && ! is_array($modellist) && empty($modellist)) $genbutton.= ' disabled';
            $genbutton.= '>';
            if ($allowgenifempty && ! is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid')
            {
               	$langs->load("errors");
               	$genbutton.= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
            }
            if (! $allowgenifempty && ! is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') $genbutton='';
            if (empty($modellist) && ! $showempty && $modulepart != 'unpaid') $genbutton='';
            $out.= $genbutton;
            $out.= '</th>';

            if (!empty($hookmanager->hooks['formfile']))
            {
                foreach($hookmanager->hooks['formfile'] as $module)
                {
                    if (method_exists($module, 'formBuilddocLineOptions')) $out .= '<th></th>';
                }
            }
            $out.= '</tr>';

            // Execute hooks
            $parameters=array('socid'=>(isset($GLOBALS['socid'])?$GLOBALS['socid']:''),'id'=>(isset($GLOBALS['id'])?$GLOBALS['id']:''),'modulepart'=>$modulepart);
            if (is_object($hookmanager))
            {
            	$reshook = $hookmanager->executeHooks('formBuilddocOptions',$parameters,$GLOBALS['object']);
            	$out.= $hookmanager->resPrint;
            }

        }

        // Get list of files
        if (! empty($filedir))
        {
            $file_list=dol_dir_list($filedir,'files',0,'','(\.meta|_preview\.png)$','date',SORT_DESC);

            $link_list = array();
            if (is_object($object))
            {
                require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
                $link = new Link($this->db);
                $sortfield = $sortorder = null;
                $res = $link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
            }
            
            $out.= '<!-- html.formfile::showdocuments -->'."\n";
            
            // Show title of array if not already shown
            if ((! empty($file_list) || ! empty($link_list) || preg_match('/^massfilesarea/', $modulepart)) && ! $headershown)
            {
                $headershown=1;
                $out.= '<div class="titre">'.$titletoshow.'</div>'."\n";
                $out.= '<table class="border" summary="listofdocumentstable" id="'.$modulepart.'_table" width="100%">'."\n";
            }

            // Loop on each file found
			if (is_array($file_list))
			{
				foreach($file_list as $file)
				{
					$var=!$var;

					// Define relative path for download link (depends on module)
					$relativepath=$file["name"];										// Cas general
                    if ($modulesubdir) $relativepath=$modulesubdir."/".$file["name"];	// Cas propal, facture...
					if ($modulepart == 'export') $relativepath = $file["name"];			// Other case

					$out.= "<tr ".$bc[$var].">";

					$documenturl = DOL_URL_ROOT.'/document.php';
					if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl=$conf->global->DOL_URL_ROOT_DOCUMENT_PHP;
					
					// Show file name with link to download
					$out.= '<td class="nowrap">';
					$out.= '<a data-ajax="false" href="'.$documenturl.'?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).($param?'&'.$param:'').'"';
					$mime=dol_mimetype($relativepath,'',0);
					if (preg_match('/text/',$mime)) $out.= ' target="_blank"';
					$out.= ' target="_blank">';
					$out.= img_mime($file["name"],$langs->trans("File").': '.$file["name"]).' '.dol_trunc($file["name"],$maxfilenamelength);
					$out.= '</a>'."\n";
                    $out.= $this->showPreview($file,$modulepart,$relativepath);
					$out.= '</td>';

					// Show file size
					$size=(! empty($file['size'])?$file['size']:dol_filesize($filedir."/".$file["name"]));
					$out.= '<td align="right" class="nowrap">'.dol_print_size($size).'</td>';

					// Show file date
					$date=(! empty($file['date'])?$file['date']:dol_filemtime($filedir."/".$file["name"]));
					$out.= '<td align="right" class="nowrap">'.dol_print_date($date, 'dayhour', 'tzuser').'</td>';

					if ($delallowed || $printer || $morepicto)
					{
						$out.= '<td align="right">';
						if ($delallowed)
						{
							$out.= '<a href="'.$urlsource.(strpos($urlsource,'?')?'&amp;':'?').'action=remove_file&amp;file='.urlencode($relativepath);
							$out.= ($param?'&amp;'.$param:'');
							//$out.= '&modulepart='.$modulepart; // TODO obsolete ?
							//$out.= '&urlsource='.urlencode($urlsource); // TODO obsolete ?
							$out.= '">'.img_picto($langs->trans("Delete"), 'delete.png').'</a>';
							//$out.='</td>';
						}
						if ($printer)
						{
							//$out.= '<td align="right">';
                            $out.= '&nbsp;<a href="'.$urlsource.(strpos($urlsource,'?')?'&amp;':'?').'action=print_file&amp;printer='.$modulepart.'&amp;file='.urlencode($relativepath);
                            $out.= ($param?'&amp;'.$param:'');
                            $out.= '">'.img_picto($langs->trans("PrintFile", $relativepath),'printer.png').'</a>';
						}
						if ($morepicto)
						{
							$morepicto=preg_replace('/__FILENAMEURLENCODED__/',urlencode($relativepath),$morepicto);
                        	$out.=$morepicto;
						}
                        $out.='</td>';
                    }

                    if (is_object($hookmanager))
                    {
            			$parameters=array('socid'=>(isset($GLOBALS['socid'])?$GLOBALS['socid']:''),'id'=>(isset($GLOBALS['id'])?$GLOBALS['id']:''),'modulepart'=>$modulepart,'relativepath'=>$relativepath);
                    	$res = $hookmanager->executeHooks('formBuilddocLineOptions',$parameters,$file);
                        if (empty($res))
                        {
                            $out .= $hookmanager->resPrint;		// Complete line
                            $out.= '</tr>';
                        }
                        else $out = $hookmanager->resPrint;		// Replace line
              		}
				}

                $this->numoffiles++;
            }
            // Loop on each file found
            if (is_array($link_list))
            {
                $colspan=2;
                    
                foreach($link_list as $file)
                {
                    $var=!$var;
                    
                    $out.= "<tr ".$bc[$var].">";
                    $out.='<td colspan="'.$colspan.'" class="maxwidhtonsmartphone">';
                    $out.='<a data-ajax="false" href="' . $link->url . '" target="_blank">';
                    $out.=$file->label;
                    $out.='</a>';
                    $out.='</td>';
                    $out.='<td align="right">';
                    $out.=dol_print_date($file->datea,'dayhour');
                    $out.='</td>';
                    if ($delallowed || $printer || $morepicto) $out.='<td></td>';
                    $out.='</tr>';
                }
                $this->numoffiles++;
            }
            
		 	if (count($file_list) == 0 && count($link_list) == 0 && $headershown)
            {
	        	$out.='<tr '.$bc[0].'><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
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
     *	Show a Document icon with link(s)
     *  You may want to call this into a div like this:
     *  print '<div class="inline-block valignmiddle">'.$formfile->getDocumentsLink($element_doc, $filename, $filedir).'</div>';
     *
     *	@param	string	$modulepart		propal, facture, facture_fourn, ...
     *	@param	string	$modulesubdir	Sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
     *	@param	string	$filedir		Full path to directory to scan
     *  @param	string	$filter			Filter filenames on this regex string (Example: '\.pdf$')
     *	@return	string              	Output string with HTML link of documents (might be empty string). This also fill the array ->infofiles
     */
    function getDocumentsLink($modulepart, $modulesubdir, $filedir, $filter='')
    {
        global $conf, $langs;

    	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    	$out='';
    	$this->infofiles=array('nboffiles'=>0,'extensions'=>array(),'files'=>array());

    	if (! empty($conf->dol_use_jmobile)) return '';
    	 
		$file_list=dol_dir_list($filedir, 'files', 0, preg_quote(basename($modulesubdir),'/').'[^\-]+', '\.meta$|\.png$');	// Get list of files starting with name of ref (but not followed by "-" to discard uploaded files)

    	// For ajax treatment
		$out.= '<!-- html.formfile::getDocumentsLink -->'."\n";
    	if (! empty($file_list))
    	{
    	    $out='<dl class="dropdown inline-block">
    			<dt><a data-ajax="false" href="#" onClick="return false;">'.img_picto('', 'listlight').'</a></dt>
    			<dd><div class="multichoicedoc"><ul class="ulselectedfields" style="display: none;">';
    	    $tmpout='';

    		// Loop on each file found
    		foreach($file_list as $file)
    		{
    		    $i++;
    			if ($filter && ! preg_match('/'.$filter.'/i', $file["name"])) continue;	// Discard this. It does not match provided filter.

    			// Define relative path for download link (depends on module)
    			$relativepath=$file["name"];								// Cas general
    			if ($modulesubdir) $relativepath=$modulesubdir."/".$file["name"];	// Cas propal, facture...
    			// Autre cas
    			if ($modulepart == 'donation')            {
    				$relativepath = get_exdir($modulesubdir,2,0,0,null,'donation').$file["name"];
    			}
    			if ($modulepart == 'export')              {
    				$relativepath = $file["name"];
    			}

    			$this->infofiles['nboffiles']++;
    			$this->infofiles['files'][]=$file['fullname'];
    			$ext=pathinfo($file["name"], PATHINFO_EXTENSION);
    			if (empty($this->infofiles[$ext])) $this->infofiles['extensions'][$ext]=1;
    			else $this->infofiles['extensions'][$ext]++;

    			// Preview
    			$urladvanced = getAdvancedPreviewUrl($modulepart, $relativepath);
    		    if ($urladvanced) $tmpout.= '<li><a data-ajax="false" href="'.$urladvanced.'">'.img_picto('','detail').' '.$langs->trans("Preview").' '.$ext.'</a></li>';
    			// Download
    		    $tmpout.= '<li><a data-ajax="false" class="pictopreview" href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'"';
    			$mime=dol_mimetype($relativepath,'',0);
    			if (preg_match('/text/',$mime)) $tmpout.= ' target="_blank"';
    			$tmpout.= '>';
    			$tmpout.=img_mime($relativepath, $file["name"]).' ';
    			$tmpout.= $langs->trans("Download").' '.$ext;
    			$tmpout.= '</a></li>'."\n";
    		}
    		$out.=$tmpout;
    		$out.='</ul></div></dd>
    			</dl>';
    	}
    	else
    	{
    	    // TODO Add link to regenerate doc ?
    	    //$out.= '<div id="gen_pdf_'.$modulesubdir.'" class="linkobject hideobject">'.img_picto('', 'refresh').'</div>'."\n";
    	}

    	return $out;
    }


    /**
     *  Show list of documents in a directory
     *
     *  @param	 array	$filearray          Array of files loaded by dol_dir_list('files') function before calling this
     * 	@param	 Object	$object				Object on which document is linked to
     * 	@param	 string	$modulepart			Value for modulepart used by download or viewimage wrapper
     * 	@param	 string	$param				Parameters on sort links (param must start with &, example &aaa=bbb&ccc=ddd)
     * 	@param	 int	$forcedownload		Force to open dialog box "Save As" when clicking on file
     * 	@param	 string	$relativepath		Relative path of docs (autodefined if not provided)
     * 	@param	 int	$permonobject		Permission on object (so permission to delete or crop document)
     * 	@param	 int	$useinecm			Change output for use in ecm module
     * 	@param	 string	$textifempty		Text to show if filearray is empty ('NoFileFound' if not defined)
     *  @param   int	$maxlength          Maximum length of file name shown
     *  @param	 string	$title				Title before list
     *  @param	 string $url				Full url to use for click links ('' = autodetect)
	 *  @param	 int	$showrelpart		0=Show only filename (default), 1=Show first level 1 dir
	 *  @param   int    $permtoeditline     Permission to edit document line (-1 is deprecated)
     * 	@return	 int						<0 if KO, nb of files shown if OK
     */
	function list_of_documents($filearray,$object,$modulepart,$param='',$forcedownload=0,$relativepath='',$permonobject=1,$useinecm=0,$textifempty='',$maxlength=0,$title='',$url='', $showrelpart=0, $permtoeditline=-1)
	{
		global $user, $conf, $langs, $hookmanager;
		global $bc;
		global $sortfield, $sortorder, $maxheightmini;

		$hookmanager->initHooks(array('formfile'));

		$parameters=array(
				'filearray' => $filearray,
				'modulepart'=> $modulepart,
				'param' => $param,
				'forcedownload' => $forcedownload,
				'relativepath' => $relativepath,
				'permtodelete' => $permonobject,
				'useinecm' => $useinecm,
				'textifempty' => $textifempty,
				'maxlength' => $maxlength,
				'title' => $title,
				'url' => $url
		);
		$reshook=$hookmanager->executeHooks('showFilesList', $parameters, $object);

		if (isset($reshook) && $reshook != '') // null or '' for bypass
		{
			return $reshook;
		}
		else
		{
			$param = (isset($object->id)?'&id='.$object->id:'').$param;

			if ($permtoeditline < 0)  // Old behaviour for backward compatibility. New feature should call method with value 0 or 1
			{
			    $permtoeditline=0;
			    if (in_array($modulepart, array('product','produit','service')))
			    {
			        if ($user->rights->produit->creer && $object->type == Product::TYPE_PRODUCT) $permtoeditline=1;
			        if ($user->rights->service->creer && $object->type == Product::TYPE_SERVICE) $permtoeditline=1;
			    }
			}
			if (empty($conf->global->MAIN_UPLOAD_DOC)) 
			{
			    $permtoeditline=0;
			    $permonobject=0;
			}

			// Show list of existing files
			if (empty($useinecm)) print load_fiche_titre($title?$title:$langs->trans("AttachedFiles"));
			if (empty($url)) $url=$_SERVER["PHP_SELF"];
			
			print '<!-- html.formfile::list_of_documents -->'."\n";
			if (GETPOST('action') == 'editfile' && $permtoeditline)
			{
			    print '<form action="'.$_SERVER["PHP_SELF"].'?'.$param.'" method="POST">';
			    print '<input type="hidden" name="action" value="renamefile">';
			    print '<input type="hidden" name="id" value="'.$object->id.'">';
			    print '<input type="hidden" name="modulepart" value="'.$modulepart.'">';
			}
			print '<table width="100%" class="'.($useinecm?'liste noborderbottom':'liste').'">'."\n";
			
			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Documents2"),$url,"name","",$param,'align="left"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Size"),$url,"size","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Date"),$url,"date","",$param,'align="center"',$sortfield,$sortorder);
			if (empty($useinecm)) print_liste_field_titre('',$url,"","",$param,'align="center"');
			print_liste_field_titre('');
			print "</tr>\n";

			$nboffiles=count($filearray);

			if ($nboffiles > 0) include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

			$var=true;
			foreach($filearray as $key => $file)      // filearray must be only files here
			{
				if ($file['name'] != '.'
						&& $file['name'] != '..'
						&& ! preg_match('/\.meta$/i',$file['name']))
				{
					// Define relative path used to store the file
					if (empty($relativepath))
					{
						$relativepath=(! empty($object->ref)?dol_sanitizeFileName($object->ref):'').'/';
						if ($object->element == 'invoice_supplier') $relativepath=get_exdir($object->id,2,0,0,$object,'invoice_supplier').$relativepath;	// TODO Call using a defined value for $relativepath
						if ($object->element == 'project_task') $relativepath='Call_not_supported_._Call_function_using_a_defined_relative_path_.';
					}
					// For backward compatiblity, we detect file is stored into an old path
					if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO) && $file['level1name'] == 'photos')
	                {
	                    $relativepath=preg_replace('/^.*\/produit\//','',$file['path']).'/';
	                }
					$var=!$var;
					
					$editline=0;
					
			        print '<!-- Line list_of_documents '.$key.' -->'."\n";
					print '<tr '.$bc[$var].'>';
					print '<td class="tdoverflow">';
					
					//print "XX".$file['name'];	//$file['name'] must be utf8
					print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
					if ($forcedownload) print '&attachment=1';
					if (! empty($object->entity)) print '&entity='.$object->entity;
					$filepath=$relativepath.$file['name'];
					/* Restore old code: When file is at level 2+, full relative path (and not only level1) must be into url
					if ($file['level1name'] <> $object->id)
						$filepath=$object->id.'/'.$file['level1name'].'/'.$file['name'];
					else
						$filepath=$object->id.'/'.$file['name'];
					*/
					print '&file='.urlencode($filepath);
					print '">';

					print img_mime($file['name'],$file['name'].' ('.dol_print_size($file['size'],0,0).')').' ';
					if ($showrelpart == 1) print $relativepath;
					//print dol_trunc($file['name'],$maxlength,'middle');
					if (GETPOST('action') == 'editfile' && $file['name'] == basename(GETPOST('urlfile')))
					{
					    print '</a>';
					    print '<input type="hidden" name="renamefilefrom" value="'.dol_escape_htmltag($file['name']).'">';
					    print '<input type="text" name="renamefileto" class="quatrevingtpercent" value="'.dol_escape_htmltag($file['name']).'">';
					    $editline=1;
					}
					else
					{
					    print $file['name'];
					    print '</a>';
					}
					
                    if (! $editline) print $this->showPreview($file,$modulepart,$filepath);

					print "</td>\n";
					print '<td align="right" width="80px">'.dol_print_size($file['size'],1,1).'</td>';
					print '<td align="center" width="130px">'.dol_print_date($file['date'],"dayhour","tzuser").'</td>';
					// Preview
					if (empty($useinecm))
					{
						$fileinfo = pathinfo($file['name']);
						print '<td align="center">';
						if (image_format_supported($file['name']) > 0)
						{
						    $minifile=getImageFileNameForSize($file['name'], '_mini'); // For new thumbs using same ext (in lower case howerver) than original
						    if (! dol_is_file($file['path'].'/'.$minifile)) $minifile=getImageFileNameForSize($file['name'], '_mini', '.png'); // For backward compatibility of old thumbs that were created with filename in lower case and with .png extension
						    //print $file['path'].'/'.$minifile.'<br>';

						    $urlforhref=getAdvancedPreviewUrl($modulepart, $relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']));
						    if (empty($urlforhref)) $urlforhref=DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file='.urlencode($relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']));
						    print '<a href="'.$urlforhref.'" class="aphoto" target="_blank">';
							print '<img border="0" height="'.$maxheightmini.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file='.urlencode($relativepath.$minifile).'" title="">';
							print '</a>';
						}
						else print '&nbsp;';
						print '</td>';
					}
					if (! $editline)
					{
    					// Delete or view link
    					// ($param must start with &)
    					print '<td class="valignmiddle right"><!-- action on files -->';
    					if ($useinecm)     
    					{
    					    print '<a href="'.DOL_URL_ROOT.'/ecm/docfile.php?urlfile='.urlencode($file['name']).$param.'" class="editfilelink" rel="'.urlencode($file['name']).'">'.img_view('default', 0, 'class="paddingrightonly"').'</a>';
    					}
    					else
    					{
        					$newmodulepart=$modulepart;
        					if (in_array($modulepart, array('product','produit','service'))) $newmodulepart='produit|service';
    						
        					$disablecrop=1; 
        					if (in_array($modulepart, array('product','produit','service','holiday','project'))) $disablecrop=0;
        					
    					    if (! $disablecrop && image_format_supported($file['name']) > 0)
    						{
    							if ($permtoeditline)
    							{
       								// Link to resize
       			               		print '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode($newmodulepart).'&id='.$object->id.'&file='.urlencode($relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension'])).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"),DOL_URL_ROOT.'/theme/common/transform-crop-and-resize','class="paddingrightonly"',1).'</a>';
    							}
    						}
    						
    						if ($permtoeditline)
    						{
    						    print '<a href="'.(($useinecm && $useajax)?'#':$url.'?action=editfile&urlfile='.urlencode($filepath).$param).'" class="editfilelink" rel="'.$filepath.'">'.img_edit('default',0,'class="paddingrightonly"').'</a>';
    						}
    					}
    					if ($permonobject)
    					{
    						/*
    						if ($file['level1name'] <> $object->id)
    							$filepath=$file['level1name'].'/'.$file['name'];
    						else
    							$filepath=$file['name'];
    						*/
    						$useajax=1;
    						if (! empty($conf->dol_use_jmobile)) $useajax=0;
    						if (empty($conf->use_javascript_ajax)) $useajax=0;
    						if (! empty($conf->global->MAIN_ECM_DISABLE_JS)) $useajax=0;
    
    						print '<a href="'.(($useinecm && $useajax)?'#':$url.'?action=delete&urlfile='.urlencode($filepath).$param).'" class="deletefilelink" rel="'.$filepath.'">'.img_delete().'</a>';
    					}
					    print "</td>";
					}
					else
					{
					    print '<td class="right">';
					    print '<input type="submit" class="button" name="renamefilesave" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
					    print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
					    print '</td>';
					}
					print "</tr>\n";
				}
			}
			if ($nboffiles == 0)
			{
				print '<tr '.$bc[false].'><td colspan="'.(empty($useinecm)?'5':'5').'" class="opacitymedium">';
				if (empty($textifempty)) print $langs->trans("NoFileFound");
				else print $textifempty;
				print '</td></tr>';
			}
			print "</table>";
			if (GETPOST('action') == 'editfile' && $permtoeditline)
			{
			    print '</form>';
			}
			    	
			return $nboffiles;
		}
	}


    /**
     *	Show list of documents in a directory
     *
     *  @param	string	$upload_dir         Directory that was scanned
     *  @param  array	$filearray          Array of files loaded by dol_dir_list function before calling this function
     *  @param  string	$modulepart         Value for modulepart used by download wrapper
     *  @param  string	$param              Parameters on sort links
     *  @param  int		$forcedownload      Force to open dialog box "Save As" when clicking on file
     *  @param  string	$relativepath       Relative path of docs (autodefined if not provided)
     *  @param  int		$permtodelete       Permission to delete
     *  @param  int		$useinecm           Change output for use in ecm module
     *  @param  int		$textifempty        Text to show if filearray is empty
     *  @param  int		$maxlength          Maximum length of file name shown
     *  @param	string $url				Full url to use for click links ('' = autodetect)
     *  @return int                 		<0 if KO, nb of files shown if OK
     */
    function list_of_autoecmfiles($upload_dir,$filearray,$modulepart,$param,$forcedownload=0,$relativepath='',$permtodelete=1,$useinecm=0,$textifempty='',$maxlength=0,$url='')
    {
        global $user, $conf, $langs;
        global $bc;
        global $sortfield, $sortorder;

        dol_syslog(get_class($this).'::list_of_autoecmfiles upload_dir='.$upload_dir.' modulepart='.$modulepart);

        // Show list of documents
        if (empty($useinecm)) print load_fiche_titre($langs->trans("AttachedFiles"));
        if (empty($url)) $url=$_SERVER["PHP_SELF"];
        print '<table width="100%" class="noborder">'."\n";
        print '<tr class="liste_titre">';
        $sortref="fullname";
        if ($modulepart == 'invoice_supplier') $sortref='level1name';
        print_liste_field_titre($langs->trans("Ref"),$url,$sortref,"",$param,'align="left"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Documents2"),$url,"name","",$param,'align="left"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Size"),$url,"size","",$param,'align="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Date"),$url,"date","",$param,'align="center"',$sortfield,$sortorder);
        print_liste_field_titre('','','');
        print '</tr>'."\n";

        // To show ref or specific information according to view to show (defined by $module)
        if ($modulepart == 'company')
        {
            include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
            $object_instance=new Societe($this->db);
        }
        else if ($modulepart == 'invoice')
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $object_instance=new Facture($this->db);
        }
        else if ($modulepart == 'invoice_supplier')
        {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
            $object_instance=new FactureFournisseur($this->db);
        }
        else if ($modulepart == 'propal')
        {
            include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
            $object_instance=new Propal($this->db);
        }
        else if ($modulepart == 'supplier_proposal')
        {
            include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
            $object_instance=new SupplierProposal($this->db);
        }
        else if ($modulepart == 'order')
        {
            include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $object_instance=new Commande($this->db);
        }
        else if ($modulepart == 'order_supplier')
        {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
            $object_instance=new CommandeFournisseur($this->db);
        }
        else if ($modulepart == 'contract')
        {
            include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
            $object_instance=new Contrat($this->db);
        }
        else if ($modulepart == 'product')
        {
            include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
            $object_instance=new Product($this->db);
        }
        else if ($modulepart == 'tax')
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
            $object_instance=new ChargeSociales($this->db);
        }
        else if ($modulepart == 'project')
        {
        	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
        	$object_instance=new Project($this->db);
        }
        else if ($modulepart == 'fichinter')
        {
        	include_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
        	$object_instance=new Fichinter($this->db);
        }
        else if ($modulepart == 'user')
        {
        	include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
        	$object_instance=new User($this->db);
        }
        else if ($modulepart == 'expensereport')
        {
        	include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
        	$object_instance=new ExpenseReport($this->db);
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
                if ($modulepart == 'company')          { preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=(isset($reg[1])?$reg[1]:''); }
                if ($modulepart == 'invoice')          { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
                if ($modulepart == 'invoice_supplier') { preg_match('/([^\/]+)\/[^\/]+$/',$relativefile,$reg); $ref=(isset($reg[1])?$reg[1]:''); if (is_numeric($ref)) { $id=$ref; $ref=''; } }	// $ref may be also id with old supplier invoices
                if ($modulepart == 'propal')           { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'supplier_proposal') { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
                if ($modulepart == 'order')            { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
                if ($modulepart == 'order_supplier')   { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
                if ($modulepart == 'contract')         { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
                if ($modulepart == 'product')          { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
                if ($modulepart == 'tax')              { preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=(isset($reg[1])?$reg[1]:''); }
                if ($modulepart == 'project')          { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:'');}
                if ($modulepart == 'fichinter')        { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:'');}
                if ($modulepart == 'user')             { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $id=(isset($reg[1])?$reg[1]:'');}
                if ($modulepart == 'expensereport')    { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $id=(isset($reg[1])?$reg[1]:'');}

                if (! $id && ! $ref) continue;
                $found=0;
                if (! empty($this->cache_objects[$modulepart.'_'.$id.'_'.$ref]))
                {
                    $found=1;
                }
                else
                {
                    //print 'Fetch '.$id." - ".$ref.'<br>';

                    if ($id) {
                        $result = $object_instance->fetch($id);
                    } else {
                        //fetchOneLike looks for objects with wildcards in its reference.
                        //It is useful for those masks who get underscores instead of their actual symbols
                        //fetchOneLike requires some info in the object. If it doesn't have it, then 0 is returned
                        //that's why we look only look fetchOneLike when fetch returns 0
                        if (!$result = $object_instance->fetch('', $ref)) {
                            $result = $object_instance->fetchOneLike($ref);
                        }
                    }

					if ($result > 0) {  // Save object into a cache
						$found=1; $this->cache_objects[$modulepart.'_'.$id.'_'.$ref] = clone $object_instance;
					}
                    if ($result == 0) { $found=1; $this->cache_objects[$modulepart.'_'.$id.'_'.$ref]='notfound'; unset($filearray[$key]); }
                }

                if (! $found > 0 || ! is_object($this->cache_objects[$modulepart.'_'.$id.'_'.$ref])) continue;    // We do not show orphelins files

                $var=!$var;
                print '<!-- Line list_of_autoecmfiles '.$key.' -->'."\n";
                print '<tr '.$bc[$var].'>';
                print '<td>';
                if ($found > 0 && is_object($this->cache_objects[$modulepart.'_'.$id.'_'.$ref])) print $this->cache_objects[$modulepart.'_'.$id.'_'.$ref]->getNomUrl(1,'document');
                else print $langs->trans("ObjectDeleted",($id?$id:$ref));
                print '</td>';
                print '<td>';
                //print "XX".$file['name']; //$file['name'] must be utf8
                print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
                if ($forcedownload) print '&attachment=1';
                print '&file='.urlencode($relativefile).'">';
                print img_mime($file['name'],$file['name'].' ('.dol_print_size($file['size'],0,0).')').' ';
                print dol_trunc($file['name'],$maxlength,'middle');
                print '</a>';
                print "</td>\n";
                print '<td align="right">'.dol_print_size($file['size'],1,1).'</td>';
                print '<td align="center">'.dol_print_date($file['date'],"dayhour").'</td>';
                print '<td align="right">';
                if (! empty($useinecm))  print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
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
            print '<tr '.$bc[false].'><td colspan="4">';
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
        include DOL_DOCUMENT_ROOT.'/core/tpl/ajax/fileupload_main.tpl.php';

        // Include template
        include DOL_DOCUMENT_ROOT.'/core/tpl/ajax/fileupload_view.tpl.php';

    }

    /**
     * Show array with linked files
     *
     * @param 	Object		$object			Object
     * @param 	int			$permtodelete	Deletion is allowed
     * @param 	string		$action			Action
     * @param 	string		$selected		???
     * @param	string		$param			More param to add into URL
     * @return 	int							Number of links
     */
    public function listOfLinks($object, $permtodelete=1, $action=null, $selected=null, $param='')
    {
        global $user, $conf, $langs, $user;
        global $bc;
        global $sortfield, $sortorder;

        $langs->load("link");

        require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
        $link = new Link($this->db);
        $links = array();
        if ($sortfield == "name") {
            $sortfield = "label";
        } elseif ($sortfield == "date") {
            $sortfield = "datea";
        } else {
            $sortfield = null;
        }
        $res = $link->fetchAll($links, $object->element, $object->id, $sortfield, $sortorder);
        $param .= (isset($object->id)?'&id=' . $object->id : '');

        // Show list of associated links
        print load_fiche_titre($langs->trans("LinkedFiles"));

        print '<form action="' . $_SERVER['PHP_SELF'] . ($param?'?'.$param:'') . '" method="POST">';

        print '<table width="100%" class="liste">';
        print '<tr class="liste_titre">';
        print_liste_field_titre(
            $langs->trans("Links"),
            $_SERVER['PHP_SELF'],
            "name",
            "",
            $param,
            'align="left"',
            $sortfield,
            $sortorder
        );
        print_liste_field_titre(
            "",
            "",
            "",
            "",
            "",
            'align="right"'
        );
        print_liste_field_titre(
            $langs->trans("Date"),
            $_SERVER['PHP_SELF'],
            "date",
            "",
            $param,
            'align="center"',
            $sortfield,
            $sortorder
        );
        print_liste_field_titre(
            '',
            $_SERVER['PHP_SELF'],
            "",
            "",
            $param,
            'align="center"'
        );
        print_liste_field_titre('','','');
        print '</tr>';
        $nboflinks = count($links);
        if ($nboflinks > 0) include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

        $var = true;
        foreach ($links as $link)
        {
            $var =! $var;
            print '<tr ' . $bc[$var] . '>';
            //edit mode
            if ($action == 'update' && $selected === $link->id)
            {
                print '<td>';
                print '<input type="hidden" name="id" value="' . $object->id . '">';
                print '<input type="hidden" name="linkid" value="' . $link->id . '">';
                print '<input type="hidden" name="action" value="confirm_updateline">';
                print $langs->trans('Link') . ': <input type="text" name="link" value="' . $link->url . '">';
                print '</td>';
                print '<td>';
                print $langs->trans('Label') . ': <input type="text" name="label" value="' . $link->label . '">';
                print '</td>';
                print '<td align="center">' . dol_print_date(dol_now(), "dayhour", "tzuser") . '</td>';
                print '<td align="right"></td>';
                print '<td align="right">';
                print '<input type="submit" name="save" class="button" value="' . dol_escape_htmltag($langs->trans('Save')) . '">';
                print '<input type="submit" name="cancel" class="button" value="' . dol_escape_htmltag($langs->trans('Cancel')) . '">';
                print '</td>';
            }
            else
			{
                print '<td>';
                print img_picto('', 'object_globe').' ';
                print '<a data-ajax="false" href="' . $link->url . '" target="_blank">';
                print $link->label;
                print '</a>';
                print '</td>'."\n";
                print '<td align="right"></td>';
                print '<td align="center">' . dol_print_date($link->datea, "dayhour", "tzuser") . '</td>';
                print '<td align="center"></td>';
                print '<td align="right">';
                print '<a href="' . $_SERVER['PHP_SELF'] . '?action=update&linkid=' . $link->id . $param . '" class="editfilelink" >' . img_edit() . '</a>';	// id= is included into $param
                if ($permtodelete) {
                    print ' &nbsp; <a href="'. $_SERVER['PHP_SELF'] .'?action=delete&linkid=' . $link->id . $param . '" class="deletefilelink">' . img_delete() . '</a>';	// id= is included into $param
                } else {
                    print '&nbsp;';
                }
                print '</td>';
            }
            print "</tr>\n";
        }
        if ($nboflinks == 0)
        {
            print '<tr ' . $bc[false] . '><td colspan="5" class="opacitymedium">';
            print $langs->trans("NoLinkFound");
            print '</td></tr>';
        }
        print "</table>";

        print '</form>';

        return $nboflinks;
    }
    
    
    /**
     * Show detail icon with link for preview
     * 
     * @param   array     $file           File
     * @param   string    $modulepart     propal, facture, facture_fourn, ...
     * @param   string    $relativepath   Relative path of docs
     * @param   string    $ruleforpicto   Rule for picto: 0=Preview picto, 1=Use picto of mime type of file)
     * @return  string    $out            Output string with HTML
     */
    public function showPreview($file, $modulepart, $relativepath, $ruleforpicto=0)
    {
        global $langs, $conf;

        $out='';
        if ($conf->browser->layout != 'phone')
        {
            $urladvancedpreview=getAdvancedPreviewUrl($modulepart, $relativepath);      // Return if a file is qualified for preview
            if ($urladvancedpreview)
            {
                $out.= '<a data-ajax="false" class="pictopreview" href="'.$urladvancedpreview.'">';
                if (empty($ruleforpicto)) $out.= img_picto($langs->trans('Preview').' '.$file['name'], 'detail');
                else $out.= img_mime($relativepath, $langs->trans('Preview').' '.$file['name']);
                $out.= '</a>';
            }
        }
        return $out;
    }

}

