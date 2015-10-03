<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2015 Juanjo Menent		<jmenent@2byte.es>
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
 *		\file       htdocs/admin/security.php
 *      \ingroup    setup
 *      \brief      Page de configuration du module securite
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

$action=GETPOST('action');

$langs->load("users");
$langs->load("admin");
$langs->load("other");

if (!$user->admin) accessforbidden();

// Allow/Disallow change to clear passwords once passwords are crypted
$allow_disable_encryption=true;

/*
 * Actions
 */
if ($action == 'setgeneraterule')
{
	if (! dolibarr_set_const($db, 'USER_PASSWORD_GENERATED',$_GET["value"],'chaine',0,'',$conf->entity))
	{
		dol_print_error($db);
	}
	else
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'activate_encrypt')
{
    $error=0;

	$db->begin();

    dolibarr_set_const($db, "DATABASE_PWD_ENCRYPTED", "1",'chaine',0,'',$conf->entity);

    $sql = "SELECT u.rowid, u.pass, u.pass_crypted";
    $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql.= " WHERE u.pass IS NOT NULL AND LENGTH(u.pass) < 32"; // Not a MD5 value

    $resql=$db->query($sql);
    if ($resql)
    {
        $numrows=$db->num_rows($resql);
        $i=0;
        while ($i < $numrows)
        {
            $obj=$db->fetch_object($resql);
            if (dol_hash($obj->pass))
            {
                $sql = "UPDATE ".MAIN_DB_PREFIX."user";
                $sql.= " SET pass_crypted = '".dol_hash($obj->pass)."', pass = NULL";
                $sql.= " WHERE rowid=".$obj->rowid;
                //print $sql;

                $resql2 = $db->query($sql);
                if (! $resql2)
                {
                    dol_print_error($db);
                    $error++;
                    break;
                }

                $i++;
            }
        }
    }
    else dol_print_error($db);

	//print $error." ".$sql;
    //exit;
    if (! $error)
	{
		$db->commit();
		header("Location: security.php");
	    exit;
	}
	else
	{
		$db->rollback();
		dol_print_error($db,'');
	}
}
else if ($action == 'disable_encrypt')
{
	//On n'autorise pas l'annulation de l'encryption car les mots de passe ne peuvent pas etre decodes
	//Do not allow "disable encryption" as passwords cannot be decrypted
	if ($allow_disable_encryption)
	{
		dolibarr_del_const($db, "DATABASE_PWD_ENCRYPTED",$conf->entity);
    }
	header("Location: security.php");
    exit;
}

if ($action == 'activate_encryptdbpassconf')
{
	$result = encodedecode_dbpassconf(1);
	if ($result > 0)
	{
	    sleep(3);  // Don't know why but we need to wait file is completely saved before making the reload. Even with flush and clearstatcache, we need to wait.
	    
	    // database value not required
		//dolibarr_set_const($db, "MAIN_DATABASE_PWD_CONFIG_ENCRYPTED", "1");
		header("Location: security.php");
		exit;
	}
	else
	{
		setEventMessage($langs->trans('InstrucToEncodePass',dol_encode($dolibarr_main_db_pass)),'warnings');	
	}
}
else if ($action == 'disable_encryptdbpassconf')
{
	$result = encodedecode_dbpassconf(0);
	if ($result > 0)
	{
	    sleep(3);  // Don't know why but we need to wait file is completely saved before making the reload. Even with flush and clearstatcache, we need to wait.
	    
		// database value not required
		//dolibarr_del_const($db, "MAIN_DATABASE_PWD_CONFIG_ENCRYPTED",$conf->entity);
		header("Location: security.php");
		exit;
	}
	else
	{
		setEventMessage($langs->trans('InstrucToClearPass',$dolibarr_main_db_pass),'warnings');
	}
}

if ($action == 'activate_MAIN_SECURITY_DISABLEFORGETPASSLINK')
{
	dolibarr_set_const($db, "MAIN_SECURITY_DISABLEFORGETPASSLINK", '1','chaine',0,'',$conf->entity);
	header("Location: security.php");
	exit;
}
else if ($action == 'disable_MAIN_SECURITY_DISABLEFORGETPASSLINK')
{
	dolibarr_del_const($db, "MAIN_SECURITY_DISABLEFORGETPASSLINK",$conf->entity);
	header("Location: security.php");
	exit;
}

if ($action == 'maj_pattern')
{
	dolibarr_set_const($db, "USER_PASSWORD_PATTERN", GETPOST("pattern"),'chaine',0,'',$conf->entity);
	header("Location: security.php");
	exit;
}







/*
 * View
 */
$form = new Form($db);

llxHeader('',$langs->trans("Passwords"));

print load_fiche_titre($langs->trans("SecuritySetup"),'','title_setup');

print $langs->trans("GeneratedPasswordDesc")."<br>\n";
print "<br>\n";


$head=security_prepare_head();

dol_fiche_head($head, 'passwords', $langs->trans("Security"));


$var=false;


// Choix du gestionnaire du generateur de mot de passe
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="USER_PASSWORD_GENERATED">';
print '<input type="hidden" name="consttype" value="yesno">';

// Charge tableau des modules generation
$dir = "../core/modules/security/generate";
clearstatcache();
$handle=opendir($dir);
$i=1;
if (is_resource($handle))
{
    while (($file = readdir($handle))!==false)
    {
        if (preg_match('/(modGeneratePass[a-z]+)\.class\.php/i',$file,$reg))
        {
            // Chargement de la classe de numerotation
            $classname = $reg[1];
            require_once $dir.'/'.$file;

            $obj = new $classname($db,$conf,$langs,$user);
            $arrayhandler[$obj->id]=$obj;
    		$i++;
        }
    }
    closedir($handle);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("RuleForGeneratedPasswords").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print '</tr>';

foreach ($arrayhandler as $key => $module)
{
	// Show modules according to features level
    if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

	if ($module->isEnabled())
	{
        $var = !$var;
        print '<tr '.$bc[$var].'><td width="100">';
        print ucfirst($key);
        print "</td><td>\n";
        print $module->getDescription().'<br>';
        print $langs->trans("MinLength").': '.$module->length;
        print '</td>';

        // Show example of numbering module
        print '<td class="nowrap">';
        $tmp=$module->getExample();
        if (preg_match('/^Error/',$tmp)) { $langs->load("errors"); print '<div class="error">'.$langs->trans($tmp).'</div>'; }
        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
        else print $tmp;
        print '</td>'."\n";

        print '<td width="100" align="center">';
        if ($conf->global->USER_PASSWORD_GENERATED == $key)
        {
            print img_picto('','tick');
        }
        else
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=setgeneraterule&amp;value='.$key.'">'.$langs->trans("Activate").'</a>';
        }
        print "</td></tr>\n";
	}
}
print '</table>';
print '</form>';

//if($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK == 1)
// Patter for Password Perso
if ($conf->global->USER_PASSWORD_GENERATED == "Perso"){
$var=!$var;

	$tabConf = explode(";",$conf->global->USER_PASSWORD_PATTERN);
	/*$this->length2 = $tabConf[0];
	$this->NbMaj = $tabConf[1];
	$this->NbNum = $tabConf[2];
	$this->NbSpe = $tabConf[3];
	$this->NbRepeat = $tabConf[4];
	$this->WithoutAmbi = $tabConf[5];
	*/
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="3"> '.$langs->trans("PasswordPatternDesc").'</td>';
	print '</tr>';

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>' . $langs->trans("MinLength")."</td>";
	print '<td colspan="2"><input type="number" value="'.$tabConf[0].'" id="minlenght" min="1"></td>';
	print '</tr>';

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>' . $langs->trans("NbMajMin")."</td>";
	print '<td colspan="2"><input type="number" value="'.$tabConf[1].'" id="NbMajMin" min="0"></td>';
	print '</tr>';

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>' . $langs->trans("NbNumMin")."</td>";
	print '<td colspan="2"><input type="number" value="'.$tabConf[2].'" id="NbNumMin" min="0"></td>';
	print '</tr>';

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>' . $langs->trans("NbSpeMin")."</td>";
	print '<td colspan="2"><input type="number" value="'.$tabConf[3].'" id="NbSpeMin" min="0"></td>';
	print '</tr>';

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>' . $langs->trans("NbIteConsecutive")."</td>";
	print '<td colspan="2"><input type="number" value="'.$tabConf[4].'" id="NbIteConsecutive" min="0"></td>';
	print '</tr>';

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>' . $langs->trans("NoAmbiCaracAutoGeneration")."</td>";
	print '<td colspan="2"><input type="checkbox" id="NoAmbiCaracAutoGeneration" '.($tabConf[5] ? "checked" : "").' min="0"> <span id="textcheckbox">'.($tabConf[5] ? $langs->trans("Activated") : $langs->trans("Disabled")).'</span></td>';
	print '</tr>';

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td colspan="2"></td><td width="103" align="center"><a id="linkChangePattern">'.$langs->trans("Save").'</a></td>';
	print '</tr>';
	print '</table>';

	print '<script type="text/javascript">';
	print '	function getStringArg(){';
	print '		var pattern = "";';
	print '		pattern += $("#minlenght").val() + ";";';
	print '		pattern += $("#NbMajMin").val() + ";";';
	print '		pattern += $("#NbNumMin").val() + ";";';
	print '		pattern += $("#NbSpeMin").val() + ";";';
	print '		pattern += $("#NbIteConsecutive").val() + ";";';
	print '		pattern += $("#NoAmbiCaracAutoGeneration")[0].checked ? "1" : "0";';
	print '		return pattern;';
	print '	}';

	print '	function valuePossible(){';
	print '		var length = parseInt($("#minlenght").val());';
	print '		var length_mini = parseInt($("#NbMajMin").val()) + parseInt($("#NbNumMin").val()) + parseInt($("#NbSpeMin").val());';
	print '		return length >= length_mini;';
	print '	}';

	print '	function generatelink(){';
	print '		return "security.php?action=maj_pattern&pattern="+getStringArg();';
	print '	}';

	print '	function valuePatternChange(){';
	print '		var lang_save = "'.$langs->trans("Save").'";';
	print '		var lang_error = "'.$langs->trans("Error").'";';
	print '		var lang_Disabled = "'.$langs->trans("Disabled").'";';
	print '		var lang_Activated = "'.$langs->trans("Activated").'";';
	print '		$("#textcheckbox").html($("#NoAmbiCaracAutoGeneration")[0].checked ? unescape(lang_Activated) : unescape(lang_Disabled));';
	print '		if(valuePossible()){';
	print '			$("#linkChangePattern").attr("href",generatelink()).text(lang_save);';
	print '		}';
	print '		else{';
	print '			$("#linkChangePattern").attr("href", null).text(lang_error);';
	print '		}';
	print '	}';

	print '	$("#minlenght").change(function(){valuePatternChange();});';
	print '	$("#NbMajMin").change(function(){valuePatternChange();});';
	print '	$("#NbNumMin").change(function(){valuePatternChange();});';
	print '	$("#NbSpeMin").change(function(){valuePatternChange();});';
	print '	$("#NbIteConsecutive").change(function(){valuePatternChange();});';
	print '	$("#NoAmbiCaracAutoGeneration").change(function(){valuePatternChange();});';

	print '</script>';
}


// Cryptage mot de passe
print '<br>';
$var=true;
print "<form method=\"post\" action=\"security.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"encrypt\">";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print '</tr>';

// Disable clear password in database
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("DoNotStoreClearPassword").'</td>';
print '<td align="center" width="60">';
if (! empty($conf->global->DATABASE_PWD_ENCRYPTED))
{
	print img_picto($langs->trans("Active"),'tick');
}
print '</td>';
if (! $conf->global->DATABASE_PWD_ENCRYPTED)
{
	print '<td align="center" width="100">';
	print '<a href="security.php?action=activate_encrypt">'.$langs->trans("Activate").'</a>';
	print "</td>";
}
if (! empty($conf->global->DATABASE_PWD_ENCRYPTED))
{
	print '<td align="center" width="100">';
	if ($allow_disable_encryption)
	{
		//On n'autorise pas l'annulation de l'encryption car les mots de passe ne peuvent pas etre decodes
	  	//Do not allow "disable encryption" as passwords cannot be decrypted
	  	print '<a href="security.php?action=disable_encrypt">'.$langs->trans("Disable").'</a>';
	}
	else
	{
		print '-';
	}
	print "</td>";
}
print "</td>";
print '</tr>';

// Cryptage du mot de base de la base dans conf.php
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("MainDbPasswordFileConfEncrypted").'</td>';
print '<td align="center" width="60">';
if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
{
	print img_picto($langs->trans("Active"),'tick');
}

print '</td>';

print '<td align="center" width="100">';
if (empty($dolibarr_main_db_pass) && empty($dolibarr_main_db_encrypted_pass))
{
	$langs->load("errors");
	print img_warning($langs->trans("WarningPassIsEmpty"));
}
else
{
	if (empty($dolibarr_main_db_encrypted_pass))
	{
		print '<a href="security.php?action=activate_encryptdbpassconf">'.$langs->trans("Activate").'</a>';
	}
	if (! empty($dolibarr_main_db_encrypted_pass))
	{
		print '<a href="security.php?action=disable_encryptdbpassconf">'.$langs->trans("Disable").'</a>';
	}
}
print "</td>";

print "</td>";
print '</tr>';


// Disable link "Forget password" on logon
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("DisableForgetPasswordLinkOnLogonPage").'</td>';
print '<td align="center" width="60">';
if(! empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
{
	print img_picto($langs->trans("Active"),'tick');
}
print '</td>';
if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
{
	print '<td align="center" width="100">';
	print '<a href="security.php?action=activate_MAIN_SECURITY_DISABLEFORGETPASSLINK">'.$langs->trans("Activate").'</a>';
	print "</td>";
}
if (!empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
{
	print '<td align="center" width="100">';
	print '<a href="security.php?action=disable_MAIN_SECURITY_DISABLEFORGETPASSLINK">'.$langs->trans("Disable").'</a>';
	print "</td>";
}
print "</td>";
print '</tr>';


print '</table>';
print '</form>';


//print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

print '</div>';


llxFooter();

$db->close();
