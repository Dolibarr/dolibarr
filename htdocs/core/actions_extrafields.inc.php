<?php
/* Copyright (C) 2011-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 *
 * $elementype must be defined.
 */

/**
 *	\file			htdocs/core/actions_extrafields.inc.php
 *  \brief			Code for actions on extrafields admin pages
 */

$maxsizestring=255;
$maxsizeint=10;
$mesg=array();

$extrasize=GETPOST('size', 'intcomma');
$type=GETPOST('type', 'alpha');
$param=GETPOST('param', 'alpha');

if ($type=='double' && strpos($extrasize, ',')===false) $extrasize='24,8';
if ($type=='date')     $extrasize='';
if ($type=='datetime') $extrasize='';
if ($type=='select')   $extrasize='';


// Add attribute
if ($action == 'add')
{
	if ($_POST["button"] != $langs->trans("Cancel"))
	{
	    // Check values
		if (! $type)
		{
			$error++;
			$langs->load("errors");
			$mesg[]=$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"));
			$action = 'create';
		}
		if ($type=='varchar' && $extrasize <= 0)
		{
		    $error++;
		    $langs->load("errors");
		    $mesg[]=$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Size"));
		    $action = 'edit';
		}
        if ($type=='varchar' && $extrasize > $maxsizestring)
        {
            $error++;
            $langs->load("errors");
            $mesg[]=$langs->trans("ErrorSizeTooLongForVarcharType", $maxsizestring);
            $action = 'create';
        }
        if ($type=='int' && $extrasize > $maxsizeint)
        {
            $error++;
            $langs->load("errors");
            $mesg[]=$langs->trans("ErrorSizeTooLongForIntType", $maxsizeint);
            $action = 'create';
        }
        if ($type=='select' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForSelectType");
        	$action = 'create';
        }
        if ($type=='sellist' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForSelectListType");
        	$action = 'create';
        }
        if ($type=='checkbox' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForCheckBoxType");
        	$action = 'create';
        }
        if ($type=='link' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForLinkType");
        	$action = 'create';
        }
        if ($type=='radio' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForRadioType");
        	$action = 'create';
        }
        if  ((($type=='radio') || ($type=='checkbox')) && $param)
        {
        	// Construct array for parameter (value of select list)
    		$parameters = $param;
    		$parameters_array = explode("\r\n", $parameters);
    		foreach($parameters_array as $param_ligne)
    		{
    			if (!empty($param_ligne)) {
	    			if (preg_match_all('/,/', $param_ligne, $matches))
	    			{
	    				if (count($matches[0])>1) {
	    					$error++;
	    					$langs->load("errors");
	    					$mesg[]=$langs->trans("ErrorBadFormatValueList", $param_ligne);
	    					$action = 'create';
	    				}
	    			}
	    			else
	    			{
	    				$error++;
	    				$langs->load("errors");
	    				$mesg[]=$langs->trans("ErrorBadFormatValueList", $param_ligne);
	    				$action = 'create';
	    			}
    			}
    		}
        }

	    if (! $error)
	    {
    		// attrname must be alphabetical and lower case only
    		if (isset($_POST["attrname"]) && preg_match("/^[a-z0-9-_]+$/", $_POST['attrname']) && !is_numeric($_POST["attrname"]))
    		{
    			// Construct array for parameter (value of select list)
        		$default_value = GETPOST('default_value', 'alpha');
    			$parameters = $param;
    			$parameters_array = explode("\r\n", $parameters);
    			//In sellist we have only one line and it can have come to do SQL expression
    			if ($type=='sellist') {
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
    					list($key,$value) = explode(',', $param_ligne);
    					$params['options'][$key] = $value;
    				}
    			}

    			// Visibility: -1=not visible by default in list, 1=visible, 0=hidden
    			$visibility = GETPOST('list', 'alpha');
				if ($type == 'separate') $visibility=3;

                $result=$extrafields->addExtraField(
                	GETPOST('attrname', 'alpha'),
                	GETPOST('label', 'alpha'),
                	$type,
                	GETPOST('pos', 'int'),
                	$extrasize,
                	$elementtype,
                	(GETPOST('unique', 'alpha')?1:0),
                	(GETPOST('required', 'alpha')?1:0),
                	$default_value,
                	$params,
                	(GETPOST('alwayseditable', 'alpha')?1:0),
                	(GETPOST('perms', 'alpha')?GETPOST('perms', 'alpha'):''),
                	$visibility,
					GETPOST('help', 'alpha'),
                    GETPOST('computed_value', 'alpha'),
                	(GETPOST('entitycurrentorall', 'alpha')?0:''),
                    GETPOST('langfile', 'alpha'),
                    1,
                    (GETPOST('totalizable', 'alpha')?1:0)
                );
    			if ($result > 0)
    			{
    				setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    				header("Location: ".$_SERVER["PHP_SELF"]);
    				exit;
    			}
    			else
    			{
                    $error++;
    			    $mesg=$extrafields->error;
                    setEventMessages($mesg, null, 'errors');
    			}
    		}
    		else
    		{
                $error++;
    		    $langs->load("errors");
    			$mesg=$langs->trans("ErrorFieldCanNotContainSpecialNorUpperCharacters", $langs->transnoentities("AttributeCode"));
    			setEventMessages($mesg, null, 'errors');
    			$action = 'create';
    		}
	    }
	    else
	    {
	    	setEventMessages($mesg, null, 'errors');
	    }
	}
}

// Rename field
if ($action == 'update')
{
	if ($_POST["button"] != $langs->trans("Cancel"))
	{
        // Check values
		if (! $type)
		{
			$error++;
			$langs->load("errors");
			$mesg[]=$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"));
			$action = 'edit';
		}
		if ($type=='varchar' && $extrasize <= 0)
		{
		    $error++;
		    $langs->load("errors");
		    $mesg[]=$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Size"));
		    $action = 'edit';
		}
		if ($type=='varchar' && $extrasize > $maxsizestring)
        {
            $error++;
            $langs->load("errors");
            $mesg[]=$langs->trans("ErrorSizeTooLongForVarcharType", $maxsizestring);
            $action = 'edit';
        }
        if ($type=='int' && $extrasize > $maxsizeint)
        {
            $error++;
            $langs->load("errors");
            $mesg[]=$langs->trans("ErrorSizeTooLongForIntType", $maxsizeint);
            $action = 'edit';
        }
        if ($type=='select' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForSelectType");
        	$action = 'edit';
        }
        if ($type=='sellist' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForSelectListType");
        	$action = 'edit';
        }
        if ($type=='checkbox' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForCheckBoxType");
        	$action = 'edit';
        }
        if ($type=='radio' && !$param)
        {
        	$error++;
        	$langs->load("errors");
        	$mesg[]=$langs->trans("ErrorNoValueForRadioType");
        	$action = 'edit';
        }
        if  ((($type=='radio') || ($type=='checkbox')) && $param)
        {
        	// Construct array for parameter (value of select list)
        	$parameters = $param;
        	$parameters_array = explode("\r\n", $parameters);
        	foreach($parameters_array as $param_ligne)
        	{
        		if (!empty($param_ligne)) {
	        		if (preg_match_all('/,/', $param_ligne, $matches))
	        		{
	        			if (count($matches[0])>1) {
	        				$error++;
	        				$langs->load("errors");
	        				$mesg[]=$langs->trans("ErrorBadFormatValueList", $param_ligne);
	        				$action = 'edit';
	        			}
	        		}
	        		else
	        		{
	        			$error++;
	        			$langs->load("errors");
	        			$mesg[]=$langs->trans("ErrorBadFormatValueList", $param_ligne);
	        			$action = 'edit';
	        		}
        		}
        	}
        }

	    if (! $error)
	    {
            if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/", $_POST['attrname']))
    		{
    			$pos = GETPOST('pos', 'int');
    			// Construct array for parameter (value of select list)
    			$parameters = $param;
    			$parameters_array = explode("\r\n", $parameters);
    			//In sellist we have only one line and it can have come to do SQL expression
    			if ($type=='sellist') {
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
    					list($key,$value) = explode(',', $param_ligne);
    					$params['options'][$key] = $value;
    				}
    			}

    			// Visibility: -1=not visible by default in list, 1=visible, 0=hidden
    			$visibility = GETPOST('list', 'alpha');
    			if ($type == 'separate') $visibility=3;

                $result=$extrafields->update(
    				GETPOST('attrname', 'alpha'),
    				GETPOST('label', 'alpha'),
    				$type,
    				$extrasize,
    				$elementtype,
    				(GETPOST('unique', 'alpha')?1:0),
    				(GETPOST('required', 'alpha')?1:0),
    				$pos,
    				$params,
    				(GETPOST('alwayseditable', 'alpha')?1:0),
    				(GETPOST('perms', 'alpha')?GETPOST('perms', 'alpha'):''),
                	$visibility,
					GETPOST('help', 'alpha'),
    			    GETPOST('default_value', 'alpha'),
    				GETPOST('computed_value', 'alpha'),
    				(GETPOST('entitycurrentorall', 'alpha')?0:''),
                    GETPOST('langfile'),
                    1,
                    (GETPOST('totalizable', 'alpha')?1:0)
    			);
    			if ($result > 0)
    			{
    				setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    				header("Location: ".$_SERVER["PHP_SELF"]);
    				exit;
    			}
    			else
    			{
                    $error++;
    			    $mesg=$extrafields->error;
    			    setEventMessages($mesg, null, 'errors');
    			}
    		}
    		else
    		{
    		    $error++;
    			$langs->load("errors");
    			$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("AttributeCode"));
    			setEventMessages($mesg, null, 'errors');
    		}
	    }
	    else
	    {
	    	setEventMessages($mesg, null, 'errors');
	    }
	}
}

// Delete attribute
if ($action == 'delete')
{
	if(isset($_GET["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/", $_GET["attrname"]))
	{
        $result=$extrafields->delete($_GET["attrname"], $elementtype);
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
		$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("AttributeCode"));
	}
}
