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

$main_use_mailman = MAIN_USE_MAILMAN;
$main_use_glasnost = MAIN_USE_GLASNOST;
$main_use_glasnost_auto = MAIN_USE_GLASNOST_AUTO;
$main_use_spip = MAIN_USE_SPIP;
$main_use_spip_auto = MAIN_USE_SPIP_AUTO;

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

if (defined("MAIN_USE_MAILMAN") && MAIN_USE_MAILMAN == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=MAIN_USE_MAILMAN">désactiver</a>';
  print '</td></tr>';
  print '</table>';
  // Edition des varibales globales rattache au theme Mailman 
  $constantes=array('MAIN_MAILMAN_LISTS',
		    'MAIN_MAILMAN_UNSUB_URL',
		    'MAIN_MAILMAN_URL'
		    );
  form_constantes($constantes);
}
else
{
  print "&nbsp;";
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=MAIN_USE_MAILMAN">activer</a>';
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

if (defined("MAIN_USE_SPIP") && MAIN_USE_SPIP == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=MAIN_USE_SPIP">désactiver</a>';
  print '</td></tr>';
  print '</table>';
  // Edition des varibales globales rattache au theme Mailman 
  $constantes=array('MAIN_USE_SPIP_AUTO',
		    'MAIN_SPIP_SERVEUR',
		    'MAIN_SPIP_DB',
		    'MAIN_SPIP_USER',
		    'MAIN_SPIP_PASS'
		    );
  form_constantes($constantes);
}
else
{
  print "&nbsp;";
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=MAIN_USE_SPIP">activer</a>';
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

if (defined("MAIN_USE_GLASNOST") && MAIN_USE_GLASNOST == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=MAIN_USE_GLASNOST">désactiver</a>';
  print '</td></tr>';
  print '</table>';
  // Edition des varibales globales rattache au theme Mailman 
  $constantes=array('MAIN_USE_GLASNOST_AUTO',
		    'MAIN_GLASNOST_SERVEUR',
		    'MAIN_GLASNOST_USER',
		    'MAIN_GLASNOST_PASS'
		    );
  form_constantes($constantes);
}
else
{
  print "&nbsp;";
  print "</td><td>\n";
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=MAIN_USE_GLASNOST">activer</a>';
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
$constantes=array('ADH_TEXT_NEW_ADH',
		  'MAIN_MAIL_COTIS_SUBJECT',
		  'MAIN_MAIL_COTIS',
		  'MAIN_MAIL_EDIT_SUBJECT',
		  'MAIN_MAIL_EDIT',
		  'MAIN_MAIL_NEW_SUBJECT',
		  'MAIN_MAIL_NEW',
		  'MAIN_MAIL_RESIL_SUBJECT',
		  'MAIN_MAIL_RESIL',
		  'MAIN_MAIL_VALID_SUBJECT',
		  'MAIN_MAIL_VALID',
		  'MAIN_MAIL_FROM'
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
