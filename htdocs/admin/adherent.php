<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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
require("./pre.inc.php");

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

// positionne la variable pour le test d'affichage de l'icone

$main_use_mailman = ADHERENT_USE_MAILMAN;
$main_use_glasnost = ADHERENT_USE_GLASNOST;
$main_use_glasnost_auto = ADHERENT_USE_GLASNOST_AUTO;
$main_use_spip = ADHERENT_USE_SPIP;
$main_use_spip_auto = ADHERENT_USE_SPIP_AUTO;

$typeconst=array('yesno','texte','chaine');
$var=True;

if ($HTTP_POST_VARS["action"] == 'update' || $HTTP_POST_VARS["action"] == 'add')
{
  if (isset($HTTP_POST_VARS["consttype"]) && $HTTP_POST_VARS["consttype"] != ''){
    $sql = "REPLACE INTO llx_const SET name='".$_POST["constname"]."', value = '".$HTTP_POST_VARS["constvalue"]."',note='".$HTTP_POST_VARS["constnote"]."', type='".$typeconst[$HTTP_POST_VARS["consttype"]]."',visible=0";
  }else{
    $sql = "REPLACE INTO llx_const SET name='".$_POST["constname"]."', value = '".$HTTP_POST_VARS["constvalue"]."',note='".$HTTP_POST_VARS["constnote"]."',visible=0";
  }
  
  if ($db->query($sql))
    {
      Header("Location: adherent.php");
    }
  
  /*
  $result = $db->query($sql);
  if (!$result)
    {
      print $db->error();
    }
  */
}

if ($action == 'set')
{
  $sql = "REPLACE INTO llx_const SET name = '$name', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      Header("Location: adherent.php");
    }
}

if ($action == 'unset')
{
  $sql = "DELETE FROM llx_const WHERE name = '$name'";

  if ($db->query($sql))
    {
      Header("Location: adherent.php");
    }
}

llxHeader();

/*
 * Interface de configuration de certaines variables de la partie adherent
 */

print_titre("Gestion des adhérents : Configurations de parametres");

/*
 * Mailman
 */
print '<table border="1" cellpadding="3" cellspacing="0">';
print "<tr $bc[$var] class=value><td>Mailman</td><td>Système de mailing listes";
print '</td><td align="center">';

if (defined("ADHERENT_USE_MAILMAN") && ADHERENT_USE_MAILMAN == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=ADHERENT_USE_MAILMAN">désactiver</a>';
  print '</td></tr>';
  print '</table>';
  // Edition des varibales globales rattache au theme Mailman 
  $constantes=array('ADHERENT_MAILMAN_LISTS',
		    'ADHERENT_MAILMAN_LISTS_COTISANT',
		    'ADHERENT_MAILMAN_ADMINPW',
		    'ADHERENT_MAILMAN_SERVER',
		    'ADHERENT_MAILMAN_UNSUB_URL',
		    'ADHERENT_MAILMAN_URL'
		    );
  form_constantes($constantes);
}
else
{
  print "&nbsp;";
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=ADHERENT_USE_MAILMAN">activer</a>';
  print '</td></tr>';
  print '</table>';
}

print "<HR><BR>\n";

/*
 * Gestion banquaire
 */
print '<table border="1" cellpadding="3" cellspacing="0">';
print "<tr $bc[$var] class=value><td>Gestion Banquaire</td><td>Gestion banquaire des adherents";
print '</td><td align="center">';

if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=ADHERENT_BANK_USE">désactiver</a>';
  print '</td></tr>';
  print '</table>';
  // Edition des varibales globales rattache au theme Mailman 
  $constantes=array('ADHERENT_BANK_USE_AUTO',
		    'ADHERENT_BANK_ACCOUNT',
		    'ADHERENT_BANK_CATEGORIE'
		    );
  form_constantes($constantes);
}
else
{
  print "&nbsp;";
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=ADHERENT_BANK_USE">activer</a>';
  print '</td></tr>';
  print '</table>';
}

print "<HR><BR>\n";
/*
 * Spip
 */
$var=!$var;
print '<table border="1" cellpadding="3" cellspacing="0">';
print "<tr $bc[$var] class=value><td>Spip</td><td>Système de publication en ligne";
print '</td><td align="center">';

if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=ADHERENT_USE_SPIP">désactiver</a>';
  print '</td></tr>';
  print '</table>';
  // Edition des varibales globales rattache au theme Mailman 
  $constantes=array('ADHERENT_USE_SPIP_AUTO',
		    'ADHERENT_SPIP_SERVEUR',
		    'ADHERENT_SPIP_DB',
		    'ADHERENT_SPIP_USER',
		    'ADHERENT_SPIP_PASS'
		    );
  form_constantes($constantes);
}
else
{
  print "&nbsp;";
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=ADHERENT_USE_SPIP">activer</a>';
  print '</td></tr>';
  print '</table>';
}

print "<HR><BR>\n";
/*
 * Glasnost
 */
$var=!$var;
print '<table border="1" cellpadding="3" cellspacing="0">';
print "<tr $bc[$var] class=value><td>Glasnost</td><td>Système de vote en ligne";
print '</td><td align="center">';

if (defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=ADHERENT_USE_GLASNOST">désactiver</a>';
  print '</td></tr>';
  print '</table>';
  // Edition des varibales globales rattache au theme Mailman 
  $constantes=array('ADHERENT_USE_GLASNOST_AUTO',
		    'ADHERENT_GLASNOST_SERVEUR',
		    'ADHERENT_GLASNOST_USER',
		    'ADHERENT_GLASNOST_PASS'
		    );
  form_constantes($constantes);
}
else
{
  print "&nbsp;";
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=ADHERENT_USE_GLASNOST">activer</a>';
  print '</td></tr>';
  print '</table>';
}

print "<HR><BR>\n";
$var=!$var;
/*
 * Edition des varibales globales non rattache a un theme specifique 
 */
print '<table border="1" cellpadding="3" cellspacing="0">';
print "<tr $bc[$var] class=value><td>Variables globales</td><td>Variables globales non rattachées a un thème";
print '</td></tr>';
print '</table>';
$constantes=array('ADHERENT_TEXT_NEW_ADH',
		  'ADHERENT_MAIL_COTIS_SUBJECT',
		  'ADHERENT_MAIL_COTIS',
		  'ADHERENT_MAIL_EDIT_SUBJECT',
		  'ADHERENT_MAIL_EDIT',
		  'ADHERENT_MAIL_NEW_SUBJECT',
		  'ADHERENT_MAIL_NEW',
		  'ADHERENT_MAIL_RESIL_SUBJECT',
		  'ADHERENT_MAIL_RESIL',
		  'ADHERENT_MAIL_VALID_SUBJECT',
		  'ADHERENT_MAIL_VALID',
		  'ADHERENT_MAIL_FROM',
		  'ADHERENT_CARD_HEADER_TEXT',
		  'ADHERENT_CARD_TEXT',
		  'ADHERENT_CARD_FOOTER_TEXT'
		  );
form_constantes($constantes);

$db->close();
llxFooter();

function form_constantes($tableau){
  // Variables globales
  global $db,$bc;
  $form = new Form($db);
  print '<table border="1" cellpadding="3" cellspacing="0">';
  print '<TR class="liste_titre">';
  print '<TD>Description</TD>';
  print '<TD>Valeur</TD>';
  print '<TD>Type</TD>';
  //print '<TD>Note</TD>';
  print "<TD>Action</TD>";
  print "</TR>\n";
  $var=True;
  
  foreach($tableau as $const){
    $sql = "SELECT rowid, name, value, type, note FROM llx_const WHERE name='$const'";
    $result = $db->query($sql);
    if ($result && ($db->num_rows() == 1)) {
      $obj = $db->fetch_object(0);
      $var=!$var;
      print '<form action="'.$PHP_SELF.'" method="POST">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="rowid" value="'.$rowid.'">';
      print '<input type="hidden" name="constname" value="'.$obj->name.'">';
      print '<input type="hidden" name="constnote" value="'.stripslashes(nl2br($obj->note)).'">';
      
      print "<tr $bc[$var] class=value><td>".stripslashes(nl2br($obj->note))."</td>\n";
      
      print '<td>';
      if ($obj->type == 'yesno')
	{
	  $form->selectyesnonum('constvalue',$obj->value);
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),0);
	}
      elseif ($obj->type == 'texte')
	{
	  print '<textarea name="constvalue" cols="35" rows="5"wrap="soft">';
	  print $obj->value;
	  print "</textarea>\n";
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),1);
	}
      else
	{
	  print '<input type="text" size="30" name="constvalue" value="'.stripslashes($obj->value).'">';
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),2);
	}
      print '</td><td>';
      
      //      print '<input type="text" size="15" name="constnote" value="'.stripslashes(nl2br($obj->note)).'">';
      //      print '</td><td>';
      print '<input type="Submit" value="Update" name="Button"><BR>';
      print '<a href="'.$PHP_SELF.'?name=$const&action=unset">Delete</a>';
      print "</td></tr>\n";
      
      print '</form>';
      $i++;
    }    
  }
  print '</table>';
}
?>
