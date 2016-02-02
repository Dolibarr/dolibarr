<?php
/* Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/admin/websites.php
 *		\ingroup    setup
 *		\brief      Page to administer web sites
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->load("errors");
$langs->load("admin");
$langs->load("companies");
$langs->load("website");

$action=GETPOST('action','alpha')?GETPOST('action','alpha'):'view';
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$rowid=GETPOST('rowid','alpha');

$id=1;

if (!$user->admin) accessforbidden();

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"),'switch_off');
$actl[1] = img_picto($langs->trans("Activated"),'switch_on');

$listoffset=GETPOST('listoffset');
$listlimit=GETPOST('listlimit')>0?GETPOST('listlimit'):1000;
$status = 1;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('admin'));

// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Name of SQL tables of dictionaries
$tabname=array();
$tabname[1] = MAIN_DB_PREFIX."website";

// Dictionary labels
$tablib=array();
$tablib[1] = "Websites";

// Requests to extract data
$tabsql=array();
$tabsql[1] = "SELECT f.rowid as rowid, f.entity, f.shortname, f.description, f.status FROM ".MAIN_DB_PREFIX."website as f";

// Criteria to sort dictionaries
$tabsqlsort=array();
$tabsqlsort[1] ="shortname ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield=array();
$tabfield[1] = "shortname,description";

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue=array();
$tabfieldvalue[1] = "shortname,description";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert=array();
$tabfieldinsert[1] = "shortname,description,entity";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid=array();
$tabrowid[1] = "";

// Condition to show dictionary in setup page
$tabcond=array();
$tabcond[1] = (! empty($conf->websites->enabled));

// List of help for fields
$tabhelp=array();
$tabhelp[1]  = array();

// List of check for fields (NOT USED YET)
$tabfieldcheck=array();
$tabfieldcheck[1]  = array();


// Define elementList and sourceList (used for dictionary type of contacts "llx_c_type_contact")
$elementList = array();
$sourceList=array();

// Actions add or modify an entry into a dictionary
if (GETPOST('actionadd') || GETPOST('actionmodify'))
{
    $listfield=explode(',',$tabfield[$id]);
    $listfieldinsert=explode(',',$tabfieldinsert[$id]);
    $listfieldmodify=explode(',',$tabfieldinsert[$id]);
    $listfieldvalue=explode(',',$tabfieldvalue[$id]);

    // Check that all fields are filled
    $ok=1;
    foreach ($listfield as $f => $value)
    {
        if (! isset($_POST[$value]) || $_POST[$value]=='')  // Fields that are not mandatory
        {
            $ok=0;
            $fieldnamekey=$listfield[$f];
            setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
        }
    }
    
    // Si verif ok et action add, on ajoute la ligne
    if ($ok && GETPOST('actionadd'))
    {
        if ($tabrowid[$id])
        {
            // Recupere id libre pour insertion
            $newid=0;
            $sql = "SELECT max(".$tabrowid[$id].") newid from ".$tabname[$id];
            $result = $db->query($sql);
            if ($result)
            {
                $obj = $db->fetch_object($result);
                $newid=($obj->newid + 1);

            } else {
                dol_print_error($db);
            }
        }

        // Add new entry
        $sql = "INSERT INTO ".$tabname[$id]." (";
        // List of fields
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldinsert))
        	$sql.= $tabrowid[$id].",";
        $sql.= $tabfieldinsert[$id];
        $sql.=",status)";
        $sql.= " VALUES(";

        // List of values
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldinsert))
        	$sql.= $newid.",";
        $i=0;
        foreach ($listfieldinsert as $f => $value)
        {
            if ($value == 'entity') {
            	$_POST[$listfieldvalue[$i]] = $conf->entity;
            }
            if ($i) $sql.=",";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.=",1)";

        dol_syslog("actionadd", LOG_DEBUG);
        $result = $db->query($sql);
        if ($result)	// Add is ok
        {
            setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
        	$_POST=array('id'=>$id);	// Clean $_POST array, we keep only
        }
        else
        {
            if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
            }
            else {
                dol_print_error($db);
            }
        }
    }

    // Si verif ok et action modify, on modifie la ligne
    if ($ok && GETPOST('actionmodify'))
    {
        if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
        else { $rowidcol="rowid"; }

        // Modify entry
        $sql = "UPDATE ".$tabname[$id]." SET ";
        // Modifie valeur des champs
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldmodify))
        {
            $sql.= $tabrowid[$id]."=";
            $sql.= "'".$db->escape($rowid)."', ";
        }
        $i = 0;
        foreach ($listfieldmodify as $field)
        {
            if ($field == 'entity') {
            	$_POST[$listfieldvalue[$i]] = $conf->entity;
            }
            if ($i) $sql.=",";
            $sql.= $field."=";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.= " WHERE ".$rowidcol." = '".$rowid."'";

        dol_syslog("actionmodify", LOG_DEBUG);
        //print $sql;
        $resql = $db->query($sql);
        if (! $resql)
        {
            setEventMessages($db->error(), null, 'errors');
        }
    }
    //$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

if (GETPOST('actioncancel'))
{
    //$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    $sql = "DELETE from ".MAIN_DB_PREFIX."website_pages WHERE fk_website ='".$rowid."'";
    $result = $db->query($sql);
    
    $sql = "DELETE from ".MAIN_DB_PREFIX."website WHERE rowid ='".$rowid."'";
    $result = $db->query($sql);
    if (! $result)
    {
        if ($db->errno() == 'DB_ERROR_CHILD_EXISTS')
        {
            setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
        }
        else
        {
            dol_print_error($db);
        }
    }
}

// activate
if ($action == $acts[0])
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    if ($rowid) {
        $sql = "UPDATE ".$tabname[$id]." SET status = 1 WHERE rowid ='".$rowid."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}

// disable
if ($action == $acts[1])
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    if ($rowid) {
        $sql = "UPDATE ".$tabname[$id]." SET status = 0 WHERE rowid ='".$rowid."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}



/*
 * View
 */

$form = new Form($db);
$formadmin=new FormAdmin($db);

llxHeader();

$titre=$langs->trans("WebsiteSetup");
$linkback='';
print load_fiche_titre($titre,$linkback,'title_setup');

print $langs->trans("WebsiteSetupDesc").'<br>';
print "<br>\n";


// Confirmation de la suppression de la ligne
if ($action == 'delete')
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid.'&id='.$id, $langs->trans('DeleteWebsite'), $langs->trans('ConfirmDeleteWebsite'), 'confirm_delete','',0,1);
}
//var_dump($elementList);

/*
 * Show a dictionary
 */
if ($id)
{
    // Complete requete recherche valeurs avec critere de tri
    $sql=$tabsql[$id];

    if ($sortfield)
    {
        // If sort order is "country", we use country_code instead
        $sql.= " ORDER BY ".$sortfield;
        if ($sortorder)
        {
            $sql.=" ".strtoupper($sortorder);
        }
        $sql.=", ";
        // Clear the required sort criteria for the tabsqlsort to be able to force it with selected value
        $tabsqlsort[$id]=preg_replace('/([a-z]+\.)?'.$sortfield.' '.$sortorder.',/i','',$tabsqlsort[$id]);
        $tabsqlsort[$id]=preg_replace('/([a-z]+\.)?'.$sortfield.',/i','',$tabsqlsort[$id]);
    }
    else {
        $sql.=" ORDER BY ";
    }
    $sql.=$tabsqlsort[$id];
    $sql.=$db->plimit($listlimit+1,$offset);
    //print $sql;

    $fieldlist=explode(',',$tabfield[$id]);

    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder" width="100%">';

    // Form to add a new line
    if ($tabname[$id])
    {
        $alabelisused=0;
        $var=false;

        $fieldlist=explode(',',$tabfield[$id]);

        // Line for title
        print '<tr class="liste_titre">';
        foreach ($fieldlist as $field => $value)
        {
            // Determine le nom du champ par rapport aux noms possibles
            // dans les dictionnaires de donnees
            $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
            $valuetoshow=$langs->trans($valuetoshow);   // try to translate
            $align="left";
            if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
            if ($valuetoshow != '')
            {
                print '<td align="'.$align.'">';
            	if (! empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i',$tabhelp[$id][$value])) print '<a href="'.$tabhelp[$id][$value].'" target="_blank">'.$valuetoshow.' '.img_help(1,$valuetoshow).'</a>';
            	else if (! empty($tabhelp[$id][$value])) print $form->textwithpicto($valuetoshow,$tabhelp[$id][$value]);
            	else print $valuetoshow;
                print '</td>';
             }
             if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') $alabelisused=1;
        }

        if ($id == 4) print '<td></td>';
        print '<td colspan="4">';
        print '<input type="hidden" name="id" value="'.$id.'">';
        print '</td>';
        print '</tr>';

        // Line to enter new values
        print "<tr ".$bcnd[$var].">";

        $obj = new stdClass();
        // If data was already input, we define them in obj to populate input fields.
        if (GETPOST('actionadd'))
        {
            foreach ($fieldlist as $key=>$val)
            {
                if (GETPOST($val))
                	$obj->$val=GETPOST($val);
            }
        }

        $tmpaction = 'create';
        $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
        $reshook=$hookmanager->executeHooks('createDictionaryFieldlist',$parameters, $obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
        $error=$hookmanager->error; $errors=$hookmanager->errors;

        if ($id == 3) unset($fieldlist[2]);

        if (empty($reshook))
        {
        	if ($tabname[$id] == MAIN_DB_PREFIX.'c_email_templates' && $action == 'edit')
        	{
				fieldList($fieldlist,$obj,$tabname[$id],'hide');
        	}
        	else
        	{
        		fieldList($fieldlist,$obj,$tabname[$id],'add');
        	}
        }

        if ($id == 4) print '<td></td>';
        print '<td colspan="3" align="right">';
        if ($action != 'edit')
        {
        	print '<input type="submit" class="button" name="actionadd" value="'.$langs->trans("Add").'">';
        }
        print '</td>';
        print "</tr>";

        $colspan=count($fieldlist)+2;
        if ($id == 4) $colspan++;

        if (! empty($alabelisused) && $id != 25)  // If there is one label among fields, we show legend of *
        {
        	print '<tr><td colspan="'.$colspan.'">* '.$langs->trans("LabelUsedByDefault").'.</td></tr>';
        }
        print '<tr><td colspan="'.$colspan.'">&nbsp;</td></tr>';	// Keep &nbsp; to have a line with enough height
    }

    print '</form>';



    // List of available values in database
    dol_syslog("htdocs/admin/dict", LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=true;
        if ($num)
        {
            // There is several pages
            if ($num > $listlimit)
            {
                print '<tr class="none"><td align="right" colspan="'.(3+count($fieldlist)).'">';
                print_fleche_navigation($page, $_SERVER["PHP_SELF"], '&id='.$id, ($num > $listlimit), '<li class="pagination"><span>'.$langs->trans("Page").' '.($page+1).'</span></li>');
                print '</td></tr>';
            }

            // Title of lines
            print '<tr class="liste_titre">';
            foreach ($fieldlist as $field => $value)
            {
                // Determine le nom du champ par rapport aux noms possibles
                // dans les dictionnaires de donnees
                $showfield=1;							  	// Par defaut
                $align="left";
                $sortable=1;
                $valuetoshow='';
                /*
                $tmparray=getLabelOfField($fieldlist[$field]);
                $showfield=$tmp['showfield'];
                $valuetoshow=$tmp['valuetoshow'];
                $align=$tmp['align'];
                $sortable=$tmp['sortable'];
				*/
                $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
                $valuetoshow=$langs->trans($valuetoshow);   // try to translate
                if ($fieldlist[$field]=='source')          { $valuetoshow=$langs->trans("Contact"); }
                if ($fieldlist[$field]=='price')           { $valuetoshow=$langs->trans("PriceUHT"); }
                if ($fieldlist[$field]=='taux')            {
					if ($tabname[$id] != MAIN_DB_PREFIX."c_revenuestamp") $valuetoshow=$langs->trans("Rate");
					else $valuetoshow=$langs->trans("Amount");
					$align='right';
	            }
                if ($fieldlist[$field]=='localtax1_type')  { $valuetoshow=$langs->trans("UseLocalTax")." 2"; $align="center"; $sortable=0; }
                if ($fieldlist[$field]=='localtax1')       { $valuetoshow=$langs->trans("Rate")." 2"; $sortable=0; }
                if ($fieldlist[$field]=='localtax2_type')  { $valuetoshow=$langs->trans("UseLocalTax")." 3"; $align="center"; $sortable=0; }
                if ($fieldlist[$field]=='localtax2')       { $valuetoshow=$langs->trans("Rate")." 3"; $sortable=0; }
                if ($fieldlist[$field]=='organization')    { $valuetoshow=$langs->trans("Organization"); }
                if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
                if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
                if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
                if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label')
                {
                	$valuetoshow=$langs->trans("Label");
                   	if ($id != 25) $valuetoshow.="*";
                }
                if ($fieldlist[$field]=='libelle_facture') { $valuetoshow=$langs->trans("LabelOnDocuments")."*"; }
                if ($fieldlist[$field]=='country')         { $valuetoshow=$langs->trans("Country"); }
                if ($fieldlist[$field]=='recuperableonly') { $valuetoshow=$langs->trans("NPR"); $align="center"; }
                if ($fieldlist[$field]=='nbjour')          { $valuetoshow=$langs->trans("NbOfDays"); }
                if ($fieldlist[$field]=='fdm')             { $valuetoshow=$langs->trans("AtEndOfMonth"); }
                if ($fieldlist[$field]=='decalage')        { $valuetoshow=$langs->trans("Offset"); }
                if ($fieldlist[$field]=='width')           { $valuetoshow=$langs->trans("Width"); }
                if ($fieldlist[$field]=='height')          { $valuetoshow=$langs->trans("Height"); }
                if ($fieldlist[$field]=='unit')            { $valuetoshow=$langs->trans("MeasuringUnit"); }
                if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='country_id') { $showfield=0; }
                if ($fieldlist[$field]=='accountancy_code'){ $valuetoshow=$langs->trans("AccountancyCode"); }
                if ($fieldlist[$field]=='accountancy_code_sell'){ $valuetoshow=$langs->trans("AccountancyCodeSell"); $sortable=0; }
                if ($fieldlist[$field]=='accountancy_code_buy'){ $valuetoshow=$langs->trans("AccountancyCodeBuy"); $sortable=0; }
				if ($fieldlist[$field]=='fk_pcg_version')  { $valuetoshow=$langs->trans("Pcg_version"); }
                if ($fieldlist[$field]=='account_parent')  { $valuetoshow=$langs->trans("Accountsparent"); }
                if ($fieldlist[$field]=='pcg_type')        { $valuetoshow=$langs->trans("Pcg_type"); }
                if ($fieldlist[$field]=='pcg_subtype')     { $valuetoshow=$langs->trans("Pcg_subtype"); }
                if ($fieldlist[$field]=='sortorder')       { $valuetoshow=$langs->trans("SortOrder"); }
	            if ($fieldlist[$field]=='short_label')     { $valuetoshow=$langs->trans("ShortLabel"); }
            	if ($fieldlist[$field]=='type_template')   { $valuetoshow=$langs->trans("TypeOfTemplate"); }

                // Affiche nom du champ
                if ($showfield)
                {
                    print getTitleFieldOfList($valuetoshow,0,$_SERVER["PHP_SELF"],($sortable?$fieldlist[$field]:''),($page?'page='.$page.'&':'').'&id='.$id,"","align=".$align,$sortfield,$sortorder);
                }
            }
			// Favorite - Only activated on country dictionary
            if ($id == 4) print getTitleFieldOfList($langs->trans("Favorite"),0,$_SERVER["PHP_SELF"],"favorite",($page?'page='.$page.'&':'').'&id='.$id,"",'align="center"',$sortfield,$sortorder);

			print getTitleFieldOfList($langs->trans("Status"),0,$_SERVER["PHP_SELF"],"status",($page?'page='.$page.'&':'').'&id='.$id,"",'align="center"',$sortfield,$sortorder);
            print getTitleFieldOfList('');
            print getTitleFieldOfList('');
            print '</tr>';

            // Lines with values
            while ($i < $num)
            {
                $var = ! $var;

                $obj = $db->fetch_object($resql);
                //print_r($obj);
                print '<tr '.$bc[$var].' id="rowid-'.$obj->rowid.'">';
                if ($action == 'edit' && ($rowid == (! empty($obj->rowid)?$obj->rowid:$obj->code)))
                {
                    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="page" value="'.$page.'">';
                    print '<input type="hidden" name="rowid" value="'.$rowid.'">';

                    $tmpaction='edit';
                    $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
                    $reshook=$hookmanager->executeHooks('editDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
                    $error=$hookmanager->error; $errors=$hookmanager->errors;

                    if (empty($reshook)) fieldList($fieldlist,$obj,$tabname[$id],'edit');

                    print '<td colspan="3" align="right"><a name="'.(! empty($obj->rowid)?$obj->rowid:$obj->code).'">&nbsp;</a><input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
                    print '&nbsp;<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'"></td>';
                }
                else
                {
	              	$tmpaction = 'view';
                    $parameters=array('var'=>$var, 'fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
                    $reshook=$hookmanager->executeHooks('viewDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks

                    $error=$hookmanager->error; $errors=$hookmanager->errors;

                    if (empty($reshook))
                    {
                        foreach ($fieldlist as $field => $value)
                        {
                            $showfield=1;
                        	$align="left";
                            $valuetoshow=$obj->$fieldlist[$field];
                            if ($value == 'type_template')
                            {
                                $valuetoshow = isset($elementList[$valuetoshow])?$elementList[$valuetoshow]:$valuetoshow;
                            }
                            if ($value == 'element')
                            {
                                $valuetoshow = isset($elementList[$valuetoshow])?$elementList[$valuetoshow]:$valuetoshow;
                            }
                            else if ($value == 'source')
                            {
                                $valuetoshow = isset($sourceList[$valuetoshow])?$sourceList[$valuetoshow]:$valuetoshow;
                            }
                            else if ($valuetoshow=='all') {
                                $valuetoshow=$langs->trans('All');
                            }
                            else if ($fieldlist[$field]=='country') {
                                if (empty($obj->country_code))
                                {
                                    $valuetoshow='-';
                                }
                                else
                                {
                                    $key=$langs->trans("Country".strtoupper($obj->country_code));
                                    $valuetoshow=($key != "Country".strtoupper($obj->country_code)?$obj->country_code." - ".$key:$obj->country);
                                }
                            }
                            else if ($fieldlist[$field]=='recuperableonly' || $fieldlist[$field]=='fdm' || $fieldlist[$field] == 'deductible') {
                                $valuetoshow=yn($valuetoshow);
                                $align="center";
                            }
                            else if ($fieldlist[$field]=='price' || preg_match('/^amount/i',$fieldlist[$field])) {
                                $valuetoshow=price($valuetoshow);
                            }
                            else if ($fieldlist[$field]=='libelle_facture') {
                                $langs->load("bills");
                                $key=$langs->trans("PaymentCondition".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "PaymentCondition".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                                $valuetoshow=nl2br($valuetoshow);
                            }
                            else if ($fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_country') {
                                $key=$langs->trans("Country".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "Country".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_availability') {
                                $langs->load("propal");
                                $key=$langs->trans("AvailabilityType".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "AvailabilityType".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_actioncomm') {
                                $key=$langs->trans("Action".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "Action".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if (! empty($obj->code_iso) && $fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_currencies') {
                                $key=$langs->trans("Currency".strtoupper($obj->code_iso));
                                $valuetoshow=($obj->code_iso && $key != "Currency".strtoupper($obj->code_iso)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_typent') {
                                $key=$langs->trans(strtoupper($obj->code));
                                $valuetoshow=($key != strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_prospectlevel') {
                                $key=$langs->trans(strtoupper($obj->code));
                                $valuetoshow=($key != strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_civility') {
                                $key=$langs->trans("Civility".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "Civility".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_type_contact') {
                            	$langs->load('agenda');
                                $key=$langs->trans("TypeContact_".$obj->element."_".$obj->source."_".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "TypeContact_".$obj->element."_".$obj->source."_".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_payment_term') {
                                $langs->load("bills");
                                $key=$langs->trans("PaymentConditionShort".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "PaymentConditionShort".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_paiement') {
                                $langs->load("bills");
                                $key=$langs->trans("PaymentType".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "PaymentType".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_input_reason') {
                                $key=$langs->trans("DemandReasonType".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "DemandReasonType".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_input_method') {
                                $langs->load("orders");
                                $key=$langs->trans($obj->code);
                                $valuetoshow=($obj->code && $key != $obj->code)?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_shipment_mode') {
                                $langs->load("sendings");
                                $key=$langs->trans("SendingMethod".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "SendingMethod".strtoupper($obj->code)?$key:$obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field] == 'libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_paper_format')
                            {
                                $key = $langs->trans('PaperFormat'.strtoupper($obj->code));
                                $valuetoshow = ($obj->code && $key != 'PaperFormat'.strtoupper($obj->code) ? $key : $obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field] == 'label' && $tabname[$id] == MAIN_DB_PREFIX.'c_type_fees')
                            {
                                $langs->load('trips');
                                $key = $langs->trans(strtoupper($obj->code));
                                $valuetoshow = ($obj->code && $key != strtoupper($obj->code) ? $key : $obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='country_id') {
                                $showfield=0;
                            }
                            else if ($fieldlist[$field]=='unicode') {
                            	$valuetoshow = $langs->getCurrencySymbol($obj->code,1);
                            }
                            else if ($fieldlist[$field]=='label' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_units') {
	                            $langs->load("products");
	                            $valuetoshow=$langs->trans($obj->$fieldlist[$field]);
                            }
                            else if ($fieldlist[$field]=='short_label' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_units') {
	                            $langs->load("products");
	                            $valuetoshow = $langs->trans($obj->$fieldlist[$field]);
                            }
                            else if (($fieldlist[$field] == 'unit') && ($tabname[$id] == MAIN_DB_PREFIX.'c_paper_format'))
                            {
                            	$key = $langs->trans('SizeUnit'.strtolower($obj->unit));
                                $valuetoshow = ($obj->code && $key != 'SizeUnit'.strtolower($obj->unit) ? $key : $obj->$fieldlist[$field]);
                            }

							else if ($fieldlist[$field]=='localtax1_type') {
							  if ($obj->localtax1 != 0)
							    $valuetoshow=$localtax_typeList[$valuetoshow];
							  else
							    $valuetoshow = '';
							  $align="center";
							}
							else if ($fieldlist[$field]=='localtax2_type') {
							 if ($obj->localtax2 != 0)
							    $valuetoshow=$localtax_typeList[$valuetoshow];
							  else
							    $valuetoshow = '';
							  $align="center";
							}
							else if ($fieldlist[$field]=='localtax1') {
                                $valuetoshow = price($valuetoshow, 0, $langs, 0, 0);
							  if ($obj->localtax1 == 0)
							    $valuetoshow = '';
							  $align="right";
							}
							else if ($fieldlist[$field]=='localtax2') {
                                $valuetoshow = price($valuetoshow, 0, $langs, 0, 0);
							  if ($obj->localtax2 == 0)
							    $valuetoshow = '';
							  $align="right";
							}
							else if (in_array($fieldlist[$field],array('taux','localtax1','localtax2')))
							{
                                $valuetoshow = price($valuetoshow, 0, $langs, 0, 0);
								$align="right";
							}
							else if (in_array($fieldlist[$field],array('recuperableonly')))
							{
								$align="center";
							}
							else if ($fieldlist[$field]=='accountancy_code' || $fieldlist[$field]=='accountancy_code_sell' || $fieldlist[$field]=='accountancy_code_buy') {
                                $valuetoshow = length_accountg($valuetoshow);
                            }

							// Show value for field
							if ($showfield) print '<td align="'.$align.'">'.$valuetoshow.'</td>';
                        }
                    }

                    // Can an entry be erased or disabled ?
                    $iserasable=1;$isdisable=1;	// true by default

                    if (isset($obj->code) && $id != 10)
                    {
                    	if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i',$obj->code))) { $iserasable = 0; $isdisable = 0; }
                    	else if ($obj->code == 'RECEP') { $iserasable = 0; $isdisable = 0; }
                    	else if ($obj->code == 'EF0')   { $iserasable = 0; $isdisable = 0; }
                    }

                    if (isset($obj->type) && in_array($obj->type, array('system', 'systemauto'))) { $iserasable=0; }
                    if (in_array($obj->code, array('AC_OTH','AC_OTH_AUTO')) || in_array($obj->type, array('systemauto'))) { $isdisable=0; $isdisable = 0; }

                    $url = $_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(! empty($obj->rowid)?$obj->rowid:(! empty($obj->code)?$obj->code:'')).'&amp;code='.(! empty($obj->code)?urlencode($obj->code):'').'&amp;id='.$id.'&amp;';

					// Favorite
					// Only activated on country dictionary
                    if ($id == 4)
					{
						print '<td align="center" class="nowrap">';
						if ($iserasable) print '<a href="'.$url.'action='.$acts[$obj->favorite].'_favorite">'.$actl[$obj->favorite].'</a>';
						else print $langs->trans("AlwaysActive");
						print '</td>';
					}

                    // Active
                    print '<td align="center" class="nowrap">';
                    print '<a href="'.$url.'action='.$acts[$obj->status].'">'.$actl[$obj->status].'</a>';
                    print "</td>";

                    // Modify link
                    if ($iserasable) print '<td align="center"><a class="reposition" href="'.$url.'action=edit">'.img_edit().'</a></td>';
                    else print '<td>&nbsp;</td>';

                    // Delete link
                    if ($iserasable) print '<td align="center"><a href="'.$url.'action=delete">'.img_delete().'</a></td>';
                    else print '<td>&nbsp;</td>';

                    print "</tr>\n";
                }
                $i++;
            }
        }
    }
    else {
        dol_print_error($db);
    }

    print '</table>';

    print '</form>';
}

print '<br>';


llxFooter();
$db->close();


/**
 *	Show fields in insert/edit mode
 *
 * 	@param		array	$fieldlist		Array of fields
 * 	@param		Object	$obj			If we show a particular record, obj is filled with record fields
 *  @param		string	$tabname		Name of SQL table
 *  @param		string	$context		'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we dont want it to be rendered
 *	@return		void
 */
function fieldList($fieldlist, $obj='', $tabname='', $context='')
{
	global $conf,$langs,$db;
	global $form;
	global $region_id;
	global $elementList,$sourceList,$localtax_typeList;
	global $bc;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);
	if (! empty($conf->accounting->enabled)) $formaccountancy = New FormVentilation($db);

	foreach ($fieldlist as $field => $value)
	{
		if ($fieldlist[$field] == 'country')
		{
			if (in_array('region_id',$fieldlist))
			{
				print '<td>';
				//print join(',',$fieldlist);
				print '</td>';
				continue;
			}	// For state page, we do not show the country input (we link to region, not country)
			print '<td>';
			$fieldname='country';
			print $form->select_country((! empty($obj->country_code)?$obj->country_code:(! empty($obj->country)?$obj->country:'')), $fieldname, '', 28, 'maxwidth300');
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'country_id')
		{
			if (! in_array('country',$fieldlist))	// If there is already a field country, we don't show country_id (avoid duplicate)
			{
				$country_id = (! empty($obj->$fieldlist[$field]) ? $obj->$fieldlist[$field] : 0);
				print '<td>';
				print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$country_id.'">';
				print '</td>';
			}
		}
		elseif ($fieldlist[$field] == 'region')
		{
			print '<td>';
			$formcompany->select_region($region_id,'region');
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'region_id')
		{
			$region_id = (! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:0);
			print '<td>';
			print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$region_id.'">';
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'lang')
		{
			print '<td>';
			print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT,'lang');
			print '</td>';
		}
		// Le type de template
		elseif ($fieldlist[$field] == 'type_template')
		{
			print '<td>';
			print $form->selectarray('type_template', $elementList,(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:''));
			print '</td>';
		}
		// Le type de l'element (pour les type de contact)
		elseif ($fieldlist[$field] == 'element')
		{
			print '<td>';
			print $form->selectarray('element', $elementList,(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:''));
			print '</td>';
		}
		// La source de l'element (pour les type de contact)
		elseif ($fieldlist[$field] == 'source')
		{
			print '<td>';
			print $form->selectarray('source', $sourceList,(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:''));
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'type' && $tabname == MAIN_DB_PREFIX."c_actioncomm")
		{
			print '<td>';
			print 'user<input type="hidden" name="type" value="user">';
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'recuperableonly' || $fieldlist[$field] == 'fdm' || $fieldlist[$field] == 'deductible') {
			print '<td>';
			print $form->selectyesno($fieldlist[$field],(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:''),1);
			print '</td>';
		}
		elseif (in_array($fieldlist[$field],array('nbjour','decalage','taux','localtax1','localtax2'))) {
			$align="left";
			if (in_array($fieldlist[$field],array('taux','localtax1','localtax2'))) $align="right";	// Fields aligned on right
			print '<td align="'.$align.'">';
			print '<input type="text" class="flat" value="'.(isset($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'" size="3" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
		elseif (in_array($fieldlist[$field], array('libelle_facture'))) {
			print '<td><textarea cols="30" rows="'.ROWS_2.'" class="flat" name="'.$fieldlist[$field].'">'.(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'</textarea></td>';
		}
		elseif (in_array($fieldlist[$field], array('content')))
		{
			if ($tabname == MAIN_DB_PREFIX.'c_email_templates')
			{
				print '<td colspan="4"></td></tr><tr class="pair nohover"><td colspan="5">';		// To create an artificial CR for the current tr we are on
			}
			else print '<td>';
			if ($context != 'hide')
			{
				//print '<textarea cols="3" rows="'.ROWS_2.'" class="flat" name="'.$fieldlist[$field].'">'.(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'</textarea>';
				$doleditor = new DolEditor($fieldlist[$field], (! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:''), '', 140, 'dolibarr_mailings', 'In', 0, false, true, ROWS_5, '90%');
				print $doleditor->Create(1);
			}
			else print '&nbsp;';
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'price' || preg_match('/^amount/i',$fieldlist[$field])) {
			print '<td><input type="text" class="flat" value="'.price((! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'')).'" size="8" name="'.$fieldlist[$field].'"></td>';
		}
		elseif ($fieldlist[$field] == 'code' && isset($obj->$fieldlist[$field])) {
			print '<td><input type="text" class="flat" value="'.(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'" size="10" name="'.$fieldlist[$field].'"></td>';
		}
		elseif ($fieldlist[$field]=='unit') {
			print '<td>';
			$units = array(
					'mm' => $langs->trans('SizeUnitmm'),
					'cm' => $langs->trans('SizeUnitcm'),
					'point' => $langs->trans('SizeUnitpoint'),
					'inch' => $langs->trans('SizeUnitinch')
			);
			print $form->selectarray('unit', $units, (! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:''), 0, 0, 0);
			print '</td>';
		}
		// Le type de taxe locale
		elseif ($fieldlist[$field] == 'localtax1_type' || $fieldlist[$field] == 'localtax2_type')
		{
			print '<td align="center">';
			print $form->selectarray($fieldlist[$field], $localtax_typeList, (! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:''));
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'accountancy_code' || $fieldlist[$field] == 'accountancy_code_sell' || $fieldlist[$field] == 'accountancy_code_buy')
		{
			print '<td>';
			if (! empty($conf->accounting->enabled))
			{
				$accountancy_account = (! empty($obj->$fieldlist[$field]) ? $obj->$fieldlist[$field] : 0);
				print $formaccountancy->select_account($accountancy_account, $fieldlist[$field], 1, '', 1, 1);
			}
			else
			{
				print '<input type="text" size="10" class="flat" value="'.(isset($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'" name="'.$fieldlist[$field].'">';
			}
			print '</td>';
		}
		else
		{
			print '<td>';
			$size='';
			if ($fieldlist[$field]=='code') $size='size="8" ';
			if ($fieldlist[$field]=='position') $size='size="4" ';
			if ($fieldlist[$field]=='libelle') $size='size="32" ';
			if ($fieldlist[$field]=='tracking') $size='size="92" ';
			if ($fieldlist[$field]=='sortorder') $size='size="2" ';
			print '<input type="text" '.$size.' class="flat" value="'.(isset($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
	}
}

