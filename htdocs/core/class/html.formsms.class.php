<?php
/* Copyright (C) 2005-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
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
 *       \file       htdocs/core/class/html.formsms.class.php
 *       \ingroup    core
 *       \brief      Fichier de la class permettant la generation du formulaire html d'envoi de mail unitaire
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/**
 *      Class permettant la generation du formulaire d'envoi de Sms
 *      Usage: $formsms = new FormSms($db)
 *             $formsms->proprietes=1 ou chaine ou tableau de valeurs
 *             $formsms->show_form() affiche le formulaire
 */
class FormSms
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $fromid;
	public $fromname;
	public $fromsms;

	/**
	 * @var string
	 */
	public $fromtype;
	public $replytoname;
	public $replytomail;
	public $toname;
	public $tomail;

	public $withsubstit; // Show substitution array

	/**
	 * @var int
	 */
	public $withfrom;

	/**
	 * @var int
	 */
	public $withto;

	/**
	 * @var int
	 */
	public $withtopic;

	/**
	 * @var int
	 */
	public $withbody;

	/**
	 * @var int 	Id of company
	 */
	public $withtosocid;

	public $withfromreadonly;
	public $withreplytoreadonly;
	public $withtoreadonly;
	public $withtopicreadonly;
	public $withbodyreadonly;
	public $withcancel;

	public $substit = array();
	public $param = array();

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->withfrom = 1;
		$this->withto = 1;
		$this->withtopic = 1;
		$this->withbody = 1;

		$this->withfromreadonly = 1;
		$this->withreplytoreadonly = 1;
		$this->withtoreadonly = 0;
		$this->withtopicreadonly = 0;
		$this->withbodyreadonly = 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show the form to input an sms.
	 *
	 *	@param	string	$morecss Class on first column td
	 *  @param int $showform Show form tags and submit button (recommended is to use with value 0)
	 *	@return	void
	 */
	public function show_form($morecss = 'titlefield', $showform = 1)
	{
		// phpcs:enable
		global $conf, $langs, $form;

		if (!is_object($form)) {
			$form = new Form($this->db);
		}

		// Load translation files required by the page
		$langs->loadLangs(array('other', 'mails', 'sms'));

		$soc = new Societe($this->db);
		if (!empty($this->withtosocid) && $this->withtosocid > 0) {
			$soc->fetch($this->withtosocid);
		}

		print "\n<!-- Begin form SMS -->\n";

		print '
<script nonce="'.getNonce().'" type="text/javascript">
function limitChars(textarea, limit, infodiv)
{
    var text = textarea.value;
    var textlength = text.length;
    var info = document.getElementById(infodiv);

    info.innerHTML = (limit - textlength);
    return true;
}
</script>';

		if ($showform) {
			print "<form method=\"POST\" name=\"smsform\" enctype=\"multipart/form-data\" action=\"".$this->param["returnurl"]."\">\n";
		}

		print '<input type="hidden" name="token" value="'.newToken().'">';
		foreach ($this->param as $key => $value) {
			print "<input type=\"hidden\" name=\"$key\" value=\"$value\">\n";
		}
		print "<table class=\"border centpercent\">\n";

		// Substitution array
		if (!empty($this->withsubstit)) {		// Unset or set ->withsubstit=0 to disable this.
			print "<tr><td colspan=\"2\">";
			$help = "";
			foreach ($this->substit as $key => $val) {
				$help .= $key.' -> '.$langs->trans($val).'<br>';
			}
			print $form->textwithpicto($langs->trans("SmsTestSubstitutionReplacedByGenericValues"), $help);
			print "</td></tr>\n";
		}

		// From
		if ($this->withfrom) {
			if ($this->withfromreadonly) {
				print '<tr><td class="titlefield '.$morecss.'">'.$langs->trans("SmsFrom");
				print '<input type="hidden" name="fromsms" value="'.$this->fromsms.'">';
				print "</td><td>";
				if ($this->fromtype == 'user') {
					$langs->load("users");
					$fuser = new User($this->db);
					$fuser->fetch($this->fromid);
					print $fuser->getNomUrl(1);
					print ' &nbsp; ';
				}
				if ($this->fromsms) {
					print $this->fromsms;
				} else {
					if ($this->fromtype) {
						$langs->load("errors");
						print '<span class="warning"> &lt;'.$langs->trans("ErrorNoPhoneDefinedForThisUser").'&gt; </span>';
					}
				}
				print "</td></tr>\n";
				print "</td></tr>\n";
			} else {
				print '<tr><td class="'.$morecss.'">'.$langs->trans("SmsFrom")."</td><td>";
				if (getDolGlobalString('MAIN_SMS_SENDMODE')) {
					$sendmode = getDolGlobalString('MAIN_SMS_SENDMODE');	// $conf->global->MAIN_SMS_SENDMODE looks like a value 'module'
					$classmoduleofsender = getDolGlobalString('MAIN_MODULE_'.strtoupper($sendmode).'_SMS', $sendmode);	// $conf->global->MAIN_MODULE_XXX_SMS looks like a value 'class@module'
					if ($classmoduleofsender == 'ovh') {
						$classmoduleofsender = 'ovhsms@ovh';	// For backward compatibility
					}

					$tmp = explode('@', $classmoduleofsender);
					$classfile = $tmp[0];
					$module = (empty($tmp[1]) ? $tmp[0] : $tmp[1]);
					dol_include_once('/'.$module.'/class/'.$classfile.'.class.php');
					try {
						$classname = ucfirst($classfile);
						if (class_exists($classname)) {
							$sms = new $classname($this->db);
							$resultsender = $sms->SmsSenderList();
						} else {
							$sms = new stdClass();
							$sms->error = 'The SMS manager "'.$classfile.'" defined into SMS setup MAIN_MODULE_'.strtoupper($sendmode).'_SMS is not found';
						}
					} catch (Exception $e) {
						dol_print_error(null, 'Error to get list of senders: '.$e->getMessage());
						exit;
					}
				} else {
					dol_syslog("Warning: The SMS sending method has not been defined into MAIN_SMS_SENDMODE", LOG_WARNING);
					$resultsender = array();
					$resultsender[0]->number = $this->fromsms;
				}

				if (is_array($resultsender) && count($resultsender) > 0) {
					print '<select name="fromsms" id="fromsms" class="flat">';
					foreach ($resultsender as $obj) {
						print '<option value="'.$obj->number.'">'.$obj->number.'</option>';
					}
					print '</select>';
				} else {
					print '<span class="error wordbreak">'.$langs->trans("SmsNoPossibleSenderFound");
					if (is_object($sms) && !empty($sms->error)) {
						print ' '.$sms->error;
					}
					print '</span>';
				}
				print '</td>';
				print "</tr>\n";
			}
		}

		// To (target)
		if ($this->withto || is_array($this->withto)) {
			print '<tr><td>';
			//$moretext=$langs->trans("YouCanUseCommaSeparatorForSeveralRecipients");
			$moretext = '';
			print $form->textwithpicto($langs->trans("SmsTo"), $moretext);
			print '</td><td>';
			if ($this->withtoreadonly) {
				print (!is_array($this->withto) && !is_numeric($this->withto)) ? $this->withto : "";
			} else {
				print '<input class="width150" id="sendto" name="sendto" value="'.dol_escape_htmltag(!is_array($this->withto) && $this->withto != '1' ? (GETPOSTISSET("sendto") ? GETPOST("sendto") : $this->withto) : "+").'">';
				if (!empty($this->withtosocid) && $this->withtosocid > 0) {
					$liste = array();
					foreach ($soc->thirdparty_and_contact_phone_array() as $key => $value) {
						$liste[$key] = $value;
					}
					print " ".$langs->trans("or")." ";
					//var_dump($_REQUEST);exit;
					print $form->selectarray("receiver", $liste, GETPOST("receiver"), 1);
				}
				print '<span class="opacitymedium hideonsmartphone"> '.$langs->trans("SmsInfoNumero").'</span>';
			}
			print "</td></tr>\n";
		}

		// Message
		if ($this->withbody) {
			$defaultmessage = '';
			if ($this->param["models"] == 'body') {
				$defaultmessage = $this->withbody;
			}
			$defaultmessage = make_substitutions($defaultmessage, $this->substit);
			if (GETPOSTISSET("message")) {
				$defaultmessage = GETPOST("message", 'restricthtml');
			}
			$defaultmessage = str_replace('\n', "\n", $defaultmessage);

			print "<tr>";
			print '<td class="tdtop">'.$langs->trans("SmsText")."</td>";
			print "<td>";
			if ($this->withbodyreadonly) {
				print nl2br($defaultmessage);
				print '<input type="hidden" name="message" value="'.dol_escape_htmltag($defaultmessage).'">';
			} else {
				print '<textarea class="quatrevingtpercent" name="message" id="message" rows="'.ROWS_4.'" onkeyup="limitChars(this, 160, \'charlimitinfospan\')">'.$defaultmessage.'</textarea>';
				print '<div id="charlimitinfo" class="opacitymedium">'.$langs->trans("SmsInfoCharRemain").': <span id="charlimitinfospan">'.(160 - dol_strlen($defaultmessage)).'</span></div></td>';
			}
			print "</td></tr>\n";
		}

		print '
           <tr>
            <td>'.$langs->trans("DelayBeforeSending").':</td>
            <td> <input name="deferred" id="deferred" size="4" value="0"></td></tr>

           <tr><td>'.$langs->trans("Priority").' :</td><td>
           <select name="priority" id="priority" class="flat">
           <option value="0">high</option>
           <option value="1">medium</option>
           <option value="2" selected>low</option>
           <option value="3">veryLow</option>
           </select></td></tr>

           <tr><td>'.$langs->trans("Type").' :</td><td>
           <select name="class" id="class" class="flat">
           <option value="0">Flash</option>
           <option value="1" selected>Standard</option>
           <option value="2">SIM</option>
           <option value="3">ToolKit</option>
           </select></td></tr>

           <tr><td>'.$langs->trans("DisableStopIfSupported").' :</td><td>
           <select name="disablestop" id="disablestop" class="flat">
           <option value="0" selected>No</option>
           <option value="1" selected>Yes</option>
           </select></td></tr>';

		print "</table>\n";


		if ($showform) {
			print '<div class="center">';
			print '<input type="submit" class="button" name="sendmail" value="'.dol_escape_htmltag($langs->trans("SendSms")).'">';
			if ($this->withcancel) {
				print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				print '<input class="button button-cancel" type="submit" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
			}
			print '</div>';

			print "</form>\n";
		}

		print "<!-- End form SMS -->\n";
	}
}
