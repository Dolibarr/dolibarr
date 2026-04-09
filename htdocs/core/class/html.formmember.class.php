<?php
/* Copyright (C) 2026		direct copy of htdocs/core/class/html.formcompany.class.php
 * Copyright (C) 2026		Jon Bendtsen          		<jon.bendtsen.github@jonb.dk>
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
 *	\file       htdocs/core/class/html.formmember.class.php
 *  \ingroup    core
 *	\brief      File of class to build HTML component for third parties management
 */


/**
 *	Class to build HTML component for member management
 *	Only common components are here.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';


/**
 * Class of forms component to manage members
 */
class FormMember extends Form
{
	/**
	 *  Output list of members
	 *
	 *  @param  object		$object         Object we try to find contacts
	 *  @param  string		$var_id         Name of id field
	 *  @param  int 		$selected       Preselected member
	 *  @param  string		$htmlname       Name of HTML form
	 * 	@param	int[]		$limitto		Disable answers that are not id in this array list
	 *  @param	int			$forceid		This is to force another object id than object->id
	 *  @param	string		$moreparam		String with more param to add into url when noajax search is used.
	 *  @param	string		$morecss		More CSS on select component
	 * 	@return int 						The selected member ID
	 */
	public function selectMemberForNewContact($object, $var_id, $selected = 0, $htmlname = 'newcompany', $limitto = [], $forceid = 0, $moreparam = '', $morecss = '')
	{
		dol_syslog(get_class($this)."::selectMemberForNewContact::object->element=".$object->element, LOG_DEBUG);
		global $conf, $user, $hookmanager;

		if (!empty($conf->use_javascript_ajax) && getDolGlobalString('MEMBER_USE_SEARCH_TO_SELECT')) {
			// Use Ajax search
			$minLength = (is_numeric(getDolGlobalString('MEMBER_USE_SEARCH_TO_SELECT')) ? $conf->global->MEMBER_USE_SEARCH_TO_SELECT : 2);

			$memid = 0;
			$name = '';
			if ($selected > 0) {
				$tmpmember = new Adherent($this->db);
				$result = $tmpmember->fetch($selected);
				if ($result > 0) {
					$memid = $selected;
					$name = $tmpmember->fullname;
				}
			}

			$events = array();
			// Add an entry 'method' to say 'yes, we must execute url with param action = method';
			// Add an entry 'url' to say which url to execute
			// Add an entry htmlname to say which element we must change once url is called
			// Add entry params => array('cssid' => 'attr') to say to remov or add attribute attr if answer of url return  0 or >0 lines
			// To refresh contacts list on thirdparty list change
			$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php', 1), 'htmlname' => 'contactid', 'params' => array('add-member-contact' => 'disabled'));

			if (count($events)) {	// If there is some ajax events to run once selection is done, we add code here to run events
				print '<script nonce="' . getNonce() . '" type="text/javascript">
				jQuery(document).ready(function() {
					$("#search_' . $htmlname . '").change(function() {
						var obj = ' . json_encode($events) . ';
						$.each(obj, function(key,values) {
							if (values.method.length) {
								runJsCodeForEvent' . $htmlname . '(values);
							}
						});

						$(this).trigger("blur");
					});

					// Function used to execute events when search_htmlname change
					function runJsCodeForEvent' . $htmlname . '(obj) {
						var id = $("#' . $htmlname . '").val();
						var method = obj.method;
						var url = obj.url;
						var htmlname = obj.htmlname;
						var showempty = obj.showempty;
						console.log("Run runJsCodeForEvent-' . $htmlname . ' from selectMemberForNewContact id="+id+" method="+method+" showempty="+showempty+" url="+url+" htmlname="+htmlname);
						$.getJSON(url,
							{
								action: method,
								id: id,
								htmlname: htmlname
							},
							function(response) {
								if (response != null)
								{
									console.log("Change select#"+htmlname+" with content "+response.value)
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
								}
							}
						);
					}
				});
				</script>';
			}

			print "\n" . '<!-- Input text for member with Ajax.Autocompleter ('.get_class($this)."::selectMemberForNewContact".') -->' . "\n";
			print '<input type="text" size="30" id="search_' . $htmlname . '" name="search_' . $htmlname . '" value="' . $name . '" />';
			print ajax_autocompleter((string) ($memid ? $memid : -1), $htmlname, DOL_URL_ROOT . '/adherents/ajax/ajaxmembers.php', '', $minLength, 0);
			return $memid;
		} else {
			// Search to list thirdparties
			$sql = "SELECT a.rowid, a.firstname as firstname, a.lastname as lastname ";
			if (getDolGlobalString('MEMBER_ADD_REF_IN_LIST')) {
				$sql .= ", a.ref";
			}
			if (getDolGlobalString('MEMBER_ADD_EXTREF_IN_LIST')) {
				$sql .= ", a.ref_ext";
			}
			if (getDolGlobalString('MEMBER_ADD_CIVILITY_IN_LIST')) {
				$sql .= ", a.civility";
			}
			if (getDolGlobalString('MEMBER_ADD_GENDER_IN_LIST')) {
				$sql .= ", a.gender";
			}
			if (getDolGlobalString('MEMBER_ADD_TYPE_IN_LIST')) {
				$sql .= ", a.fk_adherent_type";
				// this should probably be joined with llx_adherent_type
			}
			if (getDolGlobalString('MEMBER_ADD_EMAIL_IN_LIST')) {
				$sql .= ", a.email";
			}
			if (getDolGlobalString('MEMBER_ADD_NATURE_IN_LIST')) {
				$sql .= ", a.morphy";
			}
			if (getDolGlobalString('MEMBER_SHOW_ADDRESS_SELECTLIST')) {
				$sql .= ", a.address, a.zip, a.town";
				$sql .= ", dictp.code as country_code";
			}
			$sql .= " FROM " . $this->db->prefix() . "adherent as a";
			if (getDolGlobalString('MEMBER_SHOW_ADDRESS_SELECTLIST')) {
				$sql .= " LEFT JOIN " . $this->db->prefix() . "c_country as dictp ON dictp.rowid = a.country";
			}
			// Filter on active member only (status = 1) Closed member must not be selectable
			$sql .= " WHERE a.entity IN (" . getEntity('member') . ")  AND a.statut = 1";
			// For ajax search we limit here. For combo list, we limit later
			if (count($limitto)) {
				$sql .= " AND a.rowid IN (" . $this->db->sanitize(implode(',', $limitto)) . ")";
			}
			// Add where from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('selectMembersForNewContactListWhere', $parameters); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
			if (getDolGlobalString('MEMBER_SORT_IN_LIST') == 'ref') {
				$sql .= " ORDER BY a.ref ASC";
			} elseif (getDolGlobalString('MEMBER_SORT_IN_LIST') == 'lastname') {
				$sql .= " ORDER BY a.lastname ASC";
			} elseif (getDolGlobalString('MEMBER_SORT_IN_LIST') == 'email') {
				$sql .= " ORDER BY a.email ASC";
			} else {
				// First name is default in my country, but at least you can choose a few different options
				$sql .= " ORDER BY a.firstname ASC";
			}

			$resql = $this->db->query($sql);
			if ($resql) {
				print '<select class="flat' . ($morecss ? ' ' . $morecss : '') . '" id="' . $htmlname . '" name="' . $htmlname . '"';
				if ($conf->use_javascript_ajax) {
					$javaScript = "window.location='" . dol_escape_js($_SERVER['PHP_SELF']) . "?" . $var_id . "=" . ($forceid > 0 ? $forceid : $object->id) . $moreparam . "&" . $htmlname . "=' + form." . $htmlname . ".options[form." . $htmlname . ".selectedIndex].value;";
					print ' onChange="' . $javaScript . '"';
				}
				print '>';
				print '<option value="-1">&nbsp;</option>';

				$num = $this->db->num_rows($resql);
				$i = 0;
				$firstMember = 0;  // For static analysis
				if ($num) {
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						if ($i == 0) {
							$firstMember = $obj->rowid;
						}
						$disabled = 0;
						if (count($limitto) && !in_array($obj->rowid, $limitto)) {
							$disabled = 1;
						}
						if (getDolGlobalString('MEMBER_ORDER_LASTNAME')) {
							$showname = ''.dol_escape_htmltag($obj->lastname, 0, 0, '', 0, 1).', '.dol_escape_htmltag($obj->firstname, 0, 0, '', 0, 1);
						} else {
							$showname = ''.dol_escape_htmltag($obj->firstname, 0, 0, '', 0, 1).' '.dol_escape_htmltag($obj->lastname, 0, 0, '', 0, 1);
						}
						if ($selected > 0 && $selected == $obj->rowid) {
							print '<option value="' . $obj->rowid . '"';
							if ($disabled) {
								print ' disabled';
							}
							print ' selected>'.$showname.'</option>';
							$firstMember = $obj->rowid;
						} else {
							print '<option value="' . $obj->rowid . '"';
							if ($disabled) {
								print ' disabled';
							}
							print '>' .$showname. '</option>';
						}
						$i++;
					}
				}
				if ($selected > 0 && $firstMember != $selected) {
					// This must mean that the preselected member was not found in the status above, so we should add it but disabled
					// '<option value="'.$selected.">Foo Bar</option>'
					require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
					$selectedmember = new Adherent($this->db);
					$resultselection = $selectedmember->fetch($selected);
					if ($resultselection) {
						print '<option value="' . $selected . '"';
						print ' disabled';
						if (getDolGlobalString('MEMBER_ORDER_LASTNAME')) {
							$showname = ''.dol_escape_htmltag($selectedmember->lastname, 0, 0, '', 0, 1).', '.dol_escape_htmltag($selectedmember->firstname, 0, 0, '', 0, 1);
						} else {
							$showname = ''.dol_escape_htmltag($selectedmember->firstname, 0, 0, '', 0, 1).' '.dol_escape_htmltag($selectedmember->lastname, 0, 0, '', 0, 1);
						}
						print ' selected>'.$showname.'</option>';
						$firstMember = $selected;
					}
				}
				print "</select>\n";
				print ajax_combobox($htmlname);
				return $firstMember;
			} else {
				dol_print_error($this->db);
				return 0;
			}
		}
	}

	/**
	 *  Return a select list with types of contacts
	 *
	 *  @param	?Object		$object         	Object to use to find type of contact
	 *  @param  string		$selected       	Default selected value
	 *  @param  string		$htmlname			HTML select name
	 *  @param  string		$source				Source ('member' is currently the only supported)
	 *  @param  string		$sortorder			Sort criteria ('position', 'code', ...)
	 *  @param  int			$showempty      	1=Add en empty line
	 *  @param  string      $morecss        	Add more css to select component
	 *  @param  int<0,1>  	$output         	0=return HTML, 1= direct print
	 *  @param	int<0,1>	$forcehidetooltip	Force hide tooltip for admin
	 *  @return	string|void						Depending on $output param, return the HTML select list (recommended method) or nothing
	 */
	public function selectTypeContact($object, $selected, $htmlname = 'type', $source = 'member', $sortorder = 'position', $showempty = 0, $morecss = '', $output = 1, $forcehidetooltip = 0)
	{
		dol_syslog(get_class($this)."::selectMemberForNewContact::object->element=".$object->element, LOG_DEBUG);
		global $user, $langs;

		$out = '';
		if (is_object($object) && method_exists($object, 'liste_type_contact')) {
			'@phan-var-force CommonObject $object';  // CommonObject has the method.
			$lesTypes = $object->liste_type_contact($source, $sortorder, 2, 1);	// List of types into c_type_contact for element=$object->element


			$out .= '<select class="flat valignmiddle' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '" id="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="0">&nbsp;</option>';
			}
			if (count($lesTypes)) {
				foreach ($lesTypes as $key => $arrayvalue) {
					$out .= '<option value="'.$key.'" data-code="'.$arrayvalue['code'].'"';
					if ($key == $selected) {
						$out .= ' selected';
					}
					$out .= '>'.$arrayvalue['label'].'</option>';
				}
			} else {
				$out .= '<option value="-1" data-code="nosuchmembercontactrole"';
				$out .= ' disabled';
				$out .= '>AskYourAdminToDefineContactRolesForMembers</option>';
			}
			$out .= "</select>";
			if ($user->admin && empty($forcehidetooltip)) {
				$out .= ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}

			$out .= ajax_combobox($htmlname);

			$out .= "\n";
		}
		if (empty($output)) {
			return $out;
		} else {
			print $out;
		}
	}
}
