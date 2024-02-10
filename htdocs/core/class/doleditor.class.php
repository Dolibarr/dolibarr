<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Gaëtan MAISON <gm@ilad.org>
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
 *		Usage: $doleditor=new DolEditor('body',$message,320,'toolbar_mailing');
 *		       $doleditor->Create();
 */
class DolEditor
{
	public $tool; // Store the selected tool

	// If using fckeditor
	public $editor;

	// If not using fckeditor
	public $content;
	public $htmlname;
	public $toolbarname;
	public $toolbarstartexpanded;
	public $rows;
	public $cols;
	public $height;
	public $width;
	public $uselocalbrowser;
	public $readonly;
	public $posx;
	public $posy;


	/**
	 *  Create an object to build an HTML area to edit a large string content
	 *
	 *  @param 	string	$htmlname		        		HTML name of WYSIWIG field
	 *  @param 	string	$content		        		Content of WYSIWIG field
	 *  @param	int|string	$width						Width in pixel of edit area (auto by default)
	 *  @param 	int		$height			       		 	Height in pixel of edit area (200px by default)
	 *  @param 	string	$toolbarname	       		 	Name of bar set to use ('Full', 'dolibarr_notes[_encoded]', 'dolibarr_details[_encoded]'=the less featured, 'dolibarr_mailings[_encoded]', 'dolibarr_readonly').
	 *  @param  string	$toolbarlocation       			Deprecated. Not used
	 *  @param  boolean	$toolbarstartexpanded  			Bar is visible or not at start
	 *  @param	boolean|int		$uselocalbrowser		Enabled to add links to local object with local browser. If false, only external images can be added in content.
	 *  @param  boolean|string	$okforextendededitor    True=Allow usage of extended editor tool if qualified (like ckeditor). If 'textarea', force use of simple textarea. If 'ace', force use of Ace.
	 *                                                  Warning: If you use 'ace', don't forget to also include ace.js in page header. Also, the button "save" must have class="buttonforacesave".
	 *  @param  int		$rows                   		Size of rows for textarea tool
	 *  @param  string	$cols                   		Size of cols for textarea tool (textarea number of cols '70' or percent 'x%')
	 *  @param	int		$readonly						0=Read/Edit, 1=Read only
	 *  @param	array	$poscursor						Array for initial cursor position array('x'=>x, 'y'=>y)
	 */
	public function __construct($htmlname, $content, $width = '', $height = 200, $toolbarname = 'Basic', $toolbarlocation = 'In', $toolbarstartexpanded = false, $uselocalbrowser = 1, $okforextendededitor = true, $rows = 0, $cols = '', $readonly = 0, $poscursor = array())
	{
		global $conf;

		dol_syslog(get_class($this)."::DolEditor htmlname=".$htmlname." width=".$width." height=".$height." toolbarname=".$toolbarname);

		if (!$rows) {
			$rows = round($height / 20);
		}
		if (!$cols) {
			$cols = ($width ? round($width / 6) : 80);
		}
		$shorttoolbarname = preg_replace('/_encoded$/', '', $toolbarname);

		// Name of extended editor to use (FCKEDITOR_EDITORNAME can be 'ckeditor' or 'fckeditor')
		$defaulteditor = 'ckeditor';
		$this->tool = !getDolGlobalString('FCKEDITOR_EDITORNAME') ? $defaulteditor : $conf->global->FCKEDITOR_EDITORNAME;
		$this->uselocalbrowser = $uselocalbrowser;
		$this->readonly = $readonly;

		// Check if extended editor is ok. If not we force textarea
		if ((!isModEnabled('fckeditor') && $okforextendededitor != 'ace') || empty($okforextendededitor)) {
			$this->tool = 'textarea';
		}
		if ($okforextendededitor === 'ace') {
			$this->tool = 'ace';
		}
		//if ($conf->dol_use_jmobile) $this->tool = 'textarea';       // ckeditor and ace seems ok with mobile

		// Define some properties
		if (in_array($this->tool, array('textarea', 'ckeditor', 'ace'))) {
			if ($this->tool == 'ckeditor' && !dol_textishtml($content)) {	// We force content to be into HTML if we are using an advanced editor if content is not HTML.
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
	 *  @param	int		$noprint             1=Return HTML string instead of printing it to output
	 *  @param	string	$morejs		         Add more js. For example: ".on( \'saveSnapshot\', function(e) { alert(\'ee\'); });". Used by CKEditor only.
	 *  @param  boolean $disallowAnyContent  Disallow to use any content. true=restrict to a predefined list of allowed elements. Used by CKEditor only.
	 *  @param	string	$titlecontent		 Show title content before editor area. Used by ACE editor only.
	 *  @param	string	$option				 For ACE editor, set the source language ('html', 'php', 'javascript', ...)
	 *  @param	string	$moreparam			 Add extra tags to the textarea
	 *  @param	string	$morecss			 Add extra css to the textarea
	 *  @return	void|string
	 */
	public function Create($noprint = 0, $morejs = '', $disallowAnyContent = true, $titlecontent = '', $option = '', $moreparam = '', $morecss = '')
	{
		// phpcs:enable
		global $conf, $langs;

		$fullpage = false;
		if (isset($conf->global->FCKEDITOR_ALLOW_ANY_CONTENT)) {
			$disallowAnyContent = !getDolGlobalString('FCKEDITOR_ALLOW_ANY_CONTENT'); // Only predefined list of html tags are allowed or all
		}

		$found = 0;
		$out = '';

		if (in_array($this->tool, array('textarea', 'ckeditor'))) {
			$found = 1;
			//$out.= '<textarea id="'.$this->htmlname.'" name="'.$this->htmlname.'" '.($this->readonly?' disabled':'').' rows="'.$this->rows.'"'.(preg_match('/%/',$this->cols)?' style="margin-top: 5px; width: '.$this->cols.'"':' cols="'.$this->cols.'"').' class="flat">';
			// TODO We do not put the 'disabled' tag because on a read form, it change style with grey.
			//print $this->content;
			$out .= '<textarea id="'.$this->htmlname.'" name="'.$this->htmlname.'" rows="'.$this->rows.'"'.(preg_match('/%/', $this->cols) ? ' style="margin-top: 5px; width: '.$this->cols.'"' : ' cols="'.$this->cols.'"').' '.($moreparam ? $moreparam : '').' class="flat '.$morecss.'">';
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

				$out .= '<!-- Output ckeditor $disallowAnyContent='.dol_escape_htmltag($disallowAnyContent).' toolbarname='.dol_escape_htmltag($this->toolbarname).' -->'."\n";
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">
            			$(document).ready(function () {
							/* console.log("Run ckeditor"); */
                            /* if (CKEDITOR.loadFullCore) CKEDITOR.loadFullCore(); */
                            /* should be editor=CKEDITOR.replace but what if there is several editors ? */
                            tmpeditor = CKEDITOR.replace(\''.dol_escape_js($this->htmlname).'\',
            					{
            						/* property:xxx is same than CKEDITOR.config.property = xxx */
            						customConfig: ckeditorConfig,
									removePlugins: \''.dol_escape_js($pluginstodisable).'\',
									versionCheck: false,
            						readOnly: '.($this->readonly ? 'true' : 'false').',
                            		htmlEncodeOutput: '.dol_escape_js($htmlencode_force).',
            						allowedContent: '.($disallowAnyContent ? 'false' : 'true').',		/* Advanced Content Filter (ACF) is own when allowedContent is false */
            						extraAllowedContent: \'a[target];div{float,display}\',				/* Add the style float and display into div to default other allowed tags */
									disallowedContent: '.($disallowAnyContent ? '\'\'' : '\'\'').',		/* Tags that are not allowed */
            						fullPage: '.($fullpage ? 'true' : 'false').',						/* if true, the html, header and body tags are kept */
                            		toolbar: \''.dol_escape_js($this->toolbarname).'\',
            						toolbarStartupExpanded: '.($this->toolbarstartexpanded ? 'true' : 'false').',
            						width: '.($this->width ? '\''.dol_escape_js($this->width).'\'' : '\'\'').',
            						height: '.dol_escape_js($this->height).',
                                    skin: \''.dol_escape_js($skin).'\',
                                    '.$scaytautostartup.'
                                    language: \''.dol_escape_js($langs->defaultlang).'\',
                                    textDirection: \''.dol_escape_js($langs->trans("DIRECTION")).'\',
                                    on : {
                                                instanceReady : function(ev) {
													console.log("ckeditor instanceReady");
                                                    // Output paragraphs as <p>Text</p>.
                                                    this.dataProcessor.writer.setRules( \'p\', {
                                                        indent : false,
                                                        breakBeforeOpen : true,
                                                        breakAfterOpen : false,
                                                        breakBeforeClose : false,
                                                        breakAfterClose : true
                                                    });
                                                },
												/* This is to remove the tab Link on image popup. Does not work, so commented */
												/*
												dialogDefinition: function (event) {
										            var dialogName = event.data.name;
										            var dialogDefinition = event.data.definition;
										            if (dialogName == \'image\') {
										                dialogDefinition.removeContents(\'Link\');
										            }
										        }
												*/
										},
									disableNativeSpellChecker: '.(!getDolGlobalString('CKEDITOR_NATIVE_SPELLCHECKER') ? 'true' : 'false');

				if ($this->uselocalbrowser) {
					$out .= ','."\n";
					// To use filemanager with old fckeditor (GPL)
					// Note: ckeditorFilebrowserBrowseUrl and ckeditorFilebrowserImageBrowseUrl are defined in header by main.inc.php. They include url to browser with url of upload connector in parameter
					$out .= '    filebrowserBrowseUrl : ckeditorFilebrowserBrowseUrl,';
					$out .= '    filebrowserImageBrowseUrl : ckeditorFilebrowserImageBrowseUrl,';
					//$out.= '    filebrowserUploadUrl : \''.DOL_URL_ROOT.'/includes/fckeditor/editor/filemanagerdol/connectors/php/upload.php?Type=File\',';
					//$out.= '    filebrowserImageUploadUrl : \''.DOL_URL_ROOT.'/includes/fckeditor/editor/filemanagerdol/connectors/php/upload.php?Type=Image\',';
					$out .= "\n";
					// To use filemanager with ckfinder (Non free) and ckfinder directory is inside htdocs/includes
					/* $out.= '    filebrowserBrowseUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/ckfinder.html\',
							   filebrowserImageBrowseUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/ckfinder.html?Type=Images\',
							   filebrowserFlashBrowseUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/ckfinder.html?Type=Flash\',
							   filebrowserUploadUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files\',
							   filebrowserImageUploadUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images\',
							   filebrowserFlashUploadUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash\','."\n";
					*/
					$out .= '    filebrowserWindowWidth : \'900\',
                               filebrowserWindowHeight : \'500\',
                               filebrowserImageWindowWidth : \'900\',
                               filebrowserImageWindowHeight : \'500\'';
				}
				$out .= '	})'.$morejs;	// end CKEditor.replace
				// Show the CKEditor javascript object once loaded is ready 'For debug)
				//$out .= '; CKEDITOR.on(\'instanceReady\', function(ck) { ck.editor.removeMenuItem(\'maximize\'); ck.editor.removeMenuItem(\'Undo\'); ck.editor.removeMenuItem(\'undo\'); console.log(ck.editor); console.log(ck.editor.toolbar[0]); }); ';
				$out .= '});'."\n";	// end document.ready
				$out .= '</script>'."\n";
			}
		}

		// Output editor ACE
		// Warning: ace.js and ext-statusbar.js must be loaded by the parent page.
		if (preg_match('/^ace/', $this->tool)) {
			$found = 1;
			$format = $option;

			$out .= "\n".'<!-- Output Ace editor -->'."\n";

			if ($titlecontent) {
				$out .= '<div class="aceeditorstatusbar" id="statusBar'.$this->htmlname.'">'.$titlecontent;
				$out .= ' &nbsp; - &nbsp; <span id="morelines" class="right classlink cursorpointer morelines'.$this->htmlname.'">'.dol_escape_htmltag($langs->trans("ShowMoreLines")).'</span> &nbsp; &nbsp; ';
				$out .= '</div>';
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
				$out .= 'jQuery(document).ready(function() {'."\n";
				$out .= '	var aceEditor = window.ace.edit("'.$this->htmlname.'aceeditorid");
							aceEditor.moveCursorTo('.($this->posy+1).','.$this->posx.');
							aceEditor.gotoLine('.($this->posy+1).','.$this->posx.');
	    	    		   	var StatusBar = window.ace.require("ace/ext/statusbar").StatusBar;									// Init status bar. Need lib ext-statusbar
	        			   	var statusBar = new StatusBar(aceEditor, document.getElementById("statusBar'.$this->htmlname.'"));	// Init status bar. Need lib ext-statusbar

							var oldNbOfLines = 0;
							jQuery(".morelines'.$this->htmlname.'").click(function() {
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
					aceEditor.setOptions({
	   				   enableBasicAutocompletion: true, // the editor completes the statement when you hit Ctrl + Space. Need lib ext-language_tools.js
					   enableLiveAutocompletion: false, // the editor completes the statement while you are typing. Need lib ext-language_tools.js
					   showPrintMargin: false, // hides the vertical limiting strip
					   minLines: 10,
					   maxLines: '.(empty($this->height) ? '34' : (round($this->height / 10))).',
				       fontSize: "110%" // ensures that the editor fits in the environment
					});

					// defines the style of the editor
					aceEditor.setTheme("ace/theme/chrome");
					// hides line numbers (widens the area occupied by error and warning messages)
					//aceEditor.renderer.setOption("showLineNumbers", false);
					// ensures proper autocomplete, validation and highlighting of JavaScript code
					//aceEditor.getSession().setMode("ace/mode/javascript_expression");
					'."\n";

			$out .= 'jQuery(document).ready(function() {
						jQuery(".buttonforacesave").click(function() {
        					console.log("We click on savefile button for component '.dol_escape_js($this->htmlname).'");
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
