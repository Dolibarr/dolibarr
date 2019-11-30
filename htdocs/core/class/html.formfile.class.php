<?php
/* Copyright (C) 2008-2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2016	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Charles-Fr BENKE	<charles.fr@benke.fr>
 * Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes		<bafbes@gmail.com>
 * Copyright (C) 2016-2017	Ferran Marcet		<fmarcet@2byte.es>

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
	private $db;

	public $error;
	public $numoffiles;
	public $infofiles;			// Used to return informations by function getDocumentsLink


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
	 *  Show form to upload a new file.
	 *
	 *  @param  string		$url			Url
	 *  @param  string		$title			Title zone (Title or '' or 'none')
	 *  @param  int			$addcancel		1=Add 'Cancel' button
	 *	@param	int			$sectionid		If upload must be done inside a particular ECM section (is sectionid defined, sectiondir must not be)
	 * 	@param	int			$perm			Value of permission to allow upload
	 *  @param  int			$size          		Length of input file area. Deprecated.
	 *  @param	Object		$object			Object to use (when attachment is done on an element)
	 *  @param	string		$options		Add an option column
	 *  @param	integer		$useajax		Use fileupload ajax (0=never, 1=if enabled, 2=always whatever is option). @deprecated 2 should never be used and if 1 is used, option should no be enabled.
	 *  @param	string		$savingdocmask		Mask to use to define output filename. For example 'XXXXX-__YYYYMMDD__-__file__'
	 *  @param	integer		$linkfiles		1=Also add form to link files, 0=Do not show form to link files
	 *  @param	string		$htmlname		Name and id of HTML form ('formuserfile' by default, 'formuserfileecm' when used to upload a file in ECM)
	 *  @param	string		$accept			Specifies the types of files accepted (This is not a security check but an user interface facility. eg '.pdf,image/*' or '.png,.jpg' or 'video/*')
	 *	@param	string		$sectiondir		If upload must be done inside a particular directory (is sectiondir defined, sectionid must not be)
	 * 	@return	int							<0 if KO, >0 if OK
	 */
	function form_attach_new_file($url, $title='', $addcancel=0, $sectionid=0, $perm=1, $size=50, $object='', $options='', $useajax=1, $savingdocmask='', $linkfiles=1, $htmlname='formuserfile', $accept='', $sectiondir='')
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
			$out .= '<input type="hidden" id="'.$htmlname.'_section_dir" name="section_dir" value="'.$sectiondir.'">';
			$out .= '<input type="hidden" id="'.$htmlname.'_section_id"  name="section_id" value="'.$sectionid.'">';
			$out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

			$out .= '<table width="100%" class="nobordernopadding">';
			$out .= '<tr>';

			if (! empty($options)) $out .= '<td>'.$options.'</td>';

			$out .= '<td class="valignmiddle nowrap">';

			$max=$conf->global->MAIN_UPLOAD_DOC;		// En Kb
			$maxphp=@ini_get('upload_max_filesize');	// En inconnu
			if (preg_match('/k$/i',$maxphp)) $maxphp=$maxphp*1;
			if (preg_match('/m$/i',$maxphp)) $maxphp=$maxphp*1024;
			if (preg_match('/g$/i',$maxphp)) $maxphp=$maxphp*1024*1024;
			if (preg_match('/t$/i',$maxphp)) $maxphp=$maxphp*1024*1024*1024;
			// Now $max and $maxphp are in Kb
			$maxmin = $max;
			if ($maxphp > 0) $maxmin=min($max,$maxphp);

			if ($maxmin > 0)
			{
				// MAX_FILE_SIZE doit précéder le champ input de type file
				$out .= '<input type="hidden" name="max_file_size" value="'.($maxmin*1024).'">';
			}

			$out .= '<input class="flat minwidth400" type="file"';
			$out .= ((! empty($conf->global->MAIN_DISABLE_MULTIPLE_FILEUPLOAD) || $conf->browser->layout != 'classic')?' name="userfile"':' name="userfile[]" multiple');
			$out .= (empty($conf->global->MAIN_UPLOAD_DOC) || empty($perm)?' disabled':'');
			$out .= (!empty($accept)?' accept="'.$accept.'"':' accept=""');
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
				$out .= "\n<!-- Start form link new url -->\n";
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

				$out .= "\n<!-- End form link new url -->\n";
			}

			$parameters = array('socid'=>(isset($GLOBALS['socid'])?$GLOBALS['socid']:''), 'id'=>(isset($GLOBALS['id'])?$GLOBALS['id']:''), 'url'=>$url, 'perm'=>$perm);
			$res = $hookmanager->executeHooks('formattachOptions',$parameters,$object);
			if (empty($res))
			{
				print '<div class="attacharea attacharea'.$htmlname.'">';
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
	 * 		@param		int					$notused	        Not used
	 * 		@param		integer				$noform				Do not output html form tags
	 * 		@param		string				$param				More param on http links
	 * 		@param		string				$title				Title to show on top of form
	 * 		@param		string				$buttonlabel		Label on submit button
	 * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
	 * 		@return		int										<0 if KO, number of shown files if OK
	 *      @deprecated                                         Use print xxx->showdocuments() instead.
	 */
	function show_documents($modulepart,$modulesubdir,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$allowgenifempty=1,$forcenomultilang=0,$iconPDF=0,$notused=0,$noform=0,$param='',$title='',$buttonlabel='',$codelang='')
	{
		$this->numoffiles=0;
		print $this->showdocuments($modulepart,$modulesubdir,$filedir,$urlsource,$genallowed,$delallowed,$modelselected,$allowgenifempty,$forcenomultilang,$iconPDF,$notused,$noform,$param,$title,$buttonlabel,$codelang);
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
	 * 		@param		int					$notused	        Not used
	 * 		@param		integer				$noform				Do not output html form tags
	 * 		@param		string				$param				More param on http links
	 * 		@param		string				$title				Title to show on top of form
	 * 		@param		string				$buttonlabel		Label on submit button
	 * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
	 * 		@param		string				$morepicto			Add more HTML content into cell with picto
	 *      @param      Object              $object             Object when method is called from an object card.
	 *      @param		int					$hideifempty		Hide section of generated files if there is no file
	 * 		@return		string              					Output string with HTML array of documents (might be empty string)
	 */
	function showdocuments($modulepart,$modulesubdir,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$allowgenifempty=1,$forcenomultilang=0,$iconPDF=0,$notused=0,$noform=0,$param='',$title='',$buttonlabel='',$codelang='',$morepicto='',$object=null,$hideifempty=0)
	{
		// Deprecation warning
		if (! empty($iconPDF)) {
			dol_syslog(__METHOD__ . ": passing iconPDF parameter is deprecated", LOG_WARNING);
		}

		global $langs, $conf, $user, $hookmanager;
		global $form;

		if (! is_object($form)) $form=new Form($this->db);

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// For backward compatibility
		if (! empty($iconPDF)) {
			return $this->getDocumentsLink($modulepart, $modulesubdir, $filedir);
		}

		// Add entity in $param if not already exists
		if (!preg_match('/entity\=[0-9]+/', $param)) {
			$param.= 'entity='.(!empty($object->entity)?$object->entity:$conf->entity);
		}

		$printer=0;
		if (in_array($modulepart,array('facture','supplier_proposal','propal','proposal','order','commande','expedition', 'commande_fournisseur', 'expensereport')))	// The direct print feature is implemented only for such elements
		{
			$printer = (!empty($user->rights->printing->read) && !empty($conf->printing->enabled))?true:false;
		}

		$hookmanager->initHooks(array('formfile'));

		// Get list of files
		$file_list=null;
		if (! empty($filedir))
		{
			$file_list=dol_dir_list($filedir,'files',0,'','(\.meta|_preview.*.*\.png)$','date',SORT_DESC);
		}
		if ($hideifempty && empty($file_list)) return '';

		$out='';
		$forname='builddoc';
		$headershown=0;
		$showempty=0;
		$i=0;

		$out.= "\n".'<!-- Start show_document -->'."\n";
		//print 'filedir='.$filedir;

		if (preg_match('/massfilesarea_/', $modulepart))
		{
			$out.='<div id="show_files"><br></div>'."\n";
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
			elseif ($modulepart == 'product')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
					$modellist=ModelePDFProduct::liste_modeles($this->db);
				}
			}
			elseif ($modulepart == 'product_batch')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/product_batch/modules_product_batch.class.php';
					$modellist=ModelePDFProductBatch::liste_modeles($this->db);
				}
			}
			elseif ($modulepart == 'stock')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_stock.php';
					$modellist=ModelePDFStock::liste_modeles($this->db);
				}
			}
			elseif ($modulepart == 'movement')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_movement.php';
					$modellist=ModelePDFMovement::liste_modeles($this->db);
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
			else if ($modulepart == 'commande_fournisseur' || $modulepart == 'supplier_order')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
					$modellist=ModelePDFSuppliersOrders::liste_modeles($this->db);
				}
			}
			else if ($modulepart == 'facture_fournisseur' || $modulepart == 'supplier_invoice')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
					$modellist=ModelePDFSuppliersInvoices::liste_modeles($this->db);
				}
			}
			else if ($modulepart == 'supplier_payment')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_payment/modules_supplier_payment.php';
					$modellist=ModelePDFSuppliersPayments::liste_modeles($this->db);
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
			elseif ($modulepart == 'agenda' || $modulepart == 'actions')
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
			elseif ($modulepart == 'user')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/user/modules_user.class.php';
					$modellist=ModelePDFUser::liste_modeles($this->db);
				}
			}
			elseif ($modulepart == 'usergroup')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/usergroup/modules_usergroup.class.php';
					$modellist=ModelePDFUserGroup::liste_modeles($this->db);
				}
			}
			else
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
				$class='ModelePDF'.ucfirst($modulepart);
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

			// Set headershown to avoid to have table opened a second time later
			$headershown=1;

			$buttonlabeltoshow=$buttonlabel;
			if (empty($buttonlabel)) $buttonlabel=$langs->trans('Generate');

			if ($conf->browser->layout == 'phone') $urlsource.='#'.$forname.'_form';   // So we switch to form after a generation
			if (empty($noform)) $out.= '<form action="'.$urlsource.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc').'" id="'.$forname.'_form" method="post">';
			$out.= '<input type="hidden" name="action" value="builddoc">';
			$out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

			$out.= load_fiche_titre($titletoshow, '', '');
			$out.= '<div class="div-table-responsive-no-min">';
			$out.= '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';

			$out.= '<tr class="liste_titre">';

			$addcolumforpicto=($delallowed || $printer || $morepicto);
			$out.= '<th align="center" colspan="'.(3+($addcolumforpicto?1:0)).'" class="formdoc liste_titre maxwidthonsmartphone">';

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
				if ($conf->use_javascript_ajax)
				{
					$out.= ajax_combobox('model');
				}
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
			if ((! empty($file_list) || ! empty($link_list) || preg_match('/^massfilesarea/', $modulepart))
				&& ! $headershown)
			{
				$headershown=1;
				$out.= '<div class="titre">'.$titletoshow.'</div>'."\n";
				$out.= '<div class="div-table-responsive-no-min">';
				$out.= '<table class="noborder" summary="listofdocumentstable" id="'.$modulepart.'_table" width="100%">'."\n";
			}

			// Loop on each file found
			if (is_array($file_list))
			{
				foreach($file_list as $file)
				{
					// Define relative path for download link (depends on module)
					$relativepath=$file["name"];										// Cas general
					if ($modulesubdir) $relativepath=$modulesubdir."/".$file["name"];	// Cas propal, facture...
					if ($modulepart == 'export') $relativepath = $file["name"];			// Other case

					$out.= '<tr class="oddeven">';

					$documenturl = DOL_URL_ROOT.'/document.php';
					if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl=$conf->global->DOL_URL_ROOT_DOCUMENT_PHP;    // To use another wrapper

					// Show file name with link to download
					$out.= '<td class="minwidth200">';
					$out.= '<a class="documentdownload paddingright" href="'.$documenturl.'?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).($param?'&'.$param:'').'"';
					$mime=dol_mimetype($relativepath,'',0);
					if (preg_match('/text/',$mime)) $out.= ' target="_blank"';
					$out.= '>';
					$out.= img_mime($file["name"],$langs->trans("File").': '.$file["name"]);
					$out.= dol_trunc($file["name"], 150);
					$out.= '</a>'."\n";
					$out.= $this->showPreview($file,$modulepart,$relativepath,0,$param);
					$out.= '</td>';

					// Show file size
					$size=(! empty($file['size'])?$file['size']:dol_filesize($filedir."/".$file["name"]));
					$out.= '<td align="right" class="nowrap">'.dol_print_size($size,1,1).'</td>';

					// Show file date
					$date=(! empty($file['date'])?$file['date']:dol_filemtime($filedir."/".$file["name"]));
					$out.= '<td align="right" class="nowrap">'.dol_print_date($date, 'dayhour', 'tzuser').'</td>';

					if ($delallowed || $printer || $morepicto)
					{
						$out.= '<td class="right nowraponall">';
						if ($delallowed)
						{
							$tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
							$out.= '<a href="'.$tmpurlsource.(strpos($tmpurlsource,'?')?'&amp;':'?').'action=remove_file&amp;file='.urlencode($relativepath);
							$out.= ($param?'&amp;'.$param:'');
							//$out.= '&modulepart='.$modulepart; // TODO obsolete ?
							//$out.= '&urlsource='.urlencode($urlsource); // TODO obsolete ?
							$out.= '">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						}
						if ($printer)
						{
							//$out.= '<td align="right">';
							$out.= '<a class="paddingleft" href="'.$urlsource.(strpos($urlsource,'?')?'&amp;':'?').'action=print_file&amp;printer='.$modulepart.'&amp;file='.urlencode($relativepath);
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
							$out.= $hookmanager->resPrint;		// Complete line
							$out.= '</tr>';
						}
						else $out = $hookmanager->resPrint;		// Replace line
			  		}
				}

				$this->numoffiles++;
			}
			// Loop on each link found
			if (is_array($link_list))
			{
				$colspan=2;

				foreach($link_list as $file)
				{
					$out.='<tr class="oddeven">';
					$out.='<td colspan="'.$colspan.'" class="maxwidhtonsmartphone">';
					$out.='<a data-ajax="false" href="' . $link->url . '" target="_blank">';
					$out.=$file->label;
					$out.='</a>';
					$out.='</td>';
					$out.='<td align="right">';
					$out.=dol_print_date($file->datea,'dayhour');
					$out.='</td>';
					if ($delallowed || $printer || $morepicto) $out.='<td></td>';
					$out.='</tr>'."\n";
				}
				$this->numoffiles++;
			}

		 	if (count($file_list) == 0 && count($link_list) == 0 && $headershown)
			{
				$out.='<tr><td colspan="'.(3+($addcolumforpicto?1:0)).'" class="opacitymedium">'.$langs->trans("None").'</td></tr>'."\n";
			}

		}

		if ($headershown)
		{
			// Affiche pied du tableau
			$out.= "</table>\n";
			$out.= "</div>\n";
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

		// Get object entity
		if (empty($conf->multicompany->enabled))
		{
			$entity = $conf->entity;
		}
		else
		{
			preg_match('/\/([0-9]+)\/[^\/]+\/'.preg_quote($modulesubdir,'/').'$/', $filedir, $regs);
			$entity = ((! empty($regs[1]) && $regs[1] > 1) ? $regs[1] : $conf->entity);
		}

		// Get list of files starting with name of ref (but not followed by "-" to discard uploaded files and get only generated files)
		// @TODO Why not showing by default all files by just removing the '[^\-]+' at end of regex ?
		if (! empty($conf->global->MAIN_SHOW_ALL_FILES_ON_DOCUMENT_TOOLTIP))
		{
			$filterforfilesearch = preg_quote(basename($modulesubdir),'/');
		}
		else
		{
			$filterforfilesearch = preg_quote(basename($modulesubdir),'/').'[^\-]+';
		}
		$file_list=dol_dir_list($filedir, 'files', 0, $filterforfilesearch, '\.meta$|\.png$');	// We also discard .meta and .png preview

		//var_dump($file_list);
		// For ajax treatment
		$out.= '<!-- html.formfile::getDocumentsLink -->'."\n";
		if (! empty($file_list))
		{
			$out='<dl class="dropdown inline-block">
    			<dt><a data-ajax="false" href="#" onClick="return false;">'.img_picto('', 'listlight', '', 0, 0, 0, '', 'valignbottom').'</a></dt>
    			<dd><div class="multichoicedoc" style="position:absolute;left:100px;" ><ul class="ulselectedfields" style="display: none;">';
			$tmpout='';

			// Loop on each file found
			$found=0;
			foreach($file_list as $file)
			{
				$i++;
				if ($filter && ! preg_match('/'.$filter.'/i', $file["name"])) continue;	// Discard this. It does not match provided filter.

				$found++;
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
				if (! empty($conf->use_javascript_ajax) && ($conf->browser->layout != 'phone'))
				{
					$tmparray = getAdvancedPreviewUrl($modulepart, $relativepath, 1, '&entity='.$entity);
					if ($tmparray && $tmparray['url'])
					{
						$tmpout.= '<li><a href="'.$tmparray['url'].'"'.($tmparray['css']?' class="'.$tmparray['css'].'"':'').($tmparray['mime']?' mime="'.$tmparray['mime'].'"':'').($tmparray['target']?' target="'.$tmparray['target'].'"':'').'>';
						//$tmpout.= img_picto('','detail');
						$tmpout.= '<i class="fa fa-search-plus paddingright" style="color: gray"></i>';
						$tmpout.= $langs->trans("Preview").' '.$ext.'</a></li>';
					}
				}

				// Download
				$tmpout.= '<li class="nowrap"><a class="pictopreview nowrap" href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;entity='.$entity.'&amp;file='.urlencode($relativepath).'"';
				$mime=dol_mimetype($relativepath,'',0);
				if (preg_match('/text/',$mime)) $tmpout.= ' target="_blank"';
				$tmpout.= '>';
				$tmpout.= img_mime($relativepath, $file["name"]);
				$tmpout.= $langs->trans("Download").' '.$ext;
				$tmpout.= '</a></li>'."\n";
			}
			$out.=$tmpout;
			$out.='</ul></div></dd>
    			</dl>';

			if (! $found) $out='';
		}
		else
		{
			// TODO Add link to regenerate doc ?
			//$out.= '<div id="gen_pdf_'.$modulesubdir.'" class="linkobject hideobject">'.img_picto('', 'refresh').'</div>'."\n";
		}

		return $out;
	}


	/**
	 *  Show list of documents in $filearray (may be they are all in same directory but may not)
	 *  This also sync database if $upload_dir is defined.
	 *
	 *  @param	 array	$filearray          Array of files loaded by dol_dir_list('files') function before calling this.
	 * 	@param	 Object	$object				Object on which document is linked to.
	 * 	@param	 string	$modulepart			Value for modulepart used by download or viewimage wrapper.
	 * 	@param	 string	$param				Parameters on sort links (param must start with &, example &aaa=bbb&ccc=ddd)
	 * 	@param	 int	$forcedownload		Force to open dialog box "Save As" when clicking on file.
	 * 	@param	 string	$relativepath		Relative path of docs (autodefined if not provided), relative to module dir, not to MAIN_DATA_ROOT.
	 * 	@param	 int	$permonobject		Permission on object (so permission to delete or crop document)
	 * 	@param	 int	$useinecm			Change output for use in ecm module:
	 * 										0: Add a previw link. Show also rename and crop file
	 * 										1: Add link to edit ECM entry
	 * 										2: Add rename and crop file
	 * 	@param	 string	$textifempty		Text to show if filearray is empty ('NoFileFound' if not defined)
	 *  @param   int	$maxlength          Maximum length of file name shown.
	 *  @param	 string	$title				Title before list. Use 'none' to disable title.
	 *  @param	 string $url				Full url to use for click links ('' = autodetect)
	 *  @param	 int	$showrelpart		0=Show only filename (default), 1=Show first level 1 dir
	 *  @param   int    $permtoeditline     Permission to edit document line (You must provide a value, -1 is deprecated and must not be used any more)
	 *  @param   string $upload_dir         Full path directory so we can know dir relative to MAIN_DATA_ROOT. Fill this to complete file data with database indexes.
	 *  @param   string $sortfield          Sort field ('name', 'size', 'position', ...)
	 *  @param   string $sortorder          Sort order ('ASC' or 'DESC')
	 *  @param   int    $disablemove        1=Disable move button, 0=Position move is possible.
	 *  @param	 int	$addfilterfields	Add line with filters
	 * 	@return	 int						<0 if KO, nb of files shown if OK
	 *  @see list_of_autoecmfiles
	 */
	function list_of_documents($filearray,$object,$modulepart,$param='',$forcedownload=0,$relativepath='',$permonobject=1,$useinecm=0,$textifempty='',$maxlength=0,$title='',$url='', $showrelpart=0, $permtoeditline=-1,$upload_dir='',$sortfield='',$sortorder='ASC', $disablemove=1, $addfilterfields=0)
	{
		global $user, $conf, $langs, $hookmanager;
		global $sortfield, $sortorder, $maxheightmini;
		global $dolibarr_main_url_root;
		global $form;

		$disablecrop=1;
		if (in_array($modulepart, array('societe','product','produit','service','expensereport','holiday','member','project','ticket','user'))) $disablecrop=0;

		// Define relative path used to store the file
		if (empty($relativepath))
		{
			$relativepath=(! empty($object->ref)?dol_sanitizeFileName($object->ref):'').'/';
			if ($object->element == 'invoice_supplier') $relativepath=get_exdir($object->id,2,0,0,$object,'invoice_supplier').$relativepath;	// TODO Call using a defined value for $relativepath
			if ($object->element == 'project_task') $relativepath='Call_not_supported_._Call_function_using_a_defined_relative_path_.';
		}
		// For backward compatiblity, we detect file stored into an old path
		if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO) && $filearray[0]['level1name'] == 'photos')
		{
		    $relativepath=preg_replace('/^.*\/produit\//','',$filearray[0]['path']).'/';
		}
		// Defined relative dir to DOL_DATA_ROOT
		$relativedir = '';
		if ($upload_dir)
		{
			$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $upload_dir);
			$relativedir = preg_replace('/^[\\/]/','',$relativedir);
		}

		$hookmanager->initHooks(array('formfile'));
		$parameters=array(
				'filearray' => $filearray,
				'modulepart'=> $modulepart,
				'param' => $param,
				'forcedownload' => $forcedownload,
				'relativepath' => $relativepath,    // relative filename to module dir
				'relativedir' => $relativedir,      // relative dirname to DOL_DATA_ROOT
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
			if (! is_object($form))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';		// The compoent may be included into ajax page that does not include the Form class
				$form=new Form($this->db);
			}

			if (! preg_match('/&id=/', $param) && isset($object->id)) $param.='&id='.$object->id;
			$relativepathwihtoutslashend=preg_replace('/\/$/', '', $relativepath);
			if ($relativepathwihtoutslashend) $param.= '&file='.urlencode($relativepathwihtoutslashend);

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
			if (empty($useinecm) && $title != 'none') print load_fiche_titre($title?$title:$langs->trans("AttachedFiles"));
			if (empty($url)) $url=$_SERVER["PHP_SELF"];

			print '<!-- html.formfile::list_of_documents -->'."\n";
			if (GETPOST('action','aZ09') == 'editfile' && $permtoeditline)
			{
				print '<form action="'.$_SERVER["PHP_SELF"].'?'.$param.'" method="POST">';
				print '<input type="hidden" name="action" value="renamefile">';
				print '<input type="hidden" name="id" value="'.$object->id.'">';
				print '<input type="hidden" name="modulepart" value="'.$modulepart.'">';
			}

			print '<div class="div-table-responsive-no-min">';
			print '<table width="100%" id="tablelines" class="'.($useinecm?'liste noborder':'liste').'">'."\n";

			if (! empty($addfilterfields))
			{
				print '<tr class="liste_titre nodrag nodrop">';
				print '<td><input type="search_doc_ref" value="'.dol_escape_htmltag(GETPOST('search_doc_ref','alpha')).'"></td>';
				print '<td></td>';
				print '<td></td>';
				if (empty($useinecm)) print '<td></td>';
				print '<td></td>';
				print '<td></td>';
				if (! $disablemove) print '<td></td>';
				print "</tr>\n";
			}

			print '<tr class="liste_titre nodrag nodrop">';
			//print $url.' sortfield='.$sortfield.' sortorder='.$sortorder;
			print_liste_field_titre('Documents2',$url,"name","",$param,'align="left"',$sortfield,$sortorder);
			print_liste_field_titre('Size',$url,"size","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre('Date',$url,"date","",$param,'align="center"',$sortfield,$sortorder);
			if (empty($useinecm)) print_liste_field_titre('',$url,"","",$param,'align="center"');					// Preview
			print_liste_field_titre('');
			print_liste_field_titre('');
			if (! $disablemove) print_liste_field_titre('');
			print "</tr>\n";

			// Get list of files stored into database for same relative directory
			if ($relativedir)
			{
				completeFileArrayWithDatabaseInfo($filearray, $relativedir);

				//var_dump($sortfield.' - '.$sortorder);
				if ($sortfield && $sortorder)	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
				{
					//var_dump($sortfield);
					$filearray=dol_sort_array($filearray, $sortfield, $sortorder);
				}
			}

			$nboffiles=count($filearray);
			if ($nboffiles > 0) include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

			$i=0; $nboflines = 0; $lastrowid=0;
			foreach($filearray as $key => $file)      // filearray must be only files here
			{
				if ($file['name'] != '.'
						&& $file['name'] != '..'
						&& ! preg_match('/\.meta$/i',$file['name']))
				{
					if ($filearray[$key]['rowid'] > 0) $lastrowid = $filearray[$key]['rowid'];
					$filepath=$relativepath.$file['name'];

					$editline=0;
					$nboflines++;
					print '<!-- Line list_of_documents '.$key.' relativepath = '.$relativepath.' -->'."\n";
					// Do we have entry into database ?
					print '<!-- In database: position='.$filearray[$key]['position'].' -->'."\n";
					print '<tr id="row-'.($filearray[$key]['rowid']>0?$filearray[$key]['rowid']:'-AFTER'.$lastrowid.'POS'.($i+1)).'">';

					// File name
					print '<td class="minwith200">';

					// Show file name with link to download
					//print "XX".$file['name'];	//$file['name'] must be utf8
					print '<a class="paddingright" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
					if ($forcedownload) print '&attachment=1';
					if (! empty($object->entity)) print '&entity='.$object->entity;
					print '&file='.urlencode($filepath);
					print '">';
					print img_mime($file['name'], $file['name'].' ('.dol_print_size($file['size'],0,0).')', 'inline-block valignbottom paddingright');
					if ($showrelpart == 1) print $relativepath;
					//print dol_trunc($file['name'],$maxlength,'middle');
					if (GETPOST('action','aZ09') == 'editfile' && $file['name'] == basename(GETPOST('urlfile','alpha')))
					{
						print '</a>';
						$section_dir=dirname(GETPOST('urlfile','alpha'));
						print '<input type="hidden" name="section_dir" value="'.$section_dir.'">';
						print '<input type="hidden" name="renamefilefrom" value="'.dol_escape_htmltag($file['name']).'">';
						print '<input type="text" name="renamefileto" class="quatrevingtpercent" value="'.dol_escape_htmltag($file['name']).'">';
						$editline=1;
					}
					else
					{
						print dol_trunc($file['name'], 200);
						print '</a>';
					}
					// Preview link
					if (! $editline) print $this->showPreview($file, $modulepart, $filepath);
					// Public share link
					//if (! $editline && ! empty($filearray[$key]['hashp'])) print pictowithlinktodirectdownload;

					print "</td>\n";

					// Size
					$sizetoshow = dol_print_size($file['size'],1,1);
					$sizetoshowbytes = dol_print_size($file['size'],0,1);

					print '<td align="right" width="80px">';
					if ($sizetoshow == $sizetoshowbytes) print $sizetoshow;
					else {
						print $form->textwithpicto($sizetoshow, $sizetoshowbytes, -1);
					}
					print '</td>';

					// Date
					print '<td align="center" width="140px">'.dol_print_date($file['date'],"dayhour","tzuser").'</td>';	// 140px = width for date with PM format

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

							$urlforhref=getAdvancedPreviewUrl($modulepart, $relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']), 0, '&entity='.(!empty($object->entity)?$object->entity:$conf->entity));
							if (empty($urlforhref)) $urlforhref=DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file='.urlencode($relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']));
							print '<a href="'.$urlforhref.'" class="aphoto" target="_blank">';
							print '<img border="0" height="'.$maxheightmini.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file='.urlencode($relativepath.$minifile).'" title="">';
							print '</a>';
						}
						else print '&nbsp;';
						print '</td>';
					}

					// Hash of file (only if we are in a mode where a scan of dir were done and we have id of file in ECM table)
					print '<td align="center">';
					if ($relativedir && $filearray[$key]['rowid'] > 0)
					{
						if ($editline)
						{
							print $langs->trans("FileSharedViaALink").' ';
							print '<input class="inline-block" type="checkbox" name="shareenabled"'.($file['share']?' checked="checked"':'').' /> ';
						}
						else
						{
							if ($file['share'])
							{
								// Define $urlwithroot
								$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
								$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
								//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

								//print '<span class="opacitymedium">'.$langs->trans("Hash").' : '.$file['share'].'</span>';
								$forcedownload=0;
								$paramlink='';
								if (! empty($file['share'])) $paramlink.=($paramlink?'&':'').'hashp='.$file['share'];			// Hash for public share
								if ($forcedownload) $paramlink.=($paramlink?'&':'').'attachment=1';

								$fulllink=$urlwithroot.'/document.php'.($paramlink?'?'.$paramlink:'');
								//if (! empty($object->ref))       $fulllink.='&hashn='.$object->ref;		// Hash of file path
								//elseif (! empty($object->label)) $fulllink.='&hashc='.$object->label;		// Hash of file content

								print img_picto($langs->trans("FileSharedViaALink"),'object_globe.png').' ';
								print '<input type="text" class="quatrevingtpercent" id="downloadlink" name="downloadexternallink" value="'.dol_escape_htmltag($fulllink).'">';
								//print ' <a href="'.$fulllink.'">'.$langs->trans("Download").'</a>';	// No target here
							}
							else
							{
								//print '<span class="opacitymedium">'.$langs->trans("FileNotShared").'</span>';
							}
						}
					}
					print '</td>';

					// Actions buttons
					if (! $editline)
					{
						// Delete or view link
						// ($param must start with &)
						print '<td class="valignmiddle right actionbuttons"><!-- action on files -->';
						if ($useinecm == 1)
						{
							print '<a href="'.DOL_URL_ROOT.'/ecm/file_card.php?urlfile='.urlencode($file['name']).$param.'" class="editfilelink" rel="'.urlencode($file['name']).'">'.img_edit('default', 0, 'class="paddingrightonly"').'</a>';
						}
						if (! $useinecm || $useinecm == 2)
						{
							$newmodulepart=$modulepart;
							if (in_array($modulepart, array('product','produit','service'))) $newmodulepart='produit|service';

							if (! $disablecrop && image_format_supported($file['name']) > 0)
							{
								if ($permtoeditline)
								{
	   								// Link to resize
	   						   		print '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode($newmodulepart).'&id='.$object->id.'&file='.urlencode($relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension'])).'" title="'.dol_escape_htmltag($langs->trans("ResizeOrCrop")).'">'.img_picto($langs->trans("ResizeOrCrop"),'resize','class="paddingrightonly"').'</a>';
								}
							}

							if ($permtoeditline)
							{
								print '<a href="'.(($useinecm == 1)?'#':($url.'?action=editfile&urlfile='.urlencode($filepath).$param)).'" class="editfilelink" rel="'.$filepath.'">'.img_edit('default',0,'class="paddingrightonly"').'</a>';
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
							print '<a href="'.(($useinecm && $useajax)?'#':($url.'?action=delete&urlfile='.urlencode($filepath).$param)).'" class="deletefilelink" rel="'.$filepath.'">'.img_delete().'</a>';
						}
						print "</td>";

						if (empty($disablemove))
						{
							if ($nboffiles > 1 && empty($conf->browser->phone)) {
								print '<td align="center" class="linecolmove tdlineupdown">';
								if ($i > 0) {
									print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=up&amp;rowid='.$line->id.'">'.img_up('default',0,'imgupforline').'</a>';
								}
								if ($i < $nboffiles-1) {
									print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=down&amp;rowid='.$line->id.'">'.img_down('default',0,'imgdownforline').'</a>';
								}
								print '</td>';
							}
							else {
							   	print '<td align="center"'.((empty($conf->browser->phone) && empty($disablemove)) ?' class="linecolmove tdlineupdown"':' class="linecolmove"').'>';
							   	print '</td>';
							}
					   }
					}
					else
					{
						print '<td class="right">';
						print '<input type="hidden" name="ecmfileid" value="'.$filearray[$key]['rowid'].'">';
						print '<input type="submit" class="button" name="renamefilesave" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
						print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
						print '</td>';
						if (empty($disablemove)) print '<td class="right"></td>';
					}
					print "</tr>\n";

					$i++;
				}
			}
			if ($nboffiles == 0)
			{
				$colspan=(empty($useinecm)?'6':'6');
				if (empty($disablemove)) $colspan++;		// 6 columns or 7
				print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">';
				if (empty($textifempty)) print $langs->trans("NoFileFound");
				else print $textifempty;
				print '</td></tr>';
			}
			print "</table>";
			print '</div>';

			if ($nboflines > 1 && is_object($object)) {
				if (! empty($conf->use_javascript_ajax) && $permtoeditline) {
					$table_element_line = 'ecm_files';
					include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
				}
			}

			print ajax_autoselect('downloadlink');

			if (GETPOST('action','aZ09') == 'editfile' && $permtoeditline)
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
	 *  @param	string 	$url				Full url to use for click links ('' = autodetect)
	 *  @param	int		$addfilterfields	Add line with filters
	 *  @return int                 		<0 if KO, nb of files shown if OK
	 *  @see list_of_documents
	 */
	function list_of_autoecmfiles($upload_dir, $filearray, $modulepart, $param, $forcedownload=0, $relativepath='', $permtodelete=1, $useinecm=0, $textifempty='', $maxlength=0, $url='', $addfilterfields=0)
	{
		global $user, $conf, $langs, $form;
		global $sortfield, $sortorder;
		global $search_doc_ref;

		dol_syslog(get_class($this).'::list_of_autoecmfiles upload_dir='.$upload_dir.' modulepart='.$modulepart);

		// Show list of documents
		if (empty($useinecm)) print load_fiche_titre($langs->trans("AttachedFiles"));
		if (empty($url)) $url=$_SERVER["PHP_SELF"];

		if (! empty($addfilterfields))
		{
			print '<form action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="module" value="'.$modulepart.'">';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table width="100%" class="noborder">'."\n";

		if (! empty($addfilterfields))
		{
			print '<tr class="liste_titre nodrag nodrop">';
			print '<td></td>';
			print '<td><input type="text" class="maxwidth100onsmartphone" name="search_doc_ref" value="'.dol_escape_htmltag($search_doc_ref).'"></td>';
			print '<td></td>';
			print '<td></td>';
			// Action column
			print '<td class="liste_titre" align="middle">';
			$searchpicto=$form->showFilterButtons();
			print $searchpicto;
			print '</td>';
			print "</tr>\n";
		}

		print '<tr class="liste_titre">';
		$sortref="fullname";
		if ($modulepart == 'invoice_supplier') $sortref='level1name';
		print_liste_field_titre("Ref",$url,$sortref,"",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre("Documents2",$url,"name","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre("Size",$url,"size","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre("Date",$url,"date","",$param,'align="center"',$sortfield,$sortorder);
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
		else if ($modulepart == 'holiday')
		{
			include_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
			$object_instance=new Holiday($this->db);
		}

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
				if ($modulepart == 'company')           { preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'invoice')           { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'invoice_supplier')  { preg_match('/([^\/]+)\/[^\/]+$/',$relativefile,$reg); $ref=(isset($reg[1])?$reg[1]:''); if (is_numeric($ref)) { $id=$ref; $ref=''; } }	// $ref may be also id with old supplier invoices
				if ($modulepart == 'propal')            { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'supplier_proposal') { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'order')             { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'order_supplier')    { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'contract')          { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'product')           { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'tax')               { preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=(isset($reg[1])?$reg[1]:''); }
				if ($modulepart == 'project')           { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:'');}
				if ($modulepart == 'fichinter')         { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:'');}
				if ($modulepart == 'user')              { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $id=(isset($reg[1])?$reg[1]:'');}
				if ($modulepart == 'expensereport')     { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=(isset($reg[1])?$reg[1]:'');}
				if ($modulepart == 'holiday')           { preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $id=(isset($reg[1])?$reg[1]:'');}

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

				print '<!-- Line list_of_autoecmfiles '.$key.' -->'."\n";
				print '<tr class="oddeven">';
				print '<td>';
				if ($found > 0 && is_object($this->cache_objects[$modulepart.'_'.$id.'_'.$ref])) print $this->cache_objects[$modulepart.'_'.$id.'_'.$ref]->getNomUrl(1,'document');
				else print $langs->trans("ObjectDeleted",($id?$id:$ref));

				//$modulesubdir=dol_sanitizeFileName($ref);
				$modulesubdir=dirname($relativefile);

				//$filedir=$conf->$modulepart->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$filedir=$file['path'];
				//$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				//print $formfile->getDocumentsLink($modulepart, $filename, $filedir);

				print '</td>';

				// File
				print '<td>';
				//print "XX".$file['name']; //$file['name'] must be utf8
				print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
				if ($forcedownload) print '&attachment=1';
				print '&file='.urlencode($relativefile).'">';
				print img_mime($file['name'],$file['name'].' ('.dol_print_size($file['size'],0,0).')');
				print dol_trunc($file['name'],$maxlength,'middle');
				print '</a>';

				//print $this->getDocumentsLink($modulepart, $modulesubdir, $filedir, '^'.preg_quote($file['name'],'/').'$');
				print $this->showPreview($file, $modulepart, $file['relativename']);

				print "</td>\n";
				print '<td align="right">'.dol_print_size($file['size'],1,1).'</td>';
				print '<td align="center">'.dol_print_date($file['date'],"dayhour").'</td>';
				print '<td align="right">';
				//if (! empty($useinecm))  print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
				//if ($forcedownload) print '&attachment=1';
				//print '&file='.urlencode($relativefile).'">';
				//print img_view().'</a> &nbsp; ';
				//if ($permtodelete) print '<a href="'.$url.'?id='.$object->id.'&section='.$_REQUEST["section"].'&action=delete&urlfile='.urlencode($file['name']).'">'.img_delete().'</a>';
				//else print '&nbsp;';
				print "</td></tr>\n";
			}
		}

		if (count($filearray) == 0)
		{
			print '<tr class="oddeven"><td colspan="5">';
			if (empty($textifempty)) print $langs->trans("NoFileFound");
			else print $textifempty;
			print '</td></tr>';
		}
		print "</table>";
		print '</div>';

		if (! empty($addfilterfields)) print '</form>';
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
		global $langs, $conf;

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

		foreach ($links as $link)
		{
			print '<tr class="oddeven">';
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
				print $langs->trans('Label') . ': <input type="text" name="label" value="' . dol_escape_htmltag($link->label) . '">';
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
				print dol_escape_htmltag($link->label);
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
			print '<tr class="oddeven"><td colspan="5" class="opacitymedium">';
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
	 * @param   array     $file           Array with data of file. Example: array('name'=>...)
	 * @param   string    $modulepart     propal, facture, facture_fourn, ...
	 * @param   string    $relativepath   Relative path of docs
	 * @param   string    $ruleforpicto   Rule for picto: 0=Use the generic preview picto, 1=Use the picto of mime type of file)
	 * @param	string	  $param		  More param on http links
	 * @return  string    $out            Output string with HTML
	 */
	public function showPreview($file, $modulepart, $relativepath, $ruleforpicto=0, $param='')
	{
		global $langs, $conf;

		$out='';
		if ($conf->browser->layout != 'phone' && ! empty($conf->use_javascript_ajax))
		{
			$urladvancedpreview=getAdvancedPreviewUrl($modulepart, $relativepath, 1, $param);      // Return if a file is qualified for preview.
			if (count($urladvancedpreview))
			{
				$out.= '<a class="pictopreview '.$urladvancedpreview['css'].'" href="'.$urladvancedpreview['url'].'"'.(empty($urladvancedpreview['mime'])?'':' mime="'.$urladvancedpreview['mime'].'"').' '.(empty($urladvancedpreview['target'])?'':' target="'.$urladvancedpreview['target'].'"').'>';
				//$out.= '<a class="pictopreview">';
				if (empty($ruleforpicto))
				{
					//$out.= img_picto($langs->trans('Preview').' '.$file['name'], 'detail');
					$out.='<span class="fa fa-search-plus" style="color: gray"></span>';
				}
				else $out.= img_mime($relativepath, $langs->trans('Preview').' '.$file['name']);
				$out.= '</a>';
			}
		}
		return $out;
	}

}

