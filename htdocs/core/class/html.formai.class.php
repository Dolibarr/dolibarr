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
	 *	Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Return Html code for AI instruction of message and autofill result
	 *
	 * @param	string		$function		Function ('textgenerationmail', 'textgenerationwebpage', ...)
	 * @param	string		$format			Format for output ('', 'html', ...)
	 * @param   string      $htmlContent    HTML name of WYSIWYG field
	 * @return 	string      				HTML code to ask AI instruction and autofill result
	 */
	public function getSectionForAIPrompt($function = 'textgeneration', $format = '', $htmlContent = 'message')
	{
		global $langs;

		$langs->load("other");

		$htmlContent = preg_replace('/[^a-z0-9_]/', '', $htmlContent);

		$out = '<div id="ai_input'.$htmlContent.'" class="ai_input'.$htmlContent.' hidden paddingtop paddingbottom">';
		$out .= '<input type="text" class="quatrevingtpercent" id="ai_instructions'.$htmlContent.'" name="instruction" placeholder="'.$langs->trans("EnterYourAIPromptHere").'..." />';
		$out .= '<input id="generate_button'.$htmlContent.'" type="button" class="button smallpaddingimp"  value="'.$langs->trans('Generate').'"/>';
		$out .= '<div id="ai_status_message'.$htmlContent.'" class="fieldrequired hideobject marginrightonly margintoponly">';
		$out .= '<i class="fa fa-spinner fa-spin fa-2x fa-fw valignmiddle marginrightonly"></i>'.$langs->trans("AIProcessingPleaseWait", getDolGlobalString('AI_API_SERVICE', 'chatgpt'));
		$out .= '</div>';

		if ($function == 'imagegeneration') {
			$out .= '<div id="ai_image_result" class="margintoponly"></div>'; // Div for displaying the generated image
		}

		$out .= "</div>\n";

		$out .= "<script type='text/javascript'>
			$(document).ready(function() {
				// for keydown
				$('#ai_instructions".$htmlContent."').keydown(function(event) {
					if (event.keyCode === 13) {
						event.preventDefault();
						$('#generate_button".$htmlContent."').click();
					}
				});

				$('#generate_button".$htmlContent."').click(function() {
					console.log('We click on generate_button".$htmlContent." ai button, so we make an ajax on url /ai/ajax/generate_content.php');

					var instructions = $('#ai_instructions".$htmlContent."').val();
					var timeoutfinished = 0;
					var apicallfinished = 0;

					$('#ai_status_message".$htmlContent."').show();
					$('.icon-container .loader').show();
					setTimeout(function() {
						timeoutfinished = 1;
						if (apicallfinished) {
							$('#ai_status_message".$htmlContent."').hide();
						}
					}, 2000);

					if ('".$function."' === 'imagegeneration') {
						// Handle image generation request
						$.ajax({
							url: '". DOL_URL_ROOT."/ai/ajax/generate_content.php?token=".currentToken()."',
							type: 'POST',
							contentType: 'application/json',
							data: JSON.stringify({
								'format': '".dol_escape_js($format)."',			/* the format for output */
								'function': '".dol_escape_js($function)."',		/* the AI feature to call */
								'instructions': instructions,					/* the prompt string */
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
								alert(error);
								console.error('error ajax', status, error);
								$('#ai_status_message').hide();
							}
						});
					} else {

						// set editor in readonly
						if (CKEDITOR.instances.".$htmlContent.") {
							CKEDITOR.instances.".$htmlContent.".setReadOnly(1);
						}

						$.ajax({
							url: '". DOL_URL_ROOT."/ai/ajax/generate_content.php?token=".currentToken()."',
							type: 'POST',
							contentType: 'application/json',
							data: JSON.stringify({
								'format': '".dol_escape_js($format)."',			/* the format for output */
								'function': '".dol_escape_js($function)."',		/* the AI feature to call */
								'instructions': instructions,					/* the prompt string */
							}),
							success: function(response) {
								console.log('Add response into field \'#".$htmlContent."\': '+response);

								jQuery('#".$htmlContent."').val(response);		// If #htmlcontent is a input name or textarea
								jQuery('#".$htmlContent."').html(response);		// If #htmlContent is a div
								//jQuery('#".$htmlContent."preview').val(response);

								if (CKEDITOR.instances) {
									var editorInstance = CKEDITOR.instances.".$htmlContent.";
									if (editorInstance) {
										editorInstance.setReadOnly(0);
										editorInstance.setData(response);
									}
									//var editorInstancepreview = CKEDITOR.instances.".$htmlContent."preview;
									//if (editorInstancepreview) {
									//	editorInstancepreview.setData(response);
									//}
								}

								// remove readonly
								$('#ai_instructions".$htmlContent."').val('');

								apicallfinished = 1;
								if (timeoutfinished) {
									$('#ai_status_message".$htmlContent."').hide();
								}
							},
							error: function(xhr, status, error) {
								alert(error);
								console.error('error ajax', status, error);
								$('#ai_status_message".$htmlContent."').hide();
							}

						});
					}
				});
			});
			</script>
			";
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

	/**
	 * Get list of substitution keys available for emails. This is used for tooltips help.
	 * This include the complete_substitutions_array.
	 *
	 * @param	string	$mode		'formai', 'formaiwithlines', 'formaiforlines', 'ai', ...
	 * @param	?Object	$object		Object if applicable
	 * @return	array<string,string>               Array of substitution values for emails.
	 */
	public static function getAvailableSubstitKey($mode = 'formai', $object = null)
	{
		global $langs;

		$tmparray = array();
		if ($mode == 'formai' || $mode == 'formaiwithlines' || $mode == 'formaiforlines') {
			$parameters = array('mode' => $mode);
			$tmparray = getCommonSubstitutionArray($langs, 2, null, $object); // Note: On email templated edition, this is null because it is related to all type of objects
			complete_substitutions_array($tmparray, $langs, null, $parameters);

			if ($mode == 'formwithlines') {
				$tmparray['__LINES__'] = '__LINES__'; // Will be set by the get_form function
			}
			if ($mode == 'formforlines') {
				$tmparray['__QUANTITY__'] = '__QUANTITY__'; // Will be set by the get_form function
			}
		}

		if ($mode == 'emailing') {
			$parameters = array('mode' => $mode);
			$tmparray = getCommonSubstitutionArray($langs, 2, array('object', 'objectamount'), $object); // Note: On email templated edition, this is null because it is related to all type of objects
			complete_substitutions_array($tmparray, $langs, null, $parameters);

			// For mass emailing, we have different keys specific to the data into tagerts list
			$tmparray['__ID__'] = 'IdRecord';
			$tmparray['__THIRDPARTY_CUSTOMER_CODE__'] = 'CustomerCode';
			$tmparray['__EMAIL__'] = 'EMailRecipient';
			$tmparray['__LASTNAME__'] = 'Lastname';
			$tmparray['__FIRSTNAME__'] = 'Firstname';
			$tmparray['__MAILTOEMAIL__'] = 'TagMailtoEmail';
			$tmparray['__OTHER1__'] = 'Other1';
			$tmparray['__OTHER2__'] = 'Other2';
			$tmparray['__OTHER3__'] = 'Other3';
			$tmparray['__OTHER4__'] = 'Other4';
			$tmparray['__OTHER5__'] = 'Other5';
			$tmparray['__CHECK_READ__'] = $langs->trans('TagCheckMail');
			$tmparray['__UNSUBSCRIBE__'] = $langs->trans('TagUnsubscribe');
			$tmparray['__UNSUBSCRIBE_URL__'] = $langs->trans('TagUnsubscribe').' (URL)';

			$onlinepaymentenabled = 0;
			if (isModEnabled('paypal')) {
				$onlinepaymentenabled++;
			}
			if (isModEnabled('paybox')) {
				$onlinepaymentenabled++;
			}
			if (isModEnabled('stripe')) {
				$onlinepaymentenabled++;
			}
			if ($onlinepaymentenabled && getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
				$tmparray['__SECUREKEYPAYMENT__'] = getDolGlobalString('PAYMENT_SECURITY_TOKEN');
				if (getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
					if (isModEnabled('member')) {
						$tmparray['__SECUREKEYPAYMENT_MEMBER__'] = 'SecureKeyPAYMENTUniquePerMember';
					}
					if (isModEnabled('don')) {
						$tmparray['__SECUREKEYPAYMENT_DONATION__'] = 'SecureKeyPAYMENTUniquePerDonation';
					}
					if (isModEnabled('invoice')) {
						$tmparray['__SECUREKEYPAYMENT_INVOICE__'] = 'SecureKeyPAYMENTUniquePerInvoice';
					}
					if (isModEnabled('order')) {
						$tmparray['__SECUREKEYPAYMENT_ORDER__'] = 'SecureKeyPAYMENTUniquePerOrder';
					}
					if (isModEnabled('contract')) {
						$tmparray['__SECUREKEYPAYMENT_CONTRACTLINE__'] = 'SecureKeyPAYMENTUniquePerContractLine';
					}

					//Online payment link
					if (isModEnabled('member')) {
						$tmparray['__ONLINEPAYMENTLINK_MEMBER__'] = 'OnlinePaymentLinkUniquePerMember';
					}
					if (isModEnabled('don')) {
						$tmparray['__ONLINEPAYMENTLINK_DONATION__'] = 'OnlinePaymentLinkUniquePerDonation';
					}
					if (isModEnabled('invoice')) {
						$tmparray['__ONLINEPAYMENTLINK_INVOICE__'] = 'OnlinePaymentLinkUniquePerInvoice';
					}
					if (isModEnabled('order')) {
						$tmparray['__ONLINEPAYMENTLINK_ORDER__'] = 'OnlinePaymentLinkUniquePerOrder';
					}
					if (isModEnabled('contract')) {
						$tmparray['__ONLINEPAYMENTLINK_CONTRACTLINE__'] = 'OnlinePaymentLinkUniquePerContractLine';
					}
				}
			} else {
				/* No need to show into tooltip help, option is not enabled
				$vars['__SECUREKEYPAYMENT__']='';
				$vars['__SECUREKEYPAYMENT_MEMBER__']='';
				$vars['__SECUREKEYPAYMENT_INVOICE__']='';
				$vars['__SECUREKEYPAYMENT_ORDER__']='';
				$vars['__SECUREKEYPAYMENT_CONTRACTLINE__']='';
				*/
			}
			if (getDolGlobalString('MEMBER_ENABLE_PUBLIC')) {
				$tmparray['__PUBLICLINK_NEWMEMBERFORM__'] = 'BlankSubscriptionForm';
			}
		}

		foreach ($tmparray as $key => $val) {
			if (empty($val)) {
				$tmparray[$key] = $key;
			}
		}

		return $tmparray;
	}
}
