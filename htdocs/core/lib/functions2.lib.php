<?php
/* Copyright (C) 2008-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
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
 * Return list of modules directories
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
        $modulesdir[$dirroot . '/core/modules'.$subdir.'/'] = $dirroot . '/core/modules'.$subdir.'/';

        // Scan dir from external modules
        $handle=@opendir($dirroot);
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
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
function dol_getDefaultFormat($outputlangs='')
{
    global $langs;

    $selected='EUA4';
    if (empty($outputlangs) || ! is_object($outputlangs)) $outputlangs=$langs;

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
 *	@param	Object	$object			Objet to show
 *	@return	void
 */
function dol_print_object_info($object)
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

    // Import key
    if (! empty($object->import_key))
    print $langs->trans("ImportedWithSet").': '.$object->import_key.'<br>';

    // User creation
    if (! empty($object->user_creation))
    {
        print $langs->trans("CreatedBy").': ';
        if (is_object($object->user_creation))
        {
            print $object->user_creation->getNomUrl(1);
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_creation);
            print $userstatic->getNomUrl(1);
        }
        print '<br>';
    }

    // Date creation
    if (! empty($object->date_creation))
    {
        print $langs->trans("DateCreation").': '.dol_print_date($object->date_creation, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_creation+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        print '<br>';
    }

    // User change
    if (! empty($object->user_modification))
    {
        print $langs->trans("ModifiedBy").': ';
        if (is_object($object->user_modification))
        {
            print $object->user_modification->getNomUrl(1);
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_modification);
            print $userstatic->getNomUrl(1);
        }
        print '<br>';
    }

    // Date change
    if (! empty($object->date_modification))
    {
        print $langs->trans("DateLastModification").': '.dol_print_date($object->date_modification, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_modification+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        print '<br>';
    }

    // User validation
    if (! empty($object->user_validation))
    {
        print $langs->trans("ValidatedBy").': ';
        if (is_object($object->user_validation))
        {
            print $object->user_validation->getNomUrl(1);
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_validation);
            print $userstatic->getNomUrl(1);
        }
        print '<br>';
    }

    // Date validation
    if (! empty($object->date_validation))
    {
        print $langs->trans("DateValidation").': '.dol_print_date($object->date_validation, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_validation+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        print '<br>';
    }

    // User approve
    if (! empty($object->user_approve))
    {
        print $langs->trans("ApprovedBy").': ';
        if (is_object($object->user_approve))
        {
            print $object->user_approve->getNomUrl(1);
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_approve);
            print $userstatic->getNomUrl(1);
        }
        print '<br>';
    }

    // Date approve
    if (! empty($object->date_approve))
    {
        print $langs->trans("DateApprove").': '.dol_print_date($object->date_approve, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_approve+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        print '<br>';
    }

    // User close
    if (! empty($object->user_cloture))
    {
        print $langs->trans("ClosedBy").': ';
        if (is_object($object->user_cloture))
        {
            print $object->user_cloture->getNomUrl(1);
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_cloture);
            print $userstatic->getNomUrl(1);
        }
        print '<br>';
    }

    // Date close
    if (! empty($object->date_cloture))
    {
        print $langs->trans("DateClosing").': '.dol_print_date($object->date_cloture, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_cloture+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        print '<br>';
    }

    // User conciliate
    if (! empty($object->user_rappro))
    {
        print $langs->trans("ConciliatedBy").': ';
        if (is_object($object->user_rappro))
        {
            print $object->user_rappro->getNomUrl(1);
        }
        else
        {
            $userstatic=new User($db);
            $userstatic->fetch($object->user_rappro);
            print $userstatic->getNomUrl(1);
        }
        print '<br>';
    }

    // Date conciliate
    if (! empty($object->date_rappro))
    {
        print $langs->trans("DateConciliating").': '.dol_print_date($object->date_rappro, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_rappro+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        print '<br>';
    }

    // Date send
    if (! empty($object->date_envoi))
    {
        print $langs->trans("DateLastSend").': '.dol_print_date($object->date_envoi, 'dayhour');
        if ($deltadateforuser) print ' '.$langs->trans("CurrentHour").' &nbsp; / &nbsp; '.dol_print_date($object->date_envoi+($deltadateforuser*3600),"dayhour").' &nbsp;'.$langs->trans("ClientHour");
        print '<br>';
    }
}

/**
 *	Return true if email has a domain name that can't be resolved
 *
 *	@param	string	$mail       Email address (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
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
 *  @param  int		$http		1: verify http, 0: not verify http
 *  @param  int		$pass		1: verify user and pass, 0: not verify user and pass
 *  @param  int		$port		1: verify port, 0: not verify port
 *  @param  int		$path		1: verify path, 0: not verify path
 *  @param  int		$query		1: verify query, 0: not verify query
 *  @param  int		$anchor		1: verify anchor, 0: not verify anchor
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
    //$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*";  // http://x = allowed (ex. http://localhost, http://routerlogin)
    //$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)+";  // http://x.x = minimum
    $urlregex .= "([a-z0-9+\$_-]+\.)*[a-z0-9+\$_-]{2,3}";  // http://x.xx(x) = minimum
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
    //print $urlregex.' - '.$url.' - '.$ValidUrl;exit;

    return $ValidUrl;
}

/**
 *	Clean an url string
 *
 *	@param	string	$url		Url
 *	@param  string	$http		1: keep http://, 0: remove also http://
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
        $port=$regs[3];
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
 * @param	DoliDB		$db				Database handler
 * @param   string		$mask			Mask to use
 * @param   string		$table			Table containing field with counter
 * @param   string		$field			Field containing already used values of counter
 * @param   string		$where			To add a filter on selection (for exemple to filter on invoice types)
 * @param   Societe		$objsoc			The company that own the object we need a counter for
 * @param   string		$date			Date to use for the {y},{m},{d} tags.
 * @param   string		$mode           'next' for next value or 'last' for last value
 * @return 	string					    New value
 */
function get_next_value($db,$mask,$table,$field,$where='',$objsoc='',$date='',$mode='next')
{
    global $conf;

    if (! is_object($objsoc)) $valueforccc=$objsoc;
    else $valueforccc=$objsoc->code_client;

    // Clean parameters
    if ($date == '') $date=dol_now();	// We use local year and month of PHP server to search numbers
    // but we should use local year and month of user

    // For debugging
    //include_once(DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');
    //$mask='{yyyy}-{0000}';
    //$date=dol_mktime(12, 0, 0, 1, 1, 1900);
    //$date=dol_stringtotime('20121001');

    // Extract value for mask counter, mask raz and mask offset
    if (! preg_match('/\{(0+)([@\+][0-9\-\+\=]+)?([@\+][0-9\-\+\=]+)?\}/i',$mask,$reg)) return 'ErrorBadMask';
    $masktri=$reg[1].(! empty($reg[2])?$reg[2]:'').(! empty($reg[3])?$reg[3]:'');
    $maskcounter=$reg[1];
    $maskraz=-1;
    $maskoffset=0;
    if (dol_strlen($maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';

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
        if (dol_strlen($maskrefclient_maskcounter) > 0 && dol_strlen($maskrefclient_maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';
    }
    else $maskrefclient='';

    // Extract value for third party type
    if (preg_match('/\{(t+)\}/i',$mask,$regType))
    {
        $masktype=$regType[1];
        $masktype_value=substr(preg_replace('/^TE_/','',$objsoc->typent_code),0,dol_strlen($regType[1]));//get n first characters of client code where n is length in mask
        $masktype_value=str_pad($masktype_value,dol_strlen($regType[1]),"#",STR_PAD_RIGHT);
    }
    else
    {
    	$masktype='';
    	$masktype_value='';
    }

    $maskwithonlyymcode=$mask;
    $maskwithonlyymcode=preg_replace('/\{(0+)([@\+][0-9\-\+\=]+)?([@\+][0-9\-\+\=]+)?\}/i',$maskcounter,$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{dd\}/i','dd',$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{(c+)(0*)\}/i',$maskrefclient,$maskwithonlyymcode);
    $maskwithonlyymcode=preg_replace('/\{(t+)\}/i',$masktype_value,$maskwithonlyymcode);
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

    if ($maskraz > 0)    // A reset is required
    {
    	if ($maskraz == 99) {
			$maskraz = date('m');
			$resetEveryMonth = true;
		}
        if ($maskraz > 12) return 'ErrorBadMaskBadRazMonth';

        // Define posy, posm and reg
        if ($maskraz > 1)
        {
            if (! preg_match('/^(.*)\{(y+)\}\{(m+)\}/i',$maskwithonlyymcode)
            && ! preg_match('/^(.*)\{(m+)\}\{(y+)\}/i',$maskwithonlyymcode)) return 'ErrorCantUseRazInStartedYearIfNoYearMonthInMask';
            if (preg_match('/^(.*)\{(y+)\}\{(m+)\}/i',$maskwithonlyymcode,$reg)) { $posy=2; $posm=3; }
            elseif (preg_match('/^(.*)\{(m+)\}\{(y+)\}/i',$maskwithonlyymcode,$reg)) { $posy=3; $posm=2; }
            if (dol_strlen($reg[$posy]) < 2) return 'ErrorCantUseRazWithYearOnOneDigit';
        }
        else
        {
            if (! preg_match('/^(.*)\{(y+)\}/i',$maskwithonlyymcode)) return 'ErrorCantUseRazIfNoYearInMask';
            if (preg_match('/^(.*)\{(y+)\}/i',$maskwithonlyymcode,$reg)) { $posy=2; $posm=0; }
        }
        //print "x".$maskwithonlyymcode." ".$maskraz." ".$posy." ".$posm;
		//var_dump($reg);

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

        $yearlen = dol_strlen($reg[$posy]);
        if ($yearlen == 4) $yearcomp=sprintf("%04d",date("Y",$date)+$yearoffset);
        elseif ($yearlen == 2) $yearcomp=sprintf("%02d",date("y",$date)+$yearoffset);
        elseif ($yearlen == 1) $yearcomp=substr(date("y",$date),2,1)+$yearoffset;
        if ($monthcomp > 1 && empty($resetEveryMonth))	// Test with month is useless if monthcomp = 0 or 1 (0 is same as 1) (regis: $monthcomp can't equal 0)
        {
            if ($yearlen == 4) $yearcomp1=sprintf("%04d",date("Y",$date)+$yearoffset+1);
            elseif ($yearlen == 2) $yearcomp1=sprintf("%02d",date("y",$date)+$yearoffset+1);

            $monthlen = dol_strlen($reg[$posm]);
            $yearpos = (dol_strlen($reg[1])+1);
            $monthpos = ($yearpos+$yearlen);
            if ($posy == 3) {
            	$monthpos = (dol_strlen($reg[1])+1);
            	$yearpos = ($monthpos+$monthlen);
            }

            $sqlwhere.="(";
            $sqlwhere.=" (SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$yearcomp."'";
            $sqlwhere.=" AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") >= '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."')";
            $sqlwhere.=" OR";
            $sqlwhere.=" (SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$yearcomp1."'";
            $sqlwhere.=" AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") < '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."') ";
            $sqlwhere.=')';
        }
		else if($resetEveryMonth) {
			$monthlen = dol_strlen($reg[$posm]);
            $yearpos = (dol_strlen($reg[1])+1);
            $monthpos = ($yearpos+$yearlen);
            if ($posy == 3) {
            	$monthpos = (dol_strlen($reg[1])+1);
            	$yearpos = ($monthpos+$monthlen);
            }
			$sqlwhere.=" SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$yearcomp."'";
            $sqlwhere.=" AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") = '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."' ";
		}
        else   // reset is done on january
        {
            $sqlwhere.='(SUBSTRING('.$field.', '.(dol_strlen($reg[1])+1).', '.dol_strlen($reg[2]).") = '".$yearcomp."')";
        }
    }
    //print "sqlwhere=".$sqlwhere." yearcomp=".$yearcomp."<br>\n";	// sqlwhere and yearcomp defined only if we ask a reset
    //print "masktri=".$masktri." maskcounter=".$maskcounter." maskraz=".$maskraz." maskoffset=".$maskoffset."<br>\n";

    // Define $sqlstring
    $posnumstart=strpos($maskwithnocode,$maskcounter);	// Pos of counter in final string (from 0 to ...)
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

    // Get counter in database
    $counter=0;
    $sql = "SELECT MAX(".$sqlstring.") as val";
    $sql.= " FROM ".MAIN_DB_PREFIX.$table;
    $sql.= " WHERE ".$field." LIKE '".$maskLike."'";
    $sql.= " AND ".$field." NOT LIKE '%PROV%'";
    $sql.= " AND entity IN (".getEntity($table, 1).")";
    if ($where) $sql.=$where;
    if ($sqlwhere) $sql.=' AND '.$sqlwhere;

    //print $sql.'<br>';
    dol_syslog("functions2::get_next_value mode=".$mode." sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $obj = $db->fetch_object($resql);
        $counter = $obj->val;
    }
    else dol_print_error($db);

    // Check if we must force counter to maskoffset
    if (empty($counter) || preg_match('/[^0-9]/i',$counter)) $counter=$maskoffset;
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

        $ref='';
        $sql = "SELECT ".$field." as ref";
        $sql.= " FROM ".MAIN_DB_PREFIX.$table;
        $sql.= " WHERE ".$field." LIKE '".$maskLike."'";
    	$sql.= " AND ".$field." NOT LIKE '%PROV%'";
        $sql.= " AND entity IN (".getEntity($table, 1).")";
        if ($where) $sql.=$where;
        if ($sqlwhere) $sql.=' AND '.$sqlwhere;

        dol_syslog("functions2::get_next_value sql=".$sql);
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
            $maskrefclient_sql.= " AND entity IN (".getEntity($table, 1).")";
            if ($where) $maskrefclient_sql.=$where; //use the same optional where as general mask
            if ($sqlwhere) $maskrefclient_sql.=' AND '.$sqlwhere; //use the same sqlwhere as general mask
            $maskrefclient_sql.=' AND (SUBSTRING('.$field.', '.(strpos($maskwithnocode,$maskrefclient)+1).', '.dol_strlen($maskrefclient_maskclientcode).")='".$maskrefclient_clientcode."')";

            dol_syslog("functions2::get_next_value maskrefclient_sql=".$maskrefclient_sql, LOG_DEBUG);
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
        	$numFinal = preg_replace('/\{y\}/i',   substr(date("y",$date),2,1)+$yearoffset, $numFinal);
		}
		else	// we want yyyy to be current year
		{
        	$numFinal = preg_replace('/\{yyyy\}/i',date("Y",$date), $numFinal);
        	$numFinal = preg_replace('/\{yy\}/i',  date("y",$date), $numFinal);
        	$numFinal = preg_replace('/\{y\}/i',   substr(date("y",$date),2,1), $numFinal);
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
            //print "maskrefclient=".$maskrefclient." maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";
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
    }

    dol_syslog("functions2::get_next_value return ".$numFinal,LOG_DEBUG);
    return $numFinal;
}

/**
 * Check value
 *
 * @param 	string	$mask		Mask to use
 * @param 	string	$value		Value
 * @return	int     		    <0 if KO, 0 if OK
 */
function check_value($mask,$value)
{
    $result=0;

    // Extract value for mask counter, mask raz and mask offset
    if (! preg_match('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i',$mask,$reg)) return 'ErrorBadMask';
    $masktri=$reg[1].(isset($reg[2])?$reg[2]:'').(isset($reg[3])?$reg[3]:'');
    $maskcounter=$reg[1];
    $maskraz=-1;
    $maskoffset=0;
    if (dol_strlen($maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';

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
        if (dol_strlen($maskrefclient_maskcounter) > 0 && dol_strlen($maskrefclient_maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';
    }
    else $maskrefclient='';

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
    if (! empty($reg[3]) && preg_match('^\+',$reg[3])) $maskoffset=preg_replace('/^\+/','',$reg[3]);

    // Define $sqlwhere

    // If a restore to zero after a month is asked we check if there is already a value for this year.
    if (! empty($reg[2]) && preg_match('/^@/',$reg[2]))  $maskraz=preg_replace('/^@/','',$reg[2]);
    if (! empty($reg[3]) && preg_match('/^@/',$reg[3]))  $maskraz=preg_replace('/^@/','',$reg[3]);
    if ($maskraz >= 0)
    {
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
 *	@return int					Number of week
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
 *	@param  int		&$from_unit 		Unite originale en puissance de 10
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
 *	@param	User	&$user      Object user
 *	@param	array	$tab        Tableau (cle=>valeur) des parametres a sauvegarder
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
        $sql.="'".$key."'";
        $i++;
    }
    $sql.= ")";
    dol_syslog("functions2.lib::dol_set_user_param sql=".$sql, LOG_DEBUG);

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
            $sql.= " '".$key."','".$db->escape($value)."')";

            dol_syslog("functions2.lib::dol_set_user_param sql=".$sql, LOG_DEBUG);
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
        $string = $langs->trans("Offered");
    }
    else
    {
        $string = $reduction.'%';
    }

    return $string;
}

/**
 * 	Return OS version.
 *  Note that PHP_OS returns only OS (not version) and OS PHP was built on, not
 *  necessarly OS PHP runs on.
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
 */
function version_php()
{
    return phpversion();
}

/**
 * 	Return Dolibarr version
 *
 * 	@return		string			Dolibarr version
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
    $sql.= " AND entity IN (0,".(! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)?"1,":"").$conf->entity.")";

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
            if (! empty($obj->description))	// List of directories to scan is defined
            {
                include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                $const=$obj->description;
                $dirtoscan.=($dirtoscan?',':'').preg_replace('/[\r\n]+/',',',trim($conf->global->$const));

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
                        $tmpfiles=dol_dir_list($tmpdir,'files',0,'\.od(s|t)$','','name',SORT_ASC,0,true); // Disable hook for the moment
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
                $liste[$obj->id]=$obj->label?$obj->label:$obj->lib;
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
 *
 * @param	string		$ip			IP Address
 * @return	It returns 0 if $ip is not a valid IPv4
 * 			It returns 1 if $ip is a valid IPv4 and is a public IP
 * 			It returns 2 if $ip is a valid IPv4 and is a private lan IP
 */
function is_ip($ip)
{
    if (!preg_match("/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/", $ip)) return 0;
    if (sprintf("%u",ip2long($ip)) == sprintf("%u",ip2long('255.255.255.255'))) return 0;
    if (sprintf("%u",ip2long('10.0.0.0')) <= sprintf("%u",ip2long($ip)) and sprintf("%u",ip2long($ip)) <= sprintf("%u",ip2long('10.255.255.255'))) return 2;
    if (sprintf("%u",ip2long('172.16.0.0')) <= sprintf("%u",ip2long($ip)) and sprintf("%u",ip2long($ip)) <= sprintf("%u",ip2long('172.31.255.255'))) return 2;
    if (sprintf("%u",ip2long('192.168.0.0')) <= sprintf("%u",ip2long($ip)) and sprintf("%u",ip2long($ip)) <= sprintf("%u",ip2long('192.168.255.255'))) return 2;
    if (sprintf("%u",ip2long('169.254.0.0')) <= sprintf("%u",ip2long($ip)) and sprintf("%u",ip2long($ip)) <= sprintf("%u",ip2long('169.254.255.255'))) return 2;
    return 1;
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
 *  @return     param
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
