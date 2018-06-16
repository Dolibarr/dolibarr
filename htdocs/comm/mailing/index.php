<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/comm/mailing/index.php
 *       \ingroup    mailing
 *       \brief      Page accueil de la zone mailing
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT .'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('commercial', 'orders'));


// Security check
$result=restrictedArea($user,'mailing');


/*
 *	View
 */

$help_url='EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing';
llxHeader('','EMailing',$help_url);

print load_fiche_titre($langs->trans("MailingArea"));

//print '<table class="notopnoleftnoright" width="100%">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


//if (! empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
//{
    // Recherche emails
    print '<form method="post" action="'.DOL_URL_ROOT.'/comm/mailing/list.php">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAMailing").'</td></tr>';
    print '<tr class="oddeven"><td class="nowrap">';
    print $langs->trans("Ref").':</td><td><input type="text" class="flat inputsearch" name="sref"></td>';
    print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
    print '<tr class="oddeven"><td class="nowrap">';
    print $langs->trans("Other").':</td><td><input type="text" class="flat inputsearch" name="sall"></td>';

    print "</table></form><br>\n";
//}


// Affiche stats de tous les modules de destinataires mailings
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("TargetsStatistics").'</td></tr>';

$dir=DOL_DOCUMENT_ROOT."/core/modules/mailings";
$handle=opendir($dir);

if (is_resource($handle))
{
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
        {
            if (preg_match("/(.*)\.(.*)\.(.*)/i",$file,$reg))
            {
                $modulename=$reg[1];
       			if ($modulename == 'example') continue;

                // Loading Class
                $file = $dir."/".$modulename.".modules.php";
                $classname = "mailing_".$modulename;
                require_once $file;
                $mailmodule = new $classname($db);

                $qualified=1;
                foreach ($mailmodule->require_module as $key)
                {
                    if (! $conf->$key->enabled || (! $user->admin && $mailmodule->require_admin))
                    {
                        $qualified=0;
                        //print "Les pr�requis d'activation du module mailing ne sont pas respect�s. Il ne sera pas actif";
                        break;
                    }
                }

                // Si le module mailing est qualifi�
                if ($qualified)
                {
                    foreach ($mailmodule->getSqlArrayForStats() as $sql)
                    {
                        print '<tr class="oddeven">';

                        $result=$db->query($sql);
                        if ($result)
                        {
                          $num = $db->num_rows($result);

                          $i = 0;

                          while ($i < $num )
                            {
                              $obj = $db->fetch_object($result);
                              print '<td>'.img_object('',$mailmodule->picto).' '.$obj->label.'</td><td align="right">'.$obj->nb.'<td>';
                              $i++;
                            }

                          $db->free($result);
                        }
                        else
                        {
                          dol_print_error($db);
                        }
                        print '</tr>';
                    }
                }
            }
        }
    }
    closedir($handle);
}


print "</table><br>";


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * List of last emailings
 */
$limit=10;
$sql  = "SELECT m.rowid, m.titre, m.nbemail, m.statut, m.date_creat";
$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql.= " ORDER BY m.date_creat DESC";
$sql.= " LIMIT ".$limit;
$result=$db->query($sql);
if ($result)
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td colspan="2">'.$langs->trans("LastMailings",$limit).'</td>';
  print '<td align="center">'.$langs->trans("DateCreation").'</td>';
  print '<td align="center">'.$langs->trans("NbOfEMails").'</td>';
  print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/mailing/list.php">'.$langs->trans("AllEMailings").'</a></td></tr>';

  $num = $db->num_rows($result);
  if ($num > 0)
  {
      $i = 0;

    while ($i < $num )
	{
	  $obj = $db->fetch_object($result);

	  print '<tr class="oddeven">';
	  print '<td class="nowrap"><a href="card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowEMail"),"email").' '.$obj->rowid.'</a></td>';
	  print '<td>'.dol_trunc($obj->titre,38).'</td>';
	  print '<td align="center">'.dol_print_date($db->jdate($obj->date_creat),'day').'</td>';
	  print '<td align="center">'.($obj->nbemail?$obj->nbemail:"0").'</td>';
	  $mailstatic=new Mailing($db);
	  print '<td align="right">'.$mailstatic->LibStatut($obj->statut,5).'</td>';
      print '</tr>';
	  $i++;
	}

    }
  else
    {
     print '<tr><td class="opacitymedium">'.$langs->trans("None").'</td></tr>';
    }
  print "</table><br>";
  $db->free($result);
}
else
{
  dol_print_error($db);
}


//print '</td></tr></table>';
print '</div></div></div>';


if ($langs->file_exists("html/spam.html",0)) {
    print "<br><br><br><br>".$langs->trans("Note")."<br>";
    print '<div style="padding: 4px; background: #FAFAFA; border: 1px solid #BBBBBB;" >';
    dol_print_file($langs,"html/spam.html",0);
    print '</div>';

    print '<br>';
 }


llxFooter();

$db->close();
