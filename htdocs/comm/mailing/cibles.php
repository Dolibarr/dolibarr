<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@uers.sourceforge.net>
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

/**     \file       htdocs/comm/mailing/cibles.php
        \brief      Page des cibles de mailing
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("mails");

$dir=DOL_DOCUMENT_ROOT."/includes/modules/mailings";

$mesg = '';


/*
 * Actions
 */
if ($_GET["action"] == 'add')
{
    $modulename=$_GET["module"];
    
    // Chargement de la classe
    $file = $dir."/".$modulename.".modules.php";
    $classname = "mailing_".$modulename;
    require_once($file);
    
    $obj = new $classname($db);
    $obj->add_to_target($_GET["rowid"]);
   
    Header("Location: cibles.php?id=".$_GET["rowid"]);
}



/*
 * Liste des destinataires
 */

llxHeader("","",$langs->trans("MailCard"));

$mil = new Mailing($db);

$html = new Form($db);
if ($mil->fetch($_GET["id"]) == 0)
{
    
    $h=0;
    $head[$h][0] = DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$mil->id;
    $head[$h][1] = $langs->trans("MailCard");
    $h++;
    
    $head[$h][0] = DOL_URL_ROOT."/comm/mailing/cibles.php?id=".$mil->id;
    $head[$h][1] = $langs->trans("MailRecipients");
    $hselected = $h;
    $h++;
    
    /*
    $head[$h][0] = DOL_URL_ROOT."/comm/mailing/history.php?id=".$mil->id;
    $head[$h][1] = $langs->trans("MailHistory");
    $h++;
    */
    dolibarr_fiche_head($head, $hselected, $langs->trans("Mailing").": ".substr($mil->titre,0,20));
    
    
    print '<table class="border" width="100%">';
    
    print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$mil->titre.'</td></tr>';
    print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3">'.htmlentities($mil->email_from).'</td></tr>';
    print '<tr><td width="25%">'.$langs->trans("TotalNbOfDistinctRecipients").'</td><td colspan="3">'.($mil->nbemail?$mil->nbemail:'<font class="error">'.$langs->trans("NoTargetYet").'</font>').'</td></tr>';
    print '</table><br>';
    
    // Ajout d'une liste de sélection
    print '<table class="noborder" width=\"100%\">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("RecipientSelectionModules").'</td>';
    //print '<td>'.$langs->trans("Name").'</td>';
    print '<td align="center">'.$langs->trans("NbOfRecipients").'</td>';
    print '<td width="80">&nbsp;</td>';
    print "</tr>\n";
    
    clearstatcache();
    
    $handle=opendir($dir);
    
    $var=True;
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
        {
            $var = !$var;
            if (eregi("(.*)\.(.*)\.(.*)",$file,$reg)) {
                $modulename=$reg[1];
    
                // Chargement de la classe
                $file = $dir."/".$modulename.".modules.php";
                $classname = "mailing_".$modulename;
                require_once($file);
    
                print '<tr '.$bc[$var].'>';
    
                print '<td>';
                $obj = new $classname($db);
                print $obj->getDesc();
                print '</td>';
    
                /*
                print '<td width=\"100\">';
                print $modulename;
                print "</td>";
                */
                print '<td align="center">'.$obj->getNbOfRecipients().'</td>';
                print '<td><a href="cibles.php?action=add&amp;rowid='.$mil->id.'&amp;module='.$modulename.'">'.img_edit_add($langs->trans("AddRecipients")).'</a></td>';
            }
            print "</tr>\n";
        }
    }
    closedir($handle);
    
    print '</table><br>';
    
    
    print "</div>";
    
    $NBMAX=100;
    
    $sql  = "SELECT mc.nom, mc.prenom, mc.email";
    $sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
    $sql .= " WHERE mc.fk_mailing=".$mil->id;
    $sql .= " limit ".($NBMAX+1);
    
    if ( $db->query($sql) )
    {
        $num = $db->num_rows();
    
        print_titre($langs->trans("MailSelectedRecipients"));
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Firstname").'</td>';
        print '<td>'.$langs->trans("Lastname").'</td>';
        print '<td>'.$langs->trans("EMail").'</td>';
        print '</tr>';
        $var = true;
        $i = 0;
    
        while ($i < $num )
        {
            $obj = $db->fetch_object();
            $var=!$var;
    
            print "<tr $bc[$var]>";
            print '<td>'.stripslashes($obj->prenom).'</a></td>';
            print '<td>'.stripslashes($obj->nom).'</a></td>';
            print '<td>'.$obj->email.'</td>';
    
            $i++;
        }
    
        print "</table><br>";
    
        $db->free();
    }
    else
    {
        dolibarr_print_error($db);
    }
}
  

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
