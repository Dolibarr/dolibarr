<?php
/* Copyright (C) 2013-2015 Jean-François FERRY     <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel     <christophe@altairis.fr>
 * Copyright (C) 2019      Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021      Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2021      Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *       \file       htdocs/core/class/html.formticket.class.php
 *       \ingroup    ticket
 *       \brief      File of class to generate the form for creating a new ticket.
 */
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formmail.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formprojet.class.php";

if (!class_exists('FormCompany')) {
	include DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
}

/**
 * Class to generate the form for creating a new ticket.
 * Usage: 	$formticket = new FormTicket($db)
 * 			$formticket->proprietes=1 ou chaine ou tableau de valeurs
 * 			$formticket->show_form() affiche le formulaire
 *
 * @package Ticket
 */
class FormTicket
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string	The track_id of the ticket. Used also for the $keytoavoidconflict to name session vars to upload files.
	 */
	public $track_id;

	/**
	 * @var int ID
	 */
	public $fk_user_create;

	public $message;
	public $topic_title;

	public $action;

	public $withtopic;
	public $withemail;
	/**
	 *
	 * @var int $withsubstit Show substitution array
	 */
	public $withsubstit;

	public $withfile;
	public $withfilereadonly;

	public $backtopage;

	public $ispublic; // To show information or not into public form

	public $withtitletopic;
	public $withtopicreadonly;
	public $withcompany; // affiche liste déroulante company
	public $withfromsocid;
	public $withfromcontactid;
	public $withnotifytiersatcreate;
	public $withusercreate; // Show name of creating user in form
	public $withcreatereadonly;

	public $withref; // Show ref field

	public $withcancel;

	public $type_code;
	public $category_code;
	public $severity_code;


	/**
	 *
	 * @var array $substit Substitutions
	 */
	public $substit = array();
	public $param = array();

	/**
	 * @var string Error code (or message)
	 */
	public $error;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->action = 'add';

		$this->withcompany = isModEnabled("societe");
		$this->withfromsocid = 0;
		$this->withfromcontactid = 0;
		//$this->withreadid=0;
		//$this->withtitletopic='';
		$this->withnotifytiersatcreate = 0;
		$this->withusercreate = 1;
		$this->withcreatereadonly = 1;
		$this->withemail = 0;
		$this->withref = 0;
		$this->withextrafields = 0; // Show extrafields or not
		//$this->withtopicreadonly=0;
	}

	/**
	 * Show the form to input ticket
	 *
	 * @param  	int	 			$withdolfichehead		With dol_get_fiche_head() and dol_get_fiche_end()
	 * @param	string			$mode					Mode ('create' or 'edit')
	 * @param	int				$public					1=If we show the form for the public interface
	 * @param	Contact|null	$with_contact			[=NULL] Contact to link to this ticket if exists
	 * @return 	void
	 */
	public function showForm($withdolfichehead = 0, $mode = 'edit', $public = 0, Contact $with_contact = null)
	{
		global $conf, $langs, $user, $hookmanager;

		// Load translation files required by the page
		$langs->loadLangs(array('other', 'mails', 'ticket'));

		$form = new Form($this->db);
		$formcompany = new FormCompany($this->db);
		$ticketstatic = new Ticket($this->db);

		$soc = new Societe($this->db);
		if (!empty($this->withfromsocid) && $this->withfromsocid > 0) {
			$soc->fetch($this->withfromsocid);
		}

		$ticketstat = new Ticket($this->db);

		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label($ticketstat->table_element);

		print "\n<!-- Begin form TICKET -->\n";

		if ($withdolfichehead) {
			print dol_get_fiche_head(null, 'card', '', 0, '');
		}

		print '<form method="POST" '.($withdolfichehead ? '' : 'style="margin-bottom: 30px;" ').'name="ticket" id="form_create_ticket" enctype="multipart/form-data" action="'.(!empty($this->param["returnurl"]) ? $this->param["returnurl"] : "").'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.$this->action.'">';
		foreach ($this->param as $key => $value) {
			print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
		}
		print '<input type="hidden" name="fk_user_create" value="'.$this->fk_user_create.'">';

		print '<table class="border centpercent">';

		if ($this->withref) {
			// Ref
			$defaultref = $ticketstat->getDefaultRef();
			print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td>';
			print '<input type="text" name="ref" value="'.dol_escape_htmltag(GETPOST("ref", 'alpha') ? GETPOST("ref", 'alpha') : $defaultref).'">';
			print '</td></tr>';
		}

		// TITLE
		$email = GETPOSTISSET('email') ? GETPOST('email', 'alphanohtml') : '';
		if ($this->withemail) {
			print '<tr><td class="titlefield"><label for="email"><span class="fieldrequired">'.$langs->trans("Email").'</span></label></td><td>';
			print '<input class="text minwidth200" id="email" name="email" value="'.$email.'" autofocus>';
			print '</td></tr>';

			if ($with_contact) {
				// contact search and result
				$html_contact_search  = '';
				$html_contact_search .= '<tr id="contact_search_line">';
				$html_contact_search .= '<td class="titlefield">';
				$html_contact_search .= '<label for="contact"><span class="fieldrequired">' . $langs->trans('Contact') . '</span></label>';
				$html_contact_search .= '<input type="hidden" id="contact_id" name="contact_id" value="" />';
				$html_contact_search .= '</td>';
				$html_contact_search .= '<td id="contact_search_result"></td>';
				$html_contact_search .= '</tr>';
				print $html_contact_search;
				// contact lastname
				$html_contact_lastname = '';
				$html_contact_lastname .= '<tr id="contact_lastname_line" class="contact_field"><td class="titlefield"><label for="contact_lastname"><span class="fieldrequired">' . $langs->trans('Lastname') . '</span></label></td><td>';
				$html_contact_lastname .= '<input type="text" id="contact_lastname" name="contact_lastname" value="' . dol_escape_htmltag(GETPOSTISSET('contact_lastname') ? GETPOST('contact_lastname', 'alphanohtml') : '') . '" />';
				$html_contact_lastname .= '</td></tr>';
				print $html_contact_lastname;
				// contact firstname
				$html_contact_firstname  = '';
				$html_contact_firstname .= '<tr id="contact_firstname_line" class="contact_field"><td class="titlefield"><label for="contact_firstname"><span class="fieldrequired">' . $langs->trans('Firstname') . '</span></label></td><td>';
				$html_contact_firstname .= '<input type="text" id="contact_firstname" name="contact_firstname" value="' . dol_escape_htmltag(GETPOSTISSET('contact_firstname') ? GETPOST('contact_firstname', 'alphanohtml') : '') . '" />';
				$html_contact_firstname .= '</td></tr>';
				print $html_contact_firstname;
				// company name
				$html_company_name  = '';
				$html_company_name .= '<tr id="contact_company_name_line" class="contact_field"><td><label for="company_name"><span>' . $langs->trans('Company') . '</span></label></td><td>';
				$html_company_name .= '<input type="text" id="company_name" name="company_name" value="' . dol_escape_htmltag(GETPOSTISSET('company_name') ? GETPOST('company_name', 'alphanohtml') : '') . '" />';
				$html_company_name .= '</td></tr>';
				print $html_company_name;
				// contact phone
				$html_contact_phone  = '';
				$html_contact_phone .= '<tr id="contact_phone_line" class="contact_field"><td><label for="contact_phone"><span>' . $langs->trans('Phone') . '</span></label></td><td>';
				$html_contact_phone .= '<input type="text" id="contact_phone" name="contact_phone" value="' . dol_escape_htmltag(GETPOSTISSET('contact_phone') ? GETPOST('contact_phone', 'alphanohtml') : '') . '" />';
				$html_contact_phone .= '</td></tr>';
				print $html_contact_phone;

				// search contact form email
				$langs->load('errors');
				print '<script type="text/javascript">
                    jQuery(document).ready(function() {
                        var contact = jQuery.parseJSON("'.dol_escape_js(json_encode($with_contact), 2).'");
                        jQuery("#contact_search_line").hide();
                        if (contact) {
                        	if (contact.id > 0) {
                        		jQuery("#contact_search_line").show();
                        		jQuery("#contact_id").val(contact.id);
								jQuery("#contact_search_result").html(contact.firstname+" "+contact.lastname);
								jQuery(".contact_field").hide();
                        	} else {
                        		jQuery(".contact_field").show();
                        	}
                        }

                    	jQuery("#email").change(function() {
                            jQuery("#contact_search_line").show();
                            jQuery("#contact_search_result").html("'.dol_escape_js($langs->trans('Select2SearchInProgress')).'");
                            jQuery("#contact_id").val("");
                            jQuery("#contact_lastname").val("");
                            jQuery("#contact_firstname").val("");
                            jQuery("#company_name").val("");
                            jQuery("#contact_phone").val("");

                            jQuery.getJSON(
                                "'.dol_escape_js(dol_buildpath('/public/ticket/ajax/ajax.php', 1)).'",
								{
									action: "getContacts",
									email: jQuery("#email").val()
								},
								function(response) {
									if (response.error) {
                                        jQuery("#contact_search_result").html("<span class=\"error\">"+response.error+"</span>");
									} else {
                                        var contact_list = response.contacts;
										if (contact_list.length == 1) {
                                            var contact = contact_list[0];
											jQuery("#contact_id").val(contact.id);
											jQuery("#contact_search_result").html(contact.firstname+" "+contact.lastname);
                                            jQuery(".contact_field").hide();
										} else if (contact_list.length <= 0) {
                                            jQuery("#contact_search_line").hide();
                                            jQuery(".contact_field").show();
										}
									}
								}
                            ).fail(function(jqxhr, textStatus, error) {
    							var error_msg = "'.dol_escape_js($langs->trans('ErrorAjaxRequestFailed')).'"+" ["+textStatus+"] : "+error;
                                jQuery("#contact_search_result").html("<span class=\"error\">"+error_msg+"</span>");
                            });
                        });
                    });
                    </script>';
			}
		}

		// If ticket created from another object
		$subelement = '';
		if (isset($this->param['origin']) && $this->param['originid'] > 0) {
			// Parse element/subelement (ex: project_task)
			$element = $subelement = $this->param['origin'];
			$regs = array();
			if (preg_match('/^([^_]+)_([^_]+)/i', $this->param['origin'], $regs)) {
				$element = $regs[1];
				$subelement = $regs[2];
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');
			$classname = ucfirst($subelement);
			$objectsrc = new $classname($this->db);
			$objectsrc->fetch(GETPOST('originid', 'int'));

			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines')) {
				$objectsrc->fetch_lines();
			}

			$objectsrc->fetch_thirdparty();
			$newclassname = $classname;
			print '<tr><td>'.$langs->trans($newclassname).'</td><td colspan="2"><input name="'.$subelement.'id" value="'.GETPOST('originid').'" type="hidden" />'.$objectsrc->getNomUrl(1).'</td></tr>';
		}

		// Type
		print '<tr><td class="titlefield"><span class="fieldrequired"><label for="selecttype_code">'.$langs->trans("TicketTypeRequest").'</span></label></td><td>';
		$this->selectTypesTickets((GETPOST('type_code', 'alpha') ? GETPOST('type_code', 'alpha') : $this->type_code), 'type_code', '', 2, 1, 0, 0, 'minwidth200');
		print '</td></tr>';

		// Group
		print '<tr><td><span class="fieldrequired"><label for="selectcategory_code">'.$langs->trans("TicketCategory").'</span></label></td><td>';
		$filter = '';
		if ($public) {
			$filter = 'public=1';
		}
		$this->selectGroupTickets((GETPOST('category_code') ? GETPOST('category_code') : $this->category_code), 'category_code', $filter, 2, 0, 0, 0, 'minwidth200');
		print '</td></tr>';

		// Severity
		print '<tr><td><span class=""><label for="selectseverity_code">'.$langs->trans("TicketSeverity").'</span></label></td><td>';
		$this->selectSeveritiesTickets((GETPOST('severity_code') ? GETPOST('severity_code') : $this->severity_code), 'severity_code', '', 2, 0);
		print '</td></tr>';

		// Subject
		if ($this->withtitletopic) {
			print '<tr><td><label for="subject"><span class="fieldrequired">'.$langs->trans("Subject").'</span></label></td><td>';

			// Réponse à un ticket : affichage du titre du thread en readonly
			if ($this->withtopicreadonly) {
				print $langs->trans('SubjectAnswerToTicket').' '.$this->topic_title;
				print '</td></tr>';
			} else {
				if (isset($this->withreadid) &&  $this->withreadid > 0) {
					$subject = $langs->trans('SubjectAnswerToTicket').' '.$this->withreadid.' : '.$this->topic_title.'';
				} else {
					$subject = GETPOST('subject', 'alpha');
				}
				print '<input class="text minwidth500" id="subject" name="subject" value="'.$subject.'" autofocus />';
				print '</td></tr>';
			}
		}

		if (!empty($conf->knowledgemanagement->enabled)) {
			// KM Articles
			print '<tr id="KWwithajax"></tr>';
			print '<!-- Script to manage change of ticket group -->
			<script>
			jQuery(document).ready(function() {
				function groupticketchange(){
					console.log("We called groupticketchange, so we try to load list KM linked to event");
					$("#KWwithajax").html("");
					idgroupticket = $("#selectcategory_code").val();

					console.log("We have selected id="+idgroupticket);

					if (idgroupticket != "") {
						$.ajax({ url: \''.DOL_URL_ROOT.'/core/ajax/fetchKnowledgeRecord.php\',
							 data: { action: \'getKnowledgeRecord\', idticketgroup: idgroupticket, token: \''.newToken().'\', lang:\''.$langs->defaultlang.'\'},
							 type: \'GET\',
							 success: function(response) {
								var urllist = \'\';
								console.log("We received response "+response);
								response = JSON.parse(response)
								for (key in response) {
									answer = response[key].answer;
									urllist += \'<li><a href="#" title="\'+response[key].title+\'" class="button_KMpopup" data-html="\'+answer+\'">\' +response[key].title+\'</a></li>\';
								}
								if (urllist != "") {
									$("#KWwithajax").html(\'<td>'.$langs->trans("KMFoundForTicketGroup").'</td><td><ul>\'+urllist+\'</ul></td>\');
									$("#KWwithajax").show();
									$(".button_KMpopup").on("click",function(){
										console.log("Open popup with jQuery(...).dialog() with KM article")
										var $dialog = $("<div></div>").html($(this).attr("data-html"))
											.dialog({
												autoOpen: false,
												modal: true,
												height: (window.innerHeight - 150),
												width: "80%",
												title: $(this).attr("title"),
											});
										$dialog.dialog("open");
										console.log($dialog);
									})
								}
							 },
							 error : function(output) {
								console.error("Error on Fetch of KM articles");
							 },
						});
					}
				};
				$("#selectcategory_code").on("change",function() { groupticketchange(); });
				if ($("#selectcategory_code").val() != "") {
					groupticketchange();
				}
			});
			</script>'."\n";
		}

		// MESSAGE
		$msg = GETPOSTISSET('message') ? GETPOST('message', 'restricthtml') : '';
		print '<tr><td><label for="message"><span class="fieldrequired">'.$langs->trans("Message").'</span></label></td><td>';

		// If public form, display more information
		$toolbarname = 'dolibarr_notes';
		if ($this->ispublic) {
			$toolbarname = 'dolibarr_details';
			print '<div class="warning">'.($conf->global->TICKET_PUBLIC_TEXT_HELP_MESSAGE ? $conf->global->TICKET_PUBLIC_TEXT_HELP_MESSAGE : $langs->trans('TicketPublicPleaseBeAccuratelyDescribe')).'</div>';
		}
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$uselocalbrowser = true;
		$doleditor = new DolEditor('message', $msg, '100%', 230, $toolbarname, 'In', true, $uselocalbrowser, getDolGlobalInt('FCKEDITOR_ENABLE_TICKET'), ROWS_8, '90%');
		$doleditor->Create();
		print '</td></tr>';

		if ($public && !empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA_TICKET)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			print '<tr><td class="titlefield"><label for="email"><span class="fieldrequired">'.$langs->trans("SecurityCode").'</span></label></td><td>';
			print '<span class="span-icon-security inline-block">';
			print '<input id="securitycode" placeholder="'.$langs->trans("SecurityCode").'" class="flat input-icon-security width125" type="text" maxlength="5" name="code" tabindex="3" />';
			print '</span>';
			print '<span class="nowrap inline-block">';
			print '<img class="inline-block valignmiddle" src="'.DOL_URL_ROOT.'/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
			print '<a class="inline-block valignmiddle" href="" tabindex="4" data-role="button">'.img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"').'</a>';
			print '</span>';
			print '</td></tr>';
		}

		// Categories
		if (isModEnabled('categorie')) {
			include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_TICKET, '', 'parent', 64, 0, 1);

			if (count($cate_arbo)) {
				// Categories
				print '<tr><td>'.$langs->trans("Categories").'</td><td colspan="3">';
				print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
				print "</td></tr>";
			}
		}

		// Attached files
		if (!empty($this->withfile)) {
			// Define list of attached files
			$listofpaths = array();
			$listofnames = array();
			$listofmimes = array();
			if (!empty($_SESSION["listofpaths"])) {
				$listofpaths = explode(';', $_SESSION["listofpaths"]);
			}

			if (!empty($_SESSION["listofnames"])) {
				$listofnames = explode(';', $_SESSION["listofnames"]);
			}

			if (!empty($_SESSION["listofmimes"])) {
				$listofmimes = explode(';', $_SESSION["listofmimes"]);
			}

			$out = '<tr>';
			$out .= '<td>'.$langs->trans("MailFile").'</td>';
			$out .= '<td>';
			// TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
			$out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">'."\n";
			$out .= '<script type="text/javascript">';
			$out .= 'jQuery(document).ready(function () {';
			$out .= '    jQuery(".removedfile").click(function() {';
			$out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
			$out .= '    });';
			$out .= '})';
			$out .= '</script>'."\n";
			if (count($listofpaths)) {
				foreach ($listofpaths as $key => $val) {
					$out .= '<div id="attachfile_'.$key.'">';
					$out .= img_mime($listofnames[$key]).' '.$listofnames[$key];
					if (!$this->withfilereadonly) {
						$out .= ' <input type="image" style="border: 0px;" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" value="'.($key + 1).'" class="removedfile" id="removedfile_'.$key.'" name="removedfile_'.$key.'" />';
					}
					$out .= '<br></div>';
				}
			} else {
				$out .= $langs->trans("NoAttachedFiles").'<br>';
			}
			if ($this->withfile == 2) { // Can add other files
				$maxfilesizearray = getMaxFileSizeArray();
				$maxmin = $maxfilesizearray['maxmin'];
				if ($maxmin > 0) {
					$out .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
				}
				$out .= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
				$out .= ' ';
				$out .= '<input type="submit" class="button smallpaddingimp reposition" id="addfile" name="addfile" value="'.$langs->trans("MailingAddFile").'" />';
			}
			$out .= "</td></tr>\n";

			print $out;
		}

		// User of creation
		if ($this->withusercreate > 0 && $this->fk_user_create) {
			print '<tr><td class="titlefield">'.$langs->trans("CreatedBy").'</td><td>';
			$langs->load("users");
			$fuser = new User($this->db);

			if ($this->withcreatereadonly) {
				if ($res = $fuser->fetch($this->fk_user_create)) {
					print $fuser->getNomUrl(1);
				}
			}
			print ' &nbsp; ';
			print "</td></tr>\n";
		}

		// Customer or supplier
		if ($this->withcompany) {
			// altairis: force company and contact id for external user
			if (empty($user->socid)) {
				// Company
				print '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>';
				$events = array();
				$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php', 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
				print img_picto('', 'company', 'class="paddingright"');
				print $form->select_company($this->withfromsocid, 'socid', '', 1, 1, '', $events, 0, 'minwidth200');
				print '</td></tr>';
				if (!empty($conf->use_javascript_ajax) && !empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
					$htmlname = 'socid';
					print '<script type="text/javascript">
                    $(document).ready(function () {
                        jQuery("#'.$htmlname.'").change(function () {
                            var obj = '.json_encode($events).';
                            $.each(obj, function(key,values) {
                                if (values.method.length) {
                                    runJsCodeForEvent'.$htmlname.'(values);
                                }
                            });
                        });

                        function runJsCodeForEvent'.$htmlname.'(obj) {
                            console.log("Run runJsCodeForEvent'.$htmlname.'");
                            var id = $("#'.$htmlname.'").val();
                            var method = obj.method;
                            var url = obj.url;
                            var htmlname = obj.htmlname;
                            var showempty = obj.showempty;
                            $.getJSON(url,
                                    {
                                        action: method,
                                        id: id,
                                        htmlname: htmlname,
                                        showempty: showempty
                                    },
                                    function(response) {
                                        $.each(obj.params, function(key,action) {
                                            if (key.length) {
                                                var num = response.num;
                                                if (num > 0) {
                                                    $("#" + key).removeAttr(action);
                                                } else {
                                                    $("#" + key).attr(action, action);
                                                }
                                            }
                                        });
                                        $("select#" + htmlname).html(response.value);
                                        if (response.num) {
                                            var selecthtml_str = response.value;
                                            var selecthtml_dom=$.parseHTML(selecthtml_str);
											if (typeof(selecthtml_dom[0][0]) !== \'undefined\') {
                                            	$("#inputautocomplete"+htmlname).val(selecthtml_dom[0][0].innerHTML);
											}
                                        } else {
                                            $("#inputautocomplete"+htmlname).val("");
                                        }
                                        $("select#" + htmlname).change();	/* Trigger event change */
                                    }
                            );
                        }
                    });
                    </script>';
				}

				// Contact and type
				print '<tr><td>'.$langs->trans("Contact").'</td><td>';
				// If no socid, set to -1 to avoid full contacts list
				$selectedCompany = ($this->withfromsocid > 0) ? $this->withfromsocid : -1;
				print img_picto('', 'contact', 'class="paddingright"');
				print $form->selectcontacts($selectedCompany, $this->withfromcontactid, 'contactid', 3, '', '', 0, 'minwidth200');
				print ' ';
				$formcompany->selectTypeContact($ticketstatic, '', 'type', 'external', '', 0, 'maginleftonly');
				print '</td></tr>';
			} else {
				print '<tr><td class="titlefield"><input type="hidden" name="socid" value="'.$user->socid.'"/></td>';
				print '<td><input type="hidden" name="contactid" value="'.$user->contact_id.'"/></td>';
				print '<td><input type="hidden" name="type" value="Z"/></td></tr>';
			}

			// Notify thirdparty at creation
			if (empty($this->ispublic)) {
				print '<tr><td><label for="notify_tiers_at_create">'.$langs->trans("TicketNotifyTiersAtCreation").'</label></td><td>';
				print '<input type="checkbox" id="notify_tiers_at_create" name="notify_tiers_at_create"'.($this->withnotifytiersatcreate ? ' checked="checked"' : '').'>';
				print '</td></tr>';
			}

			// User assigned
			print '<tr><td>';
			print $langs->trans("AssignedTo");
			print '</td><td>';
			print img_picto('', 'user', 'class="pictofixedwidth"');
			print $form->select_dolusers(GETPOST('fk_user_assign', 'int'), 'fk_user_assign', 1);
			print '</td>';
			print '</tr>';
		}

		if ($subelement != 'project') {
			if (!empty($conf->project->enabled) && !$this->ispublic) {
				$formproject = new FormProjets($this->db);
				print '<tr><td><label for="project"><span class="">'.$langs->trans("Project").'</span></label></td><td>';
				print img_picto('', 'project').$formproject->select_projects(-1, GETPOST('projectid', 'int'), 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
				print '</td></tr>';
			}
		}

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $ticketstat, $this->action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			print $ticketstat->showOptionals($extrafields, 'create');
		}

		print '</table>';

		if ($withdolfichehead) {
			print dol_get_fiche_end();
		}

		print '<br><br>';

		print $form->buttonsSaveCancel(((isset($this->withreadid) && $this->withreadid > 0) ? "SendResponse" : "CreateTicket"), ($this->withcancel ? "Cancel" : ""));

		/*
		print '<div class="center">';
		print '<input type="submit" class="button" name="add" value="'.$langs->trans(($this->withreadid > 0 ? "SendResponse" : "CreateTicket")).'" />';
		if ($this->withcancel) {
			print " &nbsp; &nbsp; &nbsp;";
			print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
		print '</div>';
		*/

		print '<input type="hidden" name="page_y">'."\n";

		print "</form>\n";
		print "<!-- End form TICKET -->\n";
	}

	/**
	 *      Return html list of tickets type
	 *
	 *      @param  string $selected    Id du type pre-selectionne
	 *      @param  string $htmlname    Nom de la zone select
	 *      @param  string $filtertype  To filter on field type in llx_c_ticket_type (array('code'=>xx,'label'=>zz))
	 *      @param  int    $format      0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
	 *      @param  int    $empty       1=peut etre vide, 0 sinon
	 *      @param  int    $noadmininfo 0=Add admin info, 1=Disable admin info
	 *      @param  int    $maxlength   Max length of label
	 *      @param	string	$morecss	More CSS
	 *      @return void
	 */
	public function selectTypesTickets($selected = '', $htmlname = 'tickettype', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0, $morecss = '')
	{
		global $langs, $user;

		$ticketstat = new Ticket($this->db);

		dol_syslog(get_class($this)."::select_types_tickets ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

		$filterarray = array();

		if ($filtertype != '' && $filtertype != '-1') {
			$filterarray = explode(',', $filtertype);
		}

		$ticketstat->loadCacheTypesTickets();

		print '<select id="select'.$htmlname.'" class="flat minwidth100'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'">';
		if ($empty) {
			print '<option value="">&nbsp;</option>';
		}

		if (is_array($ticketstat->cache_types_tickets) && count($ticketstat->cache_types_tickets)) {
			foreach ($ticketstat->cache_types_tickets as $id => $arraytypes) {
				// On passe si on a demande de filtrer sur des modes de paiments particuliers
				if (count($filterarray) && !in_array($arraytypes['type'], $filterarray)) {
					continue;
				}

				// We discard empty line if showempty is on because an empty line has already been output.
				if ($empty && empty($arraytypes['code'])) {
					continue;
				}

				if ($format == 0) {
					print '<option value="'.$id.'"';
				}

				if ($format == 1) {
					print '<option value="'.$arraytypes['code'].'"';
				}

				if ($format == 2) {
					print '<option value="'.$arraytypes['code'].'"';
				}

				if ($format == 3) {
					print '<option value="'.$id.'"';
				}

				// Si selected est text, on compare avec code, sinon avec id
				if (preg_match('/[a-z]/i', $selected) && $selected == $arraytypes['code']) {
					print ' selected="selected"';
				} elseif ($selected == $id) {
					print ' selected="selected"';
				} elseif ($arraytypes['use_default'] == "1" && !$selected && !$empty) {
					print ' selected="selected"';
				}

				print '>';
				$value = '&nbsp;';
				if ($format == 0) {
					$value = ($maxlength ? dol_trunc($arraytypes['label'], $maxlength) : $arraytypes['label']);
				} elseif ($format == 1) {
					$value = $arraytypes['code'];
				} elseif ($format == 2) {
					$value = ($maxlength ? dol_trunc($arraytypes['label'], $maxlength) : $arraytypes['label']);
				} elseif ($format == 3) {
					$value = $arraytypes['code'];
				}

				print $value;
				print '</option>';
			}
		}
		print '</select>';
		if ($user->admin && !$noadmininfo) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}

		print ajax_combobox('select'.$htmlname);
	}

	/**
	 *      Return html list of ticket anaytic codes
	 *
	 *      @param  string 		$selected   		Id categorie pre-selectionnée
	 *      @param  string 		$htmlname   		Name of select component
	 *      @param  string 		$filtertype 		To filter on some properties in llx_c_ticket_category ('public = 1'). This parameter must not come from input of users.
	 *      @param  int    		$format     		0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
	 *      @param  int    		$empty      		1=peut etre vide, 0 sinon
	 *      @param  int    		$noadmininfo		0=Add admin info, 1=Disable admin info
	 *      @param  int    		$maxlength  		Max length of label
	 *      @param	string		$morecss			More CSS
	 * 		@param	int 		$use_multilevel		If > 0 create a multilevel select which use $htmlname example: $use_multilevel = 1 permit to have 2 select boxes.
	 * 		@param	Translate	$outputlangs		Output lnaguage
	 *      @return void
	 */
	public function selectGroupTickets($selected = '', $htmlname = 'ticketcategory', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0, $morecss = '', $use_multilevel = 0, $outputlangs = null)
	{
		global $conf, $langs, $user;

		dol_syslog(get_class($this)."::selectCategoryTickets ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

		if (is_null($outputlangs) || !is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$outputlangs->load("ticket");

		$ticketstat = new Ticket($this->db);
		$ticketstat->loadCacheCategoriesTickets();

		if ($use_multilevel <= 0) {
			print '<select id="select'.$htmlname.'" class="flat minwidth100'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'">';
			if ($empty) {
				print '<option value="">&nbsp;</option>';
			}

			if (is_array($ticketstat->cache_category_tickets) && count($ticketstat->cache_category_tickets)) {
				foreach ($ticketstat->cache_category_tickets as $id => $arraycategories) {
					// Exclude some record
					if ($filtertype == 'public=1') {
						if (empty($arraycategories['public'])) {
							continue;
						}
					}

					// We discard empty line if showempty is on because an empty line has already been output.
					if ($empty && empty($arraycategories['code'])) {
						continue;
					}

					$label = ($arraycategories['label'] != '-' ? $arraycategories['label'] : '');
					if ($outputlangs->trans("TicketCategoryShort".$arraycategories['code']) != ("TicketCategoryShort".$arraycategories['code'])) {
						$label = $outputlangs->trans("TicketCategoryShort".$arraycategories['code']);
					} elseif ($outputlangs->trans($arraycategories['code']) != $arraycategories['code']) {
						$label = $outputlangs->trans($arraycategories['code']);
					}

					if ($format == 0) {
						print '<option value="'.$id.'"';
					}

					if ($format == 1) {
						print '<option value="'.$arraycategories['code'].'"';
					}

					if ($format == 2) {
						print '<option value="'.$arraycategories['code'].'"';
					}

					if ($format == 3) {
						print '<option value="'.$id.'"';
					}

					// Si selected est text, on compare avec code, sinon avec id
					if (preg_match('/[a-z]/i', $selected) && $selected == $arraycategories['code']) {
						print ' selected="selected"';
					} elseif ($selected == $id) {
						print ' selected="selected"';
					} elseif ($arraycategories['use_default'] == "1" && !$selected && !$empty) {
						print ' selected="selected"';
					}

					print '>';

					if ($format == 0) {
						$value = ($maxlength ? dol_trunc($label, $maxlength) : $label);
					}

					if ($format == 1) {
						$value = $arraycategories['code'];
					}

					if ($format == 2) {
						$value = ($maxlength ? dol_trunc($label, $maxlength) : $label);
					}

					if ($format == 3) {
						$value = $arraycategories['code'];
					}

					print $value ? $value : '&nbsp;';
					print '</option>';
				}
			}
			print '</select>';
			if ($user->admin && !$noadmininfo) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}

			print ajax_combobox('select'.$htmlname);
		} elseif ($htmlname!='') {
			$selectedgroups = array();
			$groupvalue = "";
			$groupticket=GETPOST($htmlname, 'aZ09');
			$child_id=GETPOST($htmlname.'_child_id', 'aZ09')?GETPOST($htmlname.'_child_id', 'aZ09'):0;
			if (!empty($groupticket)) {
				$tmpgroupticket = $groupticket;
				$sql = "SELECT ctc.rowid, ctc.fk_parent, ctc.code FROM ".$this->db->prefix()."c_ticket_category as ctc WHERE ctc.code = '".$this->db->escape($tmpgroupticket)."'";
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$selectedgroups[] = $obj->code;
					while ($obj->fk_parent > 0) {
						$sql = "SELECT ctc.rowid, ctc.fk_parent, ctc.code FROM ".$this->db->prefix()."c_ticket_category as ctc WHERE ctc.rowid ='".$this->db->escape($obj->fk_parent)."'";
						$resql = $this->db->query($sql);
						if ($resql) {
							$obj = $this->db->fetch_object($resql);
							$selectedgroups[] = $obj->code;
						}
					}
				}
			}
			$arrayidused = array();
			$arrayidusedconcat = array();
			$arraycodenotparent = array();
			$arraycodenotparent[] = "";

			$stringtoprint = '<span class="supportemailfield bold">'.$langs->trans("GroupOfTicket").'</span> ';
			$stringtoprint .= '<select id ="'.$htmlname.'" class="minwidth500" child_id="0">';
			$stringtoprint .= '<option value="">&nbsp;</option>';

			$sql = "SELECT ctc.rowid, ctc.code, ctc.label, ctc.fk_parent, ctc.public, ";
			$sql .= $this->db->ifsql("ctc.rowid NOT IN (SELECT ctcfather.rowid FROM llx_c_ticket_category as ctcfather JOIN llx_c_ticket_category as ctcjoin ON ctcfather.rowid = ctcjoin.fk_parent)", "'NOTPARENT'", "'PARENT'")." as isparent";
			$sql .= " FROM ".$this->db->prefix()."c_ticket_category as ctc";
			$sql .= " WHERE ctc.active > 0 AND ctc.entity = ".((int) $conf->entity);
			if ($filtertype == 'public=1') {
				$sql .= " AND ctc.public = 1";
			}
			$sql .= " AND ctc.fk_parent = 0";
			$sql .= $this->db->order('ctc.pos', 'ASC');

			$resql = $this->db->query($sql);
			if ($resql) {
				$num_rows_level0 = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num_rows_level0) {
					$obj = $this->db->fetch_object($resql);
					if ($obj) {
						$label = ($obj->label != '-' ? $obj->label : '');
						if ($outputlangs->trans("TicketCategoryShort".$obj->code) != ("TicketCategoryShort".$obj->code)) {
							$label = $outputlangs->trans("TicketCategoryShort".$obj->code);
						} elseif ($outputlangs->trans($obj->code) != $obj->code) {
							$label = $outputlangs->trans($obj->code);
						}

						$grouprowid = $obj->rowid;
						$groupvalue = $obj->code;
						$grouplabel = $label;

						$isparent = $obj->isparent;
						if (is_array($selectedgroups)) {
							$iselected = in_array($obj->code, $selectedgroups) ?'selected':'';
						} else {
							$iselected = $groupticket == $obj->code ?'selected':'';
						}
						$stringtoprint .= '<option '.$iselected.' class="'.$htmlname.dol_escape_htmltag($grouprowid).'" value="'.dol_escape_htmltag($groupvalue).'" data-html="'.dol_escape_htmltag($grouplabel).'">'.dol_escape_htmltag($grouplabel).'</option>';
						if ($isparent == 'NOTPARENT') {
							$arraycodenotparent[] = $groupvalue;
						}
						$arrayidused[] = $grouprowid;
						$arrayidusedconcat[] = $grouprowid;
					}
					$i++;
				}
			} else {
				dol_print_error($this->db);
			}
			if (count($arrayidused) == 1) {
				return '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.dol_escape_htmltag($groupvalue).'">';
			} else {
				$stringtoprint .= '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'_select" class="maxwidth500 minwidth400">';
				$stringtoprint .= '<input type="hidden" name="'.$htmlname.'_child_id" id="'.$htmlname.'_select_child_id" class="maxwidth500 minwidth400">';
			}
			$stringtoprint .= '</select>&nbsp;';

			$levelid = 1;	// The first combobox
			while ($levelid <= $use_multilevel) {	// Loop to take the child of the combo
				$tabscript = array();
				$stringtoprint .= '<select id ="'.$htmlname.'_child_'.$levelid.'" class="maxwidth500 minwidth400 groupticketchild" child_id="'.$levelid.'">';
				$stringtoprint .= '<option value="">&nbsp;</option>';

				$sql = "SELECT ctc.rowid, ctc.code, ctc.label, ctc.fk_parent, ctc.public, ctcjoin.code as codefather";
				$sql .= " FROM ".$this->db->prefix()."c_ticket_category as ctc";
				$sql .= " JOIN ".$this->db->prefix()."c_ticket_category as ctcjoin ON ctc.fk_parent = ctcjoin.rowid";
				$sql .= " WHERE ctc.active > 0 AND ctc.entity = ".((int) $conf->entity);
				$sql .= " AND ctc.rowid NOT IN (".$this->db->sanitize(join(',', $arrayidusedconcat)).")";

				if ($filtertype == 'public=1') {
					$sql .= " AND ctc.public = 1";
				}
				// Add a test to take only record that are direct child
				if (!empty($arrayidused)) {
					$sql .= " AND ctc.fk_parent IN ( ";
					foreach ($arrayidused as $idused) {
						$sql .= $idused.", ";
					}
					$sql = substr($sql, 0, -2);
					$sql .= ")";
				} else {
				}
				$sql .= $this->db->order('ctc.pos', 'ASC');

				$resql = $this->db->query($sql);
				if ($resql) {
					$num_rows = $this->db->num_rows($resql);
					$i = 0;
					$arrayidused=array();
					while ($i < $num_rows) {
						$obj = $this->db->fetch_object($resql);
						if ($obj) {
							$label = ($obj->label != '-' ? $obj->label : '');
							if ($outputlangs->trans("TicketCategoryShort".$obj->code) != ("TicketCategoryShort".$obj->code)) {
								$label = $outputlangs->trans("TicketCategoryShort".$obj->code);
							} elseif ($outputlangs->trans($obj->code) != $obj->code) {
								$label = $outputlangs->trans($obj->code);
							}

							$grouprowid = $obj->rowid;
							$groupvalue = $obj->code;
							$grouplabel = $label;
							$isparent = $obj->isparent;
							$fatherid = $obj->fk_parent;
							$arrayidused[] = $grouprowid;
							$arrayidusedconcat[] = $grouprowid;
							$groupcodefather = $obj->codefather;
							if ($isparent == 'NOTPARENT') {
								$arraycodenotparent[] = $groupvalue;
							}
							if (is_array($selectedgroups)) {
								$iselected = in_array($obj->code, $selectedgroups) ?'selected':'';
							} else {
								$iselected = $groupticket == $obj->code ?'selected':'';
							}
							$stringtoprint .= '<option '.$iselected.' class="'.$htmlname.'_'.dol_escape_htmltag($fatherid).'_child_'.$levelid.'" value="'.dol_escape_htmltag($groupvalue).'" data-html="'.dol_escape_htmltag($grouplabel).'">'.dol_escape_htmltag($grouplabel).'</option>';
							if (empty($tabscript[$groupcodefather])) {
								$tabscript[$groupcodefather] = 'if ($("#'.$htmlname.($levelid > 1 ?'_child_'.$levelid-1:'').'").val() == "'.dol_escape_js($groupcodefather).'"){
									$(".'.$htmlname.'_'.dol_escape_htmltag($fatherid).'_child_'.$levelid.'").show()
									console.log("We show childs tickets of '.$groupcodefather.' group ticket")
								}else{
									$(".'.$htmlname.'_'.dol_escape_htmltag($fatherid).'_child_'.$levelid.'").hide()
									console.log("We hide childs tickets of '.$groupcodefather.' group ticket")
								}';
							}
						}
						$i++;
					}
				} else {
					dol_print_error($this->db);
				}
				$stringtoprint .='</select>';

				$stringtoprint .='<script>';
				$stringtoprint .='arraynotparents = '.json_encode($arraycodenotparent).';';	// when the last visible combo list is number x, this is the array of group
				$stringtoprint .='if (arraynotparents.includes($("#'.$htmlname.($levelid > 1 ?'_child_'.$levelid-1:'').'").val())){
					console.log("'.$htmlname.'_child_'.$levelid.'")
					if($("#'.$htmlname.'_child_'.$levelid.'").val() == "" && ($("#'.$htmlname.'_child_'.$levelid.'").attr("child_id")>'.$child_id.')){
						$("#'.$htmlname.'_child_'.$levelid.'").hide();
						console.log("We hide '.$htmlname.'_child_'.$levelid.' input")
					}
					if(arraynotparents.includes("'.$groupticket.'") && '.$child_id.' == 0){
						$("#ticketcategory_select_child_id").val($("#'.$htmlname.'").attr("child_id"))
						$("#ticketcategory_select").val($("#'.$htmlname.'").val()) ;
						console.log("We choose '.$htmlname.' input and reload hidden input");
					}
				}
				$("#'.$htmlname.($levelid > 1 ?'_child_'.$levelid-1:'').'").change(function() {
					child_id = $("#'.$htmlname.($levelid > 1 ?'_child_'.$levelid:'').'").attr("child_id");

					/* Change of value to select this value*/
					if (arraynotparents.includes($(this).val()) || $(this).attr("child_id") == '.$use_multilevel.') {
						$("#ticketcategory_select").val($(this).val());
						$("#ticketcategory_select_child_id").val($(this).attr("child_id")) ;
						console.log("We choose to select "+ $(this).val());
					}else{
						if ($("#'.$htmlname.'_child_'.$levelid.' option").length <= 1) {
							$("#ticketcategory_select").val($(this).val());
							$("#ticketcategory_select_child_id").val($(this).attr("child_id"));
							console.log("We choose to select "+ $(this).val() + " and next combo has no item, so we keep this selection");
						} else {
							console.log("We choose to select "+ $(this).val() + " but next combo has some item, so we clean selected item");
							$("#ticketcategory_select").val("");
							$("#ticketcategory_select_child_id").val("");
						}
					}

					console.log("We select a new value into combo child_id="+child_id);

					/* Hide all selected box that are child of the one modified */
					$(".groupticketchild").each(function(){
						if ($(this).attr("child_id") > child_id) {
							console.log("hide child_id="+$(this).attr("child_id"));
							$(this).val("");
							$(this).hide();
						}
					})

					/* Now we enable the next combo */
					$("#'.$htmlname.'_child_'.$levelid.'").val("");
					if (!arraynotparents.includes($(this).val()) && $("#'.$htmlname.'_child_'.$levelid.' option").length > 1) {
						console.log($("#'.$htmlname.'_child_'.$levelid.' option").length);
						$("#'.$htmlname.'_child_'.$levelid.'").show()
					} else {
						$("#'.$htmlname.'_child_'.$levelid.'").hide()
					}
				';
				$levelid++;
				foreach ($tabscript as $script) {
					$stringtoprint .= $script;
				};
				$stringtoprint .='})';
				$stringtoprint .='</script>';
			}
			$stringtoprint .='<script>';
			$stringtoprint .='$("#'.$htmlname.'_child_'.$use_multilevel.'").change(function() {
				$("#ticketcategory_select").val($(this).val());
				$("#ticketcategory_select_child_id").val($(this).attr("child_id"));
				console.log($("#ticketcategory_select").val());
			})';
			$stringtoprint .='</script>';
			$stringtoprint .= ajax_combobox($htmlname);

			return $stringtoprint;
		}
	}

	/**
	 *      Return html list of ticket severitys
	 *
	 *      @param  string $selected    Id severity pre-selectionnée
	 *      @param  string $htmlname    Nom de la zone select
	 *      @param  string $filtertype  To filter on field type in llx_c_ticket_severity (array('code'=>xx,'label'=>zz))
	 *      @param  int    $format      0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
	 *      @param  int    $empty       1=peut etre vide, 0 sinon
	 *      @param  int    $noadmininfo 0=Add admin info, 1=Disable admin info
	 *      @param  int    $maxlength   Max length of label
	 *      @param	string	$morecss	More CSS
	 *      @return void
	 */
	public function selectSeveritiesTickets($selected = '', $htmlname = 'ticketseverity', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0, $morecss = '')
	{
		global $langs, $user;

		$ticketstat = new Ticket($this->db);

		dol_syslog(get_class($this)."::selectSeveritiesTickets ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

		$filterarray = array();

		if ($filtertype != '' && $filtertype != '-1') {
			$filterarray = explode(',', $filtertype);
		}

		$ticketstat->loadCacheSeveritiesTickets();

		print '<select id="select'.$htmlname.'" class="flat minwidth100'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'">';
		if ($empty) {
			print '<option value="">&nbsp;</option>';
		}

		if (is_array($ticketstat->cache_severity_tickets) && count($ticketstat->cache_severity_tickets)) {
			foreach ($ticketstat->cache_severity_tickets as $id => $arrayseverities) {
				// On passe si on a demande de filtrer sur des modes de paiments particuliers
				if (count($filterarray) && !in_array($arrayseverities['type'], $filterarray)) {
					continue;
				}

				// We discard empty line if showempty is on because an empty line has already been output.
				if ($empty && empty($arrayseverities['code'])) {
					continue;
				}

				if ($format == 0) {
					print '<option value="'.$id.'"';
				}

				if ($format == 1) {
					print '<option value="'.$arrayseverities['code'].'"';
				}

				if ($format == 2) {
					print '<option value="'.$arrayseverities['code'].'"';
				}

				if ($format == 3) {
					print '<option value="'.$id.'"';
				}

				// Si selected est text, on compare avec code, sinon avec id
				if (preg_match('/[a-z]/i', $selected) && $selected == $arrayseverities['code']) {
					print ' selected="selected"';
				} elseif ($selected == $id) {
					print ' selected="selected"';
				} elseif ($arrayseverities['use_default'] == "1" && !$selected && !$empty) {
					print ' selected="selected"';
				}

				print '>';
				if ($format == 0) {
					$value = ($maxlength ? dol_trunc($arrayseverities['label'], $maxlength) : $arrayseverities['label']);
				}

				if ($format == 1) {
					$value = $arrayseverities['code'];
				}

				if ($format == 2) {
					$value = ($maxlength ? dol_trunc($arrayseverities['label'], $maxlength) : $arrayseverities['label']);
				}

				if ($format == 3) {
					$value = $arrayseverities['code'];
				}

				print $value ? $value : '&nbsp;';
				print '</option>';
			}
		}
		print '</select>';
		if ($user->admin && !$noadmininfo) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}

		print ajax_combobox('select'.$htmlname);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Clear list of attached files in send mail form (also stored in session)
	 *
	 * @return	void
	 */
	public function clear_attached_files()
	{
		// phpcs:enable
		global $conf, $user;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Set tmp user directory
		$vardir = $conf->user->dir_output."/".$user->id;
		$upload_dir = $vardir.'/temp/'; // TODO Add $keytoavoidconflict in upload_dir path
		if (is_dir($upload_dir)) {
			dol_delete_dir_recursive($upload_dir);
		}

		$keytoavoidconflict = empty($this->track_id) ? '' : '-'.$this->track_id; // track_id instead of trackid
		unset($_SESSION["listofpaths".$keytoavoidconflict]);
		unset($_SESSION["listofnames".$keytoavoidconflict]);
		unset($_SESSION["listofmimes".$keytoavoidconflict]);
	}

	/**
	 * Show the form to add message on ticket
	 *
	 * @param  	string  $width      	Width of form
	 * @return 	void
	 */
	public function showMessageForm($width = '40%')
	{
		global $conf, $langs, $user, $hookmanager, $form, $mysoc;

		$formmail = new FormMail($this->db);
		$addfileaction = 'addfile';

		if (!is_object($form)) {
			$form = new Form($this->db);
		}

		// Load translation files required by the page
		$langs->loadLangs(array('other', 'mails'));

		// Clear temp files. Must be done at beginning, before call of triggers
		if (GETPOST('mode', 'alpha') == 'init' || (GETPOST('modelmailselected', 'alpha') && GETPOST('modelmailselected', 'alpha') != '-1')) {
			$this->clear_attached_files();
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
			$newlang = $this->param['langsmodels'];
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('other');
		}

		// Get message template for $this->param["models"] into c_email_templates
		$arraydefaultmessage = -1;
		if ($this->param['models'] != 'none') {
			$model_id = 0;
			if (array_key_exists('models_id', $this->param)) {
				$model_id = $this->param["models_id"];
			}

			$arraydefaultmessage = $formmail->getEMailTemplate($this->db, $this->param["models"], $user, $outputlangs, $model_id); // If $model_id is empty, preselect the first one
		}

		// Define list of attached files
		$listofpaths = array();
		$listofnames = array();
		$listofmimes = array();
		$keytoavoidconflict = empty($this->track_id) ? '' : '-'.$this->track_id; // track_id instead of trackid

		if (GETPOST('mode', 'alpha') == 'init' || (GETPOST('modelmailselected', 'alpha') && GETPOST('modelmailselected', 'alpha') != '-1')) {
			if (!empty($arraydefaultmessage->joinfiles) && is_array($this->param['fileinit'])) {
				foreach ($this->param['fileinit'] as $file) {
					$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
				}
			}
		}

		if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) {
			$listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
		}
		if (!empty($_SESSION["listofnames".$keytoavoidconflict])) {
			$listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
		}
		if (!empty($_SESSION["listofmimes".$keytoavoidconflict])) {
			$listofmimes = explode(';', $_SESSION["listofmimes".$keytoavoidconflict]);
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
			$newlang = $this->param['langsmodels'];
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('other');
		}

		print "\n<!-- Begin message_form TICKET -->\n";

		$send_email = GETPOST('send_email', 'int') ? GETPOST('send_email', 'int') : 0;

		// Example 1 : Adding jquery code
		print '<script type="text/javascript">
		jQuery(document).ready(function() {
			send_email=' . $send_email.';
			if (send_email) {
				if (!jQuery("#send_msg_email").is(":checked")) {
					jQuery("#send_msg_email").prop("checked", true).trigger("change");
				}
				jQuery(".email_line").show();
			} else {
				if (!jQuery("#private_message").is(":checked")) {
					jQuery("#private_message").prop("checked", true).trigger("change");
				}
				jQuery(".email_line").hide();
			}

			jQuery("#send_msg_email").click(function() {
				if(jQuery(this).is(":checked")) {
					if (jQuery("#private_message").is(":checked")) {
						jQuery("#private_message").prop("checked", false).trigger("change");
					}
					jQuery(".email_line").show();
				}
				else {
					jQuery(".email_line").hide();
				}
            });

            jQuery("#private_message").click(function() {
				if (jQuery(this).is(":checked")) {
					if (jQuery("#send_msg_email").is(":checked")) {
						jQuery("#send_msg_email").prop("checked", false).trigger("change");
					}
					jQuery(".email_line").hide();
				}
			});';
		print '});
		</script>';

		print '<form method="post" name="ticket" enctype="multipart/form-data" action="'.$this->param["returnurl"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.$this->action.'">';
		print '<input type="hidden" name="actionbis" value="add_message">';
		print '<input type="hidden" name="backtopage" value="'.$this->backtopage.'">';
		foreach ($this->param as $key => $value) {
			print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
		}

		// Get message template
		$model_id = 0;
		if (array_key_exists('models_id', $this->param)) {
			$model_id = $this->param["models_id"];
			$arraydefaultmessage = $formmail->getEMailTemplate($this->db, $this->param["models"], $user, $outputlangs, $model_id);
		}

		$result = $formmail->fetchAllEMailTemplate($this->param["models"], $user, $outputlangs);
		if ($result < 0) {
			setEventMessages($this->error, $this->errors, 'errors');
		}
		$modelmail_array = array();
		foreach ($formmail->lines_model as $line) {
			$modelmail_array[$line->id] = $line->label;
		}

		print '<table class="border" width="'.$width.'">';

		// External users can't send message email
		if ($user->rights->ticket->write && !$user->socid) {
			$ticketstat = new Ticket($this->db);
			$res = $ticketstat->fetch('', '', $this->track_id);

			print '<tr><td></td><td>';
			$checkbox_selected = (GETPOST('send_email') == "1" ? ' checked' : ($conf->global->TICKETS_MESSAGE_FORCE_MAIL?'checked':''));
			print '<input type="checkbox" name="send_email" value="1" id="send_msg_email" '.$checkbox_selected.'/> ';
			print '<label for="send_msg_email">'.$langs->trans('SendMessageByEmail').'</label>';
			print '</td></tr>';

			// Zone to select its email template
			if (count($modelmail_array) > 0) {
				print '<tr class="email_line"><td></td><td colspan="2"><div style="padding: 3px 0 3px 0">'."\n";
				 print $langs->trans('SelectMailModel').': '.$formmail->selectarray('modelmailselected', $modelmail_array, $this->param['models_id'], 1, 0, "", "", 0, 0, 0, '', 'minwidth200');
				if ($user->admin) {
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				}
				print ' &nbsp; ';
				print '<input type="submit" class="button" value="'.$langs->trans('Apply').'" name="modelselected" id="modelselected">';
				print '</div></td>';
			}

			// Private message (not visible by customer/external user)
			if (!$user->socid) {
				print '<tr><td></td><td>';
				$checkbox_selected = (GETPOST('private_message', 'alpha') == "1" ? ' checked' : '');
				print '<input type="checkbox" name="private_message" value="1" id="private_message" '.$checkbox_selected.'/> ';
				print '<label for="private_message">'.$langs->trans('MarkMessageAsPrivate').'</label>';
				print ' '.$form->textwithpicto('', $langs->trans("TicketMessagePrivateHelp"), 1, 'help');
				print '</td></tr>';
			}

			// Subject
			print '<tr class="email_line"><td>'.$langs->trans('Subject').'</td>';
			print '<td><input type="text" class="text minwidth500" name="subject" value="['.$conf->global->MAIN_INFO_SOCIETE_NOM.' - '.$langs->trans("Ticket").' '.$ticketstat->ref.'] '.$langs->trans('TicketNewMessage').'" />';
			print '</td></tr>';

			// Destinataires
			print '<tr class="email_line"><td>'.$langs->trans('MailRecipients').'</td><td>';
			if ($res) {
				// Retrieve email of all contacts (internal and external)
				$contacts = $ticketstat->getInfosTicketInternalContact();
				$contacts = array_merge($contacts, $ticketstat->getInfosTicketExternalContact());

				$sendto = array();

				// Build array to display recipient list
				if (is_array($contacts) && count($contacts) > 0) {
					foreach ($contacts as $key => $info_sendto) {
						if ($info_sendto['email'] != '') {
							$sendto[] = dol_escape_htmltag(trim($info_sendto['firstname']." ".$info_sendto['lastname'])." <".$info_sendto['email'].">").' <small class="opacitymedium">('.dol_escape_htmltag($info_sendto['libelle']).")</small>";
						}
					}
				}

				if ($ticketstat->origin_email && !in_array($ticketstat->origin_email, $sendto)) {
					$sendto[] = dol_escape_htmltag($ticketstat->origin_email).' <small class="opacitymedium">('.$langs->trans("TicketEmailOriginIssuer").")</small>";
				}

				if ($ticketstat->fk_soc > 0) {
					$ticketstat->socid = $ticketstat->fk_soc;
					$ticketstat->fetch_thirdparty();

					if (is_array($ticketstat->thirdparty->email) && !in_array($ticketstat->thirdparty->email, $sendto)) {
						$sendto[] = $ticketstat->thirdparty->email.' <small class="opacitymedium">('.$langs->trans('Customer').')</small>';
					}
				}

				if ($conf->global->TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS) {
					$sendto[] = $conf->global->TICKET_NOTIFICATION_EMAIL_TO.' <small class="opacitymedium">(generic email)</small>';
				}

				// Print recipient list
				if (is_array($sendto) && count($sendto) > 0) {
					print img_picto('', 'email', 'class="pictofixedwidth"');
					print implode(', ', $sendto);
				} else {
					print '<div class="warning">'.$langs->trans('WarningNoEMailsAdded').' '.$langs->trans('TicketGoIntoContactTab').'</div>';
				}
			}
			print '</td></tr>';
		}

		$uselocalbrowser = false;

		// Intro
		// External users can't send message email
		if ($user->rights->ticket->write && !$user->socid) {
			$mail_intro = GETPOST('mail_intro') ? GETPOST('mail_intro') : $conf->global->TICKET_MESSAGE_MAIL_INTRO;
			print '<tr class="email_line"><td><label for="mail_intro">';
			print $form->textwithpicto($langs->trans("TicketMessageMailIntro"), $langs->trans("TicketMessageMailIntroHelp"), 1, 'help');
			print '</label>';

			print '</td><td>';
			include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

			$doleditor = new DolEditor('mail_intro', $mail_intro, '100%', 90, 'dolibarr_details', '', false, $uselocalbrowser, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_2, 70);

			$doleditor->Create();
			print '</td></tr>';
		}

		// MESSAGE

		$defaultmessage = "";
		if (is_object($arraydefaultmessage) && $arraydefaultmessage->content) {
			$defaultmessage = $arraydefaultmessage->content;
		}
		$defaultmessage = str_replace('\n', "\n", $defaultmessage);

		// Deal with format differences between message and signature (text / HTML)
		if (dol_textishtml($defaultmessage) && !dol_textishtml($this->substit['__USER_SIGNATURE__'])) {
			$this->substit['__USER_SIGNATURE__'] = dol_nl2br($this->substit['__USER_SIGNATURE__']);
		} elseif (!dol_textishtml($defaultmessage) && dol_textishtml($this->substit['__USER_SIGNATURE__'])) {
			$defaultmessage = dol_nl2br($defaultmessage);
		}
		if (GETPOSTISSET("message") && !GETPOST('modelselected')) {
			$defaultmessage = GETPOST('message', 'restricthtml');
		} else {
			$defaultmessage = make_substitutions($defaultmessage, $this->substit);
			// Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
			$defaultmessage = preg_replace("/^(<br>)+/", "", $defaultmessage);
			$defaultmessage = preg_replace("/^\n+/", "", $defaultmessage);
		}

		print '<tr><td class="tdtop"><label for="message"><span class="fieldrequired">'.$langs->trans("Message").'</span>';
		if ($user->rights->ticket->write && !$user->socid) {
			print $form->textwithpicto('', $langs->trans("TicketMessageHelp"), 1, 'help');
		}
		print '</label></td><td>';
		//$toolbarname = 'dolibarr_details';
		$toolbarname = 'dolibarr_notes';
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('message', $defaultmessage, '100%', 200, $toolbarname, '', false, $uselocalbrowser, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_5, 70);
		$doleditor->Create();
		print '</td></tr>';

		// Signature
		// External users can't send message email
		if ($user->rights->ticket->write && !$user->socid) {
			$mail_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE;
			print '<tr class="email_line"><td><label for="mail_intro">'.$langs->trans("TicketMessageMailSignature").'</label>';
			print $form->textwithpicto('', $langs->trans("TicketMessageMailSignatureHelp"), 1, 'help');
			print '</td><td>';
			include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor = new DolEditor('mail_signature', $mail_signature, '100%', 150, 'dolibarr_details', '', false, $uselocalbrowser, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_2, 70);
			$doleditor->Create();
			print '</td></tr>';
		}

		// Attached files
		if (!empty($this->withfile)) {
			$out = '<tr>';
			$out .= '<td width="180">'.$langs->trans("MailFile").'</td>';
			$out .= '<td>';
			// TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
			$out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">'."\n";
			$out .= '<script type="text/javascript">';
			$out .= 'jQuery(document).ready(function () {';
			$out .= '    jQuery(".removedfile").click(function() {';
			$out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
			$out .= '    });';
			$out .= '})';
			$out .= '</script>'."\n";
			if (count($listofpaths)) {
				foreach ($listofpaths as $key => $val) {
					$out .= '<div id="attachfile_'.$key.'">';
					$out .= img_mime($listofnames[$key]).' '.$listofnames[$key];
					if (!$this->withfilereadonly) {
						$out .= ' <input type="image" style="border: 0px;" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" value="'.($key + 1).'" class="removedfile reposition" id="removedfile_'.$key.'" name="removedfile_'.$key.'" />';
					}
					$out .= '<br></div>';
				}
			} else {
				$out .= $langs->trans("NoAttachedFiles").'<br>';
			}
			if ($this->withfile == 2) { // Can add other files
				$out .= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
				$out .= ' ';
				$out .= '<input type="submit" class="button smallpaddingimp reposition" id="'.$addfileaction.'" name="'.$addfileaction.'" value="'.$langs->trans("MailingAddFile").'" />';
			}
			$out .= "</td></tr>\n";

			print $out;
		}

		print '</table>';

		print '<center><br>';
		print '<input type="submit" class="button" name="btn_add_message" value="'.$langs->trans("AddMessage").'" />';
		if ($this->withcancel) {
			print " &nbsp; &nbsp; ";
			print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
		print "</center>\n";

		print '<input type="hidden" name="page_y">'."\n";

		print "</form>\n";
		print "<!-- End form TICKET -->\n";
	}
}
