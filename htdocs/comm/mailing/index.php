<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
        \file       htdocs/comm/mailing/index.php
        \ingroup    commercial
        \brief      Page accueil de la zone mailing
        \version    $Revision$
*/
 
require("./pre.inc.php");

if ($user->societe_id > 0)
{
  accessforbidden();
}
	  
$langs->load("commercial");
$langs->load("orders");

llxHeader('','Mailing');

/*
 *
 */

print_titre($langs->trans("MailingArea"));
print '<br>';

print '<table class="noborder" width="100%">';

print '<tr><td valign="top" width="30%">';

// Affiche stats de tous les modules de destinataires mailings
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("TargetsStatistics").'</td></tr>';

$dir=DOL_DOCUMENT_ROOT."/includes/modules/mailings";
$handle=opendir($dir);

$var=True;
while (($file = readdir($handle))!==false)
{
    if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
    {
        if (eregi("(.*)\.(.*)\.(.*)",$file,$reg))
        {
            $var = !$var;

            $modulename=$reg[1];

            // Chargement de la classe
            $file = $dir."/".$modulename.".modules.php";
            $classname = "mailing_".$modulename;
            require_once($file);
            $mailmodule = new $classname($db);
            
            foreach ($mailmodule->statssql as $sql) 
            {
                $qualified=1;
                foreach ($mailmodule->require_module as $key)
                {
                    if (! $conf->$key->enabled || (! $user->admin && $obj->require_admin))
                    {
                        $qualified=0;
                        //print "Les prérequis d'activation du module mailing ne sont pas respectés. Il ne sera pas actif";
                        break;
                    }
                }
                
                // Si le module mailing est qualifié
                if ($qualified)
                {
                    print '<tr '.$bc[$var].'>';
        
                    $result=$db->query($sql);
                    if ($result) 
                    {
                      $num = $db->num_rows($result);
                    
                      $i = 0;
                    
                      while ($i < $num ) 
                        {
                          $obj = $db->fetch_object($result);
                          print '<td>'.img_object('',$mailmodule->picto).' '.$obj->label.'</td><td>'.$obj->nb.'<td>';
                          $i++;
                        }
                    
                      $db->free($result);
                    } 
                    else
                    {
                      dolibarr_print_error($db);
                    }
                    print '</tr>';
                }
            }            
        }
    }
}
closedir($handle);
 


print "</table><br>";

print '</td><td valign="top" width="70%">';


/*
 * Liste des derniers mailings
 */

$sql  = "SELECT m.rowid, m.titre, m.nbemail, m.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " LIMIT 10";
$result=$db->query($sql);
if ($result) 
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastMailings",10).'</td><td align="center">'.$langs->trans("Status").'</td></tr>';

  $num = $db->num_rows($result);
  if ($num > 0)
    { 
      $var = true;
      $i = 0;
      
      while ($i < $num ) 
	{
	  $obj = $db->fetch_object($result);
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print '<td><a href="fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowEMail"),"email").' '.$obj->titre.'</a></td>';
	  print '<td align="right">'.($obj->nbemail?$obj->nbemail:"0")." ".$langs->trans("EMails").'</td>';
	  $mail=new Mailing($db);
	  print '<td align="center">'.$mail->statuts[$obj->statut].'</td>';
      print '</tr>';
	  $i++;
	}

    }
  else 
    {
     print '<tr><td>'.$langs->trans("None").'</td></tr>';   
    }
  print "</table><br>";
  $db->free($result);
} 
else
{
  dolibarr_print_error($db);
}



print '</td></tr>';
print '</table>';

$db->close();


// Affiche note legislation dans la langue
$htmlfile="../../langs/".$langs->defaultlang."/html/spam.html";
if (! is_readable($htmlfile)) {
    $htmlfile="../../langs/fr_FR/html/spam.html";
}
if (is_readable($htmlfile)) {
    print "<br>".$langs->trans("Note").":<br><hr>";
    include($htmlfile);
}
 

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
