<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/admin/events.php
        \ingroup    core
        \brief      Log event setup page
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$action=$_POST["action"];

$eventstolog=array(
	array('id'=>'ACTION_CREATE',          'test'=>$conf->societe->enabled),
	array('id'=>'COMPANY_CREATE',         'test'=>$conf->societe->enabled),
	array('id'=>'CONTRACT_VALIDATE',      'test'=>$conf->contrat->enabled),
	array('id'=>'CONTRACT_CANCEL',        'test'=>$conf->contrat->enabled),
	array('id'=>'CONTRACT_CLOSE',         'test'=>$conf->contrat->enabled),
	array('id'=>'PROPAL_VALIDATE',        'test'=>$conf->propal->enabled),
	array('id'=>'PROPAL_CLOSE_SIGNED',    'test'=>$conf->propal->enabled),
	array('id'=>'PROPAL_CLOSE_REFUSED',   'test'=>$conf->propal->enabled),
	array('id'=>'BILL_VALIDATE',          'test'=>$conf->facture->enabled),
	array('id'=>'BILL_PAYED',             'test'=>$conf->facture->enabled),
	array('id'=>'BILL_CANCELED',          'test'=>$conf->facture->enabled),
	array('id'=>'PAYMENT_CUSTOMER_CREATE','test'=>$conf->facture->enabled),
	array('id'=>'PAYMENT_SUPPLIER_CREATE','test'=>$conf->fournisseur->enabled),
	array('id'=>'MEMBER_VALIDATE',        'test'=>$conf->adherent->enabled),
	array('id'=>'MEMBER_SUBSCRIPTION',    'test'=>$conf->adherent->enabled),
	array('id'=>'MEMBER_MODIFY',          'test'=>$conf->adherent->enabled),
	array('id'=>'MEMBER_RESILIATE',       'test'=>$conf->adherent->enabled),
	array('id'=>'MEMBER_DELETE',          'test'=>$conf->adherent->enabled),
);


/*
*	Actions
*/
if ($action == "save")
{
    $i=0;

    $db->begin();
    
	foreach ($eventstolog as $key => $arr)
	{
		$param='MAIN_LOGEVENTS_'.$arr['id'];
		//print "param=".$param." - ".$_POST[$param];
		if (! empty($_POST[$param])) dolibarr_set_const($db,$param,$_POST[$param],'chaine',0);
		else dolibarr_del_const($db,$param);
	}
	
    $db->commit();
    $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
}



/**
 * Affichage du formulaire de saisie
 */

llxHeader();

//$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SecuritySetup"),'','setup');

print $langs->trans("LogEventDesc")."<br>\n";
print "<br>\n";

$head=security_prepare_head();

dolibarr_fiche_head($head, 'audit', $langs->trans("Security"));


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="action" value="save">';

$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td colspan=\"2\">".$langs->trans("LogEvents")."</td>";
print "</tr>\n";
foreach ($eventstolog as $key => $arr)
{
	if ($arr['id'])
	{
	    $var=!$var;
	    print '<tr '.$bc[$var].'>';
	    print '<td>'.$arr['id'].'</td>';
	    print '<td>';
	    $key='MAIN_LOGEVENTS_'.$arr['id'];
		$value=$conf->global->$key;
		print '<input type="checkbox" name="'.$key.'" value="1"'.($value?' checked="true"':'').'>';
	    print '</td></tr>'."\n";
	}
}
print '</table>';

print '<br><center>';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";

print '</div>';



if ($mesg) print "<br>$mesg<br>";
print "<br>";

// Show message
/*
$message='';
$urlwithouturlroot=eregi_replace(DOL_URL_ROOT.'$','',$dolibarr_main_url_root);
$urlvcal='<a href="'.DOL_URL_ROOT.'/webcal/webcalexport.php?format=vcal" target="_blank">'.$urlwithouturlroot.DOL_URL_ROOT.'/webcal/webcalexport.php?format=vcal'.'</a>';
$message.=$langs->trans("WebCalUrlForVCalExport",'vcal',$urlvcal);
$message.='<br>';
$urlical='<a href="'.DOL_URL_ROOT.'/webcal/webcalexport.php?format=ical&type=event" target="_blank">'.$urlwithouturlroot.DOL_URL_ROOT.'/webcal/webcalexport.php?format=ical&type=event'.'</a>';
$message.=$langs->trans("WebCalUrlForVCalExport",'ical',$urlical);
print info_admin($message);
*/

$db->close();

llxFooter('$Date$ - $Revision$');
?>
