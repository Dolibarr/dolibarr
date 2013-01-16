<?php
/* Copyright (C) 2011-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/admin_extrafields.inc.php
 *  \brief			Code for actions on extrafields admin pages
 */

$maxsizestring=255;
$maxsizeint=10;

$extrasize=GETPOST('size');
if (GETPOST('type')=='double' && strpos($extrasize,',')===false) $extrasize='24,8';
if (GETPOST('type')=='date')     $extrasize='';
if (GETPOST('type')=='datetime') $extrasize='';


// Add attribute
if ($action == 'add')
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
            $action = 'create';
        }
        if (GETPOST('type')=='int' && $extrasize > $maxsizeint)
        {
            $error++;
            $langs->load("errors");
            $mesg=$langs->trans("ErrorSizeTooLongForIntType",$maxsizeint);
            $action = 'create';
        }

	    if (! $error)
	    {
    		// Type et taille non encore pris en compte => varchar(255)
    		if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_POST['attrname']))
    		{
                $result=$extrafields->addExtraField($_POST['attrname'],$_POST['label'],$_POST['type'],$_POST['pos'],$extrasize,$elementtype,(GETPOST('unique')?1:0),(GETPOST('required')?1:0));
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
    			$action = 'create';
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
    			$result=$extrafields->update($_POST['attrname'],$_POST['label'],$_POST['type'],$extrasize,$elementtype,(GETPOST('unique')?1:0),(GETPOST('required')?1:0));
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

?>