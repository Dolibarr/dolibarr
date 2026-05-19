<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Gaëtan MAISON <gm@ilad.org>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *       \file       htdocs/core/class/doleditor.class.php
 *       \brief      Class to manage a WYSIWYG editor
 */

/**
 *      Class to manage a WYSIWYG editor.
 *		Usage: $doleditor = new DolEditor('body', $message, 320, 'toolbar_mailing');
 *		       $doleditor->Create();
 */
class DolEditor
{
	/**
	 * @var string
	 */
	public $tool; // Store the selected tool

	// If using fckeditor
	/**
	 * @var Object - Note: seems to be unused
	 */
	public $editor;

	// If not using fckeditor
	/**
	 * @var ?string
	 */
	public $content;
	/**
	 * @var string
	 */
	public $htmlname;
	/**
	 * @var string
	 */
	public $toolbarname;
	/**
	 * @var bool
	 */
	public $toolbarstartexpanded;
	/**
	 * @var int
	 */
	public $rows;
	/**
	 * @var string
	 */
	public $cols;
	/**
	 * @var int
	 */
	public $height;
	/**
	 * @var string
	 */
	public $width;
	/**
	 * @var int<0,1>|bool
	 */
	public $uselocalbrowser;
	/**
	 * @var int<0,1>
	 */
	public $readonly;
	/**
	 * @var int
	 */
	public $posx;
	/**
	 * @var int
	 */
	public $posy;


	/**
	 *  Create an object to build an HTML area to edit a large string content
	 *
	 *  @param 	string				$htmlname		        		HTML name of WYSIWYG field
	 *  @param 	string				$content		        		Content of WYSIWYG field
	 *  @param	int|string			$width							Width in pixel of edit area (auto by default)
	 *  @param 	int					$height			       		 	Height in pixel of edit area (200px by default)
	 *  @param 	string				$toolbarname	       		 	Name of the toolbar set to use ('Basic', 'dolibarr_details[_encoded]'=the less featured, 'dolibarr_notes[_encoded]' for notes content, 'dolibarr_mailings[_encoded]' for emailing content, 'dolibarr_readonly').
	 *  @param  string				$notused		       			Deprecated. Not used
	 *  @param  bool				$toolbarstartexpanded  			Bar is visible or not at start
	 *  @param	bool|int			$uselocalbrowser				Enabled to add links to local object with a local media filemanager. -1 or true = auto, false = only external images URL can be added into content, or images saved inline with src="data:..." with a cut/paste.
	 *  @param  bool|int|string		$okforextendededitor    		1 or True=Allow usage of extended editor tool if qualified (like ckeditor). If 'textarea', force use of simple textarea. If 'ace', force use of Ace.
	 *                          	                        		Warning: If you use 'ace', don't forget to also include ace.js in page header. Also, the button "save" must have class="buttonforacesave".
	 *  @param  int					$rows                   		Size of rows for textarea text tool
	 *  @param  string				$cols                   		Size of cols for textarea text tool (textarea number of cols '70' or percent recommended 'x%')
	 *  @param	int<0,1>			$readonly						0=Read/Edit, 1=Read only
	 *  @param	array{x?:string,y?:string,find?:string}	$poscursor	Array for initial cursor position array('x'=>x, 'y'=>y).
	 *                      	                       				array('find'=> 'word')  can be used to go to line were the word has been found
	 */
	public function __construct($htmlname, $content, $width = '', $height = 200, $toolbarname = 'Basic', $notused = '', $toolbarstartexpanded = false, $uselocalbrowser = -1, $okforextendededitor = true, $rows = 0, $cols = '', $readonly = 0, $poscursor = array())  // @phpstan-ignore constructor.unusedParameter
	{
		global $conf;

		dol_syslog(get_class($this)."::DolEditor htmlname=".$htmlname." width=".$width." height=".$height." toolbarname=".$toolbarname." uselocalbrowser=".$uselocalbrowser);

		if ($uselocalbrowser) {
			// This may not be supported by new generation of WYSIWYG editors.
			$uselocalbrowser = getDolGlobalInt("WYSIWYG_ALLOW_UPLOAD_MEDIA_FILES");
		}

		if (!$rows) {
			$rows = round($height / 20);
		}
		if (!$cols) {
			$cols = ($width ? round($width / 6) : 80);
		}
		$shorttoolbarname = preg_replace('/_encoded$/', '', $toolbarname);

		// Name of extended editor to use (FCKEDITOR_EDITORNAME can be 'ckeditor' or 'fckeditor')
		$defaulteditor = 'ckeditor';
		$this->tool = getDolGlobalString('FCKEDITOR_EDITORNAME', $defaulteditor);
		$this->uselocalbrowser = $uselocalbrowser;
		$this->readonly = $readonly;

		// Check if extended editor is ok. If not we force textarea
		if ((!isModEnabled('fckeditor') && $okforextendededitor !== 'ace') || empty($okforextendededitor)) {
			$this->tool = 'textarea';
		}
		if ($okforextendededitor === 'ace') {
			$this->tool = 'ace';
		}
		//if ($conf->dol_use_jmobile) $this->tool = 'textarea';       // ckeditor and ace seems ok with mobile
		if (empty($conf->use_javascript_ajax)) {	// If no javascript, we force use of textarea
			$this->tool = 'textarea';
		}

		if (isset($poscursor['find'])) {
			$posy = 0;
			$lines = explode("\n", $content);
			$nblines = count($lines);
			for ($i = 0 ; $i < $nblines ; $i++) {
				if (preg_match('/'.$poscursor['find'].'/', $lines[$i])) {
					$posy = $i;
					break;
				}
			}
			if ($posy != 0) {
				$poscursor['y'] = $posy;
			}
		}

		// Define some properties
		if (in_array($this->tool, array('textarea', 'ckeditor', 'tinymce', 'ace'))) {
			if (in_array($this->tool, array('ckeditor', 'tinymce')) && !dol_textishtml($content)) {	// We force content to be into HTML if we are using an advanced editor if content is not HTML.
				$this->content = dol_nl2br($content);
			} else {
				$this->content = $content;
			}
			$this->htmlname 			= $htmlname;
			$this->toolbarname = $shorttoolbarname;
			$this->toolbarstartexpanded = $toolbarstartexpanded;
			$this->rows					= max(ROWS_3, $rows);
			$this->cols					= (preg_match('/%/', $cols) ? $cols : max(40, $cols)); // If $cols is a percent, we keep it, otherwise, we take max
			$this->height               = $height;
			$this->width				= $width;
			$this->posx                 = empty($poscursor['x']) ? 0 : $poscursor['x'];
			$this->posy                 = empty($poscursor['y']) ? 0 : $poscursor['y'];
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Output edit area inside the HTML stream.
	 *	Output depends on this->tool (fckeditor, ckeditor, textarea, ...)
	 *
	 *  @param	int		$noprint            1=Return HTML string instead of printing it to output
	 *  @param	string	$morejs		        Add more js. For example: ".on( \'saveSnapshot\', function(e) { alert(\'ee\'); });". Used by CKEditor only.
	 *  @param  boolean|string	$restrictContent		Decide which content is allowed. Used by CKEditor only.
	 *                                                  true = restrict allowed content to a predefined list of allowed elements.
	 *                                                  false = allow anything
	 *                                                  string = true + add values into string
	 *  @param	string	$titlecontent		Show title content before editor area. Used by ACE editor only.
	 *  @param	string	$option				For ACE editor, set the source language ('html', 'php', 'javascript', 'json', ...)
	 *  @param	string	$moreparam			Add extra tags to the textarea
	 *  @param	string	$morecss			Add extra css to the textarea
	 *  @return	void|string
	 */
	public function Create($noprint = 0, $morejs = '', $restrictContent = true, $titlecontent = '', $option = '', $moreparam = '', $morecss = '')
	{
		// phpcs:enable
		global $conf, $langs;

		$fullpage = false;

		// syntax is [] for attributes and {} for value inside style
		$extraAllowedContent = 'a[target];';
		$extraAllowedContent .= 'section[contenteditable,id];';
		$extraAllowedContent .= 'table{border-spacing};';
		$extraAllowedContent .= 'td{padding};';
		$extraAllowedContent .= 'p{margin-left,margin-right,margin-top,margin-bottom,padding,line-height};';
		$extraAllowedContent .= 'div{background-color,color,display,float,height,margin,margin-top,margin-bottom,padding,padding-left,padding-right,padding-top,padding-bottom,width,border-top-left-radius,border-top-right-radius,border-bottom-left-radius,border-bottom-right-radius,box-shadow}';

		if (is_string($restrictContent)) {
			$extraAllowedContent = $restrictContent;
		}
		if (isset($conf->global->FCKEDITOR_ALLOW_ANY_CONTENT)) {
			$restrictContent = !getDolGlobalString('FCKEDITOR_ALLOW_ANY_CONTENT'); // Only predefined list of html tags are allowed or all
		}

		$found = 0;
		$out = '';

		$this->content = (string) $this->content; // to avoid htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated

		if (in_array($this->tool, array('textarea', 'ckeditor', 'tinymce'))) {
			$found = 1;

			$out .= "\n".'<!-- Output textarea '.dol_string_nohtmltag($this->tool).' '.dol_string_nohtmltag($this->htmlname).' toolbarname = '.dol_string_nohtmltag($this->toolbarname).' -->'."\n";

			// Note: We do not put the attribute 'disabled' tag because on a read form, it change style with grey.
			$out .= '<textarea id="'.$this->htmlname.'" name="'.$this->htmlname.'"';
			$out .= ' rows="'.$this->rows.'"';
			if (preg_match('/%/', $this->cols)) {	// ->cols is a percent
				$out .= ' style="margin-top: 5px; width: ';
				if ($this->tool == 'tinymce') {
					$out .= $this->cols;
				} else {
					$out .= 'calc('.$this->cols.' - 20px)';	// The with in percent minus the margin.
				}
				$out .= '"';
			} else {
				$out   .= ' cols="'.$this->cols.'"';
			}
			$out .= ' '.($moreparam ? $moreparam : '');
			$out .= ' class="flat '.dol_string_nohtmltag($this->toolbarname).' '.$morecss.'">';
			$out .= htmlspecialchars($this->content);
			$out .= '</textarea>';

			if ($this->tool == 'ckeditor' && !empty($conf->use_javascript_ajax) && isModEnabled('fckeditor')) {
				if (!defined('REQUIRE_CKEDITOR')) {
					define('REQUIRE_CKEDITOR', '1');
				}

				$skin = getDolGlobalString('FCKEDITOR_SKIN', 'moono-lisa');		// default with ckeditor 4.6 : moono-lisa

				$pluginstodisable = 'elementspath,save,flash,div,anchor';
				if (!getDolGlobalString('FCKEDITOR_ENABLE_SPECIALCHAR')) {
					$pluginstodisable .= ',specialchar';
				}
				if (!empty($conf->dol_optimize_smallscreen)) {
					$pluginstodisable .= ',scayt,wsc,find,undo';
				}
				if (!getDolGlobalString('FCKEDITOR_ENABLE_WSC')) {	// spellchecker has end of life december 2021
					$pluginstodisable .= ',wsc';
				}
				if (!getDolGlobalString('FCKEDITOR_ENABLE_PDF')) {
					$pluginstodisable .= ',exportpdf';
				}
				if (getDolGlobalInt('MAIN_DISALLOW_URL_INTO_DESCRIPTIONS') == 2) {
					$this->uselocalbrowser = 0;	// Can't use browser to navigate into files. Only links with "<img src=data:..." are allowed.
				}
				$scaytautostartup = '';
				if (getDolGlobalString('FCKEDITOR_ENABLE_SCAYT_AUTOSTARTUP')) {
					$scaytautostartup = 'scayt_autoStartup: true,';
					$scaytautostartup .= 'scayt_sLang: \''.dol_escape_js($langs->getDefaultLang()).'\',';
				} else {
					$pluginstodisable .= ',scayt';
				}

				$htmlencode_force = preg_match('/_encoded$/', $this->toolbarname) ? 'true' : 'false';

				$out .= '<!-- Output ckeditor disallowAnyContent='.dol_escape_htmltag((string) $restrictContent).' toolbarname='.dol_escape_htmltag($this->toolbarname).' -->'."\n";
				//$out .= '<style>#cke_1_top { height: 34px !important; }</style>';
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">
            			$(document).ready(function () {
							/* console.log("Run ckeditor"); */
                            /* if (CKEDITOR.loadFullCore) CKEDITOR.loadFullCore(); */
                            /* should be editor=CKEDITOR.replace but what if there is several editors ? */
                            tmpeditor = CKEDITOR.replace(\''.dol_escape_js($this->htmlname).'\',
            					{
            						/* property: xxx is same than CKEDITOR.config.property = xxx */
            						customConfig: ckeditorConfig,
									removePlugins: \''.dol_escape_js($pluginstodisable).'\',
									versionCheck: false,
            						readOnly: '.($this->readonly ? 'true' : 'false').',
                            		htmlEncodeOutput: '.dol_escape_js($htmlencode_force).',
            						allowedContent: '.($restrictContent ? 'false' : 'true').',			/* Advanced Content Filter (ACF) is on when allowedContent is false */
            						extraAllowedContent: \''.dol_escape_js($extraAllowedContent).'\',	/* Allow a tag with attribute target, allow section tag and allow the style float and display into div to default other allowed tags */
									disallowedContent: \'\',											/* Tags that are not allowed */
            						fullPage: '.($fullpage ? 'true' : 'false').',						/* if true, the html, header and body tags are kept */
                            		toolbar: \''.dol_escape_js($this->toolbarname).'\',
            						toolbarStartupExpanded: '.($this->toolbarstartexpanded ? 'true' : 'false').',
            						width: '.($this->width ? '\''.dol_escape_js($this->width).'\'' : '\'\'').',
            						height: '.dol_escape_js((string) $this->height).',
                                    skin: \''.dol_escape_js($skin).'\',
                                    '.$scaytautostartup.'
                                    language: \''.dol_escape_js($langs->defaultlang).'\',
                                    textDirection: \''.dol_escape_js($langs->trans("DIRECTION")).'\',
                                    on : {
                                                instanceReady : function(ev) {
													console.log(\'ckeditor '.dol_escape_js($this->htmlname).' instanceReady\');

													/* If we found the attribute required on source div, we remove it (not compatible with ckeditor) */
													/* Disabled, because attribute "required" should never be used on fields for doleditor */
													/* jQuery("#'.dol_escape_js($this->htmlname).'").attr("required", false); */

                                                    // Output paragraphs as <p>Text</p>.
                                                    this.dataProcessor.writer.setRules( \'p\', {
                                                        indent : false,
                                                        breakBeforeOpen : true,
                                                        breakAfterOpen : false,
                                                        breakBeforeClose : false,
                                                        breakAfterClose : true
                                                    });
                                                },
										},
									disableNativeSpellChecker: '.(getDolGlobalString('CKEDITOR_NATIVE_SPELLCHECKER') ? 'false' : 'true');

				if ($this->uselocalbrowser) {
					$out .= ','."\n";
					// To use filemanager with old fckeditor (GPL)
					// Note: ckeditorFilebrowserBrowseUrl and ckeditorFilebrowserImageBrowseUrl are defined in header by main.inc.php. They include url to browser with url of upload connector in parameter
					$out .= '    filebrowserBrowseUrl : ckeditorFilebrowserBrowseUrl,';
					$out .= '    filebrowserImageBrowseUrl : ckeditorFilebrowserImageBrowseUrl,';
					$out .= "\n";
					$out .= '    filebrowserWindowWidth : \'900\',
                               filebrowserWindowHeight : \'500\',
                               filebrowserImageWindowWidth : \'900\',
                               filebrowserImageWindowHeight : \'500\'';
				}
				$out .= '	})'.$morejs;	// end CKEditor.replace
				// Show the CKEditor javascript object once loaded is ready (For debug)
				//$out .= '; CKEDITOR.on(\'instanceReady\', function(ck) { ck.editor.removeMenuItem(\'maximize\'); ck.editor.removeMenuItem(\'Undo\'); ck.editor.removeMenuItem(\'undo\'); console.log(ck.editor); console.log(ck.editor.toolbar[0]); }); ';
				$out .= '});'."\n";	// end document.ready
				$out .= '</script>'."\n";
			}

			if ($this->tool == 'tinymce' && !empty($conf->use_javascript_ajax) && isModEnabled('fckeditor')) {
				if (!defined('REQUIRE_TINYMCE')) {
					define('REQUIRE_TINYMCE', '1');
				}

				$htmlencode_force = preg_match('/_encoded$/', $this->toolbarname) ? 'true' : 'false';
				$pickerenabled = $this->uselocalbrowser ? 'true' : 'false';

				$out .= '<!-- Output tinymce toolbarname='.dol_escape_htmltag($this->toolbarname).' -->'."\n";
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
				$out .= '$(document).ready(function () {'."\n";
				$out .= '	if (typeof tinymce === "undefined") {'."\n";
				$out .= '		console.error("TinyMCE library not loaded. Check that .../includes/tinymce/tinymce/tinymce.min.js exists and FCKEDITOR_EDITORNAME=tinymce.");'."\n";
				$out .= '		return;'."\n";
				$out .= '	}'."\n";
				$out .= '	var toolbarName = "'.dol_escape_js($this->toolbarname).'";'."\n";
				$out .= '	var toolbars = (window.dolTinymceToolbars || {});'."\n";
				$out .= '	var toolbarStr = toolbars[toolbarName] || toolbars["dolibarr_details"] || "undo redo | bold italic | link | code";'."\n";
				$out .= '	var pluginsStr = (typeof window.dolTinymcePluginsFor === "function") ? window.dolTinymcePluginsFor(toolbarName) : "advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen table help wordcount";'."\n";
				if (!getDolGlobalString('FCKEDITOR_ENABLE_SPECIALCHAR')) {
					$out .= 'pluginsStr = pluginsStr.replace(\' charmap\', \'\');'."\n";
				}
				$out .= '	var tinyLang = "'.dol_escape_js($langs->defaultlang).'";'."\n";
				$out .= '	var tinyConf = {'."\n";
				$out .= '		selector: "textarea#'.dol_escape_js($this->htmlname).'",'."\n";
				$out .= '		toolbar: toolbarStr,'."\n";
				$out .= '		plugins: pluginsStr,'."\n";
				$out .= '		menubar: false,'."\n";
				if (!getDolGlobalString('WYSIWYG_TINYMCE_ENABLE_STATUS_BAR')) {
					$out .= '		statusbar: false,'."\n";
				}
				$out .= '		branding: false,'."\n";
				$out .= '		promotion: false,'."\n";
				$out .= '		license_key: "gpl",'."\n";
				$out .= '		readonly: '.($this->readonly ? 'true' : 'false').','."\n";
				$out .= '		height: '.((int) $this->height + 80).','."\n";
				if ($this->width) {
					$out .= '		width: "'.dol_escape_js($this->width).'",'."\n";
				}
				$out .= '		directionality: "'.dol_escape_js(strtolower($langs->trans("DIRECTION")) === 'rtl' ? 'rtl' : 'ltr').'",'."\n";
				$out .= '		forced_root_block: false,'."\n";		// equivalent of CKEDITOR.ENTER_BR
				$out .= '		convert_urls: false,'."\n";
				$out .= '		relative_urls: false,'."\n";
				$out .= '		entity_encoding: "raw",'."\n";
				$out .= '		protect: [/<\?[\s\S]*?\?>/g],'."\n";	// protect PHP tags like CKEditor did
				$out .= '		paste_data_images: true,'."\n";
				$fontlist = 'arial,tahoma,verdana,helvetica';
				if (getDolGlobalString('THEME_FONT_FAMILY')) {
					$fontlist = getDolGlobalString('THEME_FONT_FAMILY').', '.$fontlist;
				}

				$out .= '		content_style: \'p { margin: 6px 0 6px 0; line-height: 1.4em; } .mce-content-body { font-size: 0.94em; margin: 10px; font-family: '.$fontlist.' } \',';
				$out .= '		toolbar_mode: "sliding",'."\n";
				$out .= '		browser_spellcheck: '.(getDolGlobalString('CKEDITOR_NATIVE_SPELLCHECKER') ? 'true' : 'false').','."\n";
				$out .= '		language: (tinyLang && tinyLang.indexOf("en") === 0) ? "en" : tinyLang,'."\n";
				$out .= '		htmlEncodeOutput: '.dol_escape_js($htmlencode_force).','."\n";
				if ($pickerenabled === 'true') {
					$out .= '		file_picker_types: "file image media",'."\n";
					$out .= '		file_picker_callback: function (cb, value, meta) {'."\n";
					$out .= '			var url = (meta.filetype === "image") ? window.tinymceFilebrowserImageBrowseUrl : window.tinymceFilebrowserBrowseUrl;'."\n";
					$out .= '			if (!url) { return; }'."\n";
					$out .= '			var w = window.open(url, "tinymcePicker", "width=900,height=500");'."\n";
					$out .= '			window.dolTinymcePickerCb = function (picked) { if (picked) { cb(picked); } if (w) { w.close(); } };'."\n";
					$out .= '		},'."\n";
				}
				$out .= '		setup: function (editor) {'."\n";
				$out .= '			editor.on("init", function () {'."\n";
				$out .= '				console.log("tinymce '.dol_escape_js($this->htmlname).' instanceReady");'."\n";
				$out .= '			});'."\n";
				$out .= '		}'."\n";
				$out .= '	};'."\n";
				$out .= '	tinymce.init(tinyConf)'.$morejs.';'."\n";
				$out .= '});'."\n";
				$out .= '</script>'."\n";
			}
		}

		// Output editor ACE
		// Warning: ace.js and ext-statusbar.js must be loaded by the parent page.
		if (preg_match('/^ace/', $this->tool)) {
			$found = 1;
			$format = $option;

			$out .= "\n".'<!-- Output Ace editor '.dol_string_nohtmltag($this->htmlname).' -->'."\n";

			if ($titlecontent) {
				$out .= '<div class="aceeditorstatusbar" id="statusBar'.$this->htmlname.'">'.$titlecontent;
				$out .= ' &nbsp; - &nbsp; <span id="morelines" class="right classlink cursorpointer morelines'.$this->htmlname.'">'.dol_escape_htmltag($langs->trans("ShowMoreLines")).'</span> &nbsp; &nbsp; ';
				$out .= '</div>';
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
				$out .= 'jQuery(document).ready(function() {'."\n";
				$out .= '	var aceEditor = window.ace.edit("'.dol_escape_all($this->htmlname).'aceeditorid");
							aceEditor.moveCursorTo('.($this->posy + 1).','.$this->posx.');
							aceEditor.gotoLine('.($this->posy + 1).','.$this->posx.');
	    	    		   	var StatusBar = window.ace.require("ace/ext/statusbar").StatusBar;									// Init status bar. Need lib ext-statusbar
	        			   	var statusBar = new StatusBar(aceEditor, document.getElementById("statusBar'.dol_escape_all($this->htmlname).'"));	// Init status bar. Need lib ext-statusbar

							var oldNbOfLines = 0;
							jQuery(".morelines'.dol_escape_all($this->htmlname).'").click(function() {
	        	    				var aceEditorClicked = window.ace.edit("'.$this->htmlname.'aceeditorid");
									currentline = aceEditorClicked.getOption("maxLines");
									if (oldNbOfLines == 0)
									{
										oldNbOfLines = currentline;
									}
									console.log("We click on more lines, oldNbOfLines is "+oldNbOfLines+", we have currently "+currentline);
									if (currentline < 500)
									{
										aceEditorClicked.setOptions({ maxLines: 500 });
									}
									else
									{
										aceEditorClicked.setOptions({ maxLines: oldNbOfLines });
									}
							});
						})';
				$out .= '</script>'."\n";
			}

			$out .= '<pre id="'.$this->htmlname.'aceeditorid" style="'.($this->width ? 'width: '.$this->width.'px; ' : '');
			$out .= ($this->height ? ' height: '.$this->height.'px; ' : '');
			//$out.=" min-height: 100px;";
			$out .= '">';
			$out .= htmlspecialchars($this->content);
			$out .= '</pre>';
			$out .= '<input type="hidden" id="'.$this->htmlname.'_x" name="'.$this->htmlname.'_x">';
			$out .= '<input type="hidden" id="'.$this->htmlname.'_y" name="'.$this->htmlname.'_y">';
			$out .= '<textarea id="'.$this->htmlname.'" name="'.$this->htmlname.'" style="width:0px; height: 0px; display: none;">';
			$out .= htmlspecialchars($this->content);
			$out .= '</textarea>';

			$out .= '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
			$out .= 'var aceEditor = window.ace.edit("'.$this->htmlname.'aceeditorid");

					aceEditor.session.setMode("ace/mode/'.$format.'");
					aceEditor.setReadOnly('.($this->readonly ? 'true' : 'false').');
					aceEditor.setOptions({
						enableBasicAutocompletion: true, 	// the editor completes the statement when you hit Ctrl + Space. Need lib ext-language_tools.js
						enableLiveAutocompletion: false, 	// the editor completes the statement while you are typing. Need lib ext-language_tools.js
						//enableSnippets: true,				// ???
						showPrintMargin: false, 			// hides the vertical limiting strip
						minLines: 10,
						maxLines: '.(empty($this->height) ? '34' : (round(($this->height - 60) / 19))).',		// we remove 60 for the ace toolbar + bottom status line. 19 seems the height in px required for 1 line.
				    	fontSize: "110%" // ensures that the editor fits in the environment
					});

					// defines the style of the editor
					aceEditor.setTheme("ace/theme/chrome");

					// hides line numbers (widens the area occupied by error and warning messages)
					//aceEditor.renderer.setOption("showLineNumbers", false);
					// ensures proper autocomplete, validation and highlighting of JavaScript code
					//aceEditor.getSession().setMode("ace/mode/javascript_expression");'."\n";

			// If page is PAGE_CONTENT to edit HTML web page in web site module, we add some autocompletion
			if ($this->htmlname == 'PAGE_CONTENT') {
				$out .= '
						// Add custom function in the autocompletion
						var customCompleter = {
							getCompletions: function(editor, session, pos, prefix, callback) {
								var wordList = [
								{ caption: \'dol_escape_all\', value: \'dol_escape_all(string)\', meta: \'custom\' },
								{ caption: \'dol_escape_js\', value: \'dol_escape_js(string)\', meta: \'custom\' },
								{ caption: \'includeContainer\', value: \'includeContainer(alias_of_container_to_include)\', meta: \'custom\' },
								{ caption: \'redirectToContainer\', value: \'redirectToContainer(alias_of_container_to_redirect_to)\', meta: \'custom\' },
								{ caption: \'getImageFromHtmlContent\', value: \'getImageFromHtmlContent(websitepage->htmlcontent)\', meta: \'custom\' },
								];
								callback(null, wordList.map(function(word) {
									return {
										caption: word.caption,
										value: word.value,
										meta: word.meta
									};
								}));
							}
						};
	 					aceEditor.completers = [customCompleter];
					'."\n";
			}

			$out .= 'jQuery(document).ready(function() {';
			$out .= '	jQuery(".buttonforacesave").click(function() {
        					console.log("We click on button (with class .buttonforacesave) that must fill ace fields for component '.dol_escape_js($this->htmlname).'");
        					var aceEditor = window.ace.edit("'.dol_escape_js($this->htmlname).'aceeditorid");
							if (aceEditor) {
								var cursorPos = aceEditor.getCursorPosition();
								//console.log(cursorPos);
								if (cursorPos) {
									jQuery("#'.dol_escape_js($this->htmlname).'_x").val(cursorPos.column);
									jQuery("#'.dol_escape_js($this->htmlname).'_y").val(cursorPos.row);
								}
								//console.log(aceEditor.getSession().getValue());
								// Inject content of editor into the original HTML field.
								jQuery("#'.dol_escape_js($this->htmlname).'").val(aceEditor.getSession().getValue());
								/*if (jQuery("#'.dol_escape_js($this->htmlname).'").html().length > 0) return true;
								else return false;*/
								return true;
							} else {
								console.log("Failed to retrieve js object ACE from its name");
								return false;
							}
						});
					})';
			$out .= '</script>'."\n";
		}

		if (empty($found)) {
			$out .= 'Error, unknown value for tool '.$this->tool.' in DolEditor Create function.';
		}

		if ($noprint) {
			return $out;
		} else {
			print $out;
		}
	}
}
