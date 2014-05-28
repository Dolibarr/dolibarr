<?php
/* Copyright (C) 2013-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
 *      \file       htdocs/admin/fiscalyear.php
 *		\ingroup    fiscal year
 *		\brief      Setup page to configure fiscal year
 */

require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fiscalyear.class.php';

$action=GETPOST('action');

$langs->load("admin");
$langs->load("compta");

if (! $user->admin) accessforbidden();

$error=0;

// List of statut
static $tmpstatut2label=array(
		'0'=>'OpenFiscalYear',
		'1'=>'CloseFiscalYear'
);
$statut2label=array('');
foreach ($tmpstatut2label as $key => $val) $statut2label[$key]=$langs->trans($val);

$mesg='';
$errors=array();

$object = new Fiscalyear($db);

/*
 * Actions
 */

// Add
if ($action == 'add')
{
    if (! GETPOST('cancel','alpha'))
    {
        $error=0;

        $object->label		= GETPOST('label','alpha');
        $object->datestart	= dol_mktime(12, 0, 0, GETPOST('startmonth','int'), GETPOST('startday','int'), GETPOST('startyear','int'));
        $object->dateend  	= dol_mktime(12, 0, 0, GETPOST('endmonth','int'), GETPOST('endday','int'), GETPOST('endyear','int'));
        $object->statut     = 0;

        if (! $object->label)
        {
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Label")).'</div>';
            $error++;
        }
        if (! $object->datestart)
        {
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateStart")).'</div>';
            $error++;
        }
        if (! $object->dateend)
        {
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateEnd")).'</div>';
            $error++;
        }

        if (! $error)
        {
            $id = $object->create();

            if ($id > 0)
            {
                header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
                exit;
            }
            else
            {
                $mesg=$object->error;
                $action='create';
            }
        }
        else
        {
            $action='create';
        }
    }
    else
    {
        header("Location: index.php");
        exit;
    }

    if (! GETPOST('cancel','alpha'))
    {
        $error=0;

      	// Check values
      	$datestart = dol_mktime(12, 0, 0, $_POST['startmonth'], $_POST['startday'], $_POST['startyear']);
        $dateend = dol_mktime(12, 0, 0, $_POST['endmonth'], $_POST['endday'], $_POST['endyear']);
        $label = $_POST['label'];

          if (empty($label))
          {
              $mesgs[]='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Label")).'</div>';
              $error++;
              //$action='create';
          }
          if (empty($datestart) || empty($dateend))
          {
              $mesgs[]='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
              $error++;
              //$action='create';
          }

    	    if (! $error)
    	    {
				$this->db->begin();

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."accounting_fiscalyear";
        		$sql.= " (label, datestart, dateend, statut, entity)";
        		$sql.= " VALUES('".$label."',";
        		$sql.= " '".$datestart."',";
        		$sql.= " '".$dateend."',";
        		$sql.= " ' 0,";
        		$sql.= " ".$conf->entity."'";
				$sql.=')';

        		dol_syslog(get_class($this)."::create_label sql=".$sql);
        		if ($this->db->query($sql))
        		{
        			return 1;
        		}
        		else
        		{
        			$this->error=$this->db->lasterror();
        			$this->errno=$this->db->lasterrno();
        			return -1;
        		}
    	    }
    }
}

// Rename field
if ($action == 'update')
{
	if ($_POST["button"] != $langs->trans("Cancel"))
	{
        // Check values
		if (! GETPOST('type'))
		{
			$error++;
			$langs->load("errors");
			$mesg=$langs->trans("ErrorFieldRequired",$langs->trans("Type"));
			$action = 'create';
		}
		if (GETPOST('type')=='varchar' && $extrasize > $maxsizestring)
        {
            $error++;
            $langs->load("errors");
            $mesg=$langs->trans("ErrorSizeTooLongForVarcharType",$maxsizestring);
            $action = 'edit';
        }
        if (GETPOST('type')=='int' && $extrasize > $maxsizeint)
        {
            $error++;
            $langs->load("errors");
            $mesg=$langs->trans("ErrorSizeTooLongForIntType",$maxsizeint);
            $action = 'edit';
        }

	    if (! $error)
	    {
            if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_POST['attrname']))
    		{
    			$result=$extrafields->update($_POST['attrname'],$_POST['label'],$_POST['type'],$extrasize,$elementtype,(GETPOST('unique')?1:0));
    			if ($result > 0)
    			{
    				header("Location: ".$_SERVER["PHP_SELF"]);
    				exit;
    			}
    			else
    			{
                    $error++;
    			    $mesg=$extrafields->error;
    			}
    		}
    		else
    		{
    		    $error++;
    			$langs->load("errors");
    			$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
    		}
	    }
	}
}

// Delete attribute
if ($action == 'delete')
{
	if(isset($_GET["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_GET["attrname"]))
	{
        $result=$extrafields->delete($_GET["attrname"],$elementtype);
        if ($result >= 0)
        {
            header("Location: ".$_SERVER["PHP_SELF"]);
            exit;
        }
        else $mesg=$extrafields->error;
	}
	else
	{
	    $error++;
		$langs->load("errors");
		$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
	}
}

/*
 * View
 */

$form = new Form($db);

llxHeader('',$title);

$title = $langs->trans('Accountancysetup');

print_fiche_titre($langs->trans('Fiscalyear'));

dol_htmloutput_errors($mesg);

$sql = "SELECT f.rowid, f.label, f.datestart, f.dateend, f.statut, f.entity";
$sql.= " FROM ".MAIN_DB_PREFIX."accounting_fiscalyear as f";
$sql.= " WHERE f.entity = ".$conf->entity;

$result = $db->query($sql);

$max=10;

if ($result)
{
	$var=false;
    $num = $db->num_rows($result);

    $i = 0;
	
	// Load attribute_label
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Ref").'</td>';
	print '<td>'.$langs->trans("Label").'</td>';
	print '<td>'.$langs->trans("DateStart").'</td>';
	print '<td>'.$langs->trans("DateEnd").'</td>';
	print '<td>'.$langs->trans("Statut").'</td>';
	print '</tr>';
	
	if ($num)
    {
	    $fiscalyearstatic=new Fiscalyear($db);
    
		while ($i < $num && $i < $max)
        {
            $obj = $db->fetch_object($result);
            $fiscalyearstatic->ref=$obj->rowid;
            $fiscalyearstatic->id=$obj->rowid;
            print '<tr '.$bc[$var].'>';
			print '<td><a href="fiscalyear_card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowFiscalYear"),"technic").' '.$obj->rowid.'</a></td>';
            print '<td align="left">'.$obj->label.'</td>';
            print '<td align="left">'.dol_print_date($db->jdate($obj->datestart),'day').'</td>';
			print '<td align="left">'.dol_print_date($db->jdate($obj->dateend),'day').'</td>';
            print '<td>'.$fiscalyearstatic->LibStatut($obj->statut,5).'</td>';
            print '</tr>';
            $var=!$var;
            $i++;
        }

    }
    else
    {
        print '<tr '.$bc[$var].'><td colspan="5">'.$langs->trans("None").'</td></tr>';
    }
	
	print '</table>';
	print '</form>';
} 
else
{
	dol_print_error($db);
}

dol_fiche_end();

// Buttons
print '<div class="tabsAction">';
print '<a class="butAction" href="fiscalyear_card.php?action=create">'.$langs->trans("NewFiscalYear").'</a>';
print '</div>';

llxFooter();
$db->close();