<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file 		htdocs/barcode/printsheet.php
 *	\ingroup    member
 *	\brief      Page to print sheets with barcodes using the document templates into core/modules/printsheets
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/printsheet/modules_labels.php';

$langs->load("admin");
$langs->load("errors");

// Choix de l'annee d'impression ou annee courante.
$now = dol_now();
$year=dol_print_date($now,'%Y');
$month=dol_print_date($now,'%m');
$day=dol_print_date($now,'%d');
$forbarcode=GETPOST('forbarcode');
$forbartype=GETPOST('forbartype');
$mode=GETPOST('mode');
$model=GETPOST("model");			// Doc template to use for business cards
$modellabel=GETPOST("modellabel");	// Doc template to use for address sheet
$mesg='';


/*
 * Actions
 */

if ($action == 'builddoc' && empty($forbarcode))
{
    $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Barcode"));
}
if ($action == 'builddoc' && empty($forbartype))
{
    $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("BarcodeType"));
}

if ((! empty($forbarcode) || ! empty($forbartype) || ! empty($mode)) && ! $mesg)
{
	// List of values to scan for a replacement
	$substitutionarray = array (
	'%LOGIN%'=>$user->login,
	'%COMPANY%'=>$mysoc->name,
	'%ADDRESS%'=>$mysoc->address,
	'%ZIP%'=>$mysoc->zip,
	'%TOWN%'=>$mysoc->town,
	'%COUNTRY%'=>$mysoc->country,
	'%COUNTRY_CODE%'=>$mysoc->country_code,
	'%EMAIL%'=>$mysoc->email,
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

		if (is_numeric($forbarcode) || $forbartype)
		{
			for($j=0;$j<100;$j++)
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

	// Build and output PDF
	if ($mode == 'label')
	{
		if (! count($arrayofmembers))
		{
			$mesg=$langs->trans("ErrorRecordNotFound");
		}
		if (empty($modellabel) || $modellabel == '-1')
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DescADHERENT_ETIQUETTE_TYPE"));
		}
		if (! $mesg) $result=members_label_pdf_create($db, $arrayofmembers, $modellabel, $outputlangs);
	}

	if ($result <= 0)
	{
		dol_print_error('',$result);
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

llxHeader('',$langs->trans("BarCodePrintsheet"));

print_fiche_titre($langs->trans("BarCodePrintsheet"));
print '<br>';

print $langs->trans("PageToGenerateBarCodeSheets").'<br>';
print '<br>';

dol_htmloutput_errors($mesg);

print img_picto('','puce').' '.$langs->trans("BarCodePrintsheet").' ';
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
print '<br>'.$langs->trans("Barcode").': <input size="10" type="text" name="forbarcode" value="'.GETPOST('forbarcode').'">';
print '<br>'.$langs->trans("Bartype").': <input size="10" type="text" name="forbartype" value="'.GETPOST('forbartype').'">';

$barcodestickersmask=GETPOST('barcodestickersmask');
print '<br>'.$langs->trans("BarcodeStickersMask").': <textarea cols="40" type="text" name="barcodestickersmask" value="'.GETPOST('barcodestickersmask').'">'.$barcodestickersmask.'</textarea>';
print '<br><input class="button" type="submit" value="'.$langs->trans("BuildDoc").'">';
print '</form>';
print '<br>';

llxFooter();

$db->close();
?>
