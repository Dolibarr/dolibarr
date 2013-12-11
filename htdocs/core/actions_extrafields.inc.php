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
 *	\file			htdocs/core/actions_extrafields.inc.php
 *  \brief			Code for actions on extrafields admin pages
 */

$maxsizestring=255;
$maxsizeint=10;

$extrasize=GETPOST('size');
if (GETPOST('type')=='double' && strpos($extrasize,',')===false) $extrasize='24,8';
if (GETPOST('type')=='date')     $extrasize='';
if (GETPOST('type')=='datetime') $extrasize='';
if (GETPOST('type')=='select')    $extrasize='';


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
			$mesg[]=$langs->trans("ErrorFieldRequired",$langs->trans("Type"));
			$action = 'create';
		}

        if (GETPOST('type')=='varchar' && $extrasize > $maxsizestring)
        {
            $error++;
            $langs->load("errors");
            $mesg[]=$langs->trans("ErrorSizeTooLongForVarcharType",$maxsizestring);
            $action = 'create';
        }
        if (GETPOST('type')=='int' && $extrasize > $maxsizeint)
        {
            $error++;
            $langs->load("errors");
            $mesg[]=$langs->trans("ErrorSizeTooLongForIntType",$maxsizeint);
            $action = 'create';
        }
        if (GETPOST('type')=='select' && !GETPOST('param'))
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForSelectType");
        	$action = 'create';
        }
        if (GETPOST('type')=='sellist' && !GETPOST('param'))
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForSelectListType");
        	$action = 'create';
        }
        if (GETPOST('type')=='checkbox' && !GETPOST('param'))
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForCheckBoxType");
        	$action = 'create';
        }
        if (GETPOST('type')=='radio' && !GETPOST('param'))
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForRadioType");
        	$action = 'create';
        }
        if  (((GETPOST('type')=='radio') || (GETPOST('type')=='checkbox')) && GETPOST('param')) 
        {
        	// Construct array for parameter (value of select list)
    		$parameters = GETPOST('param');
    		$parameters_array = explode("\r\n",$parameters);
    		foreach($parameters_array as $param_ligne)
    		{
    			if (!empty($param_ligne)) {
	    			if (preg_match_all('/,/',$param_ligne,$matches)) 
	    			{
	    				if (count($matches[0])>1) {
	    					$error++;
	    					$langs->load("errors");
	    					$mesg[]=$langs->trans("ErrorBadFormatValueList",$param_ligne);
	    					$action = 'create';
	    				}
	    			}
	    			else 
	    			{
	    				$error++;
	    				$langs->load("errors");
	    				$mesg[]=$langs->trans("ErrorBadFormatValueList",$param_ligne);
	    				$action = 'create';
	    			}
    			}
    		}  	
        }

	    if (! $error)
	    {
    		// Type et taille non encore pris en compte => varchar(255)
    		if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_POST['attrname']))
    		{
    			// Construct array for parameter (value of select list)
        		$default_value = GETPOST('default_value');
    			$parameters = GETPOST('param');
    			$parameters_array = explode("\r\n",$parameters);
    			//In sellist we have only one line and it can have come to do SQL expression
    			if (GETPOST('type')=='sellist') {
    				foreach($parameters_array as $param_ligne)
    				{
    					$params['options'] = array($parameters=>null);
    				}
    			}
    			else
    			{
    				//Esle it's separated key/value and coma list
    				foreach($parameters_array as $param_ligne)
    				{
    					list($key,$value) = explode(',',$param_ligne);
    					$params['options'][$key] = $value;
    				}
    			}		 
    			
                $result=$extrafields->addExtraField($_POST['attrname'],$_POST['label'],$_POST['type'],$_POST['pos'],$extrasize,$elementtype,(GETPOST('unique')?1:0),(GETPOST('required')?1:0),$default_value,$params);
    			if ($result > 0)
    			{
    				setEventMessage($langs->trans('SetupSaved'));
    				header("Location: ".$_SERVER["PHP_SELF"]);
    				exit;
    			}
    			else
    			{
                    $error++;
    			    $mesg=$extrafields->error;
                    setEventMessage($mesg,'errors');
    			}
    		}
    		else
    		{
                $error++;
    		    $langs->load("errors");
    			$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
    			setEventMessage($mesg,'errors');
    			$action = 'create';
    		}
	    }
	    else 
	    {
	    	setEventMessage($mesg,'errors');
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
			$mesg[]=$langs->trans("ErrorFieldRequired",$langs->trans("Type"));
			$action = 'create';
		}
		if (GETPOST('type')=='varchar' && $extrasize > $maxsizestring)
        {
            $error++;
            $langs->load("errors");
            $mesg[]=$langs->trans("ErrorSizeTooLongForVarcharType",$maxsizestring);
            $action = 'edit';
        }
        if (GETPOST('type')=='int' && $extrasize > $maxsizeint)
        {
            $error++;
            $langs->load("errors");
            $mesg[]=$langs->trans("ErrorSizeTooLongForIntType",$maxsizeint);
            $action = 'edit';
        }
        if (GETPOST('type')=='select' && !GETPOST('param'))
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForSelectType");
        	$action = 'edit';
        }
        if (GETPOST('type')=='sellist' && !GETPOST('param'))
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForSelectListType");
        	$action = 'edit';
        }
        if (GETPOST('type')=='checkbox' && !GETPOST('param'))
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForCheckBoxType");
        	$action = 'edit';
        }
        if (GETPOST('type')=='radio' && !GETPOST('param'))
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForRadioType");
        	$action = 'edit';
        }
        if  (((GETPOST('type')=='radio') || (GETPOST('type')=='checkbox')) && GETPOST('param'))
        {
        	// Construct array for parameter (value of select list)
        	$parameters = GETPOST('param');
        	$parameters_array = explode("\r\n",$parameters);
        	foreach($parameters_array as $param_ligne)
        	{
        		if (!empty($param_ligne)) {
	        		if (preg_match_all('/,/',$param_ligne,$matches))
	        		{
	        			if (count($matches[0])>1) {
	        				$error++;
	        				$langs->load("errors");
	        				$mesg[]=$langs->trans("ErrorBadFormatValueList",$param_ligne);
	        				$action = 'edit';
	        			}
	        		}
	        		else
	        		{
	        			$error++;
	        			$langs->load("errors");
	        			$mesg[]=$langs->trans("ErrorBadFormatValueList",$param_ligne);
	        			$action = 'edit';
	        		}
        		}
        	}
        }

	    if (! $error)
	    {
            if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_POST['attrname']))
    		{
    			$pos = GETPOST('pos','int');
    			// Construct array for parameter (value of select list)
    			$parameters = GETPOST('param');
    			$parameters_array = explode("\r\n",$parameters);
    			//In sellist we have only one line and it can have come to do SQL expression
    			if (GETPOST('type')=='sellist') {
    				foreach($parameters_array as $param_ligne)
    				{
    					$params['options'] = array($parameters=>null);
    				}
    			}
    			else
    			{
    				//Esle it's separated key/value and coma list
    				foreach($parameters_array as $param_ligne)
    				{
    					list($key,$value) = explode(',',$param_ligne);
    					$params['options'][$key] = $value;
    				}
    			}
    			$result=$extrafields->update($_POST['attrname'],$_POST['label'],$_POST['type'],$extrasize,$elementtype,(GETPOST('unique')?1:0),(GETPOST('required')?1:0),$pos,$params);
    			if ($result > 0)
    			{
    				setEventMessage($langs->trans('SetupSaved'));
    				header("Location: ".$_SERVER["PHP_SELF"]);
    				exit;
    			}
    			else
    			{
                    $error++;
    			    $mesg=$extrafields->error;
    			    setEventMessage($mesg,'errors');
    			}
    		}
    		else
    		{
    		    $error++;
    			$langs->load("errors");
    			$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
    			setEventMessage($mesg,'errors');
    		}
	    }
	    else
	    {
	    	setEventMessage($mesg,'errors');
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