<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/contact/fiche.php
        \ingroup    societe
        \brief      Onglet général d'un contact
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/vcard/vcard.class.php");

$langs->load("companies");
$langs->load("users");

$error = array();
$socid=$_GET["socid"]?$_GET["socid"]:$_POST["socid"];


if ($_GET["action"] == 'create_user' && $user->admin) 
{
  // Recuperation contact actuel
  $contact = new Contact($db);
  $result = $contact->fetch($_GET["id"]);
  
  // Creation user
  $nuser = new User($db);
  $nuser->nom = $contact->nom;
  $nuser->prenom = $contact->prenom;
  $nuser->create_from_contact($contact);
}

if ($_POST["action"] == 'add')
{
    if (! $_POST["name"])
    {
        array_push($error,$langs->trans("ErrorFieldRequired",$langs->trans("Lastname")));
        $_GET["action"]="create";
    }
    if (! $_POST["firstname"])
    {
        array_push($error,$langs->trans("ErrorFieldRequired",$langs->trans("Firstname")));
        $_GET["action"]="create";
    }

    if ($_POST["name"] && $_POST["firstname"])
    {
        $contact = new Contact($db);

        $contact->socid        = $_POST["socid"];

        $contact->name         = $_POST["name"];
        $contact->firstname    = $_POST["firstname"];
        $contact->civilite_id  = $_POST["civilite_id"];
        $contact->poste        = $_POST["poste"];
        $contact->address      = $_POST["address"];
        $contact->cp           = $_POST["cp"];
        $contact->ville        = $_POST["ville"];
        $contact->fk_pays      = $_POST["pays_id"];
        $contact->email        = $_POST["email"];
        $contact->phone_pro    = $_POST["phone_pro"];
        $contact->phone_perso  = $_POST["phone_perso"];
        $contact->phone_mobile = $_POST["phone_mobile"];
        $contact->fax          = $_POST["fax"];
        $contact->jabberid     = $_POST["jabberid"];

        $contact->note         = $_POST["note"];

        $id =  $contact->create($user);
        if ($id > 0)
        {
              Header("Location: fiche.php?id=".$id);
        }

        $error=array($contact->error);
    }
}

if ($_POST["action"] == 'confirm_delete' AND $_POST["confirm"] == 'yes') 
{
    $contact = new Contact($db);
    
    $contact->old_name      = $_POST["old_name"];
    $contact->old_firstname = $_POST["old_firstname"];
    
    $result = $contact->delete($_GET["id"]);
    
    Header("Location: index.php");
}


if ($_POST["action"] == 'update') 
{
    $contact = new Contact($db);
    
    $contact->old_name      = $_POST["old_name"];
    $contact->old_firstname = $_POST["old_firstname"];
    
    $contact->socid         = $_POST["socid"];
    $contact->name          = $_POST["name"];
    $contact->firstname     = $_POST["firstname"];
    $contact->civilite_id	  = $_POST["civilite_id"];
    $contact->poste         = $_POST["poste"];
    
    $contact->address       = $_POST["address"];
    $contact->cp            = $_POST["cp"];
    $contact->ville         = $_POST["ville"];
    $contact->fk_pays       = $_POST["pays_id"];
    
    $contact->email         = $_POST["email"];
    $contact->phone_pro     = $_POST["phone_pro"];
    $contact->phone_perso   = $_POST["phone_perso"];
    $contact->phone_mobile  = $_POST["phone_mobile"];
    $contact->fax           = $_POST["fax"];
    $contact->jabberid      = $_POST["jabberid"];
    
    $contact->note          = $_POST["note"];
    
    $result = $contact->update($_POST["contactid"], $user);
    
    if ($contact->error) 
    {
        $error = $contact->error;
    }
}


/*
 *
 *
 */

llxHeader();
$form = new Form($db);

if ($socid)
{
  $objsoc = new Societe($db);
  $objsoc->fetch($socid);
}

// Affiche les erreurs
if (sizeof($error))
{
    print "<div class='error'>";
    print join("<br>",$error);
    print "</div>\n";
}


/*
 * Onglets
 */
if ($_GET["id"] > 0)
{
  // Si edition contact deja existant

  $contact = new Contact($db);
  $return=$contact->fetch($_GET["id"], $user);
  if ($return < 0) {
      dolibarr_print_error('',$contact->error);
  }
  
  $h=0;
  $head[$h][0] = DOL_URL_ROOT.'/contact/fiche.php?id='.$_GET["id"];
  $head[$h][1] = $langs->trans("General");
  $hselected=$h;
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/contact/perso.php?id='.$_GET["id"];
  $head[$h][1] = $langs->trans("PersonalInformations");
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/contact/exportimport.php?id='.$_GET["id"];
  $head[$h][1] = $langs->trans("ExportImport");
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/contact/info.php?id='.$_GET["id"];
  $head[$h][1] = $langs->trans("Info");
  $h++;
  
  dolibarr_fiche_head($head, $hselected, $langs->trans("Contact").": ".$contact->firstname.' '.$contact->name);
}


/*
 * Confirmation de la suppression du contact
 *
 */
if ($_GET["action"] == 'delete')
{
  $form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"],"Supprimer le contact","Êtes-vous sûr de vouloir supprimer ce contact&nbsp;?","confirm_delete");
  print '<br>';
}

if ($_GET["action"] == 'create')
{
    /*
     * Fiche en mode creation
     *
     */
    print_fiche_titre($langs->trans("AddContact"));
    print '<br>';

    print '<form method="post" action="fiche.php">';
    print '<input type="hidden" name="action" value="add">';
    print '<table class="border" width="100%">';

    if ($socid)
    {
        // On remplit avec le numéro de la société par défaut
        if (strlen(trim($contact->phone_pro)) == 0)
        {
            $contact->phone_pro = $objsoc->tel;
        }

        print '<tr><td>'.$langs->trans("Company").'</td>';
        print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$socid.'">'.$objsoc->nom.'</a></td>';
        print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
        print '</td></tr>';
    }
    else {
        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
        //print $form->select_societes('','socid','');
        print $langs->trans("ContactNotLinkedToCompany");
        print '</td></tr>';
    }

    print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
    print $form->select_civilite($obj->civilite);
    print '</td></tr>';

    print '<tr><td width="15%">'.$langs->trans("Lastname").'</td><td width="35%"><input name="name" type="text" size="20" maxlength="80" value="'.$contact->nom.'"></td>';
    print '<td width="15%">'.$langs->trans("Firstname").'</td><td width="35%"><input name="firstname" type="text" size="15" maxlength="80" value="'.$contact->prenom.'"></td></tr>';

    print '<tr><td>Poste/Fonction</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td>';

    print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><input name="address" type="text" size="50" maxlength="80"></td>';

    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80" value="'.$contact->cp.'">&nbsp;';
    print '<input name="ville" type="text" size="20" value="'.$contact->ville.'" maxlength="80"></td></tr>';

    print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
    $form->select_pays($contact->fk_pays);
    print '</td></tr>';

    print '<tr><td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td>';
    print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

    print '<tr><td>Portable</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td>';
    print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80"></td></tr>';

    print '<tr><td>'.$langs->trans("Email").'</td><td colspan="3"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

    print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

    print '<tr><td>'.$langs->trans("Note").'</td><td colspan="3"><textarea name="note" cols="60" rows="3"></textarea></td></tr>';

    print '<tr><td>'.$langs->trans("BillingContact").'</td><td colspan="3">';
    print $form->selectyesno("facturation",$contact->facturation);
    print '</td></tr>';

    print '<tr><td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
    print "</table><br>";

    print "</form>";
}
elseif ($_GET["action"] == 'edit')
{
    /*
     * Fiche en mode edition
     *
     */

    print '<form method="post" action="fiche.php?id='.$_GET["id"].'">';
    print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="contactid" value="'.$contact->id.'">';
    print '<input type="hidden" name="old_name" value="'.$contact->name.'">';
    print '<input type="hidden" name="old_firstname" value="'.$contact->firstname.'">';
    print '<table class="border" width="100%">';

    if ($contact->socid > 0)
    {
        $objsoc = new Societe($db);
        $objsoc->fetch($contact->socid);

        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->nom_url.'</td></tr>';
    }
    else
    {
        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
        print $langs->trans("ContactNotLinkedToCompany");
        print '</td></tr>';
    }
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
    print $form->select_civilite($contact->civilite_id);
    print '</td></tr>';

    print '<tr><td width="15%">'.$langs->trans("Lastname").'</td><td width="35%"><input name="name" type="text" size="20" maxlength="80" value="'.$contact->name.'"></td>';
    print '<td width="15%">'.$langs->trans("Firstname").'</td><td width="35%"><input name="firstname" type="text" size="15" maxlength="80" value="'.$contact->firstname.'"></td></tr>';

    print '<tr><td>Poste/Fonction</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td></tr>';

    print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><input name="address" type="text" size="50" maxlength="80" value="'.$contact->address.'"></td>';

    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80" value="'.$contact->cp.'">&nbsp;';
    print '<input name="ville" type="text" size="20" value="'.$contact->ville.'" maxlength="80"></td></tr>';

    print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
    $form->select_pays($contact->fk_pays);
    print '</td></tr>';

    print '<tr><td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td>';
    print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

    print '<tr><td>Portable</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td>';
    print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80" value="'.$contact->fax.'"></td></tr>';

    print '<tr><td>'.$langs->trans("EMail").'</td><td><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td>';
    if ($conf->mailing->enabled)
    {
        $langs->load("mails");
        print '<td nowrap>'.$langs->trans("NbOfEMailingsReceived").'</td>';
        print '<td>'.$contact->getNbOfEMailings().'</td>';
    }
    else
    {
        print '<td colspan="2">&nbsp;</td>';
    }
    print '</tr>';

    print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

    print '<tr><td>'.$langs->trans("Note").'</td><td colspan="3">';
    print '<textarea name="note" cols="60" rows="3">';
    print $contact->note;
    print '</textarea></td></tr>';

    print '<tr><td>'.$langs->trans("BillingContact").'</td><td colspan="3">';
    print $form->selectyesno("facturation",$contact->facturation);
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
    if ($contact->user_id) print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$contact->user_id.'">'.$contact->user_login.'</a>';
    else print $langs->trans("NoDolibarrAccess");
    print '</td></tr>';
    
    print '<tr><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
    print '</table>';

    print "</form>";
}
else
{
    /*
     * Fiche en mode visualisation
     *
     */
    
    print '<table class="border" width="100%">';
    
    if ($contact->socid > 0)
    {
        $objsoc = new Societe($db);
        $objsoc->fetch($contact->socid);

        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->nom_url.'</td></tr>';
    }
    else
    {
        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
        print $langs->trans("ContactNotLinkedToCompany");
        print '</td></tr>';
    }
    
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
    //TODO Aller chercher le libellé de la civilite a partir de l'id $contact->civilite_id
    //print '<tr><td valign="top">Titre : '.$contact->civilite."<br>";
    print $contact->civilite_id;
    print '</td></tr>';
    
    print '<tr><td width="15%">'.$langs->trans("Lastname").'</td><td width="35%">'.$contact->name.'</td>';
    print '<td width="15%">'.$langs->trans("Firstname").'</td><td width="35%">'.$contact->firstname.'</td></tr>';
    
    print '<tr><td>Poste/Fonction</td><td colspan="3">'.$contact->poste.'</td>';
    
    print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3">'.$contact->address.'</td></tr>';
    
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">'.$contact->cp.'&nbsp;';
    print $contact->ville.'</td></tr>';
    
    print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
    print $contact->pays;
    print '</td></tr>';

    print '<tr><td>Tel Pro</td><td>'.$contact->phone_pro.'</td>';
    print '<td>Tel Perso</td><td>'.$contact->phone_perso.'</td></tr>';
    
    print '<tr><td>Portable</td><td>'.$contact->phone_mobile.'</td>';
    print '<td>'.$langs->trans("Fax").'</td><td>'.$contact->fax.'</td></tr>';
    
    print '<tr><td>'.$langs->trans("EMail").'</td><td>';
    if ($contact->email && ! ValidEmail($contact->email))
    {
        print '<font class="error">'.$langs->trans("ErrorBadEMail",$contact->email)."</font>";
    }
    else
    {
        print $contact->email;
    }
    print '</td>';
    if ($conf->mailing->enabled)
    {
        $langs->load("mails");
        print '<td nowrap>'.$langs->trans("NbOfEMailingsReceived").'</td>';
        print '<td>'.$contact->getNbOfEMailings().'</td>';
    }
    else
    {
        print '<td colspan="2">&nbsp;</td>';
    }
    print '</tr>';

    print '<tr><td>Jabberid</td><td colspan="3">'.$contact->jabberid.'</td></tr>';
    
    print '<tr><td>'.$langs->trans("Note").'</td><td colspan="3">';
    print nl2br($contact->note);
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("BillingContact").'</td><td colspan="3">';
    print yn($contact->facturation);
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
    if ($contact->user_id) print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$contact->user_id.'">'.$contact->user_login.'</a>';
    else print $langs->trans("NoDolibarrAccess");
    print '</td></tr>';
    
    print "</table>";
    
    print "</div>";
    
    
    // Barre d'actions
    if (! $user->societe_id)
    {
        print '<div class="tabsAction">';
    
        print '<a class="tabAction" href="fiche.php?id='.$contact->id.'&amp;action=edit">'.$langs->trans('Edit').'</a>';
    
        if (! $contact->user_id && $user->admin)
        {
            print '<a class="tabAction" href="fiche.php?id='.$contact->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a>';
        }
    
        print '<a class="butDelete" href="fiche.php?id='.$contact->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
    
        print "</div><br>";
    }
    
    
    // Historique des actions sur ce contact
    print_titre ($langs->trans("TasksHistoryForThisContact"));
    $histo=array();
    $numaction = 0 ;
    
    // Recherche histo sur actioncomm
    $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, a.note, a.percent as percent,";
    $sql.= " c.code as acode, c.libelle,";
    $sql.= " u.rowid as user_id, u.code";
    $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
    $sql.= " WHERE fk_contact = ".$contact->id;
    $sql.= " AND u.rowid = a.fk_user_author";
    $sql.= " AND c.id=a.fk_action";
    $sql.= " ORDER BY a.datea DESC, a.id DESC";
    
    $resql=$db->query($sql);
    if ($resql)
    {
        $i = 0 ;
        $num = $db->num_rows($resql);
        $var=true;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $histo[$numaction]=array('type'=>'action','id'=>$obj->id,'date'=>$obj->da,'note'=>$obj->note,'percent'=>$obj->percent,
                             'acode'=>$obj->acode,'libelle'=>$obj->libelle,
                             'userid'=>$obj->user_id,'code'=>$obj->code);
            $numaction++;
            $i++;
        }
    }
    else
    {
        dolibarr_print_error($db);
    }

    // Recherche histo sur mailing
    $sql = "SELECT m.rowid as id, ".$db->pdate("mc.date_envoi")." as da, m.titre as note, '100' as percent,";
    $sql.= " 'AC_EMAILING' as acode,";
    $sql.= " u.rowid as user_id, u.code";
    $sql.= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u ";
    $sql.= " WHERE mc.email = '".$contact->email."'";
    $sql.= " AND mc.statut = 1";
    $sql.= " AND u.rowid = m.fk_user_valid";
    $sql.= " AND mc.fk_mailing=m.rowid";
    $sql.= " ORDER BY mc.date_envoi DESC, m.rowid DESC";

    $resql=$db->query($sql);
    if ($resql)
    {
        $i = 0 ;
        $num = $db->num_rows($resql);
        $var=true;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $histo[$numaction]=array('type'=>'mailing','id'=>$obj->id,'date'=>$obj->da,'note'=>$obj->note,'percent'=>$obj->percent,
                             'acode'=>$obj->acode,'libelle'=>$obj->libelle,
                             'userid'=>$obj->user_id,'code'=>$obj->code);
            $numaction++;
            $i++;
        }
    }
    else
    {
        dolibarr_print_error($db);
    }

    // Affichage actions sur contact
    print '<table width="100%" class="noborder">';
    
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Date").'</td>';
    print '<td align="center">'.$langs->trans("Status").'</td>';
    print '<td>'.$langs->trans("Actions").'</td>';
    print '<td>'.$langs->trans("Comments").'</td>';
    print '<td>'.$langs->trans("Author").'</td></tr>';

    foreach ($histo as $key=>$value)
    {
        $var=!$var;
        print "<tr $bc[$var]>";

        // Date
        print "<td>". dolibarr_print_date($histo[$key]['date'],"%d %b %Y %H:%M") ."</td>";

        // Status/Percent
        if ($histo[$key]['percent'] < 100) {
            print "<td align=\"center\">".$histo[$key]['percent']."%</td>";
        }
        else {
            print "<td align=\"center\">".$langs->trans("Done")."</td>";
        }

        // Action
        print '<td>';
        if ($histo[$key]['type']=='action')
        {
            print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowTask"),"task").' ';
            $transcode=$langs->trans("Action".$histo[$key]['acode']);
            $libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:$histo[$key]['libelle']);
            print dolibarr_trunc($libelle,30);
            print '</a>';
        }
        if ($histo[$key]['type']=='mailing')
        {
            print '<a href="'.DOL_URL_ROOT.'/comm/mailing/fiche.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"),"email").' ';
            $transcode=$langs->trans("Action".$histo[$key]['acode']);
            $libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:'Send mass mailing');
            print dolibarr_trunc($libelle,30);
            print '</a>';
        }
        print '</td>';

        // Note
        print '<td>'.dolibarr_trunc($histo[$key]['note'], 30).'</td>';

        // Author
        print '<td>';
        if ($histo[$key]['code'])
        {
            print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$histo[$key]['userid'].'">'.img_object($langs->trans("ShowUser"),'user').' '.$histo[$key]['code'].'</a>';
        }
        else print "&nbsp;";
        print "</td>";
        print "</tr>\n";
    }
    print "</table>";
  
    print '<br>';
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
