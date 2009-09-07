<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/imports/import.php
 *      \ingroup    import
 *      \brief      Edit import page
 *      \version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/imports/import.class.php");
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/import/modules_import.php');

$langs->load("exports");


if (! $user->societe_id == 0)
accessforbidden();

$entitytoicon=array(
	'invoice'=>'bill','invoice_line'=>'bill',
	'order'=>'order' ,'order_line'=>'order',
	'intervention'=>'intervention' ,'inter_line'=>'intervention',
	'member'=>'user' ,'member_type'=>'group','subscription'=>'payment',
	'tax'=>'generic' ,'tax_type'=>'generic',
	'account'=>'account',
	'payment'=>'payment',
	'product'=>'product','stock'=>'generic','warehouse'=>'stock',
	'category'=>'generic',
	'other'=>'generic'
);
$entitytolang=array(		// Translation code
	'user'=>'User',
	'company'=>'Company','contact'=>'Contact',
	'invoice'=>'Bill','invoice_line'=>'InvoiceLine',
	'order'=>'Order','order_line'=>'OrderLine',
	'intervention'=>'Intervention' ,'inter_line'=>'InterLine',
	'member'=>'Member','member_type'=>'MemberType','subscription'=>'Subscription',
	'tax'=>'SocialContribution','tax_type'=>'DictionnarySocialContributions',
	'account'=>'BankTransactions',
	'payment'=>'Payment',
	'product'=>'Product','stock'=>'Stock','warehouse'=>'Warehouse',
	'category'=>'Category',
	'other'=>'Other'
);

$array_match_file_to_database=isset($_SESSION["dol_array_match_file_to_database"])?$_SESSION["dol_array_match_file_to_database"]:array();
$datatoimport=isset($_GET["datatoimport"])? $_GET["datatoimport"] : (isset($_POST["datatoimport"])?$_POST["datatoimport"]:'');
$action=isset($_GET["action"]) ? $_GET["action"] : (isset($_POST["action"])?$_POST["action"]:'');
$step=isset($_GET["step"])? $_GET["step"] : (isset($_POST["step"])?$_POST["step"]:1);
$import_name=isset($_POST["import_name"])? $_POST["import_name"] : '';
$hexa=isset($_POST["hexa"])? $_POST["hexa"] : '';
$importmodelid=isset($_POST["importmodelid"])? $_POST["importmodelid"] : '';

$objimport=new Import($db);
$objimport->load_arrays($user,$datatoimport);

$objmodelimport=new ModeleImports();
$html = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$sqlusedforimport='';


/*
 * Actions
 */

if ($action=='downfield' || $action=='upfield')
{
	$pos=$array_match_file_to_database[$_GET["field"]];
	if ($action=='downfield') $newpos=$pos+1;
	if ($action=='upfield') $newpos=$pos-1;
	// Recherche code avec qui switcher
	$newcode="";
	foreach($array_match_file_to_database as $code=>$value)
	{
		if ($value == $newpos)
		{
			$newcode=$code;
			break;
		}
	}
	//print("Switch pos=$pos (code=".$_GET["field"].") and newpos=$newpos (code=$newcode)");
	if ($newcode)   // Si newcode trouve (protection contre resoumission de page)
	{
		$array_match_file_to_database[$_GET["field"]]=$newpos;
		$array_match_file_to_database[$newcode]=$pos;
		$_SESSION["dol_array_match_file_to_database"]=$array_match_file_to_database;
	}
}

if ($action == 'builddoc')
{
	// Build import file
	$result=$objimport->build_file($user, $_POST['model'], $datatoimport, $array_match_file_to_database);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$objimport->error.'</div>';
	}
	else
	{
		$mesg='<div class="ok">'.$langs->trans("FileSuccessfullyBuilt").'</div>';
		$sqlusedforimport=$objimport->sqlusedforimport;
	}
}

if ($action == 'deleteprof')
{
	if ($_GET["id"])
	{
		$objimport->fetch($_GET["id"]);
		$result=$objimport->delete($user);
	}
}

// Save import config to file
if ($action == 'add_import_model')
{
	if ($import_name)
	{
		asort($array_match_file_to_database);

		// Set save string
		$hexa='';
		foreach($array_match_file_to_database as $key=>$val)
		{
			if ($hexa) $hexa.=',';
			$hexa.=$key;
		}

		$objimport->model_name = $import_name;
		$objimport->datatoimport = $datatoimport;
		$objimport->hexa = $hexa;

		$result = $objimport->create($user);
		if ($result >= 0)
		{
			$mesg='<div class="ok">'.$langs->trans("ImportModelSaved",$objimport->model_name).'</div>';
		}
		else
		{
			$langs->load("errors");
			if ($objimport->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$mesg='<div class="error">'.$langs->trans("ErrorImportDuplicateProfil").'</div>';
			}
			else $mesg='<div class="error">'.$objimport->error.'</div>';
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("ImportModelName")).'</div>';
	}
}

if ($step == 3 && $action == 'select_model')
{
	// Load model from $importmodelid and set $array_match_file_to_database
	// and $_SESSION["dol_array_match_file_to_database"]
	$_SESSION["dol_array_match_file_to_database"]=array();
	$array_match_file_to_database=array();
	$result = $objimport->fetch($importmodelid);
	if ($result > 0)
	{
		$fieldsarray=split(',',$objimport->hexa);
		$i=1;
		foreach($fieldsarray as $val)
		{
			$array_match_file_to_database[$val]=$i;
			$i++;
		}
		$_SESSION["dol_array_match_file_to_database"]=$array_match_file_to_database;
	}
}



/*
 * Affichage Pages des Etapes
 */


if ($step == 1 || ! $datatoimport)
{
	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

	/*
	 * Affichage onglets
	 */
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$hselected=$h;
	$h++;

	/*
	 $head[$h][0] = '';
	 $head[$h][1] = $langs->trans("Step")." 2";
	 $h++;
	 */

	dol_fiche_head($head, $hselected, $langs->trans("NewImport"));


	print '<table class="notopnoleftnoright" width="100%">';

	print $langs->trans("SelectImportDataSet").'<br>';

	// Affiche les modules d'imports
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Module").'</td>';
	print '<td>'.$langs->trans("ImportableDatas").'</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';
	$val=true;
	if (sizeof($objimport->array_import_code))
	{
		foreach ($objimport->array_import_code as $key => $value)
		{
			$val=!$val;
			print '<tr '.$bc[$val].'><td nospan="nospan">';
			//print img_object($objimport->array_import_module[$key]->getName(),$import->array_import_module[$key]->picto).' ';
			print $objimport->array_import_module[$key]->getName();
			print '</td><td>';
			//print $value;
			print img_object($objimport->array_import_module[$key]->getName(),$objimport->array_import_icon[$key]).' ';
			print $objimport->array_import_label[$key];
			print '</td><td align="right">';
			if ($objimport->array_import_perms[$key])
			{
				print '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$objimport->array_import_code[$key].'">'.img_picto($langs->trans("NewImport"),'filenew').'</a>';
			}
			else
			{
				print $langs->trans("NotEnoughPermissions");
			}
			print '</td></tr>';
		}
	}
	else
	{
		print '<tr><td '.$bc[false].' colspan="3">'.$langs->trans("NoImportableData").'</td></tr>';
	}
	print '</table>';

	print '</table>';

	print '</div>';

	if ($mesg) print $mesg;

}

if ($step == 2 && $datatoimport)
{
	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

	/*
	 * Affichage onglets
	 */
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("NewImport"));


	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_module[0]->picto).' ';
	print $objimport->array_import_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a importer
	print '<tr><td width="25%">'.$langs->trans("DatasetToImport").'</td>';
	print '<td>';
	print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_icon[0]).' ';
	print $objimport->array_import_label[0];
	print '</td></tr>';

	print '</table>';
	print '<br>';



	print '<form name="userfile" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" METHOD="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';

	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

	$filetoimport='';
	$fullpathfiletoimport='';
	$var=true;

	// Add format informations and link to download example
	print '<tr class="liste_titre"><td colspan="6">';
	print $langs->trans("FileMustHaveOneOfFollowingFormat");
	print '</td></tr>';
	$liste=$objmodelimport->liste_modeles($db);
	foreach($liste as $key)
	{
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td width="16">'.img_picto_common($key,$objmodelimport->getPicto($key)).'</td>';
		print '<td>'.$objmodelimport->getDriverLabel($key).'</td>';
		print '<td colspan="4" align="right"><a href="'.DOL_URL_ROOT.'/imports/emptyexample.php?format='.$key.'&datatoimport='.$datatoimport.'" target="_blank">'.$langs->trans("DownloadEmptyExample").'</a></td>';
		//print '<td>'.$objmodelimport->getLibLabel($key).'</td><td>'.$objmodelimport->getLibVersion($key).'</td>';
		print '</tr>';
	}

	print '<tr><td colspan="6">&nbsp;</td></tr>';

	print '<tr><td colspan="6">'.$langs->trans("ChooseFileToImport",img_picto('','filenew')).'</td></tr>';

	print '<tr class="liste_titre"><td colspan="6">'.$langs->trans("FileWithDataToImport").'</td></tr>';

	// Input file name box
	$var=false;
	print '<tr '.$bc[$var].'><td colspan="6">';
	print '<input type="file"   name="userfile" size="20" maxlength="80"> &nbsp; &nbsp; ';
	print '<input type="submit" class="button" value="'.$langs->trans("AddFile").'" name="sendit">';
	print '<input type="hidden" value="'.$step.'" name="step">';
	print '<input type="hidden" value="'.$datatoimport.'" name="datatoimport">';
	print "</tr>\n";

	if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
	{
		create_exdir($conf->import->dir_temp);
		$nowyearmonth=dol_date('YmdHis',dol_now(),0);

		$fullpath=$conf->import->dir_temp . "/" . $nowyearmonth . '-'.$_FILES['userfile']['name'];
		if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $fullpath,1) > 0)
		{
			dol_syslog("File ".$fullpath." was added for import");
		}
		else
		{
			$langs->load("errors");
			$mesg = $langs->trans("ErrorFailedToSaveFile");
		}
	}

	$dir = $conf->import->dir_temp;

	// Search available imports
	$handle=@opendir($dir);
	if ($handle)
	{
		//print '<tr><td colspan="4">';
		//print '<table class="noborder" width="100%">';

		// Search available files to import
		$i=0;
		while (($file = readdir($handle))!==false)
		{
			if (eregi('^\.',$file)) continue;

			$modulepart='import';
			$urlsource=$_SERVER["PHP_SELF"].'?step='.$step.'&datatoimport='.$datatoimport;
			$relativepath=$file;
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td width="16">'.img_mime($file).'</td>';
			print '<td>'.$file.'</td>';
			// Affiche taille fichier
			print '<td align="right">'.dol_filesize($dir.'/'.$file).'</td>';
			// Affiche date fichier
			print '<td align="right">'.dol_print_date(filemtime($dir.'/'.$file),'dayhour').'</td>';
			// Del button
			print '<td align="right"><a href="'.DOL_URL_ROOT.'/document.php?action=remove_file&amp;setp=2&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath);
			print '&amp;urlsource='.urlencode($urlsource);
			print '">'.img_delete().'</a></td>';
			// Action button
			print '<td align="right">';
			print '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=3&datatoimport='.$datatoimport.'&filetoimport='.urlencode($relativepath).'">'.img_picto($langs->trans("NewImport"),'filenew').'</a>';
			print '</td>';
			print '</tr>';
		}
		//print '</table></td></tr>';
	}

	print '</table></form>';


	print '</div>';

	if ($mesg) print $mesg;

}

// Page to make matching between source file and database fields
if ($step == 3 && $datatoimport)
{
	// Load source fields in input file
	$fieldssource=array(
		1=>array('name'=>'aa','example1'=>'val1','example2'=>'val2'),
		2=>array('name'=>'bb','example1'=>'valb1','example2'=>'valb2')
		);


	// Load targets fields in database
	$fieldstarget=$objimport->array_import_fields[0];

	$maxpos=max(sizeof($fieldssource),sizeof($fieldstarget));

	if (sizeof($array_match_file_to_database) == 0)
	{
		// This is first input in screen, we need to define the $array_match_file_to_database array
		$pos=1;
		while ($pos <= sizeof($fieldssource))
		{
			if (sizeof($fieldssource) > 1 && $pos <= sizeof($fieldssource))
			{
				$posbis=1;
				foreach($fieldstarget as $key => $val)
				{
					if ($posbis < $pos)
					{
						$posbis++;
						continue;
					}
					// We found the key of targets that is at position pos
					$array_match_file_to_database[$pos]=$key;
					break;
				}
			}
			$pos++;
		}
		// Save the match array in session. We now will use the array in session.
		$_SESSION["dol_array_match_file_to_database"]=$array_match_file_to_database;
	}

	// Now $array_match_file_to_database contains  fieldnb(1,2,3...)=>fielddatabase(key in $array_match_file_to_database)


	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

	$param='step=3&datatoimport='.$datatoimport.'&filetoimport='.urlencode($_GET["filetoimport"]);

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?'.$param;
	$head[$h][1] = $langs->trans("Step")." 3";
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("NewImport"));

	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_module[0]->picto).' ';
	print $objimport->array_import_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a importer
	print '<tr><td width="25%">'.$langs->trans("DatasetToImport").'</td>';
	print '<td>';
	print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_icon[0]).' ';
	print $objimport->array_import_label[0];
	print '</td></tr>';

	// Nbre champs importes
	print '<tr><td width="25%">'.$langs->trans("FileToImport").'</td>';
	print '<td>'.$_GET["filetoimport"].'</td></tr>';

	print '</table>';
	print '<br>';


    // List of import models
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="select_model">';
    print '<input type="hidden" name="step" value="3">';
    print '<input type="hidden" name="datatoimport" value="'.$datatoimport.'">';
    print '<table><tr><td colspan="2">';
    print $langs->trans("SelectImportFields",img_picto('','uparrow','')).' ';
    $htmlother->select_import_model($importmodelid,'importmodelid',$datatoimport,1);
    print '<input type="submit" class="button" value="'.$langs->trans("Select").'">';
    print '</td></tr></table>';
    print '</form>';

	// Title of array with fields
	print '<table class="nobordernopadding" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("FieldsInSourceFile").'</td>';
	print '<td>'.$langs->trans("FieldsInTargetDatabase").'</td>';
	print '</tr>';

	print '<tr valign="top"><td width="50%">';

	//var_dump($array_match_file_to_database);
	$pos=1;

	print "\n<!-- Box left container -->\n";
	print '<div id="left">'."\n";

	// List of source fields
//	print '<table width="100%" class="noborder">';
	$var=true;
	while ($pos <= $maxpos)
	{
		$var=!$var;

//		print "<tr ".$bc[$var].' height="20">';
//		print '<td>';
		// Get name of database field at position $pos into $namefield
		$namefield='';
		$posbis=1;
		foreach($fieldstarget as $key => $val)
		{
			if ($posbis < $pos)
			{
				$posbis++;
				continue;
			}
			// We found the key of targets that is at position pos
			$namefield=$key;
			break;
		}
		// Now we check if there is a file field linked to this $namefield database field
		$keyfound='';
		foreach($fieldssource as $key => $val)
		{
			if (! empty($array_match_file_to_database[$key]) && $array_match_file_to_database[$key] == $namefield)
			{
//				print $langs->trans("Field").' '.$key.': ';
//				print $fieldssource[$key]['name'].' ('.$fieldssource[$key]['example1'].')';
				$keyfound=$key;
				break;
			}
		}
//		print '</td>';

		show_elem($fieldssource,$pos,$var,$keyfound);

		// Arrows
//		print '<td align="center">&nbsp;';
		if (sizeof($fieldssource) > 1 && $pos <= sizeof($fieldssource))
		{
//	        if ($pos < $maxpos) print '<a href="'.$_SERVER["PHP_SELF"].'?step=3&datatoimport='.$datatoimport.'&action=downfield&fieldpos='.$pos.'&field='.$fieldssource[$pos]['name'].'&filetoimport='.urlencode($_GET["filetoimport"]).'">'.img_down().'</a>';
//    	    if ($pos > 1) print '<a href="'.$_SERVER["PHP_SELF"].'?step=3&datatoimport='.$datatoimport.'&action=upfield&fieldpos='.$pos.'&field='.$fieldssource[$pos]['name'].'&filetoimport='.urlencode($_GET["filetoimport"]).'">'.img_up().'</a>';
		}
//		print '&nbsp;</td>';

//		print '<td>';
//		if (sizeof($fieldssource) > 1 && $pos <= sizeof($fieldssource)) print ' -> ';
//		print '</td>';

//		print '</tr>';

		$pos++;

		if ($pos > sizeof($fieldstarget)) break;
	}

	//	print '</table>';

	print "</div>\n";
	print "<!-- End box container -->\n";


	print '</td><td width="50%">';

	// List of targets fields
	$i = 0;
	$var=true;
	print '<table width="100%" class="nobordernopadding">';
	foreach($fieldstarget as $code=>$label)
	{
		$var=!$var;
		print '<tr class="liste_total" height="20">';

		$i++;

		$entity=$objimport->array_import_entities[0][$code];
		$entityicon=$entitytoicon[$entity]?$entitytoicon[$entity]:$entity;
		$entitylang=$entitytolang[$entity]?$entitytolang[$entity]:$entity;

		print '<td nowrap="nowrap" style="font-weight: normal">'.img_object('',$entityicon).' '.$langs->trans($entitylang).'</td>';
		print '<td style="font-weight: normal">'.$langs->trans($label).' ('.$code.')</td>';

		print '</tr>';
		$save_select.=$bit;
	}
	print '</table>';

	print '</td></tr>';

	// List of not imported fields
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("NotImportedFields").'</td></tr>';

	print '<tr valign="top"><td width="50%">';

	print "\n<!-- Box forget container -->\n";
	print '<div id="right">'."\n";

	// Print all input fields discarded
	show_elem('','',$var,'');

	print "</div>\n";
	print "<!-- End box container -->\n";

	print '</td>';
	print '<td width="50%">';
	// Print empty cells
	show_elem('','',$var,'none');
	print '</td></tr>';

	print '</table>';

	print '</div>';


	if ($conf->use_javascript_ajax)
	{
		print "\n";
		print '<script type="text/javascript" language="javascript">'."\n";
		print 'function updateOrder(){'."\n";
		print 'var left_list = cleanSerialize(Sortable.serialize(\'left\'));'."\n";
	    //print 'var right_list = cleanSerialize(Sortable.serialize(\'right\'));'."\n";
	    print 'var boxorder = \'A:\' + left_list;'."\n";
	    //print 'var boxorder = \'A:\' + left_list + \'-B:\' + right_list;'."\n";
	    //alert( \'boxorder=\' + boxorder )."\n";
	    print 'var userid = \''.$user->id.'\';'."\n";
	    print 'var url = "ajaximport.php";'."\n";
	    print 'var datatoimport = "'.$datatoimport.'";'."\n";
	    print 'o_options = new Object();'."\n";
	    print 'o_options = {asynchronous:true,method: \'get\',parameters: \'step=3&boxorder=\' + boxorder + \'&userid=\' + userid + \'&datatoimport=\' + datatoimport};'."\n";
	    print 'var myAjax = new Ajax.Request(url, o_options);'."\n";
	    //print 'document.
	    print '}'."\n";
	  	print "\n";

	  	print '// <![CDATA['."\n";

	  	print 'Sortable.create(\'left\', {'."\n";
		print 'tag:\'div\', '."\n";
		print 'containment:["left","right"], '."\n";
		print 'constraint:false, '."\n";
		print "handle: 'boxhandle',"."\n";
		print 'onUpdate:updateOrder';
		print "});\n";

		print 'Sortable.create(\'right\', {'."\n";
		print 'tag:\'div\', '."\n";
		print 'containment:["right","left"], '."\n";
		print 'constraint:false, '."\n";
		print "handle: 'boxhandle',"."\n";
		print 'onUpdate:updateOrder';
		print "});\n";

		print '// ]]>'."\n";
		print '</script>'."\n";
	}


	if ($mesg) print $mesg;

	/*
	 * Barre d'action
	 */
	print '<div class="tabsAction">';

	if (sizeof($array_match_file_to_database))
	{
		print '<a class="butAction" href="import.php?step=4&datatoimport='.$datatoimport.'">'.$langs->trans("NextStep").'</a>';
	}

	print '</div>';


	// Area for profils import
	if (sizeof($array_match_file_to_database))
	{
		print '<br>';
		print $langs->trans("SaveImportModel");

		print '<form class="nocellnopadd" action="import.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add_import_model">';
		print '<input type="hidden" name="step" value="'.$step.'">';
		print '<input type="hidden" name="datatoimport" value="'.$datatoimport.'">';
		print '<input type="hidden" name="hexa" value="'.$hexa.'">';

		print '<table summary="selectofimportprofil" class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ImportModelName").'</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
		$var=false;
		print '<tr '.$bc[$var].'>';
		print '<td><input name="import_name" size="32" value=""></td><td align="right">';
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '</td></tr>';

		// List of existing import profils
		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."import_model";
		$sql.= " WHERE type = '".$datatoimport."'";
		$sql.= " ORDER BY rowid";
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$var=false;
			while ($i < $num)
			{
				$var=!$var;
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td>';
				print $obj->label;
				print '</td><td align="right">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoimport='.$datatoimport.'&action=deleteprof&id='.$obj->rowid.'">';
				print img_delete();
				print '</a>';
				print '</tr>';
				$i++;
			}
		}
		else {
			dol_print_error($db);
		}

		print '</table>';
		print '</form>';
	}

}

if ($step == 4 && $datatoimport)
{
	asort($array_match_file_to_database);

	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

	/*
	 * Affichage onglets
	 */
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=3&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 3";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=4&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 4";
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("NewImport"));

	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_module[0]->picto).' ';
	print $objimport->array_import_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a importer
	print '<tr><td width="25%">'.$langs->trans("DatasetToImport").'</td>';
	print '<td>';
	print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_icon[0]).' ';
	print $objimport->array_import_label[0];
	print '</td></tr>';

	// Nbre champs importes
	print '<tr><td width="25%">'.$langs->trans("ImportedFields").'</td>';
	$list='';
	foreach($array_match_file_to_database as $code=>$label)
	{
		$list.=($list?',':'');
		$list.=$langs->trans($objimport->array_import_fields[0][$code]);
	}
	print '<td>'.$list.'</td></tr>';

	print '</table>';
	print '<br>';

	print $langs->trans("NowClickToGenerateToBuildImportFile").'<br>';

	// Liste des formats d'imports disponibles
	$var=true;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
	print '<td>'.$langs->trans("LibraryUsed").'</td>';
	print '<td alig="right">'.$langs->trans("LibraryVersion").'</td>';
	print '</tr>';

	$liste=$objmodelimport->liste_modeles($db);
	foreach($liste as $key)
	{
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td width="16">'.img_picto_common($key,$objmodelimport->getPicto($key)).'</td>';
		print '<td>'.$objmodelimport->getDriverLabel($key).'</td><td>'.$objmodelimport->getLibLabel($key).'</td><td align="right">'.$objmodelimport->getLibVersion($key).'</td></tr>';
	}
	print '</table>';

	print '</div>';

	print '<table width="100%">';
	if ($mesg)
	{
		print '<tr><td colspan="2">';
		print $mesg;
		print '</td></tr>';
	}
	if ($sqlusedforimport && $user->admin)
	{
		print '<tr><td>';
		print info_admin($langs->trans("SQLUsedForImport").':<br> '.$sqlusedforimport);
		print '</td></tr>';
	}
	print '</table>';

	print '<table width="100%"><tr><td width="50%">';

	if (! is_dir($conf->import->dir_temp)) create_exdir($conf->import->dir_temp);

	// Affiche liste des documents
	// NB: La fonction show_documents rescanne les modules qd genallowed=1
	$formfile->show_documents('import','',$conf->import->dir_temp.'/'.$user->id,$_SERVER["PHP_SELF"].'?step=4&datatoimport='.$datatoimport,$liste,1,(! empty($_POST['model'])?$_POST['model']:'csv'),'',1);

	print '</td><td width="50%">&nbsp;</td></tr>';
	print '</table>';

	// If external library PHPEXCELREADER is available
	// and defined by PHPEXCELREADER constant.
	if (file_exists(PHPEXCELREADER.'excelreader.php'))
	{
		// Test d'affichage du tableau excel et csv
		//print '<table width="100%"><tr><td>';
		//require_once(DOL_DOCUMENT_ROOT.'/lib/viewfiles.lib.php');
		//viewExcelFileContent($conf->import->dir_temp.'/1/import_member_1.xls',5,3);
		//viewCsvFileContent($conf->import->dir_temp.'/1/import_member_1.csv',5);
		//print '</td></tr></table>';
	}
}


print '<br>';


$db->close();

llxFooter('$Date$ - $Revision$');


/*
 * Function to put the movable box of a source field
 */
function show_elem($fieldssource,$pos,$var,$key)
{
	global $langs,$bc;

	print "\n\n<!-- Box start -->\n";
	print '<div style="padding: 0px 0px 0px 0px;" id="boxto_'.$pos.'">'."\n";

	print '<table summary="boxtable'.$pos.'" width="100%" class="nobordernopadding">'."\n";
	print '<tr class="liste_total" height="20">';
	if (empty($key))
	{
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		print img_picto(($pos>0?$langs->trans("MoveField",$pos):''),'uparrow','class="boxhandle" style="cursor:move;"');
		print '</td>';
		print '<td style="font-weight: normal">';
		print $langs->trans("NoFields");
		print '</td>';
	}
	elseif ($key == 'none')
	{
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		print '&nbsp;';
		print '</td>';
		print '<td style="font-weight: normal">';
		print '&nbsp;';
		print '</td>';
	}
	else
	{
		//print '<td width="16">'.img_file('','').'</td>';
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		// The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
		print img_picto($langs->trans("MoveField",$pos),'uparrow','class="boxhandle" style="cursor:move;"');
		print '</td>';
		print '<td style="font-weight: normal">';
		print $langs->trans("Field").' '.$key.': ';
		print '<b>'.$fieldssource[$key]['name'].'</b> ('.$fieldssource[$key]['example1'].')';
		print '</td>';
	}
	print '</tr>';

	print "</table>\n";

	print "</div>\n";
	print "<!-- Box end -->\n\n";
}


?>
