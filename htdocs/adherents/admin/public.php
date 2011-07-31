<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     	\file       htdocs/adherents/admin/public.php
 *		\ingroup    member
 *		\brief      File of main public page for member module
 *		\author	    Laurent Destailleur
 *		\version    $Id: public.php,v 1.3 2011/08/03 00:45:42 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");

$langs->load("members");
$langs->load("admin");

$action=GETPOST('action');


/*
 * Actions
 */

if ($action == 'update')
{
    $i=0;
    $i+=dolibarr_set_const($db, "MEMBER_ENABLE_PUBLIC",$_POST["MEMBER_ENABLE_PUBLIC"],'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db, "MEMBER_NEWFORM_AMOUNT",$_POST["MEMBER_NEWFORM_AMOUNT"],'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db, "MEMBER_NEWFORM_EDITAMOUNT",$_POST["MEMBER_NEWFORM_EDITAMOUNT"],'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db, "MEMBER_NEWFORM_PAYONLINE",$_POST["MEMBER_NEWFORM_PAYONLINE"],'chaine',0,'',$conf->entity);
    if ($i == 4) $mesg=$langs->trans("RecordModifiedSuccessfully");
}



/*
 * View
 */

$form=new Form($db);

$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';

llxHeader('',$langs->trans("MembersSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MembersSetup"),$linkback,'setup');

$head = member_admin_prepare_head($adh);

dol_fiche_head($head, 'public', $langs->trans("Member"), 0, 'user');

dol_htmloutput_mesg($mesg);

if ($conf->use_javascript_ajax)
{
    print "\n".'<script type="text/javascript" language="javascript">';
    print 'jQuery(document).ready(function () {
                function initfields()
                {
                    if (jQuery("#MEMBER_ENABLE_PUBLIC").val()==\'0\')
                    {
                        jQuery(".drag").hide();
                    }
                    if (jQuery("#MEMBER_ENABLE_PUBLIC").val()==\'1\')
                    {
                        jQuery(".drag").show();
                    }
                }
                initfields();
                jQuery("#MEMBER_ENABLE_PUBLIC").change(function() {
                    initfields();
                });
           })';
    print '</script>'."\n";
}


print $langs->trans("BlankSubscriptionFormDesc").'<br><br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print "</tr>\n";
$var=true;

// Allow public form
$var=! $var;
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("EnablePublicSubscriptionForm");
print '</td><td width="60" align="right">';
print $form->selectyesno("MEMBER_ENABLE_PUBLIC",$conf->global->MEMBER_ENABLE_PUBLIC,1);
print "</td></tr>\n";
print '</form>';

// Type
/*$var=! $var;
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bcdd[$var].'><td>';
print $langs->trans("EnablePublicSubscriptionForm");
print '</td><td width="60" align="center">';
print $form->selectyesno("forcedate",$conf->global->MEMBER_NEWFORM_FORCETYPE,1);
print "</td></tr>\n"; */

// Amount
$var=! $var;
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bcdd[$var].'><td>';
print $langs->trans("DefaultAmount");
print '</td><td width="60" align="right">';
print '<input type="text" id="MEMBER_NEWFORM_AMOUNT" name="MEMBER_NEWFORM_AMOUNT" size="5" value="'.$conf->global->MEMBER_NEWFORM_AMOUNT.'">';;
print "</td></tr>\n";

// Can edit
$var=! $var;
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bcdd[$var].'><td>';
print $langs->trans("CanEditAmount");
print '</td><td width="60" align="right">';
print $form->selectyesno("MEMBER_NEWFORM_EDITAMOUNT",$conf->global->MEMBER_NEWFORM_EDITAMOUNT,1);
print "</td></tr>\n";

if ($conf->paybox->enabled || $conf->paypal->enabled)
{
    // Jump to an online payment page
    $var=! $var;
    print '<tr '.$bcdd[$var].'><td>';
    print $langs->trans("MEMBER_NEWFORM_PAYONLINE");
    print '</td><td width="60" align="right">';
    $listofval=array();
    if ($conf->paybox->enabled) $listofval['paybox']='Paybox';
    if ($conf->paypal->enabled) $listofval['paypal']='PayPal';
    print $form->selectarray("MEMBER_NEWFORM_PAYONLINE",$listofval,$conf->global->MEMBER_NEWFORM_PAYONLINE,1);
    print "</td></tr>\n";
}

print '</table>';

print '<center>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</center>';

print '</form>';

dol_fiche_end();


print '<br>';
//print $langs->trans('FollowingLinksArePublic').'<br>';
print img_picto('','object_globe.png').' '.$langs->trans('BlankSubscriptionForm').':<br>';
print '<a target="_blank" href="'.DOL_URL_ROOT.'/public/members/new.php'.'">'.DOL_MAIN_URL_ROOT.'/public/members/new.php'.'</a>';

/*
print '<table class="border" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre"><td>'.$langs->trans("Description").'</td><td>'.$langs->trans("URL").'</td></tr>';
print '<tr><td>'.$langs->trans("BlankSubscriptionForm").'</td><td>'..'</td></tr>';
print '<tr><td>'.$langs->trans("PublicMemberList").'</td><td>'.img_picto('','object_globe.png').' '.'<a target="_blank" href="'.DOL_URL_ROOT.'/public/members/public_list.php'.'">'.DOL_MAIN_URL_ROOT.'/public/members/public_list.php'.'</a></td></tr>';
print '<tr><td>'.$langs->trans("PublicMemberCard").'</td><td>'.img_picto('','object_globe.png').' '.DOL_MAIN_URL_ROOT.'/public/members/public_card.php?id=xxx'.'</td></tr>';
print '</table>';
*/

$db->close();

llxFooter('$Date: 2011/08/03 00:45:42 $ - $Revision: 1.3 $');
?>
