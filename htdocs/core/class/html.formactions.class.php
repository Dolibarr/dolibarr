<?php
/* Copyright (c) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2018 Juanjo Menent        <jmenent@2byte.es>
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
 *      \file       htdocs/core/class/html.formactions.class.php
 *      \ingroup    core
 *      \brief      File of class with predefined functions and HTML components
 */


/**
 *      Class to manage building of HTML components
 */
class FormActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 *	Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show list of action status
	 *
	 * 	@param	string	$formname		Name of form where select is included
	 * 	@param	string	$selected		Preselected value (-1..100)
	 * 	@param	int		$canedit		1=can edit, 0=read only
	 *  @param  string	$htmlname   	Name of html prefix for html fields (selectX and valX)
	 *  @param	integer	$showempty		Show an empty line if select is used
	 *  @param	integer	$onlyselect		0=Standard, 1=Hide percent of completion and force usage of a select list, 2=Same than 1 and add "Incomplete (Todo+Running)
	 *  @param  string  $morecss        More css on select field
	 * 	@return	void
	 */
	public function form_select_status_action($formname, $selected, $canedit = 1, $htmlname = 'complete', $showempty = 0, $onlyselect = 0, $morecss = 'maxwidth100')
	{
		// phpcs:enable
		global $langs, $conf;

		$listofstatus = array(
			'na' => $langs->trans("ActionNotApplicable"),
			'0' => $langs->trans("ActionsToDoShort"),
			'50' => $langs->trans("ActionRunningShort"),
			'100' => $langs->trans("ActionDoneShort")
		);
		// +ActionUncomplete

		if (!empty($conf->use_javascript_ajax) || $onlyselect) {
			//var_dump($selected);
			if ($selected == 'done') {
				$selected = '100';
			}
			print '<select '.($canedit ? '' : 'disabled ').'name="'.$htmlname.'" id="select'.$htmlname.'" class="flat'.($morecss ? ' '.$morecss : '').'">';
			if ($showempty) {
				print '<option value="-1"'.($selected == '' ? ' selected' : '').'>&nbsp;</option>';
			}
			foreach ($listofstatus as $key => $val) {
				print '<option value="'.$key.'"'.(($selected == $key && strlen($selected) == strlen($key)) || (($selected > 0 && $selected < 100) && $key == '50') ? ' selected' : '').'>'.$val.'</option>';
				if ($key == '50' && $onlyselect == 2) {
					print '<option value="todo"'.($selected == 'todo' ? ' selected' : '').'>'.$langs->trans("ActionUncomplete").' ('.$langs->trans("ActionsToDoShort")."+".$langs->trans("ActionRunningShort").')</option>';
				}
			}
			print '</select>';
			if ($selected == 0 || $selected == 100) {
				$canedit = 0;
			}

			print ajax_combobox('select'.$htmlname, array(), 0, 0, 'resolve', '-1', $morecss);

			if (empty($onlyselect)) {
				print ' <input type="text" id="val'.$htmlname.'" name="percentage" class="flat hideifna" value="'.($selected >= 0 ? $selected : '').'" size="2"'.($canedit && ($selected >= 0) ? '' : ' disabled').'>';
				print '<span class="hideonsmartphone hideifna">%</span>';
			}
		} else {
			print ' <input type="text" id="val'.$htmlname.'" name="percentage" class="flat" value="'.($selected >= 0 ? $selected : '').'" size="2"'.($canedit ? '' : ' disabled').'>%';
		}

		if (!empty($conf->use_javascript_ajax)) {
			print "\n";
			print '<script nonce="'.getNonce().'" type="text/javascript">';
			print "
                var htmlname = '".dol_escape_js($htmlname)."';

                $(document).ready(function () {
                	select_status();

                    $('#select' + htmlname).change(function() {
						console.log('We change field select '+htmlname);
                        select_status();
                    });
                });

                function select_status() {
                    var defaultvalue = $('#select' + htmlname).val();
					console.log('val='+defaultvalue);
                    var percentage = $('input[name=percentage]');
                    var selected = '".(isset($selected) ? dol_escape_js($selected) : '')."';
                    var value = (selected>0?selected:(defaultvalue>=0?defaultvalue:''));

                    percentage.val(value);

                    if (defaultvalue == 'na' || defaultvalue == -1) {
						percentage.prop('disabled', true);
                        $('.hideifna').hide();
                    }
                    else if (defaultvalue == 0) {
						percentage.val(0);
						percentage.removeAttr('disabled'); /* Not disabled, we want to change it to higher value */
                        $('.hideifna').show();
                    }
                    else if (defaultvalue == 100) {
						percentage.val(100);
						percentage.prop('disabled', true);
                        $('.hideifna').show();
                    }
                    else {
                    	if (defaultvalue == 50 && (percentage.val() == 0 || percentage.val() == 100)) { percentage.val(50); }
                    	percentage.removeAttr('disabled');
                        $('.hideifna').show();
                    }
                }
                </script>\n";
		}
	}


	/**
	 *  Show list of actions for element
	 *
	 *  @param	Object	$object					Object
	 *  @param  string	$typeelement			'invoice', 'propal', 'order', 'invoice_supplier', 'order_supplier', 'fichinter'
	 *	@param	int		$socid					Socid of user
	 *  @param	int		$forceshowtitle			Show title even if there is no actions to show
	 *  @param  string  $morecss        		More css on table
	 *  @param	int		$max					Max number of record
	 *  @param	string	$moreparambacktopage	More param for the backtopage
	 *  @param	string	$morehtmlcenter			More html text on center of title line
	 *  @param	int		$assignedtouser			Assign event by default to this user id (will be ignored if not enough permissions)
	 *	@return	int								Return integer <0 if KO, >=0 if OK
	 */
	public function showactions($object, $typeelement, $socid = 0, $forceshowtitle = 0, $morecss = 'listactions', $max = 0, $moreparambacktopage = '', $morehtmlcenter = '', $assignedtouser = 0)
	{
		global $langs, $user, $hookmanager;

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

		$sortfield = 'a.datep,a.id';
		$sortorder = 'DESC,DESC';

		$actioncomm = new ActionComm($this->db);
		$listofactions = $actioncomm->getActions($socid, $object->id, $typeelement, '', $sortfield, $sortorder, ($max ? ($max + 1) : 0));
		if (!is_array($listofactions)) {
			dol_print_error($this->db, 'FailedToGetActions');
		}

		$num = count($listofactions);
		if ($num || $forceshowtitle) {
			$title = $langs->trans("LatestLinkedEvents", $max ? $max : '');

			$urlbacktopage = $_SERVER['PHP_SELF'].'?id='.$object->id.($moreparambacktopage ? '&'.$moreparambacktopage : '');

			$projectid = $object->fk_project;
			if ($typeelement == 'project') {
				$projectid = $object->id;
			}
			$taskid = 0;
			if ($typeelement == 'task') {
				$taskid = $object->id;
			}

			$usercanaddaction = 0;
			if (empty($assignedtouser) || $assignedtouser == $user->id) {
				$usercanaddaction = $user->hasRight('agenda', 'myactions', 'create');
				$assignedtouser = 0;
			} else {
				$usercanaddaction = $user->hasRight('agenda', 'allactions', 'create');
			}

			$url = '';
			$morehtmlright = '';
			if (isModEnabled('agenda') && $usercanaddaction) {
				$url = DOL_URL_ROOT.'/comm/action/card.php?action=create&token='.newToken().'&datep='.urlencode(dol_print_date(dol_now(), 'dayhourlog', 'tzuser'));
				$url .= '&origin='.urlencode($typeelement).'&originid='.((int) $object->id).((!empty($object->socid) && $object->socid > 0) ? '&socid='.((int) $object->socid) : ((!empty($socid) && $socid > 0) ? '&socid='.((int) $socid) : ''));
				$url .= ($projectid > 0 ? '&projectid='.((int) $projectid) : '').($taskid > 0 ? '&taskid='.((int) $taskid) : '');
				$url .= ($assignedtouser > 0 ? '&assignedtouser='.((int) $assignedtouser) : '');
				$url .= '&backtopage='.urlencode($urlbacktopage);
				$morehtmlright .= dolGetButtonTitle($langs->trans("AddEvent"), '', 'fa fa-plus-circle', $url);
			}

			$parameters = array(
				'title' => &$title,
				'morehtmlright' => &$morehtmlright,
				'morehtmlcenter' => &$morehtmlcenter,
				'usercanaddaction' => $usercanaddaction,
				'url' => &$url,
				'typeelement' => $typeelement,
				'projectid' => $projectid,
				'assignedtouser' => $assignedtouser,
				'taskid' => $taskid,
				'urlbacktopage' => $urlbacktopage
			);

			$reshook = $hookmanager->executeHooks('showActionsLoadFicheTitre', $parameters, $object);

			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}

			$error = 0;
			if (empty($reshook)) {
				print '<!-- formactions->showactions -->' . "\n";
				print load_fiche_titre($title, $morehtmlright, '', 0, 0, '', $morehtmlcenter);
			}

			$page = 0;
			$param = '';

			print '<div class="div-table-responsive-no-min">';
			print '<table class="centpercent noborder'.($morecss ? ' '.$morecss : '').'">';
			print '<tr class="liste_titre">';
			print getTitleFieldOfList('Ref', 0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, '', 1);
			print getTitleFieldOfList('Date', 0, $_SERVER["PHP_SELF"], 'a.datep', $page, $param, '', $sortfield, $sortorder, 'center ', 1);
			print getTitleFieldOfList('By', 0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, '', 1);
			print getTitleFieldOfList('Type', 0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, '', 1);
			print getTitleFieldOfList('Title', 0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, '', 1);
			print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, 'right ', 1);
			print '</tr>';
			print "\n";

			if (is_array($listofactions) && count($listofactions)) {
				$cacheusers = array();

				$cursorevent = 0;
				foreach ($listofactions as $actioncomm) {
					if ($max && $cursorevent >= $max) {
						break;
					}

					print '<tr class="oddeven">';

					// Ref
					print '<td class="nowraponall nopaddingrightimp">'.$actioncomm->getNomUrl(1, -1).'</td>';

					// Date
					print '<td class="center nowraponall">'.dol_print_date($actioncomm->datep, 'dayhourreduceformat', 'tzuserrel');
					if ($actioncomm->datef) {
						$tmpa = dol_getdate($actioncomm->datep);
						$tmpb = dol_getdate($actioncomm->datef);
						if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
							if ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes']) {
								print '-'.dol_print_date($actioncomm->datef, 'hour', 'tzuserrel');
							}
						} else {
							print '-'.dol_print_date($actioncomm->datef, 'dayhourreduceformat', 'tzuserrel');
						}
					}
					print '</td>';

					// Owner
					print '<td class="nowraponall tdoverflowmax100">';
					if (!empty($actioncomm->userownerid)) {
						if (isset($cacheusers[$actioncomm->userownerid]) && is_object($cacheusers[$actioncomm->userownerid])) {
							$tmpuser = $cacheusers[$actioncomm->userownerid];
						} else {
							$tmpuser = new User($this->db);
							$tmpuser->fetch($actioncomm->userownerid);
							$cacheusers[$actioncomm->userownerid] = $tmpuser;
						}
						if ($tmpuser->id > 0) {
							print $tmpuser->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
						}
					}
					print '</td>';

					// Example: Email sent from invoice card
					//$actionstatic->code = 'AC_BILL_SENTBYMAIL
					//$actionstatic->type_code = 'AC_OTHER_AUTO'

					// Type
					$labeltype = $actioncomm->getTypeLabel(0);
					print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($labeltype).'">';
					print $actioncomm->getTypePicto();
					print $labeltype;
					print '</td>';

					// Label
					print '<td class="tdoverflowmax250">';
					print $actioncomm->getNomUrl(0);
					print '</td>';

					// Status
					print '<td class="right">';
					print $actioncomm->getLibStatut(3);
					print '</td>';
					print '</tr>';

					$cursorevent++;
				}
			} else {
				print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}

			if ($max && $num > $max) {
				print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">'.$langs->trans("More").'...</span></td></tr>';
			}

			print '</table>';
			print '</div>';
		}

		return $num;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Output html select list of type of event
	 *
	 *  @param	array|string	$selected       Type pre-selected (can be 'manual', 'auto' or 'AC_xxx'). Can be an array too.
	 *  @param  string		    $htmlname       Name of select field
	 *  @param	string		    $excludetype	A type to exclude ('systemauto', 'system', '')
	 *  @param	integer		    $onlyautoornot	1=Group all type AC_XXX into 1 line AC_MANUAL. 0=Keep details of type, -1=Keep details and add a combined line "All manual", -2=Combined line is disabled (not implemented yet)
	 *  @param	int		        $hideinfohelp	1=Do not show info help, 0=Show, -1=Show+Add info to tell how to set default value
	 *  @param  int		        $multiselect    1=Allow multiselect of action type
	 *  @param  int             $nooutput       1=No output
	 *  @param	string			$morecss		More css to add to SELECT component.
	 * 	@return	string
	 */
	public function select_type_actions($selected = '', $htmlname = 'actioncode', $excludetype = '', $onlyautoornot = 0, $hideinfohelp = 0, $multiselect = 0, $nooutput = 0, $morecss = 'minwidth300')
	{
		// phpcs:enable
		global $langs, $user, $form;

		if (!is_object($form)) {
			$form = new Form($this->db);
		}

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		$caction = new CActionComm($this->db);

		// Suggest a list with manual events or all auto events
		$arraylist = $caction->liste_array(1, 'code', $excludetype, $onlyautoornot, '', 0);		// If we use param 'all' instead of 'code', there is no group by include in answer but the key 'type' of answer array contains the key for the group by.
		if (empty($multiselect)) {
			// Add empty line at start only if no multiselect
			array_unshift($arraylist, '&nbsp;');
		}
		//asort($arraylist);

		if ($selected == 'manual') {
			$selected = 'AC_OTH';
		}
		if ($selected == 'auto') {
			$selected = 'AC_OTH_AUTO';
		}

		if (getDolGlobalString('AGENDA_ALWAYS_HIDE_AUTO')) {
			unset($arraylist['AC_OTH_AUTO']);
		}

		$out = '';

		// Reformat the array
		$newarraylist = array();
		foreach ($arraylist as $key => $value) {
			$disabled = '';
			if (strpos($key, 'AC_ALL_') !== false && strpos($key, 'AC_ALL_AUTO') === false) {
				$disabled = 'disabled';
			}
			$newarraylist[$key] = array('id' => $key, 'label' => $value, 'disabled' => $disabled);
		}

		if (!empty($multiselect)) {
			if (!is_array($selected) && !empty($selected)) {
				$selected = explode(',', $selected);
			}
			$out .= $form->multiselectarray($htmlname, $newarraylist, $selected, 0, 0, 'centpercent', 0, 0);
		} else {
			$out .= $form->selectarray($htmlname, $newarraylist, $selected, 0, 0, 0, '', 0, 0, 0, '', $morecss, 1);
		}

		if ($user->admin && empty($onlyautoornot) && $hideinfohelp <= 0) {
			$out .= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup").($hideinfohelp == -1 ? ". ".$langs->trans("YouCanSetDefaultValueInModuleSetup") : ''), 1);
		}

		if ($nooutput) {
			return $out;
		} else {
			print $out;
		}
		return '';
	}
}
