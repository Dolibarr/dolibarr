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


$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $_GET["page"] ;
$pageprev = $_GET["page"] - 1;
$pagenext = $_GET["page"] + 1;

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";



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

if ($_GET["action"] == 'clear')
{
    // Chargement de la classe
    $file = $dir."/modules_mailings.php";
    $classname = "MailingTargets";
    require_once($file);
    
    $obj = new $classname($db);
    $obj->clear_target($_GET["rowid"]);
   
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
    print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$mil->statuts[$mil->statut].'</td></tr>';
    print '</table><br>';
    
    print "</div>";
    

    // Affiche les listes de sélection
    if ($mil->statut == 0) {
        print '<table class="noborder" width=\"100%\">';
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("RecipientSelectionModules").'</td>';
        print '<td align="center">'.$langs->trans("NbOfRecipients").'</td>';
        print '<td align="center" width="120">';
        if ($mil->statut == 0) {
           print $langs->trans("Actions");
        }
        print '</td>';
        print "</tr>\n";
        
        clearstatcache();
        
        $handle=opendir($dir);
        
        $var=True;
        while (($file = readdir($handle))!==false)
        {
            if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
            {
                if (eregi("(.*)\.(.*)\.(.*)",$file,$reg)) {
                    $var = !$var;
                    $modulename=$reg[1];
        
                    // Chargement de la classe
                    $file = $dir."/".$modulename.".modules.php";
                    $classname = "mailing_".$modulename;
                    require_once($file);
        
                    print '<tr '.$bc[$var].'>';
        
                    print '<td>';
                    $obj = new $classname($db);
                    print img_object('',$obj->picto).' '.$obj->getDesc();
                    print '</td>';
        
                    /*
                    print '<td width=\"100\">';
                    print $modulename;
                    print "</td>";
                    */
                    print '<td align="center">'.$obj->getNbOfRecipients().'</td>';
    
                    print '<td align="center">';
                    if ($mil->statut == 0) {
                        print '<form action="cibles.php?action=add&rowid='.$mil->id.'&module='.$modulename.'" method="POST"><input type="submit" value="'.$langs->trans("Add").'"></form>';
                    }
                    else {
                        //print $langs->trans("MailNoChangePossible");
                        print "&nbsp;";
                    }
                    print '</td>';
                }
                print "</tr>\n";
            }
        }
        closedir($handle);

        print '<tr>';
        print '<td>&nbsp;</td><td>&nbsp;</td><td align="center"><form action="cibles.php?action=clear&rowid='.$mil->id.'" method="POST"><input type="submit" value="'.$langs->trans("Clear").'"></form></td>';
        print '</tr>';

        print '</table><br>';
    }
    

    // Liste des destinataires sélectionnés
    $sql  = "SELECT mc.nom, mc.prenom, mc.email";
    $sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
    $sql .= " WHERE mc.fk_mailing=".$mil->id;
    if ($sortfield) { $sql .= " ORDER BY $sortfield $sortorder"; }
    $sql .= $db->plimit($conf->liste_limit+1, $offset);
    
    if ( $db->query($sql) )
    {
        $num = $db->num_rows();
    
        $addu = "&amp;id=".$mil->id."&amp;page=$page";;
        print_barre_liste($langs->trans("MailSelectedRecipients"), $page, "cibles.php","&amp;id=".$mil->id,$sortfield,$sortorder,"",$num);
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans("Lastname"),"cibles.php","mc.nom",$addu,"","",$sortfield);
        print_liste_field_titre($langs->trans("Firstname"),"cibles.php","mc.prenom",$addu,"","",$sortfield);
        print_liste_field_titre($langs->trans("EMail"),"cibles.php","mc.email",$addu,"","",$sortfield);
        print '</tr>';
        $var = true;
        $i = 0;
    
        while ($i < $num )
        {
            $obj = $db->fetch_object();
            $var=!$var;
    
            print "<tr $bc[$var]>";
            print '<td>'.stripslashes($obj->nom).'</a></td>';
            print '<td>'.stripslashes($obj->prenom).'</a></td>';
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
