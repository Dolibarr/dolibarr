<?php
/* Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011 Juanjo Menent		<jmenent@2byte.es>
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
class FormMail
{
    var $db;

    var $withform;

    var $fromname;
    var $frommail;
    var $replytoname;
    var $replytomail;
    var $toname;
    var $tomail;

    var $withsubstit;			// Show substitution array
    var $withfrom;
    var $withto;
    var $withtofree;
    var $withtocc;
    var $withtoccc;
    var $withtopic;
    var $withfile;				// 0=No attaches files, 1=Show attached files, 2=Can add new attached files
    var $withbody;

    var $withfromreadonly;
    var $withreplytoreadonly;
    var $withtoreadonly;
    var $withtoccreadonly;
    var $withtopicreadonly;
    var $withfilereadonly;
    var $withdeliveryreceipt;
    var $withcancel;
    var $withfckeditor;

    var $substit=array();
    var $param=array();

    var $error;


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
        $this->witherrorstoreadonly=0;
        $this->withtopicreadonly=0;
        $this->withfilereadonly=0;
        $this->withbodyreadonly=0;
        $this->withdeliveryreceiptreadonly=0;
        $this->withfckeditor=0;

        return 1;
    }

    /**
     * Clear list of attached files in send mail form (stored in session)
     *
     * @return	void
     */
    function clear_attached_files()
    {
        global $conf,$user;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        // Set tmp user directory
        $vardir=$conf->user->dir_output."/".$user->id;
        $upload_dir = $vardir.'/temp/';
        if (is_dir($upload_dir)) dol_delete_dir_recursive($upload_dir);

        unset($_SESSION["listofpaths"]);
        unset($_SESSION["listofnames"]);
        unset($_SESSION["listofmimes"]);
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
        if (! empty($_SESSION["listofpaths"])) $listofpaths=explode(';',$_SESSION["listofpaths"]);
        if (! empty($_SESSION["listofnames"])) $listofnames=explode(';',$_SESSION["listofnames"]);
        if (! empty($_SESSION["listofmimes"])) $listofmimes=explode(';',$_SESSION["listofmimes"]);
        if (! in_array($file,$listofnames))
        {
            $listofpaths[]=$path;
            $listofnames[]=$file;
            $listofmimes[]=$type;
            $_SESSION["listofpaths"]=join(';',$listofpaths);
            $_SESSION["listofnames"]=join(';',$listofnames);
            $_SESSION["listofmimes"]=join(';',$listofmimes);
        }
    }

    /**
     * Remove a file from the list of attached files (stored in SECTION array)
     *
     * @param  	string	$keytodelete     Key in file array
     * @return	void
     */
    function remove_attached_files($keytodelete)
    {
        $listofpaths=array();
        $listofnames=array();
        $listofmimes=array();
        if (! empty($_SESSION["listofpaths"])) $listofpaths=explode(';',$_SESSION["listofpaths"]);
        if (! empty($_SESSION["listofnames"])) $listofnames=explode(';',$_SESSION["listofnames"]);
        if (! empty($_SESSION["listofmimes"])) $listofmimes=explode(';',$_SESSION["listofmimes"]);
        if ($keytodelete >= 0)
        {
            unset ($listofpaths[$keytodelete]);
            unset ($listofnames[$keytodelete]);
            unset ($listofmimes[$keytodelete]);
            $_SESSION["listofpaths"]=join(';',$listofpaths);
            $_SESSION["listofnames"]=join(';',$listofnames);
            $_SESSION["listofmimes"]=join(';',$listofmimes);
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
        if (! empty($_SESSION["listofpaths"])) $listofpaths=explode(';',$_SESSION["listofpaths"]);
        if (! empty($_SESSION["listofnames"])) $listofnames=explode(';',$_SESSION["listofnames"]);
        if (! empty($_SESSION["listofmimes"])) $listofmimes=explode(';',$_SESSION["listofmimes"]);
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
     *
     *	@param	string	$addfileaction		Name of action when posting file attachments
     *	@param	string	$removefileaction	Name of action when removing file attachments
     *	@return string						Form to show
     */
    function get_form($addfileaction='addfile',$removefileaction='removefile')
    {
        global $conf, $langs, $user, $hookmanager;

        $langs->load("other");
        $langs->load("mails");

        $hookmanager->initHooks(array('formmail'));

        $parameters=array(
        		'addfileaction' => $addfileaction,
        		'removefileaction'=> $removefileaction
        );
        $reshook=$hookmanager->executeHooks('getFormMail', $parameters, $this);

        if (!empty($reshook))
        {
        	return $hookmanager->resPrint;
        }
        else
        {
        	$out='';

        	// Define list of attached files
        	$listofpaths=array();
        	$listofnames=array();
        	$listofmimes=array();
        	if (! empty($_SESSION["listofpaths"])) $listofpaths=explode(';',$_SESSION["listofpaths"]);
        	if (! empty($_SESSION["listofnames"])) $listofnames=explode(';',$_SESSION["listofnames"]);
        	if (! empty($_SESSION["listofmimes"])) $listofmimes=explode(';',$_SESSION["listofmimes"]);

        	$form=new Form($this->db);

        	$out.= "\n<!-- Debut form mail -->\n";
        	if ($this->withform)
        	{
        		$out.= '<form method="POST" name="mailform" enctype="multipart/form-data" action="'.$this->param["returnurl"].'">'."\n";
        		$out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
        	}
        	foreach ($this->param as $key=>$value)
        	{
        		$out.= '<input type="hidden" id="'.$key.'" name="'.$key.'" value="'.$value.'" />'."\n";
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
        		$out.= $form->textwithpicto($langs->trans("EMailTestSubstitutionReplacedByGenericValues"),$help);
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
        			if ($this->fromtype == 'user' && $this->fromid > 0)
        			{
        				$langs->load("users");
        				$fuser=new User($this->db);
        				$fuser->fetch($this->fromid);
        				$out.= $fuser->getNomUrl(1);
        			}
        			else
        			{
        				$out.= $this->fromname;
        			}
        			if ($this->frommail)
        			{
        				$out.= " &lt;".$this->frommail."&gt;";
        			}
        			else
        			{
        				if ($this->fromtype)
        				{
        					$langs->load("errors");
        					$out.= '<font class="warning"> &lt;'.$langs->trans("ErrorNoMailDefinedForThisUser").'&gt; </font>';
        				}
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
        				$out.= $form->selectarray("receiver", $this->withto, GETPOST("receiver"), 1);
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
        				$out.= $form->selectarray("receiver", $liste, GETPOST("receiver"), 1);
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
        				$out.= $form->selectarray("receivercc", $this->withto, GETPOST("receivercc"), 1);
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
        				$out.= $form->selectarray("receiverccc", $this->withto, GETPOST("receiverccc"), 1);
        			}
        		}
        		//if (! empty($conf->global->MAIN_MAIL_AUTOCOPY_TO)) print ' '.info_admin("+ ".$conf->global->MAIN_MAIL_AUTOCOPY_TO,1);
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
        			$out.= $form->selectyesno('deliveryreceipt', (isset($_POST["deliveryreceipt"])?$_POST["deliveryreceipt"]:0), 1);
        		}

        		$out.= "</td></tr>\n";
        	}

        	// Topic
        	if (! empty($this->withtopic))
        	{
        		$this->withtopic=make_substitutions($this->withtopic,$this->substit);

        		$out.= '<tr>';
        		$out.= '<td width="180">'.$langs->trans("MailTopic").'</td>';
        		$out.= '<td>';
        		if ($this->withtopicreadonly)
        		{
        			$out.= $this->withtopic;
        			$out.= '<input type="hidden" size="60" id="subject" name="subject" value="'.$this->withtopic.'" />';
        		}
        		else
        		{
        			$out.= '<input type="text" size="60" id="subject" name="subject" value="'. (isset($_POST["subject"])?$_POST["subject"]:$this->withtopic) .'" />';
        		}
        		$out.= "</td></tr>\n";
        	}

        	// Attached files
        	if (! empty($this->withfile))
        	{
        		$out.= '<tr>';
        		$out.= '<td width="180">'.$langs->trans("MailFile").'</td>';
        		$out.= '<td>';
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
        			$out.= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
        			$out.= ' ';
        			$out.= '<input type="submit" class="button" id="'.$addfileaction.'" name="'.$addfileaction.'" value="'.$langs->trans("MailingAddFile").'" />';
        		}
        		$out.= "</td></tr>\n";
        	}

        	// Message
        	if (! empty($this->withbody))
        	{
        		$defaultmessage="";

        		// TODO    A partir du type, proposer liste de messages dans table llx_models
        		if     ($this->param["models"]=='facture_send')	            { $defaultmessage=$langs->transnoentities("PredefinedMailContentSendInvoice"); }
        		elseif ($this->param["models"]=='facture_relance')			{ $defaultmessage=$langs->transnoentities("PredefinedMailContentSendInvoiceReminder"); }
        		elseif ($this->param["models"]=='propal_send')				{ $defaultmessage=$langs->transnoentities("PredefinedMailContentSendProposal"); }
        		elseif ($this->param["models"]=='order_send')				{ $defaultmessage=$langs->transnoentities("PredefinedMailContentSendOrder"); }
        		elseif ($this->param["models"]=='order_supplier_send')		{ $defaultmessage=$langs->transnoentities("PredefinedMailContentSendSupplierOrder"); }
        		elseif ($this->param["models"]=='invoice_supplier_send')	{ $defaultmessage=$langs->transnoentities("PredefinedMailContentSendSupplierInvoice"); }
        		elseif ($this->param["models"]=='shipping_send')			{ $defaultmessage=$langs->transnoentities("PredefinedMailContentSendShipping"); }
        		elseif ($this->param["models"]=='fichinter_send')			{ $defaultmessage=$langs->transnoentities("PredefinedMailContentSendFichInter"); }
        		elseif (! is_numeric($this->withbody))                      { $defaultmessage=$this->withbody; }

        		// Complete substitution array
        		if (! empty($conf->paypal->enabled) && ! empty($conf->global->PAYPAL_ADD_PAYMENT_URL))
        		{
        			require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';

        			$langs->load('paypal');

        			if ($this->param["models"]=='order_send')
        			{
        				$url=getPaypalPaymentUrl(0,'order',$this->substit['__ORDERREF__']);
        				$this->substit['__PERSONALIZED__']=$langs->transnoentitiesnoconv("PredefinedMailContentLink",$url);
        			}
        			if ($this->param["models"]=='facture_send')
        			{
        				$url=getPaypalPaymentUrl(0,'invoice',$this->substit['__FACREF__']);
        				$this->substit['__PERSONALIZED__']=$langs->transnoentitiesnoconv("PredefinedMailContentLink",$url);
        			}
        		}

        		$defaultmessage=make_substitutions($defaultmessage,$this->substit);
        		if (isset($_POST["message"])) $defaultmessage=$_POST["message"];
        		$defaultmessage=str_replace('\n',"\n",$defaultmessage);

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
        			if (!empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
        				$this->withfckeditor=1;
        			}
        			$doleditor=new DolEditor('message',$defaultmessage,'',280,$this->ckeditortoolbar,'In',true,true,$this->withfckeditor,8,72);
        			$out.= $doleditor->Create(1);
        		}
        		$out.= "</td></tr>\n";
        	}

        	if (! empty($this->withform))
        	{
        		$out.= '<tr><td align="center" colspan="2"><center>';
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
        		$out.= '</center></td></tr>'."\n";
        	}

        	$out.= '</table>'."\n";

        	if (! empty($this->withform)) $out.= '</form>'."\n";
        	$out.= "<!-- Fin form mail -->\n";

        	return $out;
        }
    }
}

?>
