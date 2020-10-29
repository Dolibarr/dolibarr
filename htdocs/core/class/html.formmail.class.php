<?php
/* Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin	    <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015-2017 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015-2017 Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2018-2019  Frédéric France     <frederic.france@netlogic.fr>
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
 *       \file       htdocs/core/class/html.formmail.class.php
 *       \ingroup    core
 *       \brief      Fichier de la classe permettant la generation du formulaire html d'envoi de mail unitaire
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/**
 *      Classe permettant la generation du formulaire html d'envoi de mail unitaire
 *      Usage: $formail = new FormMail($db)
 *             $formmail->proprietes=1 ou chaine ou tableau de valeurs
 *             $formmail->show_form() affiche le formulaire
 */
class FormMail extends Form
{
	/**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var int 1 = Include HTML form tag and show submit button
     *          0 = Do not include form tag and submit button
     *          -1 = Do not include form tag but include submit button
     */
	public $withform;

    /**
     * @var string name from
     */
	public $fromname;

    /**
     * @var string email from
     */
	public $frommail;

    /**
     * @var string user, company, robot
     */
    public $fromtype;

    /**
     * @var int from ID
     */
    public $fromid;

    /**
     * @var int also from robot
     */
    public $fromalsorobot;

    /**
     * @var string thirdparty etc
     */
    public $totype;

    /**
     * @var int ID
     */
    public $toid;

    /**
     * @var string replyto name
     */
    public $replytoname;

    /**
     * @var string replyto email
     */
	public $replytomail;

    /**
     * @var string to name
     */
	public $toname;

    /**
     * @var string to email
     */
	public $tomail;

    /**
     * @var string trackid
     */
	public $trackid;

	public $withsubstit; // Show substitution array
	public $withfrom;

	/**
	 * @var int
	 * @deprecated Fill withto with array before calling method.
	 * @see $withto
	 */
	public $withtosocid;

	/**
	 * @var int|int[]
	 */
	public $withto; // Show recipient emails

	public $withtofree; // Show free text for recipient emails
	public $withtocc;
	public $withtoccc;
	public $withtopic;
	public $withfile; // 0=No attaches files, 1=Show attached files, 2=Can add new attached files
	public $withmaindocfile; // 1=Add a checkbox "Attach also main document" for mass actions (checked by default), -1=Add checkbox (not checked by default)
	public $withbody;

	public $withfromreadonly;
	public $withreplytoreadonly;
	public $withtoreadonly;
	public $withtoccreadonly;
	public $withtocccreadonly;
	public $withtopicreadonly;
	public $withfilereadonly;
	public $withdeliveryreceipt;
	public $withcancel;
	public $withfckeditor;

	public $substit = array();
	public $substit_lines = array();
	public $param = array();

	public $withtouser = array();
	public $withtoccuser = array();

	public $lines_model;


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->withform = 1;

		$this->withfrom = 1;
		$this->withto = 1;
		$this->withtofree = 1;
		$this->withtocc = 1;
		$this->withtoccc = 0;
		$this->witherrorsto = 0;
		$this->withtopic = 1;
		$this->withfile = 0; // 1=Add section "Attached files". 2=Can add files.
		$this->withmaindocfile = 0; // 1=Add a checkbox "Attach also main document" for mass actions (checked by default), -1=Add checkbox (not checked by default)
		$this->withbody = 1;

		$this->withfromreadonly = 1;
		$this->withreplytoreadonly = 1;
		$this->withtoreadonly = 0;
		$this->withtoccreadonly = 0;
		$this->withtocccreadonly = 0;
		$this->witherrorstoreadonly = 0;
		$this->withtopicreadonly = 0;
		$this->withfilereadonly = 0;
		$this->withbodyreadonly = 0;
		$this->withdeliveryreceiptreadonly = 0;
		$this->withfckeditor = -1; // -1 = Auto
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
		if (is_dir($upload_dir)) dol_delete_dir_recursive($upload_dir);

		$keytoavoidconflict = empty($this->trackid) ? '' : '-'.$this->trackid; // this->trackid must be defined
		unset($_SESSION["listofpaths".$keytoavoidconflict]);
		unset($_SESSION["listofnames".$keytoavoidconflict]);
		unset($_SESSION["listofmimes".$keytoavoidconflict]);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Add a file into the list of attached files (stored in SECTION array)
	 *
	 * @param 	string   $path   Full absolute path on filesystem of file, including file name
	 * @param 	string   $file   Only filename (can be basename($path))
	 * @param 	string   $type   Mime type (can be dol_mimetype($file))
	 * @return	void
	 */
	public function add_attached_files($path, $file = '', $type = '')
	{
        // phpcs:enable
		$listofpaths = array();
		$listofnames = array();
		$listofmimes = array();

		if (empty($file)) $file = basename($path);
		if (empty($type)) $type = dol_mimetype($file);

		$keytoavoidconflict = empty($this->trackid) ? '' : '-'.$this->trackid; // this->trackid must be defined
		if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
		if (!empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
		if (!empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes = explode(';', $_SESSION["listofmimes".$keytoavoidconflict]);
		if (!in_array($file, $listofnames))
		{
			$listofpaths[] = $path;
			$listofnames[] = $file;
			$listofmimes[] = $type;
			$_SESSION["listofpaths".$keytoavoidconflict] = join(';', $listofpaths);
			$_SESSION["listofnames".$keytoavoidconflict] = join(';', $listofnames);
			$_SESSION["listofmimes".$keytoavoidconflict] = join(';', $listofmimes);
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Remove a file from the list of attached files (stored in SECTION array)
	 *
	 * @param  	string	$keytodelete     Key index in file array (0, 1, 2, ...)
	 * @return	void
	 */
	public function remove_attached_files($keytodelete)
	{
        // phpcs:enable
		$listofpaths = array();
		$listofnames = array();
		$listofmimes = array();

		$keytoavoidconflict = empty($this->trackid) ? '' : '-'.$this->trackid; // this->trackid must be defined
		if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
		if (!empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
		if (!empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes = explode(';', $_SESSION["listofmimes".$keytoavoidconflict]);
		if ($keytodelete >= 0)
		{
			unset($listofpaths[$keytodelete]);
			unset($listofnames[$keytodelete]);
			unset($listofmimes[$keytodelete]);
			$_SESSION["listofpaths".$keytoavoidconflict] = join(';', $listofpaths);
			$_SESSION["listofnames".$keytoavoidconflict] = join(';', $listofnames);
			$_SESSION["listofmimes".$keytoavoidconflict] = join(';', $listofmimes);
			//var_dump($_SESSION['listofpaths']);
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of attached files (stored in SECTION array)
	 *
	 * @return	array       array('paths'=> ,'names'=>, 'mimes'=> )
	 */
	public function get_attached_files()
	{
        // phpcs:enable
		$listofpaths = array();
		$listofnames = array();
		$listofmimes = array();

		$keytoavoidconflict = empty($this->trackid) ? '' : '-'.$this->trackid; // this->trackid must be defined
		if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
		if (!empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
		if (!empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes = explode(';', $_SESSION["listofmimes".$keytoavoidconflict]);
		return array('paths'=>$listofpaths, 'names'=>$listofnames, 'mimes'=>$listofmimes);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show the form to input an email
	 *  this->withfile: 0=No attaches files, 1=Show attached files, 2=Can add new attached files
	 *  this->withmaindocfile
	 *
	 *	@param	string	$addfileaction		Name of action when posting file attachments
	 *	@param	string	$removefileaction	Name of action when removing file attachments
	 *	@return	void
	 */
	public function show_form($addfileaction = 'addfile', $removefileaction = 'removefile')
	{
        // phpcs:enable
		print $this->get_form($addfileaction, $removefileaction);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Get the form to input an email
	 *  this->withfile: 0=No attaches files, 1=Show attached files, 2=Can add new attached files
	 *  this->withfile
	 *  this->param:	Contains more parameters like email templates info
	 *
	 *	@param	string	$addfileaction		Name of action when posting file attachments
	 *	@param	string	$removefileaction	Name of action when removing file attachments
	 *	@return string						Form to show
	 */
	public function get_form($addfileaction = 'addfile', $removefileaction = 'removefile')
	{
        // phpcs:enable
		global $conf, $langs, $user, $hookmanager, $form;

		if (!is_object($form)) $form = new Form($this->db);

		// Load translation files required by the page
        $langs->loadLangs(array('other', 'mails'));

		// Clear temp files. Must be done at beginning, before call of triggers
		if (GETPOST('mode', 'alpha') == 'init' || (GETPOST('modelmailselected', 'alpha') && GETPOST('modelmailselected', 'alpha') != '-1'))
		{
			$this->clear_attached_files();
		}

		// Call hook getFormMail
		$hookmanager->initHooks(array('formmail'));

		$parameters = array(
			'addfileaction' => $addfileaction,
			'removefileaction'=> $removefileaction,
			'trackid'=> $this->trackid
		);
		$reshook = $hookmanager->executeHooks('getFormMail', $parameters, $this);

		if (!empty($reshook))
		{
			return $hookmanager->resPrint;
		}
		else
		{
			$out = '';

			$disablebademails = 1;

			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $this->param['langsmodels'];
			if (!empty($newlang))
			{
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
				$outputlangs->load('other');
			}

			// Get message template for $this->param["models"] into c_email_templates
			$arraydefaultmessage = -1;
			if ($this->param['models'] != 'none')
			{
				$model_id = 0;
				if (array_key_exists('models_id', $this->param))
				{
					$model_id = $this->param["models_id"];
				}

				$arraydefaultmessage = $this->getEMailTemplate($this->db, $this->param["models"], $user, $outputlangs, $model_id); // If $model_id is empty, preselect the first one
			}

			// Define list of attached files
			$listofpaths = array();
			$listofnames = array();
			$listofmimes = array();
			$keytoavoidconflict = empty($this->trackid) ? '' : '-'.$this->trackid; // this->trackid must be defined

			if (GETPOST('mode', 'alpha') == 'init' || (GETPOST('modelmailselected', 'alpha') && GETPOST('modelmailselected', 'alpha') != '-1'))
			{
				if (!empty($arraydefaultmessage->joinfiles) && is_array($this->param['fileinit']))
				{
					foreach ($this->param['fileinit'] as $file)
					{
						$this->add_attached_files($file, basename($file), dol_mimetype($file));
					}
				}
			}

	   		if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
	   		if (!empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
	   		if (!empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes = explode(';', $_SESSION["listofmimes".$keytoavoidconflict]);


			$out .= "\n".'<!-- Begin form mail type='.$this->param["models"].' --><div id="mailformdiv"></div>'."\n";
			if ($this->withform == 1)
			{
				$out .= '<form method="POST" name="mailform" id="mailform" enctype="multipart/form-data" action="'.$this->param["returnurl"].'#formmail">'."\n";

				$out .= '<a id="formmail" name="formmail"></a>';
				$out .= '<input style="display:none" type="submit" id="sendmail" name="sendmail">';
				$out .= '<input type="hidden" name="token" value="'.newToken().'" />';
				$out .= '<input type="hidden" name="trackid" value="'.$this->trackid.'" />';
			}
			if (!empty($this->withfrom))
			{
				if (!empty($this->withfromreadonly))
				{
					$out .= '<input type="hidden" id="fromname" name="fromname" value="'.$this->fromname.'" />';
					$out .= '<input type="hidden" id="frommail" name="frommail" value="'.$this->frommail.'" />';
				}
			}
			foreach ($this->param as $key=>$value)
			{
				$out .= '<input type="hidden" id="'.$key.'" name="'.$key.'" value="'.$value.'" />'."\n";
			}

			$modelmail_array = array();
			if ($this->param['models'] != 'none')
			{
				$result = $this->fetchAllEMailTemplate($this->param["models"], $user, $outputlangs);

				if ($result < 0)
				{
					setEventMessages($this->error, $this->errors, 'errors');
				}
				foreach ($this->lines_model as $line)
				{
					$langs->trans("members");
					if (preg_match('/\((.*)\)/', $line->label, $reg))
					{
						$modelmail_array[$line->id] = $langs->trans($reg[1]); // langs->trans when label is __(xxx)__
					}
					else
					{
						$modelmail_array[$line->id] = $line->label;
					}
					if ($line->lang) $modelmail_array[$line->id] .= ' ('.$line->lang.')';
					if ($line->private) $modelmail_array[$line->id] .= ' - '.$langs->trans("Private");
					//if ($line->fk_user != $user->id) $modelmail_array[$line->id].=' - '.$langs->trans("By").' ';
				}
			}

			// Zone to select email template
			if (count($modelmail_array) > 0)
			{
				// If list of template is filled
				$out .= '<div class="center" style="padding: 0px 0 12px 0">'."\n";
				$out .= '<span class="opacitymedium">'.$langs->trans('SelectMailModel').':</span> '.$this->selectarray('modelmailselected', $modelmail_array, 0, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100');
				if ($user->admin) $out .= info_admin($langs->trans("YouCanChangeValuesForThisListFrom", $langs->transnoentitiesnoconv('Setup').' - '.$langs->transnoentitiesnoconv('EMails')), 1);
				$out .= ' &nbsp; ';
				$out .= '<input class="button" type="submit" value="'.$langs->trans('Apply').'" name="modelselected" id="modelselected">';
				$out .= ' &nbsp; ';
				$out .= '</div>';
			} elseif (!empty($this->param['models']) && in_array($this->param['models'], array(
					'propal_send', 'order_send', 'facture_send',
					'shipping_send', 'fichinter_send', 'supplier_proposal_send', 'order_supplier_send',
					'invoice_supplier_send', 'thirdparty', 'contract', 'user', 'all'
                )))
			{
				// If list of template is empty
				$out .= '<div class="center" style="padding: 0px 0 12px 0">'."\n";
				$out .= $langs->trans('SelectMailModel').': <select name="modelmailselected" disabled="disabled"><option value="none">'.$langs->trans("NoTemplateDefined").'</option></select>'; // Do not put 'disabled' on 'option' tag, it is already on 'select' and it makes chrome crazy.
				if ($user->admin) $out .= info_admin($langs->trans("YouCanChangeValuesForThisListFrom", $langs->transnoentitiesnoconv('Setup').' - '.$langs->transnoentitiesnoconv('EMails')), 1);
				$out .= ' &nbsp; ';
				$out .= '<input class="button" type="submit" value="'.$langs->trans('Apply').'" name="modelselected" disabled="disabled" id="modelselected">';
				$out .= ' &nbsp; ';
				$out .= '</div>';
			}



			$out .= '<table class="tableforemailform boxtablenotop" width="100%">'."\n";

			// Substitution array/string
			$helpforsubstitution = '';
			if (is_array($this->substit) && count($this->substit)) $helpforsubstitution .= $langs->trans('AvailableVariables').' :<br>'."\n";
			foreach ($this->substit as $key => $val)
			{
				$helpforsubstitution .= $key.' -> '.$langs->trans(dol_string_nohtmltag($val)).'<br>';
			}
			if (!empty($this->withsubstit))		// Unset or set ->withsubstit=0 to disable this.
			{
				$out .= '<tr><td colspan="2" class="right">';
				//$out.='<div class="floatright">';
				if (is_numeric($this->withsubstit)) $out .= $form->textwithpicto($langs->trans("EMailTestSubstitutionReplacedByGenericValues"), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltip'); // Old usage
				else $out .= $form->textwithpicto($langs->trans('AvailableVariables'), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltip'); // New usage
				$out .= "</td></tr>\n";
				//$out.='</div>';
			}

			/*var_dump(! empty($this->withfromreadonly));
			var_dump($this->withfrom);
			var_dump($this->fromtype);
			var_dump($this->fromname);*/

			// From
			if (!empty($this->withfrom))
			{
				if (!empty($this->withfromreadonly))
				{
					$out .= '<tr><td class="fieldrequired minwidth200">'.$langs->trans("MailFrom").'</td><td>';

					// $this->fromtype is the default value to use to select sender
					if (!($this->fromtype === 'user' && $this->fromid > 0)
						&& !($this->fromtype === 'company')
						&& !($this->fromtype === 'robot')
						&& !preg_match('/user_aliases/', $this->fromtype)
						&& !preg_match('/global_aliases/', $this->fromtype)
						&& !preg_match('/senderprofile/', $this->fromtype)
						)
					{
						// Use this->fromname and this->frommail or error if not defined
						$out .= $this->fromname;
						if ($this->frommail)
						{
							$out .= ' &lt;'.$this->frommail.'&gt;';
						}
						else
						{
							if ($this->fromtype)
							{
								$langs->load('errors');
								$out .= '<span class="warning"> &lt;'.$langs->trans('ErrorNoMailDefinedForThisUser').'&gt; </span>';
							}
						}
					} else {
						$liste = array();

						// Add user email
						if (empty($user->email))
						{
							$langs->load('errors');
							$liste['user'] = $user->getFullName($langs).' &lt;'.$langs->trans('ErrorNoMailDefinedForThisUser').'&gt;';
						}
						else
						{
							$liste['user'] = $user->getFullName($langs).' &lt;'.$user->email.'&gt;';
						}

						// Add also company main email
						$liste['company'] = $conf->global->MAIN_INFO_SOCIETE_NOM.' &lt;'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'&gt;';

						// Add also email aliases if there is some
						$listaliases = array('user_aliases'=>$user->email_aliases, 'global_aliases'=>$conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);

						// Also add robot email
						if (!empty($this->fromalsorobot))
						{
							if (!empty($conf->global->MAIN_MAIL_EMAIL_FROM) && $conf->global->MAIN_MAIL_EMAIL_FROM != $conf->global->MAIN_INFO_SOCIETE_MAIL)
							{
								$liste['robot'] = $conf->global->MAIN_MAIL_EMAIL_FROM;
								if ($this->frommail)
								{
									$liste['robot'] .= ' &lt;'.$conf->global->MAIN_MAIL_EMAIL_FROM.'&gt;';
								}
							}
						}

						// Add also email aliases from the c_email_senderprofile table
						$sql = 'SELECT rowid, label, email FROM '.MAIN_DB_PREFIX.'c_email_senderprofile';
						$sql .= ' WHERE active = 1 AND (private = 0 OR private = '.$user->id.')';
						$sql .= ' ORDER BY position';
						$resql = $this->db->query($sql);
						if ($resql)
						{
							$num = $this->db->num_rows($resql);
							$i = 0;
							while ($i < $num)
							{
								$obj = $this->db->fetch_object($resql);
								if ($obj)
								{
									$listaliases['senderprofile_'.$obj->rowid] = $obj->label.' <'.$obj->email.'>';
								}
								$i++;
							}
						}
						else dol_print_error($this->db);

						foreach ($listaliases as $typealias => $listalias)
						{
							$posalias = 0;
							$listaliasarray = explode(',', $listalias);
							foreach ($listaliasarray as $listaliasval)
							{
								$posalias++;
								$listaliasval = trim($listaliasval);
								if ($listaliasval)
								{
									$listaliasval = preg_replace('/</', '&lt;', $listaliasval);
									$listaliasval = preg_replace('/>/', '&gt;', $listaliasval);
									if (!preg_match('/&lt;/', $listaliasval)) $listaliasval = '&lt;'.$listaliasval.'&gt;';
									$liste[$typealias.'_'.$posalias] = $listaliasval;
								}
							}
						}

						// Set the default "From"
						$defaultfrom = '';
						$reshook = $hookmanager->executeHooks('getDefaultFromEmail', $parameters, $this);
						if (empty($reshook))
						{
							$defaultfrom = $this->fromtype;
						}
						if (!empty($hookmanager->resArray['defaultfrom'])) $defaultfrom = $hookmanager->resArray['defaultfrom'];

						// Using combo here make the '<email>' no more visible on list.
						//$out.= ' '.$form->selectarray('fromtype', $liste, $this->fromtype, 0, 0, 0, '', 0, 0, 0, '', 'fromforsendingprofile maxwidth200onsmartphone', 1, '', $disablebademails);
						$out .= ' '.$form->selectarray('fromtype', $liste, $defaultfrom, 0, 0, 0, '', 0, 0, 0, '', 'fromforsendingprofile maxwidth200onsmartphone', 0, '', $disablebademails);
					}

					$out .= "</td></tr>\n";
				}
				else
				{
					$out .= '<tr><td class="fieldrequired width200">'.$langs->trans("MailFrom")."</td><td>";
					$out .= $langs->trans("Name").':<input type="text" id="fromname" name="fromname" class="maxwidth200onsmartphone" value="'.$this->fromname.'" />';
					$out .= '&nbsp; &nbsp; ';
					$out .= $langs->trans("EMail").':&lt;<input type="text" id="frommail" name="frommail" class="maxwidth200onsmartphone" value="'.$this->frommail.'" />&gt;';
					$out .= "</td></tr>\n";
				}
			}

			// To
			if (!empty($this->withto) || is_array($this->withto))
			{
				$out .= '<tr><td class="fieldrequired">';
				if ($this->withtofree) $out .= $form->textwithpicto($langs->trans("MailTo"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients"));
				else $out .= $langs->trans("MailTo");
				$out .= '</td><td>';
				if ($this->withtoreadonly)
				{
					if (!empty($this->toname) && !empty($this->tomail))
					{
						$out .= '<input type="hidden" id="toname" name="toname" value="'.$this->toname.'" />';
						$out .= '<input type="hidden" id="tomail" name="tomail" value="'.$this->tomail.'" />';
						if ($this->totype == 'thirdparty')
						{
							$soc = new Societe($this->db);
							$soc->fetch($this->toid);
							$out .= $soc->getNomUrl(1);
						}
						elseif ($this->totype == 'contact')
						{
							$contact = new Contact($this->db);
							$contact->fetch($this->toid);
							$out .= $contact->getNomUrl(1);
						}
						else
						{
							$out .= $this->toname;
						}
						$out .= ' &lt;'.$this->tomail.'&gt;';
						if ($this->withtofree)
						{
							$out .= '<br>'.$langs->trans("and").' <input class="minwidth200" id="sendto" name="sendto" value="'.(!is_array($this->withto) && !is_numeric($this->withto) ? (isset($_REQUEST["sendto"]) ? $_REQUEST["sendto"] : $this->withto) : "").'" />';
						}
					}
					else
					{
						// Note withto may be a text like 'AllRecipientSelected'
						$out .= (!is_array($this->withto) && !is_numeric($this->withto)) ? $this->withto : "";
					}
				}
				else
				{
					// The free input of email
					if (!empty($this->withtofree))
					{
						$out .= '<input class="minwidth200" id="sendto" name="sendto" value="'.(($this->withtofree && !is_numeric($this->withtofree)) ? $this->withtofree : (!is_array($this->withto) && !is_numeric($this->withto) ? (isset($_REQUEST["sendto"]) ? $_REQUEST["sendto"] : $this->withto) : "")).'" />';
					}
					// The select combo
					if (!empty($this->withto) && is_array($this->withto))
					{
						if (!empty($this->withtofree)) $out .= " ".$langs->trans("and")."/".$langs->trans("or")." ";
						// multiselect array convert html entities into options tags, even if we dont want this, so we encode them a second time
						$tmparray = $this->withto;
						foreach ($tmparray as $key => $val)
						{
							$tmparray[$key] = dol_htmlentities($tmparray[$key], null, 'UTF-8', true);
						}
						$withtoselected = GETPOST("receiver", 'none'); // Array of selected value
						if (empty($withtoselected) && count($tmparray) == 1 && GETPOST('action', 'aZ09') == 'presend')
						{
							$withtoselected = array_keys($tmparray);
						}
						$out .= $form->multiselectarray("receiver", $tmparray, $withtoselected, null, null, 'inline-block minwidth500', null, "");
					}
				}
				$out .= "</td></tr>\n";
			}

			// To User
			if (!empty($this->withtouser) && is_array($this->withtouser) && !empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT))
			{
				$out .= '<tr><td>';
				$out .= $langs->trans("MailToUsers");
				$out .= '</td><td>';

				// multiselect array convert html entities into options tags, even if we dont want this, so we encode them a second time
				$tmparray = $this->withtouser;
				foreach ($tmparray as $key => $val)
				{
					$tmparray[$key] = dol_htmlentities($tmparray[$key], null, 'UTF-8', true);
				}
				$withtoselected = GETPOST("receiveruser", 'none'); // Array of selected value
				if (empty($withtoselected) && count($tmparray) == 1 && GETPOST('action', 'aZ09') == 'presend')
				{
					$withtoselected = array_keys($tmparray);
				}
				$out .= $form->multiselectarray("receiveruser", $tmparray, $withtoselected, null, null, 'inline-block minwidth500', null, "");
				$out .= "</td></tr>\n";
			}

			// With option one email per recipient
			if (!empty($this->withoptiononeemailperrecipient))
			{
				$out .= '<tr><td class="minwidth200">';
				$out .= $langs->trans("GroupEmails");
				$out .= '</td><td>';
				$out .= ' <input type="checkbox" name="oneemailperrecipient"'.($this->withoptiononeemailperrecipient > 0 ? ' checked="checked"' : '').'> ';
				$out .= $langs->trans("OneEmailPerRecipient");
				$out .= '<span class="hideonsmartphone">';
				$out .= ' - ';
				$out .= $langs->trans("WarningIfYouCheckOneRecipientPerEmail");
				$out .= '</span>';
				$out .= '</td></tr>';
			}

			// CC
			if (!empty($this->withtocc) || is_array($this->withtocc))
			{
				$out .= '<tr><td>';
				$out .= $form->textwithpicto($langs->trans("MailCC"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients"));
				$out .= '</td><td>';
				if ($this->withtoccreadonly)
				{
					$out .= (!is_array($this->withtocc) && !is_numeric($this->withtocc)) ? $this->withtocc : "";
				}
				else
				{
				    $out .= '<input class="minwidth200" id="sendtocc" name="sendtocc" value="'.(GETPOST("sendtocc", "alpha") ? GETPOST("sendtocc", "alpha") : ((!is_array($this->withtocc) && !is_numeric($this->withtocc)) ? $this->withtocc : '')).'" />';
					if (!empty($this->withtocc) && is_array($this->withtocc))
					{
						$out .= " ".$langs->trans("and")."/".$langs->trans("or")." ";
						// multiselect array convert html entities into options tags, even if we dont want this, so we encode them a second time
						$tmparray = $this->withtocc;
						foreach ($tmparray as $key => $val)
						{
							$tmparray[$key] = dol_htmlentities($tmparray[$key], null, 'UTF-8', true);
						}
						$withtoccselected = GETPOST("receivercc"); // Array of selected value
						$out .= $form->multiselectarray("receivercc", $tmparray, $withtoccselected, null, null, 'inline-block minwidth500', null, "");
					}
				}
				$out .= "</td></tr>\n";
			}

			// To User cc
			if (!empty($this->withtoccuser) && is_array($this->withtoccuser) && !empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT))
			{
				$out .= '<tr><td>';
				$out .= $langs->trans("MailToCCUsers");
				$out .= '</td><td>';

				// multiselect array convert html entities into options tags, even if we dont want this, so we encode them a second time
				$tmparray = $this->withtoccuser;
				foreach ($tmparray as $key => $val)
				{
					$tmparray[$key] = dol_htmlentities($tmparray[$key], null, 'UTF-8', true);
				}
				$withtoselected = GETPOST("receiverccuser", 'none'); // Array of selected value
				if (empty($withtoselected) && count($tmparray) == 1 && GETPOST('action', 'aZ09') == 'presend')
				{
					$withtoselected = array_keys($tmparray);
				}
				$out .= $form->multiselectarray("receiverccuser", $tmparray, $withtoselected, null, null, 'inline-block minwidth500', null, "");
				$out .= "</td></tr>\n";
			}

			// CCC
			if (!empty($this->withtoccc) || is_array($this->withtoccc)) {
				$out .= $this->getHtmlForWithCcc();
			}

			// Replyto
			if (!empty($this->withreplyto))
			{
				if ($this->withreplytoreadonly)
				{
					$out .= '<input type="hidden" id="replyname" name="replyname" value="'.$this->replytoname.'" />';
					$out .= '<input type="hidden" id="replymail" name="replymail" value="'.$this->replytomail.'" />';
					$out .= "<tr><td>".$langs->trans("MailReply")."</td><td>".$this->replytoname.($this->replytomail ? (" &lt;".$this->replytomail."&gt;") : "");
					$out .= "</td></tr>\n";
				}
			}

			// Errorsto
			if (!empty($this->witherrorsto)) {
				$out .= $this->getHtmlForWithErrorsTo();
			}

			// Ask delivery receipt
			if (!empty($this->withdeliveryreceipt)) {
				$out .= $this->getHtmlForDeliveryReceipt();
			}

			// Topic
			if (!empty($this->withtopic)) {
				$out .= $this->getHtmlForTopic($arraydefaultmessage, $helpforsubstitution);
			}

			// Attached files
			if (!empty($this->withfile))
			{
				$out .= '<tr>';
				$out .= '<td>'.$langs->trans("MailFile").'</td>';

				$out .= '<td>';

				if ($this->withmaindocfile)	// withmaindocfile is set to 1 or -1 to show the checkbox (-1 = checked or 1 = not checked)
				{
					if (GETPOSTISSET('sendmail'))
					{
						$this->withmaindocfile = (GETPOST('addmaindocfile', 'alpha') ? -1 : 1);
					}
					// If a template was selected, we use setup of template to define if join file checkbox is selected or not.
					elseif (is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0)
					{
						$this->withmaindocfile = ($arraydefaultmessage->joinfiles ? -1 : 1);
					}
				}

				if (!empty($this->withmaindocfile))
				{
					if ($this->withmaindocfile == 1)
					{
						$out .= '<input type="checkbox" name="addmaindocfile" value="1" />';
					}
					if ($this->withmaindocfile == -1)
					{
						$out .= '<input type="checkbox" name="addmaindocfile" value="1" checked="checked" />';
					}
					$out .= ' '.$langs->trans("JoinMainDoc").'.<br>';
				}

				if (is_numeric($this->withfile))
				{
					// TODO Trick to have param removedfile containing nb of file to delete. But this does not works without javascript
					$out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">'."\n";
					$out .= '<script type="text/javascript" language="javascript">';
					$out .= 'jQuery(document).ready(function () {';
					$out .= '    jQuery(".removedfile").click(function() {';
					$out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
					$out .= '    });';
					$out .= '})';
					$out .= '</script>'."\n";
					if (count($listofpaths))
					{
						foreach ($listofpaths as $key => $val)
						{
							$out .= '<div id="attachfile_'.$key.'">';
							$out .= img_mime($listofnames[$key]).' '.$listofnames[$key];
							if (!$this->withfilereadonly)
							{
								$out .= ' <input type="image" style="border: 0px;" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" value="'.($key + 1).'" class="removedfile" id="removedfile_'.$key.'" name="removedfile_'.$key.'" />';
								//$out.= ' <a href="'.$_SERVER["PHP_SELF"].'?removedfile='.($key+1).' id="removedfile_'.$key.'">'.img_delete($langs->trans("Delete").'</a>';
							}
							$out .= '<br></div>';
						}
					}
					elseif (empty($this->withmaindocfile))		// Do not show message if we asked to show the checkbox
					{
						$out .= $langs->trans("NoAttachedFiles").'<br>';
					}
					if ($this->withfile == 2)	// Can add other files
					{
						if (!empty($conf->global->FROM_MAIL_USE_INPUT_FILE_MULTIPLE)) $out .= '<input type="file" class="flat" id="addedfile" name="addedfile[]" value="'.$langs->trans("Upload").'" multiple />';
						else $out .= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
						$out .= ' ';
						$out .= '<input class="button" type="submit" id="'.$addfileaction.'" name="'.$addfileaction.'" value="'.$langs->trans("MailingAddFile").'" />';
					}
				}
				else
				{
					$out .= $this->withfile;
				}

				$out .= "</td></tr>\n";
			}

			// Message
			if (!empty($this->withbody))
			{
				$defaultmessage = GETPOST('message', 'none');
				if (!GETPOST('modelselected', 'alpha') || GETPOST('modelmailselected') != '-1')
				{
					if ($arraydefaultmessage && $arraydefaultmessage->content) {
						$defaultmessage = $arraydefaultmessage->content;
					} elseif (!is_numeric($this->withbody)) {
						$defaultmessage = $this->withbody;
					}
				}

				// Complete substitution array with the url to make online payment
				$paymenturl = ''; $validpaymentmethod = array();
				if (empty($this->substit['__REF__']))
				{
					$paymenturl = '';
				}
				else
				{
					// Set the online payment url link into __ONLINE_PAYMENT_URL__ key
					require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
					$langs->loadLangs(array('paypal', 'other'));
					$typeforonlinepayment = 'free';
					if ($this->param["models"] == 'order' || $this->param["models"] == 'order_send')   $typeforonlinepayment = 'order'; // TODO use detection on something else than template
					if ($this->param["models"] == 'invoice' || $this->param["models"] == 'facture_send') $typeforonlinepayment = 'invoice'; // TODO use detection on something else than template
					if ($this->param["models"] == 'member') $typeforonlinepayment = 'member'; // TODO use detection on something else than template
					$url = getOnlinePaymentUrl(0, $typeforonlinepayment, $this->substit['__REF__']);
					$paymenturl = $url;

					$validpaymentmethod = getValidOnlinePaymentMethods('');
				}

				if (count($validpaymentmethod) > 0 && $paymenturl)
				{
					$langs->load('other');
					$this->substit['__ONLINE_PAYMENT_TEXT_AND_URL__'] = str_replace('\n', "\n", $langs->transnoentities("PredefinedMailContentLink", $paymenturl));
					$this->substit['__ONLINE_PAYMENT_URL__'] = $paymenturl;
				}
				else
				{
					$this->substit['__ONLINE_PAYMENT_TEXT_AND_URL__'] = '';
					$this->substit['__ONLINE_PAYMENT_URL__'] = '';
				}

				//Add lines substitution key from each line
				$lines = '';
				$defaultlines = $arraydefaultmessage->content_lines;
				if (isset($defaultlines))
				{
					foreach ($this->substit_lines as $substit_line)
					{
						$lines .= make_substitutions($defaultlines, $substit_line)."\n";
					}
				}
				$this->substit['__LINES__'] = $lines;

				$defaultmessage = str_replace('\n', "\n", $defaultmessage);

				// Deal with format differences between message and signature (text / HTML)
				if (dol_textishtml($defaultmessage) && !dol_textishtml($this->substit['__USER_SIGNATURE__'])) {
					$this->substit['__USER_SIGNATURE__'] = dol_nl2br($this->substit['__USER_SIGNATURE__']);
				} elseif (!dol_textishtml($defaultmessage) && dol_textishtml($this->substit['__USER_SIGNATURE__'])) {
					$defaultmessage = dol_nl2br($defaultmessage);
				}

				if (isset($_POST["message"]) && !$_POST['modelselected']) $defaultmessage = $_POST["message"];
				else
				{
					$defaultmessage = make_substitutions($defaultmessage, $this->substit);
					// Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
					$defaultmessage = preg_replace("/^(<br>)+/", "", $defaultmessage);
					$defaultmessage = preg_replace("/^\n+/", "", $defaultmessage);
				}
				$out .= '<tr>';
				$out .= '<td class="tdtop">';
				$out .= $form->textwithpicto($langs->trans('MailText'), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltipfrombody');
				$out .= '</td>';
				$out .= '<td>';
				if ($this->withbodyreadonly)
				{
					$out .= nl2br($defaultmessage);
					$out .= '<input type="hidden" id="message" name="message" value="'.$defaultmessage.'" />';
				}
				else
				{
					if (!isset($this->ckeditortoolbar)) $this->ckeditortoolbar = 'dolibarr_notes';

					// Editor wysiwyg
					require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
					if ($this->withfckeditor == -1)
					{
						if (!empty($conf->global->FCKEDITOR_ENABLE_MAIL)) $this->withfckeditor = 1;
						else $this->withfckeditor = 0;
					}

					$doleditor = new DolEditor('message', $defaultmessage, '', 280, $this->ckeditortoolbar, 'In', true, true, $this->withfckeditor, 8, '95%');
					$out .= $doleditor->Create(1);
				}
				$out .= "</td></tr>\n";
			}

			$out .= '</table>'."\n";

			if ($this->withform == 1 || $this->withform == -1)
			{
				$out .= '<br><div class="center">';
				$out .= '<input class="button" type="submit" id="sendmail" name="sendmail" value="'.$langs->trans("SendMail").'"';
				// Add a javascript test to avoid to forget to submit file before sending email
				if ($this->withfile == 2 && $conf->use_javascript_ajax)
				{
					$out .= ' onClick="if (document.mailform.addedfile.value != \'\') { alert(\''.dol_escape_js($langs->trans("FileWasNotUploaded")).'\'); return false; } else { return true; }"';
				}
				$out .= ' />';
				if ($this->withcancel)
				{
					$out .= ' &nbsp; &nbsp; ';
					$out .= '<input class="button" type="submit" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'" />';
				}
				$out .= '</div>'."\n";
			}

			if ($this->withform == 1) $out .= '</form>'."\n";

			// Disable enter key if option MAIN_MAILFORM_DISABLE_ENTERKEY is set
			if (!empty($conf->global->MAIN_MAILFORM_DISABLE_ENTERKEY))
			{
				$out .= '<script type="text/javascript" language="javascript">';
				$out .= 'jQuery(document).ready(function () {';
				$out .= '	$(document).on("keypress", \'#mailform\', function (e) {		/* Note this is called at every key pressed ! */
	    						var code = e.keyCode || e.which;
	    						if (code == 13) {
	        						e.preventDefault();
	        						return false;
	    						}
							});';
				$out .= '		})';
				$out .= '</script>';
			}

			$out .= "<!-- End form mail -->\n";

			return $out;
		}
	}

	/**
	 * get html For WithCCC
	 *
	 * @return string html
	 */
	public function getHtmlForWithCcc()
	{
		global $conf, $langs, $form;
		$out = '<tr><td>';
		$out .= $form->textwithpicto($langs->trans("MailCCC"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients"));
		$out .= '</td><td>';
		if (!empty($this->withtocccreadonly)) {
			$out .= (!is_array($this->withtoccc) && !is_numeric($this->withtoccc)) ? $this->withtoccc : "";
		} else {
		    $out .= '<input class="minwidth200" id="sendtoccc" name="sendtoccc" value="'.(GETPOST("sendtoccc", "alpha") ? GETPOST("sendtoccc", "alpha") : ((!is_array($this->withtoccc) && !is_numeric($this->withtoccc)) ? $this->withtoccc : '')).'" />';
			if (!empty($this->withtoccc) && is_array($this->withtoccc)) {
				$out .= " ".$langs->trans("and")."/".$langs->trans("or")." ";
				// multiselect array convert html entities into options tags, even if we dont want this, so we encode them a second time
				$tmparray = $this->withtoccc;
				foreach ($tmparray as $key => $val) {
					$tmparray[$key] = dol_htmlentities($tmparray[$key], null, 'UTF-8', true);
				}
				$withtocccselected = GETPOST("receiverccc"); // Array of selected value
				$out .= $form->multiselectarray("receiverccc", $tmparray, $withtocccselected, null, null, null, null, "90%");
			}
		}

		$showinfobcc = '';
		if (!empty($conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO) && !empty($this->param['models']) && $this->param['models'] == 'propal_send') $showinfobcc = $conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO;
		if (!empty($conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO) && !empty($this->param['models']) && $this->param['models'] == 'order_send') $showinfobcc = $conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO;
		if (!empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO) && !empty($this->param['models']) && $this->param['models'] == 'facture_send') $showinfobcc = $conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO;
		if (!empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO) && !empty($this->param['models']) && $this->param['models'] == 'supplier_proposal_send') $showinfobcc = $conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO;
		if (!empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO) && !empty($this->param['models']) && $this->param['models'] == 'order_supplier_send') $showinfobcc = $conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO;
		if (!empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO) && !empty($this->param['models']) && $this->param['models'] == 'invoice_supplier_send') $showinfobcc = $conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO;
		if (!empty($conf->global->MAIN_MAIL_AUTOCOPY_PROJECT_TO) && !empty($this->param['models']) && $this->param['models'] == 'project') $showinfobcc = $conf->global->MAIN_MAIL_AUTOCOPY_PROJECT_TO;
		if ($showinfobcc) $out .= ' + '.$showinfobcc;
		$out .= "</td></tr>\n";
		return $out;
	}

	/**
	 * get Html For WithErrorsTo
	 *
	 * @return string html
	 */
	public function getHtmlForWithErrorsTo()
	{
		global $conf, $langs;
		//if (! $this->errorstomail) $this->errorstomail=$this->frommail;
		$errorstomail = (!empty($conf->global->MAIN_MAIL_ERRORS_TO) ? $conf->global->MAIN_MAIL_ERRORS_TO : $this->errorstomail);
		if ($this->witherrorstoreadonly) {
			$out = '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td>';
			$out .= '<input type="hidden" id="errorstomail" name="errorstomail" value="'.$errorstomail.'" />';
			$out .= $errorstomail;
			$out .= "</td></tr>\n";
		} else {
			$out = '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td>';
			$out .= '<input size="30" id="errorstomail" name="errorstomail" value="'.$errorstomail.'" />';
			$out .= "</td></tr>\n";
		}
		return $out;
	}

	/**
	 * get Html For Asking for Deliveriy Receipt
	 *
	 * @return string html
	 */
	public function getHtmlForDeliveryreceipt()
	{
		global $conf, $langs, $form;
		$out = '<tr><td>'.$langs->trans("DeliveryReceipt").'</td><td>';

		if (!empty($this->withdeliveryreceiptreadonly)) {
			$out .= yn($this->withdeliveryreceipt);
		} else {
			$defaultvaluefordeliveryreceipt = 0;
			if (!empty($conf->global->MAIL_FORCE_DELIVERY_RECEIPT_PROPAL) && !empty($this->param['models']) && $this->param['models'] == 'propal_send') $defaultvaluefordeliveryreceipt = 1;
			if (!empty($conf->global->MAIL_FORCE_DELIVERY_RECEIPT_SUPPLIER_PROPOSAL) && !empty($this->param['models']) && $this->param['models'] == 'supplier_proposal_send') $defaultvaluefordeliveryreceipt = 1;
			if (!empty($conf->global->MAIL_FORCE_DELIVERY_RECEIPT_ORDER) && !empty($this->param['models']) && $this->param['models'] == 'order_send') $defaultvaluefordeliveryreceipt = 1;
			if (!empty($conf->global->MAIL_FORCE_DELIVERY_RECEIPT_INVOICE) && !empty($this->param['models']) && $this->param['models'] == 'facture_send') $defaultvaluefordeliveryreceipt = 1;
			$out .= $form->selectyesno('deliveryreceipt', (isset($_POST["deliveryreceipt"]) ? $_POST["deliveryreceipt"] : $defaultvaluefordeliveryreceipt), 1);
		}
		$out .= "</td></tr>\n";
		return $out;
	}

	/**
	 * get Html For Topic of message
	 *
	 * @param	array	$arraydefaultmessage		Array with message template content
	 * @param	string	$helpforsubstitution		Help string for substitution
	 * @return 	string 								Text for topic
	 */
	public function getHtmlForTopic($arraydefaultmessage, $helpforsubstitution)
	{
		global $conf, $langs, $form;

		$defaulttopic = GETPOST('subject', 'none');
		if (!GETPOST('modelselected', 'alpha') || GETPOST('modelmailselected') != '-1') {
			if ($arraydefaultmessage && $arraydefaultmessage->topic) {
				$defaulttopic = $arraydefaultmessage->topic;
			} elseif (!is_numeric($this->withtopic)) {
				$defaulttopic = $this->withtopic;
			}
		}

		$defaulttopic = make_substitutions($defaulttopic, $this->substit);

		$out = '<tr>';
		$out .= '<td class="fieldrequired">';
		$out .= $form->textwithpicto($langs->trans('MailTopic'), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltipfromtopic');
		$out .= '</td>';
		$out .= '<td>';
		if ($this->withtopicreadonly) {
			$out .= $defaulttopic;
			$out .= '<input type="hidden" class="quatrevingtpercent" id="subject" name="subject" value="'.$defaulttopic.'" />';
		} else {
			$out .= '<input type="text" class="quatrevingtpercent" id="subject" name="subject" value="'.((isset($_POST["subject"]) && !$_POST['modelselected']) ? $_POST["subject"] : ($defaulttopic ? $defaulttopic : '')).'" />';
		}
		$out .= "</td></tr>\n";
		return $out;
	}

	/**
	 *  Return templates of email with type = $type_template or type = 'all'.
	 *  This search into table c_email_templates. Used by the get_form function.
	 *
	 *  @param	DoliDB		$db				Database handler
	 *  @param	string		$type_template	Get message for model/type=$type_template, type='all' also included.
	 *  @param	string		$user			Get template public or limited to this user
	 *  @param	Translate	$outputlangs	Output lang object
	 *  @param	int			$id				Id of template to find, or -1 for first found with position 0, or 0 for first found whatever is position (priority order depends on lang provided or not) or -2 for exact match with label (no answer if not found)
	 *  @param  int         $active         1=Only active template, 0=Only disabled, -1=All
	 *  @param	string		$label			Label of template
	 *  @return ModelMail|integer			One instance of ModelMail or -1 if error
	 */
	public function getEMailTemplate($db, $type_template, $user, $outputlangs, $id = 0, $active = 1, $label = '')
	{
        $ret = new ModelMail();

		if ($id == -2 && empty($label)) {
			$this->error = 'LabelIsMandatoryWhenIdIs-2';
			return -1;
		}

		$languagetosearch = (is_object($outputlangs) ? $outputlangs->defaultlang : '');
		// Define $languagetosearchmain to fall back on main language (for example to get 'es_ES' for 'es_MX')
		$tmparray = explode('_', $languagetosearch);
		$languagetosearchmain = $tmparray[0].'_'.strtoupper($tmparray[0]);
		if ($languagetosearchmain == $languagetosearch) $languagetosearchmain = '';

		$sql = "SELECT rowid, label, topic, joinfiles, content, content_lines, lang";
		$sql .= " FROM ".MAIN_DB_PREFIX.'c_email_templates';
		$sql .= " WHERE (type_template='".$db->escape($type_template)."' OR type_template='all')";
		$sql .= " AND entity IN (".getEntity('c_email_templates').")";
		$sql .= " AND (private = 0 OR fk_user = ".$user->id.")"; // Get all public or private owned
		if ($active >= 0) $sql .= " AND active = ".$active;
		if ($label) $sql .= " AND label ='".$db->escape($label)."'";
		if (!($id > 0) && $languagetosearch) $sql .= " AND (lang = '".$db->escape($languagetosearch)."'".($languagetosearchmain ? " OR lang = '".$db->escape($languagetosearchmain)."'" : "")." OR lang IS NULL OR lang = '')";
		if ($id > 0)   $sql .= " AND rowid=".$id;
		if ($id == -1) $sql .= " AND position=0";
		if ($languagetosearch) $sql .= $db->order("position,lang,label", "ASC,DESC,ASC"); // We want line with lang set first, then with lang null or ''
		else $sql .= $db->order("position,lang,label", "ASC,ASC,ASC"); // If no language provided, we give priority to lang not defined
		$sql .= $db->plimit(1);
		//print $sql;

		$resql = $db->query($sql);
		if ($resql)
		{
			// Get first found
			$obj = $db->fetch_object($resql);

			if ($obj) {
				$ret->id = $obj->rowid;
				$ret->label = $obj->label;
				$ret->lang = $obj->lang;
				$ret->topic = $obj->topic;
				$ret->content = $obj->content;
				$ret->content_lines = $obj->content_lines;
				$ret->joinfiles = $obj->joinfiles;
			}
			elseif ($id == -2) {
				// Not found with the provided label
				return -1;
			}
			else {	// If there is no template at all
				$defaultmessage = '';
				if ($type_template == 'body') { $defaultmessage = $this->withbody; }		// Special case to use this->withbody as content
				elseif ($type_template == 'facture_send') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendInvoice"); }
				elseif ($type_template == 'facture_relance') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendInvoiceReminder"); }
				elseif ($type_template == 'propal_send') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendProposal"); }
				elseif ($type_template == 'supplier_proposal_send') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendSupplierProposal"); }
				elseif ($type_template == 'order_send') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendOrder"); }
				elseif ($type_template == 'order_supplier_send') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendSupplierOrder"); }
				elseif ($type_template == 'invoice_supplier_send') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendSupplierInvoice"); }
				elseif ($type_template == 'shipping_send') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendShipping"); }
				elseif ($type_template == 'fichinter_send') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentSendFichInter"); }
				elseif ($type_template == 'thirdparty') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentThirdparty"); }
				elseif ($type_template == 'user') { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentUser"); }
				elseif (!empty($type_template)) { $defaultmessage = $outputlangs->transnoentities("PredefinedMailContentGeneric"); }

				$ret->label = 'default';
				$ret->lang = $outputlangs->defaultlang;
				$ret->topic = '';
				$ret->joinfiles = 1;
				$ret->content = $defaultmessage;
				$ret->content_lines = '';
			}

			$db->free($resql);
			return $ret;
		}
		else
		{
			dol_print_error($db);
			return -1;
		}
	}

	/**
	 *      Find if template exists
	 *      Search into table c_email_templates
	 *
	 * 		@param	string		$type_template	Get message for key module
	 *      @param	string		$user			Use template public or limited to this user
	 *      @param	Translate	$outputlangs	Output lang object
	 *      @return	int		<0 if KO,
	 */
	public function isEMailTemplate($type_template, $user, $outputlangs)
	{
		$ret = array();

		$sql = "SELECT label, topic, content, lang";
		$sql .= " FROM ".MAIN_DB_PREFIX.'c_email_templates';
		$sql .= " WHERE type_template='".$this->db->escape($type_template)."'";
		$sql .= " AND entity IN (".getEntity('c_email_templates').")";
		$sql .= " AND (fk_user is NULL or fk_user = 0 or fk_user = ".$user->id.")";
		if (is_object($outputlangs)) $sql .= " AND (lang = '".$outputlangs->defaultlang."' OR lang IS NULL OR lang = '')";
		$sql .= $this->db->order("lang,label", "ASC");
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$this->db->free($resql);
			return $num;
		}
		else
		{
			$this->error = get_class($this).' '.__METHOD__.' ERROR:'.$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *      Find if template exists and are available for current user, then set them into $this->lines_module.
	 *      Search into table c_email_templates
	 *
	 * 		@param	string		$type_template	Get message for key module
	 *      @param	string		$user			Use template public or limited to this user
	 *      @param	Translate	$outputlangs	Output lang object
	 *      @param  int         $active         1=Only active template, 0=Only disabled, -1=All
	 *      @return	int		                    <0 if KO, nb of records found if OK
	 */
	public function fetchAllEMailTemplate($type_template, $user, $outputlangs, $active = 1)
	{
		$ret = array();

		$sql = "SELECT rowid, label, topic, content, content_lines, lang, fk_user, private, position";
		$sql .= " FROM ".MAIN_DB_PREFIX.'c_email_templates';
		$sql .= " WHERE type_template IN ('".$this->db->escape($type_template)."', 'all')";
		$sql .= " AND entity IN (".getEntity('c_email_templates').")";
		$sql .= " AND (private = 0 OR fk_user = ".$user->id.")"; // See all public templates or templates I own.
		if ($active >= 0) $sql .= " AND active = ".$active;
		//if (is_object($outputlangs)) $sql.= " AND (lang = '".$outputlangs->defaultlang."' OR lang IS NULL OR lang = '')";	// Return all languages
		$sql .= $this->db->order("position,lang,label", "ASC");
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$this->lines_model = array();
			while ($obj = $this->db->fetch_object($resql))
			{
				$line = new ModelMail();
				$line->id = $obj->rowid;
				$line->label = $obj->label;
				$line->lang = $obj->lang;
				$line->fk_user = $obj->fk_user;
				$line->private = $obj->private;
				$line->position = $obj->position;
				$line->topic = $obj->topic;
				$line->content = $obj->content;
				$line->content_lines = $obj->content_lines;

				$this->lines_model[] = $line;
			}
			$this->db->free($resql);
			return $num;
		}
		else
		{
			$this->error = get_class($this).' '.__METHOD__.' ERROR:'.$this->db->lasterror();
			return -1;
		}
	}



	/**
	 * Set substit array from object. This is call when suggesting the email template into forms before sending email.
	 *
	 * @param	CommonObject	$object		   Object to use
	 * @param   Translate  		$outputlangs   Object lang
	 * @return	void
	 * @see getCommonSubstitutionArray()
	 */
	public function setSubstitFromObject($object, $outputlangs)
	{
		global $conf, $user;

		$parameters = array();
		$tmparray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
		complete_substitutions_array($tmparray, $outputlangs, null, $parameters);

		$this->substit = $tmparray;

		// Fill substit_lines with each object lines content
		if (is_array($object->lines))
		{
			foreach ($object->lines as $line)
			{
				$substit_line = array(
					'__PRODUCT_REF__' => isset($line->product_ref) ? $line->product_ref : '',
					'__PRODUCT_LABEL__' => isset($line->product_label) ? $line->product_label : '',
					'__PRODUCT_DESCRIPTION__' => isset($line->product_desc) ? $line->product_desc : '',
					'__LABEL__' => isset($line->label) ? $line->label : '',
					'__DESCRIPTION__' => isset($line->desc) ? $line->desc : '',
					'__DATE_START_YMD__' => dol_print_date($line->date_start, 'day', 0, $outputlangs),
					'__DATE_END_YMD__' => dol_print_date($line->date_end, 'day', 0, $outputlangs),
					'__QUANTITY__' => $line->qty,
					'__SUBPRICE__' => price($line->subprice),
					'__AMOUNT__' => price($line->total_ttc),
					'__AMOUNT_EXCL_TAX__' => price($line->total_ht)
				);

				// Create dynamic tags for __PRODUCT_EXTRAFIELD_FIELD__
				if (!empty($line->fk_product))
				{
					if (!is_object($extrafields)) $extrafields = new ExtraFields($this->db);
					$extrafields->fetch_name_optionals_label('product', true);
					$product = new Product($this->db);
					$product->fetch($line->fk_product, '', '', 1);
					$product->fetch_optionals();
					if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
					{
						foreach ($extrafields->attributes[$product->table_element]['label'] as $key => $label) {
							$substit_line['__PRODUCT_EXTRAFIELD_'.strtoupper($key).'__'] = $product->array_options['options_'.$key];
						}
					}
				}
				$this->substit_lines[] = $substit_line;
			}
		}
	}

	/**
	 * Get list of substitution keys available for emails. This is used for tooltips help.
	 * This include the complete_substitutions_array.
	 *
	 * @param	string	$mode		'formemail', 'formemailwithlines', 'formemailforlines', 'emailing', ...
	 * @param	Object	$object		Object if applicable
	 * @return	array               Array of substitution values for emails.
	 */
	public static function getAvailableSubstitKey($mode = 'formemail', $object = null)
	{
		global $conf, $langs;

		$tmparray = array();
		if ($mode == 'formemail' || $mode == 'formemailwithlines' || $mode == 'formemailforlines')
		{
			$parameters = array('mode'=>$mode);
			$tmparray = getCommonSubstitutionArray($langs, 2, null, $object); // Note: On email templated edition, this is null because it is related to all type of objects
			complete_substitutions_array($tmparray, $langs, null, $parameters);

			if ($mode == 'formwithlines')
			{
				$tmparray['__LINES__'] = '__LINES__'; // Will be set by the get_form function
			}
			if ($mode == 'formforlines')
			{
				$tmparray['__QUANTITY__'] = '__QUANTITY__'; // Will be set by the get_form function
			}
		}

		if ($mode == 'emailing')
		{
			$parameters = array('mode'=>$mode);
			$tmparray = getCommonSubstitutionArray($langs, 2, array('object', 'objectamount'), $object); // Note: On email templated edition, this is null because it is related to all type of objects
			complete_substitutions_array($tmparray, $langs, null, $parameters);

			// For mass emailing, we have different keys
			$tmparray['__ID__'] = 'IdRecord';
			$tmparray['__EMAIL__'] = 'EMailRecipient';
			$tmparray['__LASTNAME__'] = 'Lastname';
			$tmparray['__FIRSTNAME__'] = 'Firstname';
			$tmparray['__MAILTOEMAIL__'] = 'TagMailtoEmail';
			$tmparray['__OTHER1__'] = 'Other1';
			$tmparray['__OTHER2__'] = 'Other2';
			$tmparray['__OTHER3__'] = 'Other3';
			$tmparray['__OTHER4__'] = 'Other4';
			$tmparray['__OTHER5__'] = 'Other5';
			$tmparray['__USER_SIGNATURE__'] = 'TagSignature';
			$tmparray['__CHECK_READ__'] = 'TagCheckMail';
			$tmparray['__UNSUBSCRIBE__'] = 'TagUnsubscribe';
				//,'__PERSONALIZED__' => 'Personalized'	// Hidden because not used yet in mass emailing

			$onlinepaymentenabled = 0;
			if (!empty($conf->paypal->enabled)) $onlinepaymentenabled++;
			if (!empty($conf->paybox->enabled)) $onlinepaymentenabled++;
			if (!empty($conf->stripe->enabled)) $onlinepaymentenabled++;
			if ($onlinepaymentenabled && !empty($conf->global->PAYMENT_SECURITY_TOKEN))
			{
				$tmparray['__SECUREKEYPAYMENT__'] = $conf->global->PAYMENT_SECURITY_TOKEN;
				if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
				{
					if ($conf->adherent->enabled) $tmparray['__SECUREKEYPAYMENT_MEMBER__'] = 'SecureKeyPAYMENTUniquePerMember';
					if ($conf->facture->enabled)  $tmparray['__SECUREKEYPAYMENT_INVOICE__'] = 'SecureKeyPAYMENTUniquePerInvoice';
					if ($conf->commande->enabled) $tmparray['__SECUREKEYPAYMENT_ORDER__'] = 'SecureKeyPAYMENTUniquePerOrder';
					if ($conf->contrat->enabled)  $tmparray['__SECUREKEYPAYMENT_CONTRACTLINE__'] = 'SecureKeyPAYMENTUniquePerContractLine';
				}
			}
			else
			{
				/* No need to show into tooltip help, option is not enabled
				$vars['__SECUREKEYPAYMENT__']='';
				$vars['__SECUREKEYPAYMENT_MEMBER__']='';
				$vars['__SECUREKEYPAYMENT_INVOICE__']='';
				$vars['__SECUREKEYPAYMENT_ORDER__']='';
				$vars['__SECUREKEYPAYMENT_CONTRACTLINE__']='';
				*/
			}
		}

		foreach ($tmparray as $key => $val)
		{
			if (empty($val)) $tmparray[$key] = $key;
		}

		return $tmparray;
	}
}


/**
 * ModelMail
 */
class ModelMail
{
	/**
	 * @var int ID
	 */
	public $id;

	/**
     * @var string Model mail label
     */
    public $label;

	public $topic;
	public $content;
	public $content_lines;
	public $lang;
	public $joinfiles;
}
