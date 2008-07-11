<?PHP
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/html.formmail.class.php
        \brief      Fichier de la classe permettant la génération du formulaire html d'envoi de mail unitaire
        \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT ."/lib/functions.lib.php");


/**     \class      FormMail
        \brief      Classe permettant la génération du formulaire html d'envoi de mail unitaire
        \remarks    Utilisation: $formail = new FormMail($db)
        \remarks                 $formmail->proprietes=1 ou chaine ou tableau de valeurs
        \remarks                 $formmail->show_form() affiche le formulaire
*/
class FormMail
{
	var $db;
	
	var $fromname;
	var $frommail;
	var $replytoname;
	var $replytomail;
	var $toname;
	var $tomail;
	
	var $withsubstit;			// Show substitution array
	var $withfrom;
	var $withto;
	var $withtocc;
	var $withtopic;
	var $withfile;				// 0=No attaches files, 1=Show attached files, 2=Can add new attached files
	var $withbody;
	
	var $withfromreadonly;
	var $withreplytoreadonly;
	var $withtoreadonly;
	var $withtoccreadonly;
	var $withtopicreadonly;
	var $withdeliveryreceipt;
	var $withcancel;
	
	var $substit=array();
	var $param=array();
	
	var $error;

  
	/**
		\brief     Constructeur
	    \param     DB      handler d'accès base de donnée
	*/
	function FormMail($DB)
	{
		$this->db = $DB;
		
		$this->withfrom=1;
		$this->withto=1;
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
		$this->withbodyreadonly=0;
		$this->withdeliveryreceiptreadonly=0;
		
		return 1;
	}

	/**
	 * Clear list of attached files (store in SECTION array)
	 */
	function clear_attached_files()
	{	
		global $conf,$user;
		
		$conf->users->dir_tmp=DOL_DATA_ROOT."/users/".$user->id;
		$upload_dir = $conf->users->dir_tmp.'/temp';
		if (is_dir($upload_dir)) dol_delete_dir_recursive($upload_dir);
		
		unset($_SESSION["listofpaths"]);
		unset($_SESSION["listofnames"]);
		unset($_SESSION["listofmimes"]);
	}
	
	/**
	 * Add a file into the list of attached files (stored in SECTION array)
	 *
	 * @param unknown_type $path
	 * @param unknown_type $file
	 * @param unknown_type $type
	 */
	function add_attached_files($path,$file,$type)
	{
		$listofpaths=array();
		$listofnames=array();
		$listofmimes=array();
		if (! empty($_SESSION["listofpaths"])) $listofpaths=split(';',$_SESSION["listofpaths"]);
		if (! empty($_SESSION["listofnames"])) $listofnames=split(';',$_SESSION["listofnames"]);
		if (! empty($_SESSION["listofmimes"])) $listofmimes=split(';',$_SESSION["listofmimes"]);
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
	 * Return list of attached files (stored in SECTION array)
	 *
	 * @return	unknown_type $type
	 */
	function get_attached_files()
	{
		$listofpaths=array();
		$listofnames=array();
		$listofmimes=array();
		if (! empty($_SESSION["listofpaths"])) $listofpaths=split(';',$_SESSION["listofpaths"]);
		if (! empty($_SESSION["listofnames"])) $listofnames=split(';',$_SESSION["listofnames"]);
		if (! empty($_SESSION["listofmimes"])) $listofmimes=split(';',$_SESSION["listofmimes"]);
		return array('paths'=>$listofpaths, 'names'=>$listofnames, 'mimes'=>$listofmimes);
	}
		
	/**
	 *	\brief  	Affiche la partie de formulaire pour saisie d'un mail en fonction des propriétés
	 * 	\remarks	this->withfile: 0=No attaches files, 1=Show attached files, 2=Can add new attached files
	 */
	function show_form()
	{
		global $conf, $langs, $user;
	
		$langs->load("other");
		$langs->load("mails");
	
		// Define list of attached files		
		$listofpaths=array();
		$listofnames=array();
		$listofmimes=array();
		if (! empty($_SESSION["listofpaths"])) $listofpaths=split(';',$_SESSION["listofpaths"]);
		if (! empty($_SESSION["listofnames"])) $listofnames=split(';',$_SESSION["listofnames"]);
		if (! empty($_SESSION["listofmimes"])) $listofmimes=split(';',$_SESSION["listofmimes"]);
		
		
		$form=new Form($DB);
	
		print "\n<!-- Debut form mail -->\n";
		print "<form method=\"post\" ENCTYPE=\"multipart/form-data\" action=\"".$this->param["returnurl"]."\">\n";
		foreach ($this->param as $key=>$value)
		{
			print "<input type=\"hidden\" name=\"$key\" value=\"$value\">\n";
		}
		print "<table class=\"border\" width=\"100%\">\n";
	
		// Substitution array
		if ($this->withsubstit)
		{
			print "<tr><td colspan=\"2\">";
			$help="";
		    foreach($this->substit as $key => $val)
			{
				$help.=$key.' -> '.$langs->trans($val).'<br>';
			}
			print $form->textwithhelp($langs->trans("EMailTestSubstitutionReplacedByGenericValues"),$help);
			print "</td></tr>\n";
		}
		
		// From
		if ($this->withfrom)
		{
			if ($this->withfromreadonly)
			{
				print '<input type="hidden" name="fromname" value="'.$this->fromname.'">';
				print '<input type="hidden" name="frommail" value="'.$this->frommail.'">';
				print "<tr><td width=\"180\">".$langs->trans("MailFrom")."</td><td>";
				if ($this->fromtype == 'user')
				{
					$langs->load("users");
					$fuser=new User($this->db);
					$fuser->id=$this->fromid;
					$fuser->fetch();
					print $fuser->getNomUrl(1);
				}
				else 
				{
					print $this->fromname;
				}
				if ($this->frommail)
				{
					print " &lt;".$this->frommail."&gt;";
				}
				else
				{
					if ($this->fromtype)
					{
						$langs->load("errors");
						print '<font class="warning"> &lt;'.$langs->trans("ErrorNoMailDefinedForThisUser").'&gt; </font>';
					}
				}
				print "</td></tr>\n";
				print "</td></tr>\n";
			}
			else
			{
				print "<tr><td>".$langs->trans("MailFrom")."</td><td>";
				print $langs->trans("Name").':<input type="text" name="fromname" size="32" value="'.$this->fromname.'">';
				print '&nbsp; &nbsp; ';
				print $langs->trans("EMail").':&lt;<input type="text" name="frommail" size="32" value="'.$this->frommail.'">&gt;';
				print "</td></tr>\n";
			}
		}
	
		// Replyto
		if ($this->withreplyto)
		{
			if ($this->withreplytoreadonly)
			{
				print '<input type="hidden" name="replyname" value="'.$this->replytoname.'">';
				print '<input type="hidden" name="replymail" value="'.$this->replytomail.'">';
				print "<tr><td>".$langs->trans("MailReply")."</td><td>".$this->replytoname.($this->replytomail?(" &lt;".$this->replytomail."&gt;"):"");
				print "</td></tr>\n";
			}
		}

		// Errorsto
		if ($this->witherrorsto)
		{
			//if (! $this->errorstomail) $this->errorstomail=$this->frommail;
			if ($this->witherrorstoreadonly)
			{
				print '<input type="hidden" name="errorstomail" value="'.$this->errorstomail.'">';
				print "<tr><td>".$langs->trans("MailErrorsTo")."</td><td>";
				print $this->errorstomail;
				print "</td></tr>\n";
			}
			else
			{
				print "<tr><td>".$langs->trans("MailErrorsTo")."</td><td>";
				print "<input size=\"30\" name=\"errorstomail\" value=\"".$this->errorstomail."\">";
				print "</td></tr>\n";
			}
		}
		
		// To
		if ($this->withto || is_array($this->withto))
		{
			print '<tr><td width="180">'.$langs->trans("MailTo").'</td><td>';
			if ($this->withtoreadonly)
			{
				print (! is_array($this->withto) && ! is_numeric($this->withto))?$this->withto:"";
			}
			else
			{
				print "<input size=\"30\" name=\"sendto\" value=\"".(! is_array($this->withto) && ! is_numeric($this->withto)?$this->withto:"")."\">";
				if (is_array($this->withto))
				{
					print " ".$langs->trans("or")." ";
					$form->select_array("receiver",$this->withto);
				}
			}
			print "</td></tr>\n";
		}
	
		// CC
		if ($this->withtocc || is_array($this->withtocc))
		{
			print '<tr><td width="180">'.$langs->trans("MailCC").'</td><td>';
			if ($this->withtoccreadonly)
			{
				print (! is_array($this->withtocc) && ! is_numeric($this->withtocc))?$this->withtocc:"";
			}
			else
			{
				print "<input size=\"30\" name=\"sendtocc\" value=\"".((! is_array($this->withtocc) && ! is_numeric($this->withtocc))?$this->withtocc:"")."\">";
				if (is_array($this->withtocc))
				{
					print " ".$langs->trans("or")." ";
					$form->select_array("receivercc",$this->withtocc);
				}
			}
			print "</td></tr>\n";
		}

		// CC
		if ($this->withtoccc || is_array($this->withtoccc))
		{
			print '<tr><td width="180">'.$langs->trans("MailCCC").'</td><td>';
			if ($this->withtocccreadonly)
			{
				print (! is_array($this->withtoccc) && ! is_numeric($this->withtoccc))?$this->withtoccc:"";
			}
			else
			{
				print "<input size=\"30\" name=\"sendtocc\" value=\"".((! is_array($this->withtoccc) && ! is_numeric($this->withtoccc))?$this->withtoccc:"")."\">";
				if (is_array($this->withtoccc))
				{
					print " ".$langs->trans("or")." ";
					$form->select_array("receiverccc",$this->withtoccc);
				}
			}
			print "</td></tr>\n";
		}
		
		// Accusé réception
		if ($this->withdeliveryreceipt)
		{
			print '<tr><td width="180">'.$langs->trans("DeliveryReceipt").'</td><td>';

			if ($this->withdeliveryreceiptreadonly)
			{
				print yn($this->withdeliveryreceipt);
			}
			else
			{
				print $form->selectyesno('deliveryreceipt',0,1);
			}

			print "</td></tr>\n";
		}

		// Topic
		if ($this->withtopic)
		{
			$this->withtopic=make_substitutions($this->withtopic,$this->substit);

			print "<tr>";
			print "<td width=\"180\">".$langs->trans("MailTopic")."</td>";
			print "<td>";
			if ($this->withtopicreadonly)
			{
				print $this->withtopic;
				print "<input type=\"hidden\" size=\"60\" name=\"subject\" value=\"".$this->withtopic."\">";
			}
			else
			{
				print "<input type=\"text\" size=\"60\" name=\"subject\" value=\"".$this->withtopic."\">";
			}
			print "</td></tr>\n";
		}

		// Attached files
		if ($this->withfile)
		{
			print "<tr>";
			print '<td width="180">'.$langs->trans("MailFile")."</td>";
			print "<td>";
			//print '<table class="nobordernopadding" width="100%"><tr><td>';
			if (sizeof($listofpaths))
			{
				foreach($listofpaths as $key => $val)
				{
					print img_mime($listofnames[$key]).' '.$listofnames[$key].'<br>';
				}
			}
			else
			{
				print $langs->trans("NoAttachedFiles").'<br>';
			}
			if ($this->withfile == 2)	// Can add other files
			{
				//print '<td><td align="right">';
				print "<input type=\"file\" class=\"flat\" name=\"addedfile\" value=\"".$langs->trans("Upload")."\"/>";
				print ' ';
				print '<input type="submit" class="button" name="addfile" value="'.$langs->trans("MailingAddFile").'">';
				//print '</td></tr></table>';
			}
			print "</td></tr>\n";
		}

		// Message
		if ($this->withbody)
		{
			$defaultmessage="";

			// \todo    A partir du type, proposer liste de messages dans table llx_models
			if ($this->param["models"]=='body') { $defaultmessage=$this->withbody; }
			if ($this->param["models"]=='facture_send')    { $defaultmessage="Veuillez trouver ci-joint la facture __FACREF__\n\nCordialement\n\n"; }
			if ($this->param["models"]=='facture_relance') { $defaultmessage="Nous apportons à votre connaissance que la facture  __FACREF__ ne semble pas avoir été réglée. La voici donc, pour rappel, en pièce jointe.\n\nCordialement\n\n"; }
			if ($this->param["models"]=='propal_send') { $defaultmessage="Veuillez trouver ci-joint la proposition commerciale __PROPREF__\n\nCordialement\n\n"; }
			if ($this->param["models"]=='order_send') { $defaultmessage="Veuillez trouver ci-joint la commande __ORDERREF__\n\nCordialement\n\n"; }

			$defaultmessage=make_substitutions($defaultmessage,$this->substit);

			print "<tr>";
			print "<td width=\"180\" valign=\"top\">".$langs->trans("MailText")."</td>";
			print "<td>";
			if ($this->withbodyreadonly)
			{
				print nl2br($defaultmessage);
				print '<input type="hidden" name="message" value="'.$defaultmessage.'">';
			}
			else
			{
				print '<textarea cols="72" rows="8" name="message">';
				print $defaultmessage;
				print '</textarea>';
			}
			print "</td></tr>\n";
		}

		print "<tr><td align=center colspan=2><center>";
		print "<input class=\"button\" type=\"submit\" name=\"sendmail\" value=\"".$langs->trans("SendMail")."\">";
		if ($this->withcancel)
		{
			print " &nbsp; &nbsp; ";
			print "<input class=\"button\" type=\"submit\" name=\"cancel\" value=\"".$langs->trans("Cancel")."\">";
		}
		print "</center></td></tr>\n";
		print "</table>\n";

		print "</form>\n";
		print "<!-- Fin form mail -->\n";
	}


  /*
   *    \brief  Affiche la partie de formulaire pour saisie d'un mail
   *    \param  withtopic   1 pour proposer à la saisie le sujet
   *    \param  withbody    1 pour proposer à la saisie le corps du message
   *    \param  withfile    1 pour proposer à la saisie l'ajout d'un fichier joint
   *    \todo   Fonction a virer quand fichier /comm/mailing.php viré (= quand ecran dans /comm/mailing prets)
   */
  function mail_topicmessagefile($withtopic=1,$withbody=1,$withfile=1,$defaultbody) {
    global $langs;

    $langs->load("other");

    print "<table class=\"border\" width=\"100%\">";

    // Topic
    if ($withtopic)
      {
	print "<tr>";
	print "<td width=\"180\">".$langs->trans("MailTopic")."</td>";
	print "<td>";
	print "<input type=\"text\" size=\"60\" name=\"subject\" value=\"\">";
	print "</td></tr>";
      }
    
    // Message
    if ($withbody)
      {
	print "<tr>";
	print "<td width=\"180\" valign=\"top\">".$langs->trans("MailText")."</td>";
	print "<td>";
	print "<textarea rows=\"8\" cols=\"72\" name=\"message\">";
	print $defaultbody;
	print "</textarea>";
	print "</td></tr>";
      }
    	
    // Si fichier joint
    if ($withfile)
      {
	print "<tr>";
	print "<td width=\"180\">".$langs->trans("MailFile")."</td>";
	print "<td>";
	print "<input type=\"file\" name=\"addedfile\" value=\"".$langs->trans("Upload")."\"/>";
	print "</td></tr>";
      }
    
    print "</table>";
  }

}

?>
