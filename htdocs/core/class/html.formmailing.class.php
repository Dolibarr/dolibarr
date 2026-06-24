<?php
/* Copyright (C) 2014 Florian Henry florian.henry@open-concept.pro
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026  		Jon Bendtsen            	<jon.bendtsen.github@jonb.dk>
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
		dol_syslog(__METHOD__.'::', LOG_DEBUG);
		global $langs;
		$langs->load("mails");

		$out = '';
		if (!empty($page)) {
			$out .= '<form action="'.$page.'">';
		}
		if (!empty($title)) {
			$out .= $title;
		}

		$out .= '<div id="select_mailing">';
		$out .= '<select class="select" name="select_mailing[]" id="select_mailing" multiple size="'.$size.'" style="'.$morecss.'">';

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
				dol_syslog(__METHOD__.'::fetching mailing with id='.$mailingid.' failed with result='.$fmresult, LOG_ERR);
			}
		}
		$out .= '    <option class="option" value="" disabled>&mdash;&mdash;&mdash;</option>';
		if (!empty($toptext)) {
			$out .= '  </optgroup>';
		}

		if (!empty($endtext) && count($endlist) > 0) {
			$out .= '  <optgroup class="optgroup" label="'.$endtext.'">';
		}
		foreach ($endlist as $mailingid) {
			$fmresult = $mailingstatic->fetch($mailingid);
			if ($fmresult) {
				$out .= '    <option class="option" value="'.$mailingstatic->id.'">'.$mailingstatic->title.'</option>';
			} else {
				dol_syslog(__METHOD__.'::fetching mailing with id='.$mailingid.' failed with result='.$fmresult, LOG_ERR);
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
		$out .= '<br>';

		$transThirdPartyEmail = $langs->trans("ThirdPartyEmail");
		$transContactForInvoices = $langs->trans("ContactForInvoices");

		$out .= '<!-- select and options for '.$htmlname .' -->';
		if ($htmlname == 'conferenceorboothattendee' ) {
			$out .= '<label for="select_mailsrc">'.$langs->trans("Source").' '.$langs->trans("ListOf", $langs->trans("Email")).'</label>';
			$out .= '<select class="select" name="select_mailsrc[]" id="select_mailsrc" size="8" multiple>';
			$out .= '    <option class="option" value="0" selected>'.$langs->trans("auto").'</option>';
			$out .= '    <option class="option" value="10">'.$langs->trans("Attendees").'</option>';
			$out .= '    <option class="option" value="20">'.$langs->trans("SearchIntoContacts").'</option>';
			$out .= '    <option class="option" value="30">'.$langs->trans("SearchIntoThirdparties").'</option>';
			$out .= '    <option class="option" value="40">'.$langs->trans("EmailCompany").'</option>';
			$out .= '</select>';
		} elseif ($htmlname == 'facture' ) {
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$facturestatic = new Facture($this->db);
			$contacttypes = $facturestatic->liste_type_contact('external', 'rowid', 1, 1) ?? array();
			$size = (int) round(3 + (log(count($contacttypes)) > 0 ? log(count($contacttypes)) : 0));

			$out .= '<h4>'.$langs->trans("Email").' '.$langs->trans("Sources").'</h4>';
			$out .= '<div id="checkbox_ThirdPartyEmail">';
			$out .= '<input type="checkbox" id="ThirdPartyEmail" name="ThirdPartyEmail" value="1"><label for="ThirdPartyEmail">'.$transThirdPartyEmail.'</label><br>';
			$out .= '</div>';
			$out .= '<br>';

			$out .= '<div id="select_contactsrc">';
			$out .= '<b><label for="select_contactsrc">'.$transContactForInvoices.'</label></b><br>';
			$out .= '<select class="select" name="select_contactsrc[]" id="select_contactsrc" size="'.$size.'" multiple>';
			foreach ($contacttypes as $code => $label) {
				$out .= '    <option class="option" value="'.$code.'">'.$langs->trans($label).'</option>';
			}
			$out .= '</select>';
			$out .= '</div>';
			$out .= '<br>';

			$out .= '<div id="showerrors">';
			$out .= '<label for="showerrors">'.$langs->trans("ShowOnVCard", $langs->trans("errors")).'</label><br>';
			$out .= '<select class="select" name="showerrors[]" id="showerrors" size="3" multiple>';
			$out .= '    <option class="option" value="ThirdPartyEmail" selected>'.$transThirdPartyEmail.'</option>';
			$out .= '    <option class="option" value="ContactForInvoices" selected>'.$transContactForInvoices.'</option>';
			$out .= '</select>';
			$out .= '</div>';
		} else {
			$out .= '<label for="select_mailsrc">'.$langs->trans("Email").' '.$langs->trans("Sources").'</label><br>';
			$out .= '<select class="select" name="select_mailsrc[]" id="select_mailsrc" size="8" multiple>';
			$out .= '    <option class="option" value="ThirdPartyEmail">'.$transThirdPartyEmail.'</option>';
			$out .= '    <option class="option" value="ContactForInvoices">'.$transContactForInvoices.'</option>';
			$out .= '</select>';
		}

		$out .= '<div id="massmail_selection_buttons_mailing"><br>';
		$out .= '<input type="hidden" name="massaction" value="confirm_premassmail">';
		$out .= '<!-- 3 buttons Add, Delete and Cancel -->';
		$out .= '<input type="submit" class="butAction button-add small reposition" id="button_add_mailing" name="button_add_mailing" value="'.$langs->trans("Add").'">';
		$out .= '<input type="submit" class="butActionDelete button-delete small reposition" id="button_delete_mailing" name="button_delete_mailing" value="'.$langs->trans("Delete").'">';
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
