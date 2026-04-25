<?php
/* Copyright (C) 2014 Florian Henry florian.henry@open-concept.pro
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
 *	\file       htdocs/core/class/html.formmailing.class.php
 *  \ingroup    core
 *	\brief      File of predefined functions for HTML forms for mailing module
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

/**
 *  Class to offer components to list and upload files
 */
class FormMailing extends Form
{
	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();


	/**
	 * Output a select with destinaries status
	 *
	 * @param 	string  $selectedid     	The selected id
	 * @param 	string  $htmlname       	Name of controm
	 * @param 	integer $show_empty     	Show empty option
	 * @param	string	$morecss			More CSS
	 * @return 	string 						HTML select
	 */
	public function selectDestinariesStatus($selectedid = '', $htmlname = 'dest_status', $show_empty = 0, $morecss = 'minwidth75')
	{
		global $langs;

		$langs->load("mails");

		require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
		$mailing = new Mailing($this->db);

		$options = array();

		$options += $mailing->statut_dest;

		// Note -1 is used for error, so we use -2 for tempty value
		return Form::selectarray($htmlname, $options, $selectedid, ($show_empty ? -2 : 0), 0, 0, '', 1, 0, 0, '', $morecss);
	}

	/**
	 * Output a form with Multi Selection of Mass Mailings
	 *
	 * @param 	string 		$page 		Page - if empty no form tags are printed, else they are
	 * @param	string		$htmlname	Name of HTML field
	 * @param 	string 		$title 		Text above the multi select form., default is '' and not shown, could be a <h4>...</h4>
	 * @param	int[] 		$toplist 	List of mass mailing ids in top/main optgroup
	 * @param 	string 		$toptext 	Text in top/main optgroup (optional) - default is '' and not shown
	 * @param	int[] 		$endlist 	List of mass mailing ids in below optgroup (optional) - default array() - not used, not shown
	 * @param 	string 		$endtext 	Text in below optgroup (optional) - default is '' and not shown
	 * @param	int 		$size 		How high the table should be in lines, default is 16
	 * @param	string 		$morecss 	More css
	 * @param	int			$nooutput 	No print output. Return it only.
	 *
	 * @return 	string 					HTML
	 */
	public function formMultiSelectMassMailing($page, $htmlname, $title = '', $toplist = array(), $toptext = '', $endlist = array(), $endtext = '', $size = 16, $morecss = '', $nooutput = 0)
	{
		dol_syslog(__CLASS__.'::'.__METHOD__.'::', LOG_DEBUG);
		global $langs;
		$langs->load("mails");

		$out = '';
		if (!empty($page)) {
			$out .= '<form action="'.$page.'">';
		}
		if (!empty($title)) {
			$out .= $title;
		}

		$out .= '<div id="select_'.$htmlname.'">';
		$out .= '<select class="select" name="select_'.$htmlname.'[]" id="select_'.$htmlname.'" multiple size="'.$size.'" style="'.$morecss.'">';

		if (!empty($toptext)) {
			$out .= '  <optgroup class="optgroup" label="'.$toptext.'">';
		}
		require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
		$mailingstatic = new Mailing($this->db);
		foreach ($toplist as $mailingid) {
			$fmresult = $mailingstatic->fetch($mailingid);
			if ($fmresult) {
				$out .= '    <option class="option" value="'.$mailingstatic->id.'">'.$mailingstatic->title.'</option>';
			} else {
				dol_syslog(__CLASS__.'::'.__METHOD__.'::fetching mailing with id='.$mailingid.' failed with result='.$fmresult, LOG_ERR);
			}
		}
		$out .= '    <option class="option" value="" disabled>&mdash;&mdash;&mdash;</option>';
		if (!empty($toptext)) {
			$out .= '  </optgroup>';
		}

		if (!empty($endtext)) {
			$out .= '  <optgroup class="optgroup" label="'.$endtext.'">';
		}
		foreach ($endlist as $mailingid) {
			$fmresult = $mailingstatic->fetch($mailingid);
			if ($fmresult) {
				$out .= '    <option class="option" value="'.$mailingstatic->id.'">'.$mailingstatic->title.'</option>';
			} else {
				dol_syslog(__CLASS__.'::'.__METHOD__.'::fetching mailing with id='.$mailingid.' failed with result='.$fmresult, LOG_ERR);
			}
		}
		if (!empty($endtext)) {
			$out .= '  </optgroup>';
		}

		$out .= '</select>';
		$out .= '</div>';

		$out .= '<br>';

		$out .= '<input type="checkbox" id="verbosereporting" name="verbosereporting" value="1"><label for="verbosereporting">'.$langs->trans("Show").' '.$langs->trans("ListOf", $langs->trans("EMails")).'</label><br>';
		$out .= '<input type="checkbox" id="ignorenocontact" name="ignorenocontact" value="1"><label for="ignorenocontact">'.$langs->trans("EvenUnsubscribe").'</label><br>';
		$out .= '<label for="select_mailsrc">'.$langs->trans("Source").' '.$langs->trans("ListOf", $langs->trans("Email")).'</label><br><select class="select" name="select_mailsrc[]" id="select_mailsrc" size="5" multiple>';
		$out .= '    <option class="option" value="0" selected>'.$langs->trans("auto").'</option>';
		$out .= '    <option class="option" value="10">'.$langs->trans("Attendees").'</option>';
		$out .= '    <option class="option" value="20">'.$langs->trans("SearchIntoContacts").'</option>';
		$out .= '    <option class="option" value="30">'.$langs->trans("SearchIntoThirdparties").'</option>';
		$out .= '    <option class="option" value="40">'.$langs->trans("EmailCompany").'</option>';
		$out .= '</select>';

		$actionname = $htmlname;
		$out .= '<div id="massmail_selection_buttons_'.$htmlname.'"><br>';
		$out .= '<input type="hidden" name="massaction" value="confirm_premassmail">';
		$out .= '<!-- 3 buttons Add, Delete and Cancel -->';
		require_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
		$staticmailingtarget = new MailingTargets($this->db);
		$out .= '<input type="submit" class="butAction button-add small reposition" id="button_add_'.$actionname.'" name="button_add_'.$actionname.'" value="'.$langs->trans("Add").'">';
		$out .= '<input type="submit" class="butActionDelete button-delete small reposition" id="button_delete_'.$actionname.'" name="button_delete_'.$actionname.'" value="'.$langs->trans("Delete").'">';
		$out .= '<input type="submit" class="button button-cancel reposition" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'" />';
		$out .= '</div><br>';

		if (!empty($page)) {
			$out .= '</form>';
		}

		if (empty($nooutput)) {
			print $out;
			return '';
		} else {
			return $out;
		}
	}
}
