<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/*!
    \file       htdocs/comm/mailing.php
    \brief      Page pour faire des mailing
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("commercial");
$langs->load("users");
$langs->load("bills");
$langs->load("companies");
$langs->load("other");

/*
 *  Modules optionnels
 */
require("../project.class.php");
require("./propal_model_pdf.class.php");
require("../propal.class.php");
require("../actioncomm.class.php");
require("../lib/CMailFile.class.php");

/*
 * Sécurité accés client
 */

if ($user->societe_id > 0) 
{
 // $action = '';
  $socidp = $user->societe_id;
}


llxHeader();


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

print_fiche_titre($langs->trans("Mailing"));


if ($_GET["action"] != 'mailing')
{	
    print $langs->trans("MailingDesc")."<br><br>";

    $form = new Form($db);	    

	print "<form method=\"post\" action=\"mailing.php?action=mailing\" name=\"mailing\">";

    // To
	print "<table class=\"border\" width=\"100%\"><tr>";
	print "<td width=\"180\">".$langs->trans("MailTo")."</td>";
    print "<td>";
	print "<select name=\"receiver\">";
	print "<option value=\"dolusers\">".$langs->trans("DolibarrUsers")."</option>";
	print "<option value=\"clients\">".$langs->trans("Customers")."</option>";
	print "<option value=\"prospects\">".$langs->trans("Prospects")."</option>";
	print "</select>";
    print "</td></tr>";
    print "</table>";

    // Affiche la partie mail topic + message + file
    $form->mail_topicmessagefile(1,1,1,$defaultmessage);

    print "<br><center><input class=\"flat\" type=\"submit\" value=\"".$langs->trans("Send")."\"></center>\n";

	print "</form\n";	
}
else
{
    print $langs->trans("MailingResult")."<br><br>";

	$cible=$_POST['receiver'];
	$subject=$_POST['subject'];
	$body=$_POST['message'];

    // Definition de la requete qui donne les groupes d'email cibles
	if($cible=="prospects")
	    $all_group_req="SELECT idp,nom FROM llx_societe WHERE client=2"; //prospect
	elseif($cible=="clients")
	    $all_group_req="SELECT idp,nom FROM llx_societe WHERE client=1"; //client
	elseif($cible=="dolusers")
	    $all_group_req="SELECT 0,'".$langs->trans("DolibarrUsers")."' nom";
    else {
        dolibarr_print_error(0,$langs->trans("ErrorUnkownReceiver"));
        exit;
    }

	$all_group_res = $db->query($all_group_req);
	if (! $all_group_res) {
	    dolibarr_print_error($db);
	    exit;
	}

	$num_soc = $db->num_rows();
	$i=0;
	if ($num_soc > 0) {
    	while ($i < $num_soc)
    	{
    		$obj = $db->fetch_object($all_group_res);
    		$tab_soc[$i]=$obj->idp;
    		$tab_soc_nom[$i]=$obj->nom;
    		$i++;
    	}
    }
    else {
        print $langs->trans("ErrorNoGroupFound");
    }
        
	print '<table class="border">';
	print '<tr><td colspan="2">'.$langs->trans("Group").' / '.$langs->trans("Company").'</td><td>'.$langs->trans("EMail").'</td><td>'.$langs->trans("Name").'</td><td>'.$langs->trans("Lastname").'</td><td>'.$langs->trans("Status").'</td></tr>';
	foreach($tab_soc as $idp)
	{
		$h=0;
        if($cible=="dolusers") {
		    $all_peop_req="SELECT rowid idp, name, firstname, email FROM llx_user";
        }
        else {
		    $all_peop_req="SELECT idp, name, firstname, email FROM llx_socpeople WHERE fk_soc=$idp";
        }

		$all_peop_res = $db->query($all_peop_req);
		if (! $all_peop_res) {
		    dolibarr_print_error($db);
		    exit;
		}
		
		$num_socpeop = $db->num_rows();
		$j=0;
		while($j < $num_socpeop)
		{

			$obj_target = $db->fetch_object($all_peop_res);

			if($obj_target->email!="")
			{
				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/plain; charset=iso-8859-1\n";
				$headers .= "From: ".$user->fullname." <".MAILING_EMAIL.">\r\n";
				$headers .= "Reply-to:".$user->fullname." <".MAILING_EMAIL.">\r\n";
				$headers .= "X-Priority: 3\r\n";
				$headers .= "X-Mailer: Dolibarr ".DOL_VERSION."\r\n";

				$m=mail($obj_target->name." ".$obj_target->firstname."<".$obj_target->email.">", $subject, $body, $headers);

				print "<tr><td>$h</td><td>$tab_soc_nom[$h]</td><td>$obj_target->email</td><td>$obj_target->name</td><td>$obj_target->firstname</td>";
				if($m)
				{
                    if($cible!="dolusers") {
    					print '<td><b>'.$langs->trans("ResultOk").'</b></td>';
    					$sql="INSERT INTO llx_actioncomm (datea, fk_action, fk_soc, fk_user_author, fk_user_action, fk_contact, percent, note,priority,propalrowid)  VALUES (NOW(),4, $idp,$user->id,$user->id,$obj_target->idp, '100%', '', 0, 0)";
    					$res= $db->query($sql);
                    }					
				}
				else
				{
                    if($cible!="dolusers") {
    					print '<td><b>'.$langs->trans("ResultKo").'</b></td>';
    					$sql="INSERT INTO llx_actioncomm (datea, fk_action, fk_soc, fk_user_author, fk_user_action, fk_contact, percent, note,priority,propalrowid)  VALUES (NOW(),4, $idp,$user->id,$user->id,$obj_target->idp, '0%', '', 0, 0)";
    					$res= $db->query($sql);
                    }
				}					
                print '</tr>';
			}
			$j++;
		}
		$h++;
	}
	print '</table>';
}
?>
