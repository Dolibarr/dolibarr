<?php
/* Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015-2017	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015-2017	Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2022		Charlene Benke			<charlene@patas-monkey.com>
 * Copyright (C) 2023		Anthony Berton			<anthony.berton@bb2a.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 *
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
 *       \file       htdocs/core/class/html.formai.class.php
 *       \ingroup    core
 *       \brief      Fichier de la class permettant la generation du formulaire html d'envoi de mail unitaire
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/**
 *      Class permettant la generation du formulaire html d'envoi de mail unitaire
 *      Usage: $formail = new FormAI($db)
 *             $formai->proprietes=1 ou chaine ou tableau de valeurs
 *             $formai->show_form() affiche le formulaire
 */
class FormAI extends Form
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string					Use case string to a button "Fill with layout" for this use case. Example 'wesitepage', 'emailing', 'email', ...
	 */
	public $withlayout;

	/**
	 * @var string	'text' or 'html' to add a button "Fill with AI generation"
	 */
	public $withaiprompt;

	/**
	 * @var array<string,string>
	 */
	public $substit = array();

	/**
	 * @var array<int,array<string,string>>
	 */
	public $substit_lines = array();

	/**
	 * @var array{}|array{models:string,langsmodels?:string,fileinit?:string[],returnurl:string}
	 */
	public $param = array();

	/**
	 * @var int<-1,1> -1 suggests the checkbox 'one email per recipient' not checked, 0 = no suggestion, 1 = suggest and checked
	 */
	public $withoptiononeemailperrecipient;

	/**
	 * @var bool
	 */
	public $aicallfunctioncalled = false;

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Return Html code for AI instructions of message and autofill result.
	 *
	 * @param	string		$function			Function/variant for text generation ('textgenerationemail', 'textgenerationwebpage', ...)
	 * @param	string		$format				Format for output ('', 'html', ...)
	 * @param   string      $htmlContent    	HTML name of WYSIWYG field
	 * @param	string		$onlyenhancements	Show only this enhancement features (show all if '')
	 * @param	string		$aiprompt			Ai prompt for textgenerationextrafield function
	 * @return 	string      					HTML code to ask AI instructions and autofill result
	 */
	public function getSectionForAIEnhancement($function = 'textgeneration', $format = '', $htmlContent = 'message', $onlyenhancements = '', $aiprompt = "")
	{
		global $langs, $form;
		require_once DOL_DOCUMENT_ROOT."/ai/lib/ai.lib.php";
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
		$formadmin = new FormAdmin($this->db);

		if (!is_object($form)) {
			$form = new Form($this->db);
		}

		$langs->load("other");

		$messageaiwait = '<i class="fa fa-spinner fa-spin fa-2x fa-fw valignmiddle marginrightonly"></i>'.$langs->trans("AIProcessingPleaseWait", getDolGlobalString('AI_API_SERVICE', 'chatgpt'));

		$htmlContent = preg_replace('/[^a-z0-9_]/', '', $htmlContent);

		$out = '';
		if (empty($onlyenhancements) || in_array($onlyenhancements, array('textgenerationemail', 'textgenerationwebpage'))) {
			$out .= '<div id="ai_textgeneration'.$htmlContent.'" class="ai_textgeneration'.$htmlContent.' paddingtop paddingbottom ai_feature">';
			//$out .= '<span>'.$langs->trans("FillMessageWithAIContent").'</span>';
			$out .= '<textarea class="centpercent textarea-ai_feature" data-functionai="textgeneration" id="ai_instructions'.$htmlContent.'" name="instruction" placeholder="'.$langs->trans("EnterYourAIPromptHere").'..." /></textarea>';
			$out .= '<input id="generate_button'.$htmlContent.'" type="button" class="button smallpaddingimp" disabled data-functionai="'.$function.'" value="'.$langs->trans('Generate').'"/>';
			$out .= '</div>';
		}

		if (empty($onlyenhancements) || in_array($onlyenhancements, array('texttranslation'))) {
			$out .= ($out ? '<br>' : '');
			$out .= '<div id="ai_translation'.$htmlContent.'" class="ai_translation'.$htmlContent.' paddingtop paddingbottom ai_feature">';
			$out .= img_picto('', 'language', 'class="pictofixedwidth paddingrightonly"');
			$out .= $formadmin->select_language("", "ai_translation".$htmlContent."_select", 0, array(), $langs->trans("TranslateByAI").'...', 0, 0, 'minwidth250 ai_translation'.$htmlContent.'_select');
			$out .= '</div>';
		}

		if (empty($onlyenhancements) || in_array($onlyenhancements, array('textsummarize'))) {
			$summarizearray = getListForAISummarize();
			$out .= ($out ? '<br>' : '');
			$out .= '<div id="ai_summarize'.$htmlContent.'" class="ai_summarize'.$htmlContent.' paddingtop paddingbottom ai_feature">';
			$out .= img_picto('', 'edit', 'class="pictofixedwidth paddingrightonly"');
			$out .= $form->selectarray("ai_summarize".$htmlContent."_select", $summarizearray, 0, $langs->trans("SummarizeByAI").'...', 0, 0, 'minwidth250 ai_summarize'.$htmlContent.'_select', 1);
			$out .= '</div>';
		}

		if (empty($onlyenhancements) || in_array($onlyenhancements, array('textrephrase'))) {
			$stylearray = getListForAIRephraseStyle();
			$out .= ($out ? '<br>' : '');
			$out .= '<div id="ai_rephraser'.$htmlContent.'" class="ai_rephraser'.$htmlContent.' paddingtop paddingbottom ai_feature">';
			$out .= img_picto('', 'edit', 'class="pictofixedwidth paddingrightonly"');
			$out .= $form->selectarray("ai_rephraser".$htmlContent."_select", $stylearray, 0, $langs->trans("RephraserByAI").'...', 0, 0, 'minwidth250 ai_rephraser'.$htmlContent.'_select', 1);
			$out .= '</div>';
		}

		if (in_array($onlyenhancements, array('textgenerationextrafield'))) {
			$out .= '<div id="ai_textgenerationextrafield'.$htmlContent.'" class="ai_textgenerationextrafield'.$htmlContent.' paddingtop paddingbottom ai_feature">';
			$out .= '<input id="input_ai_textgenerationextrafield'.$htmlContent.'" type="hidden" class="button smallpaddingimp" data-functionai="textgenerationextrafield" value="'.$aiprompt.'"/>';
			$out .= '</div>';
		}

		$out = '<!-- getSectionForAIEnhancement -->'.$out;
		$out = '<div id="ai_dropdown'.$htmlContent.'" class="dropdown-menu ai_dropdown ai_dropdown'.$htmlContent.' paddingtop paddingbottom">'.$out;

		$out .= '<div id="ai_status_message'.$htmlContent.'" class="fieldrequired hideobject marginrightonly margintoponly">';
		$out .= $messageaiwait;
		$out .= '</div>';

		if ($function == 'imagegeneration') {
			$out .= '<div id="ai_image_result" class="margintoponly"></div>'; // Div for displaying the generated image
		}

		$out .= "</div>\n";
		$out .= "<script type='text/javascript'>
			$(document).ready(function() {
				$('#ai_translation".$htmlContent."_select').data('functionai', 'texttranslation')
				$('#ai_summarize".$htmlContent."_select').data('functionai', 'textsummarize')
				$('#ai_rephraser".$htmlContent."_select').data('functionai', 'textrephraser')

				$('#ai_instructions".$htmlContent."').keyup(function(){
					console.log('We type a key up on #ai_instructions".$htmlContent."');
					if ($(this).val() != '') {
						$('#generate_button".$htmlContent."').prop('disabled', false);
					} else {
						$('#generate_button".$htmlContent."').prop('disabled', true);
					}
				});

				// for keydown
				$('#ai_instructions".$htmlContent."').keydown(function(event) {
					if (event.keyCode === 13 && $(this).val() != '') {
						console.log('We type enter on #ai_instructions".$htmlContent."');
						event.preventDefault();
						$('#generate_button".$htmlContent."').click();
					}
				});

				$('#generate_button".$htmlContent."').click(function() {
					console.log('We click on #generate_button".$htmlContent."');
					prepareCallAIGenerator($(this));
				});

				$('#ai_translation".$htmlContent."_select').on('change', function() {
					console.log('We change #ai_translation".$htmlContent."_select with lang '+$(this).val());
					if ($(this).val() != null && $(this).val() != '' && $(this).val() != '-1') {
						prepareCallAIGenerator($(this));
					}
				});

				$('#ai_summarize".$htmlContent."_select').on('change', function() {
					console.log('We change #ai_summarize".$htmlContent."_select with lang '+$(this).val());
					if ($(this).val() != null && $(this).val() != '' && $(this).val() != '-1') {
						prepareCallAIGenerator($(this));
					}
				});

				$('#ai_rephraser".$htmlContent."_select').on('change', function() {
					console.log('We change #ai_summarize".$htmlContent."_select with lang '+$(this).val());
					if ($(this).val() != null && $(this).val() != '' && $(this).val() != '-1') {
						prepareCallAIGenerator($(this));
					}
				});
				$('#linkforaiprompt".$function."').on('click', function() {
					//Get value aiprompt + prepare ai generator
					elementforprompt = $('#input_ai_textgenerationextrafield".$htmlContent."');
					aiprompt = elementforprompt.val();
					if (aiprompt != null && aiprompt != '' && aiprompt != '-1'){
						prepareCallAIGenerator(elementforprompt);
					}
				});

				function prepareCallAIGenerator(element) {
					console.log('We prepare ajax call to AI to url /ai/ajax/generate_content.php function=".dol_escape_js($function)." format=".dol_escape_js($format)."');

					var userprompt = $('#ai_instructions".$htmlContent."').val();
					var timeoutfinished = 0;
					var apicallfinished = 0;

					instructions = '';
					htmlname = '".dol_escape_js($htmlContent)."';
					format = '".dol_escape_js($format)."';
					functionai = $(element).data('functionai');		/* element is the html element we have manipulated in the ai tool */
					texttomodify = '';

					console.log('htmlname='+htmlname+' functionai='+functionai);
					if ($('#'+htmlname).is('div')) {
						texttomodify = $('#'+htmlname).html();	/* for div */
					} else {
						texttomodify = $('#'+htmlname).val();	/* for input or textarea */
					}
					if (functionai == 'texttranslation') {
						/*
						if (CKEDITOR.instances) {
							editorInstance = CKEDITOR.instances[htmlname];
							if (editorInstance) {
								texttomodify = editorInstance.getData();
							}
						}
						*/
						if (!texttomodify) {
							instructions = '';
						} else {
							lang = $('#ai_translation'+htmlname+'_select').val();
							instructions = 'Translate only the following text to ' + lang + ': ' + texttomodify;
						}
					} else if (functionai == 'textsummarize') {
						width = $('#ai_summarize'+htmlname+'_select').val();
						arr = width.split('_');
						width = arr[0];
						unit = arr[1];
						if (width == undefined || unit == undefined){
							console.log('Bad value so we choose 20 words')
							width = '50';
							unit = 'w';
						}
						switch(unit){
							case 'w':
								unit = 'words';
								break;
							case 'p':
								unit = 'paragraphs';
								break;
							case 'pc':
								unit = 'percent';
								break;
							default:
								console.log('unit not found so we choose words');
								unit = 'words';
								break;
						}
						instructions = 'Summarize the following text '+ (unit == 'percent' ? 'by ' : 'in') + width + ' ' + unit + ': ' + texttomodify;
					} else if (functionai == 'textrephraser') {
						style = $('#ai_rephraser'+htmlname+'_select').val();
						instructions = 'Rephrase the following text in a '+style+' style: ' + texttomodify;
					} else if (functionai == 'textgenerationextrafield'){
						instructions = $(element).val();
					} else {
						instructions = userprompt;
					}

					/* Show message API running */
					$('#ai_status_message".$htmlContent."').show();
					$('#ai_status_message".$htmlContent."').html('".dol_escape_js($messageaiwait)."');
					$('.icon-container .loader').show();

					setTimeout(function() {
						timeoutfinished = 1;
						$('#ai_status_message".$htmlContent."').hide();
					}, 30000);

					console.log('Instruction forged by javascript = '+instructions);

					callAIGenerator(functionai, instructions, format, htmlname);
				}

				CKEDITOR.on( 'instanceReady', function(e) {
					if (CKEDITOR.instances) {
						var htmlname = '".$htmlContent."';
						/* if a ckeditor handler exist for this div, we add a handler to have the main html component updated */
						console.log('Add handler on CKEDITOR.instances[".$htmlContent."]');
						if (CKEDITOR.instances[htmlname] != undefined) {
							CKEDITOR.instances[htmlname].on('change', function() {
								data = CKEDITOR.instances[htmlname].getData();
								$('#'+htmlname).val(data);	/* for input or textarea */
								$('#'+htmlname).html(data);	/* for div */
							})
						}
					}
				})
			});
			</script>
			";
		return $out;
	}

	/**
	 * Return javascript code for call to AI function callAIGenerator()
	 *
	 * @return 	string      				HTML code to ask AI instructions and autofill result
	 */
	public function getAjaxAICallFunction()
	{
		$out = "";
		if ($this->aicallfunctioncalled) {
			return $out;
		}

		$out .= "
		<script>
		function callAIGenerator(aifunction, instructions, format, htmlname){
			if (aifunction === 'imagegeneration') {
				// Handle image generation request
				$.ajax({
					url: '". DOL_URL_ROOT."/ai/ajax/generate_content.php?token=".currentToken()."',
					type: 'POST',
					contentType: 'application/json',
					data: JSON.stringify({
						'format': format,					/* the format for output */
						'function': aifunction,				/* the AI feature to call */
						'instructions': instructions,		/* the prompt string */
					}),
					success: function(response) {
						console.log('Received image URL: '+response);

						// make substitutions
						let substit = ". json_encode($this->substit).";
						for (let key in substit) {
							if (substit.hasOwnProperty(key)) {
								// Replace the placeholder with its corresponding value
								response = response.replace(key, substit[key]);
							}
						}

						// Assuming response is the URL of the generated image
						var imageUrl = response;
						$('#ai_image_result').html('<img src=\"' + imageUrl + '\" alt=\"Generated Image\" />');

						// Clear the input field
						$('#ai_instructions').val('');

						apicallfinished = 1;
						if (timeoutfinished) {
							$('#ai_status_message').hide();
						}
					},
					error: function(xhr, status, error) {
						/* alert(error); */
						console.log('error ajax', status, error);
						/*$('#ai_status_message'+htmlname).hide();*/
						$('#ai_status_message'+htmlname).val(error);
						$('#ai_status_message'+htmlname).html(error);
					}
				});
			} else {

				// set editor in readonly
				if (CKEDITOR.instances[htmlname]) {
					CKEDITOR.instances[htmlname].setReadOnly(1);
				}

				console.log('Call generate_content.php');
				$.ajax({
					url: '". DOL_URL_ROOT."/ai/ajax/generate_content.php?token=".currentToken()."',
					type: 'POST',
					contentType: 'application/json',
					data: JSON.stringify({
						'format': format,			/* the format for output */
						'function': aifunction,		/* the AI feature to call */
						'instructions': instructions,					/* the prompt string */
					}),
					success: function(response) {
						console.log('Add response into field \'#'+htmlname+'\': '+response);

						jQuery('#'+htmlname).val(response);		// If #htmlcontent is a input name or textarea
						jQuery('#'+htmlname).html(response).trigger('change');	// If #htmlContent is a div and trigger event change for extrafield update
						//jQuery('#'+htmlname+'preview').val(response);

						if (CKEDITOR.instances) {
							var editorInstance = CKEDITOR.instances[htmlname];
							if (editorInstance) {
								editorInstance.setReadOnly(0);
								editorInstance.setData(response);
							}
							//var editorInstancepreview = CKEDITOR.instances[htmlname+'preview'];
							//if (editorInstancepreview) {
							//	editorInstancepreview.setData(response);
							//}
						}

						// Remove all value from Ai Section select
						$('#ai_instructions'+htmlname).val('');
						$('#ai_translation'+htmlname+'_select').val('-1');
						$('#ai_translation'+htmlname+'_select').trigger('change');
						$('#ai_summarize'+htmlname+'_select').val('-1');
						$('#ai_summarize'+htmlname+'_select').trigger('change');
						$('#ai_rephraser'+htmlname+'_select').val('-1');
						$('#ai_rephraser'+htmlname+'_select').trigger('change');
						$('#ai_status_message'+htmlname).hide();
						$('#ai_dropdown'+htmlname).hide();
					},
					error: function(xhr, status, error) {
						/* alert(error); */
						console.log('error ajax ', status, error);
						/* $('#ai_status_message'+htmlname).hide(); */
						if (xhr.responseText) {
							$('#ai_status_message'+htmlname).val(xhr.responseText);
							$('#ai_status_message'+htmlname).html(xhr.responseText);
						} else {
							$('#ai_status_message'+htmlname).val(error);
							$('#ai_status_message'+htmlname).html(error);
						}
					}

				});
			}
		}
		</script>";
		$this->aicallfunctioncalled = true;
		return $out;
	}

	/**
	 * Set ->substit (and ->substit_line) array from object. This is call when suggesting the email template into forms before sending email.
	 *
	 * @param	CommonObject	$object		   Object to use
	 * @param   Translate  		$outputlangs   Object lang
	 * @return	void
	 * @see getCommonSubstitutionArray()
	 */
	public function setSubstitFromObject($object, $outputlangs)
	{
		global $extrafields;

		$parameters = array();
		$tmparray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
		complete_substitutions_array($tmparray, $outputlangs, null, $parameters);

		$this->substit = $tmparray;

		// Fill substit_lines with each object lines content
		if (is_array($object->lines)) {
			foreach ($object->lines as $line) {
				$substit_line = array(
					'__PRODUCT_REF__' => isset($line->product_ref) ? $line->product_ref : '',
					'__PRODUCT_LABEL__' => isset($line->product_label) ? $line->product_label : '',
					'__PRODUCT_DESCRIPTION__' => isset($line->product_desc) ? $line->product_desc : '',
					'__LABEL__' => isset($line->label) ? $line->label : '',
					'__DESCRIPTION__' => isset($line->desc) ? $line->desc : '',
					'__DATE_START_YMD__' => dol_print_date($line->date_start, 'day', false, $outputlangs),
					'__DATE_END_YMD__' => dol_print_date($line->date_end, 'day', false, $outputlangs),
					'__QUANTITY__' => $line->qty,
					'__SUBPRICE__' => price($line->subprice),
					'__AMOUNT__' => price($line->total_ttc),
					'__AMOUNT_EXCL_TAX__' => price($line->total_ht)
				);

				// Create dynamic tags for __PRODUCT_EXTRAFIELD_FIELD__
				if (!empty($line->fk_product)) {
					if (!is_object($extrafields)) {
						$extrafields = new ExtraFields($this->db);
					}
					$product = new Product($this->db);
					$product->fetch($line->fk_product);
					$product->fetch_optionals();

					$extrafields->fetch_name_optionals_label($product->table_element, true);

					if (!empty($extrafields->attributes[$product->table_element]['label']) && is_array($extrafields->attributes[$product->table_element]['label']) && count($extrafields->attributes[$product->table_element]['label']) > 0) {
						foreach ($extrafields->attributes[$product->table_element]['label'] as $key => $label) {
							$substit_line['__PRODUCT_EXTRAFIELD_'.strtoupper($key).'__'] = isset($product->array_options['options_'.$key]) ? $product->array_options['options_'.$key] : '';
						}
					}
				}

				$this->substit_lines[$line->id] = $substit_line;	// @phan-suppress-current-line PhanTypeMismatchProperty
			}
		}
	}
}
