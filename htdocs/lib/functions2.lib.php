<?php
/* Copyright (C) 2008 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2008 Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/lib/functions2.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all rare functions.
 *	\version		$Id$
 */


/**
 *  \brief      Renvoi le fichier $filename dans la version de la langue courante, sinon alternative
 *  \param      filename        nom du fichier a rechercher
 *  \param      searchalt       cherche aussi dans langue alternative
 *	\return		boolean
 */
function dol_print_file($langs,$filename,$searchalt=0)
{
	global $conf;

	// Test if file is in lang directory
	foreach($langs->dir as $searchdir)
	{
		$htmlfile=($searchdir."/langs/".$langs->defaultlang."/".$filename);
		dol_syslog('Translate::print_file search file '.$htmlfile, LOG_DEBUG);
		if (is_readable($htmlfile))
		{
			$content=file_get_contents($htmlfile);
			$isutf8=utf8_check($content);
			if (! $isutf8 && $conf->file->character_set_client == 'UTF-8') print utf8_encode($content);
			elseif ($isutf8 && $conf->file->character_set_client == 'ISO-8859-1') print utf8_decode($content);
			else print $content;
			return true;
		}
		else dol_syslog('Translate::print_file not found', LOG_DEBUG);

		if ($searchalt) {
			// Test si fichier dans repertoire de la langue alternative
			if ($langs->defaultlang != "en_US") $htmlfilealt = $searchdir."/langs/en_US/".$filename;
			else $htmlfilealt = $searchdir."/langs/fr_FR/".$filename;
			dol_syslog('Translate::print_file search alt file '.$htmlfilealt, LOG_DEBUG);
			//print 'getcwd='.getcwd().' htmlfilealt='.$htmlfilealt.' X '.file_exists(getcwd().'/'.$htmlfilealt);
			if (is_readable($htmlfilealt))
			{
				$content=file_get_contents($htmlfilealt);
				$isutf8=utf8_check($content);
				if (! $isutf8 && $conf->file->character_set_client == 'UTF-8') print utf8_encode($content);
				elseif ($isutf8 && $conf->file->character_set_client == 'ISO-8859-1') print utf8_decode($content);
				else print $content;
				return true;
			}
			else dol_syslog('Translate::print_file not found', LOG_DEBUG);
		}
	}

	return false;
}

/**
 *	\brief  Show informations on an object
 *	\param	object			Objet to show
 */
function dol_print_object_info($object)
{
	global $langs,$db;
	$langs->load("other");

	// Import key
	if (isset($object->import_key))
	print $langs->trans("ImportedWithSet")." : " . $object->import_key . '<br>';

	// User creation
	if (isset($object->user_creation))
	{
		print $langs->trans("CreatedBy")." : ";
		if (is_object($object->user_creation))
		{
		 	print $object->user_creation->getNomUrl(1);
		}
		else
		{
			$userstatic=new User($db);
			$userstatic->id=$object->user_creation;
			$userstatic->fetch();
			print $userstatic->getNomUrl(1);
		}
		print '<br>';
	}

	// Date
	if (isset($object->date_creation))
	print $langs->trans("DateCreation")." : " . dol_print_date($object->date_creation,"dayhourtext") . '<br>';

	// User change
	if (isset($object->user_modification))
	{
		print $langs->trans("ModifiedBy")." : ";
		if (is_object($object->user_modification))
		{
			print $object->user_modification->getNomUrl(1);
		}
		else
		{
			$userstatic=new User($db);
			$userstatic->id=$object->user_modification;
			$userstatic->fetch();
			print $userstatic->getNomUrl(1);
		}
		print '<br>';
	}

	// Date
	if (isset($object->date_modification))
	print $langs->trans("DateLastModification")." : " . dol_print_date($object->date_modification,"dayhourtext") . '<br>';

	// User validation
	if (isset($object->user_validation))
	{
		print $langs->trans("ValidatedBy")." : ";
		if (is_object($object->user_validation))
		{
			print $object->user_validation->getNomUrl(1);
		}
		else
		{
			$userstatic=new User($db);
			$userstatic->id=$object->user_validation;
			$userstatic->fetch();
			print $userstatic->getNomUrl(1);
		}
		print '<br>';
	}

	// Date
	if (isset($object->date_validation))
	print $langs->trans("DateValidation")." : " . dol_print_date($object->date_validation,"dayhourtext") . '<br>';

	// User close
	if (isset($object->user_cloture))
	{
		print $langs->trans("ClosedBy")." : ";
		if (is_object($object->user_cloture))
		{
			print $object->user_cloture->getNomUrl(1);
		}
		else
		{
			$userstatic=new User($db);
			$userstatic->id=$object->user_cloture;
			$userstatic->fetch();
			print $userstatic->getNomUrl(1);
		}
		print '<br>';
	}

	// Date
	if (isset($object->date_cloture))
	print $langs->trans("DateClosing")." : " . dol_print_date($object->date_cloture,"dayhourtext") . '<br>';

	// User conciliate
	if (isset($object->user_rappro))
	{
		print $langs->trans("ConciliatedBy")." : ";
		if (is_object($object->user_rappro))
		{
			print $object->user_rappro->getNomUrl(1);
		}
		else
		{
			$userstatic=new User($db);
			$userstatic->id=$object->user_rappro;
			$userstatic->fetch();
			print $userstatic->getNomUrl(1);
		}
		print '<br>';
	}

	// Date
	if (isset($object->date_rappro))
	print $langs->trans("DateConciliating")." : " . dol_print_date($object->date_rappro,"dayhourtext") . '<br>';

	//Date send
	if (isset($object->date_envoi))
	print $langs->trans("DateLastSend")." : " . dol_print_date($object->date_envoi,"dayhourtext") . '<br>';
}


/**
 *	\brief      Return true if email has a domain name that can't be resolved
 *	\param	    mail        adresse email (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
 *	\return     boolean     true if domain email is OK, false if KO
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
 *	\brief   	Url string validation
 *  \remarks  	<http[s]> :// [user[:pass]@] hostname [port] [/path] [?getquery] [anchor]
 *	\param   	url			Url
 *  \param   	http		1: verify http, 0: not verify http
 *  \param   	pass		1: verify user and pass, 0: not verify user and pass
 *  \param   	port		1: verify port, 0: not verify port
 *  \param   	path		1: verify path, 0: not verify path
 *  \param   	query		1: verify query, 0: not verify query
 *  \param   	anchor		1: verify anchor, 0: not verify anchor
 *	\return  	int			1=Check is OK, 0=Check is KO
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
 *	\brief   	Clean an url string
 *	\param   	url			Url
 *	\param   	http		1: keep http://, 0: remove also http://
 *	\return  	string	    Cleaned url
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
}


/**
 * 	\brief		Return lines of an html table from an array
 * 	\remarks	Used by array2table function only
 */
function array2tr($data,$troptions='',$tdoptions=''){
	$text = '<tr '.$troptions.'>' ;
	foreach($data as $key => $item){
		$text.= '<td '.$tdoptions.'>'.$item.'</td>' ;
	}
	$text.= '</tr>' ;
	return $text ;
}

/**
 * 	\brief	Return an html table from an array
 */
function array2table($data,$tableMarkup=1,$tableoptions='',$troptions='',$tdoptions=''){
	$text='' ;
	if($tableMarkup) $text = '<table '.$tableoptions.'>' ;
	foreach($data as $key => $item){
		if(is_array($item)){
			$text.=array2tr($item,$troptions,$tdoptions) ;
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
 * Return next value for a mask
 *
 * @param unknown_type 	$db				Database handler
 * @param 				$mask			Mask to use
 * @param unknown_type 	$table			Table containing field with counter
 * @param unknown_type 	$field			Field containing already used values of counter
 * @param unknown_type 	$where			To add a filter on selection (for exemple to filter on invoice types)
 * @param unknown_type 	$objsoc			The company that own the object we need a counter for
 * @param unknown_type 	$date			Date to use for the {y},{m},{d} tags.
 * @return 	string		New value
 */
function get_next_value($db,$mask,$table,$field,$where='',$objsoc='',$date='')
{
	global $conf;

	if (! is_object($objsoc)) $valueforccc=$objsoc;
	else $valueforccc=$objsoc->code_client;

	// Clean parameters
	if ($date == '') $date=mktime();	// We use local year and month of PHP server to search numbers
	// but we should use local year and month of user

	// Extract value for mask counter, mask raz and mask offset
	if (! preg_match('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i',$mask,$reg)) return 'ErrorBadMask';
	$masktri=$reg[1].$reg[2].$reg[3];
	$maskcounter=$reg[1];
	$maskraz=-1;
	$maskoffset=0;
	if (strlen($maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';

	// Extract value for third party mask counter
	if (preg_match('/\{(c+)(0*)\}/i',$mask,$regClientRef))
	{
		$maskrefclient=$regClientRef[1].$regClientRef[2];
		$maskrefclient_maskclientcode=$regClientRef[1];
		$maskrefclient_maskcounter=$regClientRef[2];
		$maskrefclient_maskoffset=0; //default value of maskrefclient_counter offset
		$maskrefclient_clientcode=substr($valueforccc,0,strlen($maskrefclient_maskclientcode));//get n first characters of client code where n is length in mask
		$maskrefclient_clientcode=str_pad($maskrefclient_clientcode,strlen($maskrefclient_maskclientcode),"#",STR_PAD_RIGHT);//padding maskrefclient_clientcode for having exactly n characters in maskrefclient_clientcode
		$maskrefclient_clientcode=dol_string_nospecial($maskrefclient_clientcode);//sanitize maskrefclient_clientcode for sql insert and sql select like
		if (strlen($maskrefclient_maskcounter) > 0 && strlen($maskrefclient_maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';
	}
	else $maskrefclient='';

	// Extract value for third party type
	if (preg_match('/\{(t+)\}/i',$mask,$regType))
	{
		$masktype=$regType[1];
		$masktype_value=substr(preg_replace('/^TE_/','',$objsoc->typent_code),0,strlen($regType[1]));//get n first characters of client code where n is length in mask
		$masktype_value=str_pad($masktype_value,strlen($regType[1]),"#",STR_PAD_RIGHT);
	}
	else $masktype='';

	$maskwithonlyymcode=$mask;
	$maskwithonlyymcode=preg_replace('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i',$maskcounter,$maskwithonlyymcode);
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

	// If an offset is asked
	if (! empty($reg[2]) && preg_match('/^\+/',$reg[2])) $maskoffset=preg_replace('/^\+/','',$reg[2]);
	if (! empty($reg[3]) && preg_match('/^\+/',$reg[3])) $maskoffset=preg_replace('/^\+/','',$reg[3]);

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

		// Define $yearcomp and $monthcomp (that will be use in the select where to search max number)
		$monthcomp=$maskraz;
		$yearoffset=0;
		$yearcomp=0;
		if (date("m",$date) < $maskraz) { $yearoffset=-1; }	// If current month lower that month of return to zero, year is previous year
		if (strlen($reg[2]) == 4) $yearcomp=sprintf("%04d",date("Y",$date)+$yearoffset);
		if (strlen($reg[2]) == 2) $yearcomp=sprintf("%02d",date("y",$date)+$yearoffset);
		if (strlen($reg[2]) == 1) $yearcomp=substr(date("y",$date),2,1)+$yearoffset;

		$sqlwhere='';
		$sqlwhere.='( (SUBSTRING('.$field.', '.(strlen($reg[1])+1).', '.strlen($reg[2]).') >= '.$yearcomp;
		if ($monthcomp > 1)	// Test useless if monthcomp = 1 (or 0 is same as 1)
		{
			$sqlwhere.=' AND SUBSTRING('.$field.', '.(strlen($reg[1])+strlen($reg[2])+1).', '.strlen($reg[3]).') >= '.$monthcomp.')';
			$sqlwhere.=' OR SUBSTRING('.$field.', '.(strlen($reg[1])+1).', '.strlen($reg[2]).') >= '.sprintf("%02d",($yearcomp+1)).' )';
		}
		else
		{
			$sqlwhere.=') )';
		}
	}
	//print "masktri=".$masktri." maskcounter=".$maskcounter." maskraz=".$maskraz." maskoffset=".$maskoffset."<br>\n";

	// Define $sqlstring
	$posnumstart=strpos($maskwithnocode,$maskcounter);	// Pos of counter in final string (from 0 to ...)
	if ($posnumstart < 0) return 'ErrorBadMaskFailedToLocatePosOfSequence';
	$sqlstring='SUBSTRING('.$field.', '.($posnumstart+1).', '.strlen($maskcounter).')';
	//print "x".$sqlstring;

	// Define $maskLike
	$maskLike = dol_string_nospecial($mask);
	$maskLike = str_replace("%","_",$maskLike);
	// Replace protected special codes with matching number of _ as wild card caracter
	$maskLike = preg_replace('/\{yyyy\}/i','____',$maskLike);
	$maskLike = preg_replace('/\{yy\}/i','__',$maskLike);
	$maskLike = preg_replace('/\{y\}/i','_',$maskLike);
	$maskLike = preg_replace('/\{mm\}/i','__',$maskLike);
	$maskLike = preg_replace('/\{dd\}/i','__',$maskLike);
	$maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'),str_pad("",strlen($maskcounter),"_"),$maskLike);
	if ($maskrefclient) $maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'),str_pad("",strlen($maskrefclient),"_"),$maskLike);
	//if ($masktype) $maskLike = str_replace(dol_string_nospecial('{'.$masktype.'}'),str_pad("",strlen($masktype),"_"),$maskLike);
	if ($masktype) $maskLike = str_replace(dol_string_nospecial('{'.$masktype.'}'),$masktype_value,$maskLike);

	// Get counter in database
	$counter=0;
	$sql = "SELECT MAX(".$sqlstring.") as val";
	$sql.= " FROM ".MAIN_DB_PREFIX.$table;
	//		$sql.= " WHERE ".$field." not like '(%'";
	$sql.= " WHERE ".$field." LIKE '".$maskLike."'";
	$sql.= " AND ".$field." NOT LIKE '%PROV%'";
	$sql.= " AND entity = ".$conf->entity;
	if ($where) $sql.=$where;
	if ($sqlwhere) $sql.=' AND '.$sqlwhere;

	//print $sql.'<br>';
	dol_syslog("functions2::get_next_value sql=".$sql, LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		$counter = $obj->val;
	}
	else dol_print_error($db);
	if (empty($counter) || preg_match('/[^0-9]/i',$counter)) $counter=$maskoffset;
	$counter++;

	if ($maskrefclient_maskcounter)
	{
		//print "maskrefclient_maskcounter=".$maskrefclient_maskcounter." maskwithnocode=".$maskwithnocode." maskrefclient=".$maskrefclient."\n<br>";

		// Define $sqlstring
		$maskrefclient_posnumstart=strpos($maskwithnocode,$maskrefclient_maskcounter,strpos($maskwithnocode,$maskrefclient));	// Pos of counter in final string (from 0 to ...)
		if ($maskrefclient_posnumstart <= 0) return 'ErrorBadMask';
		$maskrefclient_sqlstring='SUBSTRING('.$field.', '.($maskrefclient_posnumstart+1).', '.strlen($maskrefclient_maskcounter).')';
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
		$maskrefclient_maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'),str_pad("",strlen($maskcounter),"_"),$maskrefclient_maskLike);
		$maskrefclient_maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'),$maskrefclient_clientcode.str_pad("",strlen($maskrefclient_maskcounter),"_"),$maskrefclient_maskLike);

		// Get counter in database
		$maskrefclient_counter=0;
		$maskrefclient_sql = "SELECT MAX(".$maskrefclient_sqlstring.") as val";
		$maskrefclient_sql.= " FROM ".MAIN_DB_PREFIX.$table;
		//$sql.= " WHERE ".$field." not like '(%'";
		$maskrefclient_sql.= " WHERE ".$field." LIKE '".$maskrefclient_maskLike."'";
		$maskrefclient_sql.= " AND entity = ".$conf->entity;
		if ($where) $maskrefclient_sql.=$where; //use the same optional where as general mask
		if ($sqlwhere) $maskrefclient_sql.=' AND '.$sqlwhere; //use the same sqlwhere as general mask
		$maskrefclient_sql.=' AND (SUBSTRING('.$field.', '.(strpos($maskwithnocode,$maskrefclient)+1).', '.strlen($maskrefclient_maskclientcode).")='".$maskrefclient_clientcode."')";

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
	$numFinal = preg_replace('/\{yyyy\}/i',date("Y",$date),$numFinal);
	$numFinal = preg_replace('/\{yy\}/i',date("y",$date),$numFinal);
	$numFinal = preg_replace('/\{y\}/i' ,substr(date("y",$date),2,1),$numFinal);
	$numFinal = preg_replace('/\{mm\}/i',date("m",$date),$numFinal);
	$numFinal = preg_replace('/\{dd\}/i',date("d",$date),$numFinal);

	// Now we replace the counter
	$maskbefore='{'.$masktri.'}';
	$maskafter=str_pad($counter,strlen($maskcounter),"0",STR_PAD_LEFT);
	//print 'x'.$maskbefore.'-'.$maskafter.'y';
	$numFinal = str_replace($maskbefore,$maskafter,$numFinal);

	// Now we replace the refclient
	if ($maskrefclient)
	{
		//print "maskrefclient=".$maskrefclient." maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";
		$maskrefclient_maskbefore='{'.$maskrefclient.'}';
		$maskrefclient_maskafter=$maskrefclient_clientcode.str_pad($maskrefclient_counter,strlen($maskrefclient_maskcounter),"0",STR_PAD_LEFT);
		$numFinal = str_replace($maskrefclient_maskbefore,$maskrefclient_maskafter,$numFinal);
	}

	// Now we replace the type
	if ($masktype)
	{
		$masktype_maskbefore='{'.$masktype.'}';
		$masktype_maskafter=$masktype_value;
		$numFinal = str_replace($masktype_maskbefore,$masktype_maskafter,$numFinal);
	}

	dol_syslog("functions2::get_next_value return ".$numFinal,LOG_DEBUG);
	return $numFinal;
}


/**
 * Check value
 *
 * @param unknown_type 	$db				Database handler
 * @param 				$mask			Mask to use
 * @param unknown_type 	$table			Table containing field with counter
 * @param unknown_type 	$field			Field containing already used values of counter
 * @param unknown_type 	$where			To add a filter on selection (for exemple to filter on invoice types)
 * @param unknown_type 	$valueforccc
 * @param unknown_type 	$date
 * @return		int			<0 if KO, 0 if OK
 */
function check_value($mask,$value)
{
	$result=0;

	// Extract value for mask counter, mask raz and mask offset
	if (! preg_match('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i',$mask,$reg)) return 'ErrorBadMask';
	$masktri=$reg[1].$reg[2].$reg[3];
	$maskcounter=$reg[1];
	$maskraz=-1;
	$maskoffset=0;
	if (strlen($maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';

	// Extract value for third party mask counter
	if (preg_match('/\{(c+)(0*)\}/i',$mask,$regClientRef))
	{
		$maskrefclient=$regClientRef[1].$regClientRef[2];
		$maskrefclient_maskclientcode=$regClientRef[1];
		$maskrefclient_maskcounter=$regClientRef[2];
		$maskrefclient_maskoffset=0; //default value of maskrefclient_counter offset
		$maskrefclient_clientcode=substr($valueforccc,0,strlen($maskrefclient_maskclientcode));//get n first characters of client code to form maskrefclient_clientcode
		$maskrefclient_clientcode=str_pad($maskrefclient_clientcode,strlen($maskrefclient_maskclientcode),"#",STR_PAD_RIGHT);//padding maskrefclient_clientcode for having exactly n characters in maskrefclient_clientcode
		$maskrefclient_clientcode=dol_string_nospecial($maskrefclient_clientcode);//sanitize maskrefclient_clientcode for sql insert and sql select like
		if (strlen($maskrefclient_maskcounter) > 0 && strlen($maskrefclient_maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';
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

	// Check we have a number in ($posnumstart+1).', '.strlen($maskcounter)
	//

	// Check length
	$len=strlen($maskwithnocode);
	if (strlen($value) != $len) $result=-1;

	// Define $maskLike
	$maskLike = dol_string_nospecial($mask);
	$maskLike = str_replace("%","_",$maskLike);
	// Replace protected special codes with matching number of _ as wild card caracter
	$maskLike = str_replace(dol_string_nospecial('{yyyy}'),'____',$maskLike);
	$maskLike = str_replace(dol_string_nospecial('{yy}'),'__',$maskLike);
	$maskLike = str_replace(dol_string_nospecial('{y}'),'_',$maskLike);
	$maskLike = str_replace(dol_string_nospecial('{mm}'),'__',$maskLike);
	$maskLike = str_replace(dol_string_nospecial('{dd}'),'__',$maskLike);
	$maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'),str_pad("",strlen($maskcounter),"_"),$maskLike);
	if ($maskrefclient) $maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'),str_pad("",strlen($maskrefclient),"_"),$maskLike);


	dol_syslog("functions2::check_value result=".$result,LOG_DEBUG);
	return $result;
}


/**
 *	\brief   Convert a binary data to string that represent hexadecimal value
 *	\param   bin		Value to convert
 *	\param   pad      	Add 0
 *	\param   upper		Convert to tupper
 *	\return  string		x
 */
function binhex($bin, $pad=false, $upper=false)
{
	$last = strlen($bin)-1;
	for($i=0; $i<=$last; $i++){ $x += $bin[$last-$i] * pow(2,$i); }
	$x = dechex($x);
	if($pad){ while(strlen($x) < intval(strlen($bin))/4){ $x = "0$x"; } }
	if($upper){ $x = strtoupper($x); }
	return $x;
}


/**
 *	\brief   Convertir de l'hexadecimal en binaire
 *	\param   string     hexa
 *	\return  string	    bin
 */
function hexbin($hexa)
{
	$bin='';
	for($i=0;$i<strlen($hexa);$i++)
	{
		$bin.=str_pad(decbin(hexdec($hexa{$i})),4,'0',STR_PAD_LEFT);
	}
	return $bin;
}


/**
 *	\brief      Return if a filename is file name of a supported image format
 *	\param      file		Filename
 *	\return		int			-1=Not image filename, 0=Image filename but format not supported by PHP, 1=Image filename with format supported
 */
function image_format_supported($file)
{
	// Case filename is not a format image
	if (! preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$file,$reg)) return -1;

	// Case filename is a format image but not supported by this PHP
	$imgfonction='';
	if (strtolower($reg[1]) == '.gif')  $imgfonction = 'imagecreatefromgif';
	if (strtolower($reg[1]) == '.png')  $imgfonction = 'imagecreatefrompng';
	if (strtolower($reg[1]) == '.jpg')  $imgfonction = 'imagecreatefromjpeg';
	if (strtolower($reg[1]) == '.jpeg') $imgfonction = 'imagecreatefromjpeg';
	if (strtolower($reg[1]) == '.bmp')  $imgfonction = 'imagecreatefromwbmp';
	if ($imgfonction)
	{
		if (! function_exists($imgfonction))
		{
			// Fonctions de conversion non presente dans ce PHP
			return 0;
		}
	}

	// Filename is a format image and supported by this PHP
	return 1;
}


/**
 *	\brief   Retourne le numero de la semaine par rapport a une date
 *	\param   time   	Date au format 'timestamp'
 *	\return  int		Numero de semaine
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
 *	\brief   Convertit une masse d'une unite vers une autre unite
 *	\param   weight    float	Masse a convertir
 *	\param   from_unit int     Unite originale en puissance de 10
 *	\param   to_unit   int     Nouvelle unite  en puissance de 10
 *	\return  float	        Masse convertie
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
 *	\brief      Save personnal parameter
 *	\param	    db          Handler database
 *	\param	    user        Object user
 *	\param	    tab         Tableau (cle=>valeur) des parametres a sauvegarder
 *	\return     int         <0 if KO, >0 if OK
 */
function dol_set_user_param($db, $conf, &$user, $tab)
{
	// Verification parametres
	if (sizeof($tab) < 1) return -1;

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
		if ($value && (! $url || in_array($key,array('sortfield','sortorder','begin','page'))))
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,entity,param,value)";
			$sql.= " VALUES (".$user->id.",".$conf->entity.",";
			$sql.= " '".$key."','".addslashes($value)."');";
			dol_syslog("functions2.lib::dol_set_user_param sql=".$sql, LOG_DEBUG);

			$result=$db->query($sql);
			if (! $result)
			{
				dol_print_error($db);
				$db->rollback();
				return -1;
			}

			$user->page_param[$key] = $value;
		}
	}

	$db->commit();
	return 1;
}


/**
 *	\brief  	Returns formated reduction
 *	\param		reduction		Reduction percentage
 *	\return		string			Formated reduction
 */
function dol_print_reduction($reduction=0,$langs)
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
 * 	\brief		Return OS version
 * 	\return		string			OS version
 */
function version_os()
{
	// Get version of OS
	ob_start();
	phpinfo();
	$chaine = ob_get_contents();
	ob_end_clean();
	preg_match('/System <\/td><td class="v">([^<]*)<\/td>/i',$chaine,$reg);
	$osversion=$reg[1];
	return $osversion;
}

/**
 * 	\brief		Return PHP version
 * 	\return		string			PHP version
 */
function version_php()
{
	return phpversion();
}

/**
 * 	\brief		Return Dolibarr version
 * 	\return		string			Dolibarr version
 */
function version_dolibarr()
{
	return DOL_VERSION;
}

/**
 * 	\brief		Return web server version
 * 	\return		string			Web server version
 */
function version_webserver()
{
	return $_SERVER["SERVER_SOFTWARE"];
}
