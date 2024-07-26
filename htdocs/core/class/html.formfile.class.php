<?php
/* Copyright (C) 2008-2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2016	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Charles-Fr BENKE	<charles.fr@benke.fr>
 * Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes		<bafbes@gmail.com>
 * Copyright (C) 2016-2017	Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019-2023  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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

	/**
	 * @var string Error code (or message)
	 */
	public $error;

	public $numoffiles;
	public $infofiles; // Used to return information by function getDocumentsLink


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numoffiles = 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show form to upload a new file.
	 *
	 *  @param  string		$url			Url
	 *  @param  string		$title			Title zone (Title or '' or 'none')
	 *  @param  int			$addcancel		1=Add 'Cancel' button
	 *	@param	int			$sectionid		If upload must be done inside a particular ECM section (is sectionid defined, sectiondir must not be)
	 * 	@param	int			$perm			Value of permission to allow upload
	 *  @param  int			$size          	Length of input file area. Deprecated.
	 *  @param	Object		$object			Object to use (when attachment is done on an element)
	 *  @param	string		$options		Add an option column
	 *  @param  integer     $useajax        Use fileupload ajax (0=never, 1=if enabled, 2=always whatever is option).
	 *                                      Deprecated 2 should never be used and if 1 is used, option should not be enabled.
	 *  @param	string		$savingdocmask	Mask to use to define output filename. For example 'XXXXX-__YYYYMMDD__-__file__'
	 *  @param	integer		$linkfiles		1=Also add form to link files, 0=Do not show form to link files
	 *  @param	string		$htmlname		Name and id of HTML form ('formuserfile' by default, 'formuserfileecm' when used to upload a file in ECM)
	 *  @param	string		$accept			Specifies the types of files accepted (This is not a security check but an user interface facility. eg '.pdf,image/*' or '.png,.jpg' or 'video/*')
	 *	@param	string		$sectiondir		If upload must be done inside a particular directory (if sectiondir defined, sectionid must not be)
	 *  @param  int         $usewithoutform 0=Default, 1=Disable <form> and <input hidden> to use in existing form area, 2=Disable the tag <form> only
	 *  @param	int			$capture		1=Add tag capture="capture" to force use of micro or video recording to generate file. When setting this to 1, you must also provide a value for $accept.
	 *  @param	int			$disablemulti	0=Default, 1=Disable multiple file upload
	 *  @param	int			$nooutput		0=Output result with print, 1=Return result
	 * 	@return	int|string					Return integer <0 if KO, >0 if OK, or string if $noouput=1
	 */
	public function form_attach_new_file($url, $title = '', $addcancel = 0, $sectionid = 0, $perm = 1, $size = 50, $object = null, $options = '', $useajax = 1, $savingdocmask = '', $linkfiles = 1, $htmlname = 'formuserfile', $accept = '', $sectiondir = '', $usewithoutform = 0, $capture = 0, $disablemulti = 0, $nooutput = 0)
	{
		// phpcs:enable
		global $conf, $langs, $hookmanager;
		$hookmanager->initHooks(array('formfile'));

		// Deprecation warning
		if ($useajax == 2) {
			dol_syslog(__METHOD__.": using 2 for useajax is deprecated and should be not used", LOG_WARNING);
		}

		if (!empty($conf->browser->layout) && $conf->browser->layout != 'classic') {
			$useajax = 0;
		}

		if ((getDolGlobalString('MAIN_USE_JQUERY_FILEUPLOAD') && $useajax) || ($useajax == 2)) {
			// TODO: Check this works with 2 forms on same page
			// TODO: Check this works with GED module, otherwise, force useajax to 0
			// TODO: This does not support option savingdocmask
			// TODO: This break feature to upload links too
			// TODO: Thisdoes not work when param nooutput=1
			//return $this->_formAjaxFileUpload($object);
			return 'Feature too bugged so removed';
		} else {
			//If there is no permission and the option to hide unauthorized actions is enabled, then nothing is printed
			if (!$perm && getDolGlobalString('MAIN_BUTTON_HIDE_UNAUTHORIZED')) {
				if ($nooutput) {
					return '';
				} else {
					return 1;
				}
			}

			$out = "\n\n".'<!-- Start form attach new file --><div class="formattachnewfile">'."\n";

			if (empty($title)) {
				$title = $langs->trans("AttachANewFile");
			}
			if ($title != 'none') {
				$out .= load_fiche_titre($title, null, null);
			}

			if (empty($usewithoutform)) {		// Try to avoid this and set instead the form by the caller.
				// Add a param as GET parameter to detect when POST were cleaned by PHP because a file larger than post_max_size
				$url .= (strpos($url, '?') === false ? '?' : '&').'uploadform=1';

				$out .= '<form name="'.$htmlname.'" id="'.$htmlname.'" action="'.$url.'" enctype="multipart/form-data" method="POST">'."\n";
			}
			if (empty($usewithoutform) || $usewithoutform == 2) {
				$out .= '<input type="hidden" name="token" value="'.newToken().'">'."\n";
				$out .= '<input type="hidden" id="'.$htmlname.'_section_dir" name="section_dir" value="'.$sectiondir.'">'."\n";
				$out .= '<input type="hidden" id="'.$htmlname.'_section_id"  name="section_id" value="'.$sectionid.'">'."\n";
				$out .= '<input type="hidden" name="sortfield" value="'.GETPOST('sortfield', 'aZ09comma').'">'."\n";
				$out .= '<input type="hidden" name="sortorder" value="'.GETPOST('sortorder', 'aZ09comma').'">'."\n";
				$out .= '<input type="hidden" name="page_y" value="">'."\n";
			}

			$out .= '<table class="nobordernopadding centpercent">';
			$out .= '<tr>';

			if (!empty($options)) {
				$out .= '<td>'.$options.'</td>';
			}

			$out .= '<td class="valignmiddle nowrap">';

			$maxfilesizearray = getMaxFileSizeArray();
			$max = $maxfilesizearray['max'];
			$maxmin = $maxfilesizearray['maxmin'];
			$maxphptoshow = $maxfilesizearray['maxphptoshow'];
			$maxphptoshowparam = $maxfilesizearray['maxphptoshowparam'];
			if ($maxmin > 0) {
				$out .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
			}
			$out .= '<input class="flat minwidth400 maxwidth200onsmartphone" type="file"';
			$out .= ((getDolGlobalString('MAIN_DISABLE_MULTIPLE_FILEUPLOAD') || $disablemulti) ? ' name="userfile"' : ' name="userfile[]" multiple');
			$out .= (!getDolGlobalString('MAIN_UPLOAD_DOC') || empty($perm) ? ' disabled' : '');
			$out .= (!empty($accept) ? ' accept="'.$accept.'"' : ' accept=""');
			$out .= (!empty($capture) ? ' capture="capture"' : '');
			$out .= '>';
			$out .= ' ';
			if ($sectionid) {	// Show overwrite if exists for ECM module only
				$langs->load('link');
				$out .= '<span class="nowraponsmartphone"><input style="margin-right: 2px;" type="checkbox" id="overwritefile" name="overwritefile" value="1"><label for="overwritefile">'.$langs->trans("OverwriteIfExists").'</label></span>';
			}
			$out .= '<input type="submit" class="button small reposition" name="sendit" value="'.$langs->trans("Upload").'"';
			$out .= (!getDolGlobalString('MAIN_UPLOAD_DOC') || empty($perm) ? ' disabled' : '');
			$out .= '>';

			if ($addcancel) {
				$out .= ' &nbsp; ';
				$out .= '<input type="submit" class="button small button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			}

			if (getDolGlobalString('MAIN_UPLOAD_DOC')) {
				if ($perm) {
					$menudolibarrsetupmax = $langs->transnoentitiesnoconv("Home").' - '.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("Security");
					$langs->load('other');
					$out .= ' ';
					$out .= info_admin($langs->trans("ThisLimitIsDefinedInSetupAt", $menudolibarrsetupmax, $max, $maxphptoshowparam, $maxphptoshow), 1);
				}
			} else {
				$out .= ' ('.$langs->trans("UploadDisabled").')';
			}
			$out .= "</td></tr>";

			if ($savingdocmask) {
				//add a global variable for disable the auto renaming on upload
				$rename = (!getDolGlobalString('MAIN_DOC_UPLOAD_NOT_RENAME_BY_DEFAULT') ? 'checked' : '');

				$out .= '<tr>';
				if (!empty($options)) {
					$out .= '<td>'.$options.'</td>';
				}
				$out .= '<td valign="middle" class="nowrap">';
				$out .= '<input type="checkbox" '.$rename.' class="savingdocmask" name="savingdocmask" id="savingdocmask" value="'.dol_escape_js($savingdocmask).'"> ';
				$out .= '<label class="opacitymedium small" for="savingdocmask">';
				$out .= $langs->trans("SaveUploadedFileWithMask", preg_replace('/__file__/', $langs->transnoentitiesnoconv("OriginFileName"), $savingdocmask), $langs->transnoentitiesnoconv("OriginFileName"));
				$out .= '</label>';
				$out .= '</td>';
				$out .= '</tr>';
			}

			$out .= "</table>";

			if (empty($usewithoutform)) {
				$out .= '</form>';
				if (empty($sectionid)) {
					$out .= '<br>';
				}
			}

			$out .= "\n</div><!-- End form attach new file -->\n";

			if ($linkfiles) {
				$out .= "\n".'<!-- Start form link new url --><div class="formlinknewurl">'."\n";
				$langs->load('link');
				$title = $langs->trans("LinkANewFile");
				$out .= load_fiche_titre($title, null, null);

				if (empty($usewithoutform)) {
					$out .= '<form name="'.$htmlname.'_link" id="'.$htmlname.'_link" action="'.$url.'" method="POST">'."\n";
					$out .= '<input type="hidden" name="token" value="'.newToken().'">'."\n";
					$out .= '<input type="hidden" id="'.$htmlname.'_link_section_dir" name="link_section_dir" value="">'."\n";
					$out .= '<input type="hidden" id="'.$htmlname.'_link_section_id"  name="link_section_id" value="'.$sectionid.'">'."\n";
					$out .= '<input type="hidden" name="page_y" value="">'."\n";
				}

				$out .= '<div class="valignmiddle">';
				$out .= '<div class="inline-block" style="padding-right: 10px;">';
				if (getDolGlobalString('OPTIMIZEFORTEXTBROWSER')) {
					$out .= '<label for="link">'.$langs->trans("URLToLink").':</label> ';
				}
				$out .= '<input type="text" name="link" class="flat minwidth400imp" id="link" placeholder="'.dol_escape_htmltag($langs->trans("URLToLink")).'">';
				$out .= '</div>';
				$out .= '<div class="inline-block" style="padding-right: 10px;">';
				if (getDolGlobalString('OPTIMIZEFORTEXTBROWSER')) {
					$out .= '<label for="label">'.$langs->trans("Label").':</label> ';
				}
				$out .= '<input type="text" class="flat" name="label" id="label" placeholder="'.dol_escape_htmltag($langs->trans("Label")).'">';
				$out .= '<input type="hidden" name="objecttype" value="'.$object->element.'">';
				$out .= '<input type="hidden" name="objectid" value="'.$object->id.'">';
				$out .= '</div>';
				$out .= '<div class="inline-block" style="padding-right: 10px;">';
				$out .= '<input type="submit" class="button small reposition" name="linkit" value="'.$langs->trans("ToLink").'"';
				$out .= (!getDolGlobalString('MAIN_UPLOAD_DOC') || empty($perm) ? ' disabled' : '');
				$out .= '>';
				$out .= '</div>';
				$out .= '</div>';
				if (empty($usewithoutform)) {
					$out .= '<div class="clearboth"></div>';
					$out .= '</form><br>';
				}

				$out .= "\n</div><!-- End form link new url -->\n";
			}

			$parameters = array('socid' => (isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id' => (isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'url' => $url, 'perm' => $perm, 'options' => $options);
			$res = $hookmanager->executeHooks('formattachOptions', $parameters, $object);
			if (empty($res)) {
				$out = '<div class="'.($usewithoutform ? 'inline-block valignmiddle' : 'attacharea attacharea'.$htmlname).'">'.$out.'</div>';
			}
			$out .= $hookmanager->resPrint;

			if ($nooutput) {
				return $out;
			} else {
				print $out;
				return 1;
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
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
	 * 		@return		int										Return integer <0 if KO, number of shown files if OK
	 *      @deprecated                                         Use print xxx->showdocuments() instead.
	 */
	public function show_documents($modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed = 0, $modelselected = '', $allowgenifempty = 1, $forcenomultilang = 0, $iconPDF = 0, $notused = 0, $noform = 0, $param = '', $title = '', $buttonlabel = '', $codelang = '')
	{
		// phpcs:enable
		$this->numoffiles = 0;
		print $this->showdocuments($modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed, $modelselected, $allowgenifempty, $forcenomultilang, $iconPDF, $notused, $noform, $param, $title, $buttonlabel, $codelang);
		return $this->numoffiles;
	}

	/**
	 *      Return a string to show the box with list of available documents for object.
	 *      This also set the property $this->numoffiles
	 *
	 *      @param      string				$modulepart         Module the files are related to ('propal', 'facture', 'facture_fourn', 'mymodule', 'mymodule:MyObject', 'mymodule_temp', ...)
	 *      @param      string				$modulesubdir       Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into a subdir of module.
	 *      @param      string				$filedir            Directory to scan (must not end with a /). Example: '/mydolibarrdocuments/facture/FAYYMM-1234'
	 *      @param      string				$urlsource          Url of origin page (for return)
	 *      @param      int|string[]        $genallowed         Generation is allowed (1/0 or array list of templates)
	 *      @param      int					$delallowed         Remove is allowed (1/0)
	 *      @param      string				$modelselected      Model to preselect by default
	 *      @param      integer				$allowgenifempty	Allow generation even if list of template ($genallowed) is empty (show however a warning)
	 *      @param      integer				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
	 *      @param      int					$iconPDF            Deprecated, see getDocumentsLink
	 * 		@param		int					$notused	        Not used
	 * 		@param		integer				$noform				Do not output html form tags
	 * 		@param		string				$param				More param on http links
	 * 		@param		string				$title				Title to show on top of form. Example: '' (Default to "Documents") or 'none'
	 * 		@param		string				$buttonlabel		Label on submit button
	 * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
	 * 		@param		string				$morepicto			Add more HTML content into cell with picto
	 *      @param      Object|null         $object             Object when method is called from an object card.
	 *      @param		int					$hideifempty		Hide section of generated files if there is no file
	 *      @param      string              $removeaction       (optional) The action to remove a file
	 *      @param		string				$tooltipontemplatecombo		Text to show on a tooltip after the combo list of templates
	 * 		@return		string|int             					Output string with HTML array of documents (might be empty string)
	 */
	public function showdocuments($modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed = 0, $modelselected = '', $allowgenifempty = 1, $forcenomultilang = 0, $iconPDF = 0, $notused = 0, $noform = 0, $param = '', $title = '', $buttonlabel = '', $codelang = '', $morepicto = '', $object = null, $hideifempty = 0, $removeaction = 'remove_file', $tooltipontemplatecombo = '')
	{
		global $dolibarr_main_url_root;

		// Deprecation warning
		if (!empty($iconPDF)) {
			dol_syslog(__METHOD__.": passing iconPDF parameter is deprecated", LOG_WARNING);
		}

		global $langs, $conf, $user, $hookmanager;
		global $form;

		$reshook = 0;
		if (is_object($hookmanager)) {
			$parameters = array(
				'modulepart' => &$modulepart,
				'modulesubdir' => &$modulesubdir,
				'filedir' => &$filedir,
				'urlsource' => &$urlsource,
				'genallowed' => &$genallowed,
				'delallowed' => &$delallowed,
				'modelselected' => &$modelselected,
				'allowgenifempty' => &$allowgenifempty,
				'forcenomultilang' => &$forcenomultilang,
				'noform' => &$noform,
				'param' => &$param,
				'title' => &$title,
				'buttonlabel' => &$buttonlabel,
				'codelang' => &$codelang,
				'morepicto' => &$morepicto,
				'hideifempty' => &$hideifempty,
				'removeaction' => &$removeaction
			);
			$reshook = $hookmanager->executeHooks('showDocuments', $parameters, $object); // Note that parameters may have been updated by hook
			// May report error
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}
		}
		// Remode default action if $reskook > 0
		if ($reshook > 0) {
			return $hookmanager->resPrint;
		}

		if (!is_object($form)) {
			$form = new Form($this->db);
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// For backward compatibility
		if (!empty($iconPDF)) {
			return $this->getDocumentsLink($modulepart, $modulesubdir, $filedir);
		}

		// Add entity in $param if not already exists
		if (!preg_match('/entity\=[0-9]+/', $param)) {
			$param .= ($param ? '&' : '').'entity='.(empty($object->entity) ? $conf->entity : $object->entity);
		}

		$printer = 0;
		// The direct print feature is implemented only for such elements
		if (in_array($modulepart, array('contract', 'facture', 'supplier_proposal', 'propal', 'proposal', 'order', 'commande', 'expedition', 'commande_fournisseur', 'expensereport', 'delivery', 'ticket'))) {
			$printer = ($user->hasRight('printing', 'read') && !empty($conf->printing->enabled)) ? true : false;
		}

		$hookmanager->initHooks(array('formfile'));

		// Get list of files
		$file_list = null;
		if (!empty($filedir)) {
			$file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
		}
		if ($hideifempty && empty($file_list)) {
			return '';
		}

		$out = '';
		$forname = 'builddoc';
		$headershown = 0;
		$showempty = 0;
		$i = 0;

		$out .= "\n".'<!-- Start show_document -->'."\n";
		//print 'filedir='.$filedir;

		if (preg_match('/massfilesarea_/', $modulepart)) {
			$out .= '<div id="show_files"><br></div>'."\n";
			$title = $langs->trans("MassFilesArea").' <a href="" id="togglemassfilesarea" ref="shown">('.$langs->trans("Hide").')</a>';
			$title .= '<script nonce="'.getNonce().'">
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

		$titletoshow = $langs->trans("Documents");
		if (!empty($title)) {
			$titletoshow = ($title == 'none' ? '' : $title);
		}

		$submodulepart = $modulepart;

		// modulepart = 'nameofmodule' or 'nameofmodule:NameOfObject'
		$tmp = explode(':', $modulepart);
		if (!empty($tmp[1])) {
			$modulepart = $tmp[0];
			$submodulepart = $tmp[1];
		}

		$addcolumforpicto = ($delallowed || $printer || $morepicto);
		$colspan = (4 + ($addcolumforpicto ? 1 : 0));
		$colspanmore = 0;

		// Show table
		if ($genallowed) {
			$modellist = array();

			if ($modulepart == 'company') {
				$showempty = 1; // can have no template active
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';
					$modellist = ModeleThirdPartyDoc::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'propal') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
					$modellist = ModelePDFPropales::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'supplier_proposal') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_proposal/modules_supplier_proposal.php';
					$modellist = ModelePDFSupplierProposal::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'commande') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
					$modellist = ModelePDFCommandes::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'expedition') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
					$modellist = ModelePdfExpedition::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'reception') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/reception/modules_reception.php';
					$modellist = ModelePdfReception::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'delivery') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/delivery/modules_delivery.php';
					$modellist = ModelePDFDeliveryOrder::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'ficheinter') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
					$modellist = ModelePDFFicheinter::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'facture') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
					$modellist = ModelePDFFactures::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'contract') {
				$showempty = 1; // can have no template active
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
					$modellist = ModelePDFContract::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'project') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
					$modellist = ModelePDFProjects::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'project_task') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';
					$modellist = ModelePDFTask::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'product') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
					$modellist = ModelePDFProduct::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'product_batch') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/product_batch/modules_product_batch.class.php';
					$modellist = ModelePDFProductBatch::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'stock') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_stock.php';
					$modellist = ModelePDFStock::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'hrm') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/hrm/modules_evaluation.php';
					$modellist = ModelePDFEvaluation::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'movement') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_movement.php';
					$modellist = ModelePDFMovement::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'export') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
					//$modellist = ModeleExports::liste_modeles($this->db);		// liste_modeles() does not exists. We are using listOfAvailableExportFormat() method instead that return a different array format.
					$modellist = array();
				}
			} elseif ($modulepart == 'commande_fournisseur' || $modulepart == 'supplier_order') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
					$modellist = ModelePDFSuppliersOrders::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'facture_fournisseur' || $modulepart == 'supplier_invoice') {
				$showempty = 1; // can have no template active
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
					$modellist = ModelePDFSuppliersInvoices::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'supplier_payment') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_payment/modules_supplier_payment.php';
					$modellist = ModelePDFSuppliersPayments::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'remisecheque') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/modules_chequereceipts.php';
					$modellist = ModeleChequeReceipts::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'donation') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
					$modellist = ModeleDon::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'member') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/member/modules_cards.php';
					$modellist = ModelePDFCards::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'agenda' || $modulepart == 'actions') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/action/modules_action.php';
					$modellist = ModeleAction::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'expensereport') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
					$modellist = ModeleExpenseReport::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'unpaid') {
				$modellist = '';
			} elseif ($modulepart == 'user') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/user/modules_user.class.php';
					$modellist = ModelePDFUser::liste_modeles($this->db);
				}
			} elseif ($modulepart == 'usergroup') {
				if (is_array($genallowed)) {
					$modellist = $genallowed;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/core/modules/usergroup/modules_usergroup.class.php';
					$modellist = ModelePDFUserGroup::liste_modeles($this->db);
				}
			} else {
				// For normalized standard modules
				$file = dol_buildpath('/core/modules/'.$modulepart.'/modules_'.strtolower($submodulepart).'.php', 0);
				if (file_exists($file)) {
					$res = include_once $file;
				} else {
					// For normalized external modules.
					$file = dol_buildpath('/'.$modulepart.'/core/modules/'.$modulepart.'/modules_'.strtolower($submodulepart).'.php', 0);
					$res = include_once $file;
				}

				$class = 'ModelePDF'.ucfirst($submodulepart);

				if (class_exists($class)) {
					$modellist = call_user_func($class.'::liste_modeles', $this->db);
				} else {
					dol_print_error($this->db, "Bad value for modulepart '".$modulepart."' in showdocuments (class ".$class." for Doc generation not found)");
					return -1;
				}
			}

			// Set headershown to avoid to have table opened a second time later
			$headershown = 1;

			if (empty($buttonlabel)) {
				$buttonlabel = $langs->trans('Generate');
			}

			if ($conf->browser->layout == 'phone') {
				$urlsource .= '#'.$forname.'_form'; // So we switch to form after a generation
			}
			if (empty($noform)) {
				$out .= '<form action="'.$urlsource.'" id="'.$forname.'_form" method="post">';
			}
			$out .= '<input type="hidden" name="action" value="builddoc">';
			$out .= '<input type="hidden" name="page_y" value="">';
			$out .= '<input type="hidden" name="token" value="'.newToken().'">';

			$out .= load_fiche_titre($titletoshow, '', '');
			$out .= '<div class="div-table-responsive-no-min">';
			$out .= '<table class="liste formdoc noborder centpercent">';

			$out .= '<tr class="liste_titre">';
			$addcolumforpicto = ($delallowed || $printer || $morepicto);
			$colspan = (4 + ($addcolumforpicto ? 1 : 0));
			$colspanmore = 0;

			$out .= '<th colspan="'.$colspan.'" class="formdoc liste_titre maxwidthonsmartphone center">';

			// Model
			if (!empty($modellist)) {
				asort($modellist);
				$out .= '<span class="hideonsmartphone">'.$langs->trans('Model').' </span>';
				if (is_array($modellist) && count($modellist) == 1) {    // If there is only one element
					$arraykeys = array_keys($modellist);
					$modelselected = $arraykeys[0];
				}
				$morecss = 'minwidth75 maxwidth200';
				if ($conf->browser->layout == 'phone') {
					$morecss = 'maxwidth100';
				}
				$out .= $form->selectarray('model', $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss, 1, '', 0, 0);
				// script for select the separator
				/* TODO This must appear on export feature only
				$out .= '<label class="forhide" for="delimiter">Delimiter:</label>';
				$out .= '<input type="radio" class="testinput forhide" name="delimiter" value="," id="comma" checked><label class="forhide" for="comma">,</label>';
				$out .= '<input type="radio" class="testinput forhide" name="delimiter" value=";" id="semicolon"><label class="forhide" for="semicolon">;</label>';

				$out .= '<script>
							jQuery(document).ready(function() {
								$(".selectformat").on("change", function() {
									var separator;
									var selected = $(this).val();
									if (selected == "excel2007" || selected == "tsv") {
										$("input.testinput").prop("disabled", true);
										$(".forhide").hide();
									} else {
										$("input.testinput").prop("disabled", false);
										$(".forhide").show();
									}

									if ($("#semicolon").is(":checked")) {
										separator = ";";
									} else {
										separator = ",";
									}
								});
								if ("' . $conf->global->EXPORT_CSV_SEPARATOR_TO_USE . '" == ";") {
									$("#semicolon").prop("checked", true);
								} else {
									$("#comma").prop("checked", true);
								}
							});
						</script>';
				*/
				if ($conf->use_javascript_ajax) {
					$out .= ajax_combobox('model');
				}
				$out .= $form->textwithpicto('', $tooltipontemplatecombo, 1, 'help', 'marginrightonly', 0, 3, '', 0);
			} else {
				$out .= '<div class="float">'.$langs->trans("Files").'</div>';
			}

			// Language code (if multilang)
			if (($allowgenifempty || (is_array($modellist) && count($modellist) > 0)) && getDolGlobalInt('MAIN_MULTILANGS') && !$forcenomultilang && (!empty($modellist) || $showempty)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
				$formadmin = new FormAdmin($this->db);
				$defaultlang = ($codelang && $codelang != 'auto') ? $codelang : $langs->getDefaultLang();
				$morecss = 'maxwidth150';
				if ($conf->browser->layout == 'phone') {
					$morecss = 'maxwidth100';
				}
				$out .= $formadmin->select_language($defaultlang, 'lang_id', 0, null, 0, 0, 0, $morecss);
			} else {
				$out .= '&nbsp;';
			}

			// Button
			$genbutton = '<input class="button buttongen reposition nomargintop nomarginbottom" id="'.$forname.'_generatebutton" name="'.$forname.'_generatebutton"';
			$genbutton .= ' type="submit" value="'.$buttonlabel.'"';
			if (!$allowgenifempty && !is_array($modellist) && empty($modellist)) {
				$genbutton .= ' disabled';
			}
			$genbutton .= '>';
			if ($allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') {
				$langs->load("errors");
				$genbutton .= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
			}
			if (!$allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') {
				$genbutton = '';
			}
			if (empty($modellist) && !$showempty && $modulepart != 'unpaid') {
				$genbutton = '';
			}
			$out .= $genbutton;
			$out .= '</th>';

			if (!empty($hookmanager->hooks['formfile'])) {
				foreach ($hookmanager->hooks['formfile'] as $module) {
					if (method_exists($module, 'formBuilddocLineOptions')) {
						$colspanmore++;
						$out .= '<th></th>';
					}
				}
			}
			$out .= '</tr>';

			// Execute hooks
			$parameters = array('colspan' => ($colspan + $colspanmore), 'socid' => (isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id' => (isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart' => $modulepart);
			if (is_object($hookmanager)) {
				$reshook = $hookmanager->executeHooks('formBuilddocOptions', $parameters, $GLOBALS['object']);
				$out .= $hookmanager->resPrint;
			}
		}

		// Get list of files
		if (!empty($filedir)) {
			$link_list = array();
			if (is_object($object)) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
				$link = new Link($this->db);
				$sortfield = $sortorder = null;
				$res = $link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
			}

			$out .= '<!-- html.formfile::showdocuments -->'."\n";

			// Show title of array if not already shown
			if ((!empty($file_list) || !empty($link_list) || preg_match('/^massfilesarea/', $modulepart))
				&& !$headershown) {
				$headershown = 1;
				$out .= '<div class="titre">'.$titletoshow.'</div>'."\n";
				$out .= '<div class="div-table-responsive-no-min">';
				$out .= '<table class="noborder centpercent" id="'.$modulepart.'_table">'."\n";
			}

			// Loop on each file found
			if (is_array($file_list)) {
				// Defined relative dir to DOL_DATA_ROOT
				$relativedir = '';
				if ($filedir) {
					$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filedir);
					$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
				}

				// Get list of files stored into database for same relative directory
				if ($relativedir) {
					completeFileArrayWithDatabaseInfo($file_list, $relativedir);

					//var_dump($sortfield.' - '.$sortorder);
					if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
						$file_list = dol_sort_array($file_list, $sortfield, $sortorder);
					}
				}

				foreach ($file_list as $file) {
					// Define relative path for download link (depends on module)
					$relativepath = $file["name"]; // Cas general
					if ($modulesubdir) {
						$relativepath = $modulesubdir."/".$file["name"]; // Cas propal, facture...
					}
					if ($modulepart == 'export') {
						$relativepath = $file["name"]; // Other case
					}

					$out .= '<tr class="oddeven">';

					$documenturl = DOL_URL_ROOT.'/document.php';
					if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) {
						$documenturl = getDolGlobalString('DOL_URL_ROOT_DOCUMENT_PHP'); // To use another wrapper
					}

					// Show file name with link to download
					$imgpreview = $this->showPreview($file, $modulepart, $relativepath, 0, $param);

					$out .= '<td class="minwidth200 tdoverflowmax300">';
					if ($imgpreview) {
						$out .= '<span class="spanoverflow widthcentpercentminusx valignmiddle">';
					} else {
						$out .= '<span class="spanoverflow">';
					}
					$out .= '<a class="documentdownload paddingright" ';
					if (getDolGlobalInt('MAIN_DISABLE_FORCE_SAVEAS') == 2) {
						$out .= 'target="_blank" ';
					}
					$out .= 'href="'.$documenturl.'?modulepart='.$modulepart.'&file='.urlencode($relativepath).($param ? '&'.$param : '').'"';

					$mime = dol_mimetype($relativepath, '', 0);
					if (preg_match('/text/', $mime)) {
						$out .= ' target="_blank" rel="noopener noreferrer"';
					}
					$out .= ' title="'.dol_escape_htmltag($file["name"]).'"';
					$out .= '>';
					$out .= img_mime($file["name"], $langs->trans("File").': '.$file["name"]);
					$out .= dol_trunc($file["name"], 150);
					$out .= '</a>';
					$out .= '</span>'."\n";
					$out .= $imgpreview;
					$out .= '</td>';

					// Show file size
					$size = (!empty($file['size']) ? $file['size'] : dol_filesize($filedir."/".$file["name"]));
					$out .= '<td class="nowraponall right">'.dol_print_size($size, 1, 1).'</td>';

					// Show file date
					$date = (!empty($file['date']) ? $file['date'] : dol_filemtime($filedir."/".$file["name"]));
					$out .= '<td class="nowrap right">'.dol_print_date($date, 'dayhour', 'tzuser').'</td>';

					// Show share link
					$out .= '<td class="nowraponall">';
					if (!empty($file['share'])) {
						// Define $urlwithroot
						$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
						$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
						//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

						//print '<span class="opacitymedium">'.$langs->trans("Hash").' : '.$file['share'].'</span>';
						$forcedownload = 0;
						$paramlink = '';
						if (!empty($file['share'])) {
							$paramlink .= ($paramlink ? '&' : '').'hashp='.$file['share']; // Hash for public share
						}
						if ($forcedownload) {
							$paramlink .= ($paramlink ? '&' : '').'attachment=1';
						}

						$fulllink = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');

						$out .= '<a href="'.$fulllink.'" target="_blank" rel="noopener">'.img_picto($langs->trans("FileSharedViaALink"), 'globe').'</a> ';
						$out .= '<input type="text" class="quatrevingtpercentminusx width75 nopadding small" id="downloadlink'.$file['rowid'].'" name="downloadexternallink" title="'.dol_escape_htmltag($langs->trans("FileSharedViaALink")).'" value="'.dol_escape_htmltag($fulllink).'">';
						$out .= ajax_autoselect('downloadlink'.$file['rowid']);
					} else {
						//print '<span class="opacitymedium">'.$langs->trans("FileNotShared").'</span>';
					}
					$out .= '</td>';

					// Show picto delete, print...
					if ($delallowed || $printer || $morepicto) {
						$out .= '<td class="right nowraponall">';
						if ($delallowed) {
							$tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
							$out .= '<a class="reposition" href="'.$tmpurlsource.((strpos($tmpurlsource, '?') === false) ? '?' : '&').'action='.urlencode($removeaction).'&token='.newToken().'&file='.urlencode($relativepath);
							$out .= ($param ? '&'.$param : '');
							//$out.= '&modulepart='.$modulepart; // TODO obsolete ?
							//$out.= '&urlsource='.urlencode($urlsource); // TODO obsolete ?
							$out .= '">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						}
						if ($printer) {
							$out .= '<a class="marginleftonly reposition" href="'.$urlsource.(strpos($urlsource, '?') ? '&' : '?').'action=print_file&token='.newToken().'&printer='.urlencode($modulepart).'&file='.urlencode($relativepath);
							$out .= ($param ? '&'.$param : '');
							$out .= '">'.img_picto($langs->trans("PrintFile", $relativepath), 'printer.png').'</a>';
						}
						if ($morepicto) {
							$morepicto = preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
							$out .= $morepicto;
						}
						$out .= '</td>';
					}

					if (is_object($hookmanager)) {
						$addcolumforpicto = ($delallowed || $printer || $morepicto);
						$colspan = (4 + ($addcolumforpicto ? 1 : 0));
						$colspanmore = 0;
						$parameters = array('colspan' => ($colspan + $colspanmore), 'socid' => (isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id' => (isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart' => $modulepart, 'relativepath' => $relativepath);
						$res = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
						if (empty($res)) {
							$out .= $hookmanager->resPrint; // Complete line
							$out .= '</tr>';
						} else {
							$out = $hookmanager->resPrint; // Replace all $out
						}
					}
				}

				$this->numoffiles++;
			}
			// Loop on each link found
			if (is_array($link_list)) {
				$colspan = 2;

				foreach ($link_list as $file) {
					$out .= '<tr class="oddeven">';
					$out .= '<td colspan="'.$colspan.'" class="maxwidhtonsmartphone">';
					$out .= '<a data-ajax="false" href="'.$file->url.'" target="_blank" rel="noopener noreferrer">';
					$out .= $file->label;
					$out .= '</a>';
					$out .= '</td>';
					$out .= '<td class="right">';
					$out .= dol_print_date($file->datea, 'dayhour');
					$out .= '</td>';
					// for share link of files
					$out .= '<td></td>';
					if ($delallowed || $printer || $morepicto) {
						$out .= '<td></td>';
					}
					$out .= '</tr>'."\n";
				}
				$this->numoffiles++;
			}

			if (count($file_list) == 0 && count($link_list) == 0 && $headershown) {
				$out .= '<tr><td colspan="'.(3 + ($addcolumforpicto ? 1 : 0)).'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>'."\n";
			}
		}

		if ($headershown) {
			// Affiche pied du tableau
			$out .= "</table>\n";
			$out .= "</div>\n";
			if ($genallowed) {
				if (empty($noform)) {
					$out .= '</form>'."\n";
				}
			}
		}
		$out .= '<!-- End show_document -->'."\n";

		$out .= '<script>
		jQuery(document).ready(function() {
			var selectedValue = $(".selectformat").val();

			if (selectedValue === "excel2007" || selectedValue === "tsv") {
			  $(".forhide").prop("disabled", true).hide();
			} else {
			  $(".forhide").prop("disabled", false).show();
			}
		  });
			</script>';
		//return ($i?$i:$headershown);
		return $out;
	}

	/**
	 *	Show a Document icon with link(s)
	 *  You may want to call this into a div like this:
	 *  print '<div class="inline-block valignmiddle">'.$formfile->getDocumentsLink($element_doc, $filename, $filedir).'</div>';
	 *
	 *	@param	string	$modulepart		'propal', 'facture', 'facture_fourn', ...
	 *	@param	string	$modulesubdir	Sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
	 *	@param	string	$filedir		Full path to directory to scan
	 *  @param	string	$filter			Filter filenames on this regex string (Example: '\.pdf$')
	 *  @param	string	$morecss		Add more css to the download picto
	 *  @param	int 	$allfiles		0=Only generated docs, 1=All files
	 *	@return	string              	Output string with HTML link of documents (might be empty string). This also fill the array ->infofiles
	 */
	public function getDocumentsLink($modulepart, $modulesubdir, $filedir, $filter = '', $morecss = 'valignmiddle', $allfiles = 0)
	{
		global $conf, $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$out = '';
		$this->infofiles = array('nboffiles' => 0, 'extensions' => array(), 'files' => array());

		$entity = 1; // Without multicompany

		// Get object entity
		if (isModEnabled('multicompany')) {
			$regs = array();
			preg_match('/\/([0-9]+)\/[^\/]+\/'.preg_quote($modulesubdir, '/').'$/', $filedir, $regs);
			$entity = ((!empty($regs[1]) && $regs[1] > 1) ? $regs[1] : 1); // If entity id not found in $filedir this is entity 1 by default
		}

		// Get list of files starting with name of ref (Note: files with '^ref\.extension' are generated files, files with '^ref-...' are uploaded files)
		if ($allfiles || getDolGlobalString('MAIN_SHOW_ALL_FILES_ON_DOCUMENT_TOOLTIP')) {
			$filterforfilesearch = '^'.preg_quote(basename($modulesubdir), '/');
		} else {
			$filterforfilesearch = '^'.preg_quote(basename($modulesubdir), '/').'\.';
		}
		$file_list = dol_dir_list($filedir, 'files', 0, $filterforfilesearch, '\.meta$|\.png$'); // We also discard .meta and .png preview

		//var_dump($file_list);
		// For ajax treatment
		$out .= '<!-- html.formfile::getDocumentsLink -->'."\n";
		if (!empty($file_list)) {
			$out = '<dl class="dropdown inline-block">
				<dt><a data-ajax="false" href="#" onClick="return false;">'.img_picto('', 'listlight', '', 0, 0, 0, '', $morecss).'</a></dt>
				<dd><div class="multichoicedoc" style="position:absolute;left:100px;" ><ul class="ulselectedfields">';
			$tmpout = '';

			// Loop on each file found
			$found = 0;
			$i = 0;
			foreach ($file_list as $file) {
				$i++;
				if ($filter && !preg_match('/'.$filter.'/i', $file["name"])) {
					continue; // Discard this. It does not match provided filter.
				}

				$found++;
				// Define relative path for download link (depends on module)
				$relativepath = $file["name"]; // Cas general
				if ($modulesubdir) {
					$relativepath = $modulesubdir."/".$file["name"]; // Cas propal, facture...
				}
				// Autre cas
				if ($modulepart == 'donation') {
					$relativepath = get_exdir($modulesubdir, 2, 0, 0, null, 'donation').$file["name"];
				}
				if ($modulepart == 'export') {
					$relativepath = $file["name"];
				}

				$this->infofiles['nboffiles']++;
				$this->infofiles['files'][] = $file['fullname'];
				$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
				if (empty($this->infofiles[$ext])) {
					$this->infofiles['extensions'][$ext] = 1;
				} else {
					$this->infofiles['extensions'][$ext]++;
				}

				// Preview
				if (!empty($conf->use_javascript_ajax) && ($conf->browser->layout != 'phone')) {
					$tmparray = getAdvancedPreviewUrl($modulepart, $relativepath, 1, '&entity='.$entity);
					if ($tmparray && $tmparray['url']) {
						$tmpout .= '<li><a href="'.$tmparray['url'].'"'.($tmparray['css'] ? ' class="'.$tmparray['css'].'"' : '').($tmparray['mime'] ? ' mime="'.$tmparray['mime'].'"' : '').($tmparray['target'] ? ' target="'.$tmparray['target'].'"' : '').'>';
						//$tmpout.= img_picto('','detail');
						$tmpout .= '<i class="fa fa-search-plus paddingright" style="color: gray"></i>';
						$tmpout .= $langs->trans("Preview").' '.$ext.'</a></li>';
					}
				}

				// Download
				$tmpout .= '<li class="nowrap"><a class="pictopreview nowrap" ';
				if (getDolGlobalInt('MAIN_DISABLE_FORCE_SAVEAS') == 2) {
						$tmpout .= 'target="_blank" ';
				}
				$tmpout .= 'href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&amp;entity='.$entity.'&amp;file='.urlencode($relativepath).'"';
				$mime = dol_mimetype($relativepath, '', 0);
				if (preg_match('/text/', $mime)) {
					$tmpout .= ' target="_blank" rel="noopener noreferrer"';
				}
				$tmpout .= '>';
				$tmpout .= img_mime($relativepath, $file["name"]);
				$tmpout .= $langs->trans("Download").' '.$ext;
				$tmpout .= '</a></li>'."\n";
			}
			$out .= $tmpout;
			$out .= '</ul></div></dd>
				</dl>';

			if (!$found) {
				$out = '';
			}
		} else {
			// TODO Add link to regenerate doc ?
			//$out.= '<div id="gen_pdf_'.$modulesubdir.'" class="linkobject hideobject">'.img_picto('', 'refresh').'</div>'."\n";
		}

		return $out;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show list of documents in $filearray (may be they are all in same directory but may not)
	 *  This also sync database if $upload_dir is defined.
	 *
	 *  @param	 array			$filearray          Array of files loaded by dol_dir_list('files') function before calling this.
	 * 	@param	 Object|null	$object				Object on which document is linked to.
	 * 	@param	 string			$modulepart			Value for modulepart used by download or viewimage wrapper.
	 * 	@param	 string			$param				Parameters on sort links (param must start with &, example &aaa=bbb&ccc=ddd)
	 * 	@param	 int			$forcedownload		Force to open dialog box "Save As" when clicking on file.
	 * 	@param	 string			$relativepath		Relative path of docs (autodefined if not provided), relative to module dir, not to MAIN_DATA_ROOT.
	 * 	@param	 int			$permonobject		Permission on object (so permission to delete or crop document)
	 * 	@param	 int			$useinecm			Change output to add more information:
	 * 												0, 4, 5, 6: Add a preview column. Show also a rename button. Show also a crop button for some values of $modulepart (must be supported into hard coded list in this function + photos_resize.php + restrictedArea + checkUserAccessToObject)
	 * 												1: Add link to edit ECM entry
	 * 												2: Add rename and crop link
	 *                                  		    5: Add link to edit ECM entry and add a preview column
	 * 	@param	 string			$textifempty		Text to show if filearray is empty ('NoFileFound' if not defined)
	 *  @param   int			$maxlength          Maximum length of file name shown.
	 *  @param	 string			$title				Title before list. Use 'none' to disable title.
	 *  @param	 string 		$url				Full url to use for click links ('' = autodetect)
	 *  @param	 int			$showrelpart		0=Show only filename (default), 1=Show first level 1 dir
	 *  @param   int    		$permtoeditline     Permission to edit document line (You must provide a value, -1 is deprecated and must not be used any more)
	 *  @param   string 		$upload_dir         Full path directory so we can know dir relative to MAIN_DATA_ROOT. Fill this to complete file data with database indexes.
	 *  @param   string 		$sortfield          Sort field ('name', 'size', 'position', ...)
	 *  @param   string 		$sortorder          Sort order ('ASC' or 'DESC')
	 *  @param   int    		$disablemove        1=Disable move button, 0=Position move is possible.
	 *  @param	 int			$addfilterfields	Add the line with filters
	 *  @param	 int			$disablecrop		Disable crop feature on images (-1 = auto, prefer to set it explicitly to 0 or 1)
	 *  @param	 string			$moreattrondiv		More attributes on the div for responsive. Example 'style="height:280px; overflow: auto;"'
	 * 	@return	 int								Return integer <0 if KO, nb of files shown if OK
	 *  @see list_of_autoecmfiles()
	 */
	public function list_of_documents($filearray, $object, $modulepart, $param = '', $forcedownload = 0, $relativepath = '', $permonobject = 1, $useinecm = 0, $textifempty = '', $maxlength = 0, $title = '', $url = '', $showrelpart = 0, $permtoeditline = -1, $upload_dir = '', $sortfield = '', $sortorder = 'ASC', $disablemove = 1, $addfilterfields = 0, $disablecrop = -1, $moreattrondiv = '')
	{
		// phpcs:enable
		global $user, $conf, $langs, $hookmanager, $form;
		global $sortfield, $sortorder, $maxheightmini;
		global $dolibarr_main_url_root;

		if ($disablecrop == -1) {
			$disablecrop = 1;
			// Values here must be supported by the photos_resize.php page.
			if (in_array($modulepart, array('bank', 'bom', 'expensereport', 'facture', 'facture_fournisseur', 'holiday', 'medias', 'member', 'mrp', 'project', 'product', 'produit', 'propal', 'service', 'societe', 'tax', 'tax-vat', 'ticket', 'user'))) {
				$disablecrop = 0;
			}
		}

		// Define relative path used to store the file
		if (empty($relativepath)) {
			$relativepath = (!empty($object->ref) ? dol_sanitizeFileName($object->ref) : '').'/';
			if (!empty($object->element) && $object->element == 'invoice_supplier') {
				$relativepath = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$relativepath; // TODO Call using a defined value for $relativepath
			}
			if (!empty($object->element) && $object->element == 'project_task') {
				$relativepath = 'Call_not_supported_._Call_function_using_a_defined_relative_path_.';
			}
		}
		// For backward compatibility, we detect file stored into an old path
		if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO') && isset($filearray[0]) && $filearray[0]['level1name'] == 'photos') {
			$relativepath = preg_replace('/^.*\/produit\//', '', $filearray[0]['path']).'/';
		}

		// Defined relative dir to DOL_DATA_ROOT
		$relativedir = '';
		if ($upload_dir) {
			$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dir);
			$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
		}
		// For example here $upload_dir = '/pathtodocuments/commande/SO2001-123/'
		// For example here $upload_dir = '/pathtodocuments/tax/vat/1'
		// For example here $upload_dir = '/home/ldestailleur/git/dolibarr_dev/documents/fournisseur/facture/6/1/SI2210-0013' and relativedir='fournisseur/facture/6/1/SI2210-0013'

		$hookmanager->initHooks(array('formfile'));
		$parameters = array(
				'filearray' => $filearray,
				'modulepart' => $modulepart,
				'param' => $param,
				'forcedownload' => $forcedownload,
				'relativepath' => $relativepath, // relative filename to module dir
				'relativedir' => $relativedir, // relative dirname to DOL_DATA_ROOT
				'permtodelete' => $permonobject,
				'useinecm' => $useinecm,
				'textifempty' => $textifempty,
				'maxlength' => $maxlength,
				'title' => $title,
				'url' => $url
		);
		$reshook = $hookmanager->executeHooks('showFilesList', $parameters, $object);

		if (!empty($reshook)) { // null or '' for bypass
			return $reshook;
		} else {
			if (!is_object($form)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php'; // The component may be included into ajax page that does not include the Form class
				$form = new Form($this->db);
			}

			if (!preg_match('/&id=/', $param) && isset($object->id)) {
				$param .= '&id='.$object->id;
			}
			$relativepathwihtoutslashend = preg_replace('/\/$/', '', $relativepath);
			if ($relativepathwihtoutslashend) {
				$param .= '&file='.urlencode($relativepathwihtoutslashend);
			}

			if ($permtoeditline < 0) {  // Old behaviour for backward compatibility. New feature should call method with value 0 or 1
				$permtoeditline = 0;
				if (in_array($modulepart, array('product', 'produit', 'service'))) {
					if ($user->hasRight('produit', 'creer') && $object->type == Product::TYPE_PRODUCT) {
						$permtoeditline = 1;
					}
					if ($user->hasRight('service', 'creer') && $object->type == Product::TYPE_SERVICE) {
						$permtoeditline = 1;
					}
				}
			}
			if (!getDolGlobalString('MAIN_UPLOAD_DOC')) {
				$permtoeditline = 0;
				$permonobject = 0;
			}

			// Show list of existing files
			if ((empty($useinecm) || $useinecm == 3 || $useinecm == 6) && $title != 'none') {
				print load_fiche_titre($title ? $title : $langs->trans("AttachedFiles"), '', 'file-upload', 0, '', 'table-list-of-attached-files');
			}
			if (empty($url)) {
				$url = $_SERVER["PHP_SELF"];
			}

			print '<!-- html.formfile::list_of_documents -->'."\n";
			if (GETPOST('action', 'aZ09') == 'editfile' && $permtoeditline) {
				print '<form action="'.$_SERVER["PHP_SELF"].'?'.$param.'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="renamefile">';
				print '<input type="hidden" name="id" value="'.(is_object($object) ? $object->id : '').'">';
				print '<input type="hidden" name="modulepart" value="'.$modulepart.'">';
			}

			print '<div class="div-table-responsive-no-min"'.($moreattrondiv ? ' '.$moreattrondiv : '').'>';
			print '<table id="tablelines" class="centpercent liste noborder nobottom">'."\n";

			if (!empty($addfilterfields)) {
				print '<tr class="liste_titre nodrag nodrop">';
				print '<td><input type="search_doc_ref" value="'.dol_escape_htmltag(GETPOST('search_doc_ref', 'alpha')).'"></td>';
				print '<td></td>';
				print '<td></td>';
				if (empty($useinecm) || $useinecm == 4 || $useinecm == 5 || $useinecm == 6) {
					print '<td></td>';
				}
				print '<td></td>';
				print '<td></td>';
				if (empty($disablemove) && count($filearray) > 1) {
					print '<td></td>';
				}
				print "</tr>\n";
			}

			// Get list of files stored into database for same relative directory
			if ($relativedir) {
				completeFileArrayWithDatabaseInfo($filearray, $relativedir);

				//var_dump($sortfield.' - '.$sortorder);
				if ($sortfield && $sortorder) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
					$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
				}
			}

			print '<tr class="liste_titre nodrag nodrop">';
			//print $url.' sortfield='.$sortfield.' sortorder='.$sortorder;
			print_liste_field_titre('Documents2', $url, "name", "", $param, '', $sortfield, $sortorder, 'left ');
			print_liste_field_titre('Size', $url, "size", "", $param, '', $sortfield, $sortorder, 'right ');
			print_liste_field_titre('Date', $url, "date", "", $param, '', $sortfield, $sortorder, 'center ');
			if (empty($useinecm) || $useinecm == 4 || $useinecm == 5 || $useinecm == 6) {
				print_liste_field_titre('', $url, "", "", $param, '', $sortfield, $sortorder, 'center '); // Preview
			}
			// Shared or not - Hash of file
			print_liste_field_titre('');
			// Action button
			print_liste_field_titre('');
			if (empty($disablemove) && count($filearray) > 1) {
				print_liste_field_titre('');
			}
			print "</tr>\n";

			$nboffiles = count($filearray);
			if ($nboffiles > 0) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
			}

			$i = 0;
			$nboflines = 0;
			$lastrowid = 0;
			foreach ($filearray as $key => $file) {      // filearray must be only files here
				if ($file['name'] != '.' && $file['name'] != '..' && !preg_match('/\.meta$/i', $file['name'])) {
					if (array_key_exists('rowid', $filearray[$key]) && $filearray[$key]['rowid'] > 0) {
						$lastrowid = $filearray[$key]['rowid'];
					}
					//var_dump($filearray[$key]);

					// Note: for supplier invoice, $modulepart may be already 'facture_fournisseur' and $relativepath may be already '6/1/SI2210-0013/'

					if (empty($relativepath) || empty($modulepart)) {
						$filepath = $file['level1name'].'/'.$file['name'];
					} else {
						$filepath = $relativepath.$file['name'];
					}
					if (empty($modulepart)) {
						$modulepart = basename(dirname($file['path']));
					}
					if (empty($relativepath)) {
						$relativepath = preg_replace('/\/(.+)/', '', $filepath) . '/';
					}

					$editline = 0;
					$nboflines++;
					print '<!-- Line list_of_documents '.$key.' relativepath = '.$relativepath.' -->'."\n";
					// Do we have entry into database ?

					print '<!-- In database: position='.(array_key_exists('position', $filearray[$key]) ? $filearray[$key]['position'] : 0).' -->'."\n";
					print '<tr class="oddeven" id="row-'.((array_key_exists('rowid', $filearray[$key]) && $filearray[$key]['rowid'] > 0) ? $filearray[$key]['rowid'] : 'AFTER'.$lastrowid.'POS'.($i + 1)).'">';


					// File name
					print '<td class="minwith200 tdoverflowmax500" title="'.dolPrintHTMLForAttribute($file['name']).'">';

					// Show file name with link to download
					//print "XX".$file['name'];	//$file['name'] must be utf8
					print '<a class="paddingright valignmiddle" ';
					if (getDolGlobalInt('MAIN_DISABLE_FORCE_SAVEAS') == 2) {
						print 'target="_blank" ';
					}
					print 'href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
					if ($forcedownload) {
						print '&attachment=1';
					}
					if (!empty($object->entity)) {
						print '&entity='.$object->entity;
					}
					print '&file='.urlencode($filepath);
					print '">';
					print img_mime($file['name'], $file['name'].' ('.dol_print_size($file['size'], 0, 0).')', 'inline-block valignmiddle paddingright');
					if ($showrelpart == 1) {
						print $relativepath;
					}
					//print dol_trunc($file['name'],$maxlength,'middle');

					//var_dump(dirname($filepath).' - '.dirname(GETPOST('urlfile', 'alpha')));

					if (GETPOST('action', 'aZ09') == 'editfile' && $file['name'] == basename(GETPOST('urlfile', 'alpha')) && dirname($filepath) == dirname(GETPOST('urlfile', 'alpha'))) {
						print '</a>';
						$section_dir = dirname(GETPOST('urlfile', 'alpha'));
						if (!preg_match('/\/$/', $section_dir)) {
							$section_dir .= '/';
						}
						print '<input type="hidden" name="section_dir" value="'.$section_dir.'">';
						print '<input type="hidden" name="renamefilefrom" value="'.dol_escape_htmltag($file['name']).'">';
						print '<input type="text" name="renamefileto" class="quatrevingtpercent" value="'.dol_escape_htmltag($file['name']).'">';
						$editline = 1;
					} else {
						$filenametoshow = preg_replace('/\.noexe$/', '', $file['name']);
						print dol_escape_htmltag(dol_trunc($filenametoshow, 200));
						print '</a>';
					}
					// Preview link
					if (!$editline) {
						print $this->showPreview($file, $modulepart, $filepath, 0, '&entity='.(empty($object->entity) ? $conf->entity : $object->entity));
					}

					print "</td>\n";

					// Size
					$sizetoshow = dol_print_size($file['size'], 1, 1);
					$sizetoshowbytes = dol_print_size($file['size'], 0, 1);
					print '<td class="right nowraponall">';
					if ($sizetoshow == $sizetoshowbytes) {
						print $sizetoshow;
					} else {
						print $form->textwithpicto($sizetoshow, $sizetoshowbytes, -1);
					}
					print '</td>';

					// Date
					print '<td class="center nowraponall">'.dol_print_date($file['date'], "dayhour", "tzuser").'</td>';

					// Preview
					if (empty($useinecm) || $useinecm == 4 || $useinecm == 5 || $useinecm == 6) {
						$fileinfo = pathinfo($file['name']);
						print '<td class="center">';
						if (image_format_supported($file['name']) >= 0) {
							if ($useinecm == 5 || $useinecm == 6) {
								$smallfile = getImageFileNameForSize($file['name'], ''); // There is no thumb for ECM module and Media filemanager, so we use true image. TODO Change this for better performance.
							} else {
								$smallfile = getImageFileNameForSize($file['name'], '_small'); // For new thumbs using same ext (in lower case however) than original
							}
							if (!dol_is_file($file['path'].'/'.$smallfile)) {
								$smallfile = getImageFileNameForSize($file['name'], '_small', '.png'); // For backward compatibility of old thumbs that were created with filename in lower case and with .png extension
							}
							if (!dol_is_file($file['path'].'/'.$smallfile)) {
								$smallfile = getImageFileNameForSize($file['name'], ''); // This is in case no _small image exist
							}
							//print $file['path'].'/'.$smallfile.'<br>';


							$urlforhref = getAdvancedPreviewUrl($modulepart, $relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']), 1, '&entity='.(empty($object->entity) ? $conf->entity : $object->entity));
							if (empty($urlforhref)) {
								$urlforhref = DOL_URL_ROOT.'/viewimage.php?modulepart='.urlencode($modulepart).'&entity='.(empty($object->entity) ? $conf->entity : $object->entity).'&file='.urlencode($relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']));
								print '<a href="'.$urlforhref.'" class="aphoto" target="_blank" rel="noopener noreferrer">';
							} else {
								print '<a href="'.$urlforhref['url'].'" class="'.$urlforhref['css'].'" target="'.$urlforhref['target'].'" mime="'.$urlforhref['mime'].'">';
							}
							print '<img class="photo maxwidth200 shadow valignmiddle"';
							if ($useinecm == 4 || $useinecm == 5 || $useinecm == 6) {
								print ' height="20"';
							} else {
								//print ' style="max-height: '.$maxheightmini.'px"';
								print ' style="max-height: 24px"';
							}
							print ' src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.urlencode($modulepart).'&entity='.(empty($object->entity) ? $conf->entity : $object->entity).'&file='.urlencode($relativepath.$smallfile);
							if (!empty($filearray[$key]['date'])) {	// We know the date of file, we can use it as cache key so URL will be in browser cache as long as file date is not modified.
								print '&cache='.urlencode((string) $filearray[$key]['date']);
							}
							print '" title="">';
							print '</a>';
						}
						print '</td>';
					}

					// Shared or not - Hash of file
					print '<td class="center">';
					if ($relativedir && $filearray[$key]['rowid'] > 0) {	// only if we are in a mode where a scan of dir were done and we have id of file in ECM table
						if ($editline) {
							print '<label for="idshareenabled'.$key.'">'.$langs->trans("FileSharedViaALink").'</label> ';
							print '<input class="inline-block" type="checkbox" id="idshareenabled'.$key.'" name="shareenabled"'.($file['share'] ? ' checked="checked"' : '').' /> ';
						} else {
							if ($file['share']) {
								// Define $urlwithroot
								$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
								$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
								//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

								//print '<span class="opacitymedium">'.$langs->trans("Hash").' : '.$file['share'].'</span>';
								$forcedownload = 0;
								$paramlink = '';
								if (!empty($file['share'])) {
									$paramlink .= ($paramlink ? '&' : '').'hashp='.$file['share']; // Hash for public share
								}
								if ($forcedownload) {
									$paramlink .= ($paramlink ? '&' : '').'attachment=1';
								}

								$fulllink = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');

								print '<a href="'.$fulllink.'" target="_blank" rel="noopener">'.img_picto($langs->trans("FileSharedViaALink"), 'globe').'</a> ';
								print '<input type="text" class="quatrevingtpercent minwidth200imp nopadding small" id="downloadlink'.$filearray[$key]['rowid'].'" name="downloadexternallink" title="'.dol_escape_htmltag($langs->trans("FileSharedViaALink")).'" value="'.dol_escape_htmltag($fulllink).'">';
							} else {
								//print '<span class="opacitymedium">'.$langs->trans("FileNotShared").'</span>';
							}
						}
					}
					print '</td>';

					// Actions buttons (1 column or 2 if !disablemove)
					if (!$editline) {
						// Delete or view link
						// ($param must start with &)
						print '<td class="valignmiddle right actionbuttons nowraponall"><!-- action on files -->';
						if ($useinecm == 1 || $useinecm == 5) {	// ECM manual tree only
							// $section is inside $param
							$newparam = preg_replace('/&file=.*$/', '', $param); // We don't need param file=
							$backtopage = DOL_URL_ROOT.'/ecm/index.php?&section_dir='.urlencode($relativepath).$newparam;
							print '<a class="editfielda editfilelink" href="'.DOL_URL_ROOT.'/ecm/file_card.php?urlfile='.urlencode($file['name']).$param.'&backtopage='.urlencode($backtopage).'" rel="'.urlencode($file['name']).'">'.img_edit('default', 0, 'class="paddingrightonly"').'</a>';
						}

						if (empty($useinecm) || $useinecm == 2 || $useinecm == 3 || $useinecm == 6) {	// 6=Media file manager
							$newmodulepart = $modulepart;
							if (in_array($modulepart, array('product', 'produit', 'service'))) {
								$newmodulepart = 'produit|service';
							}
							if (image_format_supported($file['name']) > 0) {
								if ($permtoeditline) {
									$moreparaminurl = '';
									if (!empty($object->id) && $object->id > 0) {
										$moreparaminurl .= '&id='.$object->id;
									} elseif (GETPOST('website', 'alpha')) {
										$moreparaminurl .= '&website='.GETPOST('website', 'alpha');
									}
									// Set the backtourl
									if ($modulepart == 'medias' && !GETPOST('website')) {
										$moreparaminurl .= '&backtourl='.urlencode(DOL_URL_ROOT.'/ecm/index_medias.php?file_manager=1&modulepart='.$modulepart.'&section_dir='.$relativepath);
									}
									// Link to convert into webp
									if (!preg_match('/\.webp$/i', $file['name'])) {
										if ($modulepart == 'medias' && !GETPOST('website')) {
											print '<a href="'.DOL_URL_ROOT.'/ecm/index_medias.php?action=confirmconvertimgwebp&token='.newToken().'&section_dir='.urlencode($relativepath).'&filetoregenerate='.urlencode($fileinfo['basename']).'&module='.$modulepart.$param.$moreparaminurl.'" title="'.dol_escape_htmltag($langs->trans("GenerateChosenImgWebp")).'">'.img_picto('', 'images', 'class="flip marginrightonly"').'</a>';
										} elseif ($modulepart == 'medias' && GETPOST('website')) {
											print '<a href="'.DOL_URL_ROOT.'/website/index.php?action=confirmconvertimgwebp&token='.newToken().'&section_dir='.urlencode($relativepath).'&filetoregenerate='.urlencode($fileinfo['basename']).'&module='.$modulepart.$param.$moreparaminurl.'" title="'.dol_escape_htmltag($langs->trans("GenerateChosenImgWebp")).'">'.img_picto('', 'images', 'class="flip marginrightonly"').'</a>';
										}
									}
								}
							}
							if (!$disablecrop && image_format_supported($file['name']) > 0) {
								if ($permtoeditline) {
									// Link to resize
									$moreparaminurl = '';
									if (!empty($object->id) && $object->id > 0) {
										$moreparaminurl .= '&id='.$object->id;
									} elseif (GETPOST('website', 'alpha')) {
										$moreparaminurl .= '&website='.GETPOST('website', 'alpha');
									}
									// Set the backtourl
									if ($modulepart == 'medias' && !GETPOST('website')) {
										$moreparaminurl .= '&backtourl='.urlencode(DOL_URL_ROOT.'/ecm/index_medias.php?file_manager=1&modulepart='.$modulepart.'&section_dir='.$relativepath);
									}
									//var_dump($moreparaminurl);
									print '<a class="editfielda" href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode($newmodulepart).$moreparaminurl.'&file='.urlencode($relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension'])).'" title="'.dol_escape_htmltag($langs->trans("ResizeOrCrop")).'">'.img_picto($langs->trans("ResizeOrCrop"), 'resize', 'class="paddingrightonly"').'</a>';
								}
							}

							if ($permtoeditline) {
								$paramsectiondir = (in_array($modulepart, array('medias', 'ecm')) ? '&section_dir='.urlencode($relativepath) : '');
								print '<a class="editfielda reposition editfilelink" href="'.(($useinecm == 1 || $useinecm == 5) ? '#' : ($url.'?action=editfile&token='.newToken().'&urlfile='.urlencode($filepath).$paramsectiondir.$param)).'" rel="'.$filepath.'">'.img_edit('default', 0, 'class="paddingrightonly"').'</a>';
							}
						}
						// Output link to delete file
						if ($permonobject) {
							$useajax = 1;
							if (!empty($conf->dol_use_jmobile)) {
								$useajax = 0;
							}
							if (empty($conf->use_javascript_ajax)) {
								$useajax = 0;
							}
							if (getDolGlobalString('MAIN_ECM_DISABLE_JS')) {
								$useajax = 0;
							}
							print '<a href="'.((($useinecm && $useinecm != 3 && $useinecm != 6) && $useajax) ? '#' : ($url.'?action=deletefile&token='.newToken().'&urlfile='.urlencode($filepath).$param)).'" class="reposition deletefilelink" rel="'.$filepath.'">'.img_delete().'</a>';
						}
						print "</td>";

						if (empty($disablemove) && count($filearray) > 1) {
							if ($nboffiles > 1 && $conf->browser->layout != 'phone') {
								print '<td class="linecolmove tdlineupdown center">';
								if ($i > 0) {
									print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=up&rowid='.$object->id.'">'.img_up('default', 0, 'imgupforline').'</a>';
								}
								if ($i < ($nboffiles - 1)) {
									print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=down&rowid='.$object->id.'">'.img_down('default', 0, 'imgdownforline').'</a>';
								}
								print '</td>';
							} else {
								print '<td'.(($conf->browser->layout != 'phone') ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'>';
								print '</td>';
							}
						}
					} else {
						print '<td class="right">';
						print '<input type="hidden" name="ecmfileid" value="'.(empty($filearray[$key]['rowid']) ? '' : $filearray[$key]['rowid']).'">';
						print '<input type="submit" class="button button-save smallpaddingimp" name="renamefilesave" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
						print '<input type="submit" class="button button-cancel smallpaddingimp" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
						print '</td>';
						if (empty($disablemove) && count($filearray) > 1) {
							print '<td class="right"></td>';
						}
					}
					print "</tr>\n";

					$i++;
				}
			}
			if ($nboffiles == 0) {
				$colspan = '6';
				if (empty($disablemove) && count($filearray) > 1) {
					$colspan++; // 6 columns or 7
				}
				print '<tr class="oddeven"><td colspan="'.$colspan.'">';
				if (empty($textifempty)) {
					print '<span class="opacitymedium">'.$langs->trans("NoFileFound").'</span>';
				} else {
					print '<span class="opacitymedium">'.$textifempty.'</span>';
				}
				print '</td></tr>';
			}

			print "</table>";
			print '</div>';

			if ($nboflines > 1 && is_object($object)) {
				if (!empty($conf->use_javascript_ajax) && $permtoeditline) {
					$table_element_line = 'ecm_files';
					include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
				}
			}

			print ajax_autoselect('downloadlink');

			if (GETPOST('action', 'aZ09') == 'editfile' && $permtoeditline) {
				print '</form>';
			}

			return $nboffiles;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show list of documents in a directory of ECM module.
	 *
	 *  @param	string	$upload_dir         Directory that was scanned. This directory will contains files into subdirs REF/files
	 *  @param  array	$filearray          Array of files loaded by dol_dir_list function before calling this function
	 *  @param  string	$modulepart         Value for modulepart used by download wrapper. Value can be $object->table_name (that is 'myobject' or 'mymodule_myobject') or $object->element.'-'.$module (for compatibility purpose)
	 *  @param  string	$param              Parameters on sort links
	 *  @param  int		$forcedownload      Force to open dialog box "Save As" when clicking on file
	 *  @param  string	$relativepath       Relative path of docs (autodefined if not provided)
	 *  @param  int		$permissiontodelete       Permission to delete
	 *  @param  int		$useinecm           Change output for use in ecm module
	 *  @param  string	$textifempty        Text to show if filearray is empty
	 *  @param  int		$maxlength          Maximum length of file name shown
	 *  @param	string 	$url				Full url to use for click links ('' = autodetect)
	 *  @param	int		$addfilterfields	Add line with filters
	 *  @return int                 		Return integer <0 if KO, nb of files shown if OK
	 *  @see list_of_documents()
	 */
	public function list_of_autoecmfiles($upload_dir, $filearray, $modulepart, $param, $forcedownload = 0, $relativepath = '', $permissiontodelete = 1, $useinecm = 0, $textifempty = '', $maxlength = 0, $url = '', $addfilterfields = 0)
	{
		// phpcs:enable
		global $conf, $langs, $hookmanager, $form;
		global $sortfield, $sortorder;
		global $search_doc_ref;
		global $dolibarr_main_url_root;

		dol_syslog(get_class($this).'::list_of_autoecmfiles upload_dir='.$upload_dir.' modulepart='.$modulepart);

		// Show list of documents
		if (empty($useinecm) || $useinecm == 6) {
			print load_fiche_titre($langs->trans("AttachedFiles"));
		}
		if (empty($url)) {
			$url = $_SERVER["PHP_SELF"];
		}

		if (!empty($addfilterfields)) {
			print '<form action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="module" value="'.$modulepart.'">';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table width="100%" class="noborder">'."\n";

		if (!empty($addfilterfields)) {
			print '<tr class="liste_titre nodrag nodrop">';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre"><input type="text" class="maxwidth100onsmartphone" name="search_doc_ref" value="'.dol_escape_htmltag($search_doc_ref).'"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre"></td>';
			// Action column
			print '<td class="liste_titre right">';
			$searchpicto = $form->showFilterButtons();
			print $searchpicto;
			print '</td>';
			print "</tr>\n";
		}

		print '<tr class="liste_titre">';
		$sortref = "fullname";
		if ($modulepart == 'invoice_supplier') {
			$sortref = 'level1name';
		}
		print_liste_field_titre("Ref", $url, $sortref, "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("Documents2", $url, "name", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("Size", $url, "size", "", $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("Date", $url, "date", "", $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("Shared", $url, 'share', '', $param, '', $sortfield, $sortorder, 'right ');
		print '</tr>'."\n";

		// To show ref or specific information according to view to show (defined by $module)
		$object_instance = null;
		if ($modulepart == 'company') {
			include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$object_instance = new Societe($this->db);
		} elseif ($modulepart == 'invoice') {
			include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$object_instance = new Facture($this->db);
		} elseif ($modulepart == 'invoice_supplier') {
			include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
			$object_instance = new FactureFournisseur($this->db);
		} elseif ($modulepart == 'propal') {
			include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$object_instance = new Propal($this->db);
		} elseif ($modulepart == 'supplier_proposal') {
			include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
			$object_instance = new SupplierProposal($this->db);
		} elseif ($modulepart == 'order') {
			include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
			$object_instance = new Commande($this->db);
		} elseif ($modulepart == 'order_supplier') {
			include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
			$object_instance = new CommandeFournisseur($this->db);
		} elseif ($modulepart == 'contract') {
			include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
			$object_instance = new Contrat($this->db);
		} elseif ($modulepart == 'product') {
			include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$object_instance = new Product($this->db);
		} elseif ($modulepart == 'tax') {
			include_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
			$object_instance = new ChargeSociales($this->db);
		} elseif ($modulepart == 'tax-vat') {
			include_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
			$object_instance = new Tva($this->db);
		} elseif ($modulepart == 'salaries') {
			include_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
			$object_instance = new Salary($this->db);
		} elseif ($modulepart == 'project') {
			include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			$object_instance = new Project($this->db);
		} elseif ($modulepart == 'project_task') {
			include_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
			$object_instance = new Task($this->db);
		} elseif ($modulepart == 'fichinter') {
			include_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
			$object_instance = new Fichinter($this->db);
		} elseif ($modulepart == 'user') {
			include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
			$object_instance = new User($this->db);
		} elseif ($modulepart == 'expensereport') {
			include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
			$object_instance = new ExpenseReport($this->db);
		} elseif ($modulepart == 'holiday') {
			include_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
			$object_instance = new Holiday($this->db);
		} elseif ($modulepart == 'recruitment-recruitmentcandidature') {
			include_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';
			$object_instance = new RecruitmentCandidature($this->db);
		} elseif ($modulepart == 'banque') {
			include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$object_instance = new Account($this->db);
		} elseif ($modulepart == 'chequereceipt') {
			include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
			$object_instance = new RemiseCheque($this->db);
		} elseif ($modulepart == 'mrp-mo') {
			include_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
			$object_instance = new Mo($this->db);
		} else {
			$parameters = array('modulepart' => $modulepart);
			$reshook = $hookmanager->executeHooks('addSectionECMAuto', $parameters);
			if ($reshook > 0 && is_array($hookmanager->resArray) && count($hookmanager->resArray) > 0) {
				if (array_key_exists('classpath', $hookmanager->resArray) && !empty($hookmanager->resArray['classpath'])) {
					dol_include_once($hookmanager->resArray['classpath']);
					if (array_key_exists('classname', $hookmanager->resArray) && !empty($hookmanager->resArray['classname'])) {
						if (class_exists($hookmanager->resArray['classname'])) {
							$tmpclassname = $hookmanager->resArray['classname'];
							$object_instance = new $tmpclassname($this->db);
						}
					}
				}
			}
		}

		//var_dump($filearray);
		//var_dump($object_instance);

		// Get list of files stored into database for same relative directory
		$relativepathfromroot = preg_replace('/'.preg_quote(DOL_DATA_ROOT.'/', '/').'/', '', $upload_dir);
		if ($relativepathfromroot) {
			completeFileArrayWithDatabaseInfo($filearray, $relativepathfromroot.'/%');

			//var_dump($sortfield.' - '.$sortorder);
			if ($sortfield && $sortorder) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
				$filearray = dol_sort_array($filearray, $sortfield, $sortorder, 1);
			}
		}

		//var_dump($filearray);

		foreach ($filearray as $key => $file) {
			if (!is_dir($file['name'])
			&& $file['name'] != '.'
			&& $file['name'] != '..'
			&& $file['name'] != 'CVS'
			&& !preg_match('/\.meta$/i', $file['name'])) {
				// Define relative path used to store the file
				$relativefile = preg_replace('/'.preg_quote($upload_dir.'/', '/').'/', '', $file['fullname']);

				$id = 0;
				$ref = '';

				// To show ref or specific information according to view to show (defined by $modulepart)
				// $modulepart can be $object->table_name (that is 'mymodule_myobject') or $object->element.'-'.$module (for compatibility purpose)
				$reg = array();
				if ($modulepart == 'company' || $modulepart == 'tax' || $modulepart == 'tax-vat' || $modulepart == 'salaries') {
					preg_match('/(\d+)\/[^\/]+$/', $relativefile, $reg);
					$id = (isset($reg[1]) ? $reg[1] : '');
				} elseif ($modulepart == 'invoice_supplier') {
					preg_match('/([^\/]+)\/[^\/]+$/', $relativefile, $reg);
					$ref = (isset($reg[1]) ? $reg[1] : '');
					if (is_numeric($ref)) {
						$id = $ref;
						$ref = '';
					}
				} elseif ($modulepart == 'user') {
					// $ref may be also id with old supplier invoices
					preg_match('/(.*)\/[^\/]+$/', $relativefile, $reg);
					$id = (isset($reg[1]) ? $reg[1] : '');
				} elseif ($modulepart == 'project_task') {
					// $ref of task is the sub-directory of the project
					$reg = explode("/", $relativefile);
					$ref = (isset($reg[1]) ? $reg[1] : '');
				} elseif (in_array($modulepart, array(
					'invoice',
					'propal',
					'supplier_proposal',
					'order',
					'order_supplier',
					'contract',
					'product',
					'project',
					'project_task',
					'fichinter',
					'expensereport',
					'recruitment-recruitmentcandidature',
					'mrp-mo',
					'banque',
					'chequereceipt',
					'holiday'))) {
					preg_match('/(.*)\/[^\/]+$/', $relativefile, $reg);
					$ref = (isset($reg[1]) ? $reg[1] : '');
				} else {
					$parameters = array('modulepart' => $modulepart, 'fileinfo' => $file);
					$reshook = $hookmanager->executeHooks('addSectionECMAuto', $parameters);
					if ($reshook > 0 && is_array($hookmanager->resArray) && count($hookmanager->resArray) > 0) {
						if (array_key_exists('ref', $hookmanager->resArray) && !empty($hookmanager->resArray['ref'])) {
							$ref = $hookmanager->resArray['ref'];
						}
						if (array_key_exists('id', $hookmanager->resArray) && !empty($hookmanager->resArray['id'])) {
							$id = $hookmanager->resArray['id'];
						}
					}
					//print 'Error: Value for modulepart = '.$modulepart.' is not yet implemented in function list_of_autoecmfiles'."\n";
				}

				if (!$id && !$ref) {
					continue;
				}

				$found = 0;
				if (!empty($conf->cache['modulepartobject'][$modulepart.'_'.$id.'_'.$ref])) {
					$found = 1;
				} else {
					//print 'Fetch '.$id." - ".$ref.' class='.get_class($object_instance).'<br>';

					$result = 0;
					if (is_object($object_instance)) {
						$object_instance->id = 0;
						$object_instance->ref = '';
						if ($id) {
							$result = $object_instance->fetch($id);
						} else {
							if (!($result = $object_instance->fetch('', $ref))) {
								//fetchOneLike looks for objects with wildcards in its reference.
								//It is useful for those masks who get underscores instead of their actual symbols (because the _ had replaced all forbidden chars into filename)
								// TODO Example when this is needed ?
								// This may find when ref is 'A_B' and date was stored as 'A~B' into database, but in which case do we have this ?
								// May be we can add hidden option to enable this.
								$result = $object_instance->fetchOneLike($ref);
							}
						}
					}

					if ($result > 0) {  // Save object loaded into a cache
						$found = 1;
						$conf->cache['modulepartobject'][$modulepart.'_'.$id.'_'.$ref] = clone $object_instance;
					}
					if ($result == 0) {
						$found = 1;
						$conf->cache['modulepartobject'][$modulepart.'_'.$id.'_'.$ref] = 'notfound';
						unset($filearray[$key]);
					}
				}

				if ($found <= 0 || !is_object($conf->cache['modulepartobject'][$modulepart.'_'.$id.'_'.$ref])) {
					continue; // We do not show orphelins files
				}

				print '<!-- Line list_of_autoecmfiles key='.$key.' -->'."\n";
				print '<tr class="oddeven">';
				print '<td>';
				if ($found > 0 && is_object($conf->cache['modulepartobject'][$modulepart.'_'.$id.'_'.$ref])) {
					$tmpobject = $conf->cache['modulepartobject'][$modulepart.'_'.$id.'_'.$ref];
					//if (! in_array($tmpobject->element, array('expensereport'))) {
					print $tmpobject->getNomUrl(1, 'document');
					//} else {
					//	print $tmpobject->getNomUrl(1);
					//}
				} else {
					print $langs->trans("ObjectDeleted", ($id ? $id : $ref));
				}

				//$modulesubdir=dol_sanitizeFileName($ref);
				//$modulesubdir = dirname($relativefile);

				//$filedir=$conf->$modulepart->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				//$filedir = $file['path'];
				//$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				//print $formfile->getDocumentsLink($modulepart, $filename, $filedir);
				print '</td>';

				// File
				// Check if document source has external module part, if it the case use it for module part on document.php
				print '<td>';
				//print "XX".$file['name']; //$file['name'] must be utf8
				print '<a ';
				if (getDolGlobalInt('MAIN_DISABLE_FORCE_SAVEAS') == 2) {
					print 'target="_blank" ';
				}
				print 'href="'.DOL_URL_ROOT.'/document.php?modulepart='.urlencode($modulepart);
				if ($forcedownload) {
					print '&attachment=1';
				}
				print '&file='.urlencode($relativefile).'">';
				print img_mime($file['name'], $file['name'].' ('.dol_print_size($file['size'], 0, 0).')');
				print dol_escape_htmltag(dol_trunc($file['name'], $maxlength, 'middle'));
				print '</a>';

				//print $this->getDocumentsLink($modulepart, $modulesubdir, $filedir, '^'.preg_quote($file['name'],'/').'$');

				print $this->showPreview($file, $modulepart, $file['relativename']);

				print "</td>\n";

				// Size
				$sizetoshow = dol_print_size($file['size'], 1, 1);
				$sizetoshowbytes = dol_print_size($file['size'], 0, 1);
				print '<td class="right nowraponall">';
				if ($sizetoshow == $sizetoshowbytes) {
					print $sizetoshow;
				} else {
					print $form->textwithpicto($sizetoshow, $sizetoshowbytes, -1);
				}
				print '</td>';

				// Date
				print '<td class="center">'.dol_print_date($file['date'], "dayhour").'</td>';

				// Share link
				print '<td class="right">';
				if (!empty($file['share'])) {
					// Define $urlwithroot
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

					//print '<span class="opacitymedium">'.$langs->trans("Hash").' : '.$file['share'].'</span>';
					$forcedownload = 0;
					$paramlink = '';
					if (!empty($file['share'])) {
						$paramlink .= ($paramlink ? '&' : '').'hashp='.$file['share']; // Hash for public share
					}
					if ($forcedownload) {
						$paramlink .= ($paramlink ? '&' : '').'attachment=1';
					}

					$fulllink = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');

					print img_picto($langs->trans("FileSharedViaALink"), 'globe').' ';
					print '<input type="text" class="quatrevingtpercent width100 nopadding nopadding small" id="downloadlink" name="downloadexternallink" value="'.dol_escape_htmltag($fulllink).'">';
				}
				//if (!empty($useinecm) && $useinecm != 6)  print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
				//if ($forcedownload) print '&attachment=1';
				//print '&file='.urlencode($relativefile).'">';
				//print img_view().'</a> &nbsp; ';
				//if ($permissiontodelete) print '<a href="'.$url.'?id='.$object->id.'&section='.$_REQUEST["section"].'&action=delete&token='.newToken().'&urlfile='.urlencode($file['name']).'">'.img_delete().'</a>';
				//else print '&nbsp;';
				print "</td>";

				print "</tr>\n";
			}
		}

		if (count($filearray) == 0) {
			print '<tr class="oddeven"><td colspan="5">';
			if (empty($textifempty)) {
				print '<span class="opacitymedium">'.$langs->trans("NoFileFound").'</span>';
			} else {
				print '<span class="opacitymedium">'.$textifempty.'</span>';
			}
			print '</td></tr>';
		}
		print "</table>";
		print '</div>';

		if (!empty($addfilterfields)) {
			print '</form>';
		}
		return count($filearray);
		// Fin de zone
	}

	/**
	 * Show array with linked files
	 *
	 * @param 	Object		$object			Object
	 * @param 	int			$permissiontodelete	Deletion is allowed
	 * @param 	string		$action			Action
	 * @param 	string		$selected		???
	 * @param	string		$param			More param to add into URL
	 * @return 	int							Number of links
	 */
	public function listOfLinks($object, $permissiontodelete = 1, $action = null, $selected = null, $param = '')
	{
		global $user, $conf, $langs, $user;
		global $sortfield, $sortorder;

		$langs->load("link");

		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$link = new Link($this->db);
		$links = array();
		if ($sortfield == "name") {
			$sortfield = "label";
		} elseif ($sortfield == "date") {
			$sortfield = "datea";
		} else {
			$sortfield = '';
		}
		$res = $link->fetchAll($links, $object->element, $object->id, $sortfield, $sortorder);
		$param .= (isset($object->id) ? '&id='.$object->id : '');

		print '<!-- listOfLinks -->'."\n";

		// Show list of associated links
		print load_fiche_titre($langs->trans("LinkedFiles"), '', 'link', 0, '', 'table-list-of-links');

		print '<form action="'.$_SERVER['PHP_SELF'].($param ? '?'.$param : '').'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		print '<table class="liste noborder nobottom centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre(
			$langs->trans("Links"),
			$_SERVER['PHP_SELF'],
			"name",
			"",
			$param,
			'',
			$sortfield,
			$sortorder,
			''
		);
		print_liste_field_titre(
			"",
			"",
			"",
			"",
			"",
			'',
			'',
			'',
			'right '
		);
		print_liste_field_titre(
			$langs->trans("Date"),
			$_SERVER['PHP_SELF'],
			"date",
			"",
			$param,
			'',
			$sortfield,
			$sortorder,
			'center '
		);
		print_liste_field_titre(
			'',
			$_SERVER['PHP_SELF'],
			"",
			"",
			$param,
			'',
			'',
			'',
			'center '
		);
		print_liste_field_titre('', '', '');
		print '</tr>';
		$nboflinks = count($links);
		if ($nboflinks > 0) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
		}

		foreach ($links as $link) {
			print '<tr class="oddeven">';
			//edit mode
			if ($action == 'update' && $selected === $link->id) {
				print '<td>';
				print '<input type="hidden" name="id" value="'.$object->id.'">';
				print '<input type="hidden" name="linkid" value="'.$link->id.'">';
				print '<input type="hidden" name="action" value="confirm_updateline">';
				print $langs->trans('Link').': <input type="text" name="link" value="'.$link->url.'">';
				print '</td>';
				print '<td>';
				print $langs->trans('Label').': <input type="text" name="label" value="'.dol_escape_htmltag($link->label).'">';
				print '</td>';
				print '<td class="center">'.dol_print_date(dol_now(), "dayhour", "tzuser").'</td>';
				print '<td class="right"></td>';
				print '<td class="right">';
				print '<input type="submit" class="button button-save" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</td>';
			} else {
				print '<td>';
				print img_picto('', 'globe').' ';
				print '<a data-ajax="false" href="'.$link->url.'" target="_blank" rel="noopener noreferrer">';
				print dol_escape_htmltag($link->label);
				print '</a>';
				print '</td>'."\n";
				print '<td class="right"></td>';
				print '<td class="center">'.dol_print_date($link->datea, "dayhour", "tzuser").'</td>';
				print '<td class="center"></td>';
				print '<td class="right">';
				print '<a href="'.$_SERVER['PHP_SELF'].'?action=update&linkid='.$link->id.$param.'&token='.newToken().'" class="editfilelink editfielda reposition" >'.img_edit().'</a>'; // id= is included into $param
				if ($permissiontodelete) {
					print ' &nbsp; <a class="deletefilelink reposition" href="'.$_SERVER['PHP_SELF'].'?action=deletelink&token='.newToken().'&linkid='.((int) $link->id).$param.'">'.img_delete().'</a>'; // id= is included into $param
				} else {
					print '&nbsp;';
				}
				print '</td>';
			}
			print "</tr>\n";
		}
		if ($nboflinks == 0) {
			print '<tr class="oddeven"><td colspan="5">';
			print '<span class="opacitymedium">'.$langs->trans("NoLinkFound").'</span>';
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
	 * @param   integer   $ruleforpicto   Rule for picto: 0=Use the generic preview picto, 1=Use the picto of mime type of file). Use a negative value to show a generic picto even if preview not available.
	 * @param	string	  $param		  More param on http links
	 * @return  string    $out            Output string with HTML
	 */
	public function showPreview($file, $modulepart, $relativepath, $ruleforpicto = 0, $param = '')
	{
		global $langs, $conf;

		$out = '';
		if ($conf->browser->layout != 'phone' && !empty($conf->use_javascript_ajax)) {
			$urladvancedpreview = getAdvancedPreviewUrl($modulepart, $relativepath, 1, $param); // Return if a file is qualified for preview.
			if (count($urladvancedpreview)) {
				$out .= '<a class="pictopreview '.$urladvancedpreview['css'].'" href="'.$urladvancedpreview['url'].'"'.(empty($urladvancedpreview['mime']) ? '' : ' mime="'.$urladvancedpreview['mime'].'"').' '.(empty($urladvancedpreview['target']) ? '' : ' target="'.$urladvancedpreview['target'].'"').'>';
				//$out.= '<a class="pictopreview">';
				if (empty($ruleforpicto)) {
					//$out.= img_picto($langs->trans('Preview').' '.$file['name'], 'detail');
					$out .= '<span class="fa fa-search-plus pictofixedwidth" style="color: gray"></span>';
				} else {
					$out .= img_mime($relativepath, $langs->trans('Preview').' '.$file['name'], 'pictofixedwidth');
				}
				$out .= '</a>';
			} else {
				if ($ruleforpicto < 0) {
					$out .= img_picto('', 'generic', '', false, 0, 0, '', 'paddingright pictofixedwidth');
				}
			}
		}
		return $out;
	}
}
