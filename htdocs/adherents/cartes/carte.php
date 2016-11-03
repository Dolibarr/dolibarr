<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file 		htdocs/adherents/cartes/carte.php
 *	\ingroup    member
 *	\brief      Page to output members business cards
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/member/modules_cards.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/printsheet/modules_labels.php';

$langs->load("members");
$langs->load("errors");

// Choix de l'annee d'impression ou annee courante.
$now = dol_now();
$year=dol_print_date($now,'%Y');
$month=dol_print_date($now,'%m');
$day=dol_print_date($now,'%d');
$foruserid=GETPOST('foruserid');
$foruserlogin=GETPOST('foruserlogin');
$mode=GETPOST('mode');
$model=GETPOST("model");			// Doc template to use for business cards
$modellabel=GETPOST("modellabel");	// Doc template to use for address sheet
$mesg='';

$adherentstatic=new Adherent($db);


/*
 * Actions
 */

if ($mode == 'cardlogin' && empty($foruserlogin))
{
    $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Login"));
}

if ((! empty($foruserid) || ! empty($foruserlogin) || ! empty($mode)) && ! $mesg)
{
    $arrayofmembers=array();

    // request taking into account member with up to date subscriptions
    $sql = "SELECT d.rowid, d.firstname, d.lastname, d.login, d.societe as company, d.datefin,";
    $sql.= " d.address, d.zip, d.town, d.country, d.birth, d.email, d.photo,";
    $sql.= " t.libelle as type,";
    $sql.= " c.code as country_code, c.label as country";
    $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON d.country = c.rowid";
    $sql.= " WHERE d.fk_adherent_type = t.rowid AND d.statut = 1";
    if (is_numeric($foruserid)) $sql.=" AND d.rowid=".$foruserid;
    if ($foruserlogin) $sql.=" AND d.login='".$db->escape($foruserlogin)."'";
    $sql.= " ORDER BY d.rowid ASC";

    dol_syslog("Search members", LOG_DEBUG);
    $result = $db->query($sql);
    if ($result)
    {
    	$num = $db->num_rows($result);
    	$i = 0;
    	while ($i < $num)
    	{
    		$objp = $db->fetch_object($result);

    		if ($objp->country == '-') $objp->country='';

    		$adherentstatic->lastname=$objp->lastname;
    		$adherentstatic->firstname=$objp->firstname;

    		// List of values to scan for a replacement
            $substitutionarray = array (
                '%ID%'=>$objp->rowid,
                '%LOGIN%'=>$objp->login,
                '%FIRSTNAME%'=>$objp->firstname,
                '%LASTNAME%'=>$objp->lastname,
                '%FULLNAME%'=>$adherentstatic->getFullName($langs),
                '%COMPANY%'=>$objp->company,
                '%ADDRESS%'=>$objp->address,
                '%ZIP%'=>$objp->zip,
                '%TOWN%'=>$objp->town,
                '%COUNTRY%'=>$objp->country,
                '%COUNTRY_CODE%'=>$objp->country_code,
                '%EMAIL%'=>$objp->email,
                '%BIRTH%'=>dol_print_date($objp->birth,'day'),
                '%TYPE%'=>$objp->type,
                '%YEAR%'=>$year,
                '%MONTH%'=>$month,
                '%DAY%'=>$day,
                '%DOL_MAIN_URL_ROOT%'=>DOL_MAIN_URL_ROOT,
                '%SERVER%'=>"http://".$_SERVER["SERVER_NAME"]."/"
            );
            complete_substitutions_array($substitutionarray, $langs);

            // For business cards
            if (empty($mode) || $mode=='card' || $mode=='cardlogin')
            {
                $textleft=make_substitutions($conf->global->ADHERENT_CARD_TEXT, $substitutionarray);
                $textheader=make_substitutions($conf->global->ADHERENT_CARD_HEADER_TEXT, $substitutionarray);
                $textfooter=make_substitutions($conf->global->ADHERENT_CARD_FOOTER_TEXT, $substitutionarray);
                $textright=make_substitutions($conf->global->ADHERENT_CARD_TEXT_RIGHT, $substitutionarray);

                if (is_numeric($foruserid) || $foruserlogin)
                {
                    $nb = $_Avery_Labels[$model]['NX'] * $_Avery_Labels[$model]['NY'];
                    if ($nb <= 0) $nb=1;  // Protection to avoid empty page
                    
                    for($j=0;$j<$nb;$j++)
                    {
                        $arrayofmembers[]=array(
                        	'textleft'=>$textleft,
                            'textheader'=>$textheader,
                            'textfooter'=>$textfooter,
                            'textright'=>$textright,
                            'id'=>$objp->rowid,
                            'photo'=>$objp->photo
                        );
                    }
                }
                else
                {
                    $arrayofmembers[]=array(
                    	'textleft'=>$textleft,
                        'textheader'=>$textheader,
                        'textfooter'=>$textfooter,
                        'textright'=>$textright,
                        'id'=>$objp->rowid,
                        'photo'=>$objp->photo
                    );
                }
            }

            // For labels
            if ($mode == 'label')
            {
            	if (empty($conf->global->ADHERENT_ETIQUETTE_TEXT)) $conf->global->ADHERENT_ETIQUETTE_TEXT="%FULLNAME%\n%ADDRESS%\n%ZIP% %TOWN%\n%COUNTRY%";
                $textleft=make_substitutions($conf->global->ADHERENT_ETIQUETTE_TEXT, $substitutionarray);
                $textheader='';
                $textfooter='';
                $textright='';

                $arrayofmembers[]=array('textleft'=>$textleft,
                                        'textheader'=>$textheader,
                                        'textfooter'=>$textfooter,
                                        'textright'=>$textright,
                                        'id'=>$objp->rowid,
                                        'photo'=>$objp->photo);
            }

            $i++;
    	}

    	// Build and output PDF
        if (empty($mode) || $mode=='card' || $mode=='cardlogin')
        {
            if (! count($arrayofmembers))
            {
                $mesg=$langs->trans("ErrorRecordNotFound");
            }
            if (empty($model) || $model == '-1')
            {
            	$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DescADHERENT_CARD_TYPE"));
            }
            if (! $mesg) $result=members_card_pdf_create($db, $arrayofmembers, $model, $outputlangs);

        }
        elseif ($mode == 'label')
        {
            if (! count($arrayofmembers))
            {
                $mesg=$langs->trans("ErrorRecordNotFound");
            }
        	if (empty($modellabel) || $modellabel == '-1')
    		{
    			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DescADHERENT_ETIQUETTE_TYPE"));
    		}
        	if (! $mesg) $result=doc_label_pdf_create($db, $arrayofmembers, $modellabel, $outputlangs);
        }

    	if ($result <= 0)
    	{
    		dol_print_error('',$result);
    	}
    }
    else
    {
    	dol_print_error($db);
    }

    if (! $mesg)
    {
    	$db->close();
    	exit;
    }
}


/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("MembersCards"));

print load_fiche_titre($langs->trans("LinkToGeneratedPages"));
print '<br>';

print $langs->trans("LinkToGeneratedPagesDesc").'<br>';
print '<br>';

dol_htmloutput_errors($mesg);

print img_picto('','puce').' '.$langs->trans("DocForAllMembersCards",($conf->global->ADHERENT_CARD_TYPE?$conf->global->ADHERENT_CARD_TYPE:$langs->transnoentitiesnoconv("None"))).' ';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="foruserid" value="all">';
print '<input type="hidden" name="mode" value="card">';
print '<input type="hidden" name="action" value="builddoc">';
print $langs->trans("DescADHERENT_CARD_TYPE").' ';
// List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels=array();
foreach(array_keys($_Avery_Labels) as $codecards)
{
	$arrayoflabels[$codecards]=$_Avery_Labels[$codecards]['name'];
}
print $form->selectarray('model',$arrayoflabels,(GETPOST('model')?GETPOST('model'):$conf->global->ADHERENT_CARD_TYPE),1,0,0);
print '<br><input class="button" type="submit" value="'.$langs->trans("BuildDoc").'">';
print '</form>';
print '<br>';

print img_picto('','puce').' '.$langs->trans("DocForOneMemberCards",($conf->global->ADHERENT_CARD_TYPE?$conf->global->ADHERENT_CARD_TYPE:$langs->transnoentitiesnoconv("None"))).' ';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="mode" value="cardlogin">';
print '<input type="hidden" name="action" value="builddoc">';
print $langs->trans("DescADHERENT_CARD_TYPE").' ';
// List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels=array();
foreach(array_keys($_Avery_Labels) as $codecards)
{
	$arrayoflabels[$codecards]=$_Avery_Labels[$codecards]['name'];
}
print $form->selectarray('model',$arrayoflabels,(GETPOST('model')?GETPOST('model'):$conf->global->ADHERENT_CARD_TYPE),1,0,0);
print '<br>'.$langs->trans("Login").': <input size="10" type="text" name="foruserlogin" value="'.GETPOST('foruserlogin').'">';
print '<br><input class="button" type="submit" value="'.$langs->trans("BuildDoc").'">';
print '</form>';
print '<br>';

print img_picto('','puce').' '.$langs->trans("DocForLabels",$conf->global->ADHERENT_ETIQUETTE_TYPE).' ';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="mode" value="label">';
print '<input type="hidden" name="action" value="builddoc">';
print $langs->trans("DescADHERENT_ETIQUETTE_TYPE").' ';
// List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels=array();
foreach(array_keys($_Avery_Labels) as $codecards)
{
	$arrayoflabels[$codecards]=$_Avery_Labels[$codecards]['name'];
}
print $form->selectarray('modellabel',$arrayoflabels,(GETPOST('modellabel')?GETPOST('modellabel'):$conf->global->ADHERENT_ETIQUETTE_TYPE),1,0,0);
print '<br><input class="button" type="submit" value="'.$langs->trans("BuildDoc").'">';
print '</form>';
print '<br>';

llxFooter();

$db->close();
