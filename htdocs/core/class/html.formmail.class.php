<?php
/* Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/class/html.formmail.class.php
 *       \ingroup    core
 *       \brief      Fichier de la classe permettant la generation du formulaire html d'envoi de mail unitaire
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/html.form.class.php';


/**
 *      Classe permettant la generation du formulaire html d'envoi de mail unitaire
 *      Usage: $formail = new FormMail($db)
 *             $formmail->proprietes=1 ou chaine ou tableau de valeurs
 *             $formmail->show_form() affiche le formulaire
 */
class FormMail extends Form
{
    var $db;

    var $withform;				// 1=Include HTML form tag and show submit button, 0=Do not include form tag and submit button, -1=Do not include form tag but include submit button

    var $fromname;
    var $frommail;
    var $replytoname;
    var $replytomail;
    var $toname;
    var $tomail;
	var $trackid;

    var $withsubstit;			// Show substitution array
    var $withfrom;
	/**
	 * @var int
	 * @deprecated Fill withto with array before calling method.
	 * @see withto
	 */
	public $withtosocid;
	/**
	 * @var int|int[]
	 */
    public $withto;				// Show recipient emails
    var $withtofree;			// Show free text for recipient emails
    var $withtocc;
    var $withtoccc;
    var $withtopic;
    var $withfile;				// 0=No attaches files, 1=Show attached files, 2=Can add new attached files
    var $withbody;

    var $withfromreadonly;
    var $withreplytoreadonly;
    var $withtoreadonly;
    var $withtoccreadonly;
	var $withtocccreadonly;
	var $withtopicreadonly;
    var $withfilereadonly;
    var $withdeliveryreceipt;
    var $withcancel;
    var $withfckeditor;

    var $substit=array();
    var $param=array();

    var $error;

    public $lines_model;


    /**
     *	Constructor
     *
     *  @param	DoliDB	$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        $this->withform=1;

        $this->withfrom=1;
        $this->withto=1;
        $this->withtofree=1;
        $this->withtocc=1;
        $this->withtoccc=0;
        $this->witherrorsto=0;
        $this->withtopic=1;
        $this->withfile=0;
        $this->withbody=1;

        $this->withfromreadonly=1;
        $this->withreplytoreadonly=1;
        $this->withtoreadonly=0;
        $this->withtoccreadonly=0;
	    $this->withtocccreadonly=0;
        $this->witherrorstoreadonly=0;
        $this->withtopicreadonly=0;
        $this->withfilereadonly=0;
        $this->withbodyreadonly=0;
        $this->withdeliveryreceiptreadonly=0;
        $this->withfckeditor=-1;	// -1 = Auto

        return 1;
    }

    /**
     * Clear list of attached files in send mail form (also stored in session)
     *
     * @return	void
     */
    function clear_attached_files()
    {
        global $conf,$user;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        // Set tmp user directory
        $vardir=$conf->user->dir_output."/".$user->id;
        $upload_dir = $vardir.'/temp/';                     // TODO Add $keytoavoidconflict in upload_dir path
        if (is_dir($upload_dir)) dol_delete_dir_recursive($upload_dir);

        $keytoavoidconflict = empty($this->trackid)?'':'-'.$this->trackid;   // this->trackid must be defined
        unset($_SESSION["listofpaths".$keytoavoidconflict]);
        unset($_SESSION["listofnames".$keytoavoidconflict]);
        unset($_SESSION["listofmimes".$keytoavoidconflict]);
    }

    /**
     * Add a file into the list of attached files (stored in SECTION array)
     *
     * @param 	string   $path   Full absolute path on filesystem of file, including file name
     * @param 	string   $file   Only filename
     * @param 	string   $type   Mime type
     * @return	void
     */
    function add_attached_files($path,$file,$type)
    {
        $listofpaths=array();
        $listofnames=array();
        $listofmimes=array();
        
        $keytoavoidconflict = empty($this->trackid)?'':'-'.$this->trackid;   // this->trackid must be defined
        if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
        if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';',$_SESSION["listofnames".$keytoavoidconflict]);
        if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';',$_SESSION["listofmimes".$keytoavoidconflict]);
        if (! in_array($file,$listofnames))
        {
            $listofpaths[]=$path;
            $listofnames[]=$file;
            $listofmimes[]=$type;
            $_SESSION["listofpaths".$keytoavoidconflict]=join(';',$listofpaths);
            $_SESSION["listofnames".$keytoavoidconflict]=join(';',$listofnames);
            $_SESSION["listofmimes".$keytoavoidconflict]=join(';',$listofmimes);
        }
    }

    /**
     * Remove a file from the list of attached files (stored in SECTION array)
     *
     * @param  	string	$keytodelete     Key in file array (0, 1, 2, ...)
     * @return	void
     */
    function remove_attached_files($keytodelete)
    {
        $listofpaths=array();
        $listofnames=array();
        $listofmimes=array();
        
        $keytoavoidconflict = empty($this->trackid)?'':'-'.$this->trackid;   // this->trackid must be defined
        if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
        if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';',$_SESSION["listofnames".$keytoavoidconflict]);
        if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';',$_SESSION["listofmimes".$keytoavoidconflict]);
        if ($keytodelete >= 0)
        {
            unset ($listofpaths[$keytodelete]);
            unset ($listofnames[$keytodelete]);
            unset ($listofmimes[$keytodelete]);
            $_SESSION["listofpaths".$keytoavoidconflict]=join(';',$listofpaths);
            $_SESSION["listofnames".$keytoavoidconflict]=join(';',$listofnames);
            $_SESSION["listofmimes".$keytoavoidconflict]=join(';',$listofmimes);
            //var_dump($_SESSION['listofpaths']);
        }
    }

    /**
     * Return list of attached files (stored in SECTION array)
     *
     * @return	array       array('paths'=> ,'names'=>, 'mimes'=> )
     */
    function get_attached_files()
    {
        $listofpaths=array();
        $listofnames=array();
        $listofmimes=array();
        
        $keytoavoidconflict = empty($this->trackid)?'':'-'.$this->trackid;   // this->trackid must be defined
        if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
        if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';',$_SESSION["listofnames".$keytoavoidconflict]);
        if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';',$_SESSION["listofmimes".$keytoavoidconflict]);
        return array('paths'=>$listofpaths, 'names'=>$listofnames, 'mimes'=>$listofmimes);
    }

    /**
     *	Show the form to input an email
     *  this->withfile: 0=No attaches files, 1=Show attached files, 2=Can add new attached files
     *
     *	@param	string	$addfileaction		Name of action when posting file attachments
     *	@param	string	$removefileaction	Name of action when removing file attachments
     *	@return	void
     */
    function show_form($addfileaction='addfile',$removefileaction='removefile')
    {
        print $this->get_form($addfileaction,$removefileaction);
    }

    /**
     *	Get the form to input an email
     *  this->withfile: 0=No attaches files, 1=Show attached files, 2=Can add new attached files
     *  this->param:	Contains more parameteres like email templates info
     *
     *	@param	string	$addfileaction		Name of action when posting file attachments
     *	@param	string	$removefileaction	Name of action when removing file attachments
     *	@return string						Form to show
     */
    function get_form($addfileaction='addfile',$removefileaction='removefile')
    {
        global $conf, $langs, $user, $hookmanager, $form;

        if (! is_object($form)) $form=new Form($this->db);

        $langs->load("other");
        $langs->load("mails");

        $hookmanager->initHooks(array('formmail'));

        $parameters=array(
        	'addfileaction' => $addfileaction,
        	'removefileaction'=> $removefileaction,
            'trackid'=> $this->trackid
        );
        $reshook=$hookmanager->executeHooks('getFormMail', $parameters, $this);

        if (!empty($reshook))
        {
        	return $hookmanager->resPrint;
        }
        else
		{
        	$out='';

        	$disablebademails=1;
        	
        	// Define list of attached files
        	$listofpaths=array();
        	$listofnames=array();
        	$listofmimes=array();
            $keytoavoidconflict = empty($this->trackid)?'':'-'.$this->trackid;   // this->trackid must be defined
            
        	if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
        	if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';',$_SESSION["listofnames".$keytoavoidconflict]);
        	if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';',$_SESSION["listofmimes".$keytoavoidconflict]);

       		// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $this->param['langsmodels'];
			if (! empty($newlang))
			{
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
				$outputlangs->load('other');
			}

        	// Get message template
			$model_id=0;
        	if (array_key_exists('models_id',$this->param))
        	{
        		$model_id=$this->param["models_id"];
        	}
        	$arraydefaultmessage=$this->getEMailTemplate($this->db, $this->param["models"], $user, $outputlangs, $model_id);
			//var_dump($arraydefaultmessage);

        	$out.= "\n<!-- Begin form mail -->\n";
        	if ($this->withform == 1)
        	{
        		$out.= '<form method="POST" name="mailform" id="mailform" enctype="multipart/form-data" action="'.$this->param["returnurl"].'#formmail">'."\n";
				$out.= '<input style="display:none" type="submit" id="sendmail" name="sendmail">';
        		$out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
        		$out.= '<input type="hidden" name="trackid" value="'.$this->trackid.'" />';
        		$out.= '<a id="formmail" name="formmail"></a>';
        	}
        	foreach ($this->param as $key=>$value)
        	{
        		$out.= '<input type="hidden" id="'.$key.'" name="'.$key.'" value="'.$value.'" />'."\n";
        	}

        	$result = $this->fetchAllEMailTemplate($this->param["models"], $user, $outputlangs);
        	if ($result<0)
        	{
        		setEventMessages($this->error, $this->errors, 'errors');
        	}
        	$modelmail_array=array();
        	foreach($this->lines_model as $line)
        	{
        		$modelmail_array[$line->id]=$line->label;
        	}

        	// Zone to select its email template
        	if (count($modelmail_array)>0)
        	{
	        	$out.= '<div class="center" style="padding: 0px 0 12px 0">'."\n";
        	    $out.= $langs->trans('SelectMailModel').': '.$this->selectarray('modelmailselected', $modelmail_array, 0, 1);
	        	if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	        	$out.= ' &nbsp; ';
	        	$out.= '<input class="button" type="submit" value="'.$langs->trans('Apply').'" name="modelselected" id="modelselected">';
	        	$out.= ' &nbsp; ';
	        	$out.= '</div>';
        	}
        	elseif (! empty($this->param['models']) && in_array($this->param['models'], array(
        	        'propal_send','order_send','facture_send',
        	        'shipping_send','fichinter_send','supplier_proposal_send','order_supplier_send',
        	        'invoice_supplier_send','thirdparty'
           	    )))
        	{
	        	$out.= '<div class="center" style="padding: 0px 0 12px 0">'."\n";
        	    $out.= $langs->trans('SelectMailModel').': <select name="modelmailselected" disabled="disabled"><option value="none">'.$langs->trans("NoTemplateDefined").'</option></select>';    // Do not put disabled on option, it is already on select and it makes chrome crazy.
        	    if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        	    $out.= ' &nbsp; ';
        	    $out.= '<input class="button" type="submit" value="'.$langs->trans('Apply').'" name="modelselected" disabled="disabled" id="modelselected">';
        	    $out.= ' &nbsp; ';
        	    $out.= '</div>';
        	}



        	$out.= '<table class="border" width="100%">'."\n";

        	// Substitution array
        	if (! empty($this->withsubstit))
        	{
        		$out.= '<tr><td colspan="2">';
        		$help="";
        		foreach($this->substit as $key => $val)
        		{
        			$help.=$key.' -> '.$langs->trans($val).'<br>';
        		}
        		$out.= $form->textwithpicto($langs->trans("EMailTestSubstitutionReplacedByGenericValues"), $help);
        		$out.= "</td></tr>\n";
        	}

        	// From
        	if (! empty($this->withfrom))
        	{
        		if (! empty($this->withfromreadonly))
        		{
        			$out.= '<input type="hidden" id="fromname" name="fromname" value="'.$this->fromname.'" />';
        			$out.= '<input type="hidden" id="frommail" name="frommail" value="'.$this->frommail.'" />';
        			$out.= '<tr><td width="180">'.$langs->trans("MailFrom").'</td><td>';

                    if (! ($this->fromtype === 'user' && $this->fromid > 0)
                        && ! ($this->fromtype === 'company')
                        && ! preg_match('/user_aliases/', $this->fromtype)
                        && ! preg_match('/global_aliases/', $this->fromtype))
                    {
                        // Use this->fromname and this->frommail or error if not defined
                        $out.= $this->fromname;
                        if ($this->frommail)
                        {
                            $out.= ' &lt;'.$this->frommail.'&gt;';
                        }
                        else
                        {
                            if ($this->fromtype)
                            {
                                $langs->load('errors');
                                $out.= '<span class="warning"> &lt;'.$langs->trans('ErrorNoMailDefinedForThisUser').'&gt; </span>';
                            }
                        }
                    } else {
                        $liste = array();
                        if (empty($user->email))
                        {
                            $langs->load('errors');
                            $liste['user'] = $user->getFullName($langs) . ' &lt;'.$langs->trans('ErrorNoMailDefinedForThisUser').'&gt;';
                        }
                        else
                        {
                            $liste['user'] = $user->getFullName($langs) .' &lt;'.$user->email.'&gt;';
                        }
                        $liste['company'] = $conf->global->MAIN_INFO_SOCIETE_NOM .' &lt;'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'&gt;';
                        // Add also email aliases if there is one
                        $listaliases=array('user_aliases'=>$user->email_aliases, 'global_aliases'=>$conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);
                        foreach($listaliases as $typealias => $listalias)
                        {
                            $posalias=0;
                            $listaliasarray=explode(',', $listalias);
                            foreach ($listaliasarray as $listaliasval)
                            {
                                $posalias++;
                                $listaliasval=trim($listaliasval);
                                if ($listaliasval) 
                                {
                                    $listaliasval=preg_replace('/</', '&lt;', $listaliasval);
                                    $listaliasval=preg_replace('/>/', '&gt;', $listaliasval);
                                    if (! preg_match('/&lt;/', $listaliasval)) $listaliasval='&lt;'.$listaliasval.'&gt;';
                                    $liste[$typealias.'_'.$posalias]=$listaliasval;
                                }
                            }
                        }
                        $out.= ' '.$form->selectarray('fromtype', $liste, $this->fromtype, 0, 0, 0, '', 0, 0, 0, '', '', 0, '', $disablebademails);
                    }

        			$out.= "</td></tr>\n";
        			$out.= "</td></tr>\n";
        		}
        		else
        		{
        			$out.= "<tr><td>".$langs->trans("MailFrom")."</td><td>";
        			$out.= $langs->trans("Name").':<input type="text" id="fromname" name="fromname" size="32" value="'.$this->fromname.'" />';
        			$out.= '&nbsp; &nbsp; ';
        			$out.= $langs->trans("EMail").':&lt;<input type="text" id="frommail" name="frommail" size="32" value="'.$this->frommail.'" />&gt;';
        			$out.= "</td></tr>\n";
        		}
        	}

        	// Replyto
        	if (! empty($this->withreplyto))
        	{
        		if ($this->withreplytoreadonly)
        		{
        			$out.= '<input type="hidden" id="replyname" name="replyname" value="'.$this->replytoname.'" />';
        			$out.= '<input type="hidden" id="replymail" name="replymail" value="'.$this->replytomail.'" />';
        			$out.= "<tr><td>".$langs->trans("MailReply")."</td><td>".$this->replytoname.($this->replytomail?(" &lt;".$this->replytomail."&gt;"):"");
        			$out.= "</td></tr>\n";
        		}
        	}

        	// Errorsto
        	if (! empty($this->witherrorsto))
        	{
        		//if (! $this->errorstomail) $this->errorstomail=$this->frommail;
        		$errorstomail = (! empty($conf->global->MAIN_MAIL_ERRORS_TO) ? $conf->global->MAIN_MAIL_ERRORS_TO : $this->errorstomail);
        		if ($this->witherrorstoreadonly)
        		{
        			$out.= '<input type="hidden" id="errorstomail" name="errorstomail" value="'.$errorstomail.'" />';
        			$out.= '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td>';
        			$out.= $errorstomail;
        			$out.= "</td></tr>\n";
        		}
        		else
        		{
        			$out.= '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td>';
        			$out.= '<input size="30" id="errorstomail" name="errorstomail" value="'.$errorstomail.'" />';
        			$out.= "</td></tr>\n";
        		}
        	}

        	// To
        	if (! empty($this->withto) || is_array($this->withto))
        	{
        		$out.= '<tr><td width="180">';
        		if ($this->withtofree) $out.= $form->textwithpicto($langs->trans("MailTo"),$langs->trans("YouCanUseCommaSeparatorForSeveralRecipients"));
        		else $out.= $langs->trans("MailTo");
        		$out.= '</td><td>';
        		if ($this->withtoreadonly)
        		{
        			if (! empty($this->toname) && ! empty($this->tomail))
        			{
        				$out.= '<input type="hidden" id="toname" name="toname" value="'.$this->toname.'" />';
        				$out.= '<input type="hidden" id="tomail" name="tomail" value="'.$this->tomail.'" />';
        				if ($this->totype == 'thirdparty')
        				{
        					$soc=new Societe($this->db);
        					$soc->fetch($this->toid);
        					$out.= $soc->getNomUrl(1);
        				}
        				else if ($this->totype == 'contact')
        				{
        					$contact=new Contact($this->db);
        					$contact->fetch($this->toid);
        					$out.= $contact->getNomUrl(1);
        				}
        				else
        				{
        					$out.= $this->toname;
        				}
        				$out.= ' &lt;'.$this->tomail.'&gt;';
        				if ($this->withtofree)
        				{
        					$out.= '<br>'.$langs->trans("or").' <input size="'.(is_array($this->withto)?"30":"60").'" id="sendto" name="sendto" value="'.(! is_array($this->withto) && ! is_numeric($this->withto)? (isset($_REQUEST["sendto"])?$_REQUEST["sendto"]:$this->withto) :"").'" />';
        				}
        			}
        			else
        			{
        				$out.= (! is_array($this->withto) && ! is_numeric($this->withto))?$this->withto:"";
        			}
        		}
        		else
        		{
        			if (! empty($this->withtofree))
        			{
        				$out.= '<input size="'.(is_array($this->withto)?"30":"60").'" id="sendto" name="sendto" value="'.(! is_array($this->withto) && ! is_numeric($this->withto)? (isset($_REQUEST["sendto"])?$_REQUEST["sendto"]:$this->withto) :"").'" />';
        			}
        			if (! empty($this->withto) && is_array($this->withto))
        			{
        				if (! empty($this->withtofree)) $out.= " ".$langs->trans("or")." ";
        				$out.= $form->selectarray("receiver", $this->withto, GETPOST("receiver"), 1, 0, 0, '', 0, 0, 0, '', '', 0, '', $disablebademails);
        			}
        			if (isset($this->withtosocid) && $this->withtosocid > 0) // deprecated. TODO Remove this. Instead, fill withto with array before calling method.
        			{
        				$liste=array();
        				$soc=new Societe($this->db);
        				$soc->fetch($this->withtosocid);
        				foreach ($soc->thirdparty_and_contact_email_array(1) as $key=>$value)
        				{
        					$liste[$key]=$value;
        				}
        				if ($this->withtofree) $out.= " ".$langs->trans("or")." ";
        				$out.= $form->selectarray("receiver", $liste, GETPOST("receiver"), 1, 0, 0, '', 0, 0, 0, '', '', 0, '', $disablebademails);
        			}
        		}
        		$out.= "</td></tr>\n";
        	}

        	// CC
        	if (! empty($this->withtocc) || is_array($this->withtocc))
        	{
        		$out.= '<tr><td width="180">';
        		$out.= $form->textwithpicto($langs->trans("MailCC"),$langs->trans("YouCanUseCommaSeparatorForSeveralRecipients"));
        		$out.= '</td><td>';
        		if ($this->withtoccreadonly)
        		{
        			$out.= (! is_array($this->withtocc) && ! is_numeric($this->withtocc))?$this->withtocc:"";
        		}
        		else
        		{
        			$out.= '<input size="'.(is_array($this->withtocc)?"30":"60").'" id="sendtocc" name="sendtocc" value="'.((! is_array($this->withtocc) && ! is_numeric($this->withtocc))? (isset($_POST["sendtocc"])?$_POST["sendtocc"]:$this->withtocc) : (isset($_POST["sendtocc"])?$_POST["sendtocc"]:"") ).'" />';
        			if (! empty($this->withtocc) && is_array($this->withtocc))
        			{
        				$out.= " ".$langs->trans("or")." ";
        				$out.= $form->selectarray("receivercc", $this->withtocc, GETPOST("receivercc"), 1, 0, 0, '', 0, 0, 0, '', '', 0, '', $disablebademails);
        			}
        		}
        		$out.= "</td></tr>\n";
        	}

        	// CCC
        	if (! empty($this->withtoccc) || is_array($this->withtoccc))
        	{
        		$out.= '<tr><td width="180">';
        		$out.= $form->textwithpicto($langs->trans("MailCCC"),$langs->trans("YouCanUseCommaSeparatorForSeveralRecipients"));
        		$out.= '</td><td>';
        		if (! empty($this->withtocccreadonly))
        		{
        			$out.= (! is_array($this->withtoccc) && ! is_numeric($this->withtoccc))?$this->withtoccc:"";
        		}
        		else
        		{
        			$out.= '<input size="'.(is_array($this->withtoccc)?"30":"60").'" id="sendtoccc" name="sendtoccc" value="'.((! is_array($this->withtoccc) && ! is_numeric($this->withtoccc))? (isset($_POST["sendtoccc"])?$_POST["sendtoccc"]:$this->withtoccc) : (isset($_POST["sendtoccc"])?$_POST["sendtoccc"]:"") ).'" />';
        			if (! empty($this->withtoccc) && is_array($this->withtoccc))
        			{
        				$out.= " ".$langs->trans("or")." ";
        				$out.= $form->selectarray("receiverccc", $this->withtoccc, GETPOST("receiverccc"), 1);
        			}
        		}

        		$showinfobcc='';
        		if (! empty($conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO) && ! empty($this->param['models']) && $this->param['models'] == 'propal_send') $showinfobcc=$conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO;
				if (! empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO) && ! empty($this->param['models']) && $this->param['models'] == 'supplier_proposal_send') $showinfobcc=$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO;
        		if (! empty($conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO) && ! empty($this->param['models']) && $this->param['models'] == 'order_send') $showinfobcc=$conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO;
        		if (! empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO) && ! empty($this->param['models']) && $this->param['models'] == 'facture_send') $showinfobcc=$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO;
        		if ($showinfobcc) $out.=' + '.$showinfobcc;
        		$out.= "</td></tr>\n";
        	}

        	// Ask delivery receipt
        	if (! empty($this->withdeliveryreceipt))
        	{
        		$out.= '<tr><td width="180">'.$langs->trans("DeliveryReceipt").'</td><td>';

        		if (! empty($this->withdeliveryreceiptreadonly))
        		{
        			$out.= yn($this->withdeliveryreceipt);
        		}
        		else
        		{
        			$defaultvaluefordeliveryreceipt=0;
        			if (! empty($conf->global->MAIL_FORCE_DELIVERY_RECEIPT_PROPAL) && ! empty($this->param['models']) && $this->param['models'] == 'propal_send') $defaultvaluefordeliveryreceipt=1;
					if (! empty($conf->global->MAIL_FORCE_DELIVERY_RECEIPT_SUPPLIER_PROPOSAL) && ! empty($this->param['models']) && $this->param['models'] == 'supplier_proposal_send') $defaultvaluefordeliveryreceipt=1;
        			if (! empty($conf->global->MAIL_FORCE_DELIVERY_RECEIPT_ORDER) && ! empty($this->param['models']) && $this->param['models'] == 'order_send') $defaultvaluefordeliveryreceipt=1;
        			if (! empty($conf->global->MAIL_FORCE_DELIVERY_RECEIPT_INVOICE) && ! empty($this->param['models']) && $this->param['models'] == 'facture_send') $defaultvaluefordeliveryreceipt=1;
        			$out.= $form->selectyesno('deliveryreceipt', (isset($_POST["deliveryreceipt"])?$_POST["deliveryreceipt"]:$defaultvaluefordeliveryreceipt), 1);
        		}

        		$out.= "</td></tr>\n";
        	}

        	// Topic
        	if (! empty($this->withtopic))
        	{
        		$defaulttopic="";
        		if (count($arraydefaultmessage) > 0 && $arraydefaultmessage['topic']) $defaulttopic=$arraydefaultmessage['topic'];
        		elseif (! is_numeric($this->withtopic))	 $defaulttopic=$this->withtopic;

        		$defaulttopic=make_substitutions($defaulttopic,$this->substit);

        		$out.= '<tr>';
        		$out.= '<td width="180">'.$langs->trans("MailTopic").'</td>';
        		$out.= '<td>';
        		if ($this->withtopicreadonly)
        		{
        			$out.= $defaulttopic;
        			$out.= '<input type="hidden" size="60" id="subject" name="subject" value="'.$defaulttopic.'" />';
        		}
        		else
        		{
        			$out.= '<input type="text" size="60" id="subject" name="subject" value="'. ((isset($_POST["subject"]) && ! $_POST['modelselected'])?$_POST["subject"]:($defaulttopic?$defaulttopic:'')) .'" />';
        		}
        		$out.= "</td></tr>\n";
        	}

        	// Attached files
        	if (! empty($this->withfile))
        	{
        		$out.= '<tr>';
        		$out.= '<td width="180">'.$langs->trans("MailFile").'</td>';

        		$out.= '<td>';
        		if (is_numeric($this->withfile))
        		{
	        		// TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
	        		$out.= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">'."\n";
	        		$out.= '<script type="text/javascript" language="javascript">';
	        		$out.= 'jQuery(document).ready(function () {';
	        		$out.= '    jQuery(".removedfile").click(function() {';
	        		$out.= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
	        		$out.= '    });';
	        		$out.= '})';
	        		$out.= '</script>'."\n";
	        		if (count($listofpaths))
	        		{
	        			foreach($listofpaths as $key => $val)
	        			{
	        				$out.= '<div id="attachfile_'.$key.'">';
	        				$out.= img_mime($listofnames[$key]).' '.$listofnames[$key];
	        				if (! $this->withfilereadonly)
	        				{
	        					$out.= ' <input type="image" style="border: 0px;" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" value="'.($key+1).'" class="removedfile" id="removedfile_'.$key.'" name="removedfile_'.$key.'" />';
	        					//$out.= ' <a href="'.$_SERVER["PHP_SELF"].'?removedfile='.($key+1).' id="removedfile_'.$key.'">'.img_delete($langs->trans("Delete").'</a>';
	        				}
	        				$out.= '<br></div>';
	        			}
	        		}
	        		else
	        		{
	        			$out.= $langs->trans("NoAttachedFiles").'<br>';
	        		}
	        		if ($this->withfile == 2)	// Can add other files
	        		{
	        			if (!empty($conf->global->FROM_MAIL_USE_INPUT_FILE_MULTIPLE)) $out.= '<input type="file" class="flat" id="addedfile" name="addedfile[]" value="'.$langs->trans("Upload").'" multiple />';
						else $out.= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
	        			$out.= ' ';
	        			$out.= '<input class="button" type="submit" id="'.$addfileaction.'" name="'.$addfileaction.'" value="'.$langs->trans("MailingAddFile").'" />';
	        		}
        		}
        		else
        		{
        			$out.=$this->withfile;
        		}
        		$out.= "</td></tr>\n";
        	}

        	// Message
        	if (! empty($this->withbody))
        	{
        		$defaultmessage="";
        		if (count($arraydefaultmessage) > 0 && $arraydefaultmessage['content']) $defaultmessage=$arraydefaultmessage['content'];
        		elseif (! is_numeric($this->withbody))	$defaultmessage=$this->withbody;

        		// Complete substitution array
        		if (! empty($conf->paypal->enabled) && ! empty($conf->global->PAYPAL_ADD_PAYMENT_URL))
        		{
        			require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';

        			$langs->load('paypal');

        			// Set the paypal message and url link into __PERSONALIZED__ key
        			if ($this->param["models"]=='order_send')
        			{
        				$url=getPaypalPaymentUrl(0,'order',$this->substit['__ORDERREF__']?$this->substit['__ORDERREF__']:$this->substit['__REF__']);
        				$this->substit['__PERSONALIZED__']=str_replace('\n',"\n",$langs->transnoentitiesnoconv("PredefinedMailContentLink",$url));
        			}
        			if ($this->param["models"]=='facture_send')
        			{
        				$url=getPaypalPaymentUrl(0,'invoice',$this->substit['__REF__']);
        				$this->substit['__PERSONALIZED__']=str_replace('\n',"\n",$langs->transnoentitiesnoconv("PredefinedMailContentLink",$url));
        			}
        		}

				$defaultmessage=str_replace('\n',"\n",$defaultmessage);

				// Deal with format differences between message and signature (text / HTML)
				if(dol_textishtml($defaultmessage) && !dol_textishtml($this->substit['__SIGNATURE__'])) {
					$this->substit['__SIGNATURE__'] = dol_nl2br($this->substit['__SIGNATURE__']);
				} else if(!dol_textishtml($defaultmessage) && dol_textishtml($this->substit['__SIGNATURE__'])) {
					$defaultmessage = dol_nl2br($defaultmessage);
				}


        		if (isset($_POST["message"]) &&  ! $_POST['modelselected']) $defaultmessage=$_POST["message"];
				else
				{
					$defaultmessage=make_substitutions($defaultmessage,$this->substit);
					// Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
					$defaultmessage=preg_replace("/^(<br>)+/","",$defaultmessage);
					$defaultmessage=preg_replace("/^\n+/","",$defaultmessage);
				}

        		$out.= '<tr>';
        		$out.= '<td width="180" valign="top">'.$langs->trans("MailText").'</td>';
        		$out.= '<td>';
        		if ($this->withbodyreadonly)
        		{
        			$out.= nl2br($defaultmessage);
        			$out.= '<input type="hidden" id="message" name="message" value="'.$defaultmessage.'" />';
        		}
        		else
        		{
        			if (! isset($this->ckeditortoolbar)) $this->ckeditortoolbar = 'dolibarr_notes';

        			// Editor wysiwyg
        			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
        			if ($this->withfckeditor == -1)
        			{
        				if (! empty($conf->global->FCKEDITOR_ENABLE_MAIL)) $this->withfckeditor=1;
						else $this->withfckeditor=0;
        			}

        			$doleditor=new DolEditor('message',$defaultmessage,'',280,$this->ckeditortoolbar,'In',true,true,$this->withfckeditor,8,'95%');
        			$out.= $doleditor->Create(1);
        		}
        		$out.= "</td></tr>\n";
        	}

        	$out.= '</table>'."\n";

        	if ($this->withform == 1 || $this->withform == -1)
        	{
        		$out.= '<br><div class="center">';
        		$out.= '<input class="button" type="submit" id="sendmail" name="sendmail" value="'.$langs->trans("SendMail").'"';
        		// Add a javascript test to avoid to forget to submit file before sending email
        		if ($this->withfile == 2 && $conf->use_javascript_ajax)
        		{
        			$out.= ' onClick="if (document.mailform.addedfile.value != \'\') { alert(\''.dol_escape_js($langs->trans("FileWasNotUploaded")).'\'); return false; } else { return true; }"';
        		}
        		$out.= ' />';
        		if ($this->withcancel)
        		{
        			$out.= ' &nbsp; &nbsp; ';
        			$out.= '<input class="button" type="submit" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'" />';
        		}
        		$out.= '</div>'."\n";
        	}

        	if ($this->withform == 1) $out.= '</form>'."\n";

        	// Disable enter key if option MAIN_MAILFORM_DISABLE_ENTERKEY is set
        	if (! empty($conf->global->MAIN_MAILFORM_DISABLE_ENTERKEY))
        	{
	        	$out.= '<script type="text/javascript" language="javascript">';
		        $out.= 'jQuery(document).ready(function () {';
				$out.= '	$(document).on("keypress", \'#mailform\', function (e) {		/* Note this is calle at every key pressed ! */
	    						var code = e.keyCode || e.which;
	    						if (code == 13) {
	        						e.preventDefault();
	        						return false;
	    						}
							});';
				$out.='		})';
				$out.= '</script>';
        	}

        	$out.= "<!-- End form mail -->\n";

        	return $out;
        }
    }



	/**
	 *      Return template of email
	 *      Search into table c_email_templates
	 *
	 * 		@param	DoliDB		$db				Database handler
	 * 		@param	string		$type_template	Get message for key module
	 *      @param	string		$user			Use template public or limited to this user
	 *      @param	Translate	$outputlangs	Output lang object
	 *      @param	int			$id				Id template to find
	 *      @param  int         $active         1=Only active template, 0=Only disabled, -1=All
	 *      @return array						array('topic'=>,'content'=>,..)
	 */
	private function getEMailTemplate($db, $type_template, $user, $outputlangs, $id=0, $active=1)
	{
		$ret=array();

		$sql = "SELECT label, topic, content, lang";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_email_templates';
		$sql.= " WHERE type_template='".$db->escape($type_template)."'";
		$sql.= " AND entity IN (".getEntity("c_email_templates").")";
		$sql.= " AND (fk_user is NULL or fk_user = 0 or fk_user = ".$user->id.")";
		if ($active >= 0) $sql.=" AND active = ".$active;
		if (is_object($outputlangs)) $sql.= " AND (lang = '".$outputlangs->defaultlang."' OR lang IS NULL OR lang = '')";
		if (!empty($id)) $sql.= " AND rowid=".$id;
		$sql.= $db->order("lang,label","ASC");
		//print $sql;

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);	// Get first found
			if ($obj)
			{
				$ret['label']=$obj->label;
				$ret['topic']=$obj->topic;
				$ret['content']=$obj->content;
				$ret['lang']=$obj->lang;
			}
			else
			{
				$defaultmessage='';
				if     ($type_template=='facture_send')	            { $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendInvoice"); }
	        	elseif ($type_template=='facture_relance')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendInvoiceReminder"); }
	        	elseif ($type_template=='propal_send')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendProposal"); }
	        	elseif ($type_template=='supplier_proposal_send')	{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierProposal"); }
	        	elseif ($type_template=='order_send')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendOrder"); }
	        	elseif ($type_template=='order_supplier_send')		{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierOrder"); }
	        	elseif ($type_template=='invoice_supplier_send')	{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierInvoice"); }
	        	elseif ($type_template=='shipping_send')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendShipping"); }
	        	elseif ($type_template=='fichinter_send')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendFichInter"); }
	        	elseif ($type_template=='thirdparty')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentThirdparty"); }

	        	$ret['label']='default';
	        	$ret['topic']='';
	        	$ret['content']=$defaultmessage;
	        	$ret['lang']=$outputlangs->defaultlang;
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
		$ret=array();

		$sql = "SELECT label, topic, content, lang";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_email_templates';
		$sql.= " WHERE type_template='".$this->db->escape($type_template)."'";
		$sql.= " AND entity IN (".getEntity("c_email_templates").")";
		$sql.= " AND (fk_user is NULL or fk_user = 0 or fk_user = ".$user->id.")";
		if (is_object($outputlangs)) $sql.= " AND (lang = '".$outputlangs->defaultlang."' OR lang IS NULL OR lang = '')";
		$sql.= $this->db->order("lang,label","ASC");
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num= $this->db->num_rows($resql);
			$this->db->free($resql);
			return $num;
		}
		else
		{
			$this->error=get_class($this).' '.__METHOD__.' ERROR:'.$this->db->lasterror();
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
	 *      @param  int         $active         1=Only active template, 0=Only disabled, -1=All
	 *      @return	int		                    <0 if KO, nb of records found if OK
	 */
	public function fetchAllEMailTemplate($type_template, $user, $outputlangs, $active=1)
	{
		$ret=array();

		$sql = "SELECT rowid, label, topic, content, lang, position";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_email_templates';
		$sql.= " WHERE type_template='".$this->db->escape($type_template)."'";
		$sql.= " AND entity IN (".getEntity("c_email_templates").")";
		$sql.= " AND (fk_user is NULL or fk_user = 0 or fk_user = ".$user->id.")";
		if ($active >= 0) $sql.=" AND active = ".$active;
		if (is_object($outputlangs)) $sql.= " AND (lang = '".$outputlangs->defaultlang."' OR lang IS NULL OR lang = '')";
		$sql.= $this->db->order("position,lang,label","ASC");
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$this->lines_model=array();
			while ($obj = $this->db->fetch_object($resql))
			{
				$line = new ModelMail();
				$line->id=$obj->rowid;
				$line->label=$obj->label;
				$line->topic=$obj->topic;
				$line->content=$obj->content;
				$line->lang=$obj->lang;
				$this->lines_model[]=$line;
			}
			$this->db->free($resql);
			return $num;
		}
		else
		{
			$this->error=get_class($this).' '.__METHOD__.' ERROR:'.$this->db->lasterror();
			return -1;
		}
	}
	
	
	
	/**
	 * Set substit array from object
	 * 
	 * @param	Object	   $object		  Object to use
	 * @param   Translate  $outputlangs   Object lang 
	 * @return	void
	 */
	function setSubstitFromObject($object, $outputlangs=null)
	{
		global $user;
		$this->substit['__REF__'] = $object->ref;
		$this->substit['__REFCLIENT__'] = $object->ref_client;
		$this->substit['__REFSUPPLIER__'] = $object->ref_supplier;

		$this->substit['__DATE_YMD__'] = dol_print_date($object->date, 'day', 0, $outputlangs);
		$this->substit['__DATE_DUE_YMD__'] = dol_print_date($object->date_lim_reglement, 'day', 0, $outputlangs);
		$this->substit['__AMOUNT__'] = price($object->total_ttc);
		$this->substit['__AMOUNT_WO_TAX__'] = price($object->total_ht);
		
		$this->substit['__THIRDPARTY_ID__'] = (is_object($object->thirdparty)?$object->thirdparty->id:'');
		$this->substit['__THIRDPARTY_NAME__'] = (is_object($object->thirdparty)?$object->thirdparty->name:'');
		
		$this->substit['__PROJECT_ID__'] = (is_object($object->projet)?$object->projet->id:'');
		$this->substit['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
		$this->substit['__PROJECT_NAME__'] = (is_object($object->projet)?$object->projet->title:'');
		
		$this->substit['__SIGNATURE__'] = $user->signature;
		$this->substit['__PERSONALIZED__'] = '';
		$this->substit['__CONTACTCIVNAME__'] = '';	// Will be replace just before sending
	}
	
	/**
	 * Set substit array from object
	 * 
	 * @param	string	$mode		'form' or 'emailing'
	 * @return	void
	 */
	function getAvailableSubstitKey($mode='form')
	{
		global $conf;
		
		$vars=array();
		
		if ($mode == 'form')
		{
			$vars=array(
				'__REF__', 
				'__REFCLIENT__', 
				'__THIRDPARTY_NAME__', 
				'__PROJECT_REF__', 
				'__PROJECT_NAME__',
				'__CONTACTCIVNAME__',
				'__PERSONALIZED__',			// Paypal link will be added here in form mode
				'__SIGNATURE__', 
			);
		}
		if ($mode == 'emailing')
		{
			// For mass emailing, we have different keys
			$vars=array(
			    '__ID__' => 'IdRecord',
			    '__EMAIL__' => 'EMailRecipient',
			    '__LASTNAME__' => 'Lastname',
			    '__FIRSTNAME__' => 'Firstname',
			    '__MAILTOEMAIL__' => 'TagMailtoEmail',
			    '__OTHER1__' => 'Other1',
			    '__OTHER2__' => 'Other2',
			    '__OTHER3__' => 'Other3',
			    '__OTHER4__' => 'Other4',
			    '__OTHER5__' => 'Other5',
			    '__SIGNATURE__' => 'TagSignature',
			    '__CHECK_READ__' => 'TagCheckMail',
				'__UNSUBSCRIBE__' => 'TagUnsubscribe'
				//,'__PERSONALIZED__' => 'Personalized'	// Hidden because not used yet in mass emailing
			);
			if (! empty($conf->paypal->enabled) && ! empty($conf->global->PAYPAL_SECURITY_TOKEN))
			{
				$vars['__SECUREKEYPAYPAL__']='SecureKeyPaypal';
				if (! empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
				{
					if ($conf->adherent->enabled) $vars['__SECUREKEYPAYPAL_MEMBER__']='SecureKeyPaypalUniquePerMember';
					if ($conf->facture->enabled) $vars['__SECUREKEYPAYPAL_INVOICE__']='SecureKeyPaypalUniquePerInvoice';
					if ($conf->commande->enabled) $vars['__SECUREKEYPAYPAL_ORDER__']='SecureKeyPaypalUniquePerOrder';
					if ($conf->contrat->enabled) $vars['__SECUREKEYPAYPAL_CONTRACTLINE__']='SecureKeyPaypalUniquePerContractLine';
				}
			}
			else 
			{
				$vars['__SECUREKEYPAYPAL__']='';
				$vars['__SECUREKEYPAYPAL_MEMBER__']='';
			}
		}
		return $vars;
	}

}


/**
 * ModelMail
 */
class ModelMail
{
	public $id;
	public $label;
	public $topic;
	public $content;
	public $lang;
}
