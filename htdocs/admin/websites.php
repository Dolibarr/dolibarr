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
$tabsql[1] = "SELECT f.rowid as rowid, f.entity, f.ref, f.description, f.status FROM ".MAIN_DB_PREFIX."website as f";

// Criteria to sort dictionaries
$tabsqlsort=array();
$tabsqlsort[1] ="ref ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield=array();
$tabfield[1] = "ref,description";

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue=array();
$tabfieldvalue[1] = "ref,description";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert=array();
$tabfieldinsert[1] = "ref,description,entity";

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
       		fieldListWebsites($fieldlist,$obj,$tabname[$id],'add');
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
                if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
                if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
                if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }

                // Affiche nom du champ
                if ($showfield)
                {
                    print getTitleFieldOfList($valuetoshow,0,$_SERVER["PHP_SELF"],($sortable?$fieldlist[$field]:''),($page?'page='.$page.'&':'').'&id='.$id,"","align=".$align,$sortfield,$sortorder);
                }
            }

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

                    if (empty($reshook)) fieldListWebsites($fieldlist,$obj,$tabname[$id],'edit');

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
                        	$fieldname=$fieldlist[$field];
                            $valuetoshow=$obj->$fieldname;

							// Show value for field
							if ($showfield) print '<td align="'.$align.'">'.$valuetoshow.'</td>';
                        }
                    }

                    // Can an entry be erased or disabled ?
                    $iserasable=1;$isdisable=1;	// true by default

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
function fieldListWebsites($fieldlist, $obj='', $tabname='', $context='')
{
	global $conf,$langs,$db;
	global $form;
	global $region_id;
	global $elementList,$sourceList,$localtax_typeList;
	global $bc;

	$formadmin = new FormAdmin($db);

	foreach ($fieldlist as $field => $value)
	{
	    $fieldname = $fieldlist[$field];
		if ($fieldlist[$field] == 'lang')
		{
			print '<td>';
			print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT,'lang');
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'code' && isset($obj->$fieldname)) {
			print '<td><input type="text" class="flat" value="'.(! empty($obj->$fieldname)?$obj->$fieldname:'').'" size="10" name="'.$fieldlist[$field].'"></td>';
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
			print '<input type="text" '.$size.' class="flat" value="'.(isset($obj->$fieldname)?$obj->$fieldname:'').'" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
	}
}

