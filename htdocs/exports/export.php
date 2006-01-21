<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/exports/export.php
        \ingroup    core
        \brief      Page d'edition d'un export
        \version    $Revision$
*/
 
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/exports/export.class.php");

$langs->load("exports");

$user->getrights();

if (! $user->societe_id == 0)
  accessforbidden();

$array_selected=isset($_SESSION["export_selected_fields"])?$_SESSION["export_selected_fields"]:array();
$datatoexport=isset($_GET["datatoexport"])?$_GET["datatoexport"]:'';
$export=new Export($db);
$export->load_arrays($user,$datatoexport);
$action=isset($_GET["action"]) ? $_GET["action"] : (isset($_POST["action"])?$_POST["action"]:'');
$step=isset($_GET["step"])?$_GET["step"]:'1';



/*
 * Actions
 */
 
if ($action=='selectfield')
{ 
    $array_selected[$_GET["field"]]=sizeof($array_selected)+1;
    //print_r($array_selected);
    $_SESSION["export_selected_fields"]=$array_selected;
}
if ($action=='unselectfield')
{ 
    unset($array_selected[$_GET["field"]]);
    // Renumerote champs de array_selected (de 1 à nb_elements)
    asort($array_selected);
    $i=0;
    $array_selected_save=$array_selected;
    foreach($array_selected as $code=>$value)
    {
        $i++;
        $array_selected[$code]=$i;
        //print "x $code x $i y<br>";
    }
    $_SESSION["export_selected_fields"]=$array_selected;
}

if ($action=='downfield' || $action=='upfield')
{ 
    $pos=$array_selected[$_GET["field"]];
    if ($action=='downfield') $newpos=$pos+1;
    if ($action=='upfield') $newpos=$pos-1;
    // Recherche code avec qui switché
    $newcode="";
    foreach($array_selected as $code=>$value)
    {
        if ($value == $newpos)
        {
            $newcode=$code;
            break;
        }
    }
    //print("Switch pos=$pos (code=".$_GET["field"].") and newpos=$newpos (code=$newcode)");
    $array_selected[$_GET["field"]]=$newpos;
    $array_selected[$newcode]=$pos;
    $_SESSION["export_selected_fields"]=$array_selected;
}

if ($step == 1 || $action == 'cleanselect')
{
    $_SESSION["export_selected_fields"]=array();
    $array_selected=array();
}

if ($action == 'builddoc')
{
    include_once(DOL_DOCUMENT_ROOT.'/includes/modules/export/modules_export.php');
    $model=new ModeleExports();
    $liste=$model->liste_modeles($db);

    $model=$liste[$_POST["model"]];

    // Genère le fichier
	$export->build_file($user, $model, $datatoexport, $array_selected);
}


/*
 * Affichage Pages des Etapes
 */

if ($step == 1 || ! $datatoexport)
{
    llxHeader('',$langs->trans("NewExport"));

    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
    $head[$h][1] = $langs->trans("Step1");
    $hselected=$h;
    $h++;

    /*
    $head[$h][0] = '';
    $head[$h][1] = $langs->trans("Step2");
    $h++;
    */
    
    dolibarr_fiche_head($head, $hselected, $langs->trans("NewExport"));


    print '<table class="notopnoleftnoright" width="100%">';

    print $langs->trans("SelectExportDataSet").'<br>';
    print '<br>';
    
    // Affiche les modules d'exports
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td width="25%">'.$langs->trans("Module").'</td>';
    print '<td>'.$langs->trans("ExportableDatas").'</td>';
    print '<td>&nbsp;</td>';
    print '</tr>';
    $val=true;
    if (sizeof($export->array_export_code))
    {
        foreach ($export->array_export_code as $key => $value)
        {
            $val=!$val;
            print '<tr '.$bc[$val].'><td>';
            print img_object($export->array_export_module[$key]->getName(),$export->array_export_module[$key]->picto).' ';
            print $export->array_export_module[$key]->getName();
            print '</td><td>';
            print $export->array_export_label[$key];
            print '</td><td align="right">';
            print '<a href="'.DOL_URL_ROOT.'/exports/export.php?step=2&amp;datatoexport='.$export->array_export_code[$key].'">'.img_picto($langs->trans("NewExport"),'filenew').'</a>';
            print '</td></tr>';
        }
    }
    else
    {
        print '<tr><td '.$bc[false].' colspan="2">'.$langs->trans("NoExportableData").'</td></tr>';
    }
    print '</table>';    

    print '</table>';
}

if ($step == 2 && $datatoexport)
{
    llxHeader('',$langs->trans("NewExport"));
    
    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
    $head[$h][1] = $langs->trans("Step1");
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&amp;datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step2");
    $hselected=$h;
    $h++;
    
    dolibarr_fiche_head($head, $hselected, $langs->trans("NewExport"));

    print '<table width="100%" class="border">';

    // Module
    print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
    print '<td>';
    print img_object($export->array_export_module[0]->getName(),$export->array_export_module[0]->picto).' ';
    print $export->array_export_module[0]->getName();
    print '</td></tr>';

    // Lot de données à exporter
    print '<tr><td width="25%">'.$langs->trans("DatasetToExport").'</td>';
    print '<td>'.$export->array_export_label[0].'</td></tr>';

    print '</table>';
    print '<br>';
    
    print $langs->trans("SelectExportFields").'<br>';
    
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="48%">'.$langs->trans("ExportableFields").'</td>';
    print '<td width="4%">&nbsp;</td>';
    print '<td width="48%">'.$langs->trans("ExportedFields").'</td>';
    print '</tr>';

    // Champs exportables
    $fieldsarray=$export->array_export_fields[0];

#    $this->array_export_module[$i]=$module;
#    $this->array_export_code[$i]=$module->export_code[$r];
#    $this->array_export_label[$i]=$module->export_label[$r];
#    $this->array_export_fields_code[$i]=$module->export_fields_code[$r];
#    $this->array_export_fields_label[$i]=$module->export_fields_label[$r];
#    $this->array_export_sql[$i]=$module-
                                
    $var=true;
    foreach($fieldsarray as $code=>$label)
    {
        $var=!$var;
        print "<tr $bc[$var]>";
                    
        if (isset($array_selected[$code]) && $array_selected[$code])
        {
            // Champ sélectionné
            print '<td>&nbsp;</td>';
            print '<td><a href="'.$_SERVER["PHP_SELF"].'?step=2&amp;datatoexport='.$datatoexport.'&amp;action=unselectfield&amp;field='.$code.'">'.img_left().'</a></td>';
            print '<td>'.$langs->trans($label).' ('.$code.')</td>';
        }
        else
        {
            print '<td>'.$langs->trans($label).' ('.$code.')</td>';
            print '<td><a href="'.$_SERVER["PHP_SELF"].'?step=2&amp;datatoexport='.$datatoexport.'&amp;action=selectfield&amp;field='.$code.'">'.img_right().'</a></td>';
            print '<td>&nbsp;</td>';
        }

        print '</tr>';
    }
    
    print '</table>';

    print '</div>';
    
    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if (sizeof($array_selected))
    {
        print '<a class="butAction" href="export.php?step=3&amp;datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
    }

    print '</div>';    
}

if ($step == 3 && $datatoexport)
{
    asort($array_selected);

    llxHeader('',$langs->trans("NewExport"));
    
    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
    $head[$h][1] = $langs->trans("Step1");
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&amp;datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step2");
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=3&amp;datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step3");
    $hselected=$h;
    $h++;
    
    dolibarr_fiche_head($head, $hselected, $langs->trans("NewExport"));

    print '<table width="100%" class="border">';

    // Module
    print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
    print '<td>';
    print img_object($export->array_export_module[0]->getName(),$export->array_export_module[0]->picto).' ';
    print $export->array_export_module[0]->getName();
    print '</td></tr>';

    // Lot de données à exporter
    print '<tr><td width="25%">'.$langs->trans("DatasetToExport").'</td>';
    print '<td>'.$export->array_export_label[0].'</td></tr>';

    // Nbre champs exportés
    print '<tr><td width="25%">'.$langs->trans("ExportedFields").'</td>';
    $list='';
    foreach($array_selected as $code=>$value)
    {
        $list.=($list?',':'');
        $list.=$langs->trans($export->array_export_fields[0][$code]);
    }
    print '<td>'.$list.'</td></tr>';

    print '</table>';
    print '<br>';
    
    print $langs->trans("ChooseFieldsOrdersAndTitle").'<br>';
    
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="48%">'.$langs->trans("ExportedFields").'</td>';
    print '<td align="right" colspan="2">'.$langs->trans("Position").'</td>';
    print '<td>&nbsp;</td>';
    print '<td>'.$langs->trans("FieldsTitle").'</td>';
    print '</tr>';

    $var=true;
    foreach($array_selected as $code=>$value)
    {
        $var=!$var;
        print "<tr $bc[$var]>";
                    
        print '<td>'.$langs->trans($export->array_export_fields[0][$code]).' ('.$code.')</td>';

        print '<td align="right" width="100">';
        print $value.' ';
        print '</td><td align="center" width="20">';
        if ($value < sizeof($array_selected)) print '<a href="'.$_SERVER["PHP_SELF"].'?step=3&amp;datatoexport='.$datatoexport.'&amp;action=downfield&amp;field='.$code.'">'.img_down().'</a>';
        if ($value > 1) print '<a href="'.$_SERVER["PHP_SELF"].'?step=3&amp;datatoexport='.$datatoexport.'&amp;action=upfield&amp;field='.$code.'">'.img_up().'</a>';
        print '</td>';

        print '<td>&nbsp;</td>';

        print '<td>'.$langs->trans($export->array_export_fields[0][$code]).'</td>';

        print '</tr>';
    }
    
    print '</table>';

    print '</div>';
    
    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if (sizeof($array_selected))
    {
        print '<a class="butAction" href="export.php?step=4&amp;datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
    }

    print '</div>';    
}


if ($step == 4 && $datatoexport)
{
    asort($array_selected);

    llxHeader('',$langs->trans("NewExport"));
    
    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
    $head[$h][1] = $langs->trans("Step1");
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&amp;datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step2");
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=3&amp;datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step3");
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=4&amp;datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step4");
    $hselected=$h;
    $h++;
    
    dolibarr_fiche_head($head, $hselected, $langs->trans("NewExport"));

    print '<table width="100%" class="border">';

    // Module
    print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
    print '<td>';
    print img_object($export->array_export_module[0]->getName(),$export->array_export_module[0]->picto).' ';
    print $export->array_export_module[0]->getName();
    print '</td></tr>';

    // Lot de données à exporter
    print '<tr><td width="25%">'.$langs->trans("DatasetToExport").'</td>';
    print '<td>'.$export->array_export_label[0].'</td></tr>';

    // Nbre champs exportés
    print '<tr><td width="25%">'.$langs->trans("ExportedFields").'</td>';
    $list='';
    foreach($array_selected as $code=>$label)
    {
        $list.=($list?',':'');
        $list.=$langs->trans($export->array_export_fields[0][$code]);
    }
    print '<td>'.$list.'</td></tr>';

    print '</table>';
    print '<br>';
    
    print $langs->trans("NowClickToGenerateToBuildExportFile").'<br>';
    print '<br>';
    
    // Liste des formats d'exports disponibles
    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("AvailableFormats").'</td>';
    print '<td>'.$langs->trans("LibraryUsed").'</td>';
    print '<td>'.$langs->trans("LibraryVersion").'</td>';
    print '</tr>';

    include_once(DOL_DOCUMENT_ROOT.'/includes/modules/export/modules_export.php');
    $model=new ModeleExports();
    $liste=$model->liste_modeles($db);

    foreach($liste as $key)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td>'.$model->getModelName($key).'</td><td>'.$model->getDriverName($key).'</td><td>'.$model->getDriverVersion($key).'</td></tr>';
    }
    print '</table>';    

    print '</div>';

    $htmlform=new Form($db);
    print '<table width="100%"><tr><td width="50%">';

    if (! is_dir($conf->export->dir_ouput)) create_exdir($conf->export->dir_ouput);

    $filename=$datatoexport;
    $htmlform->show_documents('export',$filename,$conf->export->dir_ouput,$_SERVER["PHP_SELF"].'?step=4&amp;datatoexport='.$datatoexport,1,1,'csv');
    
    print '</td><td width="50%">&nbsp;</td></tr>';
    print '</table>';
}



$db->close();

llxFooter('$Date$ - $Revision$');

?>
