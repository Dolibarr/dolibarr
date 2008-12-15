<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/prelevement/fiche.php
        \ingroup    prelevement
        \brief      Fiche prelevement
        \version    $Id$
*/

require("./pre.inc.php");

if (!$user->rights->prelevement->bons->lire)
  accessforbidden();

$langs->load("bills");
$langs->load("withdrawals");


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) accessforbidden();


/*
 * Actions
 */

if ($_POST["action"] == 'confirm_credite' && $_POST["confirm"] == yes)
{
  $bon = new BonPrelevement($db,"");
  $bon->id = $_GET["id"];
  $bon->set_credite();

  Header("Location: fiche.php?id=".$_GET["id"]);
}

if ($_POST["action"] == 'infotrans')
{
  $bon = new BonPrelevement($db,"");
  $bon->fetch($_GET["id"]);

  if ($_FILES['userfile']['name'] && basename($_FILES['userfile']['name'],".ps") == $bon->ref)
    {      
      $dir = $conf->prelevement->dir_output.'/bon/';

      if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $dir . "/" . $_FILES['userfile']['name'],1) > 0)
	{
	  $dt = dolibarr_mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
	  
	  $bon->set_infotrans($user, $dt, $_POST["methode"]);
	}
    }
  else
    {
      dolibarr_syslog("Fichier invalide",LOG_WARNING);
    }

  Header("Location: fiche.php?id=".$_GET["id"]);
  exit;
}

if ($_POST["action"] == 'infocredit')
{
  $bon = new BonPrelevement($db,"");
  $bon->Fetch($_GET["id"]);
  $dt = mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

  $error = $bon->set_infocredit($user, $dt);

  if ($error == 0)
    {
      Header("Location: fiche.php?id=".$_GET["id"]);
    }
  else
    {
      Header("Location: fiche.php?id=".$_GET["id"]."&error=$error");
    }
}

llxHeader('',$langs->trans("WithdrawalReceipt"));

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;      

if ($conf->use_preview_tabs)
{
    $head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/bon.php?id='.$_GET["id"];
    $head[$h][1] = $langs->trans("Preview");
    $h++;  
}

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/lignes.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Lines");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Bills");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-rejet.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Rejects");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistics");
$h++;  

$prev_id = $_GET["id"];

$html = new Form($db);

if ($_GET["id"])
{
  $bon = new BonPrelevement($db,"");

  if ($bon->fetch($_GET["id"]) == 0)
    {
      dolibarr_fiche_head($head, $hselected, $langs->trans("WithdrawalReceipt"));

      if (isset($_GET["error"]))
	{
	  print '<div class="error">'.$bon->ReadError($_GET["error"]).'</div>';
	}



      if ($_GET["action"] == 'credite')
	{
	  $html->form_confirm("fiche.php?id=".$bon->id,$langs->trans("ClassCredited"),$langs->trans("ClassCreditedConfirm"),"confirm_credite");
	  print '<br />';
	}

      print '<table class="border" width="100%">';

      print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td>'.$bon->getNomUrl(1).'</td></tr>';
      print '<tr><td width="20%">'.$langs->trans("Date").'</td><td>'.dolibarr_print_date($bon->datec,'dayhour').'</td></tr>';
      print '<tr><td width="20%">'.$langs->trans("Amount").'</td><td>'.price($bon->amount).'</td></tr>';
      print '<tr><td width="20%">'.$langs->trans("File").'</td><td>';

      $relativepath = 'bon/'.$bon->ref;

      print '<a href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=prelevement&amp;file='.urlencode($relativepath).'">'.$bon->ref.'</a>';

      print '</td></tr>';

      print '<tr><td width="20%">Statut</td><td>';
      print '<img src="./statut'.$bon->statut.'.png">&nbsp;';
      print $lipre->statuts[$lipre->statut].'</td></tr>';

      if($bon->date_trans <> 0)
	{
	  $muser = new User($db, $bon->user_trans);
	  $muser->fetch();

	  print '<tr><td width="20%">Date Transmission / Par</td><td>';
	  print dolibarr_print_date($bon->date_trans,'dayhour');
	  print ' par '.$muser->fullname.'</td></tr>';
	  print '<tr><td width="20%">Methode Transmission</td><td>';
	  print $bon->methodes_trans[$bon->method_trans];
	  print '</td></tr>';
	}
      if($bon->date_credit <> 0)
	{
	  print '<tr><td width="20%">Credit on</td><td>';
	  print dolibarr_print_date($bon->date_credit,'dayhour');
	  print '</td></tr>';
	}

      print '</table><br />';

      if($bon->date_trans == 0)
	{
	  print '<form method="post" name="userfile" action="fiche.php?id='.$bon->id.'" enctype="multipart/form-data">';
	  print '<input type="hidden" name="action" value="infotrans">';
	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">Date Transmission</td><td>';
	  print $html->select_date('','','','','',"userfile");
	  print '</td></tr>';
	  print '<tr><td width="20%">Methode Transmission</td><td>';
	  print $html->select_array("methode",$bon->methodes_trans);
	  print '</td></tr>';
	  print '<tr><td width="20%">'.$langs->trans("File").'</td><td>';
      print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';
	  print '<input class="flat" type="file" name="userfile" size="80"><br />';
	  print '</td></tr>';
	  print '<tr><td colspan="2" align="center">';
	  print '<input type="submit" class="button" value="'.$langs->trans("Send").'">';
	  print '</td></tr>';
	  print '</table></form>';
	}

      if($bon->date_trans <> 0 && $bon->date_credit == 0)
	{
	  print '<form name="infocredit" method="post" action="fiche.php?id='.$bon->id.'">';
	  print '<input type="hidden" name="action" value="infocredit">';
	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">Crédité le</td><td>';
	  print $html->select_date('','','','','',"infocredit");
	  print '</td></tr>';
	  print '<tr><td colspan="2" align="center">';
	  print '<input type="submit" class="button" value="'.$langs->trans("Send").'">';
	  print '</td></tr>';
	  print '</table></form>';
	}

    }
  else
    {
      dolibarr_print_error($db);
    }
}

/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n</div>\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{  
  
  if ($bon->credite == 0)
    {      
      print "<a class=\"butAction\" href=\"fiche.php?action=credite&amp;id=$bon->id\">".$langs->trans("ClassCredited")."</a>";
    }


      
}

print "</div>";


llxFooter('$Date$ - $Revision$');
?>
