<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
    \ingroup    
    \brief      Page pour faire des mailing
*/
require("./pre.inc.php");


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

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/
llxHeader();
print_fiche_titre ("Mailing");
if ($_GET["action"] != 'mailing')
{	
	print "<form method=\"post\" action=\"mailing.php?action=mailing\" name=\"mailing\">";
	print "<select name=\"receiver\">";
	print "<option value=\"clients\">Clients</option>";
	print "<option value=\"prospects\">Prospects</option>";
	print "</select><br /><br />";
	print "<input type=\"text\" size=\"60\" name=\"subject\" value=\"subject\"><br /><br />";
	print "<textarea rows=\"10\" cols=\"75\" name=\"body\">Votre texte</textarea><br /><br />";
	print "<input type=\"file\" /><br /><br />";
	print "<input type=\"submit\" />";
	print "</form>";	
}
else
{
	$cible=$_POST['receiver'];
	$subject=$_POST['subject'];
	$body=$_POST['body'];
	if($cible=="prospects")
	$all_soc_req="SELECT idp,nom FROM llx_societe WHERE client=2"; //prospect
	elseif($cible=="clients")
	$all_soc_req="SELECT idp,nom FROM llx_societe WHERE client=1"; //client
	$all_soc_res = @$db->query($all_soc_req);
	$num_soc = $db->num_rows();
	$i=0;
	while ($i < $num_soc)
	{
		$obj = $db->fetch_object($i);
		$tab_soc[$i]=$obj->idp;
		$tab_soc_nom[$i]=$obj->nom;
		$i++;
	}
	print "<table border=\"0\">";
	print "<th><td>Société</td><td>E-Mail</td><td>Nom</td><td>Prénom</td><td>Status</td>";
	foreach($tab_soc as $idp)
	{
		$h=0;
		$all_socpeop_req="SELECT * FROM llx_socpeople WHERE fk_soc=$idp";
		$all_socpeop_res = @$db->query($all_socpeop_req);
		$num_socpeop = $db->num_rows();
		$j=0;
		while($j < $num_socpeop)
		{
			$obj_soc = $db->fetch_object($j);
			if($obj_soc->email!="")
			{
				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/plain; charset=iso-8859-1\n";
				$headers .= "From: ".$user->fullname." <".MAILING_EMAIL.">\r\n";
				$headers .= "Reply-to:".$user->fullname." <".MAILING_EMAIL.">\r\n";
				$headers .= "X-Priority: 3\r\n";
				$headers .= "X-Mailer: Dolibarr V 1.2\r\n";
				$m=mail ($obj_soc->name." ".$obj_soc->firstname."<".$obj_soc->email.">", $subject, $body, $headers);
				print "<tr><td>$h</td><td>$tab_soc_nom[$h]</td><td>$obj_soc->email</td><td>$obj_soc->name</td><td>$obj_soc->firstname</td>";
				if($m)
				{
					print "<td><b>réussi</b></td></tr>";
					$sql="INSERT INTO llx_actioncomm (datea, fk_action, fk_soc, fk_user_author, fk_user_action, fk_contact, percent, note,priority,propalrowid)  VALUES (NOW(),4, $idp,$user->id,$user->id,$obj_soc->idp, '100%', '', 0, 0)";
					$res= @$db->query($sql);
					
				}
				else
				{
					print "<td><b>raté</b></td></tr>";
					$sql2="INSERT INTO llx_actioncomm (datea, fk_action, fk_soc, fk_user_author, fk_user_action, fk_contact, percent, note,priority,propalrowid)  VALUES (NOW(),4, $idp,$user->id,$user->id,$obj_soc->idp, '0%', '', 0, 0)";
					$res2= @$db->query($sql2);
				}					
			}
			$j++;
		}
		$h++;
	}	
}
?>
