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
 *       \brief      Fichier de la classe permettant la generation du formulaire html d'envoi de mail unitaire
 */
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formmail.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formprojet.class.php";

if (!class_exists('FormCompany')) {
	include DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
}

/**
 * Classe permettant la generation du formulaire d'un nouveau ticket.
 *
 * @package Ticket
 * \remarks Utilisation: $formticket = new FormTicket($db)
 * \remarks $formticket->proprietes=1 ou chaine ou tableau de valeurs
 * \remarks $formticket->show_form() affiche le formulaire
 */
class FormTicket
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

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

	public $ispublic; // To show information or not into public form

	public $withtitletopic;
	public $withcompany; // affiche liste déroulante company
	public $withfromsocid;
	public $withfromcontactid;
	public $withnotifytiersatcreate;
	public $withusercreate; // Show name of creating user in form
	public $withcreatereadonly;

	public $withref; // Show ref field

	public $withcancel;

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

		$this->withcompany = $conf->societe->enabled ? 1 : 0;
		$this->withfromsocid = 0;
		$this->withfromcontactid = 0;
		//$this->withthreadid=0;
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
	 * @param	string			$action					[=''] Action in card
	 * @return 	void
	 */
	public function showForm($withdolfichehead = 0, $mode = 'edit', $public = 0, Contact $with_contact = null, $action = '')
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

		print '<form method="POST" '.($withdolfichehead ? '' : 'style="margin-bottom: 30px;" ').'name="ticket" id="form_create_ticket" enctype="multipart/form-data" action="'.$this->param["returnurl"].'">';
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
		if ($this->withemail) {
			print '<tr><td class="titlefield"><label for="email"><span class="fieldrequired">'.$langs->trans("Email").'</span></label></td><td>';
			print '<input  class="text minwidth200" id="email" name="email" value="'.(GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : $subject).'" autofocus>';
			print '</td></tr>';
		}

		// If ticket created from another object
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
		$this->selectTypesTickets((GETPOST('type_code', 'alpha') ? GETPOST('type_code', 'alpha') : $this->type_code), 'type_code', '', 2, 0, 0, 0, 'minwidth200');
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
		print '<tr><td><span class="fieldrequired"><label for="selectseverity_code">'.$langs->trans("TicketSeverity").'</span></label></td><td>';
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
				if ($this->withthreadid > 0) {
					$subject = $langs->trans('SubjectAnswerToTicket').' '.$this->withthreadid.' : '.$this->topic_title.'';
				}
				print '<input class="text minwidth500" id="subject" name="subject" value="'.(GETPOST('subject', 'alpha') ? GETPOST('subject', 'alpha') : $subject).'" autofocus />';
				print '</td></tr>';
			}
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
		$doleditor = new DolEditor('message', $msg, '100%', 230, $toolbarname, 'In', true, $uselocalbrowser, $conf->global->FCKEDITOR_ENABLE_TICKET, ROWS_8, '90%');
		$doleditor->Create();
		print '</td></tr>';

		if ($public && !empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			print '<tr><td class="titlefield"><label for="email"><span class="fieldrequired">'.$langs->trans("SecurityCode").'</span></label></td><td>';
			print '<span class="span-icon-security inline-block">';
			print '<input id="securitycode" placeholder="'.$langs->trans("SecurityCode").'" class="flat input-icon-security width125" type="text" maxlength="5" name="code" tabindex="3" />';
			print '</span>';
			print '<span class="nowrap inline-block">';
			print '<img class="inline-block valignmiddle" src="'.DOL_URL_ROOT.'/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
			print '<a class="inline-block valignmiddle" href="'.$php_self.'" tabindex="4" data-role="button">'.img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"').'</a>';
			print '</span>';
			print '</td></tr>';
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
			$out .= '<script type="text/javascript" language="javascript">';
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
		}

		if ($subelement != 'project') {
			if (!empty($conf->projet->enabled) && !$this->ispublic) {
				$formproject = new FormProjets($this->db);
				print '<tr><td><label for="project"><span class="">'.$langs->trans("Project").'</span></label></td><td>';
				print img_picto('', 'project').$formproject->select_projects(-1, GETPOST('projectid', 'int'), 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
				print '</td></tr>';
			}
		}

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $ticketstat, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			print $ticketstat->showOptionals($extrafields, 'create');
		}

		print '</table>';

		if ($withdolfichehead) {
			print dol_get_fiche_end();
		}

		print '<br><div class="center">';
		print '<input class="button" type="submit" name="add" value="'.$langs->trans(($this->withthreadid > 0 ? "SendResponse" : "CreateTicket")).'" />';
		if ($this->withcancel) {
			print " &nbsp; &nbsp; &nbsp;";
			print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
		print '</div>';

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
	 *      @param  string $selected    Id categorie pre-selectionnée
	 *      @param  string $htmlname    Nom de la zone select
	 *      @param  string $filtertype  To filter on some properties in llx_c_ticket_category ('public = 1'). This parameter must not come from input of users.
	 *      @param  int    $format      0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
	 *      @param  int    $empty       1=peut etre vide, 0 sinon
	 *      @param  int    $noadmininfo 0=Add admin info, 1=Disable admin info
	 *      @param  int    $maxlength   Max length of label
	 *      @param	string	$morecss	More CSS
	 * 		@param	int 	$use_multilevel	if != 0 create a multilevel select ( Do not use any of the other params)
	 *      @return void
	 */
	public function selectGroupTickets($selected = '', $htmlname = 'ticketcategory', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0, $morecss = '', $use_multilevel = 0)
	{
		global $langs, $user;

		if ($use_multilevel == 0) {
			$ticketstat = new Ticket($this->db);

			dol_syslog(get_class($this)."::selectCategoryTickets ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

			$ticketstat->loadCacheCategoriesTickets();

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
						$value = ($maxlength ? dol_trunc($arraycategories['label'], $maxlength) : $arraycategories['label']);
					}

					if ($format == 1) {
						$value = $arraycategories['code'];
					}

					if ($format == 2) {
						$value = ($maxlength ? dol_trunc($arraycategories['label'], $maxlength) : $arraycategories['label']);
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
		} else {
			$groupticket=GETPOST('groupticket', 'aZ09');
			$groupticketchild=GETPOST('groupticket_child', 'aZ09');
			$arraycodenotparent[] = "";
			$stringtoprint = '<span class="supportemailfield bold">'.$langs->trans("GroupOfTicket").'</span> ';
			$stringtoprint .= '<select name="groupticket" id ="groupticket" class="maxwidth500 minwidth400">';
			$stringtoprint .= '<option value="">&nbsp;</option>';

			$sql = "SELECT ctc.rowid, ctc.code, ctc.label, ctc.fk_parent, ";
			$sql .= $this->db->ifsql("ctc.rowid NOT IN (SELECT ctcfather.rowid FROM llx_c_ticket_category as ctcfather JOIN llx_c_ticket_category as ctcjoin ON ctcfather.rowid = ctcjoin.fk_parent)", "'NOTPARENT'", "'PARENT'")." as isparent";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_category as ctc";
			$sql .= " WHERE ctc.public = 1";
			$sql .= " AND ctc.active = 1";
			$sql .= " AND ctc.fk_parent = 0";
			$sql .= $this->db->order('ctc.pos', 'ASC');
			$resql = $this->db->query($sql);
			if ($resql) {
				$num_rows = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql);
					if ($obj) {
						$grouprowid = $obj->rowid;
						$groupvalue = $obj->code;
						$grouplabel = $obj->label;
						$isparent = $obj->isparent;
						$iselected = $groupticket == $obj->code ?'selected':'';
						$stringtoprint .= '<option '.$iselected.' class="groupticket'.dol_escape_htmltag($grouprowid).'" value="'.dol_escape_htmltag($groupvalue).'" data-html="'.dol_escape_htmltag($grouplabel).'">'.dol_escape_htmltag($grouplabel).'</option>';
						if ($isparent == 'NOTPARENT') {
							$arraycodenotparent[] = $groupvalue;
						}
					}
					$i++;
				}
			} else {
				dol_print_error($this->db);
			}
			if ($num_rows == 1) {
				return '<input type="hidden" name="groupticket" id="groupticket" value="'.dol_escape_htmltag($groupvalue).'">';
			}
			$stringtoprint .= '</select>&nbsp';

			$stringtoprint .= '<select name="groupticket_child" id ="groupticket_child" class="maxwidth500 minwidth400">';
			$stringtoprint .= '<option value="">&nbsp;</option>';

			$sql = "SELECT ctc.rowid, ctc.code, ctc.label, ctc.fk_parent, ctcjoin.code as codefather";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_category as ctc";
			$sql .= " JOIN ".MAIN_DB_PREFIX."c_ticket_category as ctcjoin ON ctc.fk_parent = ctcjoin.rowid";
			$sql .= " WHERE ctc.public = 1";
			$sql .= " AND ctc.active = 1";
			$sql .= " AND ctc.fk_parent <> 0";
			$sql .= $this->db->order('ctc.pos', 'ASC');
			$resql = $this->db->query($sql);
			if ($resql) {
				$num_rows = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql);
					if ($obj) {
						$grouprowid = $obj->rowid;
						$groupvalue = $obj->code;
						$grouplabel = $obj->label;
						$fatherid = $obj->fk_parent;
						$groupcodefather = $obj->codefather;
						$iselected = $groupticketchild == $obj->code ?'selected':'';
						$stringtoprint .= '<option '.$iselected.' class="groupticket_'.dol_escape_htmltag($fatherid).'_child" value="'.dol_escape_htmltag($groupvalue).'" data-html="'.dol_escape_htmltag($grouplabel).'">'.dol_escape_htmltag($grouplabel).'</option>';
						$tabscript[] = 'if($("#groupticket")[0].value == "'.dol_escape_js($groupcodefather).'"){
							$(".groupticket_'.dol_escape_htmltag($fatherid).'_child").show()
						}else{
							$(".groupticket_'.dol_escape_htmltag($fatherid).'_child").hide()
						}';
					}
					$i++;
				}
			} else {
				dol_print_error($this->db);
			}
			$stringtoprint .='</select>';

			$stringtoprint .='<script>';
			$stringtoprint .='var arraynotparents = '.json_encode($arraycodenotparent).';';
			$stringtoprint .='if (arraynotparents.includes($("#groupticket")[0].value)){$("#groupticket_child").hide()}
			else{';
			foreach ($tabscript as $script) {
				$stringtoprint .= $script;
			};
			$stringtoprint .='}
			$("#groupticket").change(function() {
				$("#groupticket_child")[0].value = ""
				if (!arraynotparents.includes(this.value)) {
				$("#groupticket_child").show()
				} else {
				$("#groupticket_child").hide()
				}
			';
			foreach ($tabscript as $script) {
				$stringtoprint .= $script;
			};
			$stringtoprint .='})';
			$stringtoprint .='</script>';
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

		$keytoavoidconflict = empty($this->trackid) ? '' : '-'.$this->trackid; // this->trackid must be defined
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
		$keytoavoidconflict = empty($this->trackid) ? '' : '-'.$this->trackid; // this->trackid must be defined

		if (GETPOST('mode', 'alpha') == 'init' || (GETPOST('modelmailselected', 'alpha') && GETPOST('modelmailselected', 'alpha') != '-1')) {
			if (!empty($arraydefaultmessage->joinfiles) && is_array($this->param['fileinit'])) {
				foreach ($this->param['fileinit'] as $file) {
					$this->add_attached_files($file, basename($file), dol_mimetype($file));
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
		print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			send_email=' . $send_email.';
			if (send_email) {
				jQuery(".email_line").show();
			} else {
				jQuery(".email_line").hide();
			}

			jQuery("#send_msg_email").click(function() {
				if(jQuery(this).is(":checked")) {
					jQuery(".email_line").show();
				}
				else {
					jQuery(".email_line").hide();
				}
            });';
		print '});
		</script>';

		print '<form method="post" name="ticket" enctype="multipart/form-data" action="'.$this->param["returnurl"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.$this->action.'">';
		print '<input type="hidden" name="actionbis" value="add_message">';
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
			print '<tr><td></td><td>';
			$checkbox_selected = (GETPOST('send_email') == "1" ? ' checked' : '');
			print '<input type="checkbox" name="send_email" value="1" id="send_msg_email" '.$checkbox_selected.'/> ';
			print '<label for="send_msg_email">'.$langs->trans('SendMessageByEmail').'</label>';
			print '</td></tr>';

			// Zone to select its email template
			if (count($modelmail_array) > 0) {
				print '<tr class="email_line"><td></td><td colspan="2"><div style="padding: 3px 0 3px 0">'."\n";
				print $langs->trans('SelectMailModel').': '.$formmail->selectarray('modelmailselected', $modelmail_array, $this->param['models_id'], 1);
				if ($user->admin) {
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				}
				print ' &nbsp; ';
				print '<input class="button" type="submit" value="'.$langs->trans('Apply').'" name="modelselected" id="modelselected">';
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
			print '<td><input type="text" class="text minwidth500" name="subject" value="['.$conf->global->MAIN_INFO_SOCIETE_NOM.' - '.$langs->trans("Ticket").' '.$this->ref.'] '.$langs->trans('TicketNewMessage').'" />';
			print '</td></tr>';

			// Destinataires
			print '<tr class="email_line"><td>'.$langs->trans('MailRecipients').'</td><td>';
			$ticketstat = new Ticket($this->db);
			$res = $ticketstat->fetch('', '', $this->track_id);
			if ($res) {
				// Retrieve email of all contacts (internal and external)
				$contacts = $ticketstat->getInfosTicketInternalContact();
				$contacts = array_merge($contacts, $ticketstat->getInfosTicketExternalContact());

				// Build array to display recipient list
				if (is_array($contacts) && count($contacts) > 0) {
					foreach ($contacts as $key => $info_sendto) {
						if ($info_sendto['email'] != '') {
							$sendto[] = dol_escape_htmltag(trim($info_sendto['firstname']." ".$info_sendto['lastname'])." <".$info_sendto['email'].">").' <small class="opacitymedium">('.dol_escape_htmltag($info_sendto['libelle']).")</small>";
						}
					}
				}

				if ($ticketstat->origin_email && !in_array($this->dao->origin_email, $sendto)) {
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

			$doleditor = new DolEditor('mail_intro', $mail_intro, '100%', 90, 'dolibarr_details', '', false, $uselocalbrowser, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);

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
		if (GETPOSTISSET("message") && !$_POST['modelselected']) {
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
		$doleditor = new DolEditor('message', $defaultmessage, '100%', 200, $toolbarname, '', false, $uselocalbrowser, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_5, 70);
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
			$doleditor = new DolEditor('mail_signature', $mail_signature, '100%', 150, 'dolibarr_details', '', false, $uselocalbrowser, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);
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
			$out .= '<script type="text/javascript" language="javascript">';
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
		print '<input class="button" type="submit" name="btn_add_message" value="'.$langs->trans("AddMessage").'" />';
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
