<?PHP
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**     \file       htdocs/html.formmail.class.php
        \brief      Fichier de la classe permettant la génération du formulaire html d'envoi de mail unitaire
        \version    $Revision$
*/

include_once ("html.form.class.php");


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

  var $withfrom;
  var $withto;
  var $withtocc;
  var $withtopic;
  var $withfile;
  var $withbody;
  
  var $withfromreadonly;
  var $withreplytoreadonly;
  var $withtoreadonly;
  var $withtoccreadonly;
  var $withtopicreadonly;

  var $substit=array();
  var $param=array();
  
  var $errorstr;
  
  /**	\brief     Constructeur
        \param     DB      handler d'accès base de donnée
  */
	
  function FormMail($DB)
  {
    $this->db = $DB;

    $this->withfrom=1;
    $this->withto=1;
    $this->withtocc=1;
    $this->withtopic=1;
    $this->withfile=0;
    $this->withbody=1;

    $this->withfromreadonly=1;
    $this->withreplytoreadonly=1;
    $this->withtoreadonly=0;
    $this->withtoccreadonly=0;
    $this->withtopicreadonly=0;
   
    return 1;
  }


  /*
   *    \brief  Affiche la partie de formulaire pour saisie d'un mail en fonction des propriétés
   */
  function show_form() {
    global $langs;
    $langs->load("other");
    
    $form=new Form($DB);

    print "\n<!-- Debut form mail -->\n";
    print "<form method=\"post\" ENCTYPE=\"multipart/form-data\" action=\"".$this->param["returnurl"]."\">\n";
    foreach ($this->param as $key=>$value) {
        print "<input type=\"hidden\" name=\"$key\" value=\"$value\">\n";
    }
    print "<table class=\"border\" width=\"100%\">\n";

    // From
    if ($this->withfrom) 
    {
        if ($this->withfromreadonly) {
            print '<input type="hidden" name="fromname" value="'.$this->fromname.'">';
            print '<input type="hidden" name="frommail" value="'.$this->frommail.'">';
            print "<tr><td width=\"180\">".$langs->trans("MailFrom")."</td><td>".$this->fromname.($this->frommail?(" &lt;".$this->frommail."&gt;"):"")."</td></tr>\n";
            print "</td></tr>\n";
        }
    }

    // Replyto
    if ($this->withreplyto) 
    {
        if ($this->withreplytoreadonly) {
            print '<input type="hidden" name="replyname" value="'.$this->replytoname.'">';
            print '<input type="hidden" name="replymail" value="'.$this->replytomail.'">';
            print "<tr><td>".$langs->trans("MailReply")."</td><td>".$this->replytoname.($this->replytomail?(" &lt;".$this->replytomail."&gt;"):"");
            print "</td></tr>\n";
        }
    }
    
    // To
    if ($this->withto || is_array($this->withto)) {
        print '<tr><td width="180">'.$langs->trans("MailTo").'</td><td>';
        if ($this->withtoreadonly) {
            print (! is_array($this->withto) && ! is_numeric($this->withto))?$this->withto:"";
        } else {
            print "<input size=\"30\" name=\"sendto\" value=\"".(! is_array($this->withto)?$this->withto:"")."\">";
            if (is_array($this->withto))
            {
                print " ".$langs->trans("or")." ";
                $form->select_array("receiver",$this->withto);
            }
        }
        print "</td></tr>\n";
    }
        
    // CC
    if ($this->withcc)
    {
        print '<tr><td width="180">'.$langs->trans("MailCC").'</td><td>';
        if ($this->withtoccreadonly) {
            print (! is_array($this->withtocc) && ! is_numeric($this->withtocc))?$this->withtocc:"";
        } else {
            print "<input size=\"30\" name=\"sendtocc\" value=\"".((! is_array($this->withtocc) && ! is_numeric($this->withtocc))?$this->withtocc:"")."\">";
            if (is_array($this->withtocc))
            {
                print " ".$langs->trans("or")." ";
                $form->select_array("receivercc",$this->withtocc);
            }
        }
        print "</td></tr>\n";
    }

    // Topic
    if ($this->withtopic)
    {
        print "<tr>";
        print "<td width=\"180\">".$langs->trans("MailTopic")."</td>";
        print "<td>";
        print "<input type=\"text\" size=\"60\" name=\"subject\" value=\"\">";
        print "</td></tr>\n";
    }

    // Si fichier joint
    if ($this->withfile)
    {
        print "<tr>";
        print "<td width=\"180\">".$langs->trans("MailFile")."</td>";
        print "<td>";
        print "<input type=\"file\" name=\"addedfile\" value=\"".$langs->trans("Upload")."\"/>";
        print "</td></tr>\n";
    }

    // Message
    if ($this->withbody)
    {
        $defaultmessage="";

        // \todo    A partir du type, proposer liste de messages dans table llx_models
        if ($this->param["models"]=='facture_send')    { $defaultmessage="Veuillez trouver ci-joint la facture __FACREF__\n\nCordialement\n\n"; }
        if ($this->param["models"]=='facture_relance') { $defaultmessage="Nous apportons à votre connaissance que la facture  __FACREF__ ne semble toujours pas avoir été réglée. La voici donc, pour rappel, en pièce jointe.\n\nCordialement\n\n"; }

        foreach ($this->substit as $key=>$value) {
            $defaultmessage=ereg_replace($key,$value,$defaultmessage);
        }
        print "<tr>";
        print "<td width=\"180\" valign=\"top\">".$langs->trans("MailText")."</td>";
        print "<td>";
        print "<textarea rows=\"8\" cols=\"72\" name=\"message\">";
        print $defaultmessage;
        print "</textarea>";
        print "</td></tr>\n";
    }
    	
    print "<tr><td align=center colspan=2><center><input class=\"flat\" type=\"submit\" value=\"".$langs->trans("Send")."\"></center></td></tr>\n";
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
