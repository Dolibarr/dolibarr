<?php
/* Copyright (C) 2008-2011  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012  Regis Houssin               <regis.houssin@capnetworks.com>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2014-2016  Marcos García               <marcosgdf@gmail.com>
 * Copyright (C) 2015       Ferran Marcet               <fmarcet@2byte.es>
 * Copyright (C) 2015-2016  Raphaël Doursenaud          <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Juanjo Menent               <jmenent@2byte.es>
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
 *	\file			htdocs/core/lib/functions2.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all rare functions.
 */

// Enable this line to trace path when function is called.
//print xdebug_print_function_stack('Functions2.lib was called');exit;

/**
 * Same function than javascript unescape() function but in PHP.
 *
 * @param 	string	$source		String to decode
 * @return	string				Unescaped string
 */
function jsUnEscape($source)
{
    $decodedStr = "";
    $pos = 0;
    $len = strlen($source);
    while ($pos < $len) {
        $charAt = substr($source, $pos, 1);
        if ($charAt == '%') {
            $pos++;
            $charAt = substr($source, $pos, 1);
            if ($charAt == 'u') {
                // we got a unicode character
                $pos++;
                $unicodeHexVal = substr($source, $pos, 4);
                $unicode = hexdec($unicodeHexVal);
                $entity = "&#". $unicode . ';';
                $decodedStr .= utf8_encode($entity);
                $pos += 4;
            }
            else {
                // we have an escaped ascii character
                $hexVal = substr($source, $pos, 2);
                $decodedStr .= chr(hexdec($hexVal));
                $pos += 2;
            }
        } else {
            $decodedStr .= $charAt;
            $pos++;
        }
    }
    return dol_html_entity_decode($decodedStr, ENT_COMPAT);
}


/**
 * Return list of modules directories. We detect directories that contains a subdirectory /core/modules
 * We discard directory modules that contains 'disabled' into their name.
 *
 * @param	string	$subdir		Sub directory (Example: '/mailings')
 * @return	array				Array of directories that can contains module descriptors
 */
function dolGetModulesDirs($subdir='')
{
    global $conf;

    $modulesdir=array();

    foreach ($conf->file->dol_document_root as $type => $dirroot)
    {
        // Default core/modules dir
        if ($type === 'main') {
            $modulesdir[$dirroot . '/core/modules' . $subdir . '/'] = $dirroot . '/core/modules' . $subdir . '/';
        }

        // Scan dir from external modules
        $handle=@opendir($dirroot);
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                if (preg_match('/disabled/',$file)) continue;   // We discard module if it contains disabled into name.

                if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
                {
                    if (is_dir($dirroot . '/' . $file . '/core/modules'.$subdir.'/'))
                    {
                        $modulesdir[$dirroot . '/' . $file . '/core/modules'.$subdir.'/'] = $dirroot . '/' . $file . '/core/modules'.$subdir.'/';
                    }
                }
            }
            closedir($handle);
        }
    }
    return $modulesdir;
}


/**
 *  Try to guess default paper format according to language into $langs
 *
 *	@param		Translate	$outputlangs		Output lang to use to autodetect output format if setup not done
 *	@return		string							Default paper format code
 */
function dol_getDefaultFormat(Translate $outputlangs = null)
{
    global $langs;

    $selected='EUA4';
    if (!$outputlangs) {
    	$outputlangs=$langs;
    }

    if ($outputlangs->defaultlang == 'ca_CA') $selected='CAP4';        // Canada
    if ($outputlangs->defaultlang == 'en_US') $selected='USLetter';    // US
    return $selected;
}

/**
 *  Output content of a file $filename in version of current language (otherwise may use an alternate language)
 *
 *  @param	Translate	$langs          Object language to use for output
 *  @param  string		$filename       Relative filename to output
 *  @param  int			$searchalt      1=Search also in alternative languages
 *	@return	boolean						true if OK, false if KO
 */
function dol_print_file($langs,$filename,$searchalt=0)
{
    global $conf;

    // Test if file is in lang directory
    foreach($langs->dir as $searchdir)
    {
        $formfile=($searchdir."/langs/".$langs->defaultlang."/".$filename);
        dol_syslog('functions2::dol_print_file search file '.$formfile, LOG_DEBUG);
        if (is_readable($formfile))
        {
            $content=file_get_contents($formfile);
            $isutf8=utf8_check($content);
            if (! $isutf8 && $conf->file->character_set_client == 'UTF-8') print utf8_encode($content);
            elseif ($isutf8 && $conf->file->character_set_client == 'ISO-8859-1') print utf8_decode($content);
            else print $content;
            return true;
        }
        else dol_syslog('functions2::dol_print_file not found', LOG_DEBUG);

        if ($searchalt) {
            // Test si fichier dans repertoire de la langue alternative
            if ($langs->defaultlang != "en_US") $formfilealt = $searchdir."/langs/en_US/".$filename;
            else $formfilealt = $searchdir."/langs/fr_FR/".$filename;
            dol_syslog('functions2::dol_print_file search alt file '.$formfilealt, LOG_DEBUG);
            //print 'getcwd='.getcwd().' htmlfilealt='.$formfilealt.' X '.file_exists(getcwd().'/'.$formfilealt);
            if (is_readable($formfilealt))
            {
                $content=file_get_contents($formfilealt);
                $isutf8=utf8_check($content);
                if (! $isutf8 && $conf->file->character_set_client == 'UTF-8') print utf8_encode($content);
                elseif ($isutf8 && $conf->file->character_set_client == 'ISO-8859-1') print utf8_decode($content);
                else print $content;
                return true;
            }
            else dol_syslog('functions2::dol_print_file not found', LOG_DEBUG);
        }
    }

    return false;
}

/**
 *	Show informations on an object
 *  TODO Move this into html.formother
 *
 *	@param	object	$object			Objet to show
 *  @param  int     $usetable       Output into a table
 *	@return	void
 */
function dol_print_object_info($object, $usetable=0)
{
    global $langs,$db;
    $langs->load("other");
    $langs->load("admin");

    include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

    $deltadateforserver=getServerTimeZoneInt('now');
    $deltadateforclient=((int) $_SESSION['dol_tz'] + (int) $_SESSION['dol_dst']);
    //$deltadateforcompany=((int) $_SESSION['dol_tz'] + (int) $_SESSION['dol_dst']);
    $deltadateforuser=round($deltadateforclient-$deltadateforserver);
    //print "x".$deltadateforserver." - ".$deltadateforclient." - ".$deltadateforuser;

    if ($usetable) print '<table class="border centpercent">';

    // Import key
    if (! empty($object->import_key))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("ImportedWithSet");
        if ($usetable) print '</td><td>';
        else print ': ';
        print $object->import_key;
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // User creation (old method using already loaded object and not id is kept for backward compatibility)
    if (! empty($object->user_creation) || ! empty($object->user_creation_id))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("CreatedBy");
        if ($usetable) print '</td><td>';
        else print ': ';
        if (is_object($object->user_creation))
        {
        	if ($object->user_creation->id) print $object->user_creation->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_creation_id ? $object->user_creation_id : $object->user_creation);
            if ($userstatic->id) print $userstatic->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // Date creation
    if (! empty($object->date_creation))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("DateCreation");
        if ($usetable) print '</td><td>';
        else print ': ';
        print dol_print_date($object->date_creation, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_creation+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // User change (old method using already loaded object and not id is kept for backward compatibility)
    if (! empty($object->user_modification) || ! empty($object->user_modification_id))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("ModifiedBy");
        if ($usetable) print '</td><td>';
        else print ': ';
        if (is_object($object->user_modification))
        {
        	if ($object->user_modification->id) print $object->user_modification->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_modification_id ? $object->user_modification_id : $object->user_modification);
            if ($userstatic->id) print $userstatic->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // Date change
    if (! empty($object->date_modification))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("DateLastModification");
        if ($usetable) print '</td><td>';
        else print ': ';
        print dol_print_date($object->date_modification, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_modification+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // User validation (old method using already loaded object and not id is kept for backward compatibility)
    if (! empty($object->user_validation) || ! empty($object->user_validation_id))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("ValidatedBy");
        if ($usetable) print '</td><td>';
        else print ': ';
        if (is_object($object->user_validation))
        {
            if ($object->user_validation->id) print $object->user_validation->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_validation_id ? $object->user_validation_id : $object->user_validation);
			if ($userstatic->id) print $userstatic->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // Date validation
    if (! empty($object->date_validation))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("DateValidation");
        if ($usetable) print '</td><td>';
        else print ': ';
        print dol_print_date($object->date_validation, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_validation+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // User approve (old method using already loaded object and not id is kept for backward compatibility)
    if (! empty($object->user_approve) || ! empty($object->user_approve_id))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("ApprovedBy");
        if ($usetable) print '</td><td>';
        else print ': ';
        if (is_object($object->user_approve))
        {
            if ($object->user_approve->id) print $object->user_approve->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_approve_id ? $object->user_approve_id : $object->user_approve);
			if ($userstatic->id) print $userstatic->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // Date approve
    if (! empty($object->date_approve))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("DateApprove");
        if ($usetable) print '</td><td>';
        else print ': ';
        print dol_print_date($object->date_approve, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_approve+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // User approve
    if (! empty($object->user_approve_id2))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("ApprovedBy");
        if ($usetable) print '</td><td>';
        else print ': ';
        $userstatic=new User($db);
        $userstatic->fetch($object->user_approve_id2);
        if ($userstatic->id) print $userstatic->getNomUrl(1, '', 0, 0, 0);
        else print $langs->trans("Unknown");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // Date approve
    if (! empty($object->date_approve2))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("DateApprove2");
        if ($usetable) print '</td><td>';
        else print ': ';
        print dol_print_date($object->date_approve2, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_approve2+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // User close
    if (! empty($object->user_cloture))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("ClosedBy");
        if ($usetable) print '</td><td>';
        else print ': ';
        if (is_object($object->user_cloture))
        {
			if ($object->user_cloture->id) print $object->user_cloture->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_cloture);
			if ($userstatic->id) print $userstatic->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // Date close
    if (! empty($object->date_cloture))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("DateClosing");
        if ($usetable) print '</td><td>';
        else print ': ';
        print dol_print_date($object->date_cloture, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_cloture+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // User conciliate
    if (! empty($object->user_rappro))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("ConciliatedBy");
        if ($usetable) print '</td><td>';
        else print ': ';
        if (is_object($object->user_rappro))
        {
			if ($object->user_rappro->id) print $object->user_rappro->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_rappro);
			if ($userstatic->id) print $userstatic->getNomUrl(1, '', 0, 0, 0);
        	else print $langs->trans("Unknown");
        }
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // Date conciliate
    if (! empty($object->date_rappro))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("DateConciliating");
        if ($usetable) print '</td><td>';
        else print ': ';
        print dol_print_date($object->date_rappro, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_rappro+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    // Date send
    if (! empty($object->date_envoi))
    {
        if ($usetable) print '<tr><td class="titlefield">';
        print $langs->trans("DateLastSend");
        if ($usetable) print '</td><td>';
        else print ': ';
        print dol_print_date($object->date_envoi, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_envoi+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        if ($usetable) print '</td></tr>';
        else print '<br>';
    }

    if ($usetable) print '</table>';
}


/**
 *	Return an email formatted to include a tracking id
 *  For example  myemail@example.com becom myemail+trackingid@example.com
 *
 *	@param	string	$email       	Email address (Ex: "toto@example.com", "John Do <johndo@example.com>")
 *	@param	string	$trackingid    	Tracking id (Ex: thi123 for thirdparty with id 123)
 *	@return string     			    Return email tracker string
 */
function dolAddEmailTrackId($email, $trackingid)
{
	$tmp=explode('@',$email);
	return $tmp[0].'+'.$trackingid.'@'.(isset($tmp[1])?$tmp[1]:'');
}

/**
 *	Return true if email has a domain name that can't be resolved
 *
 *	@param	string	$mail       Email address (Ex: "toto@example.com", "John Do <johndo@example.com>")
 *	@return boolean     		True if domain email is OK, False if KO
 */
function isValidMailDomain($mail)
{
    list($user, $domain) = explode("@", $mail, 2);
    if (checkdnsrr($domain, "MX"))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 *	Url string validation
 *  <http[s]> :// [user[:pass]@] hostname [port] [/path] [?getquery] [anchor]
 *
 *	@param	string	$url		Url
 *  @param  int		$http		1: verify http is provided, 0: not verify http
 *  @param  int		$pass		1: verify user and pass is provided, 0: not verify user and pass
 *  @param  int		$port		1: verify port is provided, 0: not verify port
 *  @param  int		$path		1: verify a path is provided "/" or "/..." or "/.../", 0: not verify path
 *  @param  int		$query		1: verify query is provided, 0: not verify query
 *  @param  int		$anchor		1: verify anchor is provided, 0: not verify anchor
 *	@return int					1=Check is OK, 0=Check is KO
 */
function isValidUrl($url,$http=0,$pass=0,$port=0,$path=0,$query=0,$anchor=0)
{
    $ValidUrl = 0;
    $urlregex = '';

    // SCHEME
    if ($http) $urlregex .= "^(http:\/\/|https:\/\/)";

    // USER AND PASS
    if ($pass) $urlregex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)";

    // HOSTNAME OR IP
    //$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*";  // x allowed (ex. http://localhost, http://routerlogin)
    //$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)+";  // x.x
    $urlregex .= "([a-z0-9+\$_\\\:-])+(\.[a-z0-9+\$_-][a-z0-9+\$_-]+)*";  // x ou x.xx (2 x ou plus)
    //use only one of the above

    // PORT
    if ($port) $urlregex .= "(\:[0-9]{2,5})";
    // PATH
    if ($path) $urlregex .= "(\/([a-z0-9+\$_-]\.?)+)*\/";
    // GET Query
    if ($query) $urlregex .= "(\?[a-z+&\$_.-][a-z0-9;:@\/&%=+\$_.-]*)";
    // ANCHOR
    if ($anchor) $urlregex .= "(#[a-z_.-][a-z0-9+\$_.-]*)$";

    // check
    if (preg_match('/'.$urlregex.'/i', $url))
    {
        $ValidUrl = 1;
    }
    //print $urlregex.' - '.$url.' - '.$ValidUrl;

    return $ValidUrl;
}

/**
 *	Clean an url string
 *
 *	@param	string	$url		Url
 *	@param  integer	$http		1 = keep both http:// and https://, 0: remove http:// but not https://
 *	@return string				Cleaned url
 */
function clean_url($url,$http=1)
{
    // Fixed by Matelli (see http://matelli.fr/showcases/patchs-dolibarr/fix-cleaning-url.html)
    // To include the minus sign in a char class, we must not escape it but put it at the end of the class
    // Also, there's no need of escape a dot sign in a class
    if (preg_match('/^(https?:[\\/]+)?([0-9A-Z.-]+\.[A-Z]{2,4})(:[0-9]+)?/i',$url,$regs))
    {
        $proto=$regs[1];
        $domain=$regs[2];
        $port=isset($regs[3])?$regs[3]:'';
        //print $url." -> ".$proto." - ".$domain." - ".$port;
        //$url = dol_string_nospecial(trim($url));
        $url = trim($url);

        // Si http: defini on supprime le http (Si https on ne supprime pas)
        $newproto=$proto;
        if ($http==0)
        {
            if (preg_match('/^http:[\\/]+/i',$url))
            {
                $url = preg_replace('/^http:[\\/]+/i','',$url);
                $newproto = '';
            }
        }

        // On passe le nom de domaine en minuscule
        $CleanUrl = preg_replace('/^'.preg_quote($proto.$domain,'/').'/i', $newproto.strtolower($domain), $url);

        return $CleanUrl;
    }
    else return $url;
}



/**
 * 	Returns an email value with obfuscated parts.
 *
 * 	@param 		string		$mail				Email
 * 	@param 		string		$replace			Replacement character (defaul: *)
 * 	@param 		int			$nbreplace			Number of replacement character (default: 8)
 * 	@param 		int			$nbdisplaymail		Number of character unchanged (default: 4)
 * 	@param 		int			$nbdisplaydomain	Number of character unchanged of domain (default: 3)
 * 	@param 		bool		$displaytld			Display tld (default: true)
 * 	@return		string							Return email with hidden parts or '';
 */
function dolObfuscateEmail($mail, $replace="*", $nbreplace=8, $nbdisplaymail=4, $nbdisplaydomain=3, $displaytld=true)
{
	if(!isValidEmail($mail))return '';
	$tab = explode('@', $mail);
	$tab2 = explode('.',$tab[1]);
	$string_replace = '';
	$mail_name = $tab[0];
	$mail_domaine = $tab2[0];
	$mail_tld = '';

	$nbofelem = count($tab2);
	for($i=1; $i < $nbofelem && $displaytld; $i++)
	{
		$mail_tld .= '.'.$tab2[$i];
	}

	for($i=0; $i < $nbreplace; $i++){
		$string_replace .= $replace;
	}

	if(strlen($mail_name) > $nbdisplaymail){
		$mail_name = substr($mail_name, 0, $nbdisplaymail);
	}

	if(strlen($mail_domaine) > $nbdisplaydomain){
		$mail_domaine = substr($mail_domaine, strlen($mail_domaine)-$nbdisplaydomain);
	}

	return $mail_name . $string_replace . $mail_domaine . $mail_tld;
}


/**
 * 	Return lines of an html table from an array
 * 	Used by array2table function only
 *
 * 	@param	array	$data		Array of data
 * 	@param	string	$troptions	Options for tr
 * 	@param	string	$tdoptions	Options for td
 * 	@return	string
 */
function array2tr($data,$troptions='',$tdoptions='')
{
    $text = '<tr '.$troptions.'>' ;
    foreach($data as $key => $item){
        $text.= '<td '.$tdoptions.'>'.$item.'</td>' ;
    }
    $text.= '</tr>' ;
    return $text ;
}

/**
 * 	Return an html table from an array
 *
 * 	@param	array	$data			Array of data
 * 	@param	int		$tableMarkup	Table markup
 * 	@param	string	$tableoptions	Options for table
 * 	@param	string	$troptions		Options for tr
 * 	@param	string	$tdoptions		Options for td
 * 	@return	string
 */
function array2table($data,$tableMarkup=1,$tableoptions='',$troptions='',$tdoptions='')
{
    $text='' ;
    if($tableMarkup) $text = '<table '.$tableoptions.'>' ;
    foreach($data as $key => $item){
        if(is_array($item)){
            $text.=array2tr($item,$troptions,$tdoptions);
        } else {
            $text.= '<tr '.$troptions.'>' ;
            $text.= '<td '.$tdoptions.'>'.$key.'</td>' ;
            $text.= '<td '.$tdoptions.'>'.$item.'</td>' ;
            $text.= '</tr>' ;
        }
    }
    if($tableMarkup) $text.= '</table>' ;
    return $text ;
}

/**
 * Return last or next value for a mask (according to area we should not reset)
 *
 * @param   DoliDB		$db				Database handler
 * @param   string		$mask			Mask to use
 * @param   string		$table			Table containing field with counter
 * @param   string		$field			Field containing already used values of counter
 * @param   string		$where			To add a filter on selection (for exemple to filter on invoice types)
 * @param   Societe		$objsoc			The company that own the object we need a counter for
 * @param   string		$date			Date to use for the {y},{m},{d} tags.
 * @param   string		$mode			'next' for next value or 'last' for last value
 * @param   bool		$bentityon		Activate the entity filter. Default is true (for modules not compatible with multicompany)
 * @param	User		$objuser		Object user we need data from.
 * @param	int			$forceentity	Entity id to force
 * @return 	string						New value (numeric) or error message
 */
function get_next_value($db,$mask,$table,$field,$where='',$objsoc='',$date='',$mode='next', $bentityon=true, $objuser=null, $forceentity=null)
{
    global $conf,$user;

    if (! is_object($objsoc)) $valueforccc=$objsoc;
    else if ($table == "commande_fournisseur" || $table == "facture_fourn" ) $valueforccc=dol_string_unaccent($objsoc->code_fournisseur);
    else $valueforccc=dol_string_unaccent($objsoc->code_client);

    $sharetable = $table;
    if ($table == 'facture' || $table == 'invoice') $sharetable = 'invoicenumber'; // for getEntity function

    // Clean parameters
    if ($date == '') $date=dol_now();	// We use local year and month of PHP server to search numbers
    // but we should use local year and month of user

    // For debugging
    //dol_syslog("mask=".$mask, LOG_DEBUG);
    //include_once(DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');
    //$mask='FA{yy}{mm}-{0000@99}';
    //$date=dol_mktime(12, 0, 0, 1, 1, 1900);
    //$date=dol_stringtotime('20130101');

    $hasglobalcounter=false;
    // Extract value for mask counter, mask raz and mask offset
    if (preg_match('/\{(0+)([@\+][0-9\-\+\=]+)?([@\+][0-9\-\+\=]+)?\}/i',$mask,$reg))
    {
        $masktri=$reg[1].(! empty($reg[2])?$reg[2]:'').(! empty($reg[3])?$reg[3]:'');
        $maskcounter=$reg[1];
        $hasglobalcounter=true;
    }
    else
    {
        // setting some defaults so the rest of the code won't fail if there is a third party counter
        $masktri='00000';
        $maskcounter='00000';
    }

    $maskraz=-1;
    $maskoffset=0;
    $resetEveryMonth=false;
    if (dol_strlen($maskcounter) < 3 && empty($conf->global->MAIN_COUNTER_WITH_LESS_3_DIGITS)) return 'ErrorCounterMustHaveMoreThan3Digits';

    // Extract value for third party mask counter
    if (preg_match('/\{(c+)(0*)\}/i',$mask,$regClientRef))
    {
        $maskrefclient=$regClientRef[1].$regClientRef[2];
        $maskrefclient_maskclientcode=$regClientRef[1];
        $maskrefclient_maskcounter=$regClientRef[2];
        $maskrefclient_maskoffset=0; //default value of maskrefclient_counter offset
        $maskrefclient_clientcode=substr($valueforccc,0,dol_strlen($maskrefclient_maskclientcode));//get n first characters of client code where n is length in mask
        $maskrefclient_clientcode=str_pad($maskrefclient_clientcode,dol_strlen($maskrefclient_maskclientcode),"#",STR_PAD_RIGHT);//padding maskrefclient_clientcode for having exactly n characters in maskrefclient_clientcode
        $maskrefclient_clientcode=dol_string_nospecial($maskrefclient_clientcode);//sanitize maskrefclient_clientcode for sql insert and sql select like
        if (dol_strlen($maskrefclient_maskcounter) > 0 && dol_strlen($maskrefclient_maskcounter) < 3) return 'ErrorCounterMustHaveMoreThan3Digits';
    }
    else $maskrefclient='';

    // fail if there is neither a global nor a third party counter
    if (! $hasglobalcounter && ($maskrefclient_maskcounter == ''))
    {
        return 'ErrorBadMask';
    }

    // Extract value for third party type
    if (preg_match('/\{(t+)\}/i',$mask,$regType))
    {
        $masktype=$regType[1];
        $masktype_value=substr(preg_replace('/^TE_/','',$objsoc->typent_code),0,dol_strlen($regType[1]));// get n first characters of thirdpaty typent_code (where n is length in mask)
        $masktype_value=str_pad($masktype_value,dol_strlen($regType[1]),"#",STR_PAD_RIGHT);				 // we fill on right with # to have same number of char than into mask
    }
    else
    {
    	$masktype='';
    	$masktype_value='';
    }

    // Extract value for user
    if (preg_match('/\{(u+)\}/i',$mask,$regType))
    {
    	$lastname = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
    	if (is_object($objuser)) $lastname = $objuser->lastname;

    	$maskuser=$regType[1];
    	$maskuser_value=substr($lastname,0,dol_strlen($regType[1]));// get n first characters of user firstname (where n is length in mask)
    	$maskuser_value=str_pad($maskuser_value,dol_strlen($regType[1]),"#",STR_PAD_RIGHT);				 // we fill on right with # to have same number of char than into mask
    }
    else
    {
    	$maskuser='';
    	$maskuser_value='';
    }

    // Personalized field {XXX-1} à {XXX-9}
    $maskperso=array();
    $maskpersonew=array();
    $tmpmask=$mask;
    while (preg_match('/\{([A-Z]+)\-([1-9])\}/',$tmpmask,$regKey))
    {
        $maskperso[$regKey[1]]='{'.$regKey[1].'-'.$regKey[2].'}';
        $maskpersonew[$regKey[1]]=str_pad('', $regKey[2], '_', STR_PAD_RIGHT);
        $tmpmask=preg_replace('/\{'.$regKey[1].'\-'.$regKey[2].'\}/i', $maskpersonew[$regKey[1]], $tmpmask);
    }

    if (strstr($mask,'user_extra_'))
    {
			$start = "{user_extra_";
			$end = "\}";
			$extra= get_string_between($mask, "user_extra_", "}");
			if(!empty($user->array_options['options_'.$extra])){
				$mask =  preg_replace('#('.$start.')(.*?)('.$end.')#si', $user->array_options['options_'.$extra], $mask);
			}
    }
    $maskwithonlyymcode=$mask;
    $maskwithonlyymcode=preg_replace('/\{(0+)([@\+][0-9\-\+\=]+)?([@\+][0-9\-\+\=]+)?\}/i',$maskcounter,$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{dd\}/i','dd',$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{(c+)(0*)\}/i',$maskrefclient,$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{(t+)\}/i',$masktype_value,$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{(u+)\}/i',$maskuser_value,$maskwithonlyymcode);
    foreach($maskperso as $key => $val)
    {
        $maskwithonlyymcode=preg_replace('/'.preg_quote($val,'/').'/i', $maskpersonew[$key], $maskwithonlyymcode);
    }
    $maskwithnocode=$maskwithonlyymcode;
    $maskwithnocode=preg_replace('/\{yyyy\}/i','yyyy',$maskwithnocode);
    $maskwithnocode=preg_replace('/\{yy\}/i','yy',$maskwithnocode);
    $maskwithnocode=preg_replace('/\{y\}/i','y',$maskwithnocode);
    $maskwithnocode=preg_replace('/\{mm\}/i','mm',$maskwithnocode);
    // Now maskwithnocode = 0000ddmmyyyyccc for example
    // and maskcounter    = 0000 for example
    //print "maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";
    //var_dump($reg);

    // If an offset is asked
    if (! empty($reg[2]) && preg_match('/^\+/',$reg[2])) $maskoffset=preg_replace('/^\+/','',$reg[2]);
    if (! empty($reg[3]) && preg_match('/^\+/',$reg[3])) $maskoffset=preg_replace('/^\+/','',$reg[3]);

    // Define $sqlwhere
    $sqlwhere='';
    $yearoffset=0;	// Use year of current $date by default
    $yearoffsettype=false;		// false: no reset, 0,-,=,+: reset at offset SOCIETE_FISCAL_MONTH_START, x=reset at offset x

    // If a restore to zero after a month is asked we check if there is already a value for this year.
    if (! empty($reg[2]) && preg_match('/^@/',$reg[2]))	$yearoffsettype = preg_replace('/^@/','',$reg[2]);
    if (! empty($reg[3]) && preg_match('/^@/',$reg[3]))	$yearoffsettype = preg_replace('/^@/','',$reg[3]);

    //print "yearoffset=".$yearoffset." yearoffsettype=".$yearoffsettype;
    if (is_numeric($yearoffsettype) && $yearoffsettype >= 1)
        $maskraz=$yearoffsettype; // For backward compatibility
    else if ($yearoffsettype === '0' || (! empty($yearoffsettype) && ! is_numeric($yearoffsettype) && $conf->global->SOCIETE_FISCAL_MONTH_START > 1))
        $maskraz = $conf->global->SOCIETE_FISCAL_MONTH_START;
    //print "maskraz=".$maskraz;	// -1=no reset

    if ($maskraz > 0) {   // A reset is required
        if ($maskraz == 99) {
            $maskraz = date('m', $date);
            $resetEveryMonth = true;
        }
        if ($maskraz > 12) return 'ErrorBadMaskBadRazMonth';

        // Define posy, posm and reg
        if ($maskraz > 1)	// if reset is not first month, we need month and year into mask
        {
            if (preg_match('/^(.*)\{(y+)\}\{(m+)\}/i',$maskwithonlyymcode,$reg)) { $posy=2; $posm=3; }
            elseif (preg_match('/^(.*)\{(m+)\}\{(y+)\}/i',$maskwithonlyymcode,$reg)) { $posy=3; $posm=2; }
            else return 'ErrorCantUseRazInStartedYearIfNoYearMonthInMask';

            if (dol_strlen($reg[$posy]) < 2) return 'ErrorCantUseRazWithYearOnOneDigit';
        }
        else // if reset is for a specific month in year, we need year
        {
            if (preg_match('/^(.*)\{(m+)\}\{(y+)\}/i',$maskwithonlyymcode,$reg)) { $posy=3; $posm=2; }
        	else if (preg_match('/^(.*)\{(y+)\}\{(m+)\}/i',$maskwithonlyymcode,$reg)) { $posy=2; $posm=3; }
            else if (preg_match('/^(.*)\{(y+)\}/i',$maskwithonlyymcode,$reg)) { $posy=2; $posm=0; }
            else return 'ErrorCantUseRazIfNoYearInMask';
        }
        // Define length
        $yearlen = $posy?dol_strlen($reg[$posy]):0;
        $monthlen = $posm?dol_strlen($reg[$posm]):0;
        // Define pos
       	$yearpos = (dol_strlen($reg[1])+1);
        $monthpos = ($yearpos+$yearlen);
        if ($posy == 3 && $posm == 2) {		// if month is before year
          	$monthpos = (dol_strlen($reg[1])+1);
           	$yearpos = ($monthpos+$monthlen);
        }
        //print "xxx ".$maskwithonlyymcode." maskraz=".$maskraz." posy=".$posy." yearlen=".$yearlen." yearpos=".$yearpos." posm=".$posm." monthlen=".$monthlen." monthpos=".$monthpos." yearoffsettype=".$yearoffsettype." resetEveryMonth=".$resetEveryMonth."\n";

        // Define $yearcomp and $monthcomp (that will be use in the select where to search max number)
        $monthcomp=$maskraz;
        $yearcomp=0;

        if (! empty($yearoffsettype) && ! is_numeric($yearoffsettype) && $yearoffsettype != '=')	// $yearoffsettype is - or +
        {
        	$currentyear=date("Y", $date);
        	$fiscaldate=dol_mktime('0','0','0',$maskraz,'1',$currentyear);
        	$newyeardate=dol_mktime('0','0','0','1','1',$currentyear);
        	$nextnewyeardate=dol_mktime('0','0','0','1','1',$currentyear+1);
        	//echo 'currentyear='.$currentyear.' date='.dol_print_date($date, 'day').' fiscaldate='.dol_print_date($fiscaldate, 'day').'<br>';

        	// If after or equal of current fiscal date
        	if ($date >= $fiscaldate)
        	{
        		// If before of next new year date
        		if ($date < $nextnewyeardate && $yearoffsettype == '+') $yearoffset=1;
        	}
        	// If after or equal of current new year date
        	else if ($date >= $newyeardate && $yearoffsettype == '-') $yearoffset=-1;
        }
        // For backward compatibility
        else if (date("m",$date) < $maskraz && empty($resetEveryMonth)) { $yearoffset=-1; }	// If current month lower that month of return to zero, year is previous year

        if ($yearlen == 4) $yearcomp=sprintf("%04d",date("Y",$date)+$yearoffset);
        elseif ($yearlen == 2) $yearcomp=sprintf("%02d",date("y",$date)+$yearoffset);
        elseif ($yearlen == 1) $yearcomp=substr(date("y",$date),2,1)+$yearoffset;
        if ($monthcomp > 1 && empty($resetEveryMonth))	// Test with month is useless if monthcomp = 0 or 1 (0 is same as 1) (regis: $monthcomp can't equal 0)
        {
            if ($yearlen == 4) $yearcomp1=sprintf("%04d",date("Y",$date)+$yearoffset+1);
            elseif ($yearlen == 2) $yearcomp1=sprintf("%02d",date("y",$date)+$yearoffset+1);

            $sqlwhere.="(";
            $sqlwhere.=" (SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$yearcomp."'";
            $sqlwhere.=" AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") >= '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."')";
            $sqlwhere.=" OR";
            $sqlwhere.=" (SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$yearcomp1."'";
            $sqlwhere.=" AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") < '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."') ";
            $sqlwhere.=')';
        }
		else if ($resetEveryMonth)
		{
			$sqlwhere.="(SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$yearcomp."'";
            $sqlwhere.=" AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") = '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."')";
		}
        else   // reset is done on january
        {
            $sqlwhere.='(SUBSTRING('.$field.', '.$yearpos.', '.$yearlen.") = '".$yearcomp."')";
        }
    }
    //print "sqlwhere=".$sqlwhere." yearcomp=".$yearcomp."<br>\n";	// sqlwhere and yearcomp defined only if we ask a reset
    //print "masktri=".$masktri." maskcounter=".$maskcounter." maskraz=".$maskraz." maskoffset=".$maskoffset."<br>\n";

    // Define $sqlstring
    if (function_exists('mb_strrpos'))
    	{
    	$posnumstart=mb_strrpos($maskwithnocode,$maskcounter, 'UTF-8');
	}
	else
	{
    	$posnumstart=strrpos($maskwithnocode,$maskcounter);
	}	// Pos of counter in final string (from 0 to ...)
    if ($posnumstart < 0) return 'ErrorBadMaskFailedToLocatePosOfSequence';
    $sqlstring='SUBSTRING('.$field.', '.($posnumstart+1).', '.dol_strlen($maskcounter).')';

    // Define $maskLike
    $maskLike = dol_string_nospecial($mask);
    $maskLike = str_replace("%","_",$maskLike);

    // Replace protected special codes with matching number of _ as wild card caracter
    $maskLike = preg_replace('/\{yyyy\}/i','____',$maskLike);
    $maskLike = preg_replace('/\{yy\}/i','__',$maskLike);
    $maskLike = preg_replace('/\{y\}/i','_',$maskLike);
    $maskLike = preg_replace('/\{mm\}/i','__',$maskLike);
    $maskLike = preg_replace('/\{dd\}/i','__',$maskLike);
    $maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'),str_pad("",dol_strlen($maskcounter),"_"),$maskLike);
    if ($maskrefclient) $maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'),str_pad("",dol_strlen($maskrefclient),"_"),$maskLike);
    if ($masktype) $maskLike = str_replace(dol_string_nospecial('{'.$masktype.'}'),$masktype_value,$maskLike);
    if ($maskuser) $maskLike = str_replace(dol_string_nospecial('{'.$maskuser.'}'),$maskuser_value,$maskLike);
    foreach($maskperso as $key => $val)
    {
    	$maskLike = str_replace(dol_string_nospecial($maskperso[$key]),$maskpersonew[$key],$maskLike);
    }

    // Get counter in database
    $counter=0;
    $sql = "SELECT MAX(".$sqlstring.") as val";
    $sql.= " FROM ".MAIN_DB_PREFIX.$table;
    $sql.= " WHERE ".$field." LIKE '".$maskLike."'";
	$sql.= " AND ".$field." NOT LIKE '(PROV%)'";
    if ($bentityon) // only if entity enable
    	$sql.= " AND entity IN (".getEntity($sharetable).")";
    else if (! empty($forceentity))
    	$sql.= " AND entity = ".(int) $forceentity;
    if ($where) $sql.=$where;
    if ($sqlwhere) $sql.=' AND '.$sqlwhere;

    //print $sql.'<br>';
    dol_syslog("functions2::get_next_value mode=".$mode."", LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $obj = $db->fetch_object($resql);
        $counter = $obj->val;
    }
    else dol_print_error($db);

    // Check if we must force counter to maskoffset
    if (empty($counter)) $counter=$maskoffset;
    else if (preg_match('/[^0-9]/i',$counter))
    {
    	$counter=0;
    	dol_syslog("Error, the last counter found is '".$counter."' so is not a numeric value. We will restart to 1.", LOG_ERR);
    }
    else if ($counter < $maskoffset && empty($conf->global->MAIN_NUMBERING_OFFSET_ONLY_FOR_FIRST)) $counter=$maskoffset;

    if ($mode == 'last')	// We found value for counter = last counter value. Now need to get corresponding ref of invoice.
    {
        $counterpadded=str_pad($counter,dol_strlen($maskcounter),"0",STR_PAD_LEFT);

        // Define $maskLike
        $maskLike = dol_string_nospecial($mask);
        $maskLike = str_replace("%","_",$maskLike);
        // Replace protected special codes with matching number of _ as wild card caracter
        $maskLike = preg_replace('/\{yyyy\}/i','____',$maskLike);
        $maskLike = preg_replace('/\{yy\}/i','__',$maskLike);
        $maskLike = preg_replace('/\{y\}/i','_',$maskLike);
        $maskLike = preg_replace('/\{mm\}/i','__',$maskLike);
        $maskLike = preg_replace('/\{dd\}/i','__',$maskLike);
        $maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'),$counterpadded,$maskLike);
        if ($maskrefclient) $maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'),str_pad("",dol_strlen($maskrefclient),"_"),$maskLike);
        if ($masktype) $maskLike = str_replace(dol_string_nospecial('{'.$masktype.'}'),$masktype_value,$maskLike);
        if ($maskuser) $maskLike = str_replace(dol_string_nospecial('{'.$maskuser.'}'),$maskuser_value,$maskLike);

        $ref='';
        $sql = "SELECT ".$field." as ref";
        $sql.= " FROM ".MAIN_DB_PREFIX.$table;
        $sql.= " WHERE ".$field." LIKE '".$maskLike."'";
    	$sql.= " AND ".$field." NOT LIKE '%PROV%'";
    	if ($bentityon) // only if entity enable
        	$sql.= " AND entity IN (".getEntity($sharetable).")";
        else if (! empty($forceentity))
        	$sql.= " AND entity = ".(int) $forceentity;
        if ($where) $sql.=$where;
        if ($sqlwhere) $sql.=' AND '.$sqlwhere;

        dol_syslog("functions2::get_next_value mode=".$mode."", LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $ref = $obj->ref;
        }
        else dol_print_error($db);

        $numFinal=$ref;
    }
    else if ($mode == 'next')
    {
        $counter++;

        // If value for $counter has a length higher than $maskcounter chars
        if ($counter >= pow(10, dol_strlen($maskcounter)))
        {
        	$counter='ErrorMaxNumberReachForThisMask';
        }

        if (! empty($maskrefclient_maskcounter))
        {
            //print "maskrefclient_maskcounter=".$maskrefclient_maskcounter." maskwithnocode=".$maskwithnocode." maskrefclient=".$maskrefclient."\n<br>";

            // Define $sqlstring
            $maskrefclient_posnumstart=strpos($maskwithnocode,$maskrefclient_maskcounter,strpos($maskwithnocode,$maskrefclient));	// Pos of counter in final string (from 0 to ...)
            if ($maskrefclient_posnumstart <= 0) return 'ErrorBadMask';
            $maskrefclient_sqlstring='SUBSTRING('.$field.', '.($maskrefclient_posnumstart+1).', '.dol_strlen($maskrefclient_maskcounter).')';
            //print "x".$sqlstring;

            // Define $maskrefclient_maskLike
            $maskrefclient_maskLike = dol_string_nospecial($mask);
            $maskrefclient_maskLike = str_replace("%","_",$maskrefclient_maskLike);
            // Replace protected special codes with matching number of _ as wild card caracter
            $maskrefclient_maskLike = str_replace(dol_string_nospecial('{yyyy}'),'____',$maskrefclient_maskLike);
            $maskrefclient_maskLike = str_replace(dol_string_nospecial('{yy}'),'__',$maskrefclient_maskLike);
            $maskrefclient_maskLike = str_replace(dol_string_nospecial('{y}'),'_',$maskrefclient_maskLike);
            $maskrefclient_maskLike = str_replace(dol_string_nospecial('{mm}'),'__',$maskrefclient_maskLike);
            $maskrefclient_maskLike = str_replace(dol_string_nospecial('{dd}'),'__',$maskrefclient_maskLike);
            $maskrefclient_maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'),str_pad("",dol_strlen($maskcounter),"_"),$maskrefclient_maskLike);
            $maskrefclient_maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'),$maskrefclient_clientcode.str_pad("",dol_strlen($maskrefclient_maskcounter),"_"),$maskrefclient_maskLike);

            // Get counter in database
            $maskrefclient_counter=0;
            $maskrefclient_sql = "SELECT MAX(".$maskrefclient_sqlstring.") as val";
            $maskrefclient_sql.= " FROM ".MAIN_DB_PREFIX.$table;
            //$sql.= " WHERE ".$field." not like '(%'";
            $maskrefclient_sql.= " WHERE ".$field." LIKE '".$maskrefclient_maskLike."'";
            if ($bentityon) // only if entity enable
            	$maskrefclient_sql.= " AND entity IN (".getEntity($sharetable).")";
            else if (! empty($forceentity))
            	$sql.= " AND entity = ".(int) $forceentity;
            if ($where) $maskrefclient_sql.=$where; //use the same optional where as general mask
            if ($sqlwhere) $maskrefclient_sql.=' AND '.$sqlwhere; //use the same sqlwhere as general mask
            $maskrefclient_sql.=' AND (SUBSTRING('.$field.', '.(strpos($maskwithnocode,$maskrefclient)+1).', '.dol_strlen($maskrefclient_maskclientcode).")='".$maskrefclient_clientcode."')";

            dol_syslog("functions2::get_next_value maskrefclient", LOG_DEBUG);
            $maskrefclient_resql=$db->query($maskrefclient_sql);
            if ($maskrefclient_resql)
            {
                $maskrefclient_obj = $db->fetch_object($maskrefclient_resql);
                $maskrefclient_counter = $maskrefclient_obj->val;
            }
            else dol_print_error($db);

            if (empty($maskrefclient_counter) || preg_match('/[^0-9]/i',$maskrefclient_counter)) $maskrefclient_counter=$maskrefclient_maskoffset;
			$maskrefclient_counter++;
        }

        // Build numFinal
        $numFinal = $mask;

        // We replace special codes except refclient
		if (! empty($yearoffsettype) && ! is_numeric($yearoffsettype) && $yearoffsettype != '=')	// yearoffsettype is - or +, so we don't want current year
		{
	        $numFinal = preg_replace('/\{yyyy\}/i',date("Y",$date)+$yearoffset, $numFinal);
        	$numFinal = preg_replace('/\{yy\}/i',  date("y",$date)+$yearoffset, $numFinal);
        	$numFinal = preg_replace('/\{y\}/i',   substr(date("y",$date),1,1)+$yearoffset, $numFinal);
		}
		else	// we want yyyy to be current year
		{
        	$numFinal = preg_replace('/\{yyyy\}/i',date("Y",$date), $numFinal);
        	$numFinal = preg_replace('/\{yy\}/i',  date("y",$date), $numFinal);
        	$numFinal = preg_replace('/\{y\}/i',   substr(date("y",$date),1,1), $numFinal);
		}
        $numFinal = preg_replace('/\{mm\}/i',  date("m",$date), $numFinal);
        $numFinal = preg_replace('/\{dd\}/i',  date("d",$date), $numFinal);

        // Now we replace the counter
        $maskbefore='{'.$masktri.'}';
        $maskafter=str_pad($counter,dol_strlen($maskcounter),"0",STR_PAD_LEFT);
        //print 'x'.$maskbefore.'-'.$maskafter.'y';
        $numFinal = str_replace($maskbefore,$maskafter,$numFinal);

        // Now we replace the refclient
        if ($maskrefclient)
        {
            //print "maskrefclient=".$maskrefclient." maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode." maskrefclient_clientcode=".$maskrefclient_clientcode."\n<br>";exit;
            $maskrefclient_maskbefore='{'.$maskrefclient.'}';
            $maskrefclient_maskafter=$maskrefclient_clientcode.str_pad($maskrefclient_counter,dol_strlen($maskrefclient_maskcounter),"0",STR_PAD_LEFT);
            $numFinal = str_replace($maskrefclient_maskbefore,$maskrefclient_maskafter,$numFinal);
        }

        // Now we replace the type
        if ($masktype)
        {
            $masktype_maskbefore='{'.$masktype.'}';
            $masktype_maskafter=$masktype_value;
            $numFinal = str_replace($masktype_maskbefore,$masktype_maskafter,$numFinal);
        }

        // Now we replace the user
        if ($maskuser)
        {
        	$maskuser_maskbefore='{'.$maskuser.'}';
        	$maskuser_maskafter=$maskuser_value;
        	$numFinal = str_replace($maskuser_maskbefore,$maskuser_maskafter,$numFinal);
        }
    }

    dol_syslog("functions2::get_next_value return ".$numFinal,LOG_DEBUG);
    return $numFinal;
}

function get_string_between($string, $start, $end){
    $string = " ".$string;
     $ini = strpos($string,$start);
     if ($ini == 0) return "";
     $ini += strlen($start);
     $len = strpos($string,$end,$ini) - $ini;
     return substr($string,$ini,$len);
}

/**
 * Check value
 *
 * @param 	string	$mask		Mask to use
 * @param 	string	$value		Value
 * @return	int|string		    <0 or error string if KO, 0 if OK
 */
function check_value($mask,$value)
{
    $result=0;

    $hasglobalcounter=false;
    // Extract value for mask counter, mask raz and mask offset
    if (preg_match('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i',$mask,$reg))
    {
        $masktri=$reg[1].(isset($reg[2])?$reg[2]:'').(isset($reg[3])?$reg[3]:'');
        $maskcounter=$reg[1];
        $hasglobalcounter=true;
    }
    else
    {
        // setting some defaults so the rest of the code won't fail if there is a third party counter
        $masktri='00000';
        $maskcounter='00000';
    }

    $maskraz=-1;
    $maskoffset=0;
    if (dol_strlen($maskcounter) < 3) return 'ErrorCounterMustHaveMoreThan3Digits';

    // Extract value for third party mask counter
    if (preg_match('/\{(c+)(0*)\}/i',$mask,$regClientRef))
    {
        $maskrefclient=$regClientRef[1].$regClientRef[2];
        $maskrefclient_maskclientcode=$regClientRef[1];
        $maskrefclient_maskcounter=$regClientRef[2];
        $maskrefclient_maskoffset=0; //default value of maskrefclient_counter offset
        $maskrefclient_clientcode=substr('',0,dol_strlen($maskrefclient_maskclientcode));//get n first characters of client code to form maskrefclient_clientcode
        $maskrefclient_clientcode=str_pad($maskrefclient_clientcode,dol_strlen($maskrefclient_maskclientcode),"#",STR_PAD_RIGHT);//padding maskrefclient_clientcode for having exactly n characters in maskrefclient_clientcode
        $maskrefclient_clientcode=dol_string_nospecial($maskrefclient_clientcode);//sanitize maskrefclient_clientcode for sql insert and sql select like
        if (dol_strlen($maskrefclient_maskcounter) > 0 && dol_strlen($maskrefclient_maskcounter) < 3) return 'ErrorCounterMustHaveMoreThan3Digits';
    }
    else $maskrefclient='';

    // fail if there is neither a global nor a third party counter
    if (! $hasglobalcounter && ($maskrefclient_maskcounter == ''))
    {
        return 'ErrorBadMask';
    }

    $maskwithonlyymcode=$mask;
    $maskwithonlyymcode=preg_replace('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i',$maskcounter,$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{dd\}/i','dd',$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{(c+)(0*)\}/i',$maskrefclient,$maskwithonlyymcode);
    $maskwithnocode=$maskwithonlyymcode;
    $maskwithnocode=preg_replace('/\{yyyy\}/i','yyyy',$maskwithnocode);
    $maskwithnocode=preg_replace('/\{yy\}/i','yy',$maskwithnocode);
    $maskwithnocode=preg_replace('/\{y\}/i','y',$maskwithnocode);
    $maskwithnocode=preg_replace('/\{mm\}/i','mm',$maskwithnocode);
    // Now maskwithnocode = 0000ddmmyyyyccc for example
    // and maskcounter    = 0000 for example
    //print "maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";

    // If an offset is asked
    if (! empty($reg[2]) && preg_match('/^\+/',$reg[2])) $maskoffset=preg_replace('/^\+/','',$reg[2]);
    if (! empty($reg[3]) && preg_match('/^\+/',$reg[3])) $maskoffset=preg_replace('/^\+/','',$reg[3]);

    // Define $sqlwhere

    // If a restore to zero after a month is asked we check if there is already a value for this year.
    if (! empty($reg[2]) && preg_match('/^@/',$reg[2]))  $maskraz=preg_replace('/^@/','',$reg[2]);
    if (! empty($reg[3]) && preg_match('/^@/',$reg[3]))  $maskraz=preg_replace('/^@/','',$reg[3]);
    if ($maskraz >= 0)
    {
        if ($maskraz == 99) {
            $maskraz = date('m');
            $resetEveryMonth = true;
        }
        if ($maskraz > 12) return 'ErrorBadMaskBadRazMonth';

        // Define reg
        if ($maskraz > 1 && ! preg_match('/^(.*)\{(y+)\}\{(m+)\}/i',$maskwithonlyymcode,$reg)) return 'ErrorCantUseRazInStartedYearIfNoYearMonthInMask';
        if ($maskraz <= 1 && ! preg_match('/^(.*)\{(y+)\}/i',$maskwithonlyymcode,$reg)) return 'ErrorCantUseRazIfNoYearInMask';
        //print "x".$maskwithonlyymcode." ".$maskraz;
    }
    //print "masktri=".$masktri." maskcounter=".$maskcounter." maskraz=".$maskraz." maskoffset=".$maskoffset."<br>\n";

    // Check we have a number in ($posnumstart+1).', '.dol_strlen($maskcounter)
    //

    // Check length
    $len=dol_strlen($maskwithnocode);
    if (dol_strlen($value) != $len) $result=-1;

    // Define $maskLike
    /* seems not used
    $maskLike = dol_string_nospecial($mask);
    $maskLike = str_replace("%","_",$maskLike);
    // Replace protected special codes with matching number of _ as wild card caracter
    $maskLike = str_replace(dol_string_nospecial('{yyyy}'),'____',$maskLike);
    $maskLike = str_replace(dol_string_nospecial('{yy}'),'__',$maskLike);
    $maskLike = str_replace(dol_string_nospecial('{y}'),'_',$maskLike);
    $maskLike = str_replace(dol_string_nospecial('{mm}'),'__',$maskLike);
    $maskLike = str_replace(dol_string_nospecial('{dd}'),'__',$maskLike);
    $maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'),str_pad("",dol_strlen($maskcounter),"_"),$maskLike);
    if ($maskrefclient) $maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'),str_pad("",strlen($maskrefclient),"_"),$maskLike);
	*/

    dol_syslog("functions2::check_value result=".$result,LOG_DEBUG);
    return $result;
}

/**
 *	Convert a binary data to string that represent hexadecimal value
 *
 *	@param   string		$bin		Value to convert
 *	@param   boolean	$pad      	Add 0
 *	@param   boolean	$upper		Convert to tupper
 *	@return  string					x
 */
function binhex($bin, $pad=false, $upper=false)
{
    $last = dol_strlen($bin)-1;
    for($i=0; $i<=$last; $i++){ $x += $bin[$last-$i] * pow(2,$i); }
    $x = dechex($x);
    if($pad){ while(dol_strlen($x) < intval(dol_strlen($bin))/4){ $x = "0$x"; } }
    if($upper){ $x = strtoupper($x); }
    return $x;
}

/**
 *	Convert an hexadecimal string into a binary string
 *
 *	@param	string	$hexa		Hexadecimal string to convert (example: 'FF')
 *	@return string	    		bin
 */
function hexbin($hexa)
{
    $bin='';
    $strLength = dol_strlen($hexa);
    for($i=0;$i<$strLength;$i++)
    {
        $bin.=str_pad(decbin(hexdec($hexa{$i})),4,'0',STR_PAD_LEFT);
    }
    return $bin;
}

/**
 *	Retourne le numero de la semaine par rapport a une date
 *
 *	@param	string	$time   	Date au format 'timestamp'
 *	@return string					Number of week
 */
function numero_semaine($time)
{
    $stime = strftime('%Y-%m-%d',$time);

    if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?/i',$stime,$reg))
    {
        // Date est au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
        $annee = $reg[1];
        $mois = $reg[2];
        $jour = $reg[3];
    }

    /*
     * Norme ISO-8601:
     * - La semaine 1 de toute annee est celle qui contient le 4 janvier ou que la semaine 1 de toute annee est celle qui contient le 1er jeudi de janvier.
     * - La majorite des annees ont 52 semaines mais les annees qui commence un jeudi et les annees bissextiles commencant un mercredi en possede 53.
     * - Le 1er jour de la semaine est le Lundi
     */

    // Definition du Jeudi de la semaine
    if (date("w",mktime(12,0,0,$mois,$jour,$annee))==0) // Dimanche
    $jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)-3*24*60*60;
    else if (date("w",mktime(12,0,0,$mois,$jour,$annee))<4) // du Lundi au Mercredi
    $jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)+(4-date("w",mktime(12,0,0,$mois,$jour,$annee)))*24*60*60;
    else if (date("w",mktime(12,0,0,$mois,$jour,$annee))>4) // du Vendredi au Samedi
    $jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee)-(date("w",mktime(12,0,0,$mois,$jour,$annee))-4)*24*60*60;
    else // Jeudi
    $jeudiSemaine = mktime(12,0,0,$mois,$jour,$annee);

    // Definition du premier Jeudi de l'annee
    if (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))==0) // Dimanche
    {
        $premierJeudiAnnee = mktime(12,0,0,1,1,date("Y",$jeudiSemaine))+4*24*60*60;
    }
    else if (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))<4) // du Lundi au Mercredi
    {
        $premierJeudiAnnee = mktime(12,0,0,1,1,date("Y",$jeudiSemaine))+(4-date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine))))*24*60*60;
    }
    else if (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))>4) // du Vendredi au Samedi
    {
        $premierJeudiAnnee = mktime(12,0,0,1,1,date("Y",$jeudiSemaine))+(7-(date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))-4))*24*60*60;
    }
    else // Jeudi
    {
        $premierJeudiAnnee = mktime(12,0,0,1,1,date("Y",$jeudiSemaine));
    }

    // Definition du numero de semaine: nb de jours entre "premier Jeudi de l'annee" et "Jeudi de la semaine";
    $numeroSemaine =     (
    (
    date("z",mktime(12,0,0,date("m",$jeudiSemaine),date("d",$jeudiSemaine),date("Y",$jeudiSemaine)))
    -
    date("z",mktime(12,0,0,date("m",$premierJeudiAnnee),date("d",$premierJeudiAnnee),date("Y",$premierJeudiAnnee)))
    ) / 7
    ) + 1;

    // Cas particulier de la semaine 53
    if ($numeroSemaine==53)
    {
        // Les annees qui commence un Jeudi et les annees bissextiles commencant un Mercredi en possede 53
        if (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))==4 || (date("w",mktime(12,0,0,1,1,date("Y",$jeudiSemaine)))==3 && date("z",mktime(12,0,0,12,31,date("Y",$jeudiSemaine)))==365))
        {
            $numeroSemaine = 53;
        }
        else
        {
            $numeroSemaine = 1;
        }
    }

    //echo $jour."-".$mois."-".$annee." (".date("d-m-Y",$premierJeudiAnnee)." - ".date("d-m-Y",$jeudiSemaine).") -> ".$numeroSemaine."<BR>";

    return sprintf("%02d",$numeroSemaine);
}

/**
 *	Convertit une masse d'une unite vers une autre unite
 *
 *	@param	float	$weight    		Masse a convertir
 *	@param  int		$from_unit 		Unite originale en puissance de 10
 *	@param  int		$to_unit   		Nouvelle unite  en puissance de 10
 *	@return float	        		Masse convertie
 */
function weight_convert($weight,&$from_unit,$to_unit)
{
    /* Pour convertire 320 gr en Kg appeler
     *  $f = -3
     *  weigh_convert(320, $f, 0) retournera 0.32
     *
     */
    while ($from_unit  <> $to_unit)
    {
        if ($from_unit > $to_unit)
        {
            $weight = $weight * 10;
            $from_unit = $from_unit - 1;
            $weight = weight_convert($weight,$from_unit, $to_unit);
        }
        if ($from_unit < $to_unit)
        {
            $weight = $weight / 10;
            $from_unit = $from_unit + 1;
            $weight = weight_convert($weight,$from_unit, $to_unit);
        }
    }

    return $weight;
}

/**
 *	Save personnal parameter
 *
 *	@param	DoliDB	$db         Handler database
 *	@param	Conf	$conf		Object conf
 *	@param	User	$user      	Object user
 *	@param	array	$tab        Array (key=>value) with all parameters to save
 *	@return int         		<0 if KO, >0 if OK
 *
 *	@see		dolibarr_get_const, dolibarr_set_const, dolibarr_del_const
 */
function dol_set_user_param($db, $conf, &$user, $tab)
{
    // Verification parametres
    if (count($tab) < 1) return -1;

    $db->begin();

    // We remove old parameters for all keys in $tab
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param";
    $sql.= " WHERE fk_user = ".$user->id;
    $sql.= " AND entity = ".$conf->entity;
    $sql.= " AND param in (";
    $i=0;
    foreach ($tab as $key => $value)
    {
        if ($i > 0) $sql.=',';
        $sql.="'".$db->escape($key)."'";
        $i++;
    }
    $sql.= ")";
    dol_syslog("functions2.lib::dol_set_user_param", LOG_DEBUG);

    $resql=$db->query($sql);
    if (! $resql)
    {
        dol_print_error($db);
        $db->rollback();
        return -1;
    }

    foreach ($tab as $key => $value)
    {
        // Set new parameters
        if ($value)
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,entity,param,value)";
            $sql.= " VALUES (".$user->id.",".$conf->entity.",";
            $sql.= " '".$db->escape($key)."','".$db->escape($value)."')";

            dol_syslog("functions2.lib::dol_set_user_param", LOG_DEBUG);
            $result=$db->query($sql);
            if (! $result)
            {
                dol_print_error($db);
                $db->rollback();
                return -1;
            }
            $user->conf->$key = $value;
            //print "key=".$key." user->conf->key=".$user->conf->$key;
        }
        else
        {
            unset($user->conf->$key);
        }
    }

    $db->commit();
    return 1;
}

/**
 *	Returns formated reduction
 *
 *	@param	int			$reduction		Reduction percentage
 *	@param	Translate	$langs			Output language
 *	@return	string						Formated reduction
 */
function dol_print_reduction($reduction,$langs)
{
    $string = '';
    if ($reduction == 100)
    {
        $string = $langs->transnoentities("Offered");
    }
    else
    {
    	$string = vatrate($reduction,true);
    }

    return $string;
}

/**
 * 	Return OS version.
 *  Note that PHP_OS returns only OS (not version) and OS PHP was built on, not necessarly OS PHP runs on.
 *
 * 	@return		string			OS version
 */
function version_os()
{
    $osversion=php_uname();
    return $osversion;
}

/**
 * 	Return PHP version
 *
 * 	@return		string			PHP version
 *  @see		versionphparray
 */
function version_php()
{
    return phpversion();
}

/**
 * 	Return Dolibarr version
 *
 * 	@return		string			Dolibarr version
 *  @see		versiondolibarrarray
 */
function version_dolibarr()
{
    return DOL_VERSION;
}

/**
 * 	Return web server version
 *
 * 	@return		string			Web server version
 */
function version_webserver()
{
    return $_SERVER["SERVER_SOFTWARE"];
}

/**
 * 	Return list of activated modules usable for document generation
 *
 * 	@param	DoliDB		$db				    Database handler
 * 	@param	string		$type			    Type of models (company, invoice, ...)
 *  @param  int		    $maxfilenamelength  Max length of value to show
 * 	@return	mixed			    			0 if no module is activated, or array(key=>label). For modules that need directory scan, key is completed with ":filename".
 */
function getListOfModels($db,$type,$maxfilenamelength=0)
{
    global $conf,$langs;
    $liste=array();
    $found=0;
    $dirtoscan='';

    $sql = "SELECT nom as id, nom as lib, libelle as label, description as description";
    $sql.= " FROM ".MAIN_DB_PREFIX."document_model";
    $sql.= " WHERE type = '".$type."'";
    $sql.= " AND entity IN (0,".$conf->entity.")";
    $sql.= " ORDER BY description DESC";

    dol_syslog('/core/lib/function2.lib.php::getListOfModels', LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num)
        {
            $found=1;

            $obj = $db->fetch_object($resql);

            // If this generation module needs to scan a directory, then description field is filled
            // with the constant that contains list of directories to scan (COMPANY_ADDON_PDF_ODT_PATH, ...).
            if (! empty($obj->description))	// A list of directories to scan is defined
            {
                include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                $const=$obj->description;
                //irtoscan.=($dirtoscan?',':'').preg_replace('/[\r\n]+/',',',trim($conf->global->$const));
                $dirtoscan= preg_replace('/[\r\n]+/',',',trim($conf->global->$const));

		$listoffiles=array();

                // Now we add models found in directories scanned
                $listofdir=explode(',',$dirtoscan);
                foreach($listofdir as $key=>$tmpdir)
                {
                    $tmpdir=trim($tmpdir);
                    $tmpdir=preg_replace('/DOL_DATA_ROOT/',DOL_DATA_ROOT,$tmpdir);
                    if (! $tmpdir) { unset($listofdir[$key]); continue; }
                    if (is_dir($tmpdir))
                    {
			// all type of template is allowed
			$tmpfiles=dol_dir_list($tmpdir, 'files', 0, '', '', 'name', SORT_ASC, 0);
                        if (count($tmpfiles)) $listoffiles=array_merge($listoffiles,$tmpfiles);
                    }
                }

                if (count($listoffiles))
                {
                    foreach($listoffiles as $record)
                    {
                        $max=($maxfilenamelength?$maxfilenamelength:28);
                        $liste[$obj->id.':'.$record['fullname']]=dol_trunc($record['name'],$max,'middle');
                    }
                }
                else
                {
                    $liste[0]=$obj->label.': '.$langs->trans("None");
                }
            }
            else
            {
                if ($type == 'member' && $obj->lib == 'standard')   // Special case, if member template, we add variant per format
                {
                    global $_Avery_Labels;
                    include_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php';
                    foreach($_Avery_Labels as $key => $val)
                    {
                        $liste[$obj->id.':'.$key]=($obj->label?$obj->label:$obj->lib).' '.$val['name'];
                    }
                }
                else    // Common usage
                {
                    $liste[$obj->id]=$obj->label?$obj->label:$obj->lib;
                }
            }
            $i++;
        }
    }
    else
    {
        dol_print_error($db);
        return -1;
    }

    if ($found) return $liste;
    else return 0;
}

/**
 * This function evaluates a string that should be a valid IPv4
 * Note: For ip 169.254.0.0, it returns 0 with some PHP (5.6.24) and 2 with some minor patchs of PHP (5.6.25). See https://github.com/php/php-src/pull/1954.
 *
 * @param	string $ip IP Address
 * @return	int 0 if not valid or reserved range, 1 if valid and public IP, 2 if valid and private range IP
 */
function is_ip($ip)
{
	// First we test if it is a valid IPv4
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {

		// Then we test if it is a private range
		if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) return 2;

		// Then we test if it is a reserved range
		if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) return 0;

		return 1;
	}

	return 0;
}

/**
 *  Build a login from lastname, firstname
 *
 *  @param	string		$lastname		Lastname
 *  @param  string		$firstname		Firstname
 *	@return	string						Login
 */
function dol_buildlogin($lastname,$firstname)
{
    $login=strtolower(dol_string_unaccent($firstname));
    $login.=($login?'.':'');
    $login.=strtolower(dol_string_unaccent($lastname));
    $login=dol_string_nospecial($login,''); // For special names
    return $login;
}

/**
 *  Return array to use for SoapClient constructor
 *
 *  @return     array
 */
function getSoapParams()
{
    global $conf;

    $params=array();
    $proxyuse =(empty($conf->global->MAIN_PROXY_USE)?false:true);
    $proxyhost=(empty($conf->global->MAIN_PROXY_USE)?false:$conf->global->MAIN_PROXY_HOST);
    $proxyport=(empty($conf->global->MAIN_PROXY_USE)?false:$conf->global->MAIN_PROXY_PORT);
    $proxyuser=(empty($conf->global->MAIN_PROXY_USE)?false:$conf->global->MAIN_PROXY_USER);
    $proxypass=(empty($conf->global->MAIN_PROXY_USE)?false:$conf->global->MAIN_PROXY_PASS);
    $timeout  =(empty($conf->global->MAIN_USE_CONNECT_TIMEOUT)?10:$conf->global->MAIN_USE_CONNECT_TIMEOUT);               // Connection timeout
    $response_timeout=(empty($conf->global->MAIN_USE_RESPONSE_TIMEOUT)?30:$conf->global->MAIN_USE_RESPONSE_TIMEOUT);    // Response timeout
    //print extension_loaded('soap');
    if ($proxyuse)
    {
        $params=array('connection_timeout'=>$timeout,
                      'response_timeout'=>$response_timeout,
                      'proxy_use'      => 1,
                      'proxy_host'     => $proxyhost,
                      'proxy_port'     => $proxyport,
                      'proxy_login'    => $proxyuser,
                      'proxy_password' => $proxypass,
                      'trace'		   => 1
        );
    }
    else
    {
        $params=array('connection_timeout'=>$timeout,
                      'response_timeout'=>$response_timeout,
                      'proxy_use'      => 0,
                      'proxy_host'     => false,
                      'proxy_port'     => false,
                      'proxy_login'    => false,
                      'proxy_password' => false,
                      'trace'		   => 1
        );
    }
    return $params;
}


/**
 * List urls of element
 *
 * @param 	int		$objectid		Id of record
 * @param 	string	$objecttype		Type of object ('invoice', 'order', 'expedition_bon', ...)
 * @param 	int		$withpicto		Picto to show
 * @param 	string	$option			More options
 * @return	string					URL of link to object id/type
 */
function dolGetElementUrl($objectid,$objecttype,$withpicto=0,$option='')
{
	global $db, $conf, $langs;

	$ret='';

	// Parse element/subelement (ex: project_task)
	$module = $element = $subelement = $objecttype;
	if (preg_match('/^([^_]+)_([^_]+)/i',$objecttype,$regs))
	{
		$module = $element = $regs[1];
		$subelement = $regs[2];
	}

	$classpath = $element.'/class';

	// To work with non standard path
	if ($objecttype == 'facture' || $objecttype == 'invoice') {
		$classpath = 'compta/facture/class';
		$module='facture';
		$subelement='facture';
	}
	if ($objecttype == 'commande' || $objecttype == 'order') {
		$classpath = 'commande/class';
		$module='commande';
		$subelement='commande';
	}
	if ($objecttype == 'propal')  {
		$classpath = 'comm/propal/class';
	}
	if ($objecttype == 'supplier_proposal')  {
		$classpath = 'supplier_proposal/class';
	}
	if ($objecttype == 'shipping') {
		$classpath = 'expedition/class';
		$subelement = 'expedition';
		$module = 'expedition_bon';
	}
	if ($objecttype == 'delivery') {
		$classpath = 'livraison/class';
		$subelement = 'livraison';
		$module = 'livraison_bon';
	}
	if ($objecttype == 'contract') {
		$classpath = 'contrat/class';
		$module='contrat';
		$subelement='contrat';
	}
	if ($objecttype == 'member') {
		$classpath = 'adherents/class';
		$module='adherent';
		$subelement='adherent';
	}
	if ($objecttype == 'cabinetmed_cons') {
		$classpath = 'cabinetmed/class';
		$module='cabinetmed';
		$subelement='cabinetmedcons';
	}
	if ($objecttype == 'fichinter') {
		$classpath = 'fichinter/class';
		$module='ficheinter';
		$subelement='fichinter';
	}
	if ($objecttype == 'task') {
		$classpath = 'projet/class';
		$module='projet';
		$subelement='task';
	}
	if ($objecttype == 'stock') {
		$classpath = 'product/stock/class';
		$module='stock';
		$subelement='stock';
	}

	//print "objecttype=".$objecttype." module=".$module." subelement=".$subelement;

	$classfile = strtolower($subelement); $classname = ucfirst($subelement);
	if ($objecttype == 'invoice_supplier') {
		$classfile = 'fournisseur.facture';
		$classname='FactureFournisseur';
		$classpath = 'fourn/class';
		$module='fournisseur';
	}
	elseif ($objecttype == 'order_supplier')   {
		$classfile = 'fournisseur.commande';
		$classname='CommandeFournisseur';
		$classpath = 'fourn/class';
		$module='fournisseur';
	}
	elseif ($objecttype == 'stock')   {
		$classpath = 'product/stock/class';
		$classfile='entrepot';
		$classname='Entrepot';
	}
	if (! empty($conf->$module->enabled))
	{
		$res=dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
		if ($res)
		{
			if (class_exists($classname))
			{
				$object = new $classname($db);
				$res=$object->fetch($objectid);
				if ($res > 0) $ret=$object->getNomUrl($withpicto,$option);
				unset($object);
			}
			else dol_syslog("Class with classname ".$classname." is unknown even after the include", LOG_ERR);
		}
	}
	return $ret;
}


/**
 * Clean corrupted tree (orphelins linked to a not existing parent), record linked to themself and child-parent loop
 *
 * @param	DoliDB	$db					Database handler
 * @param	string	$tabletocleantree	Table to clean
 * @param	string	$fieldfkparent		Field name that contains id of parent
 * @return	int							Nb of records fixed/deleted
 */
function cleanCorruptedTree($db, $tabletocleantree, $fieldfkparent)
{
	$totalnb=0;
	$listofid=array();
	$listofparentid=array();

	// Get list of all id in array listofid and all parents in array listofparentid
	$sql='SELECT rowid, '.$fieldfkparent.' as parent_id FROM '.MAIN_DB_PREFIX.$tabletocleantree;
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$listofid[]=$obj->rowid;
			if ($obj->parent_id > 0) $listofparentid[$obj->rowid]=$obj->parent_id;
			$i++;
		}
	}
	else
	{
		dol_print_error($db);
	}

	if (count($listofid))
	{
		print 'Code requested to clean tree (may be to solve data corruption), so we check/clean orphelins and loops.'."<br>\n";

		// Check loops on each other
		$sql = "UPDATE ".MAIN_DB_PREFIX.$tabletocleantree." SET ".$fieldfkparent." = 0 WHERE ".$fieldfkparent." = rowid";	// So we update only records linked to themself
		$resql = $db->query($sql);
		if ($resql)
		{
			$nb=$db->affected_rows($sql);
			if ($nb > 0)
			{
				print '<br>Some record that were parent of themself were cleaned.';
			}

			$totalnb+=$nb;
		}
		//else dol_print_error($db);

		// Check other loops
		$listofidtoclean=array();
		foreach($listofparentid as $id => $pid)
		{
			// Check depth
			//print 'Analyse record id='.$id.' with parent '.$pid.'<br>';

			$cursor=$id; $arrayidparsed=array();	// We start from child $id
			while ($cursor > 0)
			{
				$arrayidparsed[$cursor]=1;
				if ($arrayidparsed[$listofparentid[$cursor]])	// We detect a loop. A record with a parent that was already into child
				{
					print 'Found a loop between id '.$id.' - '.$cursor.'<br>';
					unset($arrayidparsed);
					$listofidtoclean[$cursor]=$id;
					break;
				}
				$cursor=$listofparentid[$cursor];
			}

			if (count($listofidtoclean)) break;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX.$tabletocleantree;
		$sql.= " SET ".$fieldfkparent." = 0";
		$sql.= " WHERE rowid IN (".join(',',$listofidtoclean).")";	// So we update only records detected wrong
		$resql = $db->query($sql);
		if ($resql)
		{
			$nb=$db->affected_rows($sql);
			if ($nb > 0)
			{
				// Removed orphelins records
				print '<br>Some records were detected to have parent that is a child, we set them as root record for id: ';
				print join(',',$listofidtoclean);
			}

			$totalnb+=$nb;
		}
		//else dol_print_error($db);

		// Check and clean orphelins
		$sql = "UPDATE ".MAIN_DB_PREFIX.$tabletocleantree;
		$sql.= " SET ".$fieldfkparent." = 0";
		$sql.= " WHERE ".$fieldfkparent." NOT IN (".join(',',$listofid).")";	// So we update only records linked to a non existing parent
		$resql = $db->query($sql);
		if ($resql)
		{
			$nb=$db->affected_rows($sql);
			if ($nb > 0)
			{
				// Removed orphelins records
				print '<br>Some orphelins were found and modified to be parent so records are visible again for id: ';
				print join(',',$listofid);
			}

			$totalnb+=$nb;
		}
		//else dol_print_error($db);

		print '<br>We fixed '.$totalnb.' record(s). Some records may still be corrupted. New check may be required.';
		return $totalnb;
	}
}

/**
 *	Get an array with properties of an element
 *
 * @param 	string 	$element_type 	Element type: 'action', 'facture', 'project_task' or 'object@modulext'...
 * @return 	array					(module, classpath, element, subelement, classfile, classname)
 */
function getElementProperties($element_type)
{
    // Parse element/subelement (ex: project_task)
    $module = $element = $subelement = $element_type;

    // If we ask an resource form external module (instead of default path)
    if (preg_match('/^([^@]+)@([^@]+)$/i',$element_type,$regs))
    {
        $element = $subelement = $regs[1];
        $module 	= $regs[2];
    }

    //print '<br>1. element : '.$element.' - module : '.$module .'<br>';
    if ( preg_match('/^([^_]+)_([^_]+)/i',$element,$regs))
    {
        $module = $element = $regs[1];
        $subelement = $regs[2];
    }

    // For compat
    if($element_type == "action") {
        $classpath = 'comm/action/class';
        $subelement = 'Actioncomm';
        $module = 'agenda';
    }

    // To work with non standard path
    if ($element_type == 'facture' || $element_type == 'invoice') {
        $classpath = 'compta/facture/class';
        $module='facture';
        $subelement='facture';
    }
    if ($element_type == 'commande' || $element_type == 'order') {
        $classpath = 'commande/class';
        $module='commande';
        $subelement='commande';
    }
    if ($element_type == 'propal')  {
        $classpath = 'comm/propal/class';
    }
    if ($element_type == 'supplier_proposal')  {
        $classpath = 'supplier_proposal/class';
    }
    if ($element_type == 'shipping') {
        $classpath = 'expedition/class';
        $subelement = 'expedition';
        $module = 'expedition_bon';
    }
    if ($element_type == 'delivery') {
        $classpath = 'livraison/class';
        $subelement = 'livraison';
        $module = 'livraison_bon';
    }
    if ($element_type == 'contract') {
        $classpath = 'contrat/class';
        $module='contrat';
        $subelement='contrat';
    }
    if ($element_type == 'member') {
        $classpath = 'adherents/class';
        $module='adherent';
        $subelement='adherent';
    }
    if ($element_type == 'cabinetmed_cons') {
        $classpath = 'cabinetmed/class';
        $module='cabinetmed';
        $subelement='cabinetmedcons';
    }
    if ($element_type == 'fichinter') {
        $classpath = 'fichinter/class';
        $module='ficheinter';
        $subelement='fichinter';
    }
    if ($element_type == 'dolresource' || $element_type == 'resource') {
        $classpath = 'resource/class';
        $module='resource';
        $subelement='dolresource';
    }
    if ($element_type == 'propaldet') {
        $classpath = 'comm/propal/class';
        $module='propal';
        $subelement='propaleligne';
    }
    if ($element_type == 'order_supplier')  {
        $classpath = 'fourn/class';
        $module='fournisseur';
        $subelement='commandefournisseur';
        $classfile='fournisseur.commande';
    }
    if ($element_type == 'invoice_supplier')  {
        $classpath = 'fourn/class';
        $module='fournisseur';
        $subelement='facturefournisseur';
        $classfile='fournisseur.facture';
    }

    if (!isset($classfile)) $classfile = strtolower($subelement);
    if (!isset($classname)) $classname = ucfirst($subelement);
    if (!isset($classpath)) $classpath = $module.'/class';

    $element_properties = array(
        'module' => $module,
        'classpath' => $classpath,
        'element' => $element,
        'subelement' => $subelement,
        'classfile' => $classfile,
        'classname' => $classname
    );
    return $element_properties;
}

/**
 * Fetch an object from its id and element_type
 * Inclusion of classes is automatic
 *
 * @param	int     	$element_id 	Element id
 * @param	string  	$element_type 	Element type
 * @param	ref     	$element_ref 	Element ref (Use this if element_id but not both)
 * @return 	int|object 					object || 0 || -1 if error
 */
function fetchObjectByElement($element_id, $element_type, $element_ref='')
{
    global $conf;
	global $db,$conf;

    $element_prop = getElementProperties($element_type);
    if (is_array($element_prop) && $conf->{$element_prop['module']}->enabled)
    {
        dol_include_once('/'.$element_prop['classpath'].'/'.$element_prop['classfile'].'.class.php');

		$objecttmp = new $element_prop['classname']($db);
		$ret = $objecttmp->fetch($element_id, $element_ref);
		if ($ret >= 0)
		{
			return $objecttmp;
		}
	}
	return 0;
}


/**
 *	Convert an array with RGB value into hex RGB value.
 *  This is the opposite function of colorStringToArray
 *
 *  @param	array	$arraycolor			Array
 *  @param	string	$colorifnotfound	Color code to return if entry not defined or not a RGB format
 *  @return	string						RGB hex value (without # before). For example: 'FF00FF', '01FF02'
 *  @see	colorStringToArray
 */
function colorArrayToHex($arraycolor,$colorifnotfound='888888')
{
	if (! is_array($arraycolor)) return $colorifnotfound;
	if (empty($arraycolor)) return $colorifnotfound;
	return sprintf("%02s",dechex($arraycolor[0])).sprintf("%02s",dechex($arraycolor[1])).sprintf("%02s",dechex($arraycolor[2]));
}

/**
 *	Convert a string RGB value ('FFFFFF', '255,255,255') into an array RGB array(255,255,255).
 *  This is the opposite function of colorArrayToHex.
 *  If entry is already an array, return it.
 *
 *  @param	string	$stringcolor		String with hex (FFFFFF) or comma RGB ('255,255,255')
 *  @param	array	$colorifnotfound	Color code array to return if entry not defined
 *  @return	string						RGB hex value (without # before). For example: FF00FF
 *  @see	colorArrayToHex
 */
function colorStringToArray($stringcolor,$colorifnotfound=array(88,88,88))
{
	if (is_array($stringcolor)) return $stringcolor;	// If already into correct output format, we return as is
	$tmp=preg_match('/^#?([0-9a-fA-F][0-9a-fA-F])([0-9a-fA-F][0-9a-fA-F])([0-9a-fA-F][0-9a-fA-F])$/',$stringcolor,$reg);
	if (! $tmp)
	{
		$tmp=explode(',',$stringcolor);
		if (count($tmp) < 3) return $colorifnotfound;
		return $tmp;
	}
	return array(hexdec($reg[1]),hexdec($reg[2]),hexdec($reg[3]));
}

/**
 * Applies the Cartesian product algorithm to an array
 * Source: http://stackoverflow.com/a/15973172
 *
 * @param   array $input    Array of products
 * @return  array           Array of combinations
 */
function cartesianArray(array $input) {
    // filter out empty values
    $input = array_filter($input);

    $result = array(array());

    foreach ($input as $key => $values) {
        $append = array();

        foreach($result as $product) {
            foreach($values as $item) {
                $product[$key] = $item;
                $append[] = $product;
            }
        }

        $result = $append;
    }

    return $result;
}


/**
 * Get name of directory where the api_...class.php file is stored
 *
 * @param   string  $module     Module name
 * @return  string              Directory name
 */
function getModuleDirForApiClass($module)
{
    $moduledirforclass=$module;
    if ($moduledirforclass != 'api') $moduledirforclass = preg_replace('/api$/i','',$moduledirforclass);

    if ($module == 'contracts') {
    	$moduledirforclass = 'contrat';
    }
    elseif (in_array($module, array('admin', 'login', 'setup', 'access', 'status', 'tools', 'documents'))) {
        $moduledirforclass = 'api';
    }
    elseif ($module == 'contact' || $module == 'contacts' || $module == 'customer' || $module == 'thirdparty' || $module == 'thirdparties') {
        $moduledirforclass = 'societe';
    }
    elseif ($module == 'propale' || $module == 'proposals') {
        $moduledirforclass = 'comm/propal';
    }
    elseif ($module == 'agenda' || $module == 'agendaevents') {
        $moduledirforclass = 'comm/action';
    }
    elseif ($module == 'adherent' || $module == 'members' || $module == 'memberstypes' || $module == 'subscriptions') {
        $moduledirforclass = 'adherents';
    }
    elseif ($module == 'banque' || $module == 'bankaccounts') {
        $moduledirforclass = 'compta/bank';
    }
    elseif ($module == 'category' || $module == 'categorie') {
        $moduledirforclass = 'categories';
    }
    elseif ($module == 'order' || $module == 'orders') {
        $moduledirforclass = 'commande';
    }
    elseif ($module == 'shipments') {
    	$moduledirforclass = 'expedition';
    }
    elseif ($module == 'facture' || $module == 'invoice' || $module == 'invoices') {
        $moduledirforclass = 'compta/facture';
    }
    elseif ($module == 'products') {
        $moduledirforclass = 'product';
    }
    elseif ($module == 'project' || $module == 'projects' || $module == 'tasks') {
        $moduledirforclass = 'projet';
    }
    elseif ($module == 'task') {
        $moduledirforclass = 'projet';
    }
    elseif ($module == 'stock' || $module == 'stockmovements' || $module == 'warehouses') {
        $moduledirforclass = 'product/stock';
    }
    elseif ($module == 'supplierproposals' || $module == 'supplierproposal' || $module == 'supplier_proposal') {
    	$moduledirforclass = 'supplier_proposal';
    }
    elseif ($module == 'fournisseur' || $module == 'supplierinvoices' || $module == 'supplierorders') {
        $moduledirforclass = 'fourn';
    }
    elseif ($module == 'expensereports') {
        $moduledirforclass = 'expensereport';
    }
    elseif ($module == 'users') {
        $moduledirforclass = 'user';
    }
    elseif ($module == 'ficheinter' || $module == 'interventions') {
    	$moduledirforclass = 'fichinter';
    }
    elseif ($module == 'tickets') {
    	$moduledirforclass = 'ticket';
    }

    return $moduledirforclass;
}

/*
 * Return 2 hexa code randomly
 *
 * @param	$min	int	Between 0 and 255
 * @param	$max	int	Between 0 and 255
 * @return String
 */
function random_color_part($min=0,$max=255) {
	return str_pad( dechex( mt_rand( $min, $max) ), 2, '0', STR_PAD_LEFT);
}

/*
 * Return hexadecimal color randomly
 *
 * @param	$min	int	Between 0 and 255
 * @param	$max	int	Between 0 and 255
 * @return String
 */
function random_color($min=0, $max=255) {
	return random_color_part($min, $max) . random_color_part($min, $max) . random_color_part($min, $max);
}
