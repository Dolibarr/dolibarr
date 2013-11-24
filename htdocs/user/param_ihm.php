<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro.com>
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
 *       \file       htdocs/user/param_ihm.php
 *       \brief      Page to show user setup for display
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("users");
$langs->load("languages");

// Defini si peux lire/modifier permisssions
$canreaduser=($user->admin || $user->rights->user->user->lire);

$id = GETPOST('id','int');
$action = GETPOST('action','alpha');

if ($id)
{
    // $user est le user qui edite, $id est l'id de l'utilisateur edite
    $caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
    || (($user->id != $id) && $user->rights->user->user->creer));
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id)	// A user can always read its own card
{
    $feature2='';
    $canreaduser=1;
}
$result = restrictedArea($user, 'user', $id, '&user', $feature2);
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$dirtop = "../core/menus/standard";
$dirleft = "../core/menus/standard";

// Charge utilisateur edite
$fuser = new User($db);
$fuser->fetch($id);
$fuser->getrights();

// Liste des zone de recherche permanentes supportees
$searchform=array("main_searchform_societe","main_searchform_contact","main_searchform_produitservice");
$searchformconst=array($conf->global->MAIN_SEARCHFORM_SOCIETE,$conf->global->MAIN_SEARCHFORM_CONTACT,$conf->global->MAIN_SEARCHFORM_PRODUITSERVICE);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),$langs->trans("ProductsAndServices"));

$form = new Form($db);
$formadmin=new FormAdmin($db);


/*
 * Actions
 */
if ($action == 'update' && ($caneditfield  || $user->admin))
{
    if (! $_POST["cancel"])
    {
        $tabparam=array();

        if ($_POST["check_MAIN_LANG_DEFAULT"]=="on") $tabparam["MAIN_LANG_DEFAULT"]=$_POST["main_lang_default"];
        else $tabparam["MAIN_LANG_DEFAULT"]='';

        $tabparam["MAIN_MENU_STANDARD"]=$_POST["MAIN_MENU_STANDARD"];

        if ($_POST["check_SIZE_LISTE_LIMIT"]=="on") $tabparam["MAIN_SIZE_LISTE_LIMIT"]=$_POST["main_size_liste_limit"];
        else $tabparam["MAIN_SIZE_LISTE_LIMIT"]='';

        if ($_POST["check_MAIN_THEME"]=="on") $tabparam["MAIN_THEME"]=$_POST["main_theme"];
        else $tabparam["MAIN_THEME"]='';

        $tabparam["MAIN_SEARCHFORM_CONTACT"]=$_POST["main_searchform_contact"];
        $tabparam["MAIN_SEARCHFORM_SOCIETE"]=$_POST["main_searchform_societe"];
        $tabparam["MAIN_SEARCHFORM_PRODUITSERVICE"]=$_POST["main_searchform_produitservice"];

        $result=dol_set_user_param($db, $conf, $fuser, $tabparam);

        $_SESSION["mainmenu"]="";   // Le gestionnaire de menu a pu changer

        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
        exit;
    }
}



/*
 * View
 */

llxHeader();

$head = user_prepare_head($fuser);

$title = $langs->trans("User");

if ($action == 'edit')
{
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$id.'">';
}


dol_fiche_head($head, 'guisetup', $title, 0, 'user');


print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
print '<td colspan="2">';
print $form->showrefnav($fuser,'id','',$user->rights->user->user->lire || $user->admin);
print '</td>';
print '</tr>';

// LastName
print '<tr><td width="25%" valign="top">'.$langs->trans("LastName").'</td>';
print '<td colspan="2">'.$fuser->lastname.'</td>';
print "</tr>\n";

// FirstName
print '<tr><td width="25%" valign="top">'.$langs->trans("FirstName").'</td>';
print '<td colspan="2">'.$fuser->firstname.'</td>';
print "</tr>\n";

print '</table><br>';


if ($action == 'edit')
{
	print '<script type="text/javascript" language="javascript">
	jQuery(document).ready(function() {
		$("#main_lang_default").change(function() {
			$("#check_MAIN_LANG_DEFAULT").attr(\'checked\', true);
		});
		$("#main_size_liste_limit").keyup(function() {
			if ($(this).val().length) $("#check_SIZE_LISTE_LIMIT").attr(\'checked\', true);
			else $("#check_SIZE_LISTE_LIMIT").attr(\'checked\', false);
		});
	});
	</script>';

    clearstatcache();
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="25%">'.$langs->trans("Parameter").'</td><td width="25%">'.$langs->trans("DefaultValue").'</td><td>&nbsp;</td><td>'.$langs->trans("PersonalValue").'</td></tr>';

    // Langue par defaut
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("Language").'</td>';
    print '<td>';
    $s=picto_from_langcode($conf->global->MAIN_LANG_DEFAULT);
    print $s?$s.' ':'';
    print ($conf->global->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$conf->global->MAIN_LANG_DEFAULT));
    print '</td>';
    print '<td align="left" class="nowrap" width="20%"><input '.$bc[$var].' name="check_MAIN_LANG_DEFAULT" id="check_MAIN_LANG_DEFAULT" type="checkbox" '.(! empty($fuser->conf->MAIN_LANG_DEFAULT)?" checked":"");
    print ! empty($dolibarr_main_demo)?' disabled="disabled"':'';	// Disabled for demo
    print '> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>';
    print $formadmin->select_language((! empty($fuser->conf->MAIN_LANG_DEFAULT)?$fuser->conf->MAIN_LANG_DEFAULT:''),'main_lang_default',1);
    print '</td></tr>';

    // Taille max des listes
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MaxSizeList").'</td>';
    print '<td>'.$conf->global->MAIN_SIZE_LISTE_LIMIT.'</td>';
    print '<td align="left" class="nowrap" width="20%"><input '.$bc[$var].' name="check_SIZE_LISTE_LIMIT" id="check_SIZE_LISTE_LIMIT" type="checkbox" '.(! empty($fuser->conf->MAIN_SIZE_LISTE_LIMIT)?" checked":"");
    print ! empty($dolibarr_main_demo)?' disabled="disabled"':'';	// Disabled for demo
    print '> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td><input class="flat" name="main_size_liste_limit" id="main_size_liste_limit" size="4" value="' . (! empty($fuser->conf->MAIN_SIZE_LISTE_LIMIT)?$fuser->conf->MAIN_SIZE_LISTE_LIMIT:'') . '"></td></tr>';

    print '</table><br>';

    // Theme
    show_theme($fuser,(($user->admin || empty($dolibarr_main_demo))?1:0),true);

    dol_fiche_end();


    print '<center>';
    print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
    print ' &nbsp; &nbsp; ';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</center>';

    print '</form>';

}
else
{
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="25%">'.$langs->trans("Parameter").'</td><td width="25%">'.$langs->trans("DefaultValue").'</td><td>&nbsp;</td><td>'.$langs->trans("PersonalValue").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("Language").'</td>';
    print '<td>';
    $s=picto_from_langcode($conf->global->MAIN_LANG_DEFAULT);
    print ($s?$s.' ':'');
    print (isset($conf->global->MAIN_LANG_DEFAULT) && $conf->global->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$conf->global->MAIN_LANG_DEFAULT));
    print '</td>';
    print '<td align="left" class="nowrap" width="20%"><input '.$bc[$var].' type="checkbox" disabled '.(! empty($fuser->conf->MAIN_LANG_DEFAULT)?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>';
    $s=(isset($fuser->conf->MAIN_LANG_DEFAULT) ? picto_from_langcode($fuser->conf->MAIN_LANG_DEFAULT) : '');
    print ($s?$s.' ':'');
    print (isset($fuser->conf->MAIN_LANG_DEFAULT) && $fuser->conf->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):(! empty($fuser->conf->MAIN_LANG_DEFAULT)?$langs->trans("Language_".$fuser->conf->MAIN_LANG_DEFAULT):''));
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MaxSizeList").'</td>';
    print '<td>'.(! empty($conf->global->MAIN_SIZE_LISTE_LIMIT)?$conf->global->MAIN_SIZE_LISTE_LIMIT:'&nbsp;').'</td>';
    print '<td align="left" class="nowrap" width="20%"><input '.$bc[$var].' type="checkbox" disabled '.(! empty($fuser->conf->MAIN_SIZE_LISTE_LIMIT)?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>' . (! empty($fuser->conf->MAIN_SIZE_LISTE_LIMIT)?$fuser->conf->MAIN_SIZE_LISTE_LIMIT:'&nbsp;') . '</td></tr>';

    print '</table><br>';


    // Skin
    show_theme($fuser,0,true);

    dol_fiche_end();


    print '<div class="tabsAction">';
    if (empty($user->admin) && ! empty($dolibarr_main_demo))
    {
        print "<a class=\"butActionRefused\" title=\"".$langs->trans("FeatureDisabledInDemo")."\" href=\"#\">".$langs->trans("Modify")."</a>";
    }
    else
    {
        if ($user->id == $fuser->id || ! empty($user->admin))       // Si utilisateur edite = utilisateur courant (pas besoin de droits particulier car il s'agit d'une page de modif d'output et non de données) ou si admin
        {
            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$fuser->id.'">'.$langs->trans("Modify").'</a>';
        }
        else
        {
            print "<a class=\"butActionRefused\" title=\"".$langs->trans("NotEnoughPermissions")."\" href=\"#\">".$langs->trans("Modify")."</a>";
        }
    }

    print '</div>';

}

dol_fiche_end();

llxFooter();
$db->close();
?>
